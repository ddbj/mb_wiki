<?php
/**
  Create and display graphs, powered by jpgraph.
  Last-modified: 2023.03.14

  @version 1.0
  @author K, Suwa
  @link http://mb.metabolomics.jp/wiki/Doc:Extension/CreateGraph
*/

// path
$jpgraph = '/var/www/html/jpgraph/src';

// var
$GRAPH_PIE   = 'pie';
$GRAPH_PIE3D = 'pie3';
$GRAPH_LINE  = 'line';
$GRAPH_HBAR  = 'hbar';
$GRAPH_VBAR  = 'vbar';
$GRAPH_HGBAR = 'hgbar';
$GRAPH_VGBAR = 'vgbar';

// graph color
$colors = array( 'red',
		'blue',
		'green',
		'cyan',
		'magenta',
		'pink',
		'orange',
		'yellow',
		'greenyellow',
		'seagreen',
		'olivedrab',
		'teal',
		'navy',
		'violet',
		'brown',
		'gray',
		'black',
		'white',
);

function trimValue( &$value )
{
	$value = trim( $value );
}

?>
