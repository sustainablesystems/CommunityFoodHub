<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
validate_user();
$authorization = get_auth_types($_SESSION['auth_type']);

$delivery_id = $current_delivery_id;

// If the auth_type is administrator, then we will allow viewing of invoices for other members and orders
// Note these are NOT finalized invoices... these are the dynamic ones.
if($authorization['administrator'] === true && $_GET['member_id'] && $_GET['delivery_id'] && $_GET['basket_id'])
  {
    // Save session values in order to put them back before we're done (MESSY because of register_globals!)
    $original_session_member_id = $_SESSION['member_id'];
    $original_session_delivery_id = $_SESSION['delivery_id'];
    $original_session_basket_id = $_SESSION['basket_id'];

    $member_id = $_GET['member_id'];
    $delivery_id = $_GET['delivery_id'];
    $basket_id = $_GET['basket_id'];

    $put_it_back = true;
  }

include("../func/gen_invoice.php");
// Check whether to show the invoice as finalised (default is true)
$final = ($_GET['final'] == "false") ? false : true;
$display_page = geninvoice($member_id, $basket_id, 
  $delivery_id, "members", true, $final);

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <title><?php echo ($final ? "" : "Provisional ")."Invoice for account #$member_id"; ?></title>
  </head>
<body>
<?php

    echo $display_page;
    echo "</body></html>";

//include("template_footer_orders.php");

// Restore the session variables to their original settings
if ($put_it_back === true)
  {
  $member_id = $original_session_member_id;
  $delivery_id = $original_session_delivery_id;
  $basket_id = $original_session_basket_id;
  };

?>