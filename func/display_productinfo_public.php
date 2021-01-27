<?php
$user_type = 'valid_c';
include_once ('config_foodcoop.php');

if( $minimum_weight == $maximum_weight )
  {
    $minmax = $minimum_weight.' '.Inflect::pluralize_if ($minimum_weight, $pricing_unit);
  }
else
  {
    $minmax = $minimum_weight.' - '.$maximum_weight.' '.Inflect::pluralize ($pricing_unit);
  }
if( $random_weight )
  {
    $show_weight = 'You will be billed for exact '.$meat_weight_type.' weight (approx. '.$minmax.')';
  }
else
  {
    $show_weight = '';
  }
if( $ordering_unit == 'unknown' )
  {
    $ordering_unit = '';
  }
else
  {
    $ordering_unit = Inflect::pluralize ($ordering_unit);
  }
if( $extra_charge )
  {
    $extra = '<br>Extra charge: '.CURSYM.number_format($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
  }
else
  {
    $extra = '';
  }
if( $inventory_on )
  {
    $inventory_info = $inventory.' available.';
  }
else
  {
    $inventory_info = '';
  }
if ( $image_id )
  {
    $display_image = '
      <img src="'.BASE_URL.PATH.'members/getimage.php?image_id='.$image_id.'" width="100" name="img'.$image_id.'"
      onClick="javascript:img'.$image_id.'.width=300"
      onMouseOut="javascript:img'.$image_id.'.width=100" hspace="5" border="1" align="left" alt="Click to enlarge '.$product_name.'"/>';
  }
else
  {
    $display_image = '';
  }
if ( $show_business_link == true )
  {
    if ( $business_name )
      {
        $display_producer = ' <a href="'.BASE_URL.PATH.'producers/'.strtolower($producer_id).'.php">'.stripslashes($business_name).'</a><br>';
      }
    else
      {
        $display_producer = ' <a href="'.BASE_URL.PATH.'producers/'.strtolower($producer_id).'.php">'.stripslashes($first_name).' '.stripslashes($last_name).'</a><br>';
      }
  }
else
  {
    $display_producer = '';
  }
$display .= '<tr>';
$display .= '<td valign="center" id="'.$product_id.'"> <b>#'.$product_id.'</b></td>';
$display .= '<td>'.$display_image.' <b>'.stripslashes($product_name).'</b><br>';

// Add brand name if present
if ( $brand != '' )
{
    $display .= '<i>'.$brand.'</i> brand. ';
}

// Add list of subproducts if this is a compound one
if ($is_compound)
{
    $display .= "<p><b><a href=\"".BASE_URL.PATH."box_contents.php?box_id=$product_id&subcat_id=$subcategory_id\">This Week's Contents:";
    $display .= '&nbsp;&nbsp;' . implode(', ', $subprod_names) . '</b></a></p>';
}

// Only display the product notes if this is a subproduct
if ($is_subproduct)
{
    $display .= stripslashes($detailed_notes);
}
else
{
    $display .= stripslashes($detailed_notes).' '
      .$inventory_info.' Order number of '
      .stripslashes(Inflect::pluralize ($ordering_unit)).'. '
      .$show_weight.' '
      .stripslashes($extra);
}

if( ($message) && ($product_id == $product_id_printed) )
  {
    $display .= '<br><br>'.$message;
  }
$display .= '</td>';

// Display origin if this producer is a supplier
if ( $is_supplier && !$is_compound )
{
    $display .= '<td align="center">'.$origin.'</td>';
}

if ( $show_business_link == true )
  {
  $display .= '<td>'.$display_producer;
  }
if ( $prodtype_id != 5 )
  {
    $display .= '<td align="center"><font size="-1" color="#000000">'.$prodtype.'</font></td>';
  }
else
  {
  $display .= '<td><font size="-1" color="#FFFFFF">-</font></td>';
  }
// adjust the unit price to what we actually want to display.
if (SHOW_ACTUAL_PRICE)
  {
    $display_unit_price = $unit_price / (1 - (UNIVERSAL_MARGIN + $product_margin));
    $display_unit_price = round($display_unit_price, 2);
  }
else
  {
    $display_unit_price = $unit_price;
  }

// Display quantity if this is a subproduct
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
$display .= '</td></tr>';
