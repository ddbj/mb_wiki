<?php
/*
  ExtendedFunctions.php Mediawiki extension. Flexible and powerful parser functions.
  This file is based on the original ParserFunctions.php in "Extension:ParserFunctions".
  For the original source, see at http://www.mediawiki.org/wiki/Extension:ParserFunctions.
  Last-modified: 2023.03.17

  @version 1.0
  @author K, Suwa
  @link http://mb.metabolomics.jp/wiki/Help:Extension/ExtendedFunctions
*/
 
use MediaWiki\MediaWikiServices;

class ExtendedFunctions
{
	public $userDefineVariables = array();

	/**
	 * Get carriage return.
	 * e.g. {{#cr:}} => "\n"
	 *
	 * @param Parser $parser parent parser
	 * @return string carriage return
	 */
	function cr( &$parser ) {
		return "\n";
	}

	/**
	 * Get pipe(|).
	 * e.g. {{#bar:}} => '|'
	 *
	 * @param Parser $parser parent parser
	 * @return string pipe(|)
	 */
	function bar( &$parser ) {
		return "|";
	}

	/**
	 * Insert <wbr> for each specified number of characters.
	 * e.g. {{#forcedbr:1234567890|5}} => 12345<wbr>67890
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    data
	 * @param int    $size   separate size
	 * @return inclue <wbr> string
	 */
	function forcedbr( &$parser, $str, $size = 25 ) {
		$length  = mb_strlen( $str );
		$pos     = 0;
		$new_str = "";
		do {
			if( $length <= $pos+$size ){
				$new_str .= mb_substr( $str, $pos );
			} else {
				$new_str .= mb_substr( $str, $pos, $size ) . '<wbr />';
			}
			$pos += $size;
		} while( $pos < $length );

		return $new_str;
	}

	/**
	 * Same as Lisp 'car'.
	 * e.g. {{#car:A;B;C;D;E|;}} => A
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param string $sep    separator. Default is ' '(space)
	 * @return string string which before first separator.
	 */
	function car( &$parser, $str, $sep = ' ' ) {
		if( strlen( $sep ) == 0 )
			return $str;
		return explode( $sep, $str )[0];
	}

	/**
	 * Same as Lisp 'cdr'.
	 * e.g. {{#car:A;B;C;D;E|;}} => B;C;D;E
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param string $sep    separator. Default is ' '(space)
	 * @return string string which after first separator.
	 */
	function cdr( &$parser, $str, $sep = ' ' ) {
		if( strlen( $sep ) == 0 )
			return $str;
		$index = mb_strpos( $str, $sep );
		if( $index === false )
			return '';
		return substr( $str, $index+mb_strlen( $sep ) );
	}

	/**
	 * Same as Lisp 'cadr'.
	 * e.g. {{#car:A;B;C;D;E|;}} => B
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param string $sep    separator. Default is ' '(space)
	 * @return string car after cdr
	 */
	function cadr( &$parser, $str, $sep = ' ' ) {
		return $this->car( $parser, $this->cdr( $parser, $str, $sep ), $sep );
	}

	/**
	 * Same as Lisp 'cddr'.
	 * e.g. {{#car:A;B;C;D;E|;}} => C;D;E
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param string $sep    separator. Default is ' '(space)
	 * @return string string cdr after cdr
	 */
	function cddr( &$parser, $str, $sep = ' ' ) {
		return $this->cdr( $parser, $this->cdr( $parser, $str, $sep ), $sep );
	}

	/**
	 * Same as Lisp 'caadr'.
	 * e.g. {{#car:A;B;C;D;E|;}} => C
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param string $sep    separator. Default is ' '(space)
	 * @return string car after cdr after cdr
	 */
	function caddr( &$parser, $str, $sep = ' ' ) {
		return $this->car( $parser, $this->cdr( $parser, $this->cdr( $parser, $str, $sep ), $sep ), $sep );
	}

	/**
	 * Same as Lisp 'cdddr'.
	 * e.g. {{#car:A;B;C;D;E|;}} => D;E
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param string $sep    separator. Default is ' '(space)
	 * @return string string cdr after cdr after cdr
	 */
	function cdddr( &$parser, $str, $sep = ' ' ) {
		return $this->cdr( $parser, $this->cdr( $parser, $this->cdr( $parser, $str, $sep ), $sep ), $sep );
	}

	/**
	 * Get N'th string.
	 * e.g. {{#nth:abc def ghi 123|4}} => ghi
	 *	{{#nth:123,456,789|2|,}}   => 456
	 *
	 * @param Parser $parser parent parser
	 * @param String $str    target string separated $sep
	 * @param int    $index  number
	 * @param string $sep    separator in $str. default is " "(space)
	 * @return string $index'th string in splited $str
	 */
	function nth( &$parser, $str, $index, $sep = " " ) {
		$words = explode( $sep, $str );
		$index = $index - 1;
		if( $index < 0 || count( $words ) <= $index )
			return "";
		return $words[$index];
	}

	/**
	 * Extract line contains $pattern from $str.
	 * e.g. {{#choose:abc
	 *	def
	 *	agh|a}} -> abc
	 *	agh
	 *
	 * @param Parser $parser  parent parser
	 * @param string $str     target string
	 * @param string $pattern search pattern
	 * @return string line that contains $pattern
	 */
	function choose( &$parser, $str, $pattern = '') {
		if( strlen( $pattern ) == 0) 
			return $str;

		$lines = explode( "\n", $str );
		$output = '';
		for( $i = 0; $i < count( $lines ) ; $i ++ ){
			if( mb_strpos( $lines[$i], $pattern ) !== false )
				$output .= $lines[$i] . "\n";
		}
		return $output;
	}

	/**
	 * Get max number.
	 * e.g. {{#max:1|2|3}} => 3
	 *
	 * @param Parser $parser            parent parser
	 * @param string $n1, $n2, ..., $nN number data
	 * @return number maximum number
	 */
	function getMax( ) {
		$argv = func_get_args();
		if( count( $argv ) <= 1 )
			return "";

		array_shift( $argv );
		for( $i = 0; $i < count( $argv ); $i ++ )
			$argv[$i] =  is_numeric( $argv[$i] ) ? (float)($argv[$i]) : PHP_INT_MIN;

		return max( $argv );
	}

	/**
	 * Get min number.
	 * e.g. {{#min:1|2|3}} => 1
	 *
	 * @param Parser $parser           parent parser
	 * @param string $n1, $n2, ..., $N number data
	 * @return number minimum number
	 */
	function getMin( ) {
		$argv = func_get_args();
		if( count( $argv ) <= 1 )
			return "";

		array_shift( $argv );
		for( $i = 0; $i < count( $argv ); $i ++ )
			$argv[$i] =  is_numeric( $argv[$i] ) ? (float)($argv[$i]) : PHP_INT_MAX;

		return min( $argv );
	}

	/**
	 * Is $str digit? 'digit' is $str including 0-9 only.
	 * e.g. {{#isdigit:1234567890|This is digit|This isn't digit}} => 'This is digit'
	 * e.g. {{#isdigit:123abc456|This is alphanumeric|This isn't alphanumeric}} => 'This isn't alphanumeric}}
	 *
	 * @param Parser $parser  parent parser
	 * @param string $str     target string
	 * @param string $then    string returned when $str is a number
	 * @param string $else    string returned when $str is not a number.
	 * @return string when $str is digit, then return $then, else return $else
	 */
	function isdigit( &$parser, $str, $then = '', $else = '' ) {
		return is_numeric( $str ) ? $then : $else;
	}

	/**
	 * Is $str alphanumeric? 'alphanumeric' is $str including 0-9,a-z,A-Z only
	 * e.g. {{#isdigit:123abc456|This is alphanumeric|This isn't alphanumeric}} => 'This is alphanumeric}}
	 * e.g. {{#isdigit:*123abc456*|This is alphanumeric|This isn't alphanumeric}} => 'This isn't alphanumeric}}
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param string $then   string returned when $str is alphanumeric
	 * @param string $else   string returned when $str is not alphanumeric
	 * @return string when $str is alphanumeric, then return $then, else return $else
	 */
	function isalnum( &$parser, $str = '', $then = '', $else = '' ) {
		return is_numeric( $str ) || preg_match( '/^[0-9a-zA-Z]+$/', $str ) ? $then : $else;
	}

	/**
	 * strtoupper
	 * e.g. {{#upcase:abcABC}} => ABCABC
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @return string all uppercase string
	 */
	function upcase( &$parser, $str ) {
		return mb_strtoupper( $str );
	}

	/**
	 * strtoupper
	 * e.g. {{#downcase:abcABC}} => abcabc
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @return string all downcase string
	 */
	function downcase( &$parser, $str ) {
		return mb_strtolower( $str );
	}

	/**
	 * Same as php 'trim'.
	 * e.g. {{#trim:abc123\n}} => abc123
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @return string trimmed string
	 */
	function trimHook( &$parser, $str ) {
		return trim( $str );
	}

	/**
	 * Delete all white space.
	 * e.g. {{#trimex:ab c1\t23\n}} => abc123
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @return string trimmed string
	 */
	function trimExHook( &$parser, $str ) {
		return preg_replace( "/　|\s/", "", $str );
	}

	/**
	 * Get string length.
	 * e.g. {{#length:Hello world}} => 11
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @return string string length
	 */
	function length( &$parser, $str ) {
		return mb_strlen( $str );
	}

	/**
	 * Create repeated string.
	 * e.g. {{#createString:a|4}} => aaaa
	 *
	 * @param Parser $parser parent parser
	 * @param string $char   repeat character
	 * @param int    $length repeat count
	 * @return string repeated string
	 */
	function createString( &$parser, $char, $length ) {
		if( $length < 0 || 100 < $length )
			return "";

		$str = "";
		for( $i = 0; $i < $length; $i ++ )
				$str .= $char;

		return $str;
	}

	/**
	 * Same as php 'substr'.
	 * e.g. {{#substring:Hello world|1}} => "ello world"
	 * e.g. {{#substring:Hello world|1|4}} => "ell"
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    target string
	 * @param int    $start  start index (including value)
	 * @param int    $end    end index (not including value)
	 * @return string parts string
	 */
	function substring( &$parser, $str, $start = 0, $end = 0 ) {
		if( !is_numeric( $start ) || $start < 0 )
			$start = 0;
		if( !is_numeric( $end ) || $end <= $start )
			$end = mb_strlen( $str );

		return mb_substr( $str, $start, $end-$start );
	}

	/**
	 * Same as 'indexof'. You can use '&#160;' as ' '(space). 
	 * e.g. {{#indexOf:Hello world|wor}} => 6
	 * e.g. {{#indexOf:Hello world|&#160;}} => 5
	 *
	 * @param Parser $parser  parent parser
	 * @param string $str     target string
	 * @param string $pattern search string
	 * @param int    $offset  start position
	 * @return int when $pattern exists, the position is returned. otherwise, returns ''.
	 */
	function indexOf( &$parser, $str, $pattern = ' ', $offset = 0 ) {
		# replace '&#160;' to ' '
		$pattern = str_replace( '&#160;', ' ', $pattern );
		$offset = intval( $offset );

		$index = mb_strpos( $str, $pattern, $offset );
		if( $index === false )
			return '';
		return $index;
	}

	/**
	 * Same as 'lastindexof'. You can use '&#160;' as ' '(space). 
	 * e.g. {{#lastIndexOf:abc abc abc|abc}} => 8
	 * e.g. {{#lastIndexOf:abc abc abc|&#160;}} => 7
	 *
	 * @param Parser $parser  parent parser
	 * @param string $str     target string
	 * @param string $pattern search string
	 * @return int when $pattern exists, the position is returned. otherwise, returns ''.
	 */
	function lastIndexOf( &$parser, $str, $pattern = ' ' ) {
		$pattern = str_replace( '&#160;', ' ', $pattern );
		$index = mb_strrpos( $str, $pattern );
		if( $index === false )
			return '';
		return $index;
	}

	/**
	 * Replace string. You can use '&#160;' as ' '(space), '&#124;' as '|'(Bar) and '&#10;' as '\n'(cr).
	 * e.g. {{#replace|abcdefghi|def|123}} => abc123ghi
	 *
	 * @param Parser $parser      parent parser
	 * @param string $string      target string
	 * @param string $pattern     search pattern
	 * @param string $replacement string to be replaced
	 * @return string string with $pattern replaced by $replacement
	 */
	function replace( &$parser, $string, $pattern, $replacement, $caseIgnore = false ) {
		$pattern     = str_replace( '&#160;', ' ', $pattern );
		$replacement = str_replace( '&#160;', ' ', $replacement );

		$pattern     = str_replace( '&#124;', '\|', $pattern );
		$replacement = str_replace( '&#124;', '|', $replacement );

		$pattern     = str_replace( '&#9;', "\t", $pattern );
		$replacement = str_replace( '&#9;', "\t", $replacement );

		$pattern     = str_replace( '&#10;', "\n", $pattern );
		$replacement = str_replace( '&#10;', "\n", $replacement );

		$option = 'm';
		if( strcmp( 'true', $caseIgnore ) === 0 )
			$option .= 'i';
		mb_regex_encoding("UTF-8");

		return mb_ereg_replace( "$pattern", $replacement, $string, $option );
	}

	/**
	 * Examine number $pattern in $str.
	 * e.g. {{#count:abcabcabca|a}} => 4
	 *
	 * @param Parser $parser  parent parser
	 * @param string $str     target String
	 * @param string $pattern search pattern
	 * @return int number of occurrences of $pattern in $str
	 */
	function countHook( &$parser, $str, $pattern = "\n" ) {
		if( mb_strlen( $str ) == 0 || mb_strlen( $pattern ) == 0 )
			return 0;

		if( strncmp( $pattern, "\n", 4 ) == 0 )
			return substr_count( trim( $str ), $pattern ) + 1;
		return mb_substr_count( $str, $pattern );
	}

	/**
	 * Returns "AND operation" of a list of strings.
	 * e.g. {{#and:1
	 * 2
	 * 4|1
	 * 3
	 * 4}} => 1 4
	 *
	 * @param Parser $parser                    parent parser
	 * @param string $list1, $list2, ... $listN string list(variable number of arguments) 
	 * @return string string with "AND operation" applied
	*/
	function andString( ) {
		$argv = func_get_args();
		if( count( $argv ) < 3 )
			return "";

		$parser = $argv[0];
		$list   = $argv[1];
		for( $i = 2; $i < count( $argv ); $i ++ ){
			$list = $this->innerAndString( $parser, trim( $list ), trim( $argv[$i] ) );
		}

		return $list;
	}
	/**
	 * Apply "AND operation" to the two arguments given.
	 * This method is called by "addString".
	*/
	private function innerAndString( &$parser, $list1 = '', $list2 = '' ) {
		if( strlen( $list1 ) == 0 || strlen( $list2 ) == 0 )
			return '';

		$list = explode( "\n", $list1 );
		$counter = array();
		for( $i = 0; $i < count( $list ); $i ++)
			$counter[$list[$i]] = 1;
		$list = explode( "\n", $list2 );
		$result = "";
		for( $i = 0; $i < count( $list ); $i ++){
			if( isset( $counter[$list[$i]] ) && $counter[$list[$i]] == 1 ){
				$counter[$list[$i]] = 2;
				$result .= $list[$i] . "\n";
			}
		}

		return $result;
	}

	/**
	 * Returns "OR operation" of a list of strings.
	 * e.g. {{#or:1
	 * 2
	 * 4|1
	 * 3
	 * 4}} => 1 2 3 4
	 *
	 * @param Parser $parser                    parent parser
	 * @param string $list1, $list2, ... $listN string list(variable number of arguments) 
	 * @return string string with "OR operation" applied
	*/
	function orString( ) {
		$argv = func_get_args();
		if( count( $argv ) < 3 )
			return "";

		$parser = $argv[0];
		$list   = $argv[1];
		for( $i = 2; $i < count( $argv ); $i ++ ){
			$list = $this->innerOrString( $parser, trim( $list ), trim( $argv[$i] ) );
		}

		return $list;
	}
	/**
	 * Apply "OR operation" to the two arguments given.
	 * This method is called by "orString".
	*/
	private function innerOrString( &$parser, $list1 = '', $list2 = '' ) {
		if( strlen( $list1 ) == 0 && strlen( $list2 ) == 0 )
			return '';
		if( strlen( $list1 ) == 0 )
			return $list2;
		if( strlen( $list2 ) == 0 )
			return $list1;

		$list1 = explode( "\n", $list1 );
		$list2 = explode( "\n", $list2 );
		$max1 = count( $list1 );
		$max2 = count( $list2 );
		$result = "";
		$already = array();
		for( $i = 0; $i < $max1; $i ++ ){
			$result .= $list1[$i] . "\n";
			$already[$list1[$i]] = 1;
		}
		for( $i = 0; $i < $max2; $i ++ ){
			if( !isset( $already[$list2[$i]] ) )
				$result .= $list2[$i] . "\n";
		}

		return $result;
	}

	/**
	 * Call specified template, until finishing consuming argument.
	 * e.g. {{#repeat:template|3|a,b,c,d,e,f,g,h,i|,}} => {{template|a|b|c}} {{template|d,e,f}} {{template|g|h|i}}
	 *
	 * @param Parser $parser   parent parser
	 * @param string $template template to be used
	 * @param int    $args     number of arguments used in one call
	 * @param string $argv     arguments which separated $sep
	 * @param string $sep      separator of $argv
	 * @param string $pre      prefix to be prepended to argument
	 * @param string $post     postfix to be prepended to argument
	 * @return string template list in mediawiki notation.
	 */
	function prepostRepeat( &$parser, $template, $args, $argv, $sep = "\n", $pre = '', $post = '', $limit = -1, $message = "and more" ) {
		return self::repeat( $parser, $template, $args, $argv, $sep, $pre, $post, false, $limit, $message );
	}

	/**
	 * Call specified template, until finishing consuming argument.
	 * e.g. {{#numrepeat:template|3|a,b,c,d,e,f,g,h,i|,}} => {{template|1=a|2=b|3=c}} {{template|1=d,2=e,3=f}} {{template|1=g|2=h|3=i}}
	 *
	 * @param Parser $parser   parent parser
	 * @param string $template template to be used
	 * @param int    $args     number of arguments used in one call
	 * @param string $argv     arguments which separated $sep
	 * @param string $sep      separator of $argv
	 * @return string template list in mediawiki notation.
	 */
	function numberingRepeat( &$parser, $template, $args, $argv, $sep = "\n", $limit = -1, $message = "and more") {
		return self::repeat( $parser, $template, $args, $argv, $sep, "", "", true, $limit, $message );
	}

	/**
	 * Call specified template. This method is called by 'prepostRepeat'/'numberingRepeat'.
	 * e.g. {{#repeat:template|3|a,b,c,d,e,f,g,h,i|,}} => {{template|a|b|c}} {{template|d,e,f}} {{template|g|h|i}}
	 *
	 * @param Parser  $parser    parent parser
	 * @param string  $template  template to be used
	 * @param int     $args      number of arguments used in one call
	 * @param string  $argv      arguments which separated $sep
	 * @param string  $sep       separator of $argv
	 * @param string  $pre       prefix to be prepended to argument
	 * @param string  $post      postfix to be prepended to argument
	 * @param boolean $useNumber when $useNumber is 'true', uses argv with number. otherwise, uses argv without number.
	 * @return string template list in mediawiki notation.
	 */
	private function repeat( &$parser, $template, $args, $argv, $sep = "\n", $pre = '', $post = '', $useNumber = false, $limit = -1, $message = "and more" ) {
		# replace '&#160;' to ' '
		if( strncmp( $sep, '&#160;', 7 ) == 0 )
			$sep = ' ';
		# replace '\n' to "\n"
		if( strncmp( $sep, '\n', 4) == 0 )
			$sep = "\n";

		# check $args, $argv
		if( !isset( $args ) || $args <= 0 || 100 < $args || strlen( $argv ) == 0 )
			return "";

		$sep  = str_replace( '$', '\$', $sep );
		$arg  = explode( $sep, $argv );
		$size = count( $arg );
		$str  = "";
		$total = 0;
		if( $useNumber ){
			for( $i = 0; $i < $size; $i += $args ) {
				$total ++;
				if( $limit !== -1 && $limit < $total ){
					$str .= $message;
					break;
				}
				$str .= "{{" . $template;
				for( $j = 0; $j < $args && $i+$j < $size; $j ++ ){
					$str .= "|" . ($j+1) . "=" . $arg[$i+$j];
				}
				$str .= "}}";
			}
		} else {
			for( $i = 0; $i < $size; $i += $args ) {
				$total ++;
				if( $limit !== -1 && $limit < $total ){
					$str .= $message;
					break;
				}
				$str .= "{{" . $template;
				for( $j = 0; $j < $args && $i+$j < $size; $j ++ ){
					$str .= "|" . $pre . $arg[$i+$j] . $post;
				}
				$str .= "}}";
			}
		}

		return array( $parser->preprocessToDom( $str ), 'isChildObj' => true );
	}

	/**
	 * Call specified template, until finishing consuming argument. Difference to {{#:repeat:}} is that template's arguments are separated by '|'(Bar). 
	 * e.g. {{#map:template|3|a|b|c|d|e|f|g|h|i}} => {{template|a|b|c}} {{template|d|e|f}} {{template|g|h|i}}
	 *
	 * @param Parser  $parser                     parent parser
	 * @param string  $template                   template to be used
	 * @param int     $args                       number of arguments used in one call
	 * @param string  $argv1, $argv2, ..., $argvN arguments (variable number of arguments)
	 * @return string template list in mediawiki notation.
	 */
	function map( ) {
		$argv = func_get_args();
		if( count( $argv ) <= 3 )
			return '<b style="color: red">ExtendedFunctions - map - Too few arguments.</b>';

		$parser   = $argv[0];
		$template = $argv[1];
		$args     = $argv[2];
		if( strlen( $template ) == 0 || $args <= 0 || 100 < $args )
			return "";

		$str = "";
		for( $i = 3; $i < count( $argv ); $i += $args ) {
			$str .= "{{" . $template;
			for( $j = 0; $j < $args && $i+$j < count($argv); $j ++ ){
				$str .= "|" . $argv[$i+$j];
			}
			$str .= "}}";
		}

		return array( $parser->preprocessToDom( $str ), 'isChildObj' => true );
	}

	/**
	 * Define variable.
	 * e.g. {{#def:i|1}} => i = 1;
	 *
	 * @param Parser $parser parent parser
	 * @param string $name   variable name
	 * @param string $var    value
	 * @return string always ''
	*/
	function def( &$parser, $name, $var = '' ) {
		if( strlen( $name ) == 0 )
			return '';

		$this->userDefineVariables[$name] = $var;

		return '';
	}

	/**
	 * Get variable defined by {{#def:}}.
	 * e.g. {{#get:i}} => 1
	 *
	 * @param Parser  $parser parent parser
	 * @param string  $name   variable name
	 * @return string value
	*/
	function getVar( &$parser, $name ) {
		if( strlen( $name ) == 0 )
			return '';

		if( isset( $this->userDefineVariables[$name] ) )
			return $this->userDefineVariables[$name];
		return '';
	}
	/**
	 * Search $word from all pages. The result is hatched with "&&title" as the head.
	 * e.g. {{#searchline:search|0}} => 
	 * &&Sandbox'you can search ...'
	 * &&Sandbox'search is not ...'
	 * ...
	 *
	 * @param Parser $parser    parent parser
	 * @param string $word      search word
	 * @param string $namespace target namespace. Default is 0(Main)
	 * @param string $page      target page. Default is ''
	 * @return string all lines including $word.(But excluding myself)
	 */
	function searchLine( &$parser, $word, $namespace = 'Main', $page = '' ) {
		global $wgCanonicalNamespaceNames, $wgExtraNamespaces;

		# minimum length is 3.
		if( strlen( $page ) == 0 && mb_strlen( $word ) < 3)
			return '';

		# get current TITLE and NAMESPACE
		$curTitle = $parser->getTitle();
		$curNs    = $curTitle->getNamespace();
		$dbr      = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );
		# escape character caused SQL injection.
		$word = $dbr->strencode( $word );
		if( $word === false )
			return '<b style="color: red">ExtendedFunctions - searhcline - Encoding of \'search word\' failed.</b>';
		$lTitle = $dbr->strencode( str_replace( " ", "_", $curTitle->getBaseText() ) );
		if( $lTitle === false )
			return '<b style="color: red">ExtendedFunctions - searhcline - Encoding of \'title\' failed.</b>';;
		if( strlen( $page ) > 0 ){
			$page = $dbr->strencode( str_replace( " ", "_", $page ) );
			if( $page === false )
				return '<b style="color: red">ExtendedFunctions - searhcline - Encoding of \'page\' failed.</b>';
		}
		# support simple regex.
		$sqlWord = $word;
		if( $sqlWord[0] == '^' )
			$sqlWord = substr( $sqlWord, 1 );
		if( $sqlWord[strlen($sqlWord)-1] == '$' )
			$sqlWord = substr( $sqlWord, 0, mb_strlen( $sqlWord )-1 );

		# get namespace number.
		$namespaceNames = $wgCanonicalNamespaceNames + $wgExtraNamespaces;
		$ns = array_keys( $namespaceNames, $namespace );
		if( count( $ns ) == 0 )
			$ns[0] = 0;

		# if a page is specified
		$option = '';
		if( 0 < strlen( $page ) )
			$option .= ' and page_title like \'' . $page . '\'';
		if( $curNs == $ns[0] )
			$option .= ' and page_title != \'' . $lTitle . '\'';

		# search from database.
		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'page_title, old_text' ] )
			->tables( [ 'page', 'revision', 'slots', 'content', 'text' ] )
			->where( 'page.page_latest = revision.rev_id and slots.slot_revision_id = revision.rev_id and slots.slot_content_id = content.content_id and substring(content.content_address,4) = text.old_id and old_text like \'%' . $sqlWord . '%\' and page_namespace = ' . $ns[0] . $option )
			->caller( __METHOD__ )
			->fetchResultSet(); 
		$result = '';
		$phpWord = preg_replace( "/([-\(\)\[\]\/])/", "\\\\$1", $word );
		$phpWord = preg_replace( '/\+/',          "\\+",  $phpWord );
		$phpWord = preg_replace( '/([^\\\\])\%/', "$1.*", $phpWord );
		$phpWord = preg_replace( '/([^\\\\])_/',  "$1.",  $phpWord );
		$phpWord = preg_replace( '/([^\\\\])\%/', "$1.*", $phpWord );
		$phpWord = preg_replace( '/([^\\\\])_/',  "$1.",  $phpWord );
		foreach( $res as $row ){
			$buf   = $row->old_text;
			$lines = explode( "\n", $buf );
			$size  = count( $lines );
			for( $i = 0; $i < $size; $i ++ ){
				// Search line including '$phpWord'.
				if( !preg_match( "/$phpWord/", $lines[$i] ) )
					continue;
				// Check 'search' extension. (To prevent infinite recursion)
				$checkLine = strtolower( $lines[$i] );
				$own1 = strpos( $checkLine, '{{#searchline' );
				$own2 = strpos( $checkLine, '{{#countline:' );
				if( $own1 === false && $own2 === false ){	
					$result .= '&&' . $row->page_title . $lines[$i] . "\n";
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the title of the page that does not contain $word.
	 * e.g. {{#searchlinenot:search|0}} => 
	 * &&page1
	 * &&page2
	 * &&...
	 *
	 * @param Parser $parser    parent parser
	 * @param string $word      search word
	 * @param string $namespace target namespace. Default is 'Main'(0)
	 * @param string $page      target page. Default is ''
	 * @return string the titles of the page that does not contains $word.(But excluding myself)
	 */
	function searchLineNot( &$parser, $word, $namespace = 'Main', $page = '' ) {
		global $wgCanonicalNamespaceNames, $wgExtraNamespaces;

		# minimum length is 3.
		if( strlen( $page ) == 0 && mb_strlen( $word ) < 3)
			return '';

		# get TITLE and NAMESPACE
		$curTitle = $parser->getTitle();
		$curNs    = $curTitle->getNamespace();
		$dbr      = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );
		# escape character caused SQL injection.
		$word = $dbr->strencode( $word );
		if( $word === false )
			return '<b style="color: red">ExtendedFunctions - searhclinenot - Encoding of \'search word\' failed.</b>';
		$lTitle = $dbr->strencode( str_replace( " ", "_", $curTitle->getBaseText() ) );
		if( $lTitle === false )
			return '<b style="color: red">ExtendedFunctions - searhclinenot - Encoding of \'title\' failed.</b>';
		if( strlen( $page ) > 0 ){
			$page = $dbr->strencode( str_replace( " ", "_", $page ) );
			if( $page === false )
				return '<b style="color: red">ExtendedFunctions - searhclinenot - Encoding of \'page\' failed.</b>';
		}
		# support simple regex.
		$sqlWord = $word;
		if( $sqlWord[0] == '^' )
			$sqlWord = substr( $sqlWord, 1 );
		if( $sqlWord[strlen($sqlWord)-1] == '$' )
			$sqlWord = substr( $sqlWord, 0, mb_strlen( $sqlWord )-1 );

		# get namespace number.
		$namespaceNames = $wgCanonicalNamespaceNames + $wgExtraNamespaces;
		$ns = array_keys( $namespaceNames, $namespace );
		if( count( $ns ) == 0 )
			$ns[0] = 0;

		# specified page
		$option = '';
		if( strlen( $page ) > 0 )
			$option .= ' and page_title like \'' . $page . '\'';
		if( $curNs == $ns[0] )
			$option .= ' and page_title != \'' . $lTitle . '\'';

		# read database.
		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'page_title' ] )
			->tables( [ 'page', 'revision', 'slots', 'content', 'text' ] )
			->where( 'page.page_latest = revision.rev_id  and slots.slot_revision_id = revision.rev_id and slots.slot_content_id = content.content_id and substring(content.content_address,4) = text.old_id and old_text not like \'%' . $sqlWord . '%\' and page_namespace = ' . $ns[0] . $option )
			->caller( __METHOD__ )
			->fetchResultSet(); 

		$result = '';
		foreach( $res as $row){
			$result .= '&&' . $row->page_title . "\n";
		}

		return $result;
	}

	/**
	 * Same as 'searchLine'. Difference to {{#searchline:}} is that regular expression is enabled.
	 * e.g. {{#searchlinereg:^search|0}} => 
	 * '&&Sandboxsearch ...'
	 * '&&Sandboxsearch is not ...'
	 * ...
	 *
	 * @param Parser $parser    parent parser
	 * @param string $word      search word
	 * @param string $namespace target namespace. Default is 'Main'(0)
	 * @param string $page      target page. Default is ''
	 * @return string all lines including $word.(But excluding myself)
	 */
	function searchLineReg( &$parser, $word, $namespace = 'Main', $page = '' ) {
		global $wgCanonicalNamespaceNames, $wgExtraNamespaces;

		# minimum length is 3.
		if( strlen( $page ) == 0 && mb_strlen( $word ) < 3)
			return '';

		# get TITLE and NAMESPACE
		$curTitle = $parser->getTitle();
		$curNs    = $curTitle->getNamespace();
		$dbr      = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );
		$sqlWord = $dbr->strencode( $word );
		if( $sqlWord === false )
			return '<b style="color: red">ExtendedFunctions - searhclinereg - Encoding of \'serach word\' failed.</b>';
		$lTitle = $dbr->strencode( str_replace( " ", "_", $curTitle->getBaseText() ) );
		if( $lTitle === false )
			return '<b style="color: red">ExtendedFunctions - searhclinereg - Encoding of \'title\' failed.</b>';
		if( strlen( $page ) > 0 ){
			$page = $dbr->strencode( str_replace( " ", "_", $page ) );
			if( $page === false )
				return '<b style="color: red">ExtendedFunctions - searhclinereg - Encoding of \'page\' failed.</b>';
		}
		# support simple regex.
		#$sqlWord = str_replace( ' ', '_', $sqlWord );
		$sqlWord = str_replace( '~', '|',  $sqlWord );
		$sqlWord = str_replace( '<<', '[', $sqlWord );
		$sqlWord = str_replace( '>>', ']', $sqlWord );
		if( $sqlWord[0] == '^' )
			$sqlWord = substr( $sqlWord, 1 );
		if( $sqlWord[strlen($sqlWord)-1] == '$' )
			$sqlWord = substr( $sqlWord, 0, mb_strlen( $sqlWord )-1 );

		# get namespace number.
		$namespaceNames = $wgCanonicalNamespaceNames + $wgExtraNamespaces;
		$ns = array_keys( $namespaceNames, $namespace );
		if( count( $ns ) == 0 )
			$ns[0] = 0;

		# if a page is specified
		$option = '';
		if( strlen( $page ) > 0 )
			$option .= ' and page_title like \'' . $page . '\'';
		if( $curNs == $ns[0] )
			$option .= ' and page_title != \'' . $lTitle . '\'';

		# search from database.
		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'page_title, old_text' ] )
			->tables( [ 'page', 'revision', 'slots', 'content', 'text'] )
			->where( 'page.page_latest = revision.rev_id and slots.slot_revision_id = revision.rev_id and slots.slot_content_id = content.content_id and substring(content.content_address,4) = text.old_id and old_text regexp \'' . $sqlWord . '\' and page_namespace = ' . $ns[0] . $option )
			->caller( __METHOD__ )
			->fetchResultSet(); 
		$result = "";
		$phpWord = preg_replace( "/~/", "|",  $word );
		$phpWord = preg_replace( "/<</", "[", $phpWord );
		$phpWord = preg_replace( "/>>/", "]", $phpWord );
		$phpWord = str_replace( "/", "\/",    $phpWord );
		foreach( $res as $row ){
			$buf  = $row->old_text;
			$lines = explode( "\n", $buf );
			$size  = count( $lines );
			for( $i = 0; $i < $size; $i ++ ){
				// Search line including '$word'.
				if( preg_match( "/$phpWord/", $lines[$i] ) ){
					// Check 'search' extension. (To prevent infinite recursion)
					$checkLine = strtolower( $lines[$i] );
					$own1 = strpos( $checkLine, '{{#searchline' );
					$own2 = strpos( $checkLine, '{{#countline:' );
					if( $own1 === false && $own2 === false ){
						$result .= '&&' . $row->page_title . $lines[$i] . "\n";
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the number of pages containing the string.
	 * e.g. {{#countline:search|0}} => 3
	 *
	 * @param Parser $parser    parent parser
	 * @param string $word      search word
	 * @param string $namespace target namespace. Default is 'Main'(0)
	 * @param string $page      target page. Default is ''
	 * @return int the number of pages containing the string.(But excluding myself)
	 */
	function countLine( &$parser, $word, $namespace = 'Main', $page = '' ) {
		$str = $this->searchLine( $parser, $word, $namespace, $page );
		return $this->countHook( $parser, $str );
	}

	/**
	 * Search all pages for titles that contain $word. You can use regular expression.
	 * e.g. {{#searchtitle:Sandbox|0}} => 
	 * 'Sandbox1'
	 * 'Sandbox2'
	 * ...
	 *
	 * @param Parser $parser    parent parser
	 * @param string $word      search word
	 * @param string $namespace target namespace name. Default is 'Main'(0)
	 * @return string all titles including $word.(But excluding myself)
	 */
	function searchTitle( &$parser, $word, $namespace = 'Main' ) {
		global $wgCanonicalNamespaceNames, $wgExtraNamespaces;

		# minimum length is 2.
		if( mb_strlen( $word ) < 2 )
			return '';

		$curTitle = $parser->getTitle();
		$curNs    = $curTitle->getNamespace();
		$dbr      = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );

		# escape character caused SQL injection.
		$word = str_replace( '&lt;', '[', $word );
		$word = str_replace( '&gt;', ']', $word );
		$word = $dbr->strencode( str_replace( '[]', '', $word ) );
		if( $word === false )
			return '<b style="color: red">ExtendedFunctions - searhctitle - Encoding of \'serach word\' failed.</b>';
		$lTitle = $dbr->strencode( str_replace( " ", "_", $curTitle->getBaseText() ) );
		if( $lTitle === false )
			return '<b style="color: red">ExtendedFunctions - searhctitle - Encoding of \'title\' failed.</b>';
		# support simple regex.
		$sqlWord = str_replace( ' ', '_', $word );
		$sqlWord = str_replace( '~', '|', $sqlWord );

		# get namespace number.
		$namespaceNames = $wgCanonicalNamespaceNames + $wgExtraNamespaces;
		$ns = array_keys( $namespaceNames, $namespace );
		if( count( $ns ) == 0 )
			$ns[0] = 0;

		# select from database.
		if( $curNs == $ns[0] )
			$where = 'page_title regexp \'' . $sqlWord . '\' and page_title != \'' . $lTitle . '\' and page_namespace = ' . $ns[0];
		else
			$where = 'page_title regexp \'' . $sqlWord . '\' and page_namespace = ' . $ns[0];
		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'page_title' ] )
			->from( 'page' )
			->where( $where )
			->caller( __METHOD__ )
			->fetchResultSet();
		$result = '';
		foreach( $res as $row )
			$result .= $row->page_title . "\n";

		return $result;
	}


	/**
	 * Returns the number of pages $word contains in title.
	 * e.g. {{#counttitle:FL_|0}} => 53
	 *
	 * @param Parser $parser    parent parser
	 * @param string $word      search word
	 * @param int    $namespace target namespace. Default is 'Main'(0)
	 * @return int number of pages with $word in title(But excluding myself)
	 */
	function countTitle( &$parser, $word, $namespace = 'Main' ) {
		$str = $this->searchTitle( $parser, $word, $namespace );
		return $this->countHook( $parser, $str );
	}

	/**
	 * Get a page title that contains $word.
	 * e.g. {{#searchtitle:FL_|0}} => 
	 * 'FL1A19NF0001'
	 * 'FL2AF3NF0001'
	 * ...
	 *
	 * @param Parser $parser    parent parser
	 * @param string $word      search word
	 * @param int    $namespace target namespace. Default is 'Main'(0)
	 * @return string all titles(But excluding myself)
	 */
	function searchPage( &$parser, $word, $namespace = 'Main' ) {
		global $wgCanonicalNamespaceNames, $wgExtraNamespaces;

		$curTitle = $parser->getTitle();
		$curNs    = $curTitle->getNamespace();

		# minimum length is 2.
		if( mb_strlen( $word ) < 2)
			return '';

		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );
		$lang = MediaWiki\MediaWikiServices::getInstance()->getContentLanguage();

		# search namespace.
		$ns = $lang->getLocalNsIndex( $namespace );
		if( strlen( $ns ) == 0 )
			$ns = 0;

		# exclude myself.
		$option = '';
		$lTitle = $dbr->strencode( str_replace( " ", "_", $curTitle->getBaseText() ) );
		if( $curNs == $ns[0] )
			$option = 'page_title != \'' . $lTitle . '\' and ';

		# escape
		$word = $dbr->addQuotes( $word );

		# query
		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'page_namespace', 'page_title' ] )
			->tables( [ 'page', 'searchindex' ] )
			->where( $option . ' page_id = si_page and page_namespace = ' . $ns . ' and match(si_text) against(' . $word . ')')
			->caller( __METHOD__ )
			->fetchResultSet();
		# result
		$nsname = "";
		if( $ns !== 0 )
			$nsname = $lang->getFormattedNsText( $ns );
		$result = '';
		foreach( $res as $row ){
			$page = $row->page_title;
			$result .= "$nsname:$page\n";
		}

		return $result;
	}

	/**
	 * Do $page exists?
	 * e.g. {{#ifexists:Main_Page|exists|do not exists}} => 'exists'
	 * e.g. {{#ifexists:Dont_Exists|exists|do not exists}} => 'do not exists'
	 *
	 * @param Parser $parser parent parser
	 * @param string $page   page name
	 * @param string $then   string returned when $page exists
	 * @param string $else   string returned when $page does not exists.
	 * @return when $page exists, returns $then. otherwise, returns $else.
	 */
	function ifExists( &$parser, $page, $then = '', $else = '' ) {
		$targetTitle = Title::newFromText( $page );
		if( $targetTitle == null )
			return $else;

		if($targetTitle->exists())
			return $then;
		return $else;
	}

	/**
	 * Examine exists file.
	 * e.g. {{#ifexistfile:/index.htm}} -> [http://DOMAIN/index.html index.html]
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    path to file from DOCUMENT_ROOT
	 * @return string when the file exists, returns link to path. othersize, returns ''.
	 */
	function ifExistFile( &$parser, $path ) {
		# check path, if including '..'(Parent directory), return ''
		if( strpos( $path, '..' ) !== false )
			return '';

		// get document root
		if( !isset( $_SERVER["DOCUMENT_ROOT"] ) || strlen( $_SERVER["DOCUMENT_ROOT"] ) == 0 )
			$prefix = "/var/www/html"; // DOCUMENT_ROOT
		else
			$prefix = $_SERVER["DOCUMENT_ROOT"];

		// get server name
		if( !isset( $_SERVER["SERVER_NAME"] ) || strlen( $_SERVER["SERVER_NAME"] ) == 0 )
			$server = "localhost/"; // "[your domain]/". ex. "mb.metabolomics.jp/"
		else
			$server = $_SERVER["SERVER_NAME"];

		// file check
		if( file_exists( $prefix . '/' . $path ) ){
			$index = strrpos( $path, '/' );
			if( $index === false )
				$index = -1;
			return '[https://' . $server . $path . ' ' . substr($path, $index+1) . ']';
		}
		return "";
	}

	/**
	 * Get environment variable value.
	 * e.g. {{#getenv:lang}} => "ja"
	 *
	 * @param Parser $parser parent parser
	 * @param string $str    environment variable name
	 * @return string environment variable value
	 */
	function getEnvValue( &$parser, $id ) {
		$id = strtolower( $id );
		if( strcmp( $id, "lang" ) != 0 )
			return '<b style="color: red">ExtendedFunctions - getenv - Unsupported id.</b>';

		$langs = getenv( "HTTP_ACCEPT_LANGUAGE" );
		if( strlen( $langs ) == 0 )
			return '<b style="color: red">ExtendedFunctions - getenv - Can\'t get \'language\'.</b>';

		$a_lang = explode( ",", $langs );
		return $a_lang[0];
	}

	/**
	 * Get category links which separated '\n'.
	 * e.g. {{#clink:category}} => 'page_A
	 * page_B
	 * page_C
	 * ...
	 *
	 * @param Parser $parser parent parser
	 * @param string $page   page name
	 * @return when $page exists, returns category links.
	 */
	function clink( &$parser, $page ) {
		global $wgCanonicalNamespaceNames, $wgExtraNamespaces;

		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );
		$page = $dbr->strencode( $page );
		if( $page === false )
			return '<b style="color: red">ExtendedFunctions - clink - Invalid page name.</b>';

		$res = $dbr->newSelectQueryBuilder()
			->select( [  'page_namespace', 'page_title'] )
			->tables( [ 'page', 'categorylinks' ] )
			->where( 'cl_to = \'' . $page . '\' and cl_from = page_id' )
			->caller( __METHOD__ )
			->fetchResultSet(); 
		$result = '';
		$namespaceNames = $wgCanonicalNamespaceNames + $wgExtraNamespaces;
		foreach( $res as $row ){
			$ns = $row->page_namespace;
			if( $ns == 0 )
				$ns = '';
			else if( isset( $namespaceNames[$ns] ) )
				$ns = $namespaceNames[$ns] . ':';
			else
				$ns .= ':';
			$result .= $ns . $row->page_title . "\n";
		}

		return $result;
	}

	/**
	 * setup function
	*/
	public static function efSetupExFunctions() {
		$wgParser = MediaWikiServices::getInstance()->getParser();
	
		$exFunctions = new ExtendedFunctions;
	
		$wgParser->setFunctionHook( 'cr',	         array( &$exFunctions, 'cr' ) );
		$wgParser->setFunctionHook( 'bar',	         array( &$exFunctions, 'bar' ) );
		$wgParser->setFunctionHook( 'forcedbr',	     array( &$exFunctions, 'forcedbr' ) );
		$wgParser->setFunctionHook( 'car',	         array( &$exFunctions, 'car' ) );
		$wgParser->setFunctionHook( 'cdr',	         array( &$exFunctions, 'cdr' ) );
		$wgParser->setFunctionHook( 'cadr',	         array( &$exFunctions, 'cadr' ) );
		$wgParser->setFunctionHook( 'cddr',	         array( &$exFunctions, 'cddr' ) );
		$wgParser->setFunctionHook( 'caddr',	     array( &$exFunctions, 'caddr' ) );
		$wgParser->setFunctionHook( 'cdddr',	     array( &$exFunctions, 'cdddr' ) );
		$wgParser->setFunctionHook( 'nth',	         array( &$exFunctions, 'nth' ) );
		$wgParser->setFunctionHook( 'choose',	     array( &$exFunctions, 'choose' ) );
		$wgParser->setFunctionHook( 'max',	         array( &$exFunctions, 'getMax' ) );
		$wgParser->setFunctionHook( 'min',	         array( &$exFunctions, 'getMin' ) );
		$wgParser->setFunctionHook( 'isdigit',	     array( &$exFunctions, 'isdigit' ) );
		$wgParser->setFunctionHook( 'isalnum',	     array( &$exFunctions, 'isalnum' ) );
		$wgParser->setFunctionHook( 'upcase',	     array( &$exFunctions, 'upcase' ) );
		$wgParser->setFunctionHook( 'downcase',      array( &$exFunctions, 'downcase' ) );
		$wgParser->setFunctionHook( 'trim',	         array( &$exFunctions, 'trimHook' ) );
		$wgParser->setFunctionHook( 'trimex',	     array( &$exFunctions, 'trimExHook' ) );
		$wgParser->setFunctionHook( 'length',	     array( &$exFunctions, 'length' ) );
		$wgParser->setFunctionHook( 'createstring',  array( &$exFunctions, 'createString' ) );
		$wgParser->setFunctionHook( 'substring',     array( &$exFunctions, 'substring' ) );
		$wgParser->setFunctionHook( 'indexof',	     array( &$exFunctions, 'indexOf' ) );
		$wgParser->setFunctionHook( 'lastindexof',   array( &$exFunctions, 'lastIndexOf' ) );
		$wgParser->setFunctionHook( 'replace',	     array( &$exFunctions, 'replace' ) );
		$wgParser->setFunctionHook( 'count',	     array( &$exFunctions, 'countHook' ) );
		$wgParser->setFunctionHook( 'and',	         array( &$exFunctions, 'andString' ) );
		$wgParser->setFunctionHook( 'or',	         array( &$exFunctions, 'orString' ) );
		$wgParser->setFunctionHook( 'repeat',	     array( &$exFunctions, 'prepostRepeat' ) );
		$wgParser->setFunctionHook( 'repeatnum',     array( &$exFunctions, 'numberingRepeat' ) );
		$wgParser->setFunctionHook( 'map',	         array( &$exFunctions, 'map' ) );
		$wgParser->setFunctionHook( 'def',	         array( &$exFunctions, 'def' ) );
		$wgParser->setFunctionHook( 'var',	         array( &$exFunctions, 'getVar' ) );
		$wgParser->setFunctionHook( 'searchline',    array( &$exFunctions, 'searchLine' ) );
		$wgParser->setFunctionHook( 'searchlinenot', array( &$exFunctions, 'searchLineNot' ) );
		$wgParser->setFunctionHook( 'searchlinereg', array( &$exFunctions, 'searchLineReg' ) );
		$wgParser->setFunctionHook( 'searchtitle',   array( &$exFunctions, 'searchTitle' ) );
		$wgParser->setFunctionHook( 'counttitle',    array( &$exFunctions, 'countTitle' ) );
		$wgParser->setFunctionHook( 'countline',     array( &$exFunctions, 'countLine' ) );
		$wgParser->setFunctionHook( 'searchpage',    array( &$exFunctions, 'searchPage' ) );
		$wgParser->setFunctionHook( 'ifexists',	     array( &$exFunctions, 'ifExists' ) );
		$wgParser->setFunctionHook( 'ifexistfile',   array( &$exFunctions, 'ifExistFile' ) );
		$wgParser->setFunctionHook( 'getenv',        array( &$exFunctions, 'getEnvValue' ) );
		$wgParser->setFunctionHook( 'clink',	     array( &$exFunctions, 'clink' ) );
	}
	
	/**
	 * setup function
	*/
	public static function efExFunctionsLanguageGetMagic( &$magicWords, $langCode ) {
		$magicWords['cr']            = array( 0, 'cr' );
		$magicWords['bar']           = array( 0, 'bar' );
		$magicWords['forcedbr']      = array( 0, 'forcedbr' );
		$magicWords['car']           = array( 0, 'car' );
		$magicWords['cdr']           = array( 0, 'cdr' );
		$magicWords['cadr']          = array( 0, 'cadr' );
		$magicWords['cddr']          = array( 0, 'cddr' );
		$magicWords['caddr']         = array( 0, 'caddr' );
		$magicWords['cdddr']         = array( 0, 'cdddr' );
		$magicWords['nth']           = array( 0, 'nth' );
		$magicWords['choose']        = array( 0, 'choose' );
		$magicWords['max']           = array( 0, 'max' );
		$magicWords['min']           = array( 0, 'min' );
		$magicWords['isdigit']       = array( 0, 'isdigit' );
		$magicWords['isalnum']       = array( 0, 'isalnum' );
		$magicWords['upcase']        = array( 0, 'upcase' );
		$magicWords['downcase']      = array( 0, 'downcase' );
		$magicWords['trim']          = array( 0, 'trim' );
		$magicWords['trimex']        = array( 0, 'trimex' );
		$magicWords['createstring']  = array( 0, 'createstring' );
		$magicWords['substring']     = array( 0, 'substring' );
		$magicWords['indexof']       = array( 0, 'indexof' );
		$magicWords['lastindexof']   = array( 0, 'lastindexof' );
		$magicWords['replace']       = array( 0, 'replace' );
		$magicWords['count']         = array( 0, 'count' );
		$magicWords['and']           = array( 0, 'and' );
		$magicWords['or']            = array( 0, 'or' );
		$magicWords['repeat']        = array( 0, 'repeat' );
		$magicWords['repeatnum']     = array( 0, 'repeatnum' );
		$magicWords['map']           = array( 0, 'map' );
		$magicWords['def']           = array( 0, 'def' );
		$magicWords['var']           = array( 0, 'var' );
		$magicWords['searchline']    = array( 0, 'searchline' );
		$magicWords['searchlinenot'] = array( 0, 'searchlinenot' );
		$magicWords['searchlinereg'] = array( 0, 'searchlinereg' );
		$magicWords['countline']     = array( 0, 'countline' );
		$magicWords['searchtitle']   = array( 0, 'searchtitle' );
		$magicWords['counttitle']    = array( 0, 'counttitle' );
		$magicWords['searchpage']    = array( 0, 'searchpage' );
		$magicWords['ifexists']      = array( 0, 'ifexists' );
		$magicWords['ifexistfile']   = array( 0, 'ifexistfile' );
		$magicWords['getenv']        = array( 0, 'getenv' );
		$magicWords['clink']         = array( 0, 'clink' );
	
		return true;
	}
}
