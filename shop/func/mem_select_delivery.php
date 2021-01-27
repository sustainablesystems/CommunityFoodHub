<?php
echo '
  <script type="text/javascript">
    <!--
    function Load_deltype() {
    var deltype = document.delivery.deltype.options[document.delivery.deltype.selectedIndex].value
    var id_txt = "?deltype="
    location = id_txt + deltype
    }
    -->
  </script>';
if ( $qy == 'jk' )
  {
    if ( !$delcode_id )
      {
        $message2 = '<b>Please choose a Collection Location.</b><br>';
      }
    elseif ( !$deltype )
      {
        $message2 = '<b>Please choose Home, Work, or Pick up.</b>';
      }
    elseif ( !$payment_method )
      {
        $message2 = '<b>Please choose a Payment Method.</b>';
      }
    if ( $deltype && $delcode_id && $payment_method )
      {
        $sql4 = '
          SELECT
            delivery_id,
            member_id,
            basket_id
          FROM
            '.TABLE_BASKET_ALL.'
          WHERE
            delivery_id = "'.$current_delivery_id.'"
            AND member_id = "'.$member_id.'"';
        $result4 = @mysql_query($sql4,$connection) or die(mysql_error());
        $num4 = mysql_numrows($result4);
        while ($row = mysql_fetch_array($result4))
          {
            $basket_id = $row['basket_id'];
          }
        session_register("basket_id");
        if ( $num4 == 1 )
          {
            $message2 = '<b>This order has already been submitted. Click here to <a href="orders_current.php">edit the order</a></b>';
          }
        else // Open a basket
          {
            $show_page = "no";

            include_once("../func/submit_order_funcs.php");
            $basket_id = open_basket($member_id, $current_delivery_id, $delcode_id, $deltype, $payment_method);
            
            $order_started = yes;
            session_register("order_started");
            session_register("basket_id");

            $message3 = '
              <font color="#770000" size="+2"><b>You can begin shopping!</b></font><br>
              <font color="#770000" size="+1">Add items to your Basket from the Available Products below.</font>';
          }
      }
  }

/* ==============================================================
 * QUERY FOR LAST ORDER DELIVERY INFORMATION
 */
$last_basket_query = '
  SELECT
    delcode_id,
    deltype,
    payment_method
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    member_id = '.$member_id.'
  ORDER BY
    delivery_id DESC
  LIMIT 1';

$last_basket_query_results = mysql_query($last_basket_query);
$last_basket = mysql_fetch_array($last_basket_query_results);
$last_delivery_id = $last_basket['delcode_id'];
$last_delivery_type = $last_basket['deltype'];
$last_payment_method = $last_basket['payment_method'];

/* ==============================================================
 * HERE WE GET THE DELIVERY TYPE.  DEFAULT IS THE SAME AS
 * MEMBER'S LAST ORDER.
 */

// Always delivery type P - Pick Up (Collect) Order
$deltype = 'P';
/*if($_GET['deltype']!='') {
  $deltype = $_GET['deltype'];
} else {
  $deltype = $last_delivery_type;
}*/
$q = mysql_query('
  SELECT
    *
  FROM
    delivery_types');
while ( $row = mysql_fetch_array($q) )
  {
    $selected = ($row["deltype"] == $deltype)? 'SELECTED': '';
    $display_deltype .= '
      <option value="'.$row['deltype'].'" '. $selected.'>'.$row['deltype_title'].'</option>';
  }

/* ================================================================
 * THIS SECTION CREATES THE SELECTABLE DROPDOWN LIST FOR MEMBERS
 * TO SELECT THEIR DELIVERY LOCATION.  IT DEFAULTS TO THE LOCATION
 * THEY USED FOR THEIR LAST ORDER.
 */


//find all available delivery locations
$query = '
  SELECT
    *
  FROM
    '.TABLE_DELCODE.',
    '.TABLE_DELTYPE.'
  WHERE delivery_types.deltype = "'.$deltype.'"
    AND '.TABLE_DELCODE.'.deltype = '.TABLE_DELTYPE.'.deltype_group
    AND inactive = "0"
  ORDER BY
    delcode ASC'; // CHANGED from != 1 So that inactive = 2 will not show up but can still be used for member sign-ups
$sql = mysql_query($query);

// If there is only one collection location available, automatically select it
$delcode_count = mysql_numrows($sql);
$deliver_location_selected = ($delcode_count == 1) ? 'selected="yes"' : '';
while ( $row = mysql_fetch_array($sql) )
  {
    //if this code matches the last code, mark it selected
    if($row['delcode_id']==$last_delivery_id) {
      $deliver_location_selected = 'selected="yes"';
    }

    $display_delcode .= '
        <option value="'.$row['delcode_id'].'" ' . $deliver_location_selected . ' >'.$row['delcode'].'</option>';
  }

/* ====================================================================
 * PAYMENT METHOD SELECTION
 */
$query = '
  SELECT
    *
  FROM
    '.TABLE_PAY;
$sql = mysql_query($query);
// If only one payment method, don't show this section of the form
$payment_options_count = mysql_numrows($sql);
if ($payment_options_count == 1)
{
  while ( $row = mysql_fetch_object($sql) )
  {
    $display_pay .= '<input type="hidden" value="'.$row->payment_method.'" name="payment_method"/>';
  }
}
else
{
  while ( $row = mysql_fetch_object($sql) )
    {
      // Remember last payment method and present that as default
      if ($row->payment_method == $last_payment_method)
        {
          $payment_checked = ' checked';
        }
      $display_pay .= '
        <input type="radio" name="payment_method" value="'.$row->payment_method.'"'.$payment_checked.'>'.$row->payment_desc;
    }
}
$display .= '
<form action="" method="post" name="delivery">
<div align="center">
<table cellpadding="7" cellspacing="1" border="0" style="border: 5px solid red;margin-top:5px;">
  <tr>
    <th colspan="2" bgcolor="#aa0000"><font size="+1" color="#ffffcc">Select from these options to begin an order</font></th>
  </tr>';

if ( $message2 )
  {
    $display .= '
  <tr bgcolor="#ffaa66">
    <td colspan="2"><font color="#990000">'.$message2.'</font></td>
  </tr>';
  }
else
  {
    $display .= '';
  }
// Removed Delivery Type - now always P - Pick Up (Collect) Order
// Also making the select box big enough to show all collection locations at once
/*$display .= '
  <tr bgcolor="#ffaa66">
    <td align="left"><b>1. Delivery Type:</b></td>
    <td align="left">
      <select name="deltype" onChange="Load_deltype()" size="2">
        <option value="0">--- Select a delivery type ---</option>
        '.$display_deltype.'
      </select>
    </td>
  </tr>'*/
$display .= '
  <tr bgcolor="#ffaa66">
    <td align="left"><b>Collection Location</b><br>Click here for <a href="../locations.php">more details</a>.</td>
    <td align="left">
      <select name="delcode_id" size="'.($delcode_count + 1).'">
        <option value="">-- Choose a location ---</option>
        '.$display_delcode.'
      </select>
    </td>
  </tr>';
// If only one payment method, don't show this section of the form
if ($payment_options_count == 1)
{
  $display .= $display_pay;
}
else
{
  $display .= '
  <tr bgcolor="#ffaa66">
    <td align="left"><b>Payment Method</b></td>
    <td align="left">'.$display_pay.'</td>
  </tr>';
}

$display .= '
  <tr bgcolor="#ffaa66">
    <td colspan="2" align="right">
      <input type="hidden" name="qy" value="jk">';
if ( $basket_id )
  {
    $display .= '
      <input type="hidden" name="basket_id" value="'.$basket_id.'">';
  }
// Deltype P - Pick Up (Collect) Order - is default
$display .= '
      <input type="hidden" name="deltype" value="P">
      <input name="where" type="submit" value="Click to Start An Order">
    </td>
  </tr>
</table>
</div>
</form>';