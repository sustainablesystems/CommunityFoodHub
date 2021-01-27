<?php

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

function generate_producer_summary ($producer_id, $delivery_id, $detail_type, $use)
  {
    global $connection,
           $include_header,
           $include_footer;
    include_once ("general_functions.php");
    include_once (FUNC_FILE_PATH.'supplier_products.php');

    $query = '
      SELECT
        *
      FROM
        '.TABLE_DELDATE.'
      WHERE
        delivery_id = '.$delivery_id;
    $result= mysql_query("$query") or die("Error: " . mysql_error());
    while ($row = mysql_fetch_array($result))
      {
        $delivery_date = date ("M j, Y", strtotime ($row['delivery_date']));
      }



    ///                 OBTAIN PRODUCER BUSINESS AND NAME INFO.                  ///
    $sqlp = '
      SELECT
        '.TABLE_MEMBER.'.business_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.address_line1,
        '.TABLE_MEMBER.'.address_line2,
        '.TABLE_MEMBER.'.city,
        '.TABLE_MEMBER.'.state,
        '.TABLE_MEMBER.'.zip,
        '.TABLE_MEMBER.'.county,
        '.TABLE_MEMBER.'.email_address,
        '.TABLE_MEMBER.'.home_phone,
        '.TABLE_MEMBER.'.work_phone,
        '.TABLE_MEMBER.'.mobile_phone
      FROM
        '.TABLE_PRODUCER.',
        '.TABLE_MEMBER.'
      WHERE
        '.TABLE_PRODUCER.'.producer_id = "'.$producer_id.'"
        AND '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
      GROUP BY
        '.TABLE_PRODUCER.'.producer_id
      ORDER BY
        business_name ASC,
        last_name ASC';
    $resultp = @mysql_query($sqlp,$connection) or die("Couldn't execute query.");
    while ($row = mysql_fetch_array($resultp))
      {
        $a_business_name = stripslashes($row['business_name']);
        $a_first_name = stripslashes($row['first_name']);
        $a_last_name = stripslashes($row['last_name']);

        $a_address_line1 = stripslashes($row['address_line1']);
        $a_address_line2 = stripslashes($row['address_line2']);
        $a_city = stripslashes($row['city']);
        $a_state = stripslashes($row['state']);
        $a_zip = stripslashes($row['zip']);
        $a_county = stripslashes($row['county']);
        $a_email_address = stripslashes($row['email_address']);
        $a_home_phone = stripslashes($row['home_phone']);
        $a_work_phone = stripslashes($row['work_phone']);
        $a_mobile_phone = stripslashes($row['mobile_phone']);

        if (!$a_business_name)
          {
            $a_business_name = "$a_first_name $a_last_name";
          }
      }

    $sqlpr = '
      SELECT
        '.TABLE_BASKET.'.product_id,
        '.TABLE_BASKET.'.quantity,
        '.TABLE_BASKET.'.total_weight,
        '.TABLE_BASKET.'.out_of_stock,
        '.TABLE_BASKET.'.item_price,
        '.TABLE_PRODUCT.'.ordering_unit,
        '.TABLE_PRODUCT.'.pricing_unit,
        '.TABLE_PRODUCT.'.product_name,
        '.TABLE_PRODUCT.'.unit_price,
        '.TABLE_PRODUCT.'.margin,
        '.TABLE_PRODUCT.'.extra_charge,
        '.TABLE_PRODUCT.'.is_compound,
        '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
        '.TABLE_DELCODE.'.delcode,
        '.TABLE_DELCODE.'.delcode_id,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.business_name,
        '.TABLE_MEMBER.'.member_id,
        '.TABLE_BASKET_ALL.'.deltype,
        '.TABLE_CATEGORY.'.category_name,
        '.TABLE_CATEGORY.'.category_id,
        '.TABLE_ORIGIN.'.country,
        '.TABLE_ORIGIN.'.uk_county
      FROM
        '.TABLE_BASKET.'
      LEFT JOIN '.TABLE_PRODUCT.'
        ON '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
      LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.'
        ON '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id = '.TABLE_PRODUCT.'.storage_id
      LEFT JOIN '.TABLE_BASKET_ALL.'
        ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
      LEFT JOIN '.TABLE_MEMBER.'
        ON '.TABLE_BASKET_ALL.'.member_id = '.TABLE_MEMBER.'.member_id
      LEFT JOIN '.TABLE_DELCODE.'
        ON '.TABLE_BASKET_ALL.'.delcode_id = '.TABLE_DELCODE.'.delcode_id
      LEFT JOIN '.TABLE_SUBCATEGORY.'
        ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.TABLE_PRODUCT.'.subcategory_id
      LEFT JOIN '.TABLE_CATEGORY.'
        ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
      LEFT JOIN '.TABLE_ORIGIN.'
        ON '.TABLE_ORIGIN.'.origin_id = '.TABLE_PRODUCT.'.origin_id
      WHERE 
        '.TABLE_PRODUCT.'.producer_id = "'.$producer_id.'"
        AND '.TABLE_PRODUCT.'.hidefrominvoice = 0
        AND '.TABLE_BASKET_ALL.'.submitted = 1
        AND ('.TABLE_BASKET_ALL.'.delivery_id = '.$delivery_id.'
          OR '.TABLE_BASKET.'.future_delivery_id = '.$delivery_id.')
      ORDER BY
        '.TABLE_DELCODE.'.delcode,
        '.TABLE_CATEGORY.'.category_name,
        '.TABLE_PRODUCT.'.product_name,        
        '.TABLE_MEMBER.'.last_name, '.TABLE_MEMBER.'.business_name, '.TABLE_MEMBER.'.first_name';
    $resultpr = @mysql_query($sqlpr) or die(mysql_error());
    //$resultpr = @mysql_query($sqlpr) or die("Couldn't execute query 1");
    while ($row = mysql_fetch_array($resultpr))
      {
        $product_margin = $row['margin'];
        $product_id = $row['product_id'];
        $product_name = $row['product_name'];
        $delcode_id = $row['delcode_id'];
        $delcode = $row['delcode'];
        $member_id = $row['member_id'];
        $last_name = $row['last_name'];
        $first_name = $row['first_name'];
        $business_name = $row['business_name'];
        $deltype = $row['deltype'];
        $out_of_stock = $row['out_of_stock'];
        
        // Get the price from the basket - we may be viewing a historical
        // order summary.  Note: this is not good - we get the historical
        // price of the item from the HISTORICAL basket, but the basket doesn't have
        // the ordering unit, pricing unit, extra charge (whatever that is)
        // so we get those from the CURRENT product table.
        // TODO: this should be sorted - units may have changed over time,
        // and if extra charge is used and changes, historical order summaries will have the wrong prices.
        if (SHOW_ACTUAL_PRICE)
        {
          $unit_price = round ($row['item_price'] / (1 - (UNIVERSAL_MARGIN + $product_margin)), 2);
        }
        else
        {
          $unit_price = $row['item_price'];
        }
        
        $extra_charge = $row['extra_charge'];

        $quantity = $row['quantity'];
        $total_weight = $row['total_weight'];
        $ordering_unit = $row['ordering_unit'];
        $pricing_unit = $row['pricing_unit'];
        $storage_code = $row['storage_code'];
        $origin = format_origin($row['country'], $row['uk_county']);

        $category_id = $row['category_id'];
        $category_name = $row['category_name'];
        $is_compound = $row['is_compound'];

        // Figure out how to display the quantity
        $pricing_per_unit = '';

        if ($unit_price != 0)
          {
            $pricing_per_unit = CURSYM.number_format ($unit_price, 2).'/'.Inflect::singularize ($pricing_unit);
          }
        if ($unit_price != 0 && $extra_charge != 0)
          {
            $pricing_per_unit .= ' + ';
          }
        if ($extra_charge != 0)
          {
            $pricing_per_unit .= CURSYM.number_format ($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
          }

        if ($out_of_stock == 1)
        {
            $show_quantity = $quantity;
            $show_unit = $ordering_unit.' <img src="'.PATH.'grfx/checkmark_wht.gif">';
            $pricing_per_unit = 'N/A'; // Clobber the value            
        }
        else
        {
            if ($total_weight)
            {
                $show_quantity = $total_weight;
                $show_unit = $pricing_unit;
            }
            else
            {
                $show_quantity = $quantity;
                $show_unit = $ordering_unit;
            }
        }

        // If there's no last name, then use the business name
        if(!$last_name)
          {
            $show_mem = $business_name;
          }
        else
          {
            $show_mem = "$last_name, $first_name";
          }

        // Set up primary data structure
        $summary_qty[$delcode_id][$product_id][$member_id] = $show_quantity;
        // Configure deltype to only show when order is a delivery
        if ($deltype != 'P')
          {
            $summary_deltype[$member_id] = $deltype.'-'; // Will give something like D-BOISE-117
          }
        else
          {
            $summary_deltype[$member_id] = ''; // Will give something like BOISE-117
          }
        $summary_unit[$product_id] = $show_unit;
        $delcode_subtotal[$delcode_id][$product_id] += $show_quantity;
        $product_subtotal[$product_id] += $show_quantity;
        
        include_once ('general_functions.php');

        // We now get HISTORICAL price from basket, which
        // is the ordering unit price, so no need to convert it
        //$ordering_unit_price = get_price($ordering_unit, $unit_price);
        $ordering_unit_price = $unit_price;

        $products_price = $show_quantity * ($ordering_unit_price + $extra_charge);

        $delcode_subtotal_price[$delcode_id][$product_id] += $products_price;
        $product_total_price[$product_id] += $products_price;
        $delcode_total_price[$delcode_id] += $products_price;
        $grand_total_price += $products_price;
        // Remember relationships between product and its delivery location and category
        $category_product[$category_id][$product_id] = $product_name;
        $delcode_category_product[$delcode_id][$category_id][$product_id] = $product_name;

        // Set up trivial data relationships
        $delcode_id_2_delcode[$delcode_id] = $delcode;
        $product_id_2_product_name[$product_id] = $product_name;
        $product_id_2_storage_code[$product_id] = $storage_code;
        $product_id_2_origin[$product_id] = $origin;
        $product_id_2_pricing_per_unit[$product_id] = $pricing_per_unit;
        $member_id_2_show_mem[$member_id] = $show_mem;
        $category_id_2_category_name[$category_id] = $category_name;
        $product_id_2_is_compound[$product_id] = $is_compound;

        // For compound products, get the product info and quantity
        // for the subproducts too.
        // TODO: This isn't very efficient - lots of SELECTs
        // Best to do this after the main product SELECT - and select
        // all subproducts of ordered compound products in one go?
        if ($is_compound)
          {
              $compound_prod_id = $product_id;
              $compound_prod_quant = $show_quantity;
              $compound_prod_delcode_id = $delcode_id;
              
              $sql = '
                SELECT
                    product_id, product_name, unit_price, margin,
                    ordering_unit, pricing_unit, constituent_quantity,
                    category_name, '.TABLE_CATEGORY.'.category_id,
                    '.TABLE_ORIGIN.'.country, '.TABLE_ORIGIN.'.uk_county
                FROM
                    (SELECT * FROM '.TABLE_PRODUCT.' WHERE
                    producer_id = "'.$producer_id.'" AND is_compound = 0)
                    AS producer_prods
                INNER JOIN
                    (SELECT * FROM '.TABLE_COMPOUND_PRODUCT.' WHERE
                    compound_product_id = "'.$compound_prod_id.'")
                    AS edit_prod_subprods
                ON
                    producer_prods.product_id =
                    edit_prod_subprods.constituent_product_id
                LEFT JOIN '.TABLE_SUBCATEGORY.'
                    ON '.TABLE_SUBCATEGORY.'.subcategory_id = producer_prods.subcategory_id
                LEFT JOIN '.TABLE_CATEGORY.'
                    ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
                LEFT JOIN '.TABLE_ORIGIN.'
                    ON '.TABLE_ORIGIN.'.origin_id = producer_prods.origin_id
                ORDER BY
                    product_name ASC';
            $result = @mysql_query($sql,$connection) or die(mysql_error());

            while ( $row = mysql_fetch_array($result) )
            {
                $sub_prod_id = $row['product_id'];
                $sub_prod_name = stripslashes($row['product_name']);
                $sub_prod_margin = $row['margin'];

                if (SHOW_ACTUAL_PRICE)
                {
                  $sub_prod_price_per_unit = round($row['unit_price'] / (1 - (UNIVERSAL_MARGIN + $sub_prod_margin)), 2);
                }
                else
                {
                  $sub_prod_price_per_unit = $row['unit_price'];
                }

                $sub_prod_ordering_unit = $row['ordering_unit'];
                $sub_prod_pricing_unit = $row['pricing_unit'];
                $sub_prod_quant_in_compound = $row['constituent_quantity'];
                $sub_prod_quant = $sub_prod_quant_in_compound * $compound_prod_quant;
                //$sub_prod_subtotal = number_format($prod_price_per_unit * $sub_prod_quant, 2);
                $sub_prod_category_id = $row['category_id'];
                $sub_prod_category_name = $row['category_name'];

                // Always use ordering unit for subproducts
                $summary_unit[$sub_prod_id] = $sub_prod_ordering_unit;

                // This will create new rows if the subprod hasn't also been
                // ordered individually; otherwise it will add the quantity
                // to the existing one
                $delcode_subtotal_incl_subprods[$compound_prod_delcode_id][$sub_prod_id] += $sub_prod_quant;
                $product_subtotal_subprods[$sub_prod_id] += $sub_prod_quant;
                $product_id_2_origin[$sub_prod_id] = format_origin($row['country'], $row['uk_county']);

                // Remember relationships between subproduct and its
                // delivery location and category.  Delivery location is the
                // same as for the compound product, but the category will
                // be different (e.g. fruit/veg, rather than box).
                $category_product[$sub_prod_category_id][$sub_prod_id] = $sub_prod_name;
                $delcode_category_product[$compound_prod_delcode_id][$sub_prod_category_id][$sub_prod_id] = $sub_prod_name;

                // Set up trivial data relationships
                //$delcode_id_2_delcode[$delcode_id] = $delcode;
                $product_id_2_product_name[$sub_prod_id] = $sub_prod_name;

                // TODO: storage_code used for labels and "summary by customer"
                // Since summary by cust doesn't include subproducts, and FGU
                // doesn't need it anyway (AFAIK), we won't record it.
                //$product_id_2_storage_code[$product_id] = $storage_code;

                // Pricing per unit doesn't include extra_charge for subproducts
                // as it's assumed any extra charge will be included in the
                // compound product price.
                $product_id_2_pricing_per_unit[$sub_prod_id] = 
                    CURSYM.number_format ($sub_prod_price_per_unit, 2).
                    '/'.Inflect::singularize ($sub_prod_pricing_unit);
                $category_id_2_category_name[$sub_prod_category_id] = $sub_prod_category_name;
            }
          }
      }

    if ($a_address_line1 && $a_address_line2)
      {
        $a_address = "$a_address_line1<br>\n$a_address_line2";
      }
    else
      {
        $a_address = $a_address_line1.$a_address_line2;
      }

    $producer_header = '
      <table cellspacing="0" cellpadding="0" border="0" width="800">
        <tr>
          <td><h3>'.$a_business_name.'</h3></td>
          <td><font size="+1"><strong>Order #'.$delivery_id.' - '.$delivery_date.'</strong></font></td>
        </tr>
        <tr>
          <td width="15%"> Email address: </td>
          <td width="35%">'.$a_email_address.'</td>
        </tr>
        <tr>
          <td width="15%">Contact phone: </td>
          <td width="35%">'.$a_mobile_phone.'</td>
        </tr>
      </table><br>';

    if (is_array ($summary_qty))
      {
        $include_header = true;
        $include_footer = true;
        if ($detail_type == 'customer')
          {
            $page_links = '
            <div align="center"><font face="arial" size="-1"><b>[ 
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=product">Wholesale Report</a> |
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=product_by_delivery_location">Wholesale Report by Collection Location</a> |
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=labels">Labels for this order</a> |
            <a href="configure_labels.php">Configure or select label format</a> ]</b></font></div>
            <h2>Customer Summary for '.$a_business_name.'</h2>
            ';
            $display_page .= '<table border="0" cellspacing="0" width="100%">';
            foreach (array_keys ($summary_qty) as $delcode_id)
              {
                $display_page .= '
                <tr><th colspan="4">&nbsp;</th></tr>
                <tr><th colspan="4" bgcolor="#444444"><font size="+1" color="#ffffff" align="center">'.$delcode_id_2_delcode[$delcode_id].' ('.$delcode_id.')</font></td></tr>
                <tr><th colspan="4">&nbsp;</th></tr>
                ';
                foreach (array_keys ($summary_qty[$delcode_id]) as $product_id)
                  {
                    $display_page .= '
                    <tr><td colspan="4"><br>'.$a_business_name.' &ndash; (#'.$product_id.') '.$product_id_2_product_name[$product_id].' ['.$product_id_2_storage_code[$product_id].'] &ndash; '.$product_id_2_pricing_per_unit[$product_id].'</td></tr>
                    ';
                    foreach (array_keys ($summary_qty[$delcode_id][$product_id]) as $member_id)
                      {
                        $quantity = $summary_qty[$delcode_id][$product_id][$member_id];
                        $display_page .= '
                          <tr><td width="5%">&nbsp;</td>
                          <td width="10%">#'.$member_id.'</td>
                          <td width="60%">'.$member_id_2_show_mem[$member_id].'</td>
                          <td width="20%">('.$quantity.') - '.Inflect::pluralize_if ($quantity, $summary_unit[$product_id]).'<br></td></tr>';
                      }
                    // Delivery Code summary
                    $subtotal = $delcode_subtotal[$delcode_id][$product_id];
                    $total = $product_subtotal[$product_id];
                    // Product summary
                    $display_page .= '
                    <tr><td width="5%">&nbsp;</td>
                    <td width="70%" colspan="2" bgcolor="#dddddd">Product quantity ('.$delcode_id_2_delcode[$delcode_id].'): </td>
                    <td width="20%" bgcolor="#dddddd">('.$subtotal.' of '.$total.') - '.Inflect::pluralize_if ($total, $summary_unit[$product_id]).'</td></tr>
                    ';
                  }
              }
            //       $display_page .= '<hr width="50%" style="text-align:left;margin:3em 0em 3em;">';
            $display_page .= '</table>';

            if ($use == 'batch')
              {
                $display_page = $producer_header.$display_page;
              }
            else
              {
                $display_page = '</font><div style="font-size:0.9em;">'.$page_links.$producer_header.$display_page."</div>";
              }
          }

        elseif ($detail_type == 'product' || $detail_type == '')
          {
            $page_links = '
            <div align="center"><font face="arial" size="-1"><b>[
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=product_by_delivery_location">Wholesale Report by Collection Location</a> |
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=customer">Customer summary</a></a> |
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=labels">Labels for this order</a> |
            <a href="configure_labels.php">Configure or select label format</a> ]</b></font></div>
            <h2>Wholesale Report for '.$a_business_name.'</h2>
            ';

            // TODO: Do the following for each subcategory (products ordered alphabetically
            // within each subcategory as present).  Want "Organic veggies", "organic Fruit", etc.

            $display_page .= '<table border="1" cellspacing="0" width="100%">';
            $display_page .= '
                <tr><th colspan="7" bgcolor="#444444"><font size="+1" color="#ffffff" align="center">Product Totals (all delivery locations)</font></td></tr>
                ';
            
            foreach ( array_keys( $category_product ) as $category_id )
              {
                $category_name = $category_id_2_category_name[$category_id];

                $display_page .= '
                <tr><th colspan="7" bgcolor="#aaaaaa" align="left"><font color="#ffffff">'.
                $category_name.'</font></td></tr>';

                $display_product_lines = '';
                $is_compound_category = false;
                foreach ( array_keys( $category_product[$category_id] ) as $product_id)
                  {
                    // Get data for our columns
                    // TODO: Want to be able to get just the products for a delivery locations
                    // TODO: Want to calculate separate quantities for compound products/individual prods
                    $prod_quant_excl_subprods = $product_subtotal[$product_id];
                    $prod_quant_incl_subprods = $prod_quant_excl_subprods + $product_subtotal_subprods[$product_id];
                    
                    $prod_unit = $summary_unit[$product_id];
                    $prod_name = $product_id_2_product_name[$product_id];
                    $prod_id = $product_id;
                    $prod_price_per_unit = $product_id_2_pricing_per_unit[$product_id];
                    // Only show subtotals for individually ordered products (not subproducts)
                    $prod_subtotal = $product_total_price[$product_id] ?
                        number_format($product_total_price[$product_id] , 2) : "";

                    if ($is_compound_category == false)
                    {
                      // If one product is a compound one, assume this is a compound category
                      $is_compound_category = ($product_id_2_is_compound[$product_id] != 0);
                    }

                    // Only display total including subproducts for non-compound products
                    $display_product_lines .= '<tr><td>'.$prod_id.'</td><td>'.$prod_name.
                        $product_id_2_origin[$product_id].
                        '</td><td>'.$prod_unit.'</td><td>'.$prod_price_per_unit.
                        '</td><td align="right">'.$prod_quant_excl_subprods.'</td>
                        <td align="right"><font color="#ff0000"><b>'.
                            ($product_id_2_is_compound[$product_id] ?
                            '&nbsp;' : $prod_quant_incl_subprods ).
                        '</b></font></td>
                        <td align="right">'.($prod_subtotal ? CURSYM.$prod_subtotal : '&nbsp;').'</td></tr>';
                    
                    // Provide a breakdown for compound products
                    if ( $product_id_2_is_compound[$product_id] )
                      {
                        $display_product_lines .= generate_subproduct_summary(
                            $producer_id, $prod_id, $prod_quant_excl_subprods );
                      }
                  }

                // Column headings (but not for box - compound - products)
                $display_page .= '<tr align="left"><th>ID</th><th>Product name</th><th>Order unit</th>
                    <th>Price per unit</th><th align="right">Quantity</th><th align="right">'.
                    ($is_compound_category ? '&nbsp;'
                    : '<font color="#ff0000">Incl. boxes</font>').
                    '</th><th align="right">Subtotal</th></tr>';

                // Rows
                $display_page .= $display_product_lines;
              }
              // Display grand total
              $display_page .= '<tr><th colspan="7" align="right" bgcolor="#444444">
                  <font color="#ffffff" size="+1">Grand Total: '.CURSYM.number_format($grand_total_price, 2).'</font></th></tr>';
              $display_page .= '</table>';

            if ($use == 'batch') {
                $display_page = $producer_header.$display_page;
              }
            else {
                $display_page = '</font><div style="font-size:0.9em;">'.$page_links.$producer_header.$display_page.'</div>';
              }
          } // product

        elseif ($detail_type == 'product_by_delivery_location')
          {
            $page_links = '            
            <div align="center"><font face="arial" size="-1"><b>[
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=product">Wholesale Report</a> |
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=customer">Customer summary</a></a> |
            <a href="'.$_SERVER['PHP_SELF'].'?detail_type=labels">Labels for this order</a> |
            <a href="configure_labels.php">Configure or select label format</a> ]</b></font></div>
            <h2>Wholesale Report by Collection Location for '.$a_business_name.'</h2>
            ';
            $display_page .= '
            <table border="1" cellspacing="0" width="100%">
            ';

            foreach (array_keys ($delcode_category_product) as $delcode_id)
              {
                $display_page .= '
                <tr><th colspan="7" bgcolor="#444444"><font size="+1" color="#ffffff" align="center">'.$delcode_id_2_delcode[$delcode_id].' ('.$delcode_id.')</font></td></tr>
                ';
                
                foreach ( array_keys( $delcode_category_product[$delcode_id] ) as $category_id )
                  {
                    $category_name = $category_id_2_category_name[$category_id];
                    
                    $display_page .= '
                    <tr><th colspan="7" bgcolor="#aaaaaa" align="left"><font color="#ffffff">'.
                    $category_name.'</font></td></tr>';

                    $display_product_lines = '';
                    $is_compound_category = false;
                    foreach ( array_keys( $delcode_category_product[$delcode_id][$category_id] ) as $product_id)
                      {
                        $prod_quant_excl_subprods = $delcode_subtotal[$delcode_id][$product_id];
                        $prod_quant_incl_subprods = $prod_quant_excl_subprods +
                            $delcode_subtotal_incl_subprods[$delcode_id][$product_id];
                        
                        $prod_quant = $delcode_subtotal[$delcode_id][$product_id];
                        $prod_unit = $summary_unit[$product_id];
                        $prod_name = $product_id_2_product_name[$product_id];
                        $prod_id = $product_id;
                        $prod_price_per_unit = $product_id_2_pricing_per_unit[$product_id];
                        $prod_subtotal = $delcode_subtotal_price[$delcode_id][$product_id] ?
                            number_format($delcode_subtotal_price[$delcode_id][$product_id], 2) : "";

                        if ($is_compound_category == false)
                        {
                          // If one product is a compound one, assume this is a compound category
                          $is_compound_category = ($product_id_2_is_compound[$product_id] != 0);
                        }

                        // Only display total including subproducts for non-compound products
                        $display_product_lines .= '<tr><td>'.$prod_id.'</td><td>'.$prod_name.
                            $product_id_2_origin[$product_id].
                            '</td><td>'.$prod_unit.'</td><td>'.$prod_price_per_unit.
                            '</td><td align="right">'.$prod_quant_excl_subprods.'</td>
                            <td align="right"><font color="#ff0000"><b>'.
                            ( $product_id_2_is_compound[$product_id] ?
                            '&nbsp;' : $prod_quant_incl_subprods ).
                            '</b></font></td>
                            <td align="right">'.($prod_subtotal ? CURSYM.$prod_subtotal : '&nbsp;').'</td></tr>';

                        // Provide a breakdown for compound products
                        if ( $product_id_2_is_compound[$product_id] )
                          {
                            $display_product_lines .= generate_subproduct_summary(
                                $producer_id, $prod_id, $prod_quant_excl_subprods );
                          }
                      }

                    // Column headings (but not for box - compound - products)
                    $display_page .= '<tr align="left"><th>ID</th><th>Product name</th><th>Order unit</th>
                        <th>Price per unit</th><th align="right">Quantity</th><th align="right">'.
                        ($is_compound_category ? '&nbsp;'
                        : '<font color="#ff0000">Incl. boxes</font>').
                        '</th><th align="right">Subtotal</th></tr>';

                    // Rows
                    $display_page .= $display_product_lines;
                  }

                // Display delivery location total
                $display_page .= '
                    <tr><th colspan="7" align="right" bgcolor="#aaaaaa">
                        <font color="#ffffff" size="+1">Location Total: '.CURSYM.
                        number_format($delcode_total_price[$delcode_id], 2).'</font></th></tr>
                    <tr><th colspan="7">&nbsp;</th></tr>';
              }
            // Display grand total
            $display_page .= '
              <tr><th colspan="7" align="right" bgcolor="#444444">
              <font color="#ffffff" size="+1">Grand Total: '.CURSYM.
              number_format($grand_total_price, 2).'</font></th></tr>';
            $display_page .= '</table>';

            if ($use == 'batch')
              {
                $display_page = $producer_header.$display_page;
              }
            else
              {
                $display_page = '</font><div style="font-size:0.9em;">'.$page_links.$producer_header.$display_page."</div>";
              }
          } // product_by_delivery_location

        elseif ($detail_type == 'labels')
          {
            require_once ("../func/label_config.class.php");

            // Choose the labels that were selected from configure_labels.php
            $label_name = $_SESSION['label_select'];

            // Set up the label based on stored cookie label values
            $current_label = output_Label::cookieToLabel ($label_name);


            if ($label_name)
              {
                // If a printer has been chosen, then include label styles
                $label_sheet_styles .= '
                  .container {
                    overflow:hidden;
                    width:100%;
                    height:100%
                    }
                  '.$current_label->getLabelCSS();
                // Set up font scaling
                $font_scaling = $current_label->font_scaling;
                if (! $font_scaling) { $font_scaling = 1.0; };
                $font_scaling_link = ''; // Scaling is automatic, so not controls are given
              }
            else
              {
                // Otherwise include a simple spacer style between labels
                $label_sheet_styles .= '
                  .container {
                    margin-bottom: 3em;
                    }
                  a {
                    text-decoration: none;
                    color:#880088;
                    }
                  a:hover {
                    text-decoration: underline;
                    color:#0000ff;
                    }
                  ';
                // Set up font scaling
                $font_scaling = $_GET['font_scaling'];
                if (! $font_scaling) $font_scaling = 1.0;
                if ($font_scaling < 0.3) $font_scaling = 0.3;
                if ($font_scaling > 4.0) $font_scaling = 4.0;
                // Controls for scaling the label
                $font_scaling_link = 'A custom label-sheet is NOT selected.<br>
                  Click <a href="configure_labels.php">here</a> to configure custom labels (i.e. Avery labels)<br>
                  Change label size: 
                  [<a href="'.$_SERVER['PHP_SELF'].'?detail_type=labels&font_scaling='.($font_scaling - 0.1).'">Smaller</a>]
                  [<a href="'.$_SERVER['PHP_SELF'].'?detail_type=labels&font_scaling='.($font_scaling + 0.1).'">Larger</a>]
                  <br><br><br>';
              }

            // Include the header and styles for this particular application
            $label_sheet .= '
              <head>
              <style>
              .counter {
                float:left;
                font-size:'.number_format (3 * $font_scaling, 2).'em;
                font-weight:bold;
                }
              .delcode {
                font-size:'.number_format (1.2 * $font_scaling, 2).'em;
                font-weight:bold;
                }
              .customer {
                font-size:'.number_format (1.0 * $font_scaling, 2).'em;
                }
              .producer {
                font-size:'.number_format (1.0 * $font_scaling, 2).'em;
                }
              .product {
                font-size:'.number_format (0.9 * $font_scaling, 2).'em;
                font-style:italic;
                line-height:100%;
                }
              '.$label_sheet_styles;

            // Close the header and open the body
            $label_sheet .= '
              </style>
              </head>
              <body>'.$font_scaling_link;

            // Begin the label sheet content
            $label_sheet .= $current_label->beginLabelSheet();

            foreach (array_keys ($product_id_2_product_name) as $product_id)
              {
                foreach (array_keys ($summary_qty) as $delcode_id)
                  {
                    if (is_array ($summary_qty[$delcode_id][$product_id]))
                      {
                        foreach (array_keys ($summary_qty[$delcode_id][$product_id]) as $member_id)
                          {
                            $quantity = $summary_qty[$delcode_id][$product_id][$member_id];
                            $deltype = $summary_deltype[$member_id];
                            //$unit = $summary_unit[$product_id];
                            $label_sheet .= '
                              <div class="container">
                              <div class="delcode">'.$deltype.$delcode_id.'-'.$member_id.'['.$product_id_2_storage_code[$product_id].']</div>
                              <div class="customer">'.$member_id_2_show_mem[$member_id].'</div>
                              <div class="producer">'.$a_business_name.'</div>
                              <div class="product">('.$quantity.') - '.Inflect::pluralize_if ($quantity, $summary_unit[$product_id]).$product_id_2_product_name[$product_id].' (#'.$product_id.')</div>
                              </div>';
                            $label_sheet .= $current_label->advanceLabel();
                          }
                      }
                  }
              }
            $label_sheet .= $current_label->finishLabelSheet();
            // Finally, just before printing, clear the $_SESSION['label_select']
            // variable so the next use of this function will require choosing a
            // label type again.
            //    unset ($_SESSION['label_select']);
            $display_page .= $label_sheet;
          }
      }

    else
      {
        $include_header = true;
        $include_footer = true;
        $display_page .= "</font>";
        $display_page .= '<div style="font-size:0.9em;">';
        $display_page .= "<h2>No products to report</h2><br>\n";
        $display_page .= '</div>';
      }

    return $display_page;
  }

  // Subproducts subtable
  function generate_subproduct_summary( $producer_id, 
          $compound_prod_id, $compound_prod_quant )
  {
      global $connection;
      
      $sql = '
        SELECT
            product_id, product_name, unit_price, margin,
            ordering_unit, constituent_quantity,
            '.TABLE_ORIGIN.'.country,
            '.TABLE_ORIGIN.'.uk_county
        FROM
            (SELECT * FROM '.TABLE_PRODUCT.' WHERE
            producer_id = "'.$producer_id.'" AND is_compound = 0)
            AS producer_prods
        INNER JOIN
            (SELECT * FROM '.TABLE_COMPOUND_PRODUCT.' WHERE
            compound_product_id = "'.$compound_prod_id.'")
            AS edit_prod_subprods
        ON
            producer_prods.product_id =
            edit_prod_subprods.constituent_product_id
        LEFT JOIN '.TABLE_ORIGIN.'
          ON '.TABLE_ORIGIN.'.origin_id = producer_prods.origin_id
        ORDER BY
            product_name ASC';

    $result = @mysql_query($sql,$connection) or die(mysql_error());

    $display_subproducts = '<tr align="left"><th>&nbsp;</th>
        <th>&nbsp;&nbsp;&nbsp;&nbsp;Contents</th>
        <th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th>
        <th align="right">Total quant</th><th>&nbsp;</th></tr>';
    
    while ( $row = mysql_fetch_array($result) )
    {
        $prod_id = $row['product_id'];
        $prod_name = stripslashes($row['product_name']);
        $prod_margin = $row['margin'];

        if (SHOW_ACTUAL_PRICE)
        {
          $prod_price_per_unit = round($row['unit_price'] / (1 - (UNIVERSAL_MARGIN + $prod_margin)), 2);
        }
        else
        {
          $prod_price_per_unit = $row['unit_price'];
        }
        
        // When creating the box product, ordering units are used
        $prod_ordering_unit = $row['ordering_unit'];
        $sub_prod_quant_in_compound = $row['constituent_quantity'];
        $sub_prod_quant = $sub_prod_quant_in_compound * $compound_prod_quant;
        $sub_prod_subtotal = number_format($prod_price_per_unit * $sub_prod_quant, 2);

        $display_subproducts .= '<tr><td>'.$prod_id.'</td>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;'.$prod_name.
            format_origin($row['country'], $row['uk_county']).
            '</td><td>'.$prod_ordering_unit.'</td><td>&nbsp;</td>
            <td align="right">'.$sub_prod_quant_in_compound.
            '</td><td align="right">'.$sub_prod_quant.
            '</td><td>&nbsp;</td></tr>';
    }
    
    return $display_subproducts;
  }

  function format_origin($country, $uk_county)
  {
      if ( $country && $uk_county )
      {
          $origin = ' <i><font size="-2">'. $uk_county . ', ' .$country.'</font></i>';
      }
      else if ($country)
      {
          $origin = ' <i><font size="-2">'. $country.'</font></i>';
      }
      else if ($uk_county)
      {
          $origin = ' <i><font size="-2">'. $uk_county.'</font></i>';
      }
      else
      {
          $origin = '';
      }

      return $origin;
  }