<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
$authorization = get_auth_types($_SESSION['auth_type']);

/* ========= Determine if order cycle is open =============*/
$order_is_open = "";
$date_today = date("F j, Y");
$sqldd1 = '
  SELECT
    *
  FROM
    '.TABLE_CURDEL.'';
$rs1 = @mysql_query($sqldd1,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row1 = mysql_fetch_array($rs1) )
  {
    $current_delivery_id = $row1['delivery_id'];
    $delivery_date = $row1['delivery_date'];
    $closing_timestamp = $row1['closing_timestamp'];
    $date_open = strtotime ($row1['date_open']);
    $order_cycle_closed = $row1['order_cycle_closed'];
    //$open = $row['open'];
    $date_closed = strtotime ($row1['date_closed']);
  }

// Time_now is used to determine whether the order cycle is in session
// One of the following two should be uncommented for (automatic vs. manual cycling)

$time_now = time ();
// if($open==1){
//  $time_now = 0;
// } else {
//  $time_now = 99999999999999;
// }

if ($time_now > $date_open && $time_now < $date_closed) $order_is_open = true;

/* END DETERMINATION OF ORDER CYCLE */

if (SHOW_ACTUAL_PRICE)
  {
    $coop_markup = 1 / (1 - (UNIVERSAL_MARGIN + $product_margin));
  }
else
  {
    $coop_markup = 1;
  }

if ($donotlist == 3)
  {
  $wholesale_rowcolor = ' bgcolor="#eeffdd"';
  $wholesale_text = '<br><br><center style="color:#f00;letter-spacing:5px;">WHOLESALE DISCOUNTED ITEM</center>';
  }
else
  {
  $wholesale_rowcolor = '';
  $wholesale_text = '';
  }

if ( $minimum_weight == $maximum_weight )
  {
    $minmax = $minimum_weight.' '.Inflect::pluralize_if ($minimum_weight, $pricing_unit);
  }
else
  {
    $minmax = 'between '.$minimum_weight.' and '.$maximum_weight.' '.Inflect::pluralize ($pricing_unit);
  }
if ( $random_weight )
  {
    $show_weight = 'You will be billed for exact '.$meat_weight_type.' weight ('.$minmax.')';
  }
else
  {
    $show_weight = '';
  }
if ( $ordering_unit == 'unknown' )
  {
    $ordering_unit = '';
  }
else
  {
    $ordering_unit = Inflect::pluralize ($ordering_unit);
  }
if ( $extra_charge )
  {
    $extra = '<br>Extra charge: '.CURSYM.number_format($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
  }
else
  {
    $extra = '';
  }
if ( $inventory_on )
  {
    $inventory_info = $inventory.' available.';
  }
else
  {
    $inventory_info = '';
  }
$display .= '<tr'.$wholesale_rowcolor.'>';
    
$display .= '<td><b>#'.$product_id.'</b></td>';

if ( $image_id )
  {
    $display_image = '
      <img src="'.BASE_URL.PATH.'members/getimage.php?image_id='.$image_id.'" width="100" name="img'.$image_id.'"
      onclick="javascript:img'.$image_id.'.width=300"
      onMouseOut="javascript:img'.$image_id.'.width=100" hspace="5" border="1" align="left" alt="Click to enlarge '.$product_name.'"/>';
  }
else
  {
    $display_image = '';
  }

$display .= '
    <td>'.$display_image.' <b>'.stripslashes($product_name).'</b><br>';

// Add brand name if present
if ( $brand != '' )
{
    $display .= '<i>'.$brand.'</i> brand.';
}

// Add list of subproducts if this is a compound one
if ($is_compound)
{
    $display .= "<p><b><a href=\"".BASE_URL.PATH."members/box_contents.php?box_id=$product_id&subcat_id=$subcategory_id\">This Week's Contents:";
    $display .= '&nbsp;&nbsp;' . implode(', ', $subprod_names) . '</b></a></p>';
    //$display .= '<ul><br><li>'.implode ('</li><br><li>', $subprod_names).'</li></ul></b></p>';
}

// Only display the product notes if this is a subproduct
if ($is_subproduct)
{
    $display .= stripslashes($detailed_notes);
}
else
{
    $display .= $inventory_info.' Order number of '
      .stripslashes(Inflect::pluralize ($ordering_unit)).'. '
      .$show_weight.' '
      .stripslashes($detailed_notes).' '
      .stripslashes($extra);
}

if ( ($message) && ($product_id == $product_id_printed) )
{
    //$display .= '<br><br><font color="#770000">'.$message.'</font>';
    $display .= '<br><br>'.$message;
}
$display .= $wholesale_text.'</td>';

// Display origin if this producer is a supplier
if ( $is_supplier && !$is_compound )
{
    $display .= '<td align="center">'.$origin.'</td>';
}

if ($show_business_link == true)
  {
    if ($business_name)
      {
        $display.= '<td> <a href="'.BASE_URL.PATH.'producers/'.strtolower($producer_id).'.php">'.stripslashes($business_name).'</a></td>';
      }
    else
      {
        $display .= '<td> <a href="'.BASE_URL.PATH.'producers/'.strtolower($producer_id).'.php">'.stripslashes($first_name).' '.stripslashes($last_name).'</a></td>';
      }
  }

if ( $prodtype_id != 5 )
  {
    $display .= '<td> '.$prodtype.'</td>';
  }
else
  {
    $display .= '<td>&nbsp;</td>';
  }

// Figure out how much markup to show
if (SHOW_ACTUAL_PRICE)
  {
    $display_unit_price = $unit_price * $coop_markup;
    $display_unit_price = round($display_unit_price, 2);
  }
else
  {
    $display_unit_price = $unit_price;
  }

// Display quantity rather than price if this is a subproduct
  $display .= '<td align="center">';
$ordering_unit = stripslashes($ordering_unit);
if ($is_subproduct)
{
  $display .= $quantity_in_box.' '.Inflect::pluralize_if($quantity_in_box, $ordering_unit);
}
else // Display units and price
{
    $display .= Inflect::singularize($ordering_unit);
    $display .= '</td><td align="center">';


    // Display price
    if ( $display_unit_price != 0 )
      {
        // Modify price if necessary according to ordering unit
        // E.g. if price is per kilo, but ordering unit is HALF kilo
        $display_unit_price = get_price($ordering_unit, $display_unit_price);
        $display .= ' '.CURSYM.number_format($display_unit_price, 2)
                .'/'.Inflect::singularize($ordering_unit).'';
      }

    if ( $display_unit_price != 0 && $extra_charge != 0 ) $display .= '<br>and<br>';
    if ( $extra_charge != 0 )
      {
        $display .= CURSYM.number_format($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
      }
}
$display .= '</td>';

// Don't display shopping cart/start order link for subproducts
// as the member can only order the "parent" product.  Also don't display
// this column if the display_type is public (display_type may be public even if we're logged in)
if (!$is_subproduct && ($display_type != 'public'))
{
    $display .= '<td valign="center" align="center" id="'.$product_id.'">';
    if($order_is_open)
      {
        if ( isset($basket_id) )
          {
            $display .= '
              <form action="'.$PHP_SELF.'#'.$product_id.'" method="post">
                <input type="hidden" name="add" value="tocart">
                <input type="hidden" name="product_id" value="'.$product_id.'">
                <input type="hidden" name="producer_id" value="'.$producer_id.'">
                <input type="hidden" name="product_id_printed" value="'.$product_id.'">
                <input type="hidden" name="product_name" value="'.$product_name.'">
                <input type="hidden" name="subcategory_id" value="'.$subcategory_id.'">
                <input type="image" name="submit" src="../grfx/addtocart.gif" width="60" height="70" border="0" alt="Submit">
                <select name="add_product_quantity" id="add_product_quantity">
                  <option value="1" selected>1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
                  <option value="6">6</option>
                  <option value="7">7</option>
                  <option value="8">8</option>
                  <option value="9">9</option>
                  <option value="10">10</option>
                </select>
              </form>';
          }
        else
          {
            $display .= ' <a href="index.php">Begin an order</a>';
          }
      }
    else
      {
        $display .= 'Order is currently closed';
      }
    $display .= '</td>';
} // If not a subproduct

$display .= '</tr>';
