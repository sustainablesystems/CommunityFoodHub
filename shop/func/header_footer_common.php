<?php
// Display different sets of links depending on whether the user is logged in
if (($_SESSION['member_id'] != '') || ($_GET['members'] == 'true'))
{
  $links = '<table cellspacing="0" cellpadding="0" width="800"><tr><td align="center">
    <font face="arial" size="-1"><b>
      [ <a href="'.BASE_URL.PATH.'members/category_list_full.php">Continue Shopping</a> |
      <a href="'.BASE_URL.PATH.'members/orders_current.php">View Basket</a> |
      <a href="'.BASE_URL.PATH.'members/orders_current.php#checkout">Checkout</a> |
      <a href="'.BASE_URL.PATH.'members/index.php">My Account</a> |
      <a href="'.BASE_URL.PATH.'members/calendar.php">Calendar</a> |
      <a href="'.BASE_URL.PATH.'members/faq.php">Help</a> |
      <a href="'.BASE_URL.PATH.'members/contact.php">Contact Us</a> |
      <a href="'.BASE_URL.PATH.'members/logout.php">Logout</a> ]
    </b></font>
    </td>
    </tr></table>';
}
else
{
  $links = '
    <font face="arial" size="-1"><b>
      [ <a href="'.BASE_URL.PATH.'index.php">Shopping Home</a> |
      <a href="'.BASE_URL.PATH.'faq.php">Help</a> |
      <a href="'.BASE_URL.PATH.'contact.php">Contact Us</a> ]
    </b></font>';
}
?>
