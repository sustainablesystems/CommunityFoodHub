<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
validate_user();
$authorization = get_auth_types($_SESSION['auth_type']);

$date_today = date("F j, Y");

// Check if auth_type is the adminstrator and reset to current producer if not
if ( $producer_id_you && $authorization['administrator'] === false )
  {
    $producer_id = $producer_id_you;
  }
$action = "add";
include("../func/edit_product_screen.php");

include("template_hdr_orders.php");

include("../func/javascript_popup.php");
?>

  <!-- CONTENT BEGINS HERE -->

<div align="center">
  <table width="800">
    <tr>
      <td align="left">

        <h1><font color="#770000"><?php echo $business_name;?> - Add Product</font></h1>

        <?php
          echo $help;

if ($message2)
  {
    echo '<div style="border:1px solid red;background:#ffeeee;padding:3px;color:#ff0000;overflow:auto;"><h1 style="font-size:4em;float:left;margin:0px 12px 0px 0px;">!</h1><br>'.$message2.'</div>';
  };

          echo $display;
        ?>
        <br><br>
      </td>
    </tr>
  </table>
</div>
  <!-- CONTENT ENDS HERE -->

<?php include("template_footer_orders.php");?>