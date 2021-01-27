<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

$display = '
  <tr bgcolor=#EEEEEE><th>Location</th><th>Total Orders</th><th>Total Sales</th></tr>';
$sql = mysql_query('
  SELECT
    SUM(co.grand_total) AS grand_total,
    dc.delcode_id,
    count(co.basket_id) as total_baskets
  FROM
    '.TABLE_BASKET_ALL.' co,
    '.TABLE_DELCODE.' dc
  WHERE
    co.delcode_id = dc.delcode_id
    AND co.submitted = 1
  GROUP BY
    dc.delcode_id
  ORDER BY
    dc.delcode_id ASC');
$num_orders = mysql_numrows($sql);
while ( $row = mysql_fetch_array($sql) )
  {
    $display .= '
      <tr><td><b>'.$row['delcode_id'].'</b></td>
      <td align=right>'.$row['total_baskets'].'</td><td align=right>'.CURSYM.number_format($row['grand_total'],2).'</td></tr>
      ';
  }
include("template_hdr.php");?>

<!-- CONTENT BEGINS HERE -->
<div align="center">
<table width="800">
  <tr>
    <td align="left">
      <h3>Total Orders and Sales per Collection Location (over all order cycles)</h3>
      <table cellpadding="2" cellspacing="2" border="0">
        <?php echo $display;?>
      </table>
    </td>
  </tr>
</table>
</div>
<!-- CONTENT ENDS HERE -->
<?php include("template_footer.php");?>
</body>
</html>
