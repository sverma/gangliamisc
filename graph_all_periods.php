<html>
<head>
<title>Ganglia: Metric <?php echo $_GET['g']; ?> for <?php echo $_GET['h'] ?></title>
</head>
<body>
<?php

$query_string = "";
$self_query = "" ;
$percentile = "";

# build a query string but drop r and z since those designate time window and size
foreach ($_GET as $key => $value) {
  if ($key != "r" && $key != "z")
    $query_string .= "&$key=$value";
  if ( $key != "percentile_value" && $key != "width" && $key != "height" ) 
    $self_query .= "&$key=$value";
}
if ( $_POST['percentile_value'] ) { 
  $percentile = $_POST['percentile_value'];
  $query_string .= "&percentile_value=$percentile";
}
if ( $_POST['image_width'] ) { 
  $image_width = $_POST['image_width'];
  $query_string .= "&width=$image_width";
}
if ( $_POST['image_height'] ) { 
  $image_height = $_POST['image_height'];
  $query_string .= "&height=$image_height";
}


?>
<form action="graph_all_periods.php?r=<?php echo $_GET['r'] ?>&z=<?php echo $_GET['z'] ?><?php echo $query_string ?>" method="post">
Enter Percentile: <input type="text" name="percentile_value" />
Enter Width: <input type="text" name="image_width" />
Enter Height: <input type="text" name="image_height" />
<input type="submit" />
</form>
<p>
<a href="graph_all_periods.php?r=<?php echo $_GET['r'] ?>&z=<?php echo $_GET['z'] ?><?php echo $self_query ?>">
<button type="button" > DEFAULT  SETTING </button>
</a>
</p>

<b>Host: </b><?php echo $_GET['h'] ?>&nbsp;<b>Metric/Graph: </b><?php if (isset($_GET['g'])) echo $_GET['g']; else echo $_GET['m']; ?>
<br />

<a href="zoomview.php?r=hour&z=large<?php echo $query_string ?>"><img src="graph.php?r=hour&z=large<?php echo $query_string ?>"></a>
<a href="zoomview.php?r=day&z=large<?php echo $query_string ?>"><img src="graph.php?r=day&z=large<?php echo $query_string ?>"></a>
<p />

<a href="zoomview.php?r=week&z=large<?php echo $query_string ?>"><img src="graph.php?r=week&z=large<?php echo $query_string ?>"></a>
<a href="zoomview.php?r=month&z=large<?php echo $query_string ?>"><img src="graph.php?r=month&z=large<?php echo $query_string ?>"></a>

<!--- Scale the yearly image to 100% width --->

<a href="zoomview.php?r=year&z=extralarge<?php echo $query_string ?>"><img width=100% src="graph.php?r=year&z=extralarge<?php echo $query_string ?>"></a>
</body>
</html>
