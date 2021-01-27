<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
include("template_hdr.php");
?>

<br>
<table style="text-align: left;" border="1">

<?php
$sql = "SELECT * FROM producers_registration ORDER BY member_id DESC";
$rs = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$first = 1;
while ($row = mysql_fetch_array($rs))
{
	if ($first)
	{
		$keys = array_keys($row);
		$first = 0;
	}
	
	echo "<tr>\n";
	for ($i = 1; $i < count($keys); $i+=2)
	{
		echo "<th>$keys[$i]</th> ";
	}
	echo "</tr>\n";

	echo "<tr>\n";
	for ($i = 1; $i < count($keys); $i+=2)
	{
		echo "<td style='vertical-align: top;'>".$row[$keys[$i]]."</td>\n";
	}
	echo "</tr>\n";
}
?>

</table>

<?php include("template_footer.php"); ?>
