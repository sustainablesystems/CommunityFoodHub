<?php

$prior_to_show = 30;

$query = '
  SELECT
    '.TABLE_BASKET.'.product_id,
    '.TABLE_PRODUCT.'.product_name,
    '.TABLE_PRODUCT.'.detailed_notes,
    '.TABLE_PRODUCT.'.unit_price,
    '.TABLE_PRODUCT.'.margin,
    '.TABLE_PRODUCT.'.pricing_unit,
    '.TABLE_PRODUCT.'.ordering_unit,
    '.TABLE_PRODUCT.'.inventory_on,
    '.TABLE_PRODUCT.'.inventory,
    '.TABLE_PRODUCT.'.donotlist,
    '.TABLE_BASKET.'.quantity,
    '.TABLE_BASKET.'.customer_notes_to_producer,
    '.TABLE_PRODUCER.'.donotlist_producer,
    '.TABLE_PRODUCER.'.pending
  FROM
    '.TABLE_BASKET.'
  LEFT JOIN '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  LEFT JOIN '.TABLE_PRODUCT.' ON '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCT.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  WHERE
    '.TABLE_BASKET_ALL.'.member_id ='.$_SESSION['member_id'].'
    AND '.TABLE_BASKET_ALL.'.delivery_id < '.$_SESSION['current_delivery_id'].'
  ORDER BY
    '.TABLE_BASKET_ALL.'.delivery_id DESC
  LIMIT 0,'.$prior_to_show;

$result = @mysql_query($query,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ($row = mysql_fetch_array($result))
  {

    $unavailable_pre = '';
    $unavailable_post = '';
    $unavailable_background = 'BaCkGrOuNd';
    $unavailable_disabled = '';
    if (($row['donotlist'] == 1)
      || ($row['inventory_on'] == 1 && $row['inventory'] < 1)
      || ($row['donotlist_producer'] == 1)
      || ($row['pending'] == 1))
      {
        $unavailable_pre = '<del>';
        $unavailable_post = '</del>';
        $unavailable_pre = '';
        $unavailable_post = '';
        $unavailable_background = ' style="background-color:#dddddd;color:#aaa;padding:0em 1em;"';
        $unavailable_disabled = ' disabled';
      }

    $unit_price = $row['unit_price'];
    $product_margin = $row['margin'];
    if (SHOW_ACTUAL_PRICE)
      {
          $unit_price /= (1 - (UNIVERSAL_MARGIN + $product_margin));
          $unit_price = round($unit_price, 2);
      }

    // Modify price if necessary according to ordering unit
    // E.g. if price is per kilo, but ordering unit is HALF kilo
    $display_unit_price = get_price($row['ordering_unit'], $unit_price);

    $markup  = '
      <div style="width:100%;">
        <form name="order-'.$row['product_id'].'" action="orders_current.php" method="post">
        <tr '.$unavailable_background.'>
          <td width="10%" style="padding-bottom:5px;border-top:1px solid #888;">
            &nbsp;<strong>'.$unavailable_pre.$row['product_id'].$unavailable_post.'</strong>
          </td>
          <td width="40%" align="left" style="padding-bottom:5px;border-top:1px solid #888;">
            <strong style="margin:0">'.$unavailable_pre.'
              <a href="category_list_full.php#'.$row['product_id'].'">'.$row['product_name'].'</a>'
            .$unavailable_post.'</strong>
          </td>
          <td width="20%" style="padding-bottom:5px;border-top:1px solid #888;">
            '.CURSYM.number_format($display_unit_price, 2).' / '.$row['ordering_unit'].'
          </td>
          <td width="20%" style="padding-bottom:5px;border-top:1px solid #888;">
            <input type="text" name="quantity" value="'.$row['quantity'].'" size=3 maxlength="4"'.$unavailable_disabled.'> '.$row['ordering_unit'].'
          </td>
          <td width="10%" style="padding-bottom:5px;border-top:1px solid #888;">
            <input name="where" type="submit" value="Add to Current Order"'.$unavailable_disabled.'>
          </td>
        </tr>
      </div>
      <input type="hidden" name="product_id" value="'.$row['product_id'].'">
        <tr id="order-'.$row['product_id'].'O" style="display:none;">
          <td colspan="5" width="50%" valign="top"'.$unavailable_background.'>
            '.$unavailable_pre.$row['detailed_notes'].$unavailable_post.'
          </td>
          <input type="hidden" name="yp" value="ds">
          <input type="hidden" name="source" value="prior">
        </tr>
      </form>';
    $product_data[$row['product_id']] = $markup;
  }

// Remove any items already ordered this time around...

$query = '
  SELECT
    '.TABLE_BASKET.'.product_id
  FROM
    '.TABLE_BASKET.'
  LEFT JOIN '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  WHERE
    '.TABLE_BASKET_ALL.'.member_id ='.$_SESSION['member_id'].'
    AND '.TABLE_BASKET_ALL.'.delivery_id = '.$_SESSION['current_delivery_id'];

$result = @mysql_query($query,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ($row = mysql_fetch_array($result))
  {
    unset ($product_data[$row['product_id']]);
  }


// Sort by product_id and display
if (is_array ($product_data) && ksort ($product_data))
  {
    foreach (array_keys ($product_data) as $product_id)
      {
        // Set up alternating row colors (BaCkGrOuNd is replaced unless it is already designated by $unavailable_background)
        if ($row_odd != true)
          {
            $row_odd = true;
            $product_data[$product_id] = str_replace ('BaCkGrOuNd', 'style="background-color:#c8d8e8;color:#000;padding:0em 1em;"', $product_data[$product_id]);
          }
        else
          {
            $row_odd = false;
            $product_data[$product_id] = str_replace ('BaCkGrOuNd', 'style="background-color:#deecee;color:#000;padding:0em 1em;"', $product_data[$product_id]);
          }
        $prior_orders .= '<tr><td>'.$product_data[$product_id]."</td></tr>\n";
      }
    if ($_POST['source'] == "prior" || is_string ($_GET['open']))
      {
        $not_display_prior = ' style="display:none;" '; // Don't expand unless posted from this list
      }
    else
      {
        $display_prior = ' style="display:none;" '; // Don't expand unless posted from this list
      }
    echo '
      <div style="font-size:80%;" id="prior">
        <font style="font-size:150%;font-weight:bold;">
        <img title="click to show" id="prior_baskets-ic" '.$not_display_prior.' src="grfx/arrow_closed.png" onClick=\'{document.getElementById("prior_baskets").style.display="";document.getElementById("prior_baskets-ic").style.display="none";document.getElementById("prior_baskets-io").style.display="";}\'>
        <img title="click to hide" id="prior_baskets-io" '.$display_prior.' src="grfx/arrow_open.png" onClick=\'{document.getElementById("prior_baskets").style.display="none";document.getElementById("prior_baskets-io").style.display="none";document.getElementById("prior_baskets-ic").style.display="";}\'>
        &nbsp;View Items From Previous Orders<br /></font>
        <p>(Click on triangles to show or hide the display)</p>
        <p><font style="font-size:120%;">These products are the '.$prior_to_show.' most recent items you ordered.  To add an item to your basket, check the quantity listed and then click &ldquo;Add to Current Order&rdquo;.  Products that are greyed out are not available for order at this time.</font></p>
        <table border="0" cellspacing="0" cellpadding="0" width="100%" '.$display_prior.' id="prior_baskets">
          '.$prior_orders.'
        </table>
      </div>
      <hr>';
  }
?>
 