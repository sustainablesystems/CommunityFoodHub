#!/usr/bin/php5

<?php

/* This script checks the current orders, and if there are any orders (baskets)
 * that were opened but have not yet been submitted (either empty or not) it
 * emails a reminder to those members.  The member is free to ignore this -
 * probably they didn't intend to order anyway, but occasionally they will have
 * forgotten to submit it or may not have understood that they needed to do that.
 *
 * This script is intended to be run as a scheduled task (cron job).
 */

include_once ("../../local_food_include/config_foodcoop.php");

// Get delivery ID and closing time
$sql = '
  SELECT
    delivery_id,
    order_cycle_closed
  FROM
    '.TABLE_CURDEL;
$rs = @mysql_query($sql,$connection) or die(mysql_error());

if( $row = mysql_fetch_array($rs) )
{
  $delivery_id = $row['delivery_id'];
  $cycle_closes = $row['order_cycle_closed'];

  // Get the member names and emails of people who have unsubmitted baskets
  // (Note: these baskets may be empty)
  $sql = '
    SELECT
      '.TABLE_MEMBER.'.last_name,
      '.TABLE_MEMBER.'.first_name,
      '.TABLE_MEMBER.'.email_address
    FROM
      '.TABLE_MEMBER.'
    INNER JOIN
      '.TABLE_BASKET_ALL.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
    WHERE
      '.TABLE_BASKET_ALL.'.delivery_id = '.$delivery_id.'
      AND '.TABLE_BASKET_ALL.'.submitted = 0
    ';
  $rs = @mysql_query($sql,$connection) or die(mysql_error());
  while ( $row = mysql_fetch_array($rs) )
  {
    $member_email = $row['email_address'];
    $member_name = $row['first_name']." ".$row['last_name'];

    // Send an email reminder
    $message = 'Dear '.$member_name.',

*Please ignore this automated email if you do not wish to order this cycle, or have already submitted your order.*

You have an open basket in the '.SITE_NAME.' shop.

Your order is important to us so if you want to be sure you get your food for this collection cycle, you must complete and submit your basket by:

*'.$cycle_closes.'*

To submit your order, please log in to your '.SITE_NAME.' account at:

http://'.DOMAIN_NAME.PATH.'members/orders_login.php

...and click on *Checkout*.

Thank you and we look forward to packing your order.

-------------------
You are receiving this email from '.ORDER_EMAIL.' because
you opened an order on our website: http://'.DOMAIN_NAME.PATH.'.
If you would like to unsubscribe from '.SITE_NAME.',
please email '.MEMBERSHIP_EMAIL.'.
'.SITE_MAILING_ADDR;

    $email_headers  = "From: ".ORDER_EMAIL."\n";
    $email_headers .= "BCC: ".ORDER_EMAIL;
    mail( $member_email,
          'REMINDER: '.$member_name.', your '.SITE_NAME.' order is unsubmitted',
          $message,
          $email_headers);
  }
}
?>
