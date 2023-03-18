<?php
/**
  Create and display graphs, powered by jpgraph.
  Last-modified: 2023.03.14

  @version 1.0
  @author K, Suwa
  @link http://mb.metabolomics.jp/wiki/Doc:Extension/CreateGraph
*/

require_once( 'graph.inc' );
require_once( $jpgraph . '/jpgraph.php' );

// check required options
if( !isset( $_GET['graph'] ) || !isset( $_GET['data'] ) ){
	exit( "Not enough data. 'graph' and/or 'data' is necessary." );
}

$type  = $_GET['graph'];
$datas = explode( ";", preg_replace( "/;$/", "", $_GET['data'] ) );
$data_count = count ( $datas );
// check data count.
if( $data_count <= 4 )
	exit( "Not enough data. 'data' is invalid." );

$index  = 0;
$margin = array( 40, 20, 40, 40 );
$size   = preg_replace( "/^size=/",  "", $datas[$index] ); $index ++;
// get 'margin' option.
if( strncmp( $datas[$index], "margin=", 7 ) == 0 ){
	$margins = explode( ",", substr( $datas[$index], 7 ) );
	array_walk( $margins, 'trimValue' );
	for( $i = 0; $i < count( $margins ); $i ++ )
		$margin[$i] = preg_replace( "/[^0-9]/", "", $margins[$i] );
	for( ; $i < 4; $i ++ ){
		if( $i == 1 )
			$margin[$i] = 20;
		else
			$margin[$i] = 40;
	}
	for( $i = 0; $i < 4; $i ++){
		if( $margin[$i] <= 0 )
			$margin[$i] = 1;
		else if( $margin[$i] > 1000 )
			$margin[$i] = 1000;
	}
	$index ++;
}
// get other options.
$title   = preg_replace( "/^title=/", "", $datas[$index] ); $index ++;
$legend  = explode( "x", preg_replace( "/^legend=/", "", $datas[$index] ) ); $index ++;
$xlabels = preg_replace( '/\\\\n/', "\n", preg_replace( "/^label=/", "", $datas[$index] ) ); $index ++;
$xlabel_array = explode( ",", $xlabels );
array_walk( $xlabel_array, 'trimValue' );
$sep = ',';

// get option value.
$data_count = $data_count - $index;
for( $i = 0; $i < $data_count; $i ++ ){
	$tmp = explode( "=", $datas[$i+$index] );
	$label[$i] = trim( $tmp[0] );
	$data[$i] = preg_replace( "/,*$/", "", $tmp[1] );
	$data[$i] = preg_replace( "/[^-0-9\.,]*/", "", $data[$i] );
}

// get legend position.
$exh = -1;
$exv = -1;
if( ( $legend[0] == '0' || preg_match( "/0.[0-9]+/", $legend[0] ) ) )
	$exh =  $legend[0];
if( ( $legend[1] == '0' || preg_match( "/0.[0-9]+/", $legend[1] ) ) )
	$exv = $legend[1];

// get 'size' option values
$size = explode( 'x', $size );
if( count( $size ) != 2 )
	exit( "Not enough data. \'size\' contains width and height." );

$size[0] = preg_replace( "/[^0-9]*/", "", $size[0] );
$size[1] = preg_replace( "/[^0-9]*/", "", $size[1] );

// pie graph
if( strcmp( $type, $GRAPH_PIE ) == 0 || strcmp( $type, $GRAPH_PIE3D ) == 0 ){
	if( count( $data ) != count( $label ) )
		exit( "Disaccord with \'data\' number and \'label\' number." );

	require( $jpgraph . '/jpgraph_pie.php' );

	$graph = new PieGraph( $size[0], $size[1] );
	$graph->title->Set( $title );
	if( $exh != -1 && $exv != -1 )
		$graph->legend->SetPos( $exh, $exv, "left", "top" );

	$pie = '';
	if( strcmp( $type, $GRAPH_PIE ) == 0 ){
		$pie = new PiePlot( $data );
	} else {
		require( $jpgraph . '/jpgraph_pie3d.php' );
		$pie = new PiePlot3D( $data );
	}
	$pie->SetLegends( $label );

	$graph->Add( $pie );
	$graph->Stroke();

} else if( strcmp( $type, $GRAPH_LINE ) == 0 ){ // line graph
	require( $jpgraph . '/jpgraph_line.php' );

	if( count( $data ) != count( $label ) ){
		exit( "Disaccord with \'data\' number and \'label\' number." );
	}

	$graph = new Graph( $size[0], $size[1] );
	$graph->SetMargin( $margin[0], $margin[1], $margin[2], $margin[3] );
	$graph->SetScale( "textlin" );
	$graph->title->Set( $title );
	if( $exh != -1 && $exv != -1 )
		$graph->legend->SetPos( $exh, $exv, "left", "top" );

	for( $i = 0; $i < count( $label ); $i ++ ){
		$lineplot[$i] = new LinePlot( explode( ',', $data[$i] ) );
		$lineplot[$i]->SetLegend( $label[$i] );
		$lineplot[$i]->SetColor( $colors[$i % count( $colors )] );
		$graph->Add( $lineplot[$i] );
	}
	$graph->xaxis->SetTickLabels( $xlabel_array );

	$graph->Stroke();

} else if( strcmp( $type, $GRAPH_HBAR ) == 0 || strcmp( $type, $GRAPH_VBAR ) == 0 || // bar graph
		strcmp( $type, $GRAPH_HGBAR ) == 0 || strcmp( $type, $GRAPH_VGBAR ) == 0 ){
	require( $jpgraph . '/jpgraph_bar.php' );

	if( count( $data ) != count( $label ) ){
		exit( "Disaccord with \'data\' number and \'label\' number." );
	}

	$graph = new Graph( $size[0], $size[1] );
	$graph->SetScale( "textlin" );
	$graph->title->Set( $title );
	if( $exh != -1 && $exv != -1 )
		$graph->legend->SetPos( $exh, $exv, "left", "top" );

	# horizontal bar, rotate 90
	if( strcmp( $type, $GRAPH_HBAR ) == 0 || strcmp( $type, $GRAPH_HGBAR ) == 0 ){
		$graph->Set90AndMargin( $margin[0], $margin[1], $margin[2], $margin[3] );
	} else
		$graph->SetMargin( $margin[0], $margin[1], $margin[2], $margin[3] );

	$plot_array = array();
	for( $i = 0; $i < count( $label ); $i ++ ){
		$barplot[$i] = new BarPlot( explode( ',', $data[$i] ) );
		$barplot[$i]->SetLegend( $label[$i] );
		$barplot[$i]->SetFillColor( $colors[$i % count( $colors )] );
		$plot_array[] = $barplot[$i];
	}
	if( strcmp( $type, $GRAPH_VBAR ) == 0 || strcmp( $type, $GRAPH_HBAR ) == 0 )
		$plots = new AccBarPlot( $plot_array ); // stacked bar
	else
		$plots = new GroupBarPlot( $plot_array ); // multiple bar
	$graph->Add( $plots );
	$graph->xaxis->SetTickLabels( $xlabel_array );

	$graph->Stroke();
}

?>
