<?php

// Find out if this order has previously been submitted
$sql = 'SELECT
          '.TABLE_BASKET_ALL.'.submitted,
          '.TABLE_BASKET_ALL.'.member_id,
          '.TABLE_BASKET_ALL.'.delivery_id,
          '.TABLE_MEMBER.'.last_name,
          '.TABLE_MEMBER.'.first_name
        FROM
          '.TABLE_BASKET_ALL.'
        INNER JOIN '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
        WHERE
          basket_id = '.$basket_id;
$result = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$row = mysql_fetch_array($result);

$basket_member_id = $row['member_id'];
$basket_delivery_id = $row['delivery_id'];
$member_name = stripslashes($row['last_name']).', '.stripslashes($row['first_name']);

// Deal with requests to submit or cancel the order
if ($process_submit == "true")
{
  // Submit order
  if ($action == "Submit Order")
  {
    $sqlu = '
      UPDATE
        '.TABLE_BASKET_ALL.'
      SET
        submitted = 1
      WHERE
        basket_id = '.$basket_id;
    $result = @mysql_query($sqlu,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

    if ($admin_submit)
    {
      $submit_message = "<b>This order has been submitted. "
        .$member_name." will receive a confirmation email shortly.</b>";
    }
    else
    {
      $submit_message = "<b>Your order has been submitted. "
        ."You will receive a confirmation email in a few minutes.</b>";
    }
    
    // Send order confirmation email
    include("../func/gen_invoice.php");
    $is_final = false;
    $email_html_text = geninvoice($basket_member_id, $basket_id, 
            $basket_delivery_id, "members", true, $is_final);
    $email_plain_text = geninvoice($basket_member_id, $basket_id, 
            $basket_delivery_id, "members", false, $is_final);
    $email_subject = SITE_NAME.' order confirmation - '.$member_name;

    send_order_confirmation($basket_member_id, $email_html_text, $email_plain_text, $email_subject);

    $already_submitted = true;
  }

  // Cancel order - delete the entire order and return to the shopping homepage
  else if ($action == "Cancel Order")
  {
    $sqld = 'DELETE FROM '.TABLE_BASKET.' WHERE basket_id = '.$basket_id;
    $resultdelete = @mysql_query($sqld,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

    $sqld = 'DELETE FROM '.TABLE_BASKET_ALL.' WHERE basket_id = '.$basket_id;
    $resultdelete = @mysql_query($sqld,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    
    $already_submitted = false;

    // Send the user back to the shopping homepage to start again
    if ($admin_submit)
    {
      header('Location: http://'.DOMAIN_NAME.PATH.'admin/orders_list.php?delivery_id='.$basket_delivery_id, true, 303);
    }
    else
    {
      header('Location: http://'.DOMAIN_NAME.PATH.'members/index.php', true, 303);
    }
    
    exit;
  }
}

$display_submit = '
  <a name="submit"/>
  <form method="post" action="#checkout">
    <input type="hidden" name="process_submit" value="true">
    <input type="hidden" name="basket_id" value="'.$basket_id.'">';

if ($admin_submit)
{
  $display_submit .= '<input type="hidden" name="member_id" value="'.$basket_member_id.'">';
  $display_submit .= '<input type="hidden" name="delivery_id" value="'.$basket_delivery_id.'">';
}

if ( !$already_submitted )
{
  /*$display_submit .= '
    <input name="action" type="image" src="../grfx/completeyourorder.gif"
    value="Submit Order" alt="Complete Your Order"
    style="vertical-align:middle;border:10px solid transparent">&nbsp;';*/
  $display_submit .= '
    <input name="action" type="submit" value="Submit Order">';
}
$display_submit .= '
    <input name="action" type="submit" value="Cancel Order">
  </form>';

$display_submit .= '<p align="center"><font face="arial" size="+0" color="#770000">';

if (isset($submit_message))
{
  $display_submit .= $submit_message.'<br>';
}

if ($already_submitted && !$admin_submit)
{
  $display_submit .= '
    <a href="orders_invoice.php?final=false" target="_new">
      <b>View provisional invoice (opens in new window)</b>
    </a>';
}

$display_submit .= '</font></p>';


function send_order_confirmation($member_id, $email_html_text, $email_plain_text, $email_subject)
{
  global $connection;
  
  // Get member information
  $sql = 'SELECT
            email_address
          FROM
            '.TABLE_MEMBER.'
          WHERE
            member_id = '.$member_id;
  $result = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
  $row = mysql_fetch_array($result);
  $email_address = $row['email_address'];
  
  // Now send email notification(s)
  $email_to = $email_address.','.ORDER_EMAIL;
  
  $boundary = uniqid();
  $email_headers  = "From: ".ORDER_EMAIL."\n";
  $email_headers .= "Reply-To: ".ORDER_EMAIL."\n";
  $email_headers .= "Errors-To: ".WEBMASTER_EMAIL."\n";
  $email_headers .= "MIME-Version: 1.0\n";
  $email_headers .= "Content-type: multipart/alternative; boundary=\"$boundary\"\n";
  $email_headers .= "Message-ID: <".md5(uniqid(time()))."@".DOMAIN_NAME.">\n";
  $email_headers .= "X-Mailer: PHP ".phpversion()."\n";
  $email_headers .= "X-Priority: 3\n";
  $email_headers .= "X-AntiAbuse: This is a machine-generated response to a user-submitted form at http://".DOMAIN_NAME.PATH.".\n\n";

  $email_body .= "--".$boundary."\n";
  $email_body .= "Content-Type: text/plain; charset=us-ascii\n\n";
  $email_body .= $email_plain_text."\n";
  $email_body .= "--".$boundary."\n";
  $email_body .= "Content-Type: text/html; charset=us-ascii\n\n";
  $email_body .= $email_html_text."\n";
  $email_body .= "--".$boundary."--\n";

  mail($email_to, $email_subject, $email_body, $email_headers);
}
?>
