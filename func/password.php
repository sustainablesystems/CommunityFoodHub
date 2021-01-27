<?php
// Common functions for dealing with passwords
function update_password($password_new, $member_email, $member_name, $is_reset)
{
  global $connection;

  if ($password_new != "" && $member_email != "" && $member_name != "")
  {
    $query_update = '
      UPDATE
        '.TABLE_MEMBER.'
      SET
        password = MD5("'.mysql_real_escape_string($password_new).'")
      WHERE
        username_m = "'.mysql_real_escape_string($member_email).'"';
    $result = mysql_query($query_update, $connection) or die(mysql_errno());

    // Send an email confirming the new password
    $message = 'Dear '.$member_name.',

We have received a request to '.($is_reset ? 'reset' : 'change').' your '.SITE_NAME.' password.

Your new password is: '.$password_new.'

'.($is_reset ? 'Please change this to something more memorable now by logging' : 'Log').' in to your '.SITE_NAME.' account at:
http://'.DOMAIN_NAME.PATH.'members/orders_login.php';
    $email_headers  = "From: ".MEMBERSHIP_EMAIL."\n";
    $email_headers .= "BCC: ".MEMBERSHIP_EMAIL;
    mail ( $member_email, 'Your '.SITE_NAME.' password - '.$member_name, $message, $email_headers);
  }
  else
  {
    echo "Error: updating password";
    exit(1);
  }
}
?>
