<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
include_once ('func/product_display.php');

if ($_GET['producer_id'])
  {
    $producer_id = $_GET['producer_id'];
  }
else
  {
    $producer_id = substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1,-4);
  }

$is_logged_in = ($_GET['members'] == 'true');
$table_type = 'public';
$subtotal_curr = 0;
$producers_are_sublist = true;
$subproduct_id = 0;
$is_prep = false;

include(FILE_PATH.PATH.'func/display_producer_page.php');

include(FILE_PATH.PATH.'func/show_businessname.php');

$sql = '
  SELECT
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_PRODUCT.'.*
  FROM
    '.TABLE_CATEGORY.',
    '.TABLE_SUBCATEGORY.',
    '.TABLE_PRODUCT.',
    '.TABLE_PRODUCER.'
  WHERE
    '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
    AND '.TABLE_SUBCATEGORY.'.subcategory_id = '.TABLE_PRODUCT.'.subcategory_id
    AND '.TABLE_PRODUCT.'.producer_id = "'.$producer_id.'"
    AND '.TABLE_PRODUCT.'.donotlist = "0"
    AND '.TABLE_PRODUCER.'.pending = "0"
    AND '.TABLE_PRODUCER.'.donotlist_producer = "0"
  ORDER BY
    '.TABLE_CATEGORY.'.category_name ASC,
    '.TABLE_SUBCATEGORY.'.subcategory_name ASC';
$rs = @mysql_query($sql,$connection) or die("Couldn't execute category query.");
$nums = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $category_id = $row['category_id'];
    $category_name = $row['category_name'];
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = $row['subcategory_name'];
    $is_compound = $row['is_compound'];
    
    if ( $current_subcategory_id<0 )
      {
        $current_subcategory_id = $row['subcategory_id'];
      }
    while ($current_subcategory_id != $subcategory_id)
      {
        $current_subcategory_id = $subcategory_id;
        $display .= "<font color=\"#770000\"><h2 id=\"cat$category_id\">$category_name</h2></font>";
        $display .= "<hr>";

        // Diplay the table in a public format (no adding to basket from this page etc.)
        $display .= products_by_producer_table($subcategory_id, $is_institution,
            $is_logged_in, $subtotal_curr, $producers_are_sublist, $table_type,
            $is_prep, $subproduct_id, $is_compound, $product_id_printed, $message, $producer_id);
      }
  }
?>

<?php
  if ($is_logged_in)
  {
    include("members/template_hdr_orders.php");
  }
  else
  {
    include("template_hdr.php");
  }
?>

<div style="width: 800px; margin: auto;">
<?php echo prdcr_info($producer_id, $is_logged_in);?>

<span id="products"></span>
<br>
<font size="5"><b><?php echo stripslashes($business_name);?> Products for Sale through <?php echo SITE_NAME_SHORT ?></b></font><br>
<?php echo "$display";?><br>

</div>

<?php
  if ($is_logged_in)
  {
    include("members/template_footer_orders.php");
  }
  else
  {
    include("template_footer.php");
  }
?>
