<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
session_start();
validate_user();

include("template_hdr_orders.php");

include("../func/contact_body.php");
include("template_footer_orders.php");
?>