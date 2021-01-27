<?php

// Function for updating balance
function updateBalance($adj_type, $adj_amount, $receiver_id)
{
  global $connection;

  if ( $adj_type == 'customer' )
    {
      $sqlu = '
        UPDATE
          '.TABLE_MEMBER.'
        SET
          curr_balance = curr_balance + "'.$adj_amount.'"
        WHERE
          member_id = "'.$receiver_id.'"';
    }
  else if ( $adj_type == "producer" )
    {
      $sqlu = '
        UPDATE
          '.TABLE_PRODUCER.'
        SET
          curr_balance = curr_balance + "'.$adj_amount.'"
        WHERE
          producer_id = "'.$receiver_id.'"';
    }
  $resultu = @mysql_query($sqlu,$connection) or die(mysql_error());
}

// Function for getting balance
function getBalance($adj_type, $receiver_id)
{
  global $connection;

  if ( $adj_type == 'customer' )
    {
      $sql = '
        SELECT
          curr_balance
        FROM
          '.TABLE_MEMBER.'
        WHERE
          member_id = "'.$receiver_id.'"';
    }
  else if ( $adj_type == "producer" )
    {
      $sql = '
        SELECT
          curr_balance
        FROM
          '.TABLE_PRODUCER.'
        WHERE
          producer_id = "'.$receiver_id.'"';
    }
  $result = @mysql_query($sql,$connection) or die(mysql_error());
  $row = mysql_fetch_array($result);

  return $row['curr_balance'];
}

?>
