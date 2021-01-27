<?php
$user_type = 'valid_c';
include_once ('config_foodcoop.php');
session_start();
validate_user();

include_once ('general_functions.php');

$producer_id = $_GET['producer_id'];
$delivery_id = $_GET['delivery_id'];
$detail_type = $_GET['detail_type'];

include('../func/order_summary_function.php');

// use=='batch' suppresses the printing of header links for other producer summaries
$display_page = generate_producer_summary ($producer_id, $delivery_id, $detail_type, 'batch');

include("template_hdr.php");
?>

  <!-- CONTENT BEGINS HERE -->

<div align="center">
<table width="800" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff">
  <tr>
    <td align="left">
      <?php echo $display_page; ?>
    </td>
  </tr>
</table>

</div>
  <!-- CONTENT ENDS HERE -->
<br><br>
<?php include("template_footer.php"); ?>