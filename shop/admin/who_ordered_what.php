<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

include_once ('general_functions.php');
include_once ('../func/delivery_funcs.php');

$display = '<h1 align="center">Who Ordered What by Collection Location</h1>';
$delivery_id = $_GET["delivery_id"];

$rs_hubs = get_delivery_locations_for_cycle($delivery_id);
$hub_count = mysql_num_rows($rs_hubs);

if ($hub_count > 0)
{
  $display .= '<table align="center" width="800" border="0" cellpadding="2" cellspacing="0"><tr><td><ul>';
  while ( $row = mysql_fetch_array($rs_hubs) )
  {
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];

    $display .= '<li><a href="who_ordered_what.php?delivery_id='.$delivery_id.'&delcode_id='.$delcode_id.'">'.$delcode.'</a>';
  }
  $display .= '</ul></td></tr></table>';
}
else // No orders for any collection locations this cycle
{
  $display .= '<p align="center">-- No orders this cycle --</p>';
}

$delcode_id_curr = $_GET["delcode_id"];
if ( $delcode_id_curr )
{
  // Save session values in order to put them back before we're done (MESSY because of register_globals!)
  $original_session_member_id = $_SESSION['member_id'];

  $total_extra = 0;
  $total = 0;
  $total_pr = 0;
  $subtotal_pr = 0;

// ADD NEW SORT ITEMS: be sure the sort item is in one of these two arrays.
// The array contains the textual sort description, the name of the related
// variable and the "SORT BY" code for mysql
// This data is also in orders_prdcr_invoice.php and should move to a config file
// Set up sort variables:
$sort_array = array (
  'Member ID' => array ('var_name' => 'member_id', 'db_sort' => ''.TABLE_BASKET_ALL.'.member_id ASC'),
  'Member Name' => array ('var_name' => 'show_mem', 'db_sort' => ''.TABLE_MEMBER.'.last_name ASC, '.TABLE_MEMBER.'.first_name ASC, '.TABLE_MEMBER.'.business_name ASC'),
  'Collection Location' => array ('var_name' => 'delcode_id', 'db_sort' => ''.TABLE_DELCODE.'.delcode_id ASC'),
  'Storage Type' => array ('var_name' => 'storage_code', 'db_sort' => ''.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code ASC'),
  'Product ID' => array ('var_name' => 'product_id', 'db_sort' => ''.TABLE_BASKET.'.product_id ASC'),
  'Product Name' => array ('var_name' => 'product_name', 'db_sort' => ''.TABLE_PRODUCT.'.product_name ASC'),
  'Subcategory' => array ('var_name' => 'subcategory_name', 'db_sort' => ''.TABLE_SUBCATEGORY.'.subcategory_name ASC'),
  'Category' => array ('var_name' => 'category_name', 'db_sort' => ''.TABLE_CATEGORY.'.sort_order ASC'),
  );

    $sort1 = 'Collection Location';
    $sort2 = 'Category';
    $sort3 = 'Product Name';
    $sort4 = 'Member Name';

// *************************************
// Table style information - START
// *************************************

    $sort1_head_color = '#9ca5b5';
    $sort1_font_color = '#000000';
    $sort1_font_size = '2.5';
    $sort1_border_color = '#777777';
    $sort1_margin_color = '#9ca5b5';
    $sort2_head_color = '#dddddd';
    $sort2_font_color = '#000000';
    $sort2_font_size = '2';
    $sort2_border_color = '#dddddd';
    $sort2_margin_color = '#dddddd';
    $sort3_head_color = '#ffffff';
    $sort3_font_color = '#000000';
    $sort3_font_size = '1.5';
    $sort3_border_color = '#ffffff';
    $sort3_margin_color = '#ffffff';
    $sort4_head_color = '#ffffff';
    $sort4_font_color = '#000000';
    $sort4_font_size = '1';
    $sort4_border_color = '#ffffff';
    $sort4_margin_color = '#ffffff';
    $producer_orders_multi = '
<style type="text/css">
.sort4_head_color
  {
  background:'.$sort4_head_color.';
  }
.sort4_left_color
  {
  border-left:1px solid '.$sort4_border_color.';
  }
.sort4_top_color
  {
  border-top:1px solid '.$sort4_border_color.';
  padding:0.5em;
  border-bottom:1px solid #aaa;
  }
.sort1_head_color
  {
  background:'.$sort1_head_color.';
  }
.sort1_font_color
  {
  color:'.$sort1_font_color.';
  }
.sort1_font_size
  {
  font-size:'.$sort1_font_size.'em;
  }
.sort1_margin_color
  {
  background:'.$sort1_margin_color.';
  }
.sort1_left_color
  {
  border-left:1px solid '.$sort1_border_color.';
  }
.sort1_right_color
  {
  border-right:1px solid '.$sort1_border_color.';
  }
.sort1_top_color
  {
  border-top:1px solid '.$sort1_border_color.';
  }
.sort2_head_color
  {
  background:'.$sort2_head_color.';
  }
.sort2_font_color
  {
  color:'.$sort2_font_color.';
  }
.sort2_font_size
  {
  font-size:'.$sort2_font_size.'em;
  }
.sort2_margin_color
  {
  background:'.$sort2_margin_color.';
  }
.sort2_top_color
  {
  border-top:1px solid '.$sort2_border_color.';
  }
.sort2_left_color
  {
  border-left:1px solid '.$sort2_border_color.';
  }
.sort3_head_color
  {
  background:'.$sort3_head_color.';
  }
.sort3_font_color
  {
  color:'.$sort3_font_color.';
  }
.sort3_font_size
  {
  font-size:'.$sort3_font_size.'em;
  }
.sort3_margin_color
  {
  background:'.$sort3_margin_color.';
  }
.sort3_left_color
  {
  border-left:1px solid '.$sort3_border_color.';
  }
.sort3_top_color
  {
  border-top:1px solid '.$sort3_border_color.';
  }
</style>';

// *************************************
// Table style information - END
// *************************************

// Open one form for all product changes
$producer_orders_multi .= '
  <table align="center"  width="800" cellspacing="0" cellpadding="0" border="0">';
////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///            BEGIN FUNCTION TO CALCULATE DISPLAY OF INVOICE                ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

$show_form = true;

$total_pr = 0;
$subtotal_pr = 0;
//TABLE_BASKET_ALL,
// ADD NEW SORT ITEMS: be sure the search item is being pulled from the database here
$sqlpr = '
  SELECT
    '.TABLE_BASKET.'.bpid,
    '.TABLE_BASKET.'.basket_id,
    '.TABLE_BASKET.'.product_id,
    '.TABLE_BASKET.'.item_price,
    '.TABLE_BASKET.'.quantity,
    '.TABLE_BASKET.'.random_weight,
    '.TABLE_BASKET.'.total_weight,
    '.TABLE_BASKET.'.extra_charge,
    '.TABLE_BASKET.'.out_of_stock,
    '.TABLE_BASKET.'.future_delivery_id,
    '.TABLE_BASKET.'.customer_notes_to_producer,
    '.TABLE_PRODUCT.'.product_name,
    '.TABLE_PRODUCT.'.random_weight,
    '.TABLE_PRODUCT.'.ordering_unit,
    '.TABLE_PRODUCT.'.pricing_unit,
    '.TABLE_PRODUCT.'.margin,
    '.TABLE_SUBCATEGORY.'.subcategory_id,
    '.TABLE_SUBCATEGORY.'.category_id,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_PRODUCT.'.subcategory_id,
    '.TABLE_PRODUCT.'.detailed_notes,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.TABLE_BASKET_ALL.'.deltype AS ddeltype,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.home_phone,
    '.TABLE_MEMBER.'.mem_taxexempt,
    '.TABLE_DELCODE.'.delcode_id,
    '.TABLE_DELCODE.'.delcode,
    '.TABLE_DELCODE.'.deltype,
    '.TABLE_DELCODE.'.truck_code
  FROM
    '.TABLE_BASKET.'
  LEFT JOIN '.TABLE_PRODUCT.'
    ON '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.'
    ON '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id = '.TABLE_PRODUCT.'.storage_id
  LEFT JOIN '.TABLE_SUBCATEGORY.'
    ON '.TABLE_PRODUCT.'.subcategory_id = '.TABLE_SUBCATEGORY.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.'
    ON '.TABLE_SUBCATEGORY.'.category_id = '.TABLE_CATEGORY.'.category_id
  LEFT JOIN '.TABLE_BASKET_ALL.'
    ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
  LEFT JOIN '.TABLE_MEMBER.'
    ON '.TABLE_BASKET_ALL.'.member_id = '.TABLE_MEMBER.'.member_id
  LEFT JOIN '.TABLE_DELCODE.'
    ON '.TABLE_BASKET_ALL.'.delcode_id = '.TABLE_DELCODE.'.delcode_id
  LEFT JOIN '.$table_rt.'
    ON '.TABLE_DELCODE.'.route_id = '.$table_rt.'.route_id
  WHERE
    '.TABLE_DELCODE.'.delcode_id = "'.$delcode_id_curr.'"
    AND '.TABLE_PRODUCT.'.hidefrominvoice = 0
    AND '.TABLE_BASKET_ALL.'.submitted = 1
    AND ('.TABLE_BASKET_ALL.'.delivery_id = '.$delivery_id.'
      OR '.TABLE_BASKET.'.future_delivery_id = '.$delivery_id.')
  ORDER BY ';

if ( $sort1 ) $sqlpr .= ' '.$sort_array[$sort1]['db_sort'].",\n";
if ( $sort2 ) $sqlpr .= ' '.$sort_array[$sort2]['db_sort'].",\n";
if ( $sort3 ) $sqlpr .= ' '.$sort_array[$sort3]['db_sort'].",\n";
if ( $sort4 ) $sqlpr .= ' '.$sort_array[$sort4]['db_sort']."\n";

$resultpr = @mysql_query($sqlpr) or die("Couldn't execute query 1");
if (mysql_num_rows($resultpr) == 0)
  {
    $display .= '<p align="center">-- No orders --</p>';
  }
while ( $row = mysql_fetch_array($resultpr) )
  {
    // ADD NEW SORT ITEMS: be sure the search item is assigned to a variable here
    $product_name = stripslashes ($row['product_name']);
    $product_id = $row['product_id'];
    $basket_id = $row['basket_id'];
    $member_id = $row['member_id'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = $row['business_name'];
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];
    $deltype = $row['deltype'];
    $truck_code = $row['truck_code'];
    $storage_code = $row['storage_code'];
    $quantity = $row['quantity'];
    $ordering_unit = $row['ordering_unit'];
    $item_price = $row['item_price'];
    $product_margin = $row['margin'];

    $bpid = $row['bpid'];
    $email_address = $row['email_address'];
    $home_phone = $row['home_phone'];
    $ddeltype = $row['ddeltype'];
    $mem_taxexempt = $row['mem_taxexempt'];
    
    if (SHOW_ACTUAL_PRICE)
    {
      // Show customer markup as default -- not wholesale
      $item_price /= (1 - (UNIVERSAL_MARGIN + $product_margin));
      $item_price = round($item_price, 2);
    }

    // If there's no last name, then use the business name
    if ( $last_name && $first_name )
      {
        $show_mem2 = $first_name.' '.$last_name;
        $show_mem = $last_name.', '.$first_name;
      }
    else
      {
        $show_mem = $business_name;
      }
    $c_basket_id = $row['basket_id'];
    $category_id = $row['category_id'];
    $subcategory_name = $row['subcategory_name'];
    $category_name = $row['category_name'];
    $random_weight = $row['random_weight'];
    $total_weight = $row['total_weight'];
    $out_of_stock = $row['out_of_stock'];
    $extra_charge = $row['extra_charge'];
    $future_delivery_id = $row['future_delivery_id'];
    $detailed_notes = stripslashes($row['detailed_notes']);
    $notes = stripslashes($row['customer_notes_to_producer']);
    $pricing_unit = $row['pricing_unit'];
    $update_id = "-$c_basket_id-$product_id"; // This is used to uniquely tag each update field
    $total_weight_updated = '';
    $out_of_stock_updated = '';
    ////////////////////////////////////////////////////////////////////////////////
    ///                                                                          ///
    ///             CALCULATE INFORMATION FOR THIS ORDER LINE-ITEM               ///
    ///                                                                          ///
    ////////////////////////////////////////////////////////////////////////////////
    if( $out_of_stock == 1 )
      {
        $display_total_price = CURSYM.number_format(0, 2);
        // This next bit sets the amount to add back in the case of an "un-outed" item
        if ( $random_weight == 1)
          {
            $out_restore_value = round(($item_price * $total_weight) + ($extra_charge * $quantity), 2);
          }
        else
          {
            $out_restore_value = round((($item_price * $quantity) + ($extra_charge * $quantity)), 2);
          }
      }
    if ( $future_delivery_id == $delivery_id )
      {
        $display_weight = '';
        $item_total_price = 0;
        $display_total_price = '<font color="#ff0000">Invoiced in a previous order</font>';
      }
    elseif ( $future_delivery_id > $delivery_id )
      {
        $display_weight = '';
        $item_total_price = 0;
        $display_total_price = '<font color=#ff0000>Future<br>delivery</font>';
      }
    elseif( $out_of_stock != 1)
      {
        if ( $random_weight == 1)
          {
            if ( $show_form )
              {
                $display_weight = '<input type="text" name="total_weight'.$update_id.'" value="'.$total_weight.'" size="2" maxlength="11"><br>'.Inflect::pluralize ($pricing_unit).$total_weight_updated;
              }
            else
              {
                // Do not display form; just the information because it is historic data
                $display_weight = $total_weight.'<br>'.$pricing_unit."s";
              }
            if ( $total_weight == 0 )
              {
                $message_incomplete = '<font color="#770000">Order Incomplete<font>';
              }
            $item_total_3dec = ($item_price * $total_weight) + ($extra_charge * $quantity);
            $item_total_price = round($item_total_3dec, 2);
            $display_unit_price = $item_total_price;
            $display_total_price = CURSYM.number_format($item_total_price, 2);
          }
        else
          {
            $display_weight = '';
            $item_total_3dec = (($item_price * $quantity) + ($extra_charge * $quantity));
            $item_total_price = round($item_total_3dec, 2);
            $display_unit_price = $item_total_price;
            $display_total_price = CURSYM.number_format($item_total_price, 2);
          }
      }
    else
      {
        // Out of stock condition
        $display_weight = '';
        $item_total_price = 0;
        $extra_charge = 0; // If not sold, then no extra charge
      }
    if ( $extra_charge )
      {
        $extra_charge_calc = $extra_charge*$quantity;
        $total_extra = $total_extra + round ($extra_charge_calc, 2);
        $display_charge = CURSYM.number_format($extra_charge_calc, 2);
      }
    else
      {
        $display_charge = '';
      }

    if( $item_total_price )
      {
        $total = $item_total_price+$total;
      }
    $total_pr = $total_pr + $quantity;
    $subtotal_pr = $subtotal_pr + $item_total_price;

// adjust the unit price to what we actually want to display.
$display_price = '';
if ( $display_unit_price != 0 )
  {
    $display_price .= $font.' '.CURSYM.number_format($display_unit_price, 2).'/'.$pricing_unit.'</font>';
  }
if ( $display_unit_price != 0 && $extra_charge != 0 ) $display_price .= '<br>and<br>';
if ( $extra_charge != 0 )
  {
    $display_price .= CURSYM.number_format($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
  }

    ////////////////////////////////////////////////////////////////////////////////
    ///                                                                          ///
    ///                 CALCULATE INDIVIDUAL DISPLAY ELEMENTS                    ///
    ///                                                                          ///
    ////////////////////////////////////////////////////////////////////////////////
    // ADD NEW SORT ITEMS: if the search is NOT in search_array2, then create
    // a new variable for header content here

    $header_member = '
      '.$show_mem.' (#'.$member_id.')';
    $header_member_name = '
      '.$show_mem.' (#'.$member_id.')';
    $header_delivery_code = $delcode.' ('.$delcode_id.')';
    $header_storage_type = '
      <b>Storage Code:</b> ['.$storage_code.']';
    $header_product = '
      <b>Product #'.$product_id.':</b> '.$product_name.'
      <br><font size="-1">'.$display_price.'</font>';
    $header_product_name = '
      <b>'.$product_name.'</b> (#'.$product_id.')';
    $header_subcategory_name = '
      <b>Subcategory:</b> '.$subcategory_name;
    $header_category_name = '
      <b><u>'.$category_name.'</u></b>';
    // Now, just in case they haven't been used in a header, we add line-item member and product information
    // Line items are for information the really SHOULD be represented to each line of the invoice, somehow
    $line_item_member = $header_member;
    $line_item_product = '
      <b>Product #'.$product_id.':</b> '.$product_name.'
      <br>'.$display_price;
    if ( $notes )
      {
        // These aren't appropriate in a header (above) since they are from a particular customer
        $line_item_product .= '<br><b>Customer note:</b>'.$notes;
      }
    // ADD NEW SEARCH ITEMS: if the search is NOT in search_array2, then check
    // conditions for adding it to the various headers sort1, sort2, or sort3 below
    // Get the header for sort1
    if ( $sort_array[$sort1]['var_name'] == 'member_id' )
      {
        $sort1_header = '';
        $line_item_member = $header_member;
      }
    elseif ( $sort_array[$sort1]['var_name'] == 'show_mem' )
      {
        $sort1_header = '';
        $line_item_member = $header_member_name; // We no longer need member info in the line items
      }
    elseif ( $sort_array[$sort1]['var_name'] == 'delcode_id' )
      {
        $sort1_header = $header_delivery_code;
      }
    elseif ( $sort_array[$sort1]['var_name'] == 'product_id' )
      {
        $sort1_header = $header_product;
        $line_item_product = ''; // We no longer need product info in the line items
      }
    elseif ( $sort_array[$sort1]['var_name'] == 'product_name' )
      {
        $sort1_header = $header_product_name;
        $line_item_product = ''; // We no longer need product info in the line items
      }
    elseif ( $sort_array[$sort1]['var_name'] == 'storage_code' )
      {
        $sort1_header = $header_storage_type;
      }
    elseif ( $sort_array[$sort1]['var_name'] == 'subcategory_name' )
      {
        $sort1_header = $header_subcategory_name;
      }
    elseif ( $sort_array[$sort1]['var_name'] == 'category_name' )
      {
        $sort1_header = $header_category_name;
      }
    // Get the header for sort2
    if ( $sort_array[$sort2]['var_name'] == 'member_id' )
      {
        $sort2_header = '';
        $line_item_member = $header_member;
      }
    elseif ( $sort_array[$sort2]['var_name'] == 'show_mem' )
      {
        $sort2_header = '';
        $line_item_member = $header_member_name;
      }
    elseif ( $sort_array[$sort2]['var_name'] == 'delcode_id' )
      {
        $sort2_header = $header_delivery_code;
      }
    elseif ( $sort_array[$sort2]['var_name'] == 'product_id' )
      {
        $sort2_header = $header_product;
        $line_item_product = '';
      }
    elseif ( $sort_array[$sort2]['var_name'] == 'product_name' )
      {
        $sort2_header = $header_product_name;
        $line_item_product = ''; // We no longer need product info in the line items
      }
    elseif ( $sort_array[$sort2]['var_name'] == 'storage_code' )
      {
        $sort2_header = $header_storage_type;
      }
    elseif ( $sort_array[$sort2]['var_name'] == 'subcategory_name' )
      {
        $sort2_header = $header_subcategory_name;
      }
    elseif ( $sort_array[$sort2]['var_name'] == 'category_name' )
      {
        $sort2_header = $header_category_name;
      }
    // Get the header for sort3
    if ( $sort_array[$sort3]['var_name'] == 'member_id' )
      {
        $sort3_header = '';
        $line_item_member = $header_member;
      }
    elseif ( $sort_array[$sort3]['var_name'] == 'show_mem' )
      {
        $sort3_header = '';
        $line_item_member = $header_member_name;
      }
    elseif ( $sort_array[$sort3]['var_name'] == 'delcode_id' )
      {
        $sort3_header = $header_delivery_code;
      }
    elseif ( $sort_array[$sort3]['var_name'] == 'product_id' )
      {
        $sort3_header = $header_product;
        $line_item_product = '';
      }
    elseif ( $sort_array[$sort3]['var_name'] == 'product_name' )
      {
        $sort3_header = $header_product_name;
        $line_item_product = ''; // We no longer need product info in the line items
      }
    elseif ( $sort_array[$sort3]['var_name'] == 'storage_code' )
      {
        $sort3_header = $header_storage_type;
      }
    elseif ( $sort_array[$sort3]['var_name'] == 'subcategory_name' )
      {
        $sort3_header = $header_subcategory_name;
      }
    elseif ( $sort_array[$sort3]['var_name'] == 'category_name' )
      {
        $sort3_header = $header_category_name;
      }
    $line_item = $line_item_product;
    if ( $line_item_member && $line_item_product )
      {
        $line_item .='<br>';
      }
    $line_item .= $line_item_member;
    if( $bpid == $_POST['bpid'] )
      {
        $line_item .= '<br>'.$message2;
      }
      
    $line_markup = '
      <tr>
        <td class="sort1_left_color sort1_margin_color">&nbsp;</td>
        <td class="sort2_left_color sort2_margin_color">&nbsp;</td>
        <td class="sort3_left_color sort3_margin_color">&nbsp;</td>
        <td align="left" valign="top" class="sort4_top_color sort4_left_color">'.$line_item.'</td>
        <td align="center" valign="top" class="sort4_top_color">&nbsp;'.$quantity.' '.Inflect::pluralize_if ($quantity, $ordering_unit).'</td>
        <td align="center" valign="top" class="sort4_top_color">&nbsp;'.$display_weight.'</td>
        <td align="left" valign="top" class="sort4_top_color"><table border="0" cellpadding="0" cellspacing="0"><tr><td>'.$display_outofstock.'</td><td>'.$display_stock.'</td></tr></table></td>
        <td align="center" valign="top" class="sort1_right_color sort4_top_color">&nbsp;'.$display_total_price.'
          <input type="hidden" name="product_id'.$update_id.'" value="'.$product_id.'">
          <input type="hidden" name="bpid'.$update_id.'" value="'.$bpid.'">
          <input type="hidden" name="member_id'.$update_id.'" value="'.$member_id.'">
        </td>';

    ////////////////////////////////////////////////////////////////////////////////
    ///                                                                          ///
    ///                         SEND PRIMARY SORT HEADER                         ///
    ///                                                                          ///
    ////////////////////////////////////////////////////////////////////////////////
    // This compares i.e. $sort_array[$sort1]['value'] with $member_id
    // when $sort_array[$sort1]['var_name'] happens to be 'member_id'
    // And if they're different, then we need a new major section...
    if ( $sort_array[$sort1]['value'] != $$sort_array[$sort1]['var_name'] )
      {
        // Assign the new value to compare against
        $sort_array[$sort1]['value'] = $$sort_array[$sort1]['var_name'];
        // We will also want to force a second-level sort subsection
        $sort_array[$sort2]['value'] = '';
        // Now add the first-level sort section header
        // Only add if it contains something
        $producer_orders_multi .= $sort1_header == '' ? '' : '
          <tr class="sort1_head_color">
            <td colspan="9" class="sort1_top_color sort1_left_color sort1_right_color">
              <table width="100%">
                <tr class="sort1_head_color">
                  <td align="center" class="sort1_font_color sort1_font_size">'.$sort1_header.'</td>
                </tr>
              </table>
            </td>
          </tr>';
      }
    //echo "SORT_ARRAY[SORT1][VALUE]: ".$sort_array[$sort1]['value']."<br>\n";
    //echo "SORT_ARRAY[SORT1][".$sort_array[$sort1]['var_name']."]: ".$$sort_array[$sort1]['var_name']."<br>\n";
    ////////////////////////////////////////////////////////////////////////////////
    ///                                                                          ///
    ///                        SEND SECONDARY SORT HEADER                        ///
    ///                                                                          ///
    ////////////////////////////////////////////////////////////////////////////////
    // This compares i.e. $sort_array[$sort2]['value'] with $member_id
    // when $sort_array[$sort2]['var_name'] happens to be 'member_id'
    // And if they're different, then we need a new secondary section...
    if ( $sort_array[$sort2]['value'] != $$sort_array[$sort2]['var_name'] )
      {
        // Assign the new value to compare against
        $sort_array[$sort2]['value'] = $$sort_array[$sort2]['var_name'];
        // We will also want to force a third-level sort subsection
        $sort_array[$sort3]['value'] = '';
        // Now add the second-level sort section header
        // Only add if it contains something
        $producer_orders_multi .= $sort2_header == '' ? '' : '
          <tr class="sort2_head_color">
            <td class="sort1_head_color sort1_left_color">&nbsp;</td>
            <td colspan="8" class="sort1_right_color sort2_top_color sort2_left_color">
              <table width="100%">
                <tr class="sort2_head_color">
                  <td align="left" class="sort2_font_color sort2_font_size">'.$sort2_header.'</td>
                </tr>
              </table>
            </td>
          </tr>';
      }
    ////////////////////////////////////////////////////////////////////////////////
    ///                                                                          ///
    ///                        SEND TERTIARY SORT HEADER                         ///
    ///                                                                          ///
    ////////////////////////////////////////////////////////////////////////////////
    // This compares i.e. $sort_array[$sort3]['value'] with $member_id
    // when $sort_array[$sort3]['var_name'] happens to be 'member_id'
    // And if they're different, then we need a new secondary section...
    if ( $sort_array[$sort3]['value'] != $$sort_array[$sort3]['var_name'] )
      {
        // Assign the new value to compare against
        $sort_array[$sort3]['value'] = $$sort_array[$sort3]['var_name'];
        // There is no fourth-level subsection
        // Now add the third-level sort section header
        // Only add if it contains something
        $producer_orders_multi .= $sort3_header == '' ? '' : '
          <tr class="sort3_head_color">
            <td class="sort1_head_color sort1_left_color">&nbsp;</td>
            <td class="sort2_head_color sort2_left_color">&nbsp;</td>
            <td colspan="7" class="sort1_right_color sort3_left_color sort3_top_color sort3_head_color">
              <table width="100%">
                <tr class="sort3_head_color">
                  <td align="left" class="sort3_font_color sort3_font_size">'.$sort3_header.'</td>
                </tr>
              </table>
            </td>
          </tr>';
      }
    $producer_orders_multi .= $line_markup;
  }
$producer_orders_multi .= '
  <tr>
    <td colspan="9" align="center">
      <input type="hidden" name="sort1" value="'.$sort1.'">
      <input type="hidden" name="sort2" value="'.$sort2.'">
      <input type="hidden" name="sort3" value="'.$sort3.'">
      <input type="hidden" name="sort4" value="'.$sort4.'">
      <input type="hidden" name="delivery_id" value="'.$delivery_id.'">
    </td>
  </tr>
</table>';

// Restore the session variables to their original settings
$member_id = $original_session_member_id;
}

?>

<html>
<head>
<title>Who Ordered What by Collection Location</title>
<body bgcolor="#FFFFFF">
<?php
  include("template_hdr.php");
  
  echo $display;

  echo $producer_orders_multi;
  
  include("template_footer.php");
?>
