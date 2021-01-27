<?php
/* 
 * Generate table rows of subproducts - products that can form the contents
 * of a compound product such as a veg box or hamper.
 */
include_once ('config_foodcoop.php');
include_once ('general_functions.php');

function add_subproducts_list( $producer_id )
{
    return edit_subproducts_list( $producer_id, -1 );

} // add_subproducts_list

function edit_subproducts_list( $producer_id, $product_id )
{
    global $connection;

    // Get a list of products for this producer, and place any subproducts
    // that are already associated with product_id at the top of the list
    // (other compound products are excluded - we can't have compound products
    // that contain other compound products)
    $sql = '
        SELECT
            product_id, product_name, unit_price, 
            pricing_unit, ordering_unit, constituent_quantity, donotlist
        FROM
            (SELECT * FROM '.TABLE_PRODUCT_PREP.'
              WHERE producer_id = "'.$producer_id.'"
              AND is_compound = 0)
            AS producer_prods
        LEFT JOIN
            (SELECT * FROM '.TABLE_COMPOUND_PRODUCT_PREP.' WHERE
            compound_product_id = "'.$product_id.'")
            AS edit_prod_subprods
        ON
            producer_prods.product_id =
            edit_prod_subprods.constituent_product_id
        ORDER BY 
            edit_prod_subprods.constituent_quantity DESC,
            product_name ASC';

    $result = @mysql_query($sql,$connection) or die(mysql_error());

    while ( $row = mysql_fetch_array($result) )
    {
        $prod_id = $row['product_id'];
        $prod_name = stripslashes($row['product_name']);
        $price = $row['unit_price'];
        $price_unit = $row['pricing_unit'];
        $order_unit = $row['ordering_unit'];
        $sub_prod_quant = $row['constituent_quantity'];
        $donotlist = $row['donotlist'];

        // Modify price if necessary according to ordering unit
        // E.g. if price is per kilo, but ordering unit is HALF kilo
        $order_unit_price = get_price($order_unit, $price);

        /*Only list products that are NOT archived
          EXCEPT for archived products that are part of
          this compound product.  We display these in red
          to indicate to the user that they need to be
          removed/replaced from the box/hamper contents. */
        if ( $donotlist == 0 || $sub_prod_quant > 0 )
        {
          $product_rows .= '<tr><td>'
            .($donotlist != 0 ? '<font color="red">'.$prod_name.'</font>' : $prod_name).'</td>
              <td>
              <input type="hidden" name="subprod_ids[]" value="'.$prod_id.'" />
              <input type="hidden" id="price_'.$prod_id.'" value="'.round($order_unit_price, 2).'" />
              <input type="text" name="subprod_quantities[]" value="'.$sub_prod_quant.'"
                  size=3 maxlength="4" id="'.$prod_id.'" onChange="updateCompoundPrice(event)" />
              '.$order_unit.'
              </td>
              <td>&nbsp;@ '.CURSYM.number_format($price, 2).' per '.$price_unit.'</td>
            </tr>';
        }
    }

    return $product_rows;

} // edit_subproducts_list

?>
