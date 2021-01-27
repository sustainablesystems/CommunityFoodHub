<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
session_start();
validate_user();

// TODO: Use standard header file.
$display .= '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>'.SITE_NAME.' - Shop</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="Content-Language" content="en-uk">';

include_once("../func/table_sort.php");
$display .= $scripts;
$display .= $styles;

$display .= '</head>

<body bgcolor="#FFFFFF">
<table cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color:#FFFFFF;">
  <tr>
    <td align="center">
      <div>';
if (SHOW_HEADER_LOGO === true)
  {
  $display .= '<a href="'.BASE_URL.PATH.'members/">
        <img src="'.PATH.'grfx/logo.gif" border="0" alt="Food '.ORGANIZATION_TYPE.'" align="middle"></a>
        <br>';
  }

if (SHOW_HEADER_SITENAME === true)
  {
  $display .= '<h2>'.SITE_NAME.'</h2>';
  }
  $display .= '    </div>
    </td>
  </tr>
  <tr>
    <td align="center">';

      include(FUNC_FILE_PATH.'header_footer_common.php');
      $display .= $links;
$display .= '</td>
  </tr>
</table>';

$display .= '<h1 align="center">'.SITE_NAME.' Volunteer Rota</h1>';

include_once("../func/volunteer_funcs.php");

$cal = new VolunteerCal($member_id);
$email_sent = false;
$confirmation_sent = false;

switch ($action)
{
  case "Update":
    $confirmation_sent = $cal->update($volunteer_event_ids);
    break;

  case "Send Email":
    $email_sent = $cal->send_email($subject, $message, $recipients);
    break;
  
  default:
    break;
}

$display .= '<table align="center" width="800"><tr><td>';

if ($confirmation_sent)
{
  $display .= '<p align="center"><b><font color="blue">Your volunteering dates have been updated.
    You will receive a confirmation email shortly.</font></b></p><br>';
}

$display .= '<b>Instructions:</b> Select the dates when you want to volunteer below, then click the "Update" button.  Please wait while
  the update takes place.  You can change your dates later if necessary.
  <br><br><b>Tips:</b> click column headings to change the sorting of the table, for instance click "Venue" to sort the table by venue.
  To view a calendar of dates, visit the <a href="calendar.php" target="new">'.SITE_NAME.' Calendar</a> (opens in new window).
  ';

$display .= '</td></tr></table><br>';
$display .= '<div align="center">';

$display .= $cal->table();
$display .= $cal->email_form($email_sent);

$display .= '</div>';

echo $display;

include("template_footer_orders.php");
?>
