<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

$display_history .= '<table cellspacing="0" cellpadding="3" border="1" align="center">';

$display_history .= '<tr><th>Cycle Closing Date</th>
    <th>Customer Invoices</th><th>Customer Payments</th>
    <th>Cashier Report</th>';

/*
 * Disabling, as history is incomplete - we only keep old baskets in the database,
 * not old product lists so these are misleading as they combine old basket data with current product list data.
 * Ideally, we would store finalized producer invoices in the database, as we do for customer invoices.
 */
/*
$display_history .= '<th>Who Ordered What</th>
    <th>Producer Invoices/Reports</th><th>Product Price List</th>';*/

$display_history .= '</tr>';

$sql = '
  SELECT
    delivery_id,
    delivery_date
  FROM
    '.TABLE_DELDATE.'
  ORDER BY
    delivery_id DESC';
$rs = @mysql_query($sql,$connection) or die("Couldn't execute query.");
$delivery_ctr = 0;
while ( $row = mysql_fetch_array($rs) )
  {
    $delivery_id = $row['delivery_id'];
    $delivery_date = $row['delivery_date'];
    include("../func/convert_delivery_date.php");

    $display_history .= '<tr>';
    $display_history .= '<td>'.$delivery_date.'</td>';
    $display_history .= '<td align="center"><a href="orders_list.php?delivery_id='.$delivery_id.'">View</a></td>';
    $display_history .= '<td align="center"><a href="ctotals_onebutton.php?delivery_id='.$delivery_id.'">View/Update</a> |
      <a href="ctotals_reports.php?delivery_id='.$delivery_id.'">Details</a></td>';
    $display_history .= '<td align="center"><a href="cashier_report.php?delivery_id='.$delivery_id.'">View</a></td>';

    /*
     * Disabling, as history is incomplete - we only keep old baskets in the database,
     * not old product lists so these are misleading as they combine old basket data with current product list data.
     * Ideally, we would store finalized producer invoices in the database, as we do for customer invoices.
     */
    /*
    $display_history .= '<td align="center"><a href="who_ordered_what.php?delivery_id='.$delivery_id.'">View</a></td>';
    $display_history .= '<td align="center"><a href="orders_prdcr_list.php?delivery_id='.$delivery_id.'">View</a></td>';
    if (0 == $delivery_ctr)
    {
      $display_history .= '<td align="center"><a href="price_list_full.php?cycle=curr">Current</a></td>';
    }
    else if (1 == $delivery_ctr)
    {
      $display_history .= '<td align="center"><a href="price_list_full.php?cycle=prev&delivery_id='.$delivery_id.'">Previous</a></td>';
    }
    else
    {
      $display_history .= '<td align="center">Not available</td>';
    }*/

    $display_history .= '</tr>';
    $delivery_ctr++;
  }

$display_history .= '</table>';

include("template_hdr.php");
?>
<!-- CONTENT BEGINS HERE -->
<h1 align="center">Order Cycle History</h1>

<?php echo $display_history;?>

<!-- CONTENT ENDS HERE -->
<?php include("template_footer.php"); ?>
