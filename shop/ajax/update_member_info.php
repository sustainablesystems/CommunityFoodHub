<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start ();
$authorization = get_auth_types($_SESSION['auth_type']);

// First ensure we have authority to execute member updates
if ($authorization['administrator'] === false)
  {
    echo 'Unauthorizied access';
    exit (0);
  }

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///     AJAX BACKEND FOR UPDATING A SINGLE VALUE IN THE MEMBER TABLE         ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////


// Get the arguments passed in the query_data variable
list ($member_id, $field_name, $new_value) = explode (':', $_POST['query_data']);

$member_id = mysql_real_escape_string ($member_id);
$field_name = mysql_real_escape_string ($field_name);
$new_value = mysql_real_escape_string ($new_value);

// Validate the field_name
// Ideally this would be dynamically built, but that would require another query
// so this is lighter, though less robust.
if (strpos ('|member_id|pending|username_m|password|auth_type|business_name|last_name|first_name|last_name_2|first_name_2|no_postal_mail|address_line1|address_line2|city|state|zip|county|work_address_line1|work_address_line2|work_city|work_state|work_zip|email_address|email_address_2|home_phone|work_phone|mobile_phone|fax|toll_free|home_page|membership_type_id|membership_date|membership_discontinued|mem_taxexempt|mem_delch_discount|how_heard_id|', "|$field_name|") === false)
  {
    echo 'Invalid field';
    exit (0);
  }

// Get the current value for that field
$query = '
  SELECT
    '.$field_name.'
  FROM
    '.TABLE_MEMBER.'
  WHERE
    member_id = "'.$member_id.'"';

$result= mysql_query($query) or die("Error: " . mysql_error());
while ($row = mysql_fetch_array($result))
  {
    $old_value = $row[$field_name];
  }

// Only update if there is a change
if ($old_value != $new_value)
  {
    // Update the field with the new value
    $query = '
      UPDATE
        '.TABLE_MEMBER.'
      SET
        '.$field_name.' = "'.$new_value.'"
      WHERE
        member_id = "'.$member_id.'"';

    $result= mysql_query($query) or die("Error: " . mysql_error());
    echo 'Changed value: '.$old_value;
  }
else
  {
    echo 'Not changed';
  }

// Return an informative message

?>