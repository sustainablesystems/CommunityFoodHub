<?php
$user_type = 'valid_m';
include_once ("config_foodcoop.php");
session_start();
require_once ('securimage.php');
// validate_user(); Do not validate because non-members must access this form


// SPECIAL NOTES ABOUT THIS PAGE: //////////////////////////////////////////////
//                                                                            //
// This page MAY be accessed by visitors without logging in.  If not          //
// logged-in, Information will need to be added to the form.  If properly     //
// logged in already then the form will be prefilled with the appropriate     //
// information and can be used to update that information.                    //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

/* Changes made for FGU:
 * - Not displaying first/last name 2
 * - Not displaying business name
 * - Removed "state" - now set automatically to "UK"
 * - "Zip" is now "Postcode"
 * - Removed "county" - now blank
 * - Removed all work address information - now blank
 * - Second email address now used to validate first email address
 * (we have phone numbers as alternative means of contact if the email fails)
 * - Removed "Do not send postal mail" checkbox (default is 0 - postal mail is ok)
 * - Further info about how member heard about us is now optional
 * - Put red asterisk by required fields
 * - Removed postal address completely
 * - Now just one phone number - "Contact phone number:"
 *  (this is the mobile number in the database)
 * - Removed home page
 * - Don't show membership types if there is only one
 * - Make the email the username (as per standard practice)
*/

// Set up the default action for this form (for the submit button)
if (! $_POST['action']) $action = 'Submit';

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                           PROCESS POSTED DATA                              //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Get data from the $_POST variable that pertain to BOTH Submit (new members) and Update (existing members)
if ($_POST['action'] == 'Submit' || $_POST['action'] == 'Update')
  {
    $business_name = stripslashes ($_POST['business_name']);
    $last_name = stripslashes ($_POST['last_name']);
    $first_name = stripslashes ($_POST['first_name']);
    $last_name_2 = stripslashes ($_POST['last_name_2']);
    $first_name_2 = stripslashes ($_POST['first_name_2']);
    $no_postal_mail = $_POST['no_postal_mail'];
    $address_line1 = stripslashes ($_POST['address_line1']);
    $address_line2 = stripslashes ($_POST['address_line2']);
    $city = stripslashes ($_POST['city']);
    $state = stripslashes ($_POST['state']);
    $zip = stripslashes ($_POST['zip']);
    $county = stripslashes ($_POST['county']);
    $work_address_line1 = stripslashes ($_POST['work_address_line1']);
    $work_address_line2 = stripslashes ($_POST['work_address_line2']);
    $work_city = stripslashes ($_POST['work_city']);
    $work_state = stripslashes ($_POST['work_state']);
    $work_zip = stripslashes ($_POST['work_zip']);
    $email_address = stripslashes ($_POST['email_address']);
    $email_address_2 = stripslashes ($_POST['email_address_2']);
    $home_phone = stripslashes ($_POST['home_phone']);
    $work_phone = stripslashes ($_POST['work_phone']);
    $mobile_phone = stripslashes ($_POST['mobile_phone']);
    $fax = stripslashes ($_POST['fax']);
    $toll_free = stripslashes ($_POST['toll_free']);
    $home_page = stripslashes ($_POST['home_page']);

    // VALIDATE THE DATA
    $error_array = array ();

    if ( !$first_name || !$last_name ) array_push ($error_array, 'First and last name are required');

    if ( !$mobile_phone ) array_push ($error_array, 'A contact phone number is required');

    if (!filter_var($email_address, FILTER_VALIDATE_EMAIL))
        array_push ($error_array, 'Enter a valid email address');

    if ($email_address != $email_address_2)
        array_push ($error_array, 'Email addresses do not match');

    /* Must check that the email/username doesn't already exist for BOTH new members and updates. */
    $query = 'SELECT * FROM '.TABLE_MEMBER.' WHERE username_m = "'
      .mysql_real_escape_string ($email_address).'"';

    $sql = @mysql_query($query, $connection) or die("You found a bug. <b>Error:</b> Check for existing account query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    $row_count = mysql_num_rows($sql);

    // Row count will be > 0 if we are updating e.g. the phone number,
    // as the record will already exist.  Therefore only act on row_count
    // if the email address is a new one.
    if ($row_count > 0 && $email_address != $_SESSION['valid_m'])
    {
      array_push ($error_array, 'The e-mail address "'.$email_address.'" is already in use');
    }
  }

// Get data from the $_POST variable that pertain ONLY to Submit (new members)
if ($_POST['action'] == 'Submit')
  {
    $password1 = stripslashes ($_POST['password1']);
    $password2 = stripslashes ($_POST['password2']);
    // Email as username
    $username_m = stripslashes ($_POST['email_address']);
    $how_heard = $_POST['how_heard'];
    $membership_type_id = $_POST['membership_type_id'];

    if ( strlen ($password1) < 6 )
      {
        array_push ($error_array, 'Passwords must be at least six characters long');
        $clear_password = true;
      }

    if ( $password1 != $password2 )
      {
        array_push ($error_array, 'Passwords do not match');
        $clear_password = true;
      }

    if ($clear_password === true)
      {
        $password1 = '';
        $password2 = '';
      }

    $captcha = new Securimage();
    if ($captcha->check($human_check) != true) array_push ($error_array, 'Enter the human validation text');

    if ( !$membership_type_id ) array_push ($error_array, 'Choose an account option');

    if ( !$affirmation ) array_push ($error_array, 'You must accept the Terms and Conditions to register');

    if ( !$how_heard_id ) array_push ($error_array, 'Let us know how you heard about '.SITE_NAME);
  }


// Assemble any errors encountered so far
if (count ($error_array) > 0) $error_message = '
  <p class="error_message">The information was not accepted. Please correct the following problems and
  resubmit.<ul class="error_list"><li>'.implode ("</li>\n<li>", $error_array).'</li></ul></p>';



////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                    GET MEMBER'S INFO FROM THE DATABASE                     //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Get member information from the database to pre-fill the form (only if first time through -- $_POST is unset)


if (!$_POST['action'] && $_SESSION['valid_m'])
  {
    $query = 'SELECT * FROM '.TABLE_MEMBER.' WHERE username_m = "'
      .$_SESSION['valid_m'].'"';

    $sql =  @mysql_query($query, $connection) or die("You found a bug. <b>Error:</b> Get member information query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    while ($row = mysql_fetch_object($sql))
      {
        $last_name = stripslashes ($row->last_name);
        $first_name = stripslashes ($row->first_name);
        $last_name_2 = stripslashes ($row->last_name_2);
        $first_name_2 = stripslashes ($row->first_name_2);
        $business_name = stripslashes ($row->business_name);
        $address_line1 = stripslashes ($row->address_line1);
        $address_line2 = stripslashes ($row->address_line2);
        $city = stripslashes ($row->city);
        $state = $row->state;
        $zip = $row->zip;
        $county = $row->county;
        $work_address_line1 = $row->work_address_line1;
        $work_address_line2 = $row->work_address_line2;
        $work_city = $row->work_city;
        $work_state = $row->work_state;
        $work_zip = $row->work_zip;
        $home_phone = $row->home_phone;
        $work_phone = $row->work_phone;
        $mobile_phone = $row->mobile_phone;
        $fax = $row->fax;
        $toll_free = $row->toll_free;
        $username_m = $row->username_m;
        $no_postal_mail = $row->no_postal_mail;
        $email_address = $row->email_address;
        $email_address_2 = $row->email_address_2;
        $home_page = $row->home_page;
        $how_heard_id = $row->how_heard_id;
        // Get whether the member is interested in producing/volunteering
        $volunteer = ($row->volunteer_interested == 1) ? 'yes' : '';
        $producer = ($row->producer_interested == 1) ? 'yes' : '';
        $username_m = $_SESSION['valid_m'];
        $action = 'Update';
      }
  }


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//  SET UP THE SELECT AND CHECKBOX FORMS FOR DISPLAY BASED UPON PRIOR VALUES  //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////


// Generate the membership_types_display and membership_types_options
$membership_types_options = '
      <option value="">Choose One</option>';
$query = '
  SELECT
    *
  FROM
    '.TABLE_MEMBERSHIP_TYPES.'
  WHERE 1';
$sql =  @mysql_query($query, $connection) or die("You found a bug. <b>Error:</b> Select Delivery Types Query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
// If only one membership type, set that to default
$member_types_count = mysql_numrows($sql);
if ($member_types_count == 1)
{
  while ($row = mysql_fetch_object($sql))
  {
    $membership_types_options = '<input type="hidden" value="'.$row->membership_type_id.'" name="membership_type_id"/>';
  }
}
else
{
  while ($row = mysql_fetch_object($sql))
    {
      if ($membership_type_id == $row->membership_type_id)
        {
          $selected = ' selected';
          $membership_type_text = $row->membership_description;
        }
      $membership_types_display .= '
        <dt>'.$row->membership_class.'</dt>
        <dd>'.$row->membership_description.'</dd>';
      $membership_types_options .= '
        <option value="'.$row->membership_type_id.'"'.$selected.'>'.$row->membership_class.'</option>';
    }
}

// Get how-heard select options
include_once('../func/how_heard.php');
$how_heard_options = get_how_heard_options($how_heard_id);

if ($affirmation == 'yes') $affirmation_check = ' checked';
if ($volunteer == 'yes') $volunteer_check = ' checked';
if ($producer == 'yes') $producer_check = ' checked';
if ($no_postal_mail == '1') $no_postal_mail_check = ' checked';


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                          DISPLAY THE INPUT FORM                            //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////


if (! $_SESSION['valid_m'])
  {
    $display_form_title .= '
      <h1>'.SITE_NAME.' Registration</h1>
      <div style="margin:auto;width:800px;padding:0em;">';

    $welcome_message = '
      <p><i>Thank you for your interest in registering with '.SITE_NAME.'.
      Our customers and suppliers are interested in food produced with
      sustainable practices that demonstrate good stewardship of the environment.';

    $display_form_top .= $welcome_message.'
      To register for an account, please read the <a href="'.TERMS_OF_SERVICE.'" target="_blank">
      Terms and Conditions</a>, and then complete the following information and click submit.</i></p>';

    $display_form_top .= '
      <p>If you are already registered, please <a href="orders_login.php?call='.$_SERVER['PHP_SELF'].'">log in here</a>.
        Otherwise fill out the form below to register for an account';
  }
else
  {
    $display_form_title .= '
      <h1>Update Contact Details</h1>
      <div style="margin:auto;width:800px;padding:0em;">';

    $display_form_top .= '
      <p>You can use the form below to update your account contact information';
  }
  
  $display_form_top .= ' (<font color="red">*</font> = required information).</p>';

$display_form_text .= '
  First name:    '.$first_name.'
  Last name:     '.$last_name.'

  Contact phone: '.$mobile_phone.'
    
  Email address:   '.$email_address.'
  Password: '.$password1.'

  Account type: '.$membership_type_text.'
  How you heard about '.SITE_NAME.': '.$how_heard_text.'
      More detail: '.$heard_other.'

  Interested in volunteering? '.$volunteer_check.'
  Interested in becoming a supplier? '.$producer_check.'

  Read the Terms and Conditions? '.$affirmation_check.'
  ';

$display_form_html .= $error_message.'
  <form action="'.$_SERVER['PHP_SELF'].'" name="delivery" method="post">

    <table cellspacing="15" cellpadding="2" width="100%" border="0" align="center">
      <tbody>

      <tr>
        <th class="memberform">General Information</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>First&nbsp;Name:</strong></td>
              <td><font color="red">*</font>&nbsp;<input maxlength="20" size="25" name="first_name" value="'.$first_name.'"></td>
              <td class="form_key"><strong>Last&nbsp;Name:</strong></td>
              <td><font color="red">*</font>&nbsp;<input maxlength="20" size="25" name="last_name" value="'.$last_name.'"></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <th class="memberform">Contact Information</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>Contact&nbsp;Phone:</strong></td>
              <td colspan="3">
                <font color="red">*</font>&nbsp;
                <input maxlength="20" size="25" name="mobile_phone" value="'.$mobile_phone.'">
                <i>Please enter a number that you can be reached on on collection days</i>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>Email&nbsp;Address:</strong></td>
              <td>
                <font color="red">*</font>&nbsp;
                <input maxlength="80" size="45" name="email_address" value="'.$email_address.'">
              </td>
            </tr>
            <tr>
              <td class="form_key"><strong>Repeat&nbsp;Email&nbsp;Address:</strong></td>
              <td>
                <font color="red">*</font>&nbsp;
                <input maxlength="80" size="45" name="email_address_2" value="'.$email_address_2.'">
              </td>
            </tr>
          </table>
        </td>
      </tr>';

if (! $_SESSION['valid_m']) // Do not show the following part to existing members....
  {
$display_form_html .= '
      <tr>
        <th class="memberform">Access to Ordering System</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>Password:</strong></td>
              <td colspan="2">
                <font color="red">*</font>&nbsp;
                <input type="password" maxlength="20" size="25" name="password1" value="'.$password1.'">
                <i>Your password is case sensitive</i>
              </td>
            </tr>
            <tr>
              <td class="form_key"><strong>Repeat Password:</strong></td>
              <td colspan="2">
                <font color="red">*</font>&nbsp;
                <input type="password" maxlength="20" size="25" name="password2" value="'.$password2.'">
              </td>
            </tr>
            <tr>
              <td class="form_key"><strong>Enter the following security word:</strong></td>
              <td>
                <font color="red">*</font>&nbsp;
                <input maxlength="10" size="10" name="human_check" value="">                
              </td>
              <td><img src="securimage_show.php?sid='.time().'" alt="Human validation text"></td>
            </tr>
          </table>
        </td>
      </tr>';

// If only one membership type, don't show this section of the form
if ( $member_types_count == 1 )
{
  $display_form_html .= $membership_types_options;
}
else // More than one membership type
{
  $display_form_html .= '
      <tr>
        <th class="memberform">Account Type</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key" rowspan="2" valign="top"><strong>Account&nbsp;Type:</strong></td>
              <td>
                <font color="red">*</font>&nbsp;
                <select name="membership_type_id">'.$membership_types_options.'</select>
              </td>
            </tr>
            <tr>
              <td>
                <dl>
                  '.$membership_types_display.'
                </dl>
              </td>
            </tr>
          </table>
        </td>
      </tr>';
}
  
  $display_form_html .= '
      <tr>
        <th class="memberform">Additional Information</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key" valign="top"><strong>How&nbsp;you&nbsp;heard&nbsp;about '.SITE_NAME.':</strong></td>
              <td>
                <font color="red">*</font>&nbsp;
                <select name="how_heard_id">'.$how_heard_options.'</select>
              </td>
            </tr>
            <tr>
              <td class="form_key" valign="top"><strong>Please&nbsp;give&nbsp;more&nbsp;detail:</strong></td>
              <td>
                <input maxlength="50" size="25" name="heard_other" value="'.$heard_other.'"><br>
                What website, from whom, which publication/date, etc.?
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td><font color="red">*</font>&nbsp;<input type="checkbox" name="affirmation" value="yes"'.$affirmation_check.'>
          I acknowledge that I have read and understand the '.SITE_NAME.' <a href="'.TERMS_OF_SERVICE.'" target="_blank">
          Terms and Conditions</a>.
        </td>
      </tr>';
  }

$display_form_html .= '
      <tr>
        <td><input type="checkbox" name="volunteer" value="yes"'.$volunteer_check.'> YES! I&lsquo;m interested in volunteering to help '.SITE_NAME.'.</td>
      </tr>
      <tr>
        <td><input type="checkbox" name="producer" value="yes"'.$producer_check.'> I am interested in supplying '.SITE_NAME.'.</td>
      </tr>

      <tr>
        <td align="center">
          <input type="submit" name="action" value="'.$action.'">
        </td>
      </tr>
      </tbody>
    </table>
  </form>';


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//         ADD OR CHANGE INFORMATION IN THE DATABASE FOR THIS MEMBER          //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// If everything validates, then we can post to the database...
if (count ($error_array) == 0 && $_POST['action'] == 'Submit') // For new members
  {
    // Everything validates correctly so do the INSERT and send the EMAIL

    // Begin by getting this member's pending status based upon the membership_type_id
    $query = '
      SELECT
          pending,
          initial_cost
        FROM
          membership_types
        WHERE membership_type_id = "'.$membership_type_id.'"';
    $result = @mysql_query($query, $connection) or die("You found a bug. <b>Error:</b>
        Member insert Query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if ($row = mysql_fetch_object($result))
      {
        $pending = $row->pending;
        $initial_cost = $row->initial_cost;
      }

    // Then do the database insert with the relevant membership data
    $query = '
      INSERT INTO
        '.TABLE_MEMBER.'
      SET
        pending = "'.mysql_escape_string ($pending).'",
        auth_type = "member",
        membership_type_id = "'.mysql_escape_string ($membership_type_id).'",
        membership_date = now(),
        username_m = "'.mysql_escape_string ($email_address).'",
        password = md5("'.mysql_escape_string ($password1).'"),
        last_name = "'.mysql_escape_string ($last_name).'",
        first_name = "'.mysql_escape_string ($first_name).'",
        last_name_2 = "'.mysql_escape_string ($last_name_2).'",
        first_name_2 = "'.mysql_escape_string ($first_name_2).'",
        business_name = "'.mysql_escape_string ($business_name).'",
        address_line1 = "'.mysql_escape_string ($address_line1).'",
        address_line2 = "'.mysql_escape_string ($address_line2).'",
        city = "'.mysql_escape_string ($city).'",
        state = "'.mysql_escape_string ($state).'",
        zip = "'.mysql_escape_string ($zip).'",
        county = "'.mysql_escape_string ($county).'",
        work_address_line1 = "'.mysql_escape_string ($work_address_line1).'",
        work_address_line2 = "'.mysql_escape_string ($work_address_line2).'",
        work_city = "'.mysql_escape_string ($work_city).'",
        work_state = "'.mysql_escape_string ($work_state).'",
        work_zip = "'.mysql_escape_string ($work_zip).'",
        home_phone = "'.mysql_escape_string ($home_phone).'",
        work_phone = "'.mysql_escape_string ($work_phone).'",
        mobile_phone = "'.mysql_escape_string ($mobile_phone).'",
        fax = "'.mysql_escape_string ($fax).'",
        toll_free = "'.mysql_escape_string ($toll_free).'",
        email_address = "'.mysql_escape_string ($email_address).'",
        email_address_2 = "'.mysql_escape_string ($email_address_2).'",
        home_page = "'.mysql_escape_string ($home_page).'",
        how_heard_id = "'.mysql_escape_string ($how_heard_id).'",
        no_postal_mail = "'.mysql_escape_string ($no_postal_mail).'",
        volunteer_interested = "'.($volunteer == 'yes' ? 1 : 0).'",
        producer_interested = "'.($producer == 'yes' ? 1 : 0).'"';

    $result = @mysql_query($query,$connection) or die("You found a bug. <b>Error:</b>
        Member insert Query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

    $member_id= mysql_insert_id();

    // Then do the database insert with the transaction information (membership receivables)
    $query = '
      INSERT INTO
        '.TABLE_TRANSACTIONS.'
          (
            transaction_type,
            transaction_name,
            transaction_amount,
            transaction_user,
            transaction_member_id,
            transaction_delivery_id,
            transaction_taxed,
            transaction_comments,
            transaction_timestamp
          )
        VALUES
          (
            "24",
            "Membership Receivables",
            "'.$initial_cost.'",
            "member_form",
            "'.$member_id.'",
            (SELECT delivery_id FROM '.TABLE_CURDEL.'),
            "0",
            "'.$comments.'",
            now()
          )';

    $result = @mysql_query($query,$connection) or die("You found a bug. <b>Error:</b>
        Member insert Query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

    // Figure out what sort of "welcome" to give the new member...
    if ($pending == 1)
      {
        $membership_disposition = '
          <p class="error_message" align="center">Your account number will be #'.$member_id.'.  Your account
          application will be reviewed by an administrator and you will be notified when it becomes
          active.  Until then, you will not be able to log in.</p>';
      }
    else // Pending = 0
      {
        $membership_disposition = '
          <p class="error_message" align="center">Your account number is #'.$member_id.'.  Your account has
          been automatically activated and you may <a href="'.BASE_URL.PATH.'members/orders_login.php">
          log in</a> immediately.</p>';
      }

    if ($initial_cost > 0) $membership_disposition .= '
      <p class="error_message">Please send your membership payment of '.CURSYM.number_format ($initial_cost, 2).' to:<br><br>
      '.SITE_MAILING_ADDR.'</p>';
    if ( PAYPAL_EMAIL && $initial_cost > 0 ) $membership_disposition .= '
      <p class="error_message">Or make a payment online through PayPal (opens in a new window)
      <form target="paypal" method="post" action="https://www.paypal.com/cgi-bin/webscr">
      <input type="hidden" value="_xclick" name="cmd">
      <input type="hidden" value="'.PAYPAL_EMAIL.'" name="business">
      <input type="hidden" name="amount" value="'.number_format ($initial_cost, 2).'">
      <input type="hidden" value="Membership payment for #'.$member_id.' '.$first_name.' '.$last_name.' : '.$business_name.'" name="item_name">
      <input type="image" border="0" alt="Make payment with PayPal" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_paynowCC_LG.gif">
      </form></p>';

    $display_form_message .= $membership_disposition;

    // Now send email notification(s)
    $email_to = preg_replace ('/SELF/', $email_address, MEMBER_FORM_EMAIL);
    $email_subject = 'Welcome to '.SITE_NAME.' - '.$first_name.' '.$last_name.' (#'.$member_id.')';
    $boundary = uniqid();
    // Set up the email preamble...
    $email_preamble = '
      <p>Welcome to '.SITE_NAME.'! Following is a copy of the registration information you submitted to '.SITE_NAME.'.
        Your registration is complete and no further action is required.</p>';
    $email_preamble .= $membership_disposition.$welcome_message;

    // Disable all form elements for emailing
    $html_version = $email_preamble.preg_replace ('/<(input|select|textarea)/', '<\1 disabled', $display_form_html);

    $html_version = str_replace(array('type="reset"','type="password"'), '', $html_version);

    $email_headers  = "From: ".MEMBERSHIP_EMAIL."\n";
    $email_headers .= "Reply-To: ".MEMBERSHIP_EMAIL."\n";
    $email_headers .= "Errors-To: ".WEBMASTER_EMAIL."\n";
    $email_headers .= "MIME-Version: 1.0\n";
    $email_headers .= "Content-type: multipart/alternative; boundary=\"$boundary\"\n";
    $email_headers .= "Message-ID: <".md5(uniqid(time()))."@".DOMAIN_NAME.">\n";
    $email_headers .= "X-Mailer: PHP ".phpversion()."\n";
    $email_headers .= "X-Priority: 3\n";
    $email_headers .= "X-AntiAbuse: This is a machine-generated response to a user-submitted form at ".SITE_NAME.".\n\n";

    $email_body .= "--".$boundary."\n";
    $email_body .= "Content-Type: text/plain; charset=us-ascii\n\n";
    $email_body .= strip_tags ($email_preamble).$display_form_text."\n";
    $email_body .= "--".$boundary."\n";
    $email_body .= "Content-Type: text/html; charset=us-ascii\n\n";
    $email_body .= $html_version."\n";
    $email_body .= "--".$boundary."--\n";
    
    mail ($email_to, $email_subject, $email_body, $email_headers);
    $email_sent = true;
  }

elseif (count ($error_array) == 0 && $_POST['action'] == 'Update') // For existing members
  {
    // Everything validates correctly so do the INSERT and send the EMAIL
    $query = '
      UPDATE
        '.TABLE_MEMBER.'
      SET
        last_name = "'.mysql_escape_string ($last_name).'",
        first_name = "'.mysql_escape_string ($first_name).'",
        last_name_2 = "'.mysql_escape_string ($last_name_2).'",
        first_name_2 = "'.mysql_escape_string ($first_name_2).'",
        business_name = "'.mysql_escape_string ($business_name).'",
        address_line1 = "'.mysql_escape_string ($address_line1).'",
        address_line2 = "'.mysql_escape_string ($address_line2).'",
        city = "'.mysql_escape_string ($city).'",
        state = "'.mysql_escape_string ($state).'",
        zip = "'.mysql_escape_string ($zip).'",
        county = "'.mysql_escape_string ($county).'",
        work_address_line1 = "'.mysql_escape_string ($work_address_line1).'",
        work_address_line2 = "'.mysql_escape_string ($work_address_line2).'",
        work_city = "'.mysql_escape_string ($work_city).'",
        work_state = "'.mysql_escape_string ($work_state).'",
        work_zip = "'.mysql_escape_string ($work_zip).'",
        home_phone = "'.mysql_escape_string ($home_phone).'",
        work_phone = "'.mysql_escape_string ($work_phone).'",
        mobile_phone = "'.mysql_escape_string ($mobile_phone).'",
        fax = "'.mysql_escape_string ($fax).'",
        toll_free = "'.mysql_escape_string ($toll_free).'",
        email_address = "'.mysql_escape_string ($email_address).'",
        email_address_2 = "'.mysql_escape_string ($email_address_2).'",
        home_page = "'.mysql_escape_string ($home_page).'",
        no_postal_mail = "'.mysql_escape_string ($no_postal_mail).'",
        volunteer_interested = "'.($volunteer == 'yes' ? 1 : 0).'",
        producer_interested = "'.($producer == 'yes' ? 1 : 0).'",
        username_m = "'.mysql_escape_string ($email_address).'"
      WHERE
        username_m = "'.mysql_escape_string ($username_m).'"';

    $result = @mysql_query($query,$connection) or die("You found a bug. <b>Error:</b>
        Member insert Query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    $display_form_message = '
      <p class="error_message">Your registration information has been successfully updated.<br><br></p>';

    // Update the session username and display name appropriately (it might have changed)
    $username_m = $email_address;
    $_SESSION["username_m"] = $_SESSION["valid_m"] = $username_m;
    include("../func/show_name.php");
    $_SESSION['show_name'] = $show_name;
  }

// Temporarily disabling producer registration until we know how we want to do this
/*if ($producer == 'yes' && count ($error_array) == 0)
  {
    // Get the member_id from members.username_m
    $query = '
      SELECT
        member_id
      FROM
        '.TABLE_MEMBER.'
      WHERE
        username_m = "'.$username_m.'"';
    $sql = @mysql_query($query, $connection) or die("You found a bug. <b>Error:</b>
      Member insert Query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if ($row = mysql_fetch_object($sql)) $member_id = $row->member_id;
    $_SESSION['member_id'] = $member_id;
    $_SESSION['business_name'] = $business_name;
    $_SESSION['website'] = $home_page;
    $display_form_message .= '
      <p class="error_message">You also expressed interest in becoming a producer member.</p>
      <p class="error_message">You can access the <a href="producer_form.php">producer
      registration form</a> immediately or you can return later to complete the form.
      It is a lengthy form  and you may wish to print it out prior to filling it out online.<br><br></p>';
//     header( "Location: producer_form.php?action=from_member_form");
  }*/
  
include ("template_hdr_orders.php");
echo $display_form_title;
echo $display_form_message;
if ( !$email_sent )
  {
    echo $display_form_top;
    echo $display_form_html;
  }
echo '</div>';

include("template_footer_orders.php");
