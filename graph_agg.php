<?php
# build a query string but drop r and z since those designate time window and size
$query_string = "";
$percentile = "";
foreach ($_GET as $key => $value) {
    $query_string .= "&$key=$value";
}
if ( isset($_POST['percentile_value']) && $_POST['percentile_value'] ) { 
  $percentile = $_POST['percentile_value'];
  $query_string .= "&percentile_value=$percentile";
}
if ( isset($_POST['image_width']) && $_POST['image_width']) { 
  $image_width = $_POST['image_width'];
  $query_string .= "&width=$image_width";
}
if ( isset($_POST['image_height']) && $_POST['image_height'] ) { 
  $image_height = $_POST['image_height'];
  $query_string .= "&height=$image_height";
}

/* $Id: index.php 2065 2009-09-10 17:12:13Z d_pocock $ */
include_once "./eval_config.php";
# ATD - function.php must be included before get_context.php.  It defines some needed functions.
include_once "./functions.php";
include_once "./get_context.php";
include_once "./ganglia.php";
include_once "./get_ganglia.php";
include_once "./class.TemplatePower.inc.php";
if ($context == "host" && isset($_GET["set_cluster"] )) { 
	$current_cluster  = get_hosts_for_custer($clustername ,$rrds);
	$all_rrds = get_rrds_path_by_metrics($clustername , $rrds , $metricname ) ; 
	$command_args = build_rrdgraph_command($all_rrds) ; 
	$size = isset($_GET["z"]) && in_array( $_GET[ 'z' ], $graph_sizes_keys )
             ? $_GET["z"] : "default";
	$final_size = build_graph_size ($graph_sizes , $size ) ;
	$width = $final_size[0] ;
	$height = $final_size[1] ;
	$title = "$clustername - $metricname" ;
	$command = RRDTOOL . " graph - --title='$title' --start $start --end $end --width $width --height $height --vertical-label 'unit' $command_args" ;
	if(  $command) {
	    /*Make sure the image is not cached*/
	    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");   // Date in the past
	    header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	    header ("Cache-Control: no-cache, must-revalidate");   // HTTP/1.1
	    header ("Pragma: no-cache");                     // HTTP/1.0
	    if ( isset ( $_GET["debug"]  ) ) {
		$debug = $_GET["debug"] ;
	    } else 
		$debug = NULL ; 
	    if ($debug>2) {
		header ("Content-type: text/html");
		print "<html><body>";
		print htmlentities( $command );
		print "</body></html>";
		
	    } else {
		header ("Content-type: image/png");
		  passthru($command);
	    }
	}
}
# Usefull for addons.
function build_graph_size ($graph_sizes , $size ) {
	# Assumes we have a $start variable (set in get_context.php).
	# $graph_sizes and $graph_sizes_keys defined in conf.php.  Add custom sizes there.
	if ( isset($_GET['height'] ) )
	  $height = $_GET['height'];
	elseif ( isset($_POST['image_height']) ) // saurabh.ve: Simple post acceptance for image height 
	  $height = $_POST['image_height'];
	else
	  $height  = $graph_sizes[ $size ][ 'height' ];

	if ( isset($_GET['width'] ) )
	  $width =  $_GET['width'];
	elseif ( isset($_POST['image_width']) ) // saurabh.ve: Simple post acceptance for image height 
	  $width = $_POST['image_width'];
	else
	  $width = $graph_sizes[ $size ][ 'width' ];
	$final_size = array() ; 
	array_push ( $final_size , $width ) ; 	
	array_push ( $final_size , $height ) ; 	
	return $final_size ; 
}
		
function get_hosts_for_custer($cluster_name, $rrd_dir) { 
	$handle = opendir("$rrd_dir/$cluster_name") ;
	$notpattern = '/^__/';
	$server_array = array() ; 
        while (false !== ($file = readdir($handle))) {
		if ( ! preg_match( "/__Summar|^\.|^\.\./",$file ) ) {
			array_push ( $server_array , $file ) ;  
		}
	}
	return $server_array ; 
}	

function get_rrds_path_by_metrics ( $cluster_name, $rrd_dir , $metric ) {
	$handle = opendir("$rrd_dir/$cluster_name") ;
	$rrd_path_array = array() ;
	$current_cluster = get_hosts_for_custer($cluster_name ,$rrd_dir);
	$i2 = 0 ;
	foreach (  $current_cluster as $i => $value ) { 
		$host = $current_cluster[$i]; 
		$cluster_hosts = array(); 
		// check the RRD file for this metric exists or not
		// TODO: build list of hosts which doesn't have this metric and alert the web frontend user 
		if ( file_exists ( "$rrd_dir/$cluster_name/$host/$metric.rrd" ) ) { 
			$rrd_path_array[$i2]["host"] = $host ; 
			$rrd_path_array[$i2]["path"] = "$rrd_dir/$cluster_name/$host/$metric.rrd"  ; 
			$i2++; 
		}
	}
	return $rrd_path_array ; 
}

//Main Function that build the RRD graph command 
function build_rrdgraph_command ( $all_rrds_path ) { 
	// First build the DEFinitions 
	$cf_func = "AVERAGE" ;
	$graph_type = "LINE1" ; 
	$def  = "" ;
	$cdef = "" ;  
	$final_string = ""; 
	$draw = ""; 
	$color_list = get_random_colors (sizeof ( $all_rrds_path ) ) ; 
	foreach ( $all_rrds_path as $i => $value ) { 
		$host = $all_rrds_path[$i]["host"] ;
		$rrd_path = $all_rrds_path[$i]["path"] ;
		$def = "DEF:'sum$i'='$rrd_path:sum':$cf_func " ;
		$draw = "$graph_type:'sum${i}'$color_list[$i]:\"$host\l\" ";
		$cdef_vdef_gprint = "CDEF:sum${i}_pos=sum${i},0,LT,0,sum${i},IF VDEF:sum${i}_last=sum${i}_pos,LAST VDEF:sum${i}_min=sum${i}_pos,MINIMUM VDEF:sum${i}_avg=sum${i}_pos,AVERAGE VDEF:sum${i}_max=sum${i}_pos,MAXIMUM GPRINT:'sum${i}_last':'Now\:%7.2lf%s' GPRINT:'sum${i}_min':'Min\:%7.2lf%s' GPRINT:'sum${i}_avg':'Avg\:%7.2lf%s' GPRINT:'sum${i}_max':'Max\:%7.2lf%s\l' " ;
		$final_string .= $def ; 
		$final_string .= $draw ; 
		$final_string .= $cdef_vdef_gprint ; 
	} 
	return $final_string ; 
}

function get_random_colors ( $num_of_colors ) { 
	// TODO build rendom colors based on http://www.utexas.edu/learn/html/colors.html
		
	$colors = array( "#000000","#000033","#000066","#000099","#0000CC","#0000FF","#003300","#003333","#003366","#003399","#0033CC","#0033FF","#006600","#006633","#006666","#006699","#0066CC","#0066FF","#009900","#009933","#009966","#009999","#0099CC","#0099FF","#00C000","#00CC00","#00CC33","#00CC66","#00CC99","#00CCCC","#00CCFF","#00FF00","#00FF33","#00FF66","#00FF99","#00FFCC","#00FFFF","#330000","#330033","#330066","#330099","#3300CC","#3300FF","#333300","#333333","#333366","#333399","#3333CC","#3333FF","#336600","#336633","#336666","#336699","#3366CC","#3366FF","#339900","#339933","#339966","#339999","#3399CC","#3399FF","#33CC00","#33CC33","#33CC66","#33CC99","#33CCCC","#33CCFF","#33FF00","#33FF33","#33FF66","#33FF99","#33FFCC","#33FFFF","#660000","#660033","#660066","#660099","#6600CC","#6600FF","#663300","#663333","#663366","#663399","#6633CC","#6633FF","#666600","#666633","#666666","#666699","#6666CC","#6666FF","#669900","#669933","#669966","#669999","#6699CC","#6699FF","#66CC00","#66CC33","#66CC66","#66CC99","#66CCCC","#66CCFF","#66FF00","#66FF33","#66FF66","#66FF99","#66FFCC","#66FFFF","#990000","#990033","#990066","#990099","#9900CC","#9900FF","#993300","#993333","#993366","#993399","#9933CC","#9933FF","#996600","#996633","#996666","#996699","#9966CC","#9966FF","#999900","#999933","#999966","#999999","#9999CC","#9999FF","#99CC00","#99CC33","#99CC66","#99CC99","#99CCCC","#99CCFF","#99FF00","#99FF33","#99FF66","#99FF99","#99FFCC","#99FFFF","#CC0000","#CC0033","#CC0066","#CC0099","#CC00CC","#CC00FF","#CC3300","#CC3333","#CC3366","#CC3399","#CC33CC","#CC33FF","#CC6600","#CC6633","#CC6666","#CC6699","#CC66CC","#CC66FF","#CC9900","#CC9933","#CC9966","#CC9999","#CC99CC","#CC99FF","#CCCC00","#CCCC33","#CCCC66","#CCCC99","#CCCCCC","#CCCCFF","#CCFF00","#CCFF33","#CCFF66","#CCFF99","#CCFFCC","#CCFFFF","#FF0000","#FF0033","#FF0066","#FF0099","#FF00CC","#FF00FF","#FF3300","#FF3333","#FF3366","#FF3399","#FF33CC","#FF33FF","#FF6600","#FF6633","#FF6666","#FF6699","#FF66CC","#FF66FF","#FF9900","#FF9933","#FF9966","#FF9999","#FF99CC","#FF99FF","#FFCC00","#FFCC33","#FFCC66","#FFCC99","#FFCCCC","#FFCCFF","#FFFF00","#FFFF33","#FFFF66","#FFFF99","#FFFFCC","#FFFFFF" ) ;  
	$total_colors = sizeof($colors) ; 
	$color_list = array() ; 
	for ( $i = 0 ; $i < $num_of_colors ; $i++ ) { 
		$rand = rand(0,$total_colors) ;
		array_push ( $color_list , $colors[$rand] ) ; 
	}
	return $color_list ; 
}	
?>
