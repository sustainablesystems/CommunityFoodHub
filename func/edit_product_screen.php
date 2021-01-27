<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
include_once ('counties_and_countries.php');
$authorization = get_auth_types($_SESSION['auth_type']);

$display .= '
<script type="text/javascript">
<!--
var action_timeout;

function lookup(inputString)
{
  $.post("'.PATH.'ajax/subcat2cat_fee.php", {subcategory_id: ""+inputString+""}, function(data)
    {
    if(data.length > 2)
      {
      var data_array = data.split("|");
      document.getElementById("category_name").value = data_array[0]; // Category
      document.getElementById("coop_fee").value = data_array[1]; // Coop Fee
      updatePrices();
      }
    });
} // lookup
function updatePrices()
{
  var overall_margin = document.getElementById("overall_margin_coop").value / 100.0;
  if ((overall_margin >= 1.0) || (overall_margin <= -1.0))
  {
    overall_margin = '.(SHOW_ACTUAL_PRICE ? UNIVERSAL_MARGIN : 0).';
  }
  document.getElementById("unit_price_prdcr").value=(document.getElementById("unit_price_coop").value*'.(1 - PRODUCER_MARKDOWN).').toFixed(2);
  document.getElementById("unit_price_cust").value=(document.getElementById("unit_price_coop").value/(1 - overall_margin)).toFixed(2);
  document.getElementById("overall_margin_coop").value=(overall_margin * 100.0).toFixed(2) ;
}
function beginUpdateMargin(delta)
{
  updateMargin(delta);
  action_timeout = setInterval(function(){updateMargin(delta)},100);
}
function updateMargin(delta)
{
  var new_margin = parseFloat(document.getElementById("overall_margin_coop").value) + delta;
  document.getElementById("overall_margin_coop").value = new_margin;
  updatePrices();
}
function endUpdateMargin(delta)
{
  if (typeof(action_timeout) != "undefined") clearTimeout(action_timeout);
}
// Calculate a suggested price for the compound product based on its subproducts.
function updateCompoundPrice(event)
{
  var subProdID = event.target.id;
  var subProdQuantityNew;

  if ( event.target.value == "" )
  {
    subProdQuantityNew = 0;
  }
  else
  {
    subProdQuantityNew = parseFloat( event.target.value );
  }
  
  if ( !isNaN(subProdQuantityNew) && subProdQuantityNew >= 0 )
  {
    var subProdPrice = parseFloat( document.getElementById("price_" + subProdID).value );
    var compoundProdTotal = parseFloat( document.getElementById("unit_price_coop").value );
    
    // The box was previously empty
    var subProdQuantityOld = parseFloat( event.target.defaultValue );
    if ( isNaN(subProdQuantityOld) ) subProdQuantityOld = 0;

    subProdQuantityNew = Math.round( subProdQuantityNew );

    compoundProdTotal += subProdPrice * ( subProdQuantityNew - subProdQuantityOld );
    document.getElementById("unit_price_coop").value = compoundProdTotal.toFixed(2);

    event.target.value = subProdQuantityNew > 0 ? subProdQuantityNew : "";
  }

  // Invalid input - reset it
  else
  {
    event.target.value = event.target.defaultValue;
  }

  // Remember the value
  event.target.defaultValue = event.target.value;

  updatePrices();
}
// Functions for brand text box
function last_choice(selection)
{
  return selection.selectedIndex == (selection.length - 1);
}
function process_brand(selection,new_brand_div)
{
  if(last_choice(selection))
  {
    new_brand_div.style.display = "";
  }
  else
  {
    new_brand_div.style.display = "none";
    document.getElementById("new_brand_name_textbox").value = "";
  }
}

// -->
</script>

';

// If auth_type is not the adminstrator than reset producer to self
if ( $producer_id_you && $authorization['administrator'] === false )
  {
    $producer_id = $producer_id_you;
  }

if ( $authorization['administrator'] === true )
  {
    $admin = 'yes';
  }


include('edit_product_screen_updatequery.php');
include('edit_product_screen_helpalerts.php');
include('edit_product_screen_selectsubprods.php');

// Get the current saved information for this product
if ( $action == 'edit' )
  {
    include('edit_product_screen_selectprod.php');
  }

// $action = "edit" will be set to enable editing the product.

$product_current_subcategory_id = $subcategory_id;
  $sqlsc = '
    SELECT
      *
    FROM
      '.TABLE_SUBCATEGORY.',
      '.TABLE_CATEGORY.'
    WHERE
      '.TABLE_SUBCATEGORY.'.category_id = '.TABLE_CATEGORY.'.category_id
    ORDER BY
      category_name ASC,
      subcategory_name ASC';
  $rs = @mysql_query($sqlsc,$connection) or die(mysql_error());
  $display_subcat = '
    <option value="">Select Subcategory</option>';
  while ( $row = mysql_fetch_array($rs) )
    {
      $subcategory_id = $row['subcategory_id'];
      $subcategory_name = $row['subcategory_name'];
      $category_name = $row['category_name'];
      $option_select = '';
      // Is this the option that has already been selected (for editing existing products)?
      if ( $subcategory_id == $product_current_subcategory_id )
        {
          $option_select = ' selected';
        }
      if ( $category_name != $prior_category_name ) // Category changes, so do a new optgroup
        {
          if ( $prior_category_name != '' ) // If this is not the first change, then close the prior optgroup
            {
              $display_subcat .= '
                </optgroup>';
            }
          $display_subcat .= '
            <optgroup label="'.$category_name.'">';
        }
      $display_subcat .= '
        <option value="'.$subcategory_id.'"'.$option_select.'>'.$subcategory_name.'</option>';
      $prior_category_name = $category_name;
    }
  $display_subcat .= '
    </optgroup>';

// Get origin options (hardcoded country and county)
foreach ($countries_list as $country_loop)
{
    if ($country_loop == $country)
    {
        $country_options .= '<option value="'.$country.'" selected>'.$country.'</option>';
    }
    else
    {
        $country_options .= '<option value="'.$country_loop.'">'.$country_loop.'</option>';
    }
}
foreach ($uk_counties_list as $uk_county_loop)
{
    if ($uk_county_loop == $uk_county)
    {
        $uk_county_options .= '<option value="'.$uk_county.'" selected>'.$uk_county.'</option>';
    }
    else
    {
        $uk_county_options .= '<option value="'.$uk_county_loop.'">'.$uk_county_loop.'</option>';
    }
}

// Get brand names (from database)
$sql_get_brands = '
    SELECT brand_name
    FROM '.TABLE_BRAND.'
    ORDER BY brand_name ASC';
$res_get_brands = @mysql_query($sql_get_brands, $connection) or die(mysql_error());
while ( $row = mysql_fetch_array($res_get_brands) )
{
    $brand_loop = $row['brand_name'];
    if ($brand_loop == $brand_name)
    {
        $brand_options .= '<option value="'.$brand_name.'" selected>'.$brand_name.'</option>';
    }
    else
    {
        $brand_options .= '<option value="'.$brand_loop.'">'.$brand_loop.'</option>';
    }
}
$brand_options .= '<option value="">Add new brand...</option>';

// TODO: This should be read from the production_types DB table.
if ( $prodtype_id == 1 )
  {
    $prodtype_current = 'Certified Organic';
  }
elseif( $prodtype_id == 2 )
  {
    $prodtype_current = 'All Natural';
  }
elseif($prodtype_id == 3)
  {
    //$prodtype_current = "80% Organic";
    $prodtype_current = "Non-organic (in conversion)";
  }
elseif($prodtype_id == 4)
  {
    $prodtype_current = "100% Organic";
  }
elseif($prodtype_id == 6)
  {
    $prodtype_current = "Demeter Biodynamic";
  }
else
  {
    $prodtype_current = 'Not Designated';
  }
if ( $prodtype_id )
  {
    $pt_first  = '
      <option value="'.$prodtype_id.'">'.$prodtype_current.'</option>';
  }
else
  {
    $pt_first  = '
      <option value="">Product Type</option>';
  }
$display_pt .= '
  <option value="1">Certified Organic</option>
  <option value="2">All Natural</option>
  <option value="3">Non-organic (in conversion)</option>
  <option value="4">100% Organic</option>
  <option value="6">Demeter Biodynamic</option>
  <option value="5">Not Designated</option>';
if ( $retail_staple == 1 )
  {
    $chkf1 = 'checked';
    $chkf2 = '';
    $chkf3 = '';
  }
elseif ( $retail_staple == 2 )
  {
    $chkf1 = '';
    $chkf2 = 'checked';
    $chkf3 = '';
  }
elseif ( $retail_staple == 3 )
  {
    $chkf1 = '';
    $chkf2 = '';
    $chkf3 = 'checked';
  }
else
  {
    $chkf1 = '';
    $chkf2 = '';
    $chkf3 = '';
  }
if ( !$unit_price )
  {
    $show_unit_price = '0.00';
  }
else
  {
  $show_unit_price = number_format($unit_price, 2);
  }
if ( !$ordering_unit )
  {
    $show_ordering_unit = '';
  }
else
  {
  $show_ordering_unit = $ordering_unit;
  }
if ( $donotlist == 2 )
  {
    $donotlist_chk1 = '';
    $donotlist_chk2 = '';
    $donotlist_chk3 = 'checked';
    $donotlist_chk4 = '';
  }
elseif ($donotlist == 1)
  {
    $donotlist_chk1 = '';
    $donotlist_chk2 = 'checked';
    $donotlist_chk3 = '';
    $donotlist_chk4 = '';
    }
  elseif ($donotlist == '3')
    {
    $donotlist_chk1 = '';
    $donotlist_chk2 = '';
    $donotlist_chk3 = '';
    $donotlist_chk4 = 'checked';
  }
else
  {
    $donotlist_chk1 = 'checked';
    $donotlist_chk2 = '';
    $donotlist_chk3 = '';
    $donotlist_chk4 = '';
  }
if ( ! $random_weight )
  {
    $chk3 = 'checked';
    $chk4 = '';
    $chk3d = ' style="display:none;"'; // hide this section if not needed
  }
elseif ( $random_weight == 1 )
  {
    $chk3 = '';
    $chk4 = 'checked';
  }
if ( ! $future_delivery )
  {
    $chk5 = 'checked';
    $chk6 = '';
  }
elseif ($future_delivery == 1 )
  {
    $chk5 = '';
    $chk6 = 'checked';
  }
if ( ! $inventory_on )
  {
    $chk7 = '';
    $chk8 = 'checked';
    $chk8d = ' style="display:none;"'; // hide this section if not needed
  }
elseif ( $inventory_on == 1 )
  {
    $chk7 = 'checked';
    $chk8 = '';
  }
// Added subproduct display
if ( ! $is_compound )
  {
    $chk9 = '';
    $chk10 = 'checked';
    $chk10d = ' style="display:none;"'; // hide this section if not needed
  }
elseif ( $is_compound == 1 )
  {
    $chk9 = 'checked';
    $chk10 = '';
  }
if ( $meat_weight_type )
  {
    $meat_first  = '
      <option value="'.$meat_weight_type.'">'.$meat_weight_type.'</option>';
    $display_meat .= '
      <option value="'.$meat_weight_type.'">---------</option>';
  }
else
  {
    $meat_first  = '
      <option value="">Meat Weight Type</option>';
    $display_meat .= '
      <option value="">---------</option>';
  }
$display_meat .= '
  <option value="LIVE">LIVE</option>
  <option value="PROCESSED">PROCESSED</option>
  <option value="DRESSED/HANGING">DRESSED/HANGING</option>
  <option value="">NONE</option>';
$trbg   = 'bgcolor="#dddddd"';
$trbg2  = 'bgcolor="#eeeeee"';
$trbg3  = 'bgcolor="#ffccbb"';
$display .= '
  <table bgcolor="#CCCCCC" border="0" cellpadding="2" cellspacing="2">
    <tr bgcolor="#770000">
      <th><font color="#FFFFFF">Help</font></th>
      <th><font color="#FFFFFF">Headings</font></th>
      <th><font color="#FFFFFF">Product Information</font></th>
    </tr>';
if( $action == 'edit' )
  {
    $forml = '<form action="edit_products.php?product_id='.$product_id.'&producer_id='.$producer_id.'&a='.$_REQUEST['a'].'" method="post">';
  }
elseif ( $action == 'add' )
  {
    $forml = '<form action="'.$PHP_SELF.'?producer_id='.$producer_id.'&a='.$_REQUEST['a'].'" method="post">';
  }
$display .= '
    <tr '.$trbg2.'>'.$tr1.'
      <td>'.$forml.' '.$font.'
        <b>Availability</b></td><td>'.$font.' '.$alert1.'<b>
        <input type="radio" name="donotlist" value="0" '.$donotlist_chk1.'> List as RETAIL<br>
        <input type="radio" name="donotlist" value="3" '.$donotlist_chk4.'> List as WHOLESALE<br>
      <input type="radio" name="donotlist" value="1" '.$donotlist_chk2.'> Do not list<br>
        <input type="radio" name="donotlist" value="2" '.$donotlist_chk3.'> Archive this product<br>
        </b>
      </td>
    </tr>';

$display .= '
    <tr '.$trbg2.'>'.$tr2.'
      <td>'.$font.' <b>Product Name</b></td>
      <td>'.$font.' '.$alert2.'
        <input name="product_name" size="60" maxlength="75" value="'.htmlentities($product_name, ENT_QUOTES).'"><br>
        <font size="-2">(max. length 75 characters)'
        .($action=="edit"
          ? '<br>(Only basic changes - do not completely change. Click here if you want to
            <a href="add_products.php?producer_id='.$producer_id.'">add a new product</a>.)'
          : '')
        .'</font>
      </td>
    </tr>
    <tr '.$trbg2.'>'.$tr7.'
      <td>'.$font.' <b>Product Details</b><br>(optional)</td>
      <td>'.$font.' <textarea name="detailed_notes" cols="60" rows="7">'.htmlentities($detailed_notes, ENT_QUOTES).'</textarea></td>
  ';

// Add optional origin and brand.
$display .= '
    <tr '.$trbg2.'><td>&nbsp;</td>
      <td>'.$font.' <b>Brand</b><br>(optional)</td>
      <td>'.$font.' 
        <select name="brand_name" onChange=\'{process_brand(this,document.getElementById("new_brand_name"));}\'>
          <option value="">Select Brand</option>
          '.$brand_options.'
        </select>
        <div id="new_brand_name" style="display:none;">
          '.$font.' What is the name of the new brand? &nbsp;
          <input type="text" id="new_brand_name_textbox" name="new_brand_name" size="20" maxlength="40">
        </div>
      </td>
    </tr>
    <tr '.$trbg2.'><td>&nbsp;</td>
      <td>'.$font.' <b>Origin</b><br>(optional)</td>
      <td>'.$font.' 
        <select name="country">
          <option value="">Select Country</option>'
          .$country_options.'
        </select>
        <select name="uk_county">
          <option value="">Select County</option>
          '.$uk_county_options.'
        </select>
      </td>
    </tr>';

if (SHOW_ACTUAL_PRICE)
{
  //$overall_margin = UNIVERSAL_MARGIN + $product_margin;
  $selling_price = $show_unit_price / (1 - ($overall_margin / 100.0));
  $selling_price = round($selling_price, 2);
}
else
{
  $selling_price = $show_unit_price;
  $overall_margin = 0;
}

$display .= '
    <tr '.$trbg2.'>'.$tr4.'
      <td>'.$font.' <b>Subcategory</b></td>
      <td>'.$font.' '.$alert4.'
        <select id="subcategory_id" name="subcategory_id" onChange="lookup(document.getElementById("subcategory_id").value)">
          '.$subcat_first.'
          '.$display_subcat.'
        </select>
      </td>
    </tr>
    <tr '.$trbg2.'>'.$tr4b.'
      <td>'.$font.' <b>Contents</b></td>
      <td>'.$font.' Is this product a box or hamper? &nbsp; <input type="radio" name="has_contents" value="1" '.$chk9.' onClick=\'{document.getElementById("contents").style.display="";}\'>'.$font.' Yes &nbsp; &nbsp;
        <input type="radio" name="has_contents" value="0" '.$chk10.' onClick=\'{document.getElementById("contents").style.display="none";}\'>'.$font.' No<br>
        <div id="contents"'.$chk10d.'>
          <table>
            <tr>
              <td>
                '.$font.' What products does it contain?<br><font color="red"><i>Products in RED are no longer listed and must be removed or replaced.</i></font>&nbsp;
                <div style="height: 200px; width: 500px; overflow: auto; background: white; border: 1px solid #aaaaaa;">
                  <table>'.( $action == 'edit' ? edit_subproducts_list($producer_id, $product_id) : add_subproducts_list($producer_id) ).'</table>
                </div>
              </td>
              <td valign="top">&nbsp;
              </td>
            </tr>
          </table>
        </div>
      </td>
    </tr>
    <tr '.$trbg2.'>'.$tr4b.'
      <td>'.$font.' <b>Inventory</b></td>
      <td>'.$font.' Display and use inventory amounts? &nbsp; <input type="radio" name="inventory_on" value="1" '.$chk7.' onClick=\'{document.getElementById("inventory").style.display="";}\'>'.$font.' Yes &nbsp; &nbsp; 
        <input type="radio" name="inventory_on" value="0" '.$chk8.' onClick=\'{document.getElementById("inventory").style.display="none";}\'>'.$font.' No (unlimited supply)<br>
        <div id="inventory"'.$chk8d.'>
          '.$font.' How many units are available? &nbsp; 
          <input type="text" name="inventory" value="'.$inventory.'" size=4 maxlength="6">
        </div>
      </td>
    </tr>
    <tr '.$trbg2.'>'.$tr5.'
      <td>'.$font.' <b>Price and Pricing Unit</b></td>
      <td>
        <table border="0">
          <tr>
            <td style="padding:0 1em;" align="right">'.$font.' '.$alert5.'<b>Producer&nbsp;Price</b>&nbsp;(-'.(PRODUCER_MARKDOWN * 100).'%)</td>
            <td><nobr>'.$font.CURSYM.'</b> <input type="text" id="unit_price_prdcr" name="unit_price_producer" value="'.number_format($show_unit_price * (1 - PRODUCER_MARKDOWN), 2).'" size="6" maxlength="6" disabled></nobr></td>
            <td style="padding:0 1em;" rowspan="2"><b>'.$font.' per '.$alert5a.'<input name="pricing_unit" size="12" maxlength="12" value="'.$pricing_unit.'">(s)</b><br>(Use singular, not plural - e.g. kilo, item, jar, bag, box, pack)</td>
          </tr>
          <tr>
            <td style="padding:0 1em;" align="right">'.$font.' '.$alert5.'<b>Cost&nbsp;Price</td>
            <td><nobr>'.$font.CURSYM.'</b> <input type="text" id="unit_price_coop" name="unit_price" value="'
              .number_format($show_unit_price, 2).'" size=6 maxlength="6" onKeyUp="updatePrices()"
onChange="document.getElementById("unit_price_coop").value=(document.getElementById("unit_price_coop").value*1).toFixed(2)"></nobr></td>
          </tr>
          <tr>
            <td style="padding:0 1em;" align="right">'.$font.' '.$alert5.'<b>Margin</b></td>
            <td><nobr>%&nbsp;<input type="text" id="overall_margin_coop" name="overall_margin" value="'
              .number_format($overall_margin, 2).'" size="5" maxlength="5" onKeyUp="updatePrices()"
onChange="document.getElementById("overall_margin_coop").value=(document.getElementById("overall_margin_coop").value*1).toFixed(2)" readonly>
              </nobr></td>

            <td style="padding:0 1em;" rowspan="2">
              <input type="button" value=" /\ " onMouseDown="beginUpdateMargin(0.25)"
                onMouseUp="endUpdateMargin()" onMouseOut="endUpdateMargin()"
                style="font-size:10px;margin:0;padding:0;width:20px;height:20px;" >
              <br>
              <input type=button value=" \/ " onMouseDown="beginUpdateMargin(-0.25)"
                onMouseUp="endUpdateMargin()" onMouseOut="endUpdateMargin()"
                style="font-size:10px;margin:0;padding:0;width:20px;height:20px;" >
            </td>
          </tr>
          <tr>
            <td style="padding:0 1em;" align="right">'.$font.' '.$alert5.'<b>Retail&nbsp;Price</b>'
          .'</td>
            <td><nobr>'.$font.CURSYM.'</b> <input type="text" id="unit_price_cust" name="selling_price" value="'
              .number_format($selling_price, 2).'" size="6" maxlength="6" readonly></nobr></td>
          </tr>';
$display .= '
        </table>
      </td>
    </tr>
    <tr '.$trbg2.'>'.$tr5b.'
      <td>'.$font.' <b>Ordering Unit</b></td>
      <td>
        '.$font.' Order by number of'.$alert5b.'
        <input name="ordering_unit" size="20" maxlength="20" value="'.htmlentities($ordering_unit, ENT_QUOTES).'">(s)<br>
        (Use singular, not plural - e.g. half kilo, item, jar, bag, box, pack)<br>
        (If you specify "half xxxx" or "quarter xxxx" the price you entered above will be divided accordingly for display to the customer.)
      </td>
    </tr>
    <tr '.$trbg2.'>'.$tr5c.'
      <td>'.$font.' <b>Extra Charge</b></td>
      <td><nobr>
        '.$font.CURSYM.' <input type="text" name="extra_charge" value="'.number_format($extra_charge, 2).'" size="6" maxlength="6"></nobr>
        (Not subject to coop fees or taxes. Authorisation is required before using this charge.)
      </td>
    </tr>
    <tr '.$trbg.'>'.$tr8.'
      <td>'.$font.' <b>Random Weight</b></td>
      <td>'.$font.' '.$alert8.'
        Will producer need to enter a weight on the invoice to determine price?
        <font size=-2>(Please see instructions.)</font><br>
        <input type="radio" name="random_weight" value="1" '.$chk4.' onClick=\'{document.getElementById("max_min").style.display="";document.getElementById("weight_type").style.display="";}\'> Yes
        <input type="radio" name="random_weight" value="0" '.$chk3.' onClick=\'{document.getElementById("max_min").style.display="none";document.getElementById("weight_type").style.display="none";}\'> No
      </td>
    </tr>
    <tr '.$trbg.' id="max_min"'.$chk3d.'>'.$tr9.'
      <td>'.$font.' <b>Min/Max Weight</b></td>
      <td>
        '.$font.' '.$alert8.' If Random Weight is Yes: <br>
        <b>Approx. Minimum weight</b>:
        <input type="text" name="minimum_weight" value="'.$minimum_weight.'" size="6" maxlength="6">
        &nbsp;&nbsp;&nbsp;&nbsp;<b>Approx. Maximum weight</b>:
        <input type="text" name="maximum_weight" value="'.$maximum_weight.'" size="6" maxlength="6"><br>
        (For example, if pricing unit is pounds, min. weight could be 1 pound, max. weight could be 2 pounds. Use up to 2 decimal places.)
      </td>
    </tr>
    <tr '.$trbg.' id="weight_type"'.$chk3d.'>'.$tr12.'
      <td>'.$font.' <b>Meat Weight Type</b></td>
      <td>
        '.$font.' '.$alert12.' Meat weight type is only valid for random weight items:
        <select name="meat_weight_type">
          '.$meat_first.'
          '.$display_meat.'
        </select>
      </td>
    </tr>
    <tr '.$trbg2.'>'.$tr6.'
      <td>'.$font.' <b>Product Type</b></td>
      <td>
        '.$font.' '.$alert6.'
        <select name="prodtype_id">
          '.$pt_first.'
          '.$display_pt.'
        </select>
      </td>
    </tr>';
$storage_types = '';
$query = '
  SELECT
    storage_id,
    storage_type
  FROM
    '.TABLE_PRODUCT_STORAGE_TYPES;
$sql = mysql_query($query);
while ( $row2 = mysql_fetch_array($sql) )
  {
    if ( $row2['storage_id'] == $storage_id )
      {
        $selected[$row2['storage_id']] = "SELECTED";
      }
    $storage_types .= '
      <option value="'.$row2['storage_id'].'" '.$selected[$row2['storage_id']].'>'.$row2['storage_type'].'</option>';
  }
$display .= '
    <tr '.$trbg2.'>
      <td></td>
      <td>'.$font.' <b>Storage Type</b></td>
      <td>'.$font.' Indicate the type of storage:
        <select name="storage_id">
          <option value="1">Choose a storage type</option>
          '.$storage_types.'
        </select>
      </td>
    </tr>
    <tr '.$trbg2.'>'.$tr10.'
      <td>'.$font.' <b>Future Delivery</b></td>
      <td>'.$font.' '.$alert10.'
        If this product needs to be ordered one or more order cycles in advance of the order cycle in
        which it will be delivered, contact <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a> for assistance.
      </td>
    </tr>';
if( $action == 'edit' )
  {
    $display .= '
      <tr '.$trbg.'>'.$tr14.'
        <td>'.$font.'<b>Save as New?</b></td>
        <td align="left">'.$font.' '.$alert14.'
          <input type="hidden" name="new" value="'.$new.'">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="product_id" value="'.$product_id.'">
          <input type="hidden" name="producer_id" value="'.$producer_id.'">
          <input type="hidden" name="unit_price_old" value="'.$unit_price_old.'">
          <input type="hidden" name="ordering_unit_old" value="'.$ordering_unit_old.'">
          <input type="checkbox" name="where" value="Save as a New Product"> Check here to keep the original product as it was and save these changes as a new product. The new product will not have an image, even if this one does.
        </td>
      </tr>
      <tr '.$trbg.'>'.$tr12.'
        <td colspan="2" align="center">
          <table width="100%" border="0" '.$trbg.' width="100%">
            <tr>
              <td align="center">
                <input name="submit_action" type="submit" value="Update Product">
              </td>
              <td align="center">
                <input name="submit_action" type="submit" value="Cancel">
                </form>
              </td>
            </tr>
          </table>
        </td>
      </tr>';
  }
elseif( $action == 'add' )
  {
    $display .= '
      <tr '.$trbg2.'>'.$tr11.'
        <td colspan="2" align="right">
          <input type="hidden" name="producer_id" value="'.$producer_id.'">
          <input name="submit_action" type="submit" value="Add Product">
          </form>
        </td>
      </tr>';
  }
$display .= '
  </table>';
$display .= $font.' <br>For questions not covered in the <a href="help.php">(?)</a>links, contact <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a>';
include('../func/show_businessname.php');
$help = '
  <table>
    <tr>
      <td valign="top">
        <font face="arial" size="2">If you have any questions about what a particular section means, please click on the question mark (?) to the left of that section.  If you are still not sure, then please e-mail <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a>.<br><br>
      </td>
      <td>
        <form action="'.$PHP_SELF.'?producer_id='.$producer_id.'&a='.$_REQUEST['a'].'" method="post">
          <input type="hidden" name="product_id" value="'.$product_id.'">
          <input type="hidden" name="producer_id" value="'.$producer_id.'">
          <input name="submit_action" type="submit" value="Cancel">
        </form>
      </td>
    </tr>
  </table>';
?>