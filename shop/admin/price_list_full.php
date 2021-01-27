<?php
// NOTE: This is a modified version of printprod_list_all.php
// that gives a simplified price list in the format FGU wants.

include_once ("config_foodcoop.php");
include_once ('general_functions.php');
include_once('../func/delivery_funcs.php');

if ($_GET["user"] == "producer")
{
  // Accessed from member page
  $user_type = 'valid_m';
}
else
{
  // Accessed from admin page
  $user_type = 'valid_c';
}

session_start();
validate_user();

if ($_GET["cycle"] == "prev")
{
  $product_table = TABLE_PRODUCT_PREV;

  $prev_delivery_id = $_GET["delivery_id"];
  $delivery_date = get_delivery_date_str($prev_delivery_id);
}
else
{
  $product_table = TABLE_PRODUCT_PREP;
  $delivery_date = $current_delivery_date;
}

// Whether to show cost price and margin as well as selling price
if ($_GET["pricing"] == "full")
{
  $show_full_pricing_info = true;
}
else
{
  $show_full_pricing_info = false;
}

$font_category = '<font face="arial" size="+2">';
$font_product = '<font face="arial" size="+1">';

$display .= '
  <table border="1" cellpadding="0" cellspacing="0" width="800">';
if ($show_full_pricing_info)
{
  $display .= '
    <tr>
      <td valign="top"><i>Default Margin for New Products = '.UNIVERSAL_MARGIN * 100.0.'%</i></td>
      <td valign="top"><b>Cost price</b></td>
      <td valign="top"><b>'.(SHOW_ACTUAL_PRICE ? '' : '<font color="grey">')
        .'Margin'.(SHOW_ACTUAL_PRICE ? '' : '</font>').'</b></td>
      <td valign="top"><b>Selling price</b></td>
    </tr>';
}

$sql = '
  SELECT DISTINCT
    '.TABLE_CATEGORY.'.*
  FROM
    '.$product_table.'
  INNER JOIN '.TABLE_SUBCATEGORY.'
    ON '.$product_table.'.subcategory_id = '.TABLE_SUBCATEGORY.'.subcategory_id
  INNER JOIN '.TABLE_CATEGORY.'
    ON '.TABLE_SUBCATEGORY.'.category_id = '.TABLE_CATEGORY.'.category_id
  WHERE
    '.$product_table.'.donotlist != "1"
    AND '.$product_table.'.donotlist != "2"
  ORDER BY
    sort_order ASC';

$rs = @mysql_query($sql,$connection) or die("Couldn't execute category query.");
while ( $row = mysql_fetch_array($rs) )
  {
    $category_id = $row['category_id'];
    $category_name = stripslashes($row['category_name']);
    
    $display .= '
          <tr>
            <td colspan="4">'.$font_category.'<b>'.$category_name.'</b></font></td>
          </tr>';

    $sql = '
      SELECT
        *
      FROM
        '.TABLE_PRODUCT_TYPES.',
        '.$product_table.'
      LEFT JOIN '.TABLE_SUBCATEGORY.'
        ON '.$product_table.'.subcategory_id = '.TABLE_SUBCATEGORY.'.subcategory_id
      LEFT JOIN '.TABLE_CATEGORY.'
        ON '.TABLE_SUBCATEGORY.'.category_id = '.TABLE_CATEGORY.'.category_id
      WHERE
        '.TABLE_CATEGORY.'.category_id = "'.$category_id.'"
        AND '.$product_table.'.prodtype_id = '.TABLE_PRODUCT_TYPES.'.prodtype_id
        AND '.$product_table.'.donotlist != "1"
        AND '.$product_table.'.donotlist != "2"
      ORDER BY
        product_name ASC,
        unit_price ASC';
    $result = @mysql_query($sql,$connection) or die("Couldn't execute search query.");
    while ( $row = mysql_fetch_array($result) )
      {
        $product_id = $row['product_id'];
        $product_name = stripslashes($row['product_name']);
        $unit_price = $row['unit_price'];
        $pricing_unit = stripslashes($row['pricing_unit']);
        $ordering_unit = stripslashes($row['ordering_unit']);
        $prodtype_id = $row['prodtype_id'];
        $prodtype = $row['prodtype'];
        $extra_charge = $row['extra_charge'];
        $donotlist = $row['donotlist'];
        $detailed_notes = stripslashes($row['detailed_notes']);
        $margin = UNIVERSAL_MARGIN + $row['margin'];
        if (SHOW_ACTUAL_PRICE)
        {
          $price_multiplier = 1 / (1 - $margin);
        }
        else
        {
          $price_multiplier = 1;
        }

        if ( $prodtype_id != 5 )
          {
            $show_type = $prodtype;
          }
        else
          {
            $show_type = '';
          }
        if ( $extra_charge )
          {
            $extra = 'Extra charge: '.$extra_charge.'/'.$ordering_unit;
          }
        else
          {
            $extra = '';
          }
        $show_details = $detailed_notes;
        $display .= '
          <tr>
            <td valign="top">'.$font_product.'&nbsp;&nbsp;&nbsp;&nbsp;'.stripslashes($product_name)
              .($_GET["show"] == "product_id" ? ' (# '.$product_id.')' : '')
              .'</font></td>';
        if ($show_full_pricing_info)
        {
          $display .= '
            <td valign="top">'.$font_product.CURSYM.number_format($unit_price, 2).'</font></td>';
          $display .= '
            <td valign="top">'.(SHOW_ACTUAL_PRICE ? '<font face="arial" size="+1">'
                  : '<font face="arial" size="+1" color="grey">').number_format($margin * 100, 2).'%</font></td>';
        }
        $display .= '
            <td valign="top">'.$font_product.CURSYM.number_format($unit_price * $price_multiplier, 2).' / '.$pricing_unit.'</font></td>
          </tr>';
      }
  }
$display .= '</table>';
?>
<html>
<head>
<title><?php echo ucfirst (SITE_NAME); ?> Price List</title>
</head>
<body bgcolor="#FFFFFF">
  <div style="width:800px;">
  <!-- CONTENT BEGINS HERE -->
<hr color="#000000" noshade size=2 width="100%">
<font size="+3"><?php echo ucfirst (SITE_NAME_SHORT); ?> Price List - <?php echo $delivery_date;?></font>
<hr color="#000000" noshade size=2 width="100%">

<p>This report is a price list of all the items that were offered for sale in this collection.
  Please use it as a reference when you are repricing or selling food items.</p>

<p>Note: the scales do not accept decimal points when you are inputting unit prices (always in kilos).
  So, simply tap in the price per kilo without the decimal point.
  E.g. carrots @ <?php echo CURSYM;?>2.31/kilo will be tapped into the scale's keyboard as 231 and the price for the
  weighed item will appear in the right window 'Price to Pay'.</p>

<hr color="#000000" noshade size=2 width="100%">
<?php echo $display;?>
  <!-- CONTENT ENDS HERE -->
  </div>
</body>
</html>
