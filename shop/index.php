<?php
include_once('config_foodcoop.php');
include_once('func/delivery_funcs.php');

// Ensure that any existing session is destroyed if the user returns
// to the main log on page

// Initialize the session.
// If you are using session_name("something"), don't forget it now!
session_start();

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

$sql3 = '
  SELECT
    DATE_FORMAT(date_closed, "%M")          AS month,
    DATE_FORMAT(date_open, "%W, %e %M %Y")     AS date_open,
    DATE_FORMAT(date_closed, "%a, %e %b %Y")   AS date_closed,
    delivery_date,
    order_cycle_closed
  FROM
    '.TABLE_CURDEL;

$result3 = @mysql_query($sql3, $connection) or die(mysql_error() . "<br><b>1 Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($result3) )
  {
    $month = $row['month'];
    $date_open = $row['date_open'];
    $date_closed = $row['date_closed'];
    $delivery_date = $row['delivery_date'];
    $date_closed_disp = $row['order_cycle_closed'];
  }

// Get information for collection locations
$delcodes = array();      $deldates = array();
$pickup_times = array();  $deldescs = array();

$collections_count = get_deliveries($delivery_date,
        $delcodes, $deldates, $pickup_times, $deldescs);

// Generate table rows for each collection location/time
for ( $del_loop = 0; $del_loop < $collections_count; $del_loop++ )
{
  $collections_disp .= '<b>' . $delcodes[$del_loop] . '<br>';
  $collections_disp .= '<font color="#770000">' . $deldates[$del_loop]
    . ' -- ' . $pickup_times[$del_loop] . '</font></b><br>';
}

// Create the parts of the screen that differ according to
// whether or not we're logged on
// THIS SHOULD NOW NEVER GET CALLED AS WE ALWAYS LOGOUT BEFORE GETTING HERE
if ($_SESSION['valid_m'])
{
  $top_right_disp .= '    
      <b>You are currently logged in as:<br>'.$_SESSION['show_name'].'<br><br>
      <a href="members/member_form.php">Update Contact Details</a><br>
      <a href="members/reset_password.php">Change Password</a><br>
      <a href="members/logout.php">Logout</a></b>';
}
else // Logged out
{
  $top_right_disp .= '
      <form method="post" action="members/orders_login.php" name="login">
        <table>
          <tr>
            <td><b>Email address</b>:</td>
            <td>
              <input type="text" name="username_m" value="'.$username_m.'" size="20" maxlength="50">
            </td>
          </tr>
          <tr>
            <td><b>Password</b>:</td>
            <td>
              <input type="password" name="password" size="20" maxlength="25">
            </td>
          </tr>
          <tr>
            <td colspan="2" align="right">
              <input type="hidden" name="gp" value="ds">
              <input type="submit" name="submit" value="Log in to Order"><br>
              <a href="members/reset_password.php"><font size="-2">Forgot your password?</font></a><br>
              <a href="members/member_form.php"><font size="-2">New to '.SITE_NAME.'?</font></a>
            </td>
          </tr>
        </table>
      </form>';
}

?>

<?php include("template_hdr.php"); ?>

  <!-- CONTENT BEGINS HERE -->

  <h1>Welcome to <?php echo SITE_NAME; ?>&#146;s shop!</h1>

<div align="center">
<table width="800" border="0">
<tr>
<td>


  <table width="100%" cellpadding="10" cellspacing="2" border="1" bordercolor="#000000">
    <tr>
      <td align="left" rowspan="2"><?php echo $font;?>
        <?php
        // Hide "How to Join" link if the user is already logged in
        // THIS SHOULD NOW NEVER GET CALLED AS WE ALWAYS LOGOUT BEFORE GETTING HERE
        if (!$_SESSION['valid_m'])
        {
          echo '<a href="members/member_form.php"><b>Register&nbsp;for&nbsp;account</b></a><br><br>';
        }
        ?>
        <a href="category_list_full.php">
        <b>Browse&nbsp;produce</b></a><br>
        <a href="<?php echo LOCATIONS_PAGE ?>">
        <b>Collection&nbsp;locations</b></a><br>
        <a href="<?php echo COOP_PRODUCERS_PAGE ?>">
        <b>Suppliers</b></a><br>
        <a href="<?php echo HOMEPAGE_URL ?>">
        <b>About us</b></a>
      </td>
      <td bgcolor="#DDDDDD" align="center" style="white-space:nowrap;"><?php echo $font;?>
        <b>Next Collection<?php if ($collections_count > 1) echo "s";?></b>
      </td>
      <td bgcolor="#AEDE86" align="center" rowspan="2">
        <?php echo $font.$top_right_disp; ?>
      </td>
    </tr>
    <tr>
      <td align="center" valign="top" style="white-space:nowrap;"><?php echo $font;?>
        <table cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td align="left" colspan="2"><?php echo $collections_disp; ?></td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tr>
            <td align="left"><b>Order&nbsp;Opens:&nbsp;&nbsp;</b></td>
            <td align="left"><b><font color="#770000"><?php echo $date_open; ?></font></b></td>
          </tr>
          <tr>
            <td align="left"><b>Order&nbsp;Closes:&nbsp;&nbsp;</b></td>
            <td align="left"><b><font color="#770000"><?php echo $date_closed_disp; ?></font></b></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  <img src="grfx/fgu_photo_strip_5.jpg" border="0" alt="<?php echo SITE_NAME; ?>"><br>
  <table width="100%" cellpadding="10" cellspacing="2" border="1" bordercolor="#000000">
    <tr>
      <td bgcolor="#DDDDDD" align="center" style="white-space:nowrap;"><?php echo $font;?>
        <b>Available Products</b>
      </td>
      
      <td bgcolor="#DDDDDD" align="center" style="white-space:nowrap;"><?php echo $font;?>
        <b>Future Activities</b>
      </td>
    </tr>
    <tr>
      <td align="left" valign="middle"><?php echo $font;?>

        <a href="category_list_full.php#Organic%20Boxes">
        <b>Veg and fruit boxes</b></a><br>

        <a href="category_list_full.php">
        <b>Products by category</b></a>

<?php if ( USE_HTMLDOC ) {  // Don't show pdf options if htmldoc is not available ?>

        <br><br><a href="pdf/all.pdf">
        <img src="<?php echo DIR_GRAPHICS ?>icon_pdf.gif" width="12" height="13" hspace="3" alt="PDF" border="0" align="left"></a>
        <a href="pdf.php?list=<?php echo base64_encode("all");?>">
        <b>Printable Product List</b></a><br><br>
<?php } ?>

      </td>

      <td align="center">
        <table cellpadding="0" cellspacing="0" border="0">
          <tr><td>
            <iframe src="<?php echo CALENDAR_URL_HOME; ?>" style=" border-width:0 " width="600" height="250" frameborder="0" scrolling="no"></iframe>
          </td></tr>
        </table>
      </td>
      
    </tr>
  </table>

  <table width="100%" cellpadding="3" cellspacing="0" border="0">
    <tr>
      <td align="left" valign="top"><?php echo $font;?>
        <b>Note:</b> You must <A href="members/member_form.php">register</A> with <?php echo SITE_NAME; ?>
        to purchase food through the <?php echo ORGANIZATION_TYPE; ?>.
      </td>
      <td align="right"><?php echo $font;?>
        Supported by: <a href="http://www.esmeefairbairn.org.uk" target="_new">
        <img src="<?php echo DIR_GRAPHICS; ?>EF_logo_4col_small.jpg" border="0" align="top"/></a>
      </td>
    </tr>
  </table>

</td>
</tr>
</table>

</div>

  <!-- CONTENT ENDS HERE -->

<?php include("template_footer.php");?>

<script language="javascript">
  document.login.username_m.focus();
</script>
