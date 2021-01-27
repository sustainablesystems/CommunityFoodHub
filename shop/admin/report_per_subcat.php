<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();

$num_months = 13; # should be 1 higher than the actual number of months you want

$main_sql = mysql_query("SELECT baskets.delivery_id, dates.delivery_date, subcats.subcategory_name, cats.category_name, (!out_of_stock * if(prod.random_weight = 1, items.item_price * total_weight, items.item_price * quantity)) real_price
	FROM customer_basket_items items, customer_basket_overall baskets, delivery_dates dates, current_delivery cur_date, product_list prod, categories cats, subcategories subcats
	WHERE items.basket_id = baskets.basket_id AND dates.delivery_id = baskets.delivery_id AND prod.product_id = items.product_id AND prod.subcategory_id = subcats.subcategory_id AND cats.category_id = subcats.category_id AND baskets.delivery_id < cur_date.delivery_id AND baskets.delivery_id > (cur_date.delivery_id-$num_months)
	GROUP BY items.bpid");
$categories = array();
while ($row = mysql_fetch_array($main_sql))
{
	if (isset($categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]]))
		$categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]] += $row["real_price"];
	else
		$categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]] = $row["real_price"];
}

$dates_sql = mysql_query("SELECT dates.delivery_date
	FROM delivery_dates dates, current_delivery cur_date
	WHERE dates.delivery_id < cur_date.delivery_id AND dates.delivery_id > (cur_date.delivery_id-$num_months)
	ORDER BY dates.delivery_date DESC");

$delivery_dates = array();
$spreadsheet = "Subcategory";
$date_headers = "";
while ($row = mysql_fetch_array($dates_sql))
{
	array_push($delivery_dates, $row["delivery_date"]);
	$date_headers .= "<th>".$row["delivery_date"]."</th>\n";
	$spreadsheet .= "\t".$row["delivery_date"];
}

$table = "";
$spreadsheet .= "\n";
ksort($categories);
foreach ($categories as $cat_name => $cat)
{
	$table .= "<tr><th colspan='" . (1+count($delivery_dates)) . "' style='font-size: 1.5em; padding: 0.5em;'>$cat_name</th></tr>\n";
	$table .= "<tr>\n";
	$table .= "<th>Subcategory</th>\n$date_headers";
	$table .= "</tr>\n";
	$spreadsheet .= "\n*** $cat_name ***\n";
	ksort($cat);
	foreach ($cat as $subcat_name => $subcat)
	{
		
		$table .= "<tr>\n";
		$table .= "<th style='text-align: left;'>$subcat_name</th>\n";
		$spreadsheet .= $subcat_name;
		foreach ($delivery_dates as $date)
		{
			$value = (isset($subcat[$date]) && $subcat[$date] != 0) ? number_format($subcat[$date], 2) : "-";
			$table .= "<td style='text-align: right;'>$value</td> ";
			$spreadsheet .= "\t".($value == "-" ? "0.00" : $value);
		}
		$table .= "</tr>";
		$spreadsheet .= "\n";
	}
}

include("template_hdr.php");
?>

	<!-- CONTENT BEGINS HERE -->

<div align="center">
<table width="90%">
  <tr><td align="left">

  <h2>Sales By Subcategory (last 12 order cycles)</h2>

<form>
<label for="spreadsheet">Spreadsheet copyable data (click to select all, then copy):</label><br>
<textarea style="margin-bottom: 1em;" id="spreadsheet" onclick="this.select();"><?php $spreadsheet ?></textarea>
</form>

<table cellpadding="2" cellspacing="2" border="1">
<?php echo $table;?>
</table>



  </td></tr>
</table>
</div>
	<!-- CONTENT ENDS HERE -->
<?php include("template_footer.php");?>
</body>
</html>
