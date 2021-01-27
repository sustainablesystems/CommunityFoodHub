<?php

// Get array of subproduct names for the given compound product
function subprod_names_get($prod_table, $comp_prod_table, $compound_prod_id)
{
    global $connection;

    $sql = 'SELECT product_name FROM '.$prod_table.' WHERE product_id
            IN (SELECT constituent_product_id FROM '.$comp_prod_table. ' WHERE
                compound_product_id = '.$compound_prod_id.')
            ORDER BY product_name ASC';
    $result = @mysql_query($sql,$connection) or die(mysql_error());

    $product_names = array();
    while ($row = mysql_fetch_array($result))
    {
      array_push($product_names, stripslashes($row['product_name']) );
    }

    //print("Compound prod table: " .$comp_prod_table. " Subprod names: " . implode(', ', $product_names) );

    return $product_names;
}


// Get array of subproduct names with quantity and ordering unit
// for the given compound product
function subprod_details_get($prod_table, $comp_prod_table, $compound_prod_id)
{
    global $connection;
    include_once ('general_functions.php');

    $sql = '
      SELECT
        '.$prod_table.'.product_name,
        '.$prod_table.'.ordering_unit,
        '.$comp_prod_table.'.constituent_quantity
      FROM
        '.$prod_table.'
      INNER JOIN '.$comp_prod_table.'
        ON '.$prod_table.'.product_id = '.$comp_prod_table.'.constituent_product_id
      WHERE '.$comp_prod_table.'.compound_product_id = '.$compound_prod_id.'
      ORDER BY '.$prod_table.'.product_name ASC';
    $result = @mysql_query($sql,$connection) or die(mysql_error());

    $prod_list = array();
    while ($row = mysql_fetch_array($result))
    {
      $prod_name = stripslashes($row['product_name']);
      $prod_quant = $row['constituent_quantity'];
      $prod_unit = Inflect::pluralize_if($prod_quant,
              stripslashes($row['ordering_unit']) );
      // N.B. Shouldn't embed HTML here, as this will appear in plain text email invoices
      $prod_details = $prod_name.' ('.$prod_quant.' '.$prod_unit.')';

      array_push($prod_list, $prod_details);
    }

    //print("Compound prod table: " .$comp_prod_table. " Subprod details: " . implode(', ', $prod_list) );

    return $prod_list;
}


?>
