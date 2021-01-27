<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
session_start();

$message = '';

// Rather than use the check_valid_user function, we need to trap the result
if ( ! $_SESSION['valid_m'] )
  // The user is not valid, so provide a form to reset and send a new password by email
  {
    if ( $_POST['form_data'] == 'true' )
      // Validate the information and take appropriate action
      {
        $username_m = $email_address = mysql_real_escape_string($_POST['email_address']);
        
        // Check consistency between username_m and email_address
        $query_check = '
          SELECT
            username_m,
            email_address,
            first_name,
            last_name
          FROM
            '.TABLE_MEMBER.'
          WHERE
            username_m = "'.mysql_real_escape_string($username_m).'"
            AND email_address = "'.mysql_real_escape_string($email_address).'"';
        $result = @mysql_query($query_check, $connection) or die(mysql_error());
        $valid_info = false;
        while ( $row = mysql_fetch_array($result) )
          {
            if ($row['username_m'] == $username_m && $row['email_address'] == $email_address)
              {
                $valid_info = true;
                $valid_email = $row['email_address'];
                $valid_username = $row['username_m'];
                $valid_name = $row['first_name']." ".$row['last_name'];
              }
          }
        if ( $valid_info == true )
        // Everything looks good, send the new password to the validated email address.
          {
            // Generate new password
            $chars = "ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";
            $password = '' ;
            while (strlen ($password) <= rand(5,8))
              {
                $password .= substr($chars, rand(0,57), 1);
              }

            include("../func/password.php");
            update_password($password, $valid_email, $valid_name, true);

            header( 'refresh: 7; url=../index.php' );
            include("template_hdr_orders.php");
            echo
              '<table width="50%" align="center" cellspacing="5">
                <tr>
                  <td><p style="font-size:1.2em">An email has been sent to your e-mail address.
                    If you do not receive it, contact '.MEMBERSHIP_EMAIL.'.</p>
                  <p style="font-size:1.2em">In a few seconds, you will be redirected to the main page.</p></td>
                </tr>
              </table>';
            include("template_footer_orders.php");
            exit;
          }
        else
          // Information did not validate, so return to the form
          {
          $_POST['form_data'] = 'false';
          $message = '
            <p style="font-size:1.2em;color:#700;">
              Sorry... the information you submitted did not match our records.
              Please try again, or <a href="../contact.php">contact us</a> for assistance.
            </p>';
          }
      }
    if ( $_POST['form_data'] != 'true' )
      // Form data was not posted or was invalid, so show the form for input
      {
        include("template_hdr_orders.php");
        echo
          '<h1>Reset Password</h1>
          <form method="post" action="'.$_SERVER['PHP_SELF'].'" name="change_password">
          <table width="800" align="center">
            <tr><td align="left">
              Please enter the email address that you registered with '.$site_name
              .', and we\'ll send your new password to that email address.
              <p>'.$message.'</p>
            </tr></td>
            <tr><td align="center">
              <b>Email Address</b>:&nbsp;
              <input type="text" name="email_address" size="25" maxlength="50">
              <input type="hidden" name="form_data" value="true">&nbsp;
              <input type="submit" name="submit" value="Send New Password">
            </tr></td>
          </table>
          </form>';
        include("template_footer_orders.php");
      }
  }
else
  // The user is already logged in, so provide a form to change the password
  {
    if ( $_POST['form_data'] == 'true' )
      // Validate the password information and take appropriate action
      {
        $username_m = $_SESSION['username_m'];
        $old_password = $_POST['old_password'];
        $new_password1 = $_POST['new_password1'];
        $new_password2 = $_POST['new_password2'];
        // Make sure everything is filled in
        if($_SESSION['username_m'] && $old_password && $new_password1 && $new_password2)
          {
            // Check that the new passwords match
            if ( $new_password1 != $new_password2 )
              {
                $message .= '<p style="font-size:1.2em;color:#700;">New passwords do not match.</p>';
              }
            // Check that the old password is correct
            $query_pw = '
              SELECT
                "true" AS valid_password,
                first_name,
                last_name,
                email_address
              FROM
                '.TABLE_MEMBER.'
              WHERE
                username_m = "'.mysql_real_escape_string($username_m).'"
                AND password = MD5("'.mysql_real_escape_string($old_password).'")';
            $result = @mysql_query($query_pw, $connection) or die(mysql_error());
            $row = mysql_fetch_array($result);
            if ( $row['valid_password'] != 'true' )
              {
                $message .= '<p style="font-size:1.2em;color:#700;">Incorrect old password was provided.</p>';
              }
            if ($message == '')
              // Everything looks good, so go ahead and update the password
              {
                include("../func/password.php");
                update_password($new_password1, $row['email_address'],
                        $row['first_name']." ".$row['last_name'], false);
                
                header( 'refresh: 7; url=index.php' );
                include("template_hdr_orders.php");
                echo
                '<table width="50%" align="center" cellspacing="5">
                  <tr>
                    <td><p style="font-size:1.2em">Your password has been updated. </p>
                    <p style="font-size:1.2em">In a few seconds, you will be redirected to the login page.</p></td>
                  </tr>
                </table>';
                include("template_footer_orders.php");
                exit;
              }
            else
              // There was an error, so return to the form
              {
                $_POST['form_data'] = 'false';
              }
          }
        else
          {
            $_POST['form_data'] = 'false';
          }
      }
    if ( $_POST['form_data'] != 'true' )
      // Form data was not posted or was invalid, so show the form for input
      {
        include("template_hdr_orders.php");
        echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="change_password">';
        echo '
          <table width="800" align="center" cellspacing="5">
            <tr>
              <td colspan="2">';
        if ($message)
          {
            echo $message.'<p style="font-size:1.2em;color:#700;">Please re-enter your information.</p>';
          }
        else
          {
            echo '<p style="font-size:1.2em">To change your password, please enter your old password and
              enter your new password twice for confirmation.</p>';
          }
        echo '
              </td>
            </tr>
            <tr>
              <td align="left" width="25%"><b>Old&nbsp;Password</b>:</td>
              <td align="left"><input type="password" name="old_password" size="17" maxlength="20"></td>
            </tr>
            <tr>
              <td align="left"><b>New&nbsp;Password</b>:</td>
              <td align="left"><input type="password" name="new_password1" size="17" maxlength="25"></td>
            </tr>
            <tr>
              <td align="left"><b>New&nbsp;Password&nbsp;(confirm)</b>:</td>
              <td align="left"><input type="password" name="new_password2" size="17" maxlength="25"></td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td align="left">
                <input type="hidden" name="form_data" value="true">
                <input type="submit" name="submit" value="Update">
              </td>
            </tr>
          </table>
          </form>';
        include("template_footer_orders.php");
      }
  }
?>
