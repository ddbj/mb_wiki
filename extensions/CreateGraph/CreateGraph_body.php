<?php
/**
  CreateGraph.php Mediawiki extension. Create and display graphs, powered by jpgraph.
  Last-modified: 2023.03.16

  @version 1.0
  @author K, Suwa
  @link http://mb.metabolomics.jp/wiki/Help:Extension/CreateGraph
*/

use MediaWiki\MediaWikiServices;

class CreateGraph
{
	/** @var string jpgraph path. assume jpgraph v4.4.1. */
	public static string $DEFAULT_SCRIPT_PATH = '/mediawiki/scripts/graph/graph.php';

	/**
	 * create graph parser function
	 * e.g. {{#graph:vbar|size=300x300;title=test;legend=0x0;label=a,b,c;data1=10,34,20}} => [create bar graph]
	 *
	 * @param Parser $parser  parent parser
	 * @param string $type    type of graph ( line, hbar, vbar, hgbar, vgbar, pie, pie3 )
	 * @param string $data    data which separated ';'
	 * @return string <graph> tag extension
	*/
	function graph( &$parser, $type = '', $data = '' )
	{
		$str = '<graph graph="' . $type . '" data="' . $data . '" />';
		return array( $parser->preprocessToDom( $str ), 'isChildObj' => true );
	}

	/**
	 * create graph tag function. This method is called by {{#graph:---}}.
	 *
	 * @param string $input  text that is between tags.
	 * @param string $args   jpgraph options
	 * @param Parser $parser parent parser
	 * @return string <img> tag that calls jpgraph
	*/
	function replaceGraphLink( $input, $args, $parser )
	{
		global $egScriptPath;

		// check required args.
		if( !isset( $args['graph'] ) || strlen( $args['graph'] ) == 0 ||
			!isset( $args['data'] )  || strlen( $args['data']  ) == 0 )
			return '<b style="color: red">CreateGraph - Not found \'graph\' and/or \'data\' param.</b>';

		// check jpgraph path
		if( !$egScriptPath )
			$egScriptPath = CreateGraph::$DEFAULT_SCRIPT_PATH;

		return '<img src="' . $egScriptPath . '?graph=' . $args['graph'] . '&data=' . $args['data'] . '" />';	
	}

	/**
	 * setup function
	*/
	public static function SetupCreateGraph()
	{
		$wgParser = MediaWikiServices::getInstance()->getParser();
	
		$createGraph = new CreateGraph;
	
		$wgParser->setFunctionHook( 'graph',  array( &$createGraph, 'graph' ) );
		$wgParser->setHook( 'graph',          array( &$createGraph, 'replaceGraphLink' ) );
	}

	/**
	 * setup function
	*/
	public static function CreateGraphLanguageGetMagic( &$magicWords, $langCode ) {
		$magicWords['graph']  = array( 0, 'graph' );
	
		return true;
	}
}
	
