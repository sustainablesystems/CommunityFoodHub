<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

$display .= '<h1 align="center">'.SITE_NAME_SHORT.' Email Lists</h1>';
$display .= '<div width="800"><p align="center">
  <font color="red"><b>Copy and paste the mailing list you require from the list below into your email program</b></font>
  <br><b>Note:</b> discontinued members are not shown in the lists below.</p></div>';
$display .= '<table align="center" width="800" cellspacing="2" cellpadding="2" border="1">';

$sql = '
  SELECT
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.volunteer_interested,
    '.TABLE_MEMBER.'.producer_interested
  FROM
    '.TABLE_MEMBER.'
  WHERE
    pending != "1"
    AND membership_discontinued != "1"
  ORDER BY
    last_name ASC,
    first_name ASC';
$rs = @mysql_query($sql,$connection) or die(mysql_error());
$num = mysql_numrows($rs);

$member_emails = array();
$volunteer_emails = array();
$producer_interested_emails = array();

// Get lists of members, volunteer-interesed and producer-interested
// (arrays where each entry is a string in the format '"first_name last_name" <email_address>')
while ( $row = mysql_fetch_array($rs) )
{ 
  $first_name = stripslashes($row['first_name']);
  $last_name = stripslashes($row['last_name']);
  $email_address = $row['email_address'];
  $volunteer_interested = $row['volunteer_interested'];
  $producer_interested = $row['producer_interested'];

  if ( $email_address )
  {
      array_push($member_emails, '"'.$first_name.' '.$last_name.'" &lt;'.$email_address.'&gt;');
      if ( $volunteer_interested )
      {
        array_push($volunteer_emails, '"'.$first_name.' '.$last_name.'" &lt;'.$email_address.'&gt;');
      }
      if ( $producer_interested )
      {
        array_push($producer_interested_emails, '"'.$first_name.' '.$last_name.'" &lt;'.$email_address.'&gt;');
      }
  }
}


// Get producer email addresses
$producer_emails = array();
$sqlp = '
  SELECT
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.email_address
  FROM
    '.TABLE_MEMBER.'
  INNER JOIN
    '.TABLE_PRODUCER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_PRODUCER.'.member_id
  WHERE
    '.TABLE_PRODUCER.'.pending != "1"
    AND '.TABLE_PRODUCER.'.donotlist_producer != "1"
    AND '.TABLE_MEMBER.'.pending != "1"
    AND '.TABLE_MEMBER.'.membership_discontinued != "1"
  ORDER BY
    business_name ASC';
$resultp = @mysql_query($sqlp,$connection) or die(mysql_error());
while ( $row = mysql_fetch_array($resultp) )
{
  $business_name = stripslashes ($row['business_name']);
  $first_name = stripslashes($row['first_name']);
  $last_name = stripslashes($row['last_name']);
  $email_address = $row['email_address'];

  array_push($producer_emails, '"'.$business_name.' - '.$first_name.' '.$last_name.'" &lt;'.$email_address.'&gt;');
}

// Build the table of email lists
$display .= '<tr><td valign="top">Customers of '.SITE_NAME_SHORT.'</td><td id="copytext">'
  .implode(', ', $member_emails).'</td></tr>';
$display .= '<tr><td valign="top">Suppliers of '.SITE_NAME_SHORT.'</td><td>'
  .implode(', ', $producer_emails).'</td></tr>';
$display .= '<tr><td valign="top">Interested in <b>volunteering</b> for '.SITE_NAME_SHORT.'</td><td>'
  .implode(', ', $volunteer_emails).'</td></tr>';
$display .= '<tr><td valign="top">Interested in <b>supplying</b> '.SITE_NAME_SHORT.'</td><td>'
  .implode(', ', $producer_interested_emails).'</td></tr>';

$display .= '</table>';
?>

<?php include("template_hdr.php");?>
<!-- CONTENT BEGINS HERE -->

<?php echo $display;?>

<!-- CONTENT ENDS HERE -->
<?php include("template_footer.php");?>