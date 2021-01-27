<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
validate_user();
$authorization = get_auth_types($_SESSION['auth_type']);

// This is the script for editing the shopping basket

$time_now = time();
// Check to make sure a basket is open
if ( (( $time_now < $date_open || $time_now > $date_closed ))
  || !isset($basket_id) )
  {
    header( "Location: index.php");
    exit;
  }

$fontface="arial";

include("../func/add_prod.php");
include("../func/mem_edit_invoice.php");

$admin_submit = false;
include("../func/submit_order.php");

$current_total_display .= '
  <span style="font-family:Arial;font-size:14pt;">
    <b>Current total: <font color="red">'.CURSYM.number_format($total + $order_cost, 2).'</font></b>*
  </span>';

if ($total > 0)
{
  $current_total_display .= '
    <a href="orders_current.php#checkout">
    <img src="../grfx/checkoutnow.gif" alt="Proceed to CHECKOUT"
         style="vertical-align:middle;border:5px solid transparent;"/>
   </a>';
}
?>

<?php include("template_hdr_orders.php");?>
<a name="basket"/>
<h1>Your Basket</h1>
<div align="center">
<table cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" border="0" width="800">
  <tr>
    <td align="right" bgcolor="#EFEFEF">
      <?php echo $current_total_display; ?>
    </td>
  </tr>
  <tr>
    <td colspan="3">
      <?php echo $font; ?>
      <?php echo $display_page; ?>
      <?php if (isset($message3)) echo "<br>".$message3; ?>
    </td>
  </tr>
  <tr>
    <td align="right" bgcolor="#EFEFEF" valign="top">
      <?php echo $current_total_display; ?>
    </td>
  </tr>
  <tr>
    <td colspan="3">
      <?php include ("../func/prior_cart_list.php"); ?>
    </td>
  </tr>
    
<?php if ($qty_in_basket > 0) { ?>  
  <tr>    
    <td colspan="3" align="center">
      <a name="checkout"/>
      <h1>Checkout</h1>
      <?php echo $display_submit; ?>
    </td>
  </tr>
  <tr>
    <td bgcolor="#ffeedd" colspan="3" align="center" style="padding:1em 2em 1em; border:1px solid #f00;color:#f00;">
      You can update and resubmit your order until <?php echo $order_cycle_closed;?>.
      <br>
      Please remember to pick up your order. Payment is by cash or cheque on collection.
    </td>
  </tr>
<?php } ?>
  <tr>
    <td colspan="3" valign="top" align="left"><?php echo $font;?>
      * Total includes <?php echo CURSYM.number_format($order_cost, 2); ?> admin and packing.
    </td>
  </tr>
</table>

<?php include("template_footer_orders.php");?>