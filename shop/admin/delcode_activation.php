<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
include_once('../func/delivery_funcs.php');
session_start();
validate_user();

$action = $_POST['action'];
include ("template_hdr.php");


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                     BEGIN NEW PAGE - NO SUBMITTED DATA                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

// Get the number of products from the previous cycle's orders so later we can
// make them all non-new.
$query = '
  SELECT
    MAX( product_id ) AS max_id
  FROM
    `'.TABLE_PRODUCT_PREV.'`';
$result = @mysql_query($query, $connection) or $error_array[5] = "SQL Error while retrieving maximum former product id!\n";
$row = mysql_fetch_array($result); // Only need the first row
$max_id_notnew = $row['max_id'];

$query = '
  SELECT
    COUNT(product_id) AS count_new_products 
  FROM
    '.TABLE_PRODUCT.'
  WHERE
    product_id > "'.$max_id_notnew.'"';
$result = @mysql_query($query, $connection) or die("<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href=\"mailto:webmaster@$domainname\">webmaster@$domainname</a><br><br><b>Error:</b> Current Delivery Cycle " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$row = mysql_fetch_array($result); // Only need the first row
$count_new_products = $row['count_new_products'];


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                        BEGIN PROCESSING SUBMITTED PAGE                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

if ($action == "Update")
  {

    // Update the delivery codes to turn them on/off.
    $query = '
      SELECT
        *
      FROM
        '.TABLE_DELCODE.'
      ORDER BY
        delcode_id,
        delcode;';
    $result = mysql_query($query, $connection) or die("<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href=\"mailto:webmaster@$domainname\">webmaster@$domainname</a><br><br><b>Error:</b> Current Delivery Cycle " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    while ($row = mysql_fetch_object($result))
      {
        if ($row->inactive != $_POST[$row->delcode_id.'_inactive'])
          {
            $query2 = '
              UPDATE
                '.TABLE_DELCODE.'
              SET
                inactive = '.$_POST[$row->delcode_id.'_inactive'].'
              WHERE
                delcode_id = "'.$row->delcode_id.'"';
            $null = mysql_query($query2, $connection) or die("<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href=\"mailto:webmaster@$domainname\">webmaster@$domainname</a><br><br><b>Error:</b> Current Delivery Cycle " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
          }
      }
  }

// Always show the form...
echo '
  <h1 align="center">Activate Collection Locations</h1>
  <div style="width: 800px; margin: auto;">
  <p>Use the following form to change which collection sites are available.
  &quot;Standby&quot; is used for sites that are not available for this order cycle,
  but remain displayed on the locations page to indicate they are often available.</p></div>
  <form action="'.$_SERVER['PHP_SELF'].'" method="POST">';

$query = '
  SELECT
    *
  FROM
    '.TABLE_DELCODE.'
  ORDER BY
    delcode;';
$result = mysql_query($query, $connection) or die("<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href=\"mailto:webmaster@$domainname\">webmaster@$domainname</a><br><br><b>Error:</b> Current Delivery Cycle " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
echo '
    <table border="1" align="center" width="800" cellpadding="2" style="background-color:#eee; border:1px solid black; border-collapse:separate; font-size:80%">
      <tr style="color:#ffc; background-color:#468;">
        <th>ID</th>
        <th>Name</th>
        <th>Collection time</th>
        <th>State</th>
        <th>Actions</th>
      </tr>';

// Get the date of the final collection for the current cycle
$delivery_date = get_current_delivery_date();

while ($row = mysql_fetch_object($result))
  {
    if ($row->inactive == 0) // Active delivery site
      {
        $inactive_select = '
          <option value="0" selected>Active Site</option>
          <option value="1">INACTIVE</option>
          <option value="2">Standby Site</option>
          ';
        $inactive_color = '#cfc';

        // Work out the next delivery date
        $deldate = get_delivery_from_offset($delivery_date, $row->delday_offset, "l, j F Y");
      }
    elseif ($row->inactive == 1) // Inactive delivery site
      {
        $inactive_select = '
          <option value="0">Active Site</option>
          <option value="1" selected>INACTIVE</option>
          <option value="2">Standby Site</option>
          ';
        $inactive_color = '#fcc';
      }
    elseif ($row->inactive == 2) // Inactive delivery site but okay for signups
      {
        $inactive_select = '
          <option value="0">Active Site</option>
          <option value="1">INACTIVE</option>
          <option value="2" selected>Standby Site</option>
          ';
        $inactive_color = '#ffc';
      }
    if ($row->deltype == "P") // Order pickup site
      {
        $deltype_display = "Pickup";
      }
    elseif ($row->deltype == "D") // Delivery choice
      {
        $deltype_display = "Delivery";
      }
    echo '
      <tr style="background-color:'.$inactive_color.';">
        <td>'.$row->delcode_id.'</td>
        <td>'.$row->delcode.'</td>
        <td>'.(0 == $row->inactive
                ? 'Next collection is <b>'.$row->pickup_time.'</b> on <b>'.$deldate.'</b>.'
                : 'Collections are usually '.$row->pickup_time.' on '.$row->pickup_day.'.').'</td>
        <td><select name="'.$row->delcode_id.'_inactive">'.$inactive_select.'</select></td>
        <td><a href="collection_locations.php?delcode_id='.$row->delcode_id.'">Edit&nbsp;location</a></td>
      </tr>';
  }

echo '    
  </table>
  <div style="width: 800px; margin: auto;" align="right">
    <br><a href="collection_locations.php?delcode_id=AddNewLocation">Add a new location</a>&nbsp;
    <input type="submit" name="action" value="Update"/>&nbsp;
    <input type="reset" value="Reset Form"/>
  </div>
  </form>';

include ("template_footer.php");
