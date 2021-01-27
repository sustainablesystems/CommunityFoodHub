<?php
$sql = '
  SELECT
    *
  FROM
    '.TABLE_PRODUCT_PREP.',
    '.TABLE_PRODUCT_TYPES.',
    '.TABLE_CATEGORY.',
    '.TABLE_SUBCATEGORY.'
  WHERE
    '.TABLE_PRODUCT_PREP.'.product_id = '.$product_id.'
    AND '.TABLE_PRODUCT_PREP.'.prodtype_id = '.TABLE_PRODUCT_TYPES.'.prodtype_id
    AND '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
    AND '.TABLE_SUBCATEGORY.'.subcategory_id = '.TABLE_PRODUCT_PREP.'.subcategory_id';
$result = @mysql_query($sql,$connection) or die(mysql_error());

$num = mysql_numrows($result);

while ( $row = mysql_fetch_array($result) )
  {
    $product_name = stripslashes($row['product_name']);
    $new = $row['new'];
    $category_id = $row['category_id'];
    $category_name = $row['category_name'];
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = $row['subcategory_name'];
    $inventory_on = $row['inventory_on'];
    $inventory = $row['inventory'];
    $unit_price = $row['unit_price'];
    // Store the old price and ordering unit so we can check if they changed when we update the product
    $unit_price_old = $row['unit_price'];
    $ordering_unit_old = $row['ordering_unit'];
    $overall_margin = ($row['margin'] + (SHOW_ACTUAL_PRICE ? UNIVERSAL_MARGIN : 0)) * 100.0;
    $pricing_unit = $row['pricing_unit'];
    $ordering_unit = $row['ordering_unit'];
    $prodtype_id = $row['prodtype_id'];
    $prodtype = $row['prodtype'];
    $meat_weight_type = $row['meat_weight_type'];
    $extra_charge = $row['extra_charge'];
    $future_delivery = $row['future_delivery'];
    $random_weight = $row['random_weight'];
    $maximum_weight = $row['maximum_weight'];
    $minimum_weight = $row['minimum_weight'];
    $donotlist = $row['donotlist'];
    $detailed_notes = stripslashes($row['detailed_notes']);
    $retail_staple = $row['retail_staple'];
    $storage_id = $row['storage_id'];
    $is_compound = $row['is_compound'];
    $origin_id = $row['origin_id'];
    $brand_id = $row['brand_id'];
  }

  // Get origin and brand information
  if ($origin_id > 0)
  {
    $sql_get_origin = '
        SELECT *
        FROM '.TABLE_ORIGIN.'
        WHERE '.TABLE_ORIGIN.'.origin_id = "'.$origin_id.'"';
    $res_get_origin = @mysql_query($sql_get_origin, $connection) or die(mysql_error());

    while ($row = mysql_fetch_array($res_get_origin))
    {
        $country = $row['country'];
        $uk_county = $row['uk_county'];
    }
  }
  if ($brand_id > 0)
  {
    $sql_get_brand = '
        SELECT *
        FROM '.TABLE_BRAND.'
        WHERE '.TABLE_BRAND.'.brand_id = "'.$brand_id.'"';
    $res_get_brand = @mysql_query($sql_get_brand, $connection) or die(mysql_error());

    while ($row = mysql_fetch_array($res_get_brand))
    {
        $brand_name = $row['brand_name'];
    }
  }
?>