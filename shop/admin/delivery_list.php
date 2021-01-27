<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

include_once ("general_functions.php");

$sqlr = '
  SELECT
    '.TABLE_ROUTE.'.*,
    '.TABLE_DELCODE.'.delcode_id,
    '.TABLE_DELCODE.'.delcode,
    '.TABLE_DELCODE.'.deldesc,
    '.TABLE_DELCODE.'.route_id,
    '.TABLE_DELCODE.'.truck_code
  FROM
    '.TABLE_ROUTE.',
    '.TABLE_DELCODE.'
  WHERE
    '.TABLE_ROUTE.'.route_id = "'.$route_id.'"
    AND '.TABLE_DELCODE.'.route_id = '.TABLE_ROUTE.'.route_id
    AND '.TABLE_DELCODE.'.delcode_id = "'.$delcode_id.'"
  ORDER BY
    route_name ASC';
$rsr = @mysql_query($sqlr,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($rsr) )
  {
    $route_name = $row['route_name'];
    $route_desc  = $row['route_desc'];
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];
    $deldesc = $row['deldesc'];
    $truck_code = $row['truck_code'];
  }
$sql = '
  SELECT
    '.TABLE_BASKET_ALL.'.finalized,
    '.TABLE_BASKET_ALL.'.deltype as ddeltype,
    '.TABLE_BASKET_ALL.'.basket_id,
    '.TABLE_MEMBER.'.member_id,
    last_name,
    first_name,
    first_name_2,
    last_name_2,
    business_name,
    home_phone,
    work_phone,
    mobile_phone,
    fax,
    email_address,
    email_address_2,
    address_line1,
    address_line2,
    city,
    state,
    zip,
    work_address_line1,
    work_address_line2,
    work_city,
    work_state,
    work_zip
  FROM
    '.TABLE_BASKET_ALL.',
    '.TABLE_BASKET.',
    '.TABLE_MEMBER.'
  WHERE
    '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
    AND '.TABLE_BASKET_ALL.'.submitted = 1
    AND
      (
        '.TABLE_BASKET_ALL.'.delivery_id = "'.$current_delivery_id.'"
        OR '.TABLE_BASKET.'.future_delivery_id ="'.$current_delivery_id.'"
      )
    AND '.TABLE_BASKET_ALL.'.member_id = '.TABLE_MEMBER.'.member_id
    AND '.TABLE_BASKET_ALL.'.delcode_id = "'.$delcode_id.'"
    AND '.TABLE_BASKET.'.out_of_stock != "1"
    AND '.TABLE_BASKET.'.product_id != "1279"
    AND '.TABLE_BASKET.'.product_id != "1696"
    AND '.TABLE_BASKET.'.product_id != "2823"
    AND '.TABLE_BASKET.'.product_id != "1403"
    AND '.TABLE_BASKET.'.product_id != "1363"
  GROUP BY
    '.TABLE_BASKET_ALL.'.basket_id
  ORDER BY
    last_name ASC';
$rs = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$num_orders = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $basket_id = $row['basket_id'];
    $member_id = $row['member_id'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = stripslashes ($row['business_name']);
    $storage_code = 'ALL'; // Storage code isn't available so set to some value for convert_route_code function.

    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $ddeltype = $row['ddeltype'];
    $finalized = $row['finalized'];
    $home_phone = $row['home_phone'];
    $work_phone = $row['work_phone'];
    $mobile_phone = $row['mobile_phone'];
    $email_address = $row['email_address'];
    include("../func/show_name.php");
    $display .= '
      <table cellpadding=0 cellspacing=0 border=0><tr><td id="'.$member_id.'" width="400" valign=top>';
    $display .= '
      <li>
      <a href="customer_invoice.php?delivery_id='.$current_delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">
      <b>'.$show_name.' ('.$member_id.')</b></a><!-- - Deliverable Products: '.$product_quantity_of_member.' -->';
    $display .= '   <ul>';
    $display .= "Email: $email_address <br>";
    if ( $home_phone )
      {
        $display .= "Home: $home_phone <br>";
      }
    if ( $work_phone )
      {
        $display .= "Work: $work_phone <br>";
      }
    if ( $mobile_phone )
      {
        $display .= "Phone: $mobile_phone <br>";
      }
    $display .= '   </ul><br>';
    $display .= '</td>
        <td valign="middle">
          <a href="delivery_change.php?member_id='.$member_id.'&basket_id='.$basket_id.'">Change collection location</a>';
    $display .= '
          </td>
        </tr>
      </table>';
  }
$quantity_all = 0;
$sql_sum8 = '
  SELECT
    '.TABLE_BASKET_ALL.'.delivery_id,
    '.TABLE_BASKET_ALL.'.basket_id,
    '.TABLE_BASKET.'.basket_id,
    '.TABLE_BASKET_ALL.'.delcode_id,
    '.TABLE_BASKET.'.product_id,
    '.TABLE_PRODUCT.'.product_id,
    '.TABLE_PRODUCT.'.product_name,
    SUM('.TABLE_BASKET.'.quantity) AS sum_p,
    '.TABLE_PRODUCT.'.ordering_unit,
    '.TABLE_BASKET.'.out_of_stock,
    '.TABLE_BASKET.'.product_id,
    '.TABLE_BASKET.'.future_delivery_id
  FROM
    '.TABLE_BASKET_ALL.',
    '.TABLE_BASKET.',
    '.TABLE_PRODUCT.'
  WHERE
    '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
    AND '.TABLE_BASKET_ALL.'.submitted = 1
    AND
      (
        '.TABLE_BASKET_ALL.'.delivery_id = "'.$current_delivery_id.'"
        OR '.TABLE_BASKET.'.future_delivery_id ="'.$current_delivery_id.'"
      )
    AND '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
    AND '.TABLE_BASKET_ALL.'.delcode_id = "'.$delcode_id.'"
    AND '.TABLE_BASKET.'.product_id != "1279"
    AND '.TABLE_BASKET.'.product_id != "1696"
    AND '.TABLE_BASKET.'.product_id != "2823"
    AND '.TABLE_BASKET.'.product_id != "1403"
    AND '.TABLE_BASKET.'.product_id != "1363"
    AND '.TABLE_BASKET.'.out_of_stock != "1"
    AND
      (
        '.TABLE_BASKET.'.future_delivery_id ="'.$current_delivery_id.'"
        OR '.TABLE_BASKET.'.future_delivery_id ="0"
      )
  GROUP BY
    '.TABLE_PRODUCT.'.product_id
  ORDER BY
    sum_p DESC';
$result_sum8 = @mysql_query($sql_sum8,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($result_sum8) )
  {
    $product_id = $row['product_id'];
    $product_name = $row['product_name'];
    $product_quantity = $row['sum_p'];
    $ordering_unit = $row['ordering_unit'];
    $display_p .= "
      <tr><td align=\"right\">$product_quantity</td>
      <td align=\"left\">$ordering_unit </td><td>&nbsp; $product_name (# $product_id)</td></tr>";
    $quantity_all += $row['sum_p'];
  }

include("template_hdr.php");
?>
<!-- CONTENT BEGINS HERE -->
<div align="center">
<table width="800" bgcolor="#FFFFFF" cellspacing="2" cellpadding="2" border="0">
  <tr>
    <td align="left">
      <h3>Orders by Collection Location - <?php  echo $current_delivery_date;?></h3>
    </td>
  </tr>
  <tr>
    <td align="left" bgcolor="#DDDDDD">
      <b>Area: <?php echo $route_name;?></b><br>
      <?php echo $route_desc;?><br><br></td>
  </tr>
  <tr>
    <td align="left" bgcolor="#DDDDDD">
      <b>Collection Location: <?php echo $delcode;?> (<?php echo $delcode_id;?>)</b><br>
      <?php echo $deldesc;?><br><br>
    </td>
  </tr>
  <tr>
    <td align="left">
      <b>Members with Orders (<?php echo $num_orders;?>)</b>
      <ul>
        <?php echo $display;?>
      </ul><br>
      <b><?php echo $quantity_all;?> Products at this Collection Location</b>
      <blockquote>
        <table>
          <?php echo $display_p;?>
        </table>
      </blockquote>
    </td>
  </tr>
</table>
</div>
  <!-- CONTENT ENDS HERE -->

<?php include("template_footer.php"); ?>
