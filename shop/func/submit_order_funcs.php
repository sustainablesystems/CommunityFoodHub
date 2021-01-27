<?php

// Common functions for dealing with order submission

function open_basket($member_id, $current_delivery_id, $delcode_id, $deltype, $payment_method)
{
  global $connection;

  $sql5 = '
    SELECT
      mem_delch_discount,
      curr_balance
    FROM
      '.TABLE_MEMBER.'
    WHERE
       member_id = "'.$member_id.'"';
  $result5 = @mysql_query($sql5,$connection) or die("Couldn't execute query 2.");
  while ( $row = mysql_fetch_array($result5) )
    {
      $mem_delch_discount = $row['mem_delch_discount'];
      $curr_balance = $row['curr_balance'];
    }
  $sql2 = '
    SELECT
      delcharge,
      transcharge
    FROM
      '.TABLE_DELCODE.'
    WHERE delcode_id = "'.$delcode_id.'"';
  $result2 = @mysql_query($sql2,$connection) or die("Couldn't execute query 2.");
  while ( $row = mysql_fetch_array($result2) )
    {
      $delcharge = $row['delcharge'];
      $transcharge = $row['transcharge'];
    }
  if ( $mem_delch_discount == 1 )
    {
      //$delcharge = $delcharge-2.50;
      $delcharge = 0;
    }
  $sqlc = '
    SELECT
      coopfee
    FROM
      '.TABLE_DELDATE.'
    WHERE
      delivery_id = "'.$current_delivery_id.'"';
  $resultc = @mysql_query($sqlc,$connection) or die("Couldn't execute query coop fee.");
  while ( $row = mysql_fetch_array($resultc) )
    {
      $coopfee = $row['coopfee'];
    }

  $sqlo = '
    INSERT INTO
      '.TABLE_BASKET_ALL.'
        (
          member_id,
          delivery_id,
          deltype,
          delcode_id,
          coopfee,
          delivery_cost,
          transcharge,
          payment_method,
          prev_balance,
          order_date
        )
    VALUES
      (
        "'.$member_id.'",
        "'.$current_delivery_id.'",
        "'.$deltype.'",
        "'.$delcode_id.'",
        "'.$coopfee.'",
        "'.$delcharge.'",
        "'.$transcharge.'",
        "'.$payment_method.'",
        "'.$curr_balance.'",
        now()
      )';
  $resulto = @mysql_query($sqlo,$connection) or die(mysql_error());
  // Get ID for new basket
  $basket_id = mysql_insert_id();

  // Mark any adjustments belonging to the member as applied, now we've copied the current balance
  // (that includes those adjustments) to the basket
  $sql_update_adj = '
    UPDATE
      '.TABLE_CUSTOMER_ADJ.'
    SET
      applied = "1"
    WHERE
      member_id = "'.$member_id.'"';
  $result_upd = @mysql_query($sql_update_adj,$connection) or die(mysql_error());

  return $basket_id;
}


function set_order_submitted($basket_id, $submitted)
{
  global $connection;

  $sqlu = '
    UPDATE
      '.TABLE_BASKET_ALL.'
    SET
      submitted = '.($submitted ? '1' : '0').'
    WHERE
      basket_id = '.$basket_id;
  $result = @mysql_query($sqlu,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
}

?>
