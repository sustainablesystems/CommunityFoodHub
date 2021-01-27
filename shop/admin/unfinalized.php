<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

$sql = '
  SELECT
    '.TABLE_BASKET_ALL.'.basket_id AS big_basket_id,
    '.TABLE_BASKET_ALL.'.basket_id,
    '.TABLE_BASKET_ALL.'.member_id,
    '.TABLE_BASKET_ALL.'.delivery_id,
    '.TABLE_BASKET_ALL.'.invoice_content,
    '.TABLE_BASKET_ALL.'.delivery_id AS basket_delivery_id,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_BASKET_ALL.'.finalized,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_DELCODE.'.delcode_id AS collection_location_id,
    '.TABLE_BASKET_ALL.'.delcode_id,
    '.TABLE_DELDATE.'.delivery_id,
    '.TABLE_DELDATE.'.delivery_date
  FROM
    '.TABLE_BASKET_ALL.'
  JOIN '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
  JOIN '.TABLE_DELCODE.' ON '.TABLE_DELCODE.'.delcode_id = '.TABLE_BASKET_ALL.'.delcode_id
  JOIN '.TABLE_DELDATE.' ON '.TABLE_DELDATE.'.delivery_id = '.TABLE_BASKET_ALL.'.delivery_id
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id < "'.$current_delivery_id.'"
    AND '.TABLE_BASKET_ALL.'.submitted = 1
    AND
      (
        '.TABLE_BASKET_ALL.'.finalized != "1"
        OR '.TABLE_BASKET_ALL.'.invoice_content = ""
        OR
          (
            '.TABLE_BASKET_ALL.'.invoice_content NOT LIKE CONCAT("%",members.last_name,"%")
            AND '.TABLE_BASKET_ALL.'.invoice_content NOT LIKE CONCAT("%",members.first_name,"%")
          )
      )
  ORDER BY
    '.TABLE_MEMBER.'.last_name ASC,
    '.TABLE_BASKET_ALL.'.delivery_id';
$rs = @mysql_query($sql,$connection) or die("<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href=\"mailto:".WEBMASTER_EMAIL."\">".WEBMASTER_EMAIL."</a><br><br><b>Error:</b> Listing customer orders " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$num_orders = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $delivery_id = $row['delivery_id'];
    $delcode_id = $row['collection_location_id'];
    $basket_id = $row['big_basket_id'];
    $basket_delivery_id = $row['basket_delivery_id'];
    $member_id = $row['member_id'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = stripslashes ($row['business_name']);
    $finalized = $row['finalized'];
    $invoice_content = $row['invoice_content'];
    $delivery_date = $row['delivery_date'];

    include("../func/show_name_last.php");
    $display .= '<tr bgcolor="#FFFFFF">';
    if ( $basket_delivery_id == $delivery_id )
      {
        $nump='';
        $sql_sum6 = '
          SELECT
            '.TABLE_BASKET_ALL.'.delivery_id,
            '.TABLE_BASKET_ALL.'.basket_id,
            '.TABLE_BASKET.'.basket_id,
            '.TABLE_BASKET.'.out_of_stock,
            SUM('.TABLE_BASKET.'.quantity) AS sumq
          FROM
            '.TABLE_BASKET_ALL.',
            '.TABLE_BASKET.'
          WHERE
            '.TABLE_BASKET_ALL.'.delivery_id = "'.$delivery_id.'"
            AND '.TABLE_BASKET_ALL.'.submitted = 1
            AND '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
            AND '.TABLE_BASKET_ALL.'.basket_id = "'.$basket_id.'"
            AND '.TABLE_BASKET.'.out_of_stock != "1"
          GROUP BY '.TABLE_BASKET_ALL.'.basket_id';
        $result_sum6 = @mysql_query($sql_sum6,$connection) or die("<br><br>You found a bug.
          If there is an error listed below, please copy and paste the error into an email to
          <a href=\"mailto:".WEBMASTER_EMAIL."\">".WEBMASTER_EMAIL."</a><br><br><br>
          <b>Error:</b> Counting " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        while ( $row = mysql_fetch_array($result_sum6) )
          {
            $nump = $row['sumq'];
          }
      }
    else
      {
        $nump = 'Pre-ordered';
      }
    if ( $nump == '' )
      {
        $display .= '
          <td valign="top" align="center" bgcolor="#CC9900" id="'.$basket_id.'">0</td>';
      }
    else
      {
        $display .= '
          <td valign="top" align="center" bgcolor="#AEDE86" id="'.$basket_id.'">'.$nump.'</td>';
      }
    $display .= '
      <td valign="top" align="right"># '.$member_id.'</td>';
    if ( $finalized != 1 )
      {
        $display .= '<td valign="top"><a href="orders.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">'.$show_name.'</a></td>';
      }
    else
      {
        $display .= '<td valign="top">'.$show_name.'</td>';
      }

    if ( $basket_delivery_id != $delivery_id )
      {
        $display .= '<td>*Need to <a href="orders_selectmember.php">create an invoice</a> for this cycle*</td>';
      }
    else
      {
        if ( $finalized != 1 )
          {
            $display .= '
              <td valign="top"><font size="2"><a href="customer_invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">View Temp. Inv.</a></font></td>';
          }
        else
          {
            $display .= '
              <td valign="top"><font size="2"><a href="customer_invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">View Temp. Inv.</a></font></td>';
          }
      }
    include("../func/convert_delivery_date.php");
    if ( $finalized == 1 )
      {
        if ( $invoice_content != "" )
          {
            $display .= '
                <td valign="top" bgcolor="#FF8686">May be finalised to incorrect member</td>
                <td valign="top">'.$delivery_date.'</td>
              </tr>';
          }
        else
          {
            $display .= '
                <td valign="top" bgcolor="#DEAE86">Final version missing from database</td>
                <td valign="top">'.$delivery_date.'</td>
              </tr>';
          }
      }
    else
      {
        $display .= '
            <td valign="top" bgcolor="#AEDE86">Final version not yet saved</td>
            <td valign="top">'.$delivery_date.'</td>
          </tr>';
      }
  }
?>
<?php include("template_hdr.php");?>
<div align="right">
  [ <a href="index.php">Main Page</a>
  | <a href="ctotals_onebutton.php?delivery_id=<?php echo $delivery_id;?>">Customer Totals</a>
  | <a href="logout.php">Logout</a> ]
</div>
<!-- CONTENT BEGINS HERE -->
<div align="center">
<table width="800">
  <tr>
    <td align="left">
      <h3>Unfinalised Orders Prior to Current Order Cycle</h3>
      <table bgcolor="#DDDDDD" cellpadding="2" cellspacing="2" border="0">
        <tr bgcolor="#AEDE86">
          <th valign="bottom" bgcolor="#CC9900" align="center"><font face="arial" size="-2"># Prod.<br>in Basket</th>
          <th align="center">Mem. ID</th>
          <th align="center">Member (Click to Edit Order)</th>
          <th align="center">Temp. Invoice</th>
          <th align="center">Finalised After Delivery</th>
          <th align="center">Delivery Date</th>
        </tr>
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
