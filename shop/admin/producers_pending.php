<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();
include("template_hdr.php");

echo '
<style type="text/css"><!--
table, td, th
  {
    border: 1px solid #CCCCCC;
  }
--></style>';
if ( $_POST['pending'] )
  {
    foreach( $_POST['pending'] as $producer_id=>$value )
      {
        $query = '
          SELECT
            '.TABLE_MEMBER.'.business_name,
            '.TABLE_MEMBER.'.first_name,
            '.TABLE_MEMBER.'.last_name,
            '.TABLE_MEMBER.'.email_address,
            '.TABLE_MEMBER.'.member_id,
            '.TABLE_MEMBER.'.auth_type
          FROM
            '.TABLE_PRODUCER.'
          LEFT JOIN '.TABLE_MEMBER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
          WHERE
            producer_id = "'.mysql_real_escape_string($producer_id).'"';
        $sql = mysql_query($query, $connection) or die("Couldn't execute query 4.");
        $producer_info = mysql_fetch_object($sql);
        if ( $value == 'approve' )
          {
            $query = '
              UPDATE
                '.TABLE_PRODUCER.'
              SET
                pending="0",
                donotlist_producer="0"
              WHERE
                producer_id="'.mysql_real_escape_string($producer_id).'"';
            $sql = mysql_query($query);

            // Now send the confirmation email...

            if ( $producer_info )
              {
                // Now send the "Newly Activated" email notice
                $subject  = 'Account status: '.SITE_NAME;
                $email_to = preg_replace ('/SELF/', $producer_info->email_address, PRODUCER_FORM_EMAIL);
                $headers  = "From: ".MEMBERSHIP_EMAIL."\nReply-To: ".MEMBERSHIP_EMAIL."\n";
                $headers .= "Errors-To: ".WEBMASTER_EMAIL."\n";
                $headers .= "MIME-Version: 1.0\n";
                $headers .= "Content-type: text/plain; charset=us-ascii\n";
                $headers .= "Message-ID: <".md5(uniqid(time()))."@".DOMAIN_NAME.">\n";
                $headers .= "X-Mailer: PHP ".phpversion()."\n";
                $headers .= "X-Priority: 3\n";
                $headers .= 'X-AntiAbuse: This is a user-submitted email through the '.SITE_NAME." producer approval page.\n\n";
                $msg  = "Dear ".stripslashes($producer_info->first_name)." ".stripslashes($producer_info->last_name).",\n\n";
                $msg .= "Your producer account with ".SITE_NAME." has been activated. \n\n";
                $msg .= "When you log in to your regular member account, you will have a new section relating to ";
                $msg .= "producer functions. You may immediately begin adding new products to the system but they ";
                $msg .= "will not be available for ordering until an order is open.  If, for some reason, you need ";
                $msg .= "to change a product listing during an order cycle, you will need to contact one of the site ";
                $msg .= "administrators at the Producer help address below to make your changes \"live\".  Until that ";
                $msg .= "step is completed, your products and any changes you make will not show up on the public ";
                $msg .= "shopping pages (except changes to your inventory).\n\n";
                $msg .= "Producer help is available at: ".PRODUCER_CARE_EMAIL."\n";
                $msg .= "Other help is always available at: ".HELP_EMAIL."\n";
                $msg .= "Join in the fun, volunteer! ".VOLUNTEER_EMAIL."\n\n";
                $msg .= "If I can be of any help to you or you have any questions, please contact me. \n\n";
                $msg .= AUTHORIZED_PERSON."\n";
                $msg .= MEMBERSHIP_EMAIL;
                mail($email_to, $subject, $msg, $headers);
              }
            echo '&nbsp;<b>'.$producer_info->business_name.'</b> ('.$producer_id.') was updated.<br />';
          }
        else if ( $value == "remove" )
          {
            $query = '
              DELETE FROM
                '.TABLE_PRODUCER.'
              WHERE
                producer_id="'.mysql_real_escape_string($producer_id).'"';
            mysql_query($query);
            
            $query = '
              DELETE FROM
                '.TABLE_PRODUCER_REG.'
              WHERE
                producer_id="'.mysql_real_escape_string($producer_id).'"';
            mysql_query($query);
            
            if ( $producer_info )
              {
                //remove "producer" from auth_type
                $auth_type = explode(",", $producer_info->auth_type);
                foreach(array_keys($auth_type, 'producer') as $key) unset($auth_type[$key]);
                $auth_type = implode(",", $auth_type);
                $query = '
                  UPDATE
                    '.TABLE_MEMBER.'
                  SET
                    auth_type="'.mysql_real_escape_string($auth_type).'"
                  WHERE
                    member_id="'.mysql_real_escape_string($producer_info->member_id).'"';
                  mysql_query($query);
              }
            echo '&nbsp;<b>'.$producer_info->business_name.'</b> ('.$producer_id.') was removed.<br />';
          }
      }
  }
$display = '';
$query = '
  SELECT
    producer_id,
    business_name,
    first_name,
    last_name,
    p.member_id,
    mobile_phone,
    email_address
  FROM
    '.TABLE_PRODUCER.' p,
    '.TABLE_MEMBER.' m
  WHERE
    p.pending != "0"
    AND p.member_id = m.member_id';
$sql = mysql_query($query);
while ( $row = mysql_fetch_array($sql) )
  {
    $display .= '
      <tr>
        <td>
          <input type="radio" name="pending['.$row['producer_id'].']" value="" checked>Pending<br>
          <input type="radio" name="pending['.$row['producer_id'].']" value="approve">Approve<br>
          <input type="radio" name="pending['.$row['producer_id'].']" value="remove">Remove
        </td>
        <td><b>'.$row['producer_id'].'</b></td>
        <td><a href="'.PATH.'prdcr_display_quest.php?pid='.$row['producer_id'].'" target="_blank">'.stripslashes($row['business_name']).'</a></td>
        <td>'.stripslashes($row['first_name']).'</td>
        <td>'.stripslashes($row['last_name']).'</td>
        <td>'.$row['mobile_phone'].'</td>
        <td><a href="mailto:'.$row['email_address'].'">'.$row['email_address'].'</a></td>
        <td>'.$row['member_id'].'</td>
      </tr>
    ';
  }
if ( !$display )
  {
    $display = '
      <tr>
        <td colspan="8" align="right">There are no pending producers.</td>
      </tr>';
  }
else
  {
    $display .= '
      <tr>
        <td colspan="8" align="center"><input type="submit" name="submit" value="Submit"></td>
      </tr>';
  }
echo '
  <div align="center">
  <h2>Pending Producers</h2>
  <form name="pendingproducers" method="POST">
  <table>
    <tr>
      <th>Status</th>
      <th>Producer ID</th>
      <th>Business Name<br>
        (view questionnaire)</th>
      <th>First Name</th>
      <th>Last Name</th>
      <th>Phone</th>
      <th>Email</th>
      <th>Member ID</th>
    </tr>
    '.$display.'
  </table>
  </form>
  </div>
  <br /><br />
  ';
include("template_footer.php");
?>