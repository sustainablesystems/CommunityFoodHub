<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
session_start();

$show_form = "yes";

// If needing to login, keep track of the page that was initially requested and redirect to it
$success_redirect = 'Location: index.php';
if ($_GET['call'])
  {
    $redirect_call = '?call='.$_GET['call'];
    $success_redirect = 'Location: '.$_GET['call'];
  }

if ( $_POST['gp'] == "ds" && $_POST['username_m'] && $_POST['password'] )
  {
    $query = '
      SELECT
        auth_type,
        username_m,
        membership_discontinued,
        pending
      FROM
        '.TABLE_MEMBER.'
      WHERE
        username_m = "'.mysql_real_escape_string($_POST['username_m']).'"
        AND
          (password = md5("'.mysql_real_escape_string($_POST['password']).'")
          OR "'.MD5_MASTER_PASSWORD.'" = md5("'.mysql_real_escape_string($_POST['password']).'"))';
    $result = mysql_query($query, $connection);
    $row = @mysql_fetch_array($result);
    if ( mysql_numrows ($result) != 0 && $row['pending'] != 1 && $row['membership_discontinued'] != 1)
      {
        // Clear any old session variables
        session_destroy();
        // Set session variables here so it doesn't matter what page is accessed next
        session_start ();

        $query = '
          SELECT
            *
          FROM
            '.TABLE_CURDEL;
        $result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        while ( $row = mysql_fetch_array($result) )
          {
            $_SESSION['current_delivery_id'] = $row['delivery_id'];
            $delivery_date = $row['delivery_date'];
            $_SESSION['closing_timestamp'] = $row['closing_timestamp'];
            $_SESSION['date_open'] = strtotime ($row['date_open']);
            $_SESSION['order_cycle_closed'] = $row['order_cycle_closed'];
            $_SESSION['date_closed'] = strtotime ($row['date_closed']);
          }
        include("../func/convert_delivery_date.php");
        $_SESSION['current_delivery_date'] = $delivery_date;

        $sqlm = '
          SELECT
            '.TABLE_MEMBER.'.member_id,
            '.TABLE_MEMBER.'.username_m,
            '.TABLE_MEMBER.'.auth_type,
            '.TABLE_MEMBER.'.first_name,
            '.TABLE_MEMBER.'.first_name_2,
            '.TABLE_MEMBER.'.last_name,
            '.TABLE_MEMBER.'.last_name_2,
            '.TABLE_MEMBER.'.business_name,
            '.TABLE_MEMBER.'.pending,            
            '.TABLE_PRODUCER.'.producer_id
          FROM
            '.TABLE_MEMBER.'
          LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
         WHERE
            username_m = "'.mysql_real_escape_string ($_POST['username_m']).'"';

        $result = @mysql_query($sqlm, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        while ( $row = mysql_fetch_array($result) )
          {
            $_SESSION['member_id'] = $row['member_id'];
            $_SESSION['producer_id_you'] = $row['producer_id'];
            $auth_type = $row['auth_type'];
            $first_name = $row['first_name'];
            $last_name = $row['last_name'];
            $first_name_2 = $row['first_name_2'];
            $last_name_2 = $row['last_name_2'];
            $business_name = $row['business_name'];
            $pending = $row['pending'];            
            $_SESSION["username_m"] = $row['username_m'];
            $_SESSION["valid_m"] = $row['username_m'];
            $_SESSION["auth_type"] = $row['auth_type'];
          }
        include("../func/show_name.php");
        $_SESSION['show_name'] = $show_name;
        header($success_redirect);
        exit;
      }
    elseif ($row['pending'] == 1)
      {
        $msg = '<font color="red">Your account is being set up and you will be<br>unable to log in until it has been created.</font>';
      }
    elseif ($row['membership_discontinued'] == 1)
      {
        $msg = '<font color="red">Your account is currently inactive.<br>Please <a href="../contact.php">contact us</a> if you wish to reactivate your account.</font>';
      }
    else
      {
        $msg = '<font color="red">Login incorrect. Please re-enter your login information.';
      }
  }

$form_block = '
  <form method="post" action="'.$_SERVER['PHP_SELF'].$redirect_call.'" name="login">
    '.$msg.'

    <table>
        <tr><td>'.$font.'<b>Email&nbsp;address</b>:</td><td>
        <input type="text" name="username_m" size="20" maxlength="50">
        </td></tr>

        <tr><td>'.$font.'<b>Password</b>:</td><td>
        <input type="password" name="password" size="20" maxlength="25">
         </td></tr>

      <tr><td colspan="2" align="right">
        <input type="hidden" name="gp" value="ds">
        <input type="submit" name="submit" value="Log in"><br>
        <a href="reset_password.php"><font size="-2">Forgot your password?</font></a><br>
        <a href="member_form.php"><font size="-2">New to '.SITE_NAME.'?</font></a>
        </td></tr>
    </table>
  </form>
  ';

if ( $show_form == "yes" )
  {
    $display_block = $form_block;
  }

include("template_hdr_orders.php");?>

<h1>Welcome to <?php echo SITE_NAME; ?> - Log in</h1>

<div align="center">
  <table border="1" cellpadding="10" width="800">
    <tr>
      <td valign="center" align="center" width="50%" bgcolor="#DDDDDD">
        <p><?php echo $display_block; ?></p>
      </td>
      <td width="50%">
        <?php echo $font ?>
        <p>If you cannot remember the email address and password that you registered with,
        please email <a href="mailto:<?php echo MEMBERSHIP_EMAIL;?>"><?php echo MEMBERSHIP_EMAIL;?></a>.</p>
        <p>If you have your email address and password, but are having difficulty logging in,
        make sure your web browser has cookies enabled.
        If you need assistance, please email <a href="mailto:<?php echo HELP_EMAIL;?>"><?php echo HELP_EMAIL;?></a>.</p>
      </td>
    </tr>
  </table>
  <img src="../grfx/fgu_photo_strip_5.jpg" border="0" alt="<?php echo SITE_NAME; ?>">
</div>

<?php include("template_footer_orders.php");?>

<script language="javascript">
  document.login.username_m.focus();
</script>
