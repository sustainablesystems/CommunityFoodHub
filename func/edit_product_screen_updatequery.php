<?php

$is_update = ($where == '' && $submit_action == 'Update Product');
$is_add = ($where == 'Save as a New Product' || $submit_action == 'Add Product');

if( $is_update || $is_add )
  {
    // Get and check the new product information
    $product_name = mysql_real_escape_string ($product_name);
    $pricing_unit = mysql_real_escape_string ($pricing_unit);
    $ordering_unit = mysql_real_escape_string ($ordering_unit);
    $detailed_notes = mysql_real_escape_string ($detailed_notes);
    $unit_price     = preg_replace("/[^0-9\.\-]/","",$unit_price);
    $overall_margin = preg_replace("/[^0-9\.\-]/","",$overall_margin);
    $extra_charge   = preg_replace("/[^0-9\.\-]/","",$extra_charge);
    $minimum_weight = preg_replace("/[^0-9\.\/]/","",$minimum_weight);
    $maximum_weight = preg_replace("/[^0-9\.\/]/","",$maximum_weight);
    $inventory      = preg_replace("/[^0-9]/","",$inventory);
    if ( ! $product_name )
      {
        $message2 .= '<b><font color="#3333FF">You must enter a product name to continue.</font></b><br><br>';
        $alert2 = 1;
        $update = 'no';
      }
    if ( ! $prodtype_id )
      {
        $message2 .= '<b><font color="#3333FF">Please select a product type.</font></b><br><br>';
        $alert6 = "1";
        $update = 'no';
      }
    if ( !$subcategory_id )
      {
        $message2 .= '<b><font color="#3333FF">Please choose a subcategory.</font></b><br><br>';
        $alert4 = 1;
        $update = 'no';
      }
    if ( ! $unit_price )
      {
        $message2 .= '<b><font color="#3333FF">Please enter a unit price.</font></b><br><br>';
        $alert5 = 1;
        $update = 'no';
      }

    // If no product margin, zero it.
    if (!$overall_margin)
      {
        $overall_margin = 0.0;
      }

    //echo "overall margin is ".$overall_margin;
    if (($overall_margin >= 100.0) || ($overall_margin <= -100.0))
      {
        $message2 .= '<b><font color="#3333FF">Please enter a margin less than 100%.</font></b><br><br>';
        $alert5 = 1;
        $update = 'no';
      }
    else
      {
        $product_margin = ($overall_margin / 100.0) - (SHOW_ACTUAL_PRICE ? UNIVERSAL_MARGIN : 0);
      }
      
    if ( ! $pricing_unit )
      {
        $message2 .= '<b><font color="#3333FF">Please enter a pricing unit.</font></b><br><br>';
        $alert5a = 1;
        $update = 'no';
      }
    else if ( ! $ordering_unit )
      {
        $message2 .= '<b><font color="#3333FF">Please enter an ordering unit, often the same as the pricing unit.</font></b><br><br>';
        $alert5b = 1;
        $update = 'no';
      }
    else // Check for inconsistencies between pricing and ordering units
      {
        $pricing_unit_contains_kilos = strstr($pricing_unit, 'kilo');
        $ordering_unit_contains_kilos = strstr($ordering_unit, 'kilo');
        if ( ($pricing_unit_contains_kilos && !$ordering_unit_contains_kilos)
          || ($ordering_unit_contains_kilos && !$pricing_unit_contains_kilos))
          {
            $message2 .= '<b><font color="#3333FF">Pricing unit ("'.$pricing_unit
              .'") and ordering unit ("'.$ordering_unit.'") are inconsistent.</font></b><br><br>';
            $alert5a = 1;
            $alert5b = 1;
            $update = 'no';
          }
      }

    if ( $random_weight && ( ! $minimum_weight || ! $maximum_weight) )
      {
        $message2 .= '<b><font color="#3333FF">You have selected Yes for random weight product. If this is a random weight product you need to enter an approximate minimum and maximum weight. If, for example, a package is always approximately one pound, enter 1 in both the min. and max. fields and this will be reflected.</font></b><br><br>';
        $alert8 = 1;
        $update = 'no';
      }
    if ( $meat_weight_type && ! $random_weight )
      {
        $message2 .= '<b><font color="#3333FF">Meat weight type is only valid for random weight items.</font></b><br><br>';
        $alert12 = 1;
        $alert8 = 1;
        $update = 'no';
      }
    if ( ! $meat_weight_type && ! $random_weight )
      {
        $minimum_weight = '';
        $maximum_weight = '';
      }
    // Origin and brand (optional, so check they exist)
    if ( $country || $uk_county )
      {
        $origin_id = insert_origin($country, $uk_county);
      }
    if ( $brand_name || $new_brand_name )
      {
        $brand_id = insert_brand($new_brand_name ? $new_brand_name : $brand_name);
      }

    if ( $is_update )
      {
        $action = 'edit';

        if ( $update != 'no' )
        {
          if ( $new == 1 )
            {
              $changed = 0;
            }
          else
            {
              $changed = 1;
            }

          $sqlu = '
            UPDATE
              '.TABLE_PRODUCT_PREP.'
            SET
              changed = "'.$changed.'",
              product_name = "'.$product_name.'",
              subcategory_id = "'.$subcategory_id.'",
              inventory_on = "'.$inventory_on.'",
              inventory = "'.$inventory.'",
              unit_price = "'.$unit_price.'",
              margin = "'.$product_margin.'",
              pricing_unit = "'.$pricing_unit.'",
              ordering_unit = "'.$ordering_unit.'",
              meat_weight_type = "'.$meat_weight_type.'",
              prodtype_id = "'.$prodtype_id.'",
              extra_charge = "'.$extra_charge.'",
              random_weight = "'.$random_weight.'",
              minimum_weight = "'.$minimum_weight.'",
              maximum_weight = "'.$maximum_weight.'",
              donotlist = "'.$donotlist.'",
              detailed_notes = "'.$detailed_notes.'",
              storage_id = "'.$storage_id.'",
              origin_id = "'.$origin_id.'",
              brand_id = "'.$brand_id.'"
            WHERE
              producer_id = "'.$producer_id.'"
              AND product_id = "'.$product_id.'"';
          $result = @mysql_query($sqlu,$connection) or die(mysql_error());

          if (($unit_price != $unit_price_old) || ($ordering_unit != $ordering_unit_old))
          {
            /* Check if this product is part of any compound (box) products.
            If it is, then the compound product price will also need adjusting.
            Unit price, ordering unit, or both may have changed. */
            $unit_price_diff = round(get_price($ordering_unit, $unit_price), 2)
                             - round(get_price($ordering_unit_old, $unit_price_old), 2);

            $query = '
              SELECT
                '.TABLE_PRODUCT_PREP.'.product_id,
                '.TABLE_PRODUCT_PREP.'.unit_price,
                '.TABLE_COMPOUND_PRODUCT_PREP.'.constituent_quantity
              FROM
                '.TABLE_PRODUCT_PREP.'
              LEFT JOIN
                '.TABLE_COMPOUND_PRODUCT_PREP.'
              ON
                '.TABLE_PRODUCT_PREP.'.product_id = '.TABLE_COMPOUND_PRODUCT_PREP.'.compound_product_id
              WHERE
                '.TABLE_COMPOUND_PRODUCT_PREP.'.constituent_product_id = '.$product_id;
            $result_set = @mysql_query($query,$connection) or die(mysql_error());

            while ( $row = mysql_fetch_array($result_set) )
            {
              $compound_product_id = $row['product_id'];
              $constituent_quantity = $row['constituent_quantity'];
              $compound_unit_price = $row['unit_price'] + ($constituent_quantity * $unit_price_diff);

              $sqlu = '
                UPDATE
                  '.TABLE_PRODUCT_PREP.'
                SET
                  unit_price = "'.$compound_unit_price.'"
                WHERE
                  product_id = "'.$compound_product_id.'"';
              $result = @mysql_query($sqlu,$connection) or die(mysql_error());
            }
          }

          $query = '
            SELECT
              product_id
            FROM
              '.TABLE_PRODUCT.'
            WHERE
              product_id = '.$product_id;
          $sql = mysql_query($query);
          if ( mysql_num_rows($sql) > 0 )
            {
              $sqlu2 = '
                UPDATE
                  '.TABLE_PRODUCT.'
                SET
                  storage_id = '.$storage_id.',
                  inventory_on = '.$inventory_on.',
                  inventory = "'.$inventory.'"
                WHERE
                  producer_id = "'.$producer_id.'"
                  AND product_id = '.$product_id;
              $resultu2 = @mysql_query($sqlu2,$connection) or die(mysql_error());
            }

          // Simplest to delete all subproducts, then insert the current ones
          $sql_del_subprods = 'DELETE FROM '
              .TABLE_COMPOUND_PRODUCT_PREP.'
              WHERE compound_product_id = "'.$product_id.'"';
          $res_del_subprods = @mysql_query($sql_del_subprods,$connection) or die(mysql_error());
          $res_ins_subprods = insert_subproducts(
              $product_id, $subprod_quantities, $subprod_ids);

          header ("refresh: 2; url='edit_product_list.php?producer_id=$producer_id&a={$_REQUEST['a']}#$product_id'");
          echo '<div style="width:40%;margin-left:auto;margin-right:auto;font-size:3em;padding:2em;text-align:center;color:fff;background-color:#008;">Product #'.$product_id.' has been updated.</div>';
          exit (0);

        } // if data ok
        
      } // if updating existing product

    else if ( $is_add && $update != 'no')
      {
        $sqlu = '
          INSERT INTO
            '.TABLE_PRODUCT_PREP.'
            (
              producer_id,
              subcategory_id,
              inventory_on,
              inventory,
              new,
              product_name,
              unit_price,
              margin,
              pricing_unit,
              ordering_unit,
              prodtype_id,
              extra_charge,
              random_weight,
              minimum_weight,
              maximum_weight,
              meat_weight_type,
              donotlist,
              detailed_notes,
              storage_id,
              origin_id,
              brand_id
            )
          VALUES
            (
              "'.$producer_id.'",
              "'.$subcategory_id.'",
              "'.$inventory_on.'",
              "'.$inventory.'",
              "1",
              "'.$product_name.'",
              "'.$unit_price.'",
              "'.$product_margin.'",
              "'.$pricing_unit.'",
              "'.$ordering_unit.'",
              "'.$prodtype_id.'",
              "'.$extra_charge.'",
              "'.$random_weight.'",
              "'.$minimum_weight.'",
              "'.$maximum_weight.'",
              "'.$meat_weight_type.'",
              "'.$donotlist.'",
              "'.$detailed_notes.'",
              "'.$storage_id.'",
              "'.$origin_id.'",
              "'.$brand_id.'"
            )';

        $result3 = @mysql_query($sqlu,$connection) or die(mysql_error());
        $new_product_id = mysql_insert_id ();

        // Add subproducts to compound products table
        $res_ins_subprods = insert_subproducts(
            $new_product_id, $subprod_quantities, $subprod_ids);

        header("refresh: 2; url='edit_product_list.php?producer_id=$producer_id&a={$_REQUEST['a']}#$new_product_id'");
        echo '<div style="width:40%;margin-left:auto;margin-right:auto;font-size:3em;padding:2em;text-align:center;color:fff;background-color:#080;">New product #'.$new_product_id.' has been created.</div>';
        exit (0);

      } // if data ok and adding new product
      
  } // if updating or adding product
  
if($submit_action == 'Cancel')
  {
    header('refresh: 2; url="edit_product_list.php?producer_id='.$producer_id.'&a='.$_REQUEST['a'].'#'.$product_id.'"');
    echo '<div style="width:40%;margin-left:auto;margin-right:auto;font-size:3em;padding:2em;text-align:center;color:fff;background-color:#800;">Editing<br>was<br>CANCELLED.</div>';
    exit (0);
  }

// Add subproducts to compound products table
function insert_subproducts( $product_id, $subprod_quantities, $subprod_ids )
{
    global $connection;
    
    $has_subprods = false;
    
    $sql_ins_subprods = 'INSERT INTO '
      .TABLE_COMPOUND_PRODUCT_PREP.' (
        compound_product_id,
        constituent_product_id,
        constituent_quantity
      ) VALUES';

    if ($subprod_quantities != null)
    {
      $subprod_index = 0;

      foreach ($subprod_quantities as $prod_quantity)
      {
          // Ignore invalid input, and round any fractions
          $prod_quantity = (int)( (float)$prod_quantity + 0.5 );

          if ($prod_quantity > 0)
          {
              if ($has_subprods)
              {
                  $sql_ins_subprods .= ',';
              }

              $sql_ins_subprods .= ' (
                      "'.$product_id.'",
                      "'.$subprod_ids[$subprod_index].'",
                      "'.$prod_quantity.'"
                  )';

              $has_subprods = true;
          }
          $subprod_index++;
      }
    }

    // If we found some subproducts, this is a compound product,
    // so add the subproducts to the compound products table
    if ( $has_subprods )
    {
        $res_sub_prods = @mysql_query($sql_ins_subprods,$connection) or die(mysql_error());
    }
    else
    {
        $res_sub_prods = true;
    }

    if ($res_sub_prods)
    {
        // Add an update to flag the compound product in the product table
        // N.B. Better to do this with a TRIGGER in theory, but can't rely on
        // having the SUPER (in MySQL < 5.1.6) or TRIGGER (MySQL >= 5.1.6) priv
        $sql_upd_compound = "
          UPDATE ".TABLE_PRODUCT_PREP."
          SET is_compound = '".($has_subprods ? 1 : 0)."'
          WHERE product_id = '".$product_id."'";

        $res_sub_prods = @mysql_query($sql_upd_compound,$connection)
                or die(mysql_error());
    }

    return $res_sub_prods;
}


// Add country and county to origin table
function insert_origin($country, $uk_county)
{
    global $connection;
    $new_origin_id = 0;

    $sql_check_origin = '
        SELECT origin_id
        FROM '.TABLE_ORIGIN.'
        WHERE country = "'.$country.'" AND uk_county = "'.$uk_county.'"';
    
    $res_check_origin = @mysql_query($sql_check_origin,$connection) or die(mysql_error());

    // If that origin already exists, get the existing id
    $row = mysql_fetch_array($res_check_origin);
    if ($row)
    {
        $new_origin_id = $row['origin_id'];
    }
    // Otherwise insert the origin and get the new id
    else
    {
        $sql_ins_origin = '
            INSERT INTO '.TABLE_ORIGIN.'
                ( country, uk_county )
            VALUES
                ("'.$country.'", "'.$uk_county.'")';

        $res_ins_origin = @mysql_query($sql_ins_origin,$connection) or die(mysql_error());
        $new_origin_id = mysql_insert_id();
    }
    
    return $new_origin_id;
}


// Add brand name to brand table
function insert_brand($brand_name)
{
    global $connection;
    $new_brand_id = 0;

    $sql_check_brand = '
        SELECT brand_id
        FROM '.TABLE_BRAND.'
        WHERE brand_name = "'.$brand_name.'"';

    $res_check_brand = @mysql_query($sql_check_brand,$connection) or die(mysql_error());

    // If that brand already exists, get the existing id
    $row = mysql_fetch_array($res_check_brand);
    if ($row)
    {
        $new_brand_id = $row['brand_id'];
    }
    // Otherwise insert the brand and get the new id
    else
    {
        $sql_ins_brand = '
            INSERT INTO '.TABLE_BRAND.'
                ( brand_name )
            VALUES
                ("'.$brand_name.'")';

        $res_ins_brand = @mysql_query($sql_ins_brand,$connection) or die(mysql_error());
        $new_brand_id = mysql_insert_id();
    }

    return $new_brand_id;
}
?>