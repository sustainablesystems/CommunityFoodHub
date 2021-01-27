<?php

$product_name = stripslashes($product_name);
$sqlb = '
  SELECT
    product_id
  FROM
    '.TABLE_BASKET.'
  WHERE
    product_id = "'.$product_id.'"
    AND basket_id = "'.$basket_id.'"';
$resultb = @mysql_query($sqlb, $connection) or die(mysql_error());
$numb = mysql_numrows($resultb);
if ( $numb == 1 )
  {
    $message = '<font color="red"><b>"'.$product_name.'" is already in your Shopping Basket.</b><br>To increase the quantity, please go to <a href="orders_current.php">your Shopping Basket</a>.</font>';
  }
else
  {
    $sqlis = '
      SELECT
        '.TABLE_PRODUCT.'.inventory_on,
        '.TABLE_PRODUCT.'.inventory
      FROM
        '.TABLE_PRODUCT.'
      WHERE
        product_id = "'.$product_id.'"';
    $resultis = @mysql_query($sqlis, $connection) or die("Couldn't execute query s.");
    while ( $row = mysql_fetch_array($resultis) )
      {
        $inventory_on = $row['inventory_on'];
        $inventory = $row['inventory'];
      }
    // Allow adding of >1 product
    if( $inventory_on && ($inventory == '' || ($inventory - $add_product_quantity < 0) ) )
      {
        //$message = '<b>This product is sold out!</b>';
        $message = '<font color="red"><b>"'.$product_name.'" is not available in the quantity requested!</b></font>';
      }
    else
      {
        if( $inventory_on && $inventory >= 1 )
          {
            // Allow adding of >1 product
            $inventory = $inventory - $add_product_quantity;
            $sqlus = '
              UPDATE
                '.TABLE_PRODUCT.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = "'.$product_id.'"';
            $resultus = @mysql_query($sqlus, $connection) or die("Couldn't execute query updating stock in public product list.");
            $sqlus2 = '
              UPDATE '.TABLE_PRODUCT_PREP.'
              SET
                inventory = "'.$inventory.'"
              WHERE
                product_id = "'.$product_id.'"';
            $resultus2 = @mysql_query($sqlus2, $connection) or die("Couldn't execute query updating stock in prep list.");
          }
        $sql3 = '
          SELECT
            unit_price,
            extra_charge,
            ordering_unit
          FROM
            '.TABLE_PRODUCT.'
          WHERE
            product_id = "'.$product_id.'"';
        $result3 = @mysql_query($sql3, $connection) or die("Couldn't execute query 3.");
        while ( $row = mysql_fetch_array($result3) )
          {
            $unit_price = $row['unit_price'];
            $extra_charge = $row['extra_charge'];
            $ordering_unit = $row['ordering_unit'];
          }

        // Modify price if necessary according to ordering unit
        // E.g. if price is per kilo, but ordering unit is HALF kilo
        $unit_price = get_price($ordering_unit, $unit_price);

        // Allow adding of >1 product
        $sqlc = '
          INSERT INTO
            '.TABLE_BASKET.'
              (
                basket_id,
                product_id,
                item_price,
                quantity,
                extra_charge,
                item_date
              )
          VALUES
            (
              "'.$basket_id.'",
              "'.$product_id.'",
              "'.$unit_price.'",
              "'.$add_product_quantity.'",
              "'.$extra_charge.'",
              now()
            )';
        $result = @mysql_query($sqlc, $connection) or die(mysql_error());

        $message = '<font color="red"><b>'.$add_product_quantity.' x "'.$product_name.'" was added to your Shopping Basket.</b><br>
          To change the quantity, please go to <a href="orders_current.php">your Shopping Basket</a>.</font>';
        $unit_price = 0;
        $extra_charge = 0;

        // Mark order as unsubmitted as we have made a change to it
        include("../func/submit_order_funcs.php");
        set_order_submitted($basket_id, false);
      }
  }
$sqls = '
  SELECT
    '.TABLE_BASKET_ALL.'.basket_id,
    '.TABLE_BASKET_ALL.'.delivery_id,
    '.TABLE_BASKET.'.basket_id,
    '.TABLE_BASKET.'.product_id,
    '.TABLE_BASKET.'.quantity,
    '.TABLE_BASKET.'.item_price,
    '.TABLE_BASKET.'.out_of_stock,
    '.TABLE_BASKET.'.total_weight,
    '.TABLE_BASKET.'.extra_charge,
    '.TABLE_PRODUCT.'.random_weight,
    '.TABLE_PRODUCT.'.margin,
    '.TABLE_PRODUCT.'.product_id
  FROM
    '.TABLE_BASKET.',
    '.TABLE_BASKET_ALL.',
    '.TABLE_PRODUCT.'
  WHERE
    '.TABLE_BASKET_ALL.'.basket_id = "'.$basket_id.'"
    AND '.TABLE_BASKET.'.basket_id = "'.$basket_id.'"
    AND '.TABLE_BASKET_ALL.'.delivery_id = "'.$current_delivery_id.'"
    AND '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
  GROUP BY
    '.TABLE_BASKET.'.product_id';
$results = @mysql_query($sqls, $connection) or die(mysql_error());
while ( $row = mysql_fetch_array($results) )
  {
    $product_id = $row['product_id'];
    $item_price = $row['item_price'];
    $product_margin = $row['margin'];
    $quantity = $row['quantity'];
    $out_of_stock = $row['out_of_stock'];
    $random_weight = $row['random_weight'];
    $total_weight = $row['total_weight'];
    $extra_charge = $row['extra_charge'];

    if (SHOW_ACTUAL_PRICE)
      {
          $item_price /= (1.0 - (UNIVERSAL_MARGIN + $product_margin));
          $item_price = round($item_price, 2);
      }
      
    if( $out_of_stock != 1 )
      {
        if ( $random_weight == 1 )
          {
            if( $total_weight == 0 )
              {
              }
            else
              {
                $display_weight = $total_weight;
              }
            $item_total_3dec = number_format ((($item_price * $total_weight) + ($quantity * $extra_charge)), 3) + 0.00000001;
            $item_total_price = round ( $item_total_3dec, 2 );
          }
        else
          {
            $display_weight = "";
            $item_total_3dec = number_format ((($item_price * $quantity) + ($quantity * $extra_charge)), 3);// + 0.00000001;
            $item_total_price = round ($item_total_3dec, 2);
          }
      }
    else
      {
        $display_weight = '';
        $item_total_price = '0';
      }
    if( $item_total_price )
      {
        $total = $item_total_price + $total;
      }
    $total_pr = $total_pr + $quantity;
    $subtotal_pr = $subtotal_pr + $item_total_price;
  }
  mysql_free_result($results);