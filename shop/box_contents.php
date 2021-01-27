<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
include_once ('func/product_display.php');


$date_today = date("F j, Y");

// register_globals: $subcategory_id

$box_id = (int)( $_GET["box_id"] );

// TODO: Not needed?
//$is_producer_heading = true;
$is_prep = false;
$box_name = box_name_get($is_prep, $box_id);

// Clearer to explicitly set these args in my opinion
$is_logged_in = $is_institution = $producers_are_sublist = false;
$subtotal_curr = 0;
$table_type = 'public';
$subproduct_id = $box_id;
$is_prep = false;
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

<?php include('template_hdr.php');?>

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

<?php include('template_footer.php');?>