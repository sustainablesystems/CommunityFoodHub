<?php
// Refactored common code for creating the list of products by category

// Set up the "donotlist" field condition based on whether the member is an "institution" or not
// Only institutions are allowed to see donotlist=3 (wholesale products)
if ( $is_institution )
{
  $donotlist_condition = 'AND ('.TABLE_PRODUCT.'.donotlist = "0" OR '.TABLE_PRODUCT.'.donotlist = "3")';
}
else
{
  $donotlist_condition = 'AND '.TABLE_PRODUCT.'.donotlist = "0"';
}

$sql = '
  SELECT
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_PRODUCT.'.subcategory_id,
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
    category_name ASC,
    subcategory_name ASC';
$rs = @mysql_query($sql,$connection) or die("Couldn't execute category query: ".mysql_error());

// Putting categories in a table rather than continuing list downwards
$columns_count_max = 5;
$category_count = 0;
$display .= '<table width="100%" cellpadding="10" cellspacing="2" border="0">
  <tr valign="top">';
while ( $row = mysql_fetch_array($rs) )
  {
    $category_id = $row['category_id'];
    $category_name = stripslashes($row['category_name']);
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = stripslashes($row['subcategory_name']);

    // Check for a new category
    if ( !isset($category_id_prev)
            || $category_id_prev != $category_id )
      {
        $category_id_prev = $category_id;
        if ($category_count != 0)
        {
          // End previous category table cell and start a new row if necessary
          $display .= "</ul></td>";
          if ($category_count == $columns_count_max)
          {
            $display .= '</tr><tr valign="top">';
            $category_count = 0;
          }
        }
        $display .= "<td bgcolor=\"#dddddd\"><h3 align=\"center\">
          <a href=\"category_list_full.php#cat$category_id\">$category_name</a></h3><ul>";
        $category_count++;
      }

    $display .= "<li><a href=\"category_list_full.php#subcat$subcategory_id\">
      $subcategory_name</a>";
  }

  $display .= '</ul></td></tr></table>';
?>
