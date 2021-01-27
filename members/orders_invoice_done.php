<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
session_start();
validate_user();

$sql = '
  SELECT
    member_id,
    delivery_id,
    invoice_content
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    member_id = '.$member_id.'
    AND delivery_id = '.$delivery_id;
$rs = @mysql_query($sql,$connection) or die("Couldn't execute query.");
$num = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $invoice_content = $row['invoice_content'];
  }
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <title><?php echo "Past Invoice for account #$member_id"; ?></title>
  </head>
<body>
<font face="arial" size="-1">


  <!-- CONTENT BEGINS HERE -->

<?php echo $invoice_content;?>

  <!-- CONTENT ENDS HERE -->

<?php include("template_footer_orders.php");?>