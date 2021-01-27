<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
$authorization = get_auth_types($_SESSION['auth_type']);

if ( $updatevalues == 'ys' )
  {
    // Check for remove button press
    if ($action == "Remove")
    {
      $quantity = 0;
    }
    
    $sqli = '
      SELECT
        inventory_on,
        inventory,
        product_name
      FROM
        '.TABLE_PRODUCT.'
      WHERE
        product_id = '.$product_id;
     $resulti = @mysql_query($sqli,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
     while ( $row = mysql_fetch_array($resulti) )
      {
        $inventory_on = $row['inventory_on'];
        $inventory = $row['inventory'];
        $product_name = $row['product_name'];
       }

    $sqlq = '
      SELECT
        quantity AS quantity_before_change
      FROM
        '.TABLE_BASKET.'
      WHERE
        basket_id = "'.$basket_id.'"
        AND product_id = '.$product_id;
      $resultq = @mysql_query($sqlq,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    while ( $row = mysql_fetch_array($resultq) )
      {
        $quantity_before_change = $row['quantity_before_change'];
      }

    if ( $quantity < 0 )
      {
        $message2 = '<b>Please enter a quantity for the product.<br>To remove, click the "Remove" button.</b>';
      }
    elseif ( $inventory_on &&
            $inventory < ($quantity - $quantity_before_change) &&
            $inventory == 1)
      {
        $message2 = '<h3>There is only '.$inventory.' of "'.$product_name.'" available. Please add that quantity or less.</h3>';
      }
    elseif ( $inventory_on &&
            $inventory < ( $quantity - $quantity_before_change ) )
      {
        $message2 = '<h3>There are only '.$inventory.' of "'.$product_name.'" available. Please add that quantity or less.</h3>';
      }
    elseif ( $quantity == 0)
      {

        $sqld = '
          DELETE FROM
            '.TABLE_BASKET.'
          WHERE
            basket_id = '.$basket_id.'
            AND product_id = '.$product_id;
        $resultdelete = @mysql_query($sqld,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        $message4 = '<b>"'.$product_name.'" was removed from your basket.</b>';

        if ( $inventory_on )
          {
            $inventory = $inventory + $quantity_before_change;

            $sqlus = '
              UPDATE
                '.TABLE_PRODUCT.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = '.$product_id;
            $resultus = @mysql_query($sqlus,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

            $sqlus2 = '
              UPDATE
                '.TABLE_PRODUCT_PREP.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = '.$product_id;
            $resultus2 = @mysql_query($sqlus2,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
          }

          // Mark order as unsubmitted as we have made a change to it
          include("../func/submit_order_funcs.php");
          set_order_submitted($basket_id, false);
      }
    elseif ( ! ereg("[0-9]+$", $quantity) )
      {
        $message2 = '<b>Please review the quantity: the quantity must be a number.</b>';
      }
    elseif ( $product_id )
      {
        $customer_notes_to_producer = addslashes($customer_notes_to_producer);

        $sqlu = '
          UPDATE
            '.TABLE_BASKET.'
          SET
            quantity = '.$quantity.',
            customer_notes_to_producer = "'.$customer_notes_to_producer.'"
          WHERE
            basket_id = '.$basket_id.'
            AND product_id = '.$product_id;
        $result = @mysql_query($sqlu,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        $message2 = '<b>"'.$product_name.'" quantity has been updated to '.$quantity.'.</b>';

        if ( $inventory_on )
          {
            $inventory = $inventory + ($quantity_before_change - $quantity);

            $sqlus = '
              UPDATE
                '.TABLE_PRODUCT.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = '.$product_id;
            $resultus = @mysql_query($sqlus, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

            $sqlus2 = '
              UPDATE
                '.TABLE_PRODUCT_PREP.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = '.$product_id;
            $resultus2 = @mysql_query($sqlus2, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
          }

          // Mark order as unsubmitted as we have made a change to it
          include("../func/submit_order_funcs.php");
          set_order_submitted($basket_id, false);
      }
    else
      {
        $message4 = 'No product choosen or no basket started. Please go to the <a href="index.php">Shopping Home</a>.';
      }
  }

$display_page .= '
<table width="800" cellpadding="2" cellspacing="0" border="0">
  <tr><td colspan="7" align="right"><font face="'.$fontface.'">';

if ( $message4 )
  {
    $display_page .= '
      <div align="right"><font color="red" face="arial" size="-1">'.$message4.'</font></div>';
  }
$display_page .= '
  </td></tr>
  <tr>
    <td colspan="7"><hr></td>
  </tr>
  <tr>
    <th valign="bottom"><font face="'.$fontface.'" size="-1"></th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">#</th>
    <th valign="bottom" align="left"><font face="'.$fontface.'" size="-1">Product Name</th>
    <th valign="bottom" align="left"><font face="'.$fontface.'" size="-1">Price</th>
    <th valign="bottom" align="left"><font face="'.$fontface.'" size="-1">Quantity</th>
    <th valign="bottom" align="right"><font face="'.$fontface.'" size="-1">Subtotal</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">&nbsp</th>
  </tr>
  <tr>
    <td colspan="7"><hr></td>
  </tr>';
//echo '$basket_id'.$basket_id.'$member_id'.$member_id.'$current_delivery_id'.$current_delivery_id;
$sql = '
  SELECT
    '.TABLE_BASKET_ALL.'.*,
    '.TABLE_BASKET.'.*,
    '.TABLE_PRODUCT.'.product_name,
    '.TABLE_PRODUCT.'.pricing_unit,
    '.TABLE_PRODUCT.'.ordering_unit,
    '.TABLE_PRODUCT.'.random_weight,
    '.TABLE_PRODUCT.'.inventory_on,
    '.TABLE_PRODUCT.'.inventory,
    '.TABLE_PRODUCT.'.producer_id,
    '.TABLE_PRODUCT.'.product_id,
    '.TABLE_PRODUCT.'.detailed_notes,
    '.TABLE_PRODUCT.'.margin,
    '.TABLE_PRODUCER.'.*,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBERSHIP_TYPES.'.order_cost
  FROM
    '.TABLE_BASKET.',
    '.TABLE_BASKET_ALL.',
    '.TABLE_PRODUCT.',
    '.TABLE_PRODUCER.',
    '.TABLE_MEMBER.',
    '.TABLE_MEMBERSHIP_TYPES.'
  WHERE
    '.TABLE_BASKET_ALL.'.basket_id = '.$basket_id.'
    AND '.TABLE_BASKET.'.basket_id = '.$basket_id.'
    AND '.TABLE_BASKET_ALL.'.member_id = '.$member_id.'
    AND '.TABLE_BASKET_ALL.'.delivery_id = '.$current_delivery_id.'
    AND '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
    AND '.TABLE_PRODUCT.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
    AND '.TABLE_MEMBERSHIP_TYPES.'.membership_type_id = '.TABLE_MEMBER.'.membership_type_id
  GROUP BY
    '.TABLE_BASKET.'.product_id
  ORDER BY
    business_name ASC,
    last_name ASC,
    '.TABLE_PRODUCT.'.product_name ASC';
$result = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($result) )
  {
    // Figure out how much markup to show on products
    $product_margin = $row['margin'];
    if (SHOW_ACTUAL_PRICE)
      {
          $price_multiplier = 1 / (1 - (UNIVERSAL_MARGIN + $product_margin));
      }
    else
      {
          $price_multiplier = 1;
      }

    $product_id = $row['product_id'];
    $producer_id = $row['producer_id'];
    $member_id_product = $row['member_id'];
    $a_business_name = stripslashes($row['business_name']);
    $a_first_name = $row['first_name'];
    $a_last_name = $row['last_name'];
    $is_supplier = $row['is_supplier'];
    $product_name = stripslashes($row['product_name']);
    $inventory_on = $row['inventory_on'];
    $inventory = $row['inventory'];
    $item_price = round ($row['item_price'] * $price_multiplier, 2);
    $pricing_unit = $row['pricing_unit'];
    $quantity = $row['quantity'];
    $ordering_unit = $row['ordering_unit'];
    $out_of_stock = $row['out_of_stock'];
    $random_weight = $row['random_weight'];
    $total_weight = $row['total_weight'];
    $extra_charge = $row['extra_charge'];
    $detailed_notes = stripslashes($row['detailed_notes']);
    $notes = stripslashes($row['customer_notes_to_producer']);
    $item_date = $row['item_date'];
    $order_cost = $row['order_cost'];

    if ( $out_of_stock != 1 )
      {
        if ( $random_weight == 1 )
          {
            if ( $total_weight == 0 || $total_weight == '' )
              {
                $display_weight = '<font color="#770000" face="arial" size="-1">price updated after producer adds weight</font>';
                $message_incomplete = '<font color="#770000">Order Incomplete</font>';
              }
            else
              {
                $display_weight = $total_weight.' '.Inflect::pluralize_if ($display_weight, $pricing_unit);
              }

            $item_total_3dec = round((($item_price * $total_weight) + ($quantity * $extra_charge)), 3) + 0.00000001;
            $item_total_price = round($item_total_3dec, 2);

          }
        else
          {
            $display_weight = '';
            $item_total_3dec = round((($item_price * $quantity) + ($quantity * $extra_charge)), 3) + 0.00000001;
            $item_total_price = round($item_total_3dec, 2);
          }
      }
    else
      {
        $display_weight = '';
        $item_total_price = 0;
      }

    if ( $out_of_stock )
      {
        $display_outofstock = '<img src="grfx/checkmark_wht.gif"><br>';
      }
    else
      {
        $display_outofstock = '';
      }

    if ( $extra_charge )
      {
        $display_charge = CURSYM.number_format($extra_charge, 2);
      }
    else
      {
        $display_charge = '';
      }

    if ( $item_total_price )
      {
        $total = $item_total_price + $total;
      }

    $total_pr = $total_pr + $quantity;
    $subtotal_pr = $subtotal_pr + $item_total_price;

    if ( $a_business_name )
      {
        $display_business_name = $a_business_name;
      }
    else
      {
        $display_business_name = $a_first_name.' '.$a_last_name;
      }
    $display_business_name = stripslashes ($display_business_name);

    if ( $current_producer_id < 0 )
      {
        $current_producer_id = $row['producer_id'];
      }
    while ( $current_producer_id != $producer_id )
      {
        $current_producer_id = $producer_id;
        // Don't show producer for suppliers
        if (!is_supplier)
        {
            $display_page .= '
              <tr align="left" bgcolor=#DDDDDD>
                <td id="'.$producer_id.'"></td>
                <td>____</td>
                <td colspan="5"><br><font face="arial" color="#770000" size="-1"><b>'.$display_business_name.'</b></font></td>
              </tr>';
        }
      }

    if ( $current_product_id < 0 )
      {
        $current_product_id = $row['product_id'];
      }
    while ( $current_product_id != $product_id )
      {
        $current_product_id = $product_id;

        if ( $message2 &&
            ( $product_id == $product_id_printed) )
          {
            $display_page .= '
              <tr align="center">
                <td align="right" valign="top" colspan="9"><font face="arial" size="-1"><font color="red">'.$message2.'</font></td>
              </tr>';
          }

        // Just used to indicate whether the red warning box is shown on the cart page
        $qty_in_basket++;

        $display_page .= '
          <tr align="center" bgcolor=#EEEEEE>
            <td align="center" valign="top" id="'.$product_id.'"><font face="arial" size="-1">
              <form action="#basket" method="post">'.$display_outofstock.'
            </td>
            <td align="right" valign="top"><font face="arial" size="-1"><b>'.$product_id.'</b>&nbsp;&nbsp;</td>
            <td width="275" align="left" valign="top">
              <font face="arial" size="-1">
              <b><a href="category_list_full.php?#'.$product_id.'">'.$product_name.'</a></b>
            </td>
            <td align="left" valign="top">
              <font face="arial" size="-1">'.CURSYM.number_format($item_price, 2).' / '.$ordering_unit.'</td>
            <td align="left" valign="top" style="white-space:nowrap;">
              <font face="arial" size="-1">
              <input type="text" name="quantity" value="'.$quantity.'" size="1" maxlength="4"> '.Inflect::pluralize_if ($quantity, $ordering_unit).'
              <br>
              <input type="hidden" name="updatevalues" value="ys">
              <input type="hidden" name="product_id" value="'.$product_id.'">
              <input type="hidden" name="product_id_printed" value="'.$product_id.'">
              <input type="hidden" name="producer_id" value="'.$producer_id.'">
              <input type="hidden" name="member_id" value="'.$member_id.'">
              <input type="hidden" name="basket_id" value="'.$basket_id.'">
              <input name="action" type="submit" value="Update"><br>

            </td>
            <td align="right" valign="top"><font face="arial" size="-1" color="red">
              '.CURSYM.number_format($item_total_price, 2).'
            </td>
            <td align="right" valign="top">
              <input name="action" type="submit" value="Remove"><br>
              </form>
            </td>
          </tr>';
      }
  }
$display_page .= '
          <tr>
            <td colspan="7">'.$font.'<hr></td>
          </tr>
        </table>';
?>