<html>
<head>
<title>Ganglia: Metric <?php echo $_GET['g']; ?> for <?php echo $_GET['h'] ?></title>
</head>
<body>
<?php
# build a query string but drop r and z since those designate time window and size


if ( isset($_GET["clear_cluster"] ) )  { 
	unset ( $_GET["set_cluster"] ) ; 
	unset ( $_GET["clear_cluster"] ) ; 
}
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
if ( isset($_POST['percentile_value']) || isset($_POST['image_width']) || isset($_POST['image_height']) ) { 
	if ( isset ( $_GET["set_cluster"] ) ) { 
		$var = $_GET['set_cluster'] ;
		$query_string .= "&set_cluster=$var" ;
	}
}
?>
<form action="zoomview.php?r=<?php echo $_GET['r'] ?>&z=<?php echo $_GET['z'] ?><?php echo $query_string ?>" method="post">
Enter Percentile: <input type="text" name="percentile_value" />
Enter Width: <input type="text" name="image_width" />
Enter Height: <input type="text" name="image_height" />
<input type="submit" />
</form>
<table >
<tr><td rowspan="2"><a  href="zoomview.php?r=<?php echo $_GET['r'] ?>&z=<?php echo $_GET['z'] ?><?php echo $query_string ?>&set_cluster=1" ><button type="button" > COMPARE THE CLUSTER </button></a></td>
<tr><td rowspan="2"><a  href="zoomview.php?r=<?php echo $_GET['r'] ?>&z=<?php echo $_GET['z'] ?><?php echo $query_string ?>&clear_cluster=1" ><button type="button" > SWITCH TO SINGLE HOST </button></a></td>
</tr>
</table>

<b>Host: </b><?php echo $_GET['h'] ?>&nbsp;<b>Metric/Graph: </b><?php if (isset($_GET['g'])) echo $_GET['g']; else echo $_GET['m']; ?>
<br />
<p> 
<?php 
if ( $_GET["set_cluster"] ) 
print "<img src=\"graph_agg.php?$query_string&set_cluster=1\">" ;
else
print "<img src=\"graph.php?$query_string\">" ;
?>
</p>
</body>
</html>
