<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();
$date_today = date("F j, Y");

if ( $updatevalues == "ys" && !empty($payment_method) )
  {
  $sql77 = '
    SELECT
      delivery_id,
      basket_id
    FROM
      '.TABLE_BASKET_ALL.'
    WHERE
      '.TABLE_BASKET_ALL.'.delivery_id = "'.$delivery_id.'"
      AND '.TABLE_BASKET_ALL.'.submitted = 1';
  $result77 = @mysql_query($sql77,$connection) or die("".mysql_error()."");
  while ( $row = mysql_fetch_array($result77) )
    {
      $basket_id = $row['basket_id'];
      $sql_pay = '
        SELECT
          payment_method,
          balance_updated,
          grand_total
        FROM
          '.TABLE_BASKET_ALL.'
        WHERE
          basket_id = "'.$basket_id.'"';
      $result_pay = @mysql_query($sql_pay,$connection) or die("".mysql_error()."");
      while ( $row_basket = mysql_fetch_array($result_pay) )
        {
          $payment_method_previous = $row_basket['payment_method'];
          if ( $payment_method_previous != $payment_method[$basket_id] )
            {
              $finalized2 = "0";
              $payment_method2 = $payment_method[$basket_id];
            }
          else
            {
              $finalized2 = $finalized[$basket_id];
              $payment_method2 = $payment_method_previous;
            }
          $member_id = preg_replace("/[^0-9]/","",$_POST['member_id'][$basket_id]);
          $amount_paid_update = preg_replace("/[^0-9\.\-]/","",$amount_paid_new[$basket_id]);
          $membership_amount_paid_update = preg_replace("/[^0-9\.\-]/","",$membership_amount_paid[$basket_id]);
          $payment_method2 = preg_replace("/[^a-zA-Z]/","",$payment_method2);
          $finalized2 = preg_replace("/[^0-9]/","",$finalized2);

          // If this is the very first update to the basket OR a non-zero balance has been entered
          if ( $amount_paid_update != 0 || $row_basket['balance_updated'] == 0)
            {
              // Update the overall member balance
              $member_balance_update = -$amount_paid_update;
              
              // If this is the *very first* payment towards this basket
              // include the basket total in the member balance update
              // and remove any adjustments that formed part of that balance
              if ( $row_basket['balance_updated'] == 0 )
              {
                $member_balance_update += $row_basket['grand_total'];

                $sql_delete_adj = '
                  DELETE FROM
                    '.TABLE_CUSTOMER_ADJ.'
                  WHERE
                    member_id = "'.$member_id.'"
                  AND
                    applied = "1"';
                $result_del = @mysql_query($sql_delete_adj,$connection) or die(mysql_error());
              }
              
              $sqlu = '
                UPDATE
                  '.TABLE_MEMBER.'
                SET
                  curr_balance = curr_balance + "'.$member_balance_update.'"
                WHERE
                  member_id = "'.$member_id.'"';
              $resultu = @mysql_query($sqlu,$connection) or die(mysql_error());

              // Now update the basket itself
              $sqlu = '
                UPDATE
                  '.TABLE_BASKET_ALL.'
                SET
                  payment_method = "'.$payment_method2.'",
                  amount_paid = amount_paid + "'.$amount_paid_update.'",
                  balance_updated = "1",
                  order_date = now()
                WHERE
                  basket_id = "'.$basket_id.'"
                  AND delivery_id = "'.$delivery_id.'"';
              $resultu = @mysql_query($sqlu,$connection) or die(mysql_error());
              
              $message = "<H3>The information has been updated.</h3>";
            }
          elseif ( $payment_method_previous != $payment_method[$basket_id] )
            {
              // only change payment method if no amount chosen
              $sqlu = '
                UPDATE '.TABLE_BASKET_ALL.'
                SET
                  payment_method = "'.$payment_method2.'",
                  finalized = "'.$finalized2.'",
                  order_date = now()
                WHERE
                  basket_id = "'.$basket_id.'"
                  AND delivery_id = "'.$delivery_id.'"';
              $resultu = @mysql_query($sqlu,$connection) or die(mysql_error());
              $message = "<H3>The information has been updated.</h3>";
            }
          $batchno = preg_replace("/[^0-9]/","",$_POST['transaction_batchno'][$basket_id]);
          $memo = stripslashes(strip_tags($_POST['transaction_memo'][$basket_id]));
          $comments = stripslashes(strip_tags($_POST['transaction_comments'][$basket_id]));
          if ( $member_id && ($amount_paid_update != 0 || ($payment_method_previous != $payment_method[$basket_id])) || $membership_amount_paid_update != 0)
            {
              if ( $amount_paid_update != 0)
                {
                  $query = '
                    INSERT INTO
                      '.TABLE_TRANSACTIONS.'
                        (
                          transaction_type,
                          transaction_name,
                          transaction_amount,
                          transaction_user,
                          transaction_member_id,
                          transaction_basket_id,
                          transaction_delivery_id,
                          transaction_timestamp,
                          transaction_batchno,
                          transaction_memo,
                          transaction_comments,
                          transaction_method
                        )
                    VALUES
                      (
                        "23",
                        "Invoice Payment",
                        "'.$amount_paid_update.'",
                        "'.$_SESSION['valid_c'].'",
                        "'.$member_id.'",
                        "'.$basket_id.'",
                        "'.$delivery_id.'",
                        now(),
                        "'.$batchno.'",
                        "'.$memo.'",
                        "'.$comments.'",
                        "'.$payment_method2.'")';
                  $sql = mysql_query($query);
                  }
            }
        }
    }
}
// End of update loop.

$sql_sum6 = '
  SELECT
    '.TABLE_BASKET_ALL.'.delivery_id,
    '.TABLE_BASKET_ALL.'.basket_id,
    '.TABLE_BASKET.'.basket_id,
    '.TABLE_BASKET.'.out_of_stock,
    sum(quantity) AS sumq
  FROM
    '.TABLE_BASKET_ALL.',
    '.TABLE_BASKET.'
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = "'.$delivery_id.'"
    AND '.TABLE_BASKET_ALL.'.submitted = 1
    AND '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
    AND '.TABLE_BASKET.'.out_of_stock != "1"
  GROUP BY '.TABLE_BASKET_ALL.'.delivery_id';
$result_sum6 = @mysql_query($sql_sum6,$connection) or die("Couldn't execute query 6.");
while ( $row = mysql_fetch_array($result_sum6) )
  {
    $quantity_all = $row['sumq'];
  }
$surcharge = "";
$sql = '
  SELECT
    '.TABLE_BASKET_ALL.'.*,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.first_name_2,
    '.TABLE_MEMBER.'.last_name_2,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_DELDATE.'.delivery_id,
    '.TABLE_DELDATE.'.delivery_date,
    '.TABLE_PAY.'.*,
    DATE_FORMAT(order_date, "%b %d, %Y") AS last_modified,
    DATE_FORMAT(delivery_date, "%M %d, %Y") AS delivery_date
  FROM
    '.TABLE_BASKET_ALL.',
    '.TABLE_MEMBER.',
    '.TABLE_DELDATE.',
    '.TABLE_PAY.'
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = "'.$delivery_id.'"
    AND '.TABLE_BASKET_ALL.'.submitted = 1
    AND '.TABLE_DELDATE.'.delivery_id = "'.$delivery_id.'"
    AND '.TABLE_BASKET_ALL.'.payment_method = '.TABLE_PAY.'.payment_method
    AND '.TABLE_BASKET_ALL.'.member_id = '.TABLE_MEMBER.'.member_id
  GROUP BY
    '.TABLE_BASKET_ALL.'.basket_id
  ORDER BY
    last_name ASC,
    business_name ASC';

$result = @mysql_query($sql,$connection) or die("Couldn't execute query 1.");
$numtotal = mysql_numrows($result);
while ( $row = mysql_fetch_array($result) )
  {
    $basket_id = $row['basket_id'];
    $member_id = $row['member_id'];
    $business_name = stripslashes ($row['business_name']);
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $delcode = $row['delcode'];
    $delivery_cost = $row['delivery_cost'];
    $transcharge = $row['transcharge'];
    $delivery_date = $row['delivery_date'];
    $payment_method = $row['payment_method'];
    $payment_desc = $row['payment_desc'];
    $surcharge_for_paypal = $row['surcharge_for_paypal'];
    $subtotal = $row['subtotal'];
    $coopfee = $row['coopfee'];
    $grand_total_cust = round($row['grand_total'], 2);
    $grand_total_coop = $row['grand_total_coop'];
    $last_modified = $row['last_modified'];
    $draft_emailed = $row['draft_emailed'];
    $finalized = $row['finalized'];
    $prev_balance = $row['prev_balance'];

    if ( $current_basket_id < 0 )
      {
        $current_basket_id = $row['basket_id'];
      }
    while ( $current_basket_id != $basket_id )
      {
        $current_basket_id = $basket_id;
        $cust_salestax = "";
        $sql_sums = '
          SELECT
            collected_statetax,
            collected_citytax,
            collected_countytax
          FROM
            '.TABLE_CUSTOMER_SALESTAX.'
          WHERE
            customer_salestax.basket_id = "'.$basket_id.'"';
        $result_sums = @mysql_query($sql_sums,$connection) or die("Couldn't execute query sales tax.");
        while ( $row = mysql_fetch_array($result_sums) )
          {
            $collected_statetax = $row['collected_statetax'];
            $collected_citytax = $row['collected_citytax'];
            $collected_countytax = $row['collected_countytax'];
            $cust_salestax = $collected_statetax + $collected_citytax + $collected_countytax;
            $total_salestax = $cust_salestax + $total_salestax + 0;
          }
        $draft_emailed = '';
        if ( $draft_emailed )
          {
            $draft_emailed = 'Y';
          }
        $final_invoice = '';
        if ( $finalized )
          {
            $final_invoice = 'Y';
          }
        if ( $payment_method == 'P')
          {
            $p_chk = "checked";
            $c_chk = "";
            if ( $delivery_id > DELIVERY_NO_PAYPAL )
              {
                $subtotal_1 = $subtotal + $coopfee + $transcharge + $delivery_cost + $cust_salestax;
                $total_sent_to_paypal = ($subtotal_1 + .30) / .971;
                //$surcharge = number_format((($total_sent_to_paypal*.029) + .30),2);
                $surcharge = number_format($surcharge_for_paypal, 2);
                if ($surcharge_for_paypal) $minus_paypal = "<br>-$surcharge for paying by check/cash";
              }
          }
        elseif ( $payment_method == 'C' )
          {
            $c_chk = 'checked';
            $p_chk = '';
            $surcharge = '';
            $minus_paypal = '';
          }
        else
          {
            $c_chk = '';
            $p_chk = '';
            $surcharge = '';
            $minus_paypal = '';
          }
        $quantity_mem = '';
        $sql_sum8 = '
          SELECT
            '.TABLE_BASKET_ALL.'.delivery_id,
            '.TABLE_BASKET_ALL.'.basket_id,
            '.TABLE_BASKET_ALL.'.amount_paid,
            '.TABLE_BASKET.'.basket_id,
            '.TABLE_BASKET.'.out_of_stock,
            sum(quantity) AS sum_mem
          FROM
            '.TABLE_BASKET_ALL.',
            '.TABLE_BASKET.'
          WHERE
            '.TABLE_BASKET_ALL.'.delivery_id = "'.$delivery_id.'"
            AND '.TABLE_BASKET_ALL.'.submitted = 1
            AND '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
            AND '.TABLE_BASKET.'.out_of_stock != "1"
            AND '.TABLE_BASKET_ALL.'.member_id = "'.$member_id.'"
          GROUP BY
            '.TABLE_BASKET_ALL.'.delivery_id';
        $result_sum8 = @mysql_query($sql_sum8,$connection) or die("Couldn't execute query 8.");
        while ( $row = mysql_fetch_array($result_sum8) )
          {
            $quantity_mem = $row['sum_mem'];
            $amount_paid = $row['amount_paid'];
          }

        // I am expecting surcharge to be zero - we're not using PayPal -
        // but it should (ha!) work in theory since I've not changed the PayPal functionality.
        // prev_balance is used to indicate what's owed cumulatively from previous orders.
        // amount_paid will usually be zero, but maybe we might want to update
        // the amount paid for a basket twice in some circumstances, e.g.
        // we entered the wrong amount the first time.
        $discrepancy = $grand_total_cust - $surcharge - $amount_paid;
        $discrepancy += $prev_balance;
        $discrepancy = number_format($discrepancy,2);
        $discrep_color = '';
       
        include("../func/show_name_last.php");
        if ( $discrepancy == 0 || $finalized != 1 )
          {
            $mismatch_color = 'bgcolor="#DDDDDD"';
          }
        else if ( $discrepancy < 0 )
          {
            $mismatch_color = 'bgcolor="#FF6666"';
          }
        else
          {
            $mismatch_color = 'bgcolor="#FFCC33"';
          }
        if ( $finalized != 1 )
          {
            $unfinalized = '<font size="-2" color="#880000"><br>Unfinalised</font>';
          }
        else
          {
            $unfinalized = '';
          }

        // As a default, note that we fill in the input field for "Total Amount Owed" with the
        // amount that would be needed to pay off the balance in full, except where
        // we owe them money ($discrepancy < 0), where we assume by default that they paid 0 and we paid
        // them nothing (so they remain in credit).  We highlight the cell in red in that case,
        // to indicate that this needs checking (as in fact we may have settled what we owe them).
        $display_month .= '
          <tr>
            <td align="right" valign="top"><font face="arial" size="-1"><b>#&nbsp;'.$member_id.'</b></td>
            <td align="left" valign="top"><font face="arial" size="-1">
              <b><a href="customer_invoice.php?member_id='.$member_id.'&basket_id='.$basket_id.'&delivery_id='.$delivery_id.'" target="_blank">'.$show_name.'</a></b>&nbsp;&nbsp;</td>
            <td align="right" valign="top"><font face="arial" size="-1">
              <input type=radio name="payment_method['.$basket_id.']" value="P" '.$p_chk.'>P
              <input type=radio name="payment_method['.$basket_id.']" value="C" '.$c_chk.'>C</td>
            <td align="right" valign="top" '.$discrep_color.'><font face="arial" size="-1"><b>'.CURSYM.$prev_balance.'</b></td>
            <td align="right" valign="top" '.$mismatch_color.'><font face="arial" size="-1">'.$amount_paid.'</td>
            <td align="right" valign="top"><nobr><font face="arial" size="-1"><b>'.CURSYM.number_format($grand_total_cust, 2).'</b> '.$minus_paypal.'</nobr></td>
            <td align="right" valign="top" '.$mismatch_color.'>
              <font face="arial" size="-1"><b>'.CURSYM.number_format($discrepancy, 2).'</b>
                <br><nobr>'.CURSYM.'<input type="text" name="amount_paid_new['
                  .$basket_id.']" size="5" maxlength="10" id="shopping_amount'.$member_id.'"
                  value="'.($discrepancy < 0 ? '0.00' : number_format($discrepancy, 2)).'"> '.$unfinalized.'</nobr>
              <input type="hidden" name="member_id['.$basket_id.']" value="'.$member_id.'">
              <input type="hidden" name="finalized['.$basket_id.']" value="'.$finalized.'"></td>
            <td><input type="input" name="transaction_batchno['.$basket_id.']" value="" maxlength="8" size="4" id="batchno'.$member_id.'"></td>
            <td><input type="input" name="transaction_memo['.$basket_id.']" value="" maxlength="20" size="10"></td>
            <td><input type="input" name="transaction_comments['.$basket_id.']" value="" maxlength="200"></td>
            <td align="right" valign="top"><font face="arial" size="-2"><i>'.$last_modified.'</i></td>
          </tr>';
      }
  }
$fontface='arial';
?>

<?php
$scripts .= '<script type="text/javascript" src="auto_fill_ctotals.js"></script>';
include("template_hdr.php");
?>

<h1>Record of Customer Payments for <?php echo $delivery_date;?></h1>
<b>Total Products Sold: <?php echo $quantity_all;?> Products &nbsp;&nbsp;&nbsp;
Total Orders: <?php echo $numtotal;?></b> &nbsp;&nbsp;&nbsp;<font size="-1">(Print Landscape for best results.)</font>
<br><a href="ctotals_reports.php?delivery_id=<?php echo$delivery_id;?>">Breakdown of Customer Payments</a> |
<a href="history_saved.php">Previous Order Cycles</a>

<p>
  <strong>Auto-fill form</strong>
  (click to show/hide)
  <img title="click to show" id="autofill-ic" src="../members/grfx/arrow_closed.png" onClick='{document.getElementById("autofill").style.display="";document.getElementById("autofill-ic").style.display="none";document.getElementById("autofill-io").style.display="";}'>
  <img title="click to hide" id="autofill-io" style="display:none;" src="../members/grfx/arrow_open.png" onClick='{document.getElementById("autofill").style.display="none";document.getElementById("autofill-io").style.display="none";document.getElementById("autofill-ic").style.display="";}'>
</p>

<div id="autofill" style="display:none;">
<p>Paste sales data from Excel (or other spreadsheet program) into the text area below to auto-fill the form. The first column should be member id#, the second column should be amount paid for shopping invoice, the third column should be membership amount paid (optional). Dollar amounts with a $ prefix will have the $ removed, and rows with no shopping or membership payment amount will be ignored. If there are rows with dollar amounts but the member id# isn't in the list, an error message will be displayed. <em>Note: copying columns that aren't next to each other in Excel will break the formatting, to get around this, copy the rows, paste them into a new document (where they will be put next to each other), then copy out of that and paste here.</em></p>
<form onsubmit="form_auto_fill(); return(false);" style="text-align:center;">
<textarea id="auto_fill_box" cols="30" rows="4"></textarea><br>
Batch #:<input type="text" id="auto_fill_batchno"><br>
<input type="submit" value="Auto Fill">
</form>
</div>

<font color="#CC9900"><?php echo $message;?></font>
<hr>
<form action='<?php echo $_SERVER['PHP_SELF'];?>?delivery_id=<?php echo$delivery_id;?>&updatevalues=ys' method='post'>
<table cellpadding="2" cellspacing="0" border="1">
  <tr>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Mem. ID</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Member Name</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Payment Method</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Previous<br>Balance</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Amount Paid So Far</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Shopping <br>Due / Pmt.</th>
    <!--<th valign="bottom" bgcolor="#DDDDDD"><font size="-2">Membership <br>Due / Pmt.</th>-->
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Total Amount Owed</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Batch No.</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Memo</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Comments</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="<?php echo $fontface;?>" size="-2">Last Modified</th>
  </tr>
<?php echo $display_month;?>
  <tr>
    <td bgcolor="#AEDE86" colspan="11" align="right">
      <input name="where" type="submit" value="SAVE CHANGES">
    </td>
  </tr>
</table>
  </form>
<?php include("template_footer.php");?>
