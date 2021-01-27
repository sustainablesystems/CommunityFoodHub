<?php
include_once ('config_foodcoop.php');
include_once('func/delivery_funcs.php');
// User may be logged on already
session_start();
// validate_user(); Do not validate because non-members must access this page

$display = "<tr><td align='left' colspan='2'><em>Note: If you don't see your area listed here,
  please contact <a href='mailto:".GENERAL_EMAIL."'>".GENERAL_EMAIL."</a>.
  If there is interest in a particular location, we may be able to add it.</em></td></tr>";

$delivery_date = get_current_delivery_date();

// Get all active and standby sites
$sqlr = '
  SELECT
    '.TABLE_DELCODE.'.route_id,
    route_name,
    delcode_id,
    delcode,
    deldesc,
    pickup_time,
    pickup_day,
    delday_offset,
    '.TABLE_DELCODE.'.inactive
  FROM
    '.TABLE_ROUTE.'
  INNER JOIN
     '.TABLE_DELCODE.'
  ON '.TABLE_ROUTE.'.route_id = '.TABLE_DELCODE.'.route_id
  WHERE '.TABLE_DELCODE.'.inactive != 1
  AND '.TABLE_ROUTE.'.inactive = 0
  ORDER BY
    route_name ASC,
    '.TABLE_DELCODE.'.inactive ASC,
    delcode ASC';
$rsr = @mysql_query($sqlr,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$route_id_prev = -1; // Invalid
while ( $row = mysql_fetch_array($rsr) )
  {
    $route_id = $row['route_id'];
    $route_name = $row['route_name'];
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];
    $deldesc = $row['deldesc'];
    $pickup_time = $row['pickup_time'];
    $pickup_day = $row['pickup_day'];
    $offset = $row['delday_offset'];
    $location_on_standby = ($row['inactive'] == 2);
    $deldate = get_delivery_from_offset($delivery_date, $offset, "l, j F Y");

    // Display the route for the first collection location only
    if ($route_id != $route_id_prev )
      {
        $display .= '
          <tr>
            <td align="left" colspan="2" bgcolor="#EDF3FC" id="'.$delcode_id.'">
              <h2>'.$route_name.'</h2>
            </td>
          </tr>';
      }

    $display .= '
      <tr>
        <td align="left" valign="top" colspan="2">
        <a name="'.$delcode_id.'"></a><h3>'.$delcode.'</h3></td>
      </tr>';

    if ($location_on_standby)
    {
      $display .= '
        <tr>
          <td align="left" valign="top">Collections are usually '
          .$pickup_time.' on '.$pickup_day.'.</td>';
    }
    else // Active location
    {
      $display .= '
        <tr>
          <td align="left" valign="top">Next collection is <b>'
          .$pickup_time.'</b> on <b>'.$deldate.'</b>.</td>';
    }

    $display .= '
        <td align="left" valign="top">'.nl2br ($deldesc).'</td>
      </tr>
      <tr><td colspan="2">&nbsp;</td></tr>';

    $route_id_prev = $route_id;
  }
?>

  <!-- CONTENT BEGINS HERE -->
<?php

include("template_hdr.php");

$display_block = "<h1>Collection Locations and Times</h1>
$font
<table bgcolor='#FFFFFF' cellspacing='5' cellpadding='0' border='0' align='center' width='800'>
  $display
</table>
";

echo $display_block;

include("template_footer.php");

?>