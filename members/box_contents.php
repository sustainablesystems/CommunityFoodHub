<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
include_once ('../func/product_display.php');

// TODO: One of only three diffs from shop/box_contents.php
session_start();
validate_user();
$authorization = get_auth_types($_SESSION['auth_type']);

$date_today = date("F j, Y");

// register_globals: $subcategory_id

$box_id = (int)( $_GET["box_id"] );

// Box contents can be shown when a producer is editing the (in prep) product list
$is_prep = ($_GET["prep"] == 'true' ? true : false);

$box_name = box_name_get($is_prep, $box_id);

// Get the time until the order closes
$seconds_until_close = strtotime ($_SESSION['closing_timestamp']) - time();
// Set up the "donotlist" field condition based on whether the member is an "institution" or not
// Only institutions are allowed to see donotlist=3 (wholesale products)
if ($authorization['institution'] === true && $seconds_until_close < INSTITUTION_WINDOW)
{
    $is_institution = true;
    //$donotlist_condition = 'AND ('.TABLE_PRODUCT.'.donotlist = "0" OR '.TABLE_PRODUCT.'.donotlist = "3")';
  }
else
{
    $is_institution = false;
    //$donotlist_condition = 'AND '.TABLE_PRODUCT.'.donotlist = "0"';
}

$producers_are_sublist = false;
$is_logged_in = true;
$table_type = 'subproducts'; // N.B. not 'shop' as can't add to cart.
$subtotal_curr = 0;
$subproduct_id = $box_id;
$is_compound = false;

$display = products_by_producer_table($subcategory_id, $is_institution, $is_logged_in,
        $subtotal_curr, $producers_are_sublist, $table_type, $is_prep, $subproduct_id,
        $is_compound, $product_id_printed, $message, null);

$display_heading = '<font color="#770000"><h2>Contents of '.$box_name.'</h2></font>';

// If a subcategory ID has been provided, link back to the subcategory in the full product list
if ($_GET["subcat_id"])
{
  $subcat_id = (int)($_GET["subcat_id"]);
  $display_heading .= '
      <p><font size="-1"><b>
        <a href="category_list_full.php#subcat'.$subcat_id.'">Return to Product List</a>
      </b></font></p>';
}
?>

<!-- TODO: One of only three diffs from shop/box_contents.php -->
<?php include("template_hdr_orders.php");?>

<script type="text/javascript" language="javascript">
var new_window = null; function create_window(w,h,url) {
var options = "width=" + w + ",height=" + h + ",status=no";
new_window = window.open(url, "new_window", options); return false; }
</script>

  <!-- CONTENT BEGINS HERE -->
<div style="width: 800px; margin: auto;">
  <?php echo $display_heading.$display; ?>
</div>
  <!-- CONTENT ENDS HERE -->

<!-- TODO: One of only three diffs from shop/box_contents.php -->
<?php include('template_footer_orders.php');?>