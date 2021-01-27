<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
include_once ('general_functions.php');
session_start();
validate_user();
$authorization = get_auth_types($_SESSION['auth_type']);

// Need to set this so that admins can add "wholesale" products, if desired
if ($authorization['administrator'] === true)
  {
    $_SESSION['auth_type'] .= ',institution';
    $authorization['institution'] = true;
  }

$fontface="arial";

include("../func/add_prod.php");
include("../func/mem_edit_invoice_admin.php");

$admin_submit = true;
include("../func/submit_order.php"); // Note: sets $member_name

include("template_hdr.php");
?>

<h1 align="center">Edit Basket</h1>
<a name="basket"/>
<table align="center" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" border="0" width="800">
  <tr>
    <td bgcolor="#FFFFFF" colspan="3" align="left">
      <font color="#770000"><?php echo $message;?></font>
    </td>
  </tr>
  
  <form name="order" action="#basket" method="post">
    <tr>
      <th valign="bottom" align="left" bgcolor="#AEDE86" colspan="3"><font face="<?php echo $fontface;?>">
        <b>Add Products: Basket # <?php echo $basket_id;?>, Member # <?php echo $member_id.' - '.$member_name;?></b>
      </th>
    </tr>
    <tr>
      <td valign="top" bgcolor="#DDDDDD">
        <table cellspacing="2" cellpadding="2" width="100%" border="0">
          <tr>
            <td align="left"><?php echo$font;?># <input type="text" name="product_id" size=5 maxlength="6">&nbsp;<b>Product ID</b></td>
            <td align="left"><?php echo$font;?><input type="text" name="quantity" value="1" size=3 maxlength="4">&nbsp;<b>Quantity</b></td>
          </tr>
          <tr bgcolor="#DDDDDD">
            <td><a href="price_list_full.php?cycle=curr&show=product_id" target="_new"><?php echo$font;?>Price List with Product IDs</a></td>
            <td align="left">
              <input type="hidden" name="yp" value="ds">
              <input type="hidden" name="delivery_id" value="<?php echo $delivery_id;?>">
              <input type="hidden" name="member_id" value="<?php echo $member_id;?>">
              <input type="hidden" name="basket_id" value="<?php echo $basket_id;?>">
              <input name="where" type="submit" value="Add this Product to the Order">
              <script language=javascript> document.order.product_id.focus(); </script>
            </td>            
          </tr>
        </table>
      </td>
      <td>&nbsp;</td>
      <td align="center" valign="center" bgcolor="#ADB6C6" valign="top" rowspace="2">
        <?php echo$font;?>
        <b>Order Subtotal = <?php echo CURSYM."".number_format($total, 2)."";?></b><br>
        <a href="customer_invoice.php?delivery_id=<?php echo$delivery_id; ?>&basket_id=<?php echo$basket_id; ?>&member_id=<?php echo$member_id; ?>" target="_new">View Current Invoice</a>
      </td>
    </tr>
  </form>

  <tr>
    <td colspan="3"><?php echo$font;?><?php echo $display_page;?><br>
      <?php echo $message3;?>
    </td>
  </tr>
</table>
<a name="checkout"/>
<div align="center">
  <?php echo $display_submit; ?><br>
  [ <a href="orders_list.php?delivery_id=<?php echo$delivery_id;?>#<?php echo$basket_id;?>">View All Customer Invoices</a> ]
</div>
<?php include("template_footer.php");?>
