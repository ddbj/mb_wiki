<?php
/**
  HTMLFormFunctions.php Mediawiki extension. Enable HTML form-related tags(form, input, textarea, etc).
  Last-modified: 2023.03.16

  @version 1.0
  @author K, Suwa
  @link http://mb.metabolomics.jp/wiki/Help:Extension/HTMLFormFunctions
*/

use MediaWiki\MediaWikiServices;

class HTMLFormFunctions 
{
    /** @var string parameter prefix  */
	public static string $PARAM_PREFIX = 'my_';

	/**
	 * Replace with allowed tags
	 * e.g. {{#formtag:input|type="submit"}} => <input type="submit">
	 *      {{#formtag:form|action="-"|...}} => <form action="-" method="post">...</form>
	 *
	 * @param Parser $parser parent parser
	 * @param string $tag    HTML tag
	 * @param string $arg    HTML tag parameter
	 * @param string $input  data which be nipped HTML tag
	 * @return string which parse by HTMLForm.php 
	*/
	function formtag( &$parser, $tag, $arg = '', $input = '' ) {
		$html = "";

		if( strncmp( $tag, 'input', 6) == 0 ){
			$html = '<input ' . $arg . ' />';

		} else if( strncmp( $tag, 'form', 5 ) == 0 || strncmp( $tag, 'textarea', 9 ) == 0 ||
				strncmp( $tag, 'select', 7 ) == 0 || strncmp( $tag, 'option', 7 ) == 0 ||
				strncmp( $tag, 'optgroup', 9 ) == 0 || strncmp( $tag, 'fieldset', 9 ) == 0 ||
				strncmp( $tag, 'legend', 7 ) == 0 || strncmp( $tag, 'label', 6 ) == 0 ||
				strncmp( $tag, 'button', 7 ) == 0){
			$html = '<' . $tag . ' ' . $arg . '>' . $input . '</' . $tag . '>';
		}

		return array( $parser->preprocessToDom( $html ), 'isChildObj' => true );
	}

	/**
	 * Get posted data.
	 * e.g. {{#get:data}} => abcdef
	 *
	 * @param Parser $parser parent parser
	 * @param string $name   data name
	 * @param string $sep    separator or default argument, When data is array, so $sep is separator. When data is empty, so return $sep.
	 * @return posted data
	 */
	function get( &$parser, $name, $sep = ' ' ) {
		global $wgRequest;

		// disabled cache
		$parser->getOutput()->updateCacheExpiry(0);

		if( strpos( $name, '[]' ) === FALSE ) { // single data
			$data = $wgRequest->getText( HTMLFormFunctions::$PARAM_PREFIX . $name );
		} else { // array data
			$name = str_replace( '[]', '', $name );
			$tmp = $wgRequest->getArray( HTMLFormFunctions::$PARAM_PREFIX . $name );
			if( count( $tmp ) > 0 )
				$data = implode( $sep, $tmp );
			else
				$data = "";
		}
		if( strlen( $data ) == 0 && strlen( $sep ) > 0 )
			$data = $sep;
		return $data;
	}

	/**
	 * Encode string.
	 * e.g. {{#encode:a%b#c!d=e}} => a%25b%23c%21d%3De
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param string $code   character code to be converted(currently, only 'EUC-JP' is supported).
	 * @return posted encoded string
	 */
	function strEncode( &$parser, $str, $code = '' ) {
		$result = "";
		if( strlen( $code ) == 0 ){
			$tmp = urlencode( $str );
			for( $i = 0; $i < strlen( $tmp ); $i ++ ){
				$ch = substr( $tmp, $i, 1 );
				if( strcmp( $ch, "%" ) == 0 ){
					$result .= substr( $tmp, $i, 3 );
					$i += 2;
				} else if( strcmp( $ch, "+" ) == 0 ){
					$result .= "%20";
				} else {
					$result .= "%" . bin2hex( $ch );
				}
			}
		} else if( strcmp( "EUC-JP", $code ) == 0 ){
			$result = urlencode( mb_convert_encoding( $str, "EUC-JP", "UTF-8" ) );
		}

		return $result;
	}

	/**
	 * Decode string.
	 * e.g. {{#decode:{{#encode:{{a%25b%23c%21d%3De}}}} => abcde
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    encoded string
	 * @return posted decoded string
	 */
	function strDecode( &$parser, $str ) {
		return urldecode( $str );
	}

	/**
	 * Create 'Volatile' link. 'Volatile' is a page that disables the cache and receives data and changes its display each time it is opened.
	 * e.g. {{#volatile:Test|to TestPage|arg1|arg2|arg3}} => show a link displayed "to TestPage".
	 *
	 * @param Parser $parser   parent parser
	 * @param string $action   the page in 'Volatile' namespace.
	 * @param string $linkname text displayed as a link.
	 * @param string $args     arguments.
	 * @return string 'formtag' texts.
	*/
	function volatile( &$parser, $action, $linkname, ...$args ){
		global $wgArticlePath;

		$prefix = str_replace( '/$1', '', $wgArticlePath );
		$html = '{{#formtag:form|class="volatile" method="get" action="' . "$prefix/Volatile:" . $action . '|';
		$index = 1;
		foreach( $args as $arg ){
			$html .= '{{#formtag:input|type="hidden" name="' . $index . '" value="' . $arg . '"}}';
			$index ++;
		}
		$html .= '{{#formtag:button|type="submit" class="volatile"|' . $linkname . '}}';
		$html .= '}}';

		return array( $parser->preprocessToDom( $html ), 'isChildObj' => true );
	}

	/**
	 * 'input' tag function. This method is called by {{#formtag:input|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <input> tag.
    */
	function replaceInput( $input, $args, $parser ) {
		if( !isset( $args['type'] ) )
			return '<b style="color: red">HTMLFormFunctions - input - Not found \'type\' param.</b>';

		if( strcmp( $args['type'], "submit" ) == 0 )
			$html = '<input type="' . $args['type'] . '"';
		else
			$html = '<input type="' . $args['type'] . '" name="' . HTMLFormFunctions::$PARAM_PREFIX . $args['name'] . '"';
		unset( $args['type'] );
		unset( $args['name'] );
		$attributes = $this->getAttributes( $args );
	
		return $html . $attributes . ' />';
	}

	/**
	 * 'textarea' tag function. This method is called by {{#formtag:textarea|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <textarea> tag.
    */
	function replaceTextarea( $input, $args, $parser ) {
		$this->decodeTag( $input );
	
		if( !isset( $args['name'] ) )
			return '<b style="color: red">HTMLFormFunctions - textarea - Not found \'name\' param.</b>';
	
		$html = '<textarea name="' . HTMLFormFunctions::$PARAM_PREFIX . $args['name'] . '"';
		unset( $args['name'] );
		$attributes = $this->getAttributes( $args );
	
		return $html . $attributes . '>' . $input . '</textarea>';
	}

	/**
	 * 'select' tag function. This method is called by {{#formtag:select|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <select> tag.
    */
	function replaceSelect( $input, $args, $parser ) {
		$this->decodeTag( $input );
	
		if( !isset( $args['name'] ) )
			return '<b style="color: red">HTMLFormFunctions - select - Not found \'name\' param.</b>';
	
		$html = '<select name="' . HTMLFormFunctions::$PARAM_PREFIX . $args['name'] . '"';
		unset( $args['name'] );
		$attributes = $this->getAttributes( $args );
	
		return $html . $attributes . '>' . $input . '</select>';
	}

	/**
	 * 'option' tag function. This method is called by {{#formtag:option|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <option> tag.
    */
	function replaceOption( $input, $args, $parser ) {
		$this->decodeTag( $input );
	
		$attributes = $this->getAttributes( $args );
		$html = '<option' . $attributes;

		return $html . '>' . $input . '</option>';
	}

	/**
	 * 'optgroup' tag function. This method is called by {{#formtag:optgroup|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <optgroup> tag.
    */
	function replaceOptgroup( $input, $args, $parser ) {
		$this->decodeTag( $input );
	
		if( !isset( $args['label'] ) )
			return '<b style="color: red">HTMLFormFunctions - optgroup - Not found \'label\' param.</b>';

		$html = '<optgroup label="' . $args['label'] . '"';
	
		return $html . '>' . $input . '</optgroup>';
	}

	/**
	 * 'fieldset' tag function. This method is called by {{#formtag:fieldset|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <fieldset> tag.
    */
	function replaceFieldset( $input, $args, $parser ) {
		$this->decodeTag( $input );

		$attributes = $this->getAttributes( $args );

		return '<fieldset' . $attributes . '>' . $input . '</fieldset>';
	}

	/**
	 * 'legend' tag function. This method is called by {{#formtag:legend|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <legend> tag.
    */
	function replaceLegend( $input, $args, $parser ) {
		$this->decodeTag( $input );
	
		$attributes = $this->getAttributes( $args );	

		return '<legend' . $attributes . '>' . $input . '</legend>';
	}

	/**
	 * 'label' tag function. This method is called by {{#formtag:label|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <label> tag.
    */
	function replaceLabel( $input, $args, $parser ) {
		$this->decodeTag( $input );
	
		if( !isset( $args['for'] ) )
			return '<b style="color: red">HTMLFormFunctions - for - Not found \'label\' param.</b>';

		$html = '<label for="' . $args['for'] . '"';
	
		$attributes = $this->getAttributes( $args );

		return $html . $attributes . '>' . $input . '</label>';
	}

	/**
	 * 'button' tag function. This method is called by {{#formtag:button|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <button> tag.
    */
	function replaceButton( $input, $args, $parser ) {
		$this->decodeTag( $input );
	
		$attributes = $this->getAttributes( $args );

		return '<button' . $attributes . '>' . $input . '</button>';
	}

	/**
	 * 'form' tag function. This method is called by {{#formtag:form|---}}.
     *
     * @param string $input  text that is between tags.
     * @param string $args   tag attributes.
     * @param Parser $parser parent parser
     * @return string <form> tag.
    */
	function replaceForm( $input, $args, $parser ) {
		global $wgScriptPath, $wgArticlePath;

		$wgParser = MediaWikiServices::getInstance()->getParser();
	
		$this->decodeTag( $input );
		
		if( !isset( $args['action'] ) )
			return '<b style="color: red">HTMLFormFunctions - form - Not found \'action\' param.</b>';
		$url = $args['action'];

		$option = '';
		if( isset( $args['id'] ) ){
			$option .= ' id="' . $args['id'] . '"';
		}
		if( isset( $args['class'] ) ){
			$option .= ' class="' . $args['class'] . '"';
		}
	
		if( strcmp( '/', substr( $url, 0, 1 ) ) !== 0 )
			return '<b style="color: red">HTMLFormFunctions - form - "action" is not path from DOCUMENT_ROOT.</b>';

		$method = "post";
		if( strcmp( $args['method'], "get" ) == 0 )
			$method = "get";
		$pos = mb_strpos( $url, $wgScriptPath );
		if( $pos === false || $pos != 0 ){
			if( !isset( $wgArticlePath ) )
				return '<b style="color: red">HTMLFormFunctions - form - Found invalid url.</b>';
			$pos = mb_strpos( $url, str_replace( '$1', '',  $wgArticlePath ) );
		}
		if( $pos === false || $pos != 0 )
			return '<b style="color: red">HTMLFormFunctions - form - Found invalid url.</b>';
	
		return '<form action="' . $url . '" method="' . $method . '"' . $option . '>' . $input . '</form>';
	}

	/**
	 * Parse attribute string.
     *
     * @param string $args   tag attributes.
     * @return string String formatted as html attribute value.
    */
	private function getAttributes( $args )
	{
		$str = '';
		foreach( $args as $key => $value ){
			$str .= ' ' . $key . '="' . $value . '"';
		}
		return $str;
	}

	/**
	 * Check for allowed tags.
     *
     * @param string $input  tag name.
     * @return string String string with unauthorized tags escaped
    */
	private function decodeTag( &$input ) {
		$allow_tags = array(
			'table',
			't[rdh]',
			'h[1-6]',
			'[bh]r',
			'p',
			'div',
			'span',
			'su[bp]',
			'b',
			'strong',
		);
		$tags = implode( '|', $allow_tags );
	
		$regex1 = "<((\"[^\"]*\"|'[^']'|[^'\">])*)>";
		$regex2 = "&lt;((?:(input|textarea|$tags)) (\"[^\"]*\"*|'[^']*'|[^'\";])*)&gt;";
		$regex3 = "&lt;([/]?(?:$tags)[ ]*[/]?)&gt;";
	
		$input = mb_ereg_replace( $regex1, "&lt;\\1&gt;", $input );
		$input = mb_ereg_replace( $regex2, "<\\1>", $input );
		$input = mb_ereg_replace( $regex3, "<\\1>", $input );
	}

	/**
	 * setup function
	*/
	public static function SetupHTMLFormFunctions()
	{
		$wgParser = MediaWikiServices::getInstance()->getParser();
	
		$tagFunctions = new HTMLFormFunctions;
	
		$wgParser->setHook( 'input',    array( &$tagFunctions, 'replaceInput' ) );
		$wgParser->setHook( 'textarea', array( &$tagFunctions, 'replaceTextarea' ) );
		$wgParser->setHook( 'select',   array( &$tagFunctions, 'replaceSelect' ) );
		$wgParser->setHook( 'option',   array( &$tagFunctions, 'replaceOption' ) );
		$wgParser->setHook( 'optgroup', array( &$tagFunctions, 'replaceOptgroup' ) );
		$wgParser->setHook( 'fieldset', array( &$tagFunctions, 'replaceFieldset' ) );
		$wgParser->setHook( 'legend',   array( &$tagFunctions, 'replaceLegend' ) );
		$wgParser->setHook( 'label',    array( &$tagFunctions, 'replaceLabel' ) );
		$wgParser->setHook( 'button',   array( &$tagFunctions, 'replaceButton' ) );
		$wgParser->setHook( 'form',     array( &$tagFunctions, 'replaceForm' ) );
	
		$wgParser->setFunctionHook( 'formtag',  array( &$tagFunctions, 'formtag' ) );
		$wgParser->setFunctionHook( 'get',      array( &$tagFunctions, 'get' ) );
		$wgParser->setFunctionHook( 'encode',   array( &$tagFunctions, 'strEncode' ) );
		$wgParser->setFunctionHook( 'decode',   array( &$tagFunctions, 'strDecode' ) );
		$wgParser->setFunctionHook( 'volatile', array( &$tagFunctions, 'volatile' ) );
	}
	
	/**
	 * setup function
	*/
	public static function HTMLFormLanguageGetMagic( &$magicWords, $langCode ) {
		$magicWords['formtag']  = array( 0, 'formtag' );
		$magicWords['get']      = array( 0, 'get' );
		$magicWords['encode']   = array( 0, 'encode' );
		$magicWords['decode']   = array( 0, 'decode' );
		$magicWords['volatile'] = array( 0, 'volatile' );
	
		return true;
	}
}
	
