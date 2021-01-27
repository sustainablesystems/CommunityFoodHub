<?php

function subcategory_name_get($subcategory_id)
{
    global $connection;

    $sql = '
      SELECT
        subcategory_name
      FROM
        '.TABLE_SUBCATEGORY.'
      WHERE
        subcategory_id = "'.$subcategory_id.'"';
    $rs = @mysql_query($sql,$connection) or die("Couldn't execute subcategory query.");
    while ( $row = mysql_fetch_array($rs) )
    {
        $subcategory_name = $row['subcategory_name'];
    }

    return $subcategory_name;
}


function box_name_get($is_prep, $box_id)
{
    global $connection;

    $products_table_type = ($is_prep ? TABLE_PRODUCT_PREP : TABLE_PRODUCT);
    $sql = '
      SELECT
        product_name
      FROM
        '.$products_table_type.'
      WHERE
        product_id = '.$box_id;
    $rs = @mysql_query($sql,$connection) or die("Couldn't execute product query.");
    while ( $row = mysql_fetch_array($rs) )
    {
        $box_name = $row['product_name'];
    }

    return $box_name;
}


function is_producer_supplier($producer_id)
{
    global $connection;

    $sql = '
      SELECT
        is_supplier
      FROM
        '.TABLE_PRODUCER.'
      WHERE
        producer_id = "'.$producer_id.'"';
    $rs = @mysql_query($sql,$connection) or die("Couldn't execute producer query.");
    while ( $row = mysql_fetch_array($rs) )
    {
        $is_supplier = $row['is_supplier'];
    }

    return $is_supplier;
}


// TODO: Can we rationalise these args?  e.g. table_type ~= is_logged_in
// There are too many of them!  Also, document them...
function products_by_producer_table($subcategory_id, $is_institution,
        $is_logged_in, $subtotal_curr, $producers_are_sublist, $table_type,
        $is_prep, $subproduct_id, $is_compound, $product_id_printed, $message, $producer_id_filter)
{
    include_once (FUNC_FILE_PATH.'compound_products.php');
    include_once (FUNC_FILE_PATH.'supplier_products.php');
    
    global $connection;    

    $products_table_type =
      ($is_prep ? TABLE_PRODUCT_PREP : TABLE_PRODUCT);
    $compound_products_table_type =
      ($is_prep ? TABLE_COMPOUND_PRODUCT_PREP : TABLE_COMPOUND_PRODUCT);

    // Set up the "donotlist" field condition based on whether the member is an "institution" or not
    // Only institutions are allowed to see donotlist=3 (wholesale products)
    if ( $is_institution && $is_logged_in )
    {
        $donotlist_condition = 'AND ('.$products_table_type.'.donotlist = "0"
            OR '.$products_table_type.'.donotlist = "3")';
    }
    else
    {
        $donotlist_condition = 'AND '.$products_table_type.'.donotlist = "0"';
    }

    if ( $subproduct_id )
    {
        $is_subproduct = true;
        $product_condition = $products_table_type.'.product_id = '.$subproduct_id;
    }
    else
    {
        $is_subproduct = false;
        $product_condition = $products_table_type.'.subcategory_id = '.$subcategory_id;
    }

    if ($producer_id_filter != null)
    {
      $producer_condition = 'AND '.$products_table_type.'.producer_id = "'.$producer_id_filter.'"';
    }
    else
    {
      $producer_condition = 'AND '.$products_table_type.'.producer_id = '.TABLE_PRODUCER.'.producer_id';
    }

    $sqlp = '
        SELECT
            '.TABLE_PRODUCER.'.producer_id,
            '.TABLE_PRODUCER.'.member_id,
            '.TABLE_PRODUCER.'.is_supplier,
            '.TABLE_MEMBER.'.member_id,
            '.TABLE_PRODUCER.'.donotlist_producer,
            '.TABLE_MEMBER.'.business_name,
            '.TABLE_MEMBER.'.first_name,
            '.TABLE_MEMBER.'.last_name
        FROM
            '.$products_table_type.',
            '.TABLE_PRODUCER.',
            '.TABLE_MEMBER.'
        WHERE
            '.$product_condition.'
            '.$producer_condition.'
            AND '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
            '.$donotlist_condition.'
            AND '.TABLE_PRODUCER.'.pending = 0
            AND '.TABLE_PRODUCER.'.donotlist_producer = 0
        GROUP BY
            '.$products_table_type.'.producer_id
        ORDER BY
            '.TABLE_MEMBER.'.business_name';
    
    $resultp = @mysql_query($sqlp,$connection)
            or die("Couldn't execute search query 2.");
    while ( $row = mysql_fetch_array($resultp) )
    {
        $is_supplier = $row['is_supplier'];
        if ($producer_id_filter == null)
        {         
          $producer_id = $row['producer_id'];
        }
        else  // Filtering on producer ID
        {
          $producer_id = $producer_id_filter;
        }
        $business_name = stripslashes($row['business_name']);
        $first_name = stripslashes($row['first_name']);
        $last_name = stripslashes($row['last_name']);

        if (!$business_name)
        {
            $business_name = "$first_name $last_name";
        }

        if ($producers_are_sublist)
        {
            $heading_size = "h3";
        }
        else
        {
            $heading_size = "h2";
        }
        $display_type = $table_type;
        include(FUNC_FILE_PATH.'display_product_table_start.php');

        if ($subproduct_id)
        {
            $sql = '
              SELECT
                *
              FROM
                (SELECT * FROM
                    '.$products_table_type.'
                INNER JOIN
                    (SELECT constituent_product_id, constituent_quantity
                    FROM '.$compound_products_table_type.'
                    WHERE compound_product_id = '.$subproduct_id.')
                    AS products_in_box
                ON
                    '.$products_table_type.'.product_id = products_in_box.constituent_product_id)
                AS full_products_in_box,
                '.TABLE_PRODUCT_TYPES.'
              WHERE
                full_products_in_box.producer_id = "'.$producer_id.'"
                AND full_products_in_box.prodtype_id = '.TABLE_PRODUCT_TYPES.'.prodtype_id
                AND full_products_in_box.donotlist = 0
              ORDER BY
                product_name ASC,
                unit_price ASC';
        }
        else
        {
            $sql = '
                SELECT
                    *
                FROM
                    '.$products_table_type.',
                    '.TABLE_PRODUCT_TYPES.'
                WHERE
                    '.$products_table_type.'.subcategory_id = "'.$subcategory_id.'"
                    AND '.$products_table_type.'.producer_id = "'.$producer_id.'"
                    AND '.$products_table_type.'.prodtype_id = '.TABLE_PRODUCT_TYPES.'.prodtype_id
                    AND '.$products_table_type.'.donotlist = "0"
                ORDER BY
                    product_name ASC,
                    unit_price ASC';
        }

        $result = @mysql_query($sql,$connection)
                or die("Couldn't execute search query.");
        while ($row = mysql_fetch_array($result))
        {
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
            if ( $is_logged_in )
            {
                $inventory_on = $row['inventory_on'];
                $inventory = $row['inventory'];
            }            
            $origin_id = $row['origin_id'];
            $brand_id = $row['brand_id'];
            // Now being passed to this function as a parameter.
            // It is assumed that each subcategory contains EITHER compound
            // products OR singular ones - there won't be a mixture.
            if ($is_compound)
            {
                $subprod_names = subprod_names_get(
                        $products_table_type, 
                        $compound_products_table_type, $product_id);
            }
            if ($is_supplier)
            {
                $origin = prod_origin_get($origin_id);
                $brand = prod_brand_get($brand_id);
            }
            if ($subproduct_id)
            {
                $quantity_in_box = $row['constituent_quantity'];
            }
                
            // TODO: the files below also needs refactoring into a function
            // For now include the two separate files
            if ( $is_logged_in )
            {
                // This file uses $basket_id
                // Unfortunately we can only access it as a global.
                // We need to declare it as global as we're accessing it
                // from a local context (this function)
                global $basket_id;
                include(FUNC_FILE_PATH."show_product_info_members.php");
            }
            else
            {
                include(FUNC_FILE_PATH."display_productinfo_public.php");
            }

        } // While products

        $display .= '</table>';

        // If we're logged in and we're adding to a shopping cart
        if ( $is_logged_in && $subtotal_curr > 0 )
        {
          $display .= '
              <div align="right">
                  <font size="+1" color="#770000">
                      <b>Your current subtotal: '.CURSYM.number_format($subtotal_curr, 2).'</b>
                  </font>
                  <a href="orders_current.php#checkout">
                    <img src="../grfx/checkoutnow.gif" alt="Proceed to CHECKOUT"
                      style="vertical-align:middle;border:10px solid transparent"/>
                  </a>
              </div>';

          $display .= '<div align="right" style="white-space:nowrap;border:0px solid transparent">';
        }
        else
        {
          $display .= '<div align="right" style="white-space:nowrap;border:5px solid transparent">';
        }

        $display .= '
                <font size="-1"><b>'
                  .($is_logged_in
                    ? '<a href="'.BASE_URL.PATH.'members/orders_current.php">View Basket</a> | '
                    : '').
                  '<a href="#">Return to Top of Page</a>
                </b></font>
            </div>';
    } // While producers

    return $display;

} // products_by_producer_table


?>
