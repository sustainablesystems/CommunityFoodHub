<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();
$date_today = date("F j, Y");

include_once ('../func/delivery_funcs.php');

$display = '<h1 align="center">Cashier Report by Collection Location</h1>';
$display .= '<table align="center" width="800" border="0" cellpadding="2" cellspacing="0">';

$delivery_id = $_GET["delivery_id"];

$rs_hubs = get_delivery_locations_for_cycle($delivery_id);
$hub_count = mysql_num_rows($rs_hubs);

if ($hub_count > 0)
{
  $display .= '<tr><td><ul>';
  while ( $row = mysql_fetch_array($rs_hubs) )
  {
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];

    $display .= '<li><a href="cashier_report.php?delivery_id='.$delivery_id.'&delcode_id='.$delcode_id.'">'.$delcode.'</a>';
  }
  $display .= '</ul></td></tr>';
}
else // No orders for any collection locations this cycle
{
  $display .= '<tr><td align="center">-- No orders this cycle --</td></tr>';
}

$delcode_id_curr = $_GET["delcode_id"];
if ( $delcode_id_curr )
{
  // Display the cashier report for the current collection location

  $sql_member_baskets = '
    SELECT
      first_name,
      last_name,
      mobile_phone,
      grand_total_coop,
      prev_balance
    FROM
      '.TABLE_BASKET_ALL.'
    INNER JOIN '.TABLE_MEMBER.'
    ON '.TABLE_BASKET_ALL.'.member_id='.TABLE_MEMBER.'.member_id
    WHERE
      '.TABLE_BASKET_ALL.'.submitted = 1
      AND '.TABLE_BASKET_ALL.'.delivery_id = '.$delivery_id.'
      AND '.TABLE_BASKET_ALL.'.delcode_id = "'.$delcode_id_curr.'"
    ORDER BY
      '.TABLE_MEMBER.'.last_name ASC';

  $rs_member_baskets = @mysql_query($sql_member_baskets,$connection) or die(mysql_error()
          . "<br><b>Error No: </b>" . mysql_errno());
  $orders_count = mysql_num_rows($rs_member_baskets);

  $display_table_header .= "<tr><td><font size='+2'><b>Cashier Report for ".$delcode_id_curr.
    "&nbsp;&nbsp;&nbsp;&nbsp;Total Orders: ".$orders_count;
  $display_table_body .= '</b></font></td></tr></table>
    <table align="center" width="800" cellspacing="0" cellpadding="0" border="1">
    <tr>
      <th>Customer</th>
      <th>Phone</th>
      <th>Subtotal</th>
      <th width="42%">Adjustments</th>
      <th width="8%">Total</th>
      <th width="8%">Paid</th>
      <th width="8%">Cheque</th>
    </tr>';

  $orders_grand_total = 0;
  while ( $row = mysql_fetch_array($rs_member_baskets) )
  {
    $last_name = $row["last_name"];
    $first_name = $row["first_name"];
    $phone = $row["mobile_phone"];
    $order_total = $row["grand_total_coop"];
    $orders_grand_total += $order_total + $prev_balance;
    $prev_balance = $row["prev_balance"];

    $display_table_body .= '
      <tr>
        <td>'.$last_name.', '.$first_name.'</td>
        <td align="right">'.$phone.'</td>
        <td align="right">'.CURSYM.number_format($order_total + $prev_balance,2).'</td>
        <td>&nbsp;<br>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>';
  }

  $display_table_header .= '&nbsp;&nbsp;&nbsp;&nbsp;Grand Total: '.CURSYM.number_format($orders_grand_total,2);

  $display .= $display_table_header.$display_table_body;

  // Add some blank rows at the bottom of the table for people who turn up
  // on the day and order any leftovers
  for ( $blank_row_count = 0; $blank_row_count < 3; $blank_row_count++ )
  {
     $display .= '
        <tr>
          <td>&nbsp;<br>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>';
  }
}

$display .= '</table>';

?>

<html>
<head>
<title>Cashier Report by Collection Location</title>
<body bgcolor="#FFFFFF">
<?php
  include("template_hdr.php");

  echo $display;

  include("template_footer.php");
?>
