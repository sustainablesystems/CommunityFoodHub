<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
include_once ('../func/product_display.php');

session_start();
validate_user();
$authorization = get_auth_types($_SESSION['auth_type']);

if ( $add == "tocart" )
{
    include("../func/addtocart.php");
    /*$current_subtotal = "<div align=\"right\"><font size=\"-1\">
    <b>Current Subtotal: ".CURSYM.number_format($total, 2)."</b></font></div>";*/
    $subtotal_curr = $total;
}
else
{
    $subtotal_curr = 0;
}

// Get the time until the order closes
$seconds_until_close = strtotime ($_SESSION['closing_timestamp']) - time();

if ($authorization['institution'] === true && $seconds_until_close < INSTITUTION_WINDOW)
{
    $is_institution = true;
}
else
{
    $is_institution = false;
}

// Table of contents - sets $display
include(FUNC_FILE_PATH."category_list_table.php");

$producers_are_sublist = true;
$is_logged_in = true;
$table_type = 'shop';
$subproduct_id = 0;
$is_prep = false;

$sql = '
  SELECT
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_PRODUCT.'.subcategory_id,
    '.TABLE_PRODUCT.'.is_compound,
    '.TABLE_PRODUCT.'.donotlist
  FROM
    '.TABLE_CATEGORY.',
    '.TABLE_SUBCATEGORY.',
    '.TABLE_PRODUCT.',
    '.TABLE_PRODUCER.'
  WHERE
    '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
    AND '.TABLE_SUBCATEGORY.'.subcategory_id = '.TABLE_PRODUCT.'.subcategory_id
    '.$donotlist_condition.'
    AND '.TABLE_PRODUCT.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_PRODUCER.'.pending = 0
    AND '.TABLE_PRODUCER.'.donotlist_producer = 0
  GROUP BY
    '.TABLE_PRODUCT.'.subcategory_id
  ORDER BY
    sort_order ASC,
    subcategory_name ASC';

$rs = @mysql_query($sql,$connection) or die("Couldn't execute category query.");
while ( $row = mysql_fetch_array($rs) )
{
    $category_id = $row['category_id'];
    $category_name = stripslashes($row['category_name']);
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = stripslashes($row['subcategory_name']);
    $is_compound = $row['is_compound'];

    // This code just displays each category once, not repeating it
    // for every subcategory.  TODO: tidy up.  Also, when does $current_subtotal
    // ever appear?  If something's added to the basket we display the new
    // total at the end of each producer table anyway.
    if ( $current_category_id < 0 )
    {
        $current_category_id = $row['category_id'];
    }
    while ( $current_category_id != $category_id )
    {
        $current_category_id = $category_id;
        $display .= $current_subtotal;
        $display .= "<a name=\"$category_name\"/>";
        $display .= "<font color=\"#770000\"><h2 id=\"cat$category_id\">$category_name</h2></font>";
        $display .= "<hr>";
    }

    $display .= "<h3 id=\"subcat$subcategory_id\">$subcategory_name</h3>";

    $display .= products_by_producer_table($subcategory_id, $is_institution,
        $is_logged_in, $subtotal_curr, $producers_are_sublist, $table_type,
        $is_prep, $subproduct_id, $is_compound, $product_id_printed, $message, null);
}
?>

<?php include("template_hdr_orders.php");?>

  <!-- CONTENT BEGINS HERE -->
  <h1>Products by Category</h1>

  <div style="width: 800px; margin: auto;">
  <?php echo $display;?>
  </div>
  <!-- CONTENT ENDS HERE -->

<?php include("template_footer_orders.php");?>