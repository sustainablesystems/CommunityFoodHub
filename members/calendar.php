<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
session_start();
validate_user();

include("template_hdr_orders.php");

$display .= '<h1 align="center">'.SITE_NAME.' Calendar</h1>';

$display .= '<div align="center">';
$display .= '<iframe src="'.CALENDAR_URL.'" style=" border-width:0 " width="100%" height="400" frameborder="0" scrolling="no"></iframe>';
$display .= '</div>';

echo $display;

include("template_footer_orders.php");
?>
