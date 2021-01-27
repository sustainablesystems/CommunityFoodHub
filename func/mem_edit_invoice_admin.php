<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
$authorization = get_auth_types($_SESSION['auth_type']);

if( $updatevalues == 'ys' )
  {
    $sqli = '
      SELECT
        inventory_on,
        inventory
      FROM
        '.TABLE_PRODUCT.'
      WHERE
        product_id = "'.$product_id.'"';
    $resulti = @mysql_query($sqli,$connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($resulti) )
      {
        $inventory_on = $row['inventory_on'];
        $inventory = $row['inventory'];
      }
    $sqlq = '
      SELECT
        quantity AS quantity_before_change
      FROM
        '.TABLE_BASKET.'
      WHERE
        basket_id = "'.$basket_id.'"
        AND product_id = "'.$product_id.'"';
    $resultq = @mysql_query($sqlq,$connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($resultq) )
      {
        $quantity_before_change = $row['quantity_before_change'];
      }
    if ( $quantity < 0 )
      {
        $message2 = "<b>Please enter a quantity for the product.<br>To remove, enter the number 0.</b>";
      }
    elseif ( $inventory_on && $inventory < ($quantity - $quantity_before_change) && ($inventory == 1) )
      {
        $message2 = "<H3>There is only $inventory of Product ID # $product_id available. Please add that quantity or less.</h3>";
      }
    elseif ( $inventory_on && $inventory < ($quantity - $quantity_before_change) )
      {
        $message2 = "<H3>There are only $inventory of Product ID # $product_id available. Please add that quantity or less.</h3>";
      }
    elseif ( $quantity == 0 )
      {
        $sqld = '
          DELETE FROM
            '.TABLE_BASKET.'
          WHERE
            basket_id = "'.$basket_id.'"
            AND product_id = "'.$product_id.'"';
        $resultdelete = @mysql_query($sqld,$connection) or die(mysql_error());
        $message4 = '<b>Product was removed from basket.</b>';
        if ( $inventory_on )
          {
            $inventory = $inventory+$quantity_before_change;
            $sqlus = '
              UPDATE
                '.TABLE_PRODUCT.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = "'.$product_id.'"';
            $resultus = @mysql_query($sqlus,$connection) or die("Could not execute query updating stock in public product list.");
            $sqlus2 = '
              UPDATE
                '.TABLE_PRODUCT_PREP.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = "'.$product_id.'"';
            $resultus2 = @mysql_query($sqlus2,$connection) or die("Could not execute query updating stock in prep list.");
          }

          // Mark order as unsubmitted as we have made a change to it
          include("../func/submit_order_funcs.php");
          set_order_submitted($basket_id, false);
      }
    elseif ( !ereg("[0-9]+$", $quantity) )
      {
        $message2 = '<b>Please review the quantity: The quantity must be a number.</b>';
      }
    elseif ( $product_id )
      {
        $sqlu = '
          UPDATE
            '.TABLE_BASKET.'
          SET
            quantity = "'.$quantity.'"
          WHERE
            basket_id = "'.$basket_id.'"
            AND product_id = "'.$product_id.'"';
        $result = @mysql_query($sqlu,$connection) or die(mysql_error());
        $message2 = '<b>The information has been updated.</b>';
        if ( $inventory_on )
          {
            $inventory = $inventory+($quantity_before_change-$quantity);
            $sqlus = '
              UPDATE
                '.TABLE_PRODUCT.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = "'.$product_id.'"';
            $resultus = @mysql_query($sqlus,$connection) or die("Could not execute query updating stock in public product list.");
            $sqlus2 = '
              UPDATE
                '.TABLE_PRODUCT_PREP.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = "'.$product_id.'"';
            $resultus2 = @mysql_query($sqlus2,$connection) or die("Could not execute query updating stock in prep list.");
          }
          
          // Mark order as unsubmitted as we have made a change to it
          include("../func/submit_order_funcs.php");
          set_order_submitted($basket_id, false);
      }
    else
      {
        $message4 = 'No product choosen or no basket started. Please go to the <a href="index.php">main order page</a>.';
      }
  }

$display_page .= '
  <table width="800" cellpadding="2" cellspacing="0" border="0">
    <tr>
      <td colspan="9" align="right"><font face="'.$fontface.'">';
if ( $message4 )
  {
    $display_page .= '<div align="right"><font color="#770000">'.$message4.'</font></div>';
  }
$display_page .= '
      </td>
    </tr>
    <tr>
      <td colspan="9"><hr></td>
    </tr>
    <tr>
      <th valign="bottom"><font face="'.$fontface.'" size="-1"></th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">#</th>
      <th valign="bottom" align="left"><font face="'.$fontface.'" size="-1">Product Name</th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">Price</th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">Quantity</th>
      <th valign="bottom" align="right"><font face="'.$fontface.'" size="-1">Subtotal</th>
      <th valign="bottom" align="center"><font face="'.$fontface.'" size="-1">Edit</th>
    </tr>
    <tr>
      <td colspan="9"><hr></td>
    </tr>
';
  $sql = '
    SELECT
      '.TABLE_BASKET_ALL.'.*,
      '.TABLE_BASKET.'.*,
      '.TABLE_PRODUCT.'.inventory_on,
      '.TABLE_PRODUCT.'.inventory,
      '.TABLE_PRODUCT.'.*,
      '.TABLE_PRODUCER.'.*,
      '.TABLE_MEMBER.'.member_id,
      '.TABLE_MEMBER.'.business_name,
      '.TABLE_MEMBER.'.first_name,
      '.TABLE_MEMBER.'.last_name
    FROM
      '.TABLE_BASKET.',
      '.TABLE_BASKET_ALL.',
      '.TABLE_PRODUCT.',
      '.TABLE_PRODUCER.',
      '.TABLE_MEMBER.'
    WHERE
      '.TABLE_BASKET_ALL.'.basket_id = "'.$basket_id.'"
      AND '.TABLE_BASKET.'.basket_id = "'.$basket_id.'"
      AND '.TABLE_BASKET_ALL.'.member_id = "'.$member_id.'"
      AND '.TABLE_BASKET_ALL.'.delivery_id = "'.$delivery_id.'"
      AND '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
      AND '.TABLE_PRODUCT.'.producer_id = '.TABLE_PRODUCER.'.producer_id
      AND '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
    GROUP BY '.TABLE_BASKET.'.product_id
    ORDER BY business_name ASC, last_name ASC, product_name ASC';
  $result = @mysql_query($sql,$connection) or die("Couldn't execute query 1.");
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
      $member_id_product = $row['member_id'];
      $product_name = $row['product_name'];
      $inventory_on = $row['inventory_on'];
      $inventory = $row['inventory'];
      $item_price = round ($row['item_price'] * $price_multiplier, 2);
      $pricing_unit = $row['pricing_unit'];
      $quantity = $row['quantity'];
      $ordering_unit = $row['ordering_unit'];
      $out_of_stock = $row['out_of_stock'];
      $extra_charge = $row['extra_charge'];

      if ( $out_of_stock )
        {
          $display_outofstock = '<img src="grfx/checkmark_wht.gif"><br>';
          $item_total_price = 0;
        }
      else
        {
          $display_outofstock = '';
          $item_total_3dec = round ((($item_price * $quantity) + ($quantity * $extra_charge)), 3) + 0.00000001;
          $item_total_price = round ($item_total_3dec, 2);

        }
      $total += $item_total_price;

      if ( ($message2) && ($product_id == $product_id_printed) )
        {
          $display_page .= '
            <tr align="center">
              <td align="right" valign="top" colspan="9"><font face="arial" size="-1"><font color="#770000">'.$message2.'</font></td>
            </tr>';
        }
      $display_page .= '
        <tr align="center">
          <td align="center" valign="top" id="'.$product_id.'"><font face="arial" size="-1">
            <form action="#basket" method="post">'.$display_outofstock.'</td>
          <td align="right" valign="top"><font face="arial" size="-1"><b>'.$product_id.'</b>&nbsp;&nbsp;</td>
          <td width="275" align="left" valign="top"><font face="arial" size="-1">
            <b>'.$product_name.'</b></td>
        <td align="center" valign="top"><font face="arial" size="-1">'.CURSYM.number_format($item_price, 2).'/'.$ordering_unit.'</td>
        <td align="center" valign="top"><font face="arial" size="-1">
          <input type="text" name="quantity" value="'.$quantity.'" size="2" maxlength="11"></td>
        <td align="right" valign="top" class="price"><font face="arial" size="-1">'.CURSYM.number_format($item_total_price,2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">
          <input type="hidden" name="updatevalues" value="ys">
          <input type="hidden" name="delivery_id" value="'.$delivery_id.'">
          <input type="hidden" name="product_id" value="'.$product_id.'">
          <input type="hidden" name="product_id_printed" value="'.$product_id.'">
          <input type="hidden" name="member_id" value="'.$member_id.'">
          <input type="hidden" name="basket_id" value="'.$basket_id.'">
          <input name="where" type="submit" value="Update">
          </form></td>
          </tr>';
    }
$display_page .= '
  <tr>
  <td colspan="9">'.$font.'
  <hr>
  </td>
  </tr></table>';
