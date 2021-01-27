<?php

/*
 * Display information about products that are in the PRODUCT_LIST_PREP
 * table - i.e. not yet public.  Used by edit_product_list.php (producers).
 */

include_once (FUNC_FILE_PATH.'compound_products.php');
include_once (FUNC_FILE_PATH.'supplier_products.php');

$product_id = $row['product_id'];
$product_name = $row['product_name'];
$unit_price = $row['unit_price'];
$product_margin = $row['margin'];
$pricing_unit = $row['pricing_unit'];
$ordering_unit = $row['ordering_unit'];
$prodtype_id = $row['prodtype_id'];
$prodtype = $row['prodtype'];
$random_weight = $row['random_weight'];
$minimum_weight = $row['minimum_weight'];
$maximum_weight = $row['maximum_weight'];
$meat_weight_type = $row['meat_weight_type'];
$extra_charge = $row['extra_charge'];
$image_id = $row['image_id'];
$donotlist = $row['donotlist'];
$detailed_notes = $row['detailed_notes'];
$origin_id = $row['origin_id'];
$brand_id = $row['brand_id'];
$is_compound = $row['is_compound'];

if (SHOW_ACTUAL_PRICE)
  {
    // Show customer markup as default -- not wholesale
    $unit_price /= (1 - (UNIVERSAL_MARGIN + $product_margin));
    $unit_price = round($unit_price, 2);
  }

if ($is_compound)
{
    $subprod_names = subprod_names_get(
            TABLE_PRODUCT_PREP, TABLE_COMPOUND_PRODUCT_PREP, $product_id);
}
if ($is_supplier)
{
    $origin = prod_origin_get($origin_id);
    $brand = prod_brand_get($brand_id);
}

$sqli = '
  SELECT
    product_id,
    inventory_on,
    inventory
  FROM
    '.TABLE_PRODUCT_PREP.'
  WHERE
    product_id = "'.$product_id.'"';
$resulti = @mysql_query($sqli,$connection) or die("<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href=\"mailto:".WEBMASTER_EMAIL."\">".WEBMASTER_EMAIL."</a><br><br><b>Error:</b> Getting inventory " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($resulti) )
  {
    $inventory_on = $row['inventory_on'];
    $inventory = $row['inventory'];
  }
if ( $donotlist == 1 )
  {
    $plist = '<br><b>Not currently listed.</b>';
  }
elseif ( $donotlist == 2)
  {
    $plist = '<br><b>Archived.</b>';
  }
else
  {
    $plist = '';
  }
if ( $current_product_id < 0 )
  {
    $current_product_id = $row['product_id'];
  }
while ( $current_product_id != $product_id )
  {
    $current_product_id = $product_id;
  }
if ( $minimum_weight == $maximum_weight )
  {
    $minmax = $minimum_weight.' '.Inflect::pluralize_if ($minimum_weight, $pricing_unit);
  }
else
  {
    $minmax = $minimum_weight.' - '.$maximum_weight.' '.Inflect::pluralize ($pricing_unit);
  }
if ( $random_weight )
  {
    $show_weight = 'You will be billed for exact '.$meat_weight_type.' weight (approx. '.$minmax.')';
    $blue_weight = '<font color="#3333ff">(Producer will need to enter weight.)</font>';
  }
else
  {
    $show_weight = '';
    $blue_weight = '';
  }
if ( !$ordering_unit )
  {
    $ordering_unit = '<font color="#3333ff">ERROR</font>';
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

$display .= '<tr bgcolor="#ffffff">';
if ( $image_id )
  {
    $display_image = "
      <img src=\"getimage.php?image_id=$image_id\" width=100 name='img$image_id'
      onClick='javascript:img$image_id.width=300'
      onMouseOut='javascript:img$image_id.width=100' hspace=5 border=1 align=left alt=\"Click to shrink $product_name\">";
  }
else
  {
    $display_image = '';
  }

$display .= '<td><b># '.$product_id.'</b></td>';
$display .= '<td>'.$display_image.'<b>'.stripslashes($product_name).'</b><br>';

// Add brand name if present
if ( $brand != '' )
{
    $display .= '<i>'.$brand.'</i> brand. ';
}

// Add list of subproducts if this is a compound one
if ($is_compound)
{
    $display .= "<p><b>".$font."<a href=\"box_contents.php?box_id=$product_id&prep=true\">This Week's Contents:";
    $display .= '&nbsp;&nbsp;' . implode(', ', $subprod_names) . '</b></a></font></p>';
    //$display .= '<ul><br><li>'.implode ('</li><br><li>', $subprod_names).'</li></ul></b></font></p>';
}

$display .= '<font size="-1">'
  .stripslashes($detailed_notes).' '
  .$inventory_info
  .' Order number of '.Inflect::pluralize ($ordering_unit).'. '
  .$show_weight.' '
  .$extra.' '
  .$blue_weight;
if ( $display_type == 'edit' )
  {
    if( ($message) && ($product_id == $product_id_printed) )
      {
        $display .= '<br><font size="-1" color="#770000">'.$message.'</font>';
      }
  }
$display .= '</font>';
$display .= '</td>';

// Display origin if this producer is a supplier
if ( $is_supplier && !$is_compound )
{
    $display .= '<td align="center">'.$origin.'</td>';
}

if ( $prodtype_id != 5 )
  {
    $display .= '<td><font size="-1" color="#000000">'.$prodtype.'</font></td>';
  }
else
  {
    $display .= '<td><font size="-1" color="#FFFFFF">-</font></td>';
  }

$display .= '<td align="center">';
$ordering_unit = stripslashes($ordering_unit);
$display .= Inflect::singularize($ordering_unit);
$display .= '</td><td align="center">';

if ( $unit_price != 0 )
  {
    $display .= $font.' '.CURSYM.number_format($unit_price, 2).'/'.$pricing_unit.'';
  }
if ( $unit_price != 0 && $extra_charge != 0 ) $display .= '<br>and<br>';
if ( $extra_charge != 0 )
  {
    $display .= CURSYM.number_format($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
  }
$display .= '</td>';

// Place Edit options at the end
if ( $display_type == 'new_or_changed' )
  {
    $last_table_cell = '<font color="#3333ff">NEW LISTING</font>';
  }
elseif ( $display_type == 'edit' )
  {
    $last_table_cell = '
      <font size="-1">
      [<a href="edit_products.php?product_id='.$product_id.'&producer_id='.$producer_id.'&a='.$_REQUEST['a'].'">Edit Product</a>]<br>
      [<a href="uploadpi.php?product_id='.$product_id.'&producer_id='.$producer_id.'">Add&nbsp;Image</a>]
      [<a href="product_order_history.php?product_id='.$product_id.'&producer_id='.$producer_id.'">Order&nbsp;History</a>]
      '.$plist.'</font>'; // if it's currently listed or not.
  }

$display .= '<td valign="center" id="'.$product_id.'">'.$last_table_cell.'</td>';
$display .= '</tr>';