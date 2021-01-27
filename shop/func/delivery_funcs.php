<?php
/*
 * Get the names, times and descriptions for all active collection locations
 */
function get_deliveries($delivery_date,
        &$delcodes, &$deldates, &$pickup_times, &$deldescs)
{
  global $connection;
  
  $sql = '
    SELECT
      delcode,
      delday_offset,
      pickup_time,
      deldesc
    FROM
      '.TABLE_DELCODE.'
    WHERE
      inactive = 0
    GROUP BY
      delcode_id
    ORDER BY
      deltype DESC,
      delcode ASC';

  $result = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
  $num_del = mysql_numrows($result);
  $del_loop = 0;
  while ( $row = mysql_fetch_array($result) )
  {
    $delcodes[$del_loop] = $row['delcode'];
    $pickup_times[$del_loop] = $row['pickup_time'];
    $deldescs[$del_loop] = $row['deldesc'];

    // Get the delivery day from the offset
    $offset = $row['delday_offset'];
    //$deldates[$del_loop] = get_delivery_from_offset($delivery_date, $offset, "D, j M Y");
    $deldates[$del_loop] = get_delivery_from_offset($delivery_date, $offset, "l, j F");
    
    $del_loop++;
  }

  return $num_del;
}


function get_delivery_from_offset($delivery_date, $offset, $format_str)
{
  $offset_str = 'P' . ($offset < 0 ? abs($offset) : $offset) . 'D';

  // Apply the offset to the "master" delivery date
  
  // Delivery date is from current_delivery table, in format e.g. 2011-05-28
  $delDateTime = date_create( $delivery_date );
  
  $delDateInterval = new DateInterval($offset_str);
  if ($offset < 0)
  {
    $delDateTime->sub($delDateInterval);
  }
  else
  {
    $delDateTime->add($delDateInterval);
  }

  // Get a string describing the date
  //$deldates[$del_loop] = $delDateTime->format("D, d M y");
  $deldate = $delDateTime->format($format_str);

  return $deldate;
}


function update_session_current_delivery()
{
  global $connection;

  $sqldd = '
    SELECT
      *
    FROM
      '.TABLE_CURDEL;
  $rs = @mysql_query($sqldd, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
  while ( $row = mysql_fetch_array($rs) )
  {
    $_SESSION['current_delivery_id'] = $row['delivery_id'];
    $_SESSION['current_delivery_date'] = date('F j, Y', strtotime ($row['delivery_date']));
    $_SESSION['order_cycle_closed'] = $row['order_cycle_closed'];
  }
}


function get_current_delivery_date()
{
  global $connection;

  $sql = '
    SELECT
      delivery_date
    FROM
      '.TABLE_CURDEL;

  $result = @mysql_query($sql, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

  while ( $row = mysql_fetch_array($result) )
  {
    $delivery_date = $row['delivery_date'];
  }

  return $delivery_date;
}


function get_delivery_date_str($delivery_id)
{
  global $connection;

  $sql = '
    SELECT
      delivery_date
    FROM
      '.TABLE_DELDATE.'
    WHERE
    delivery_id = "'.$delivery_id.'"';
  $rs = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

  while ( $row = mysql_fetch_array($rs) )
  {
    $delivery_date = date('F j, Y', strtotime ($row['delivery_date']));
  }
  
  return $delivery_date;
}


function get_delivery_locations_for_cycle($delivery_cycle_id)
{
  global $connection;
  
  // Get the collection locations that had orders for the given delivery cycle
  $sql_hubs = '
    SELECT DISTINCT
      '.TABLE_DELCODE.'.delcode_id,
      '.TABLE_DELCODE.'.delcode
    FROM
      '.TABLE_DELCODE.'
    LEFT JOIN '.TABLE_BASKET_ALL.'
      ON '.TABLE_BASKET_ALL.'.delcode_id = '.TABLE_DELCODE.'.delcode_id
    WHERE
      '.TABLE_BASKET_ALL.'.delivery_id = "'.$delivery_cycle_id.'"
    ORDER BY
      delcode_id ASC';

  $rs_hubs = @mysql_query($sql_hubs,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

  return $rs_hubs;
}


function is_current_or_previous_delivery($delivery_id)
{
  global $connection;

  $sql = '
    SELECT MAX(delivery_id) AS max_id
    FROM
      '.TABLE_DELDATE;
  $rs = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    
  while ( $row = mysql_fetch_array($rs) )
  {
    $last_delivery_id = $row['max_id'];
  }

  if ( ($delivery_id == $last_delivery_id) ||
       ($delivery_id == $last_delivery_id - 1))
  {
    $is_curr_or_prev = true;
  }
  else
  {
    $is_curr_or_prev = false;
  }

  return $is_curr_or_prev;
}


function get_delivery_date_for_member($member_id, $delivery_id,
        &$delcode, &$deldesc, &$pickup_time)
{
  global $connection;
  
  // Get delivery information for this specific order
  $query = '
     SELECT
        '.TABLE_DELCODE.'.*,
        '.TABLE_DELDATE.'.delivery_date
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN '.TABLE_DELDATE.' ON '.TABLE_BASKET_ALL.'.delivery_id = '.TABLE_DELDATE.'.delivery_id
      LEFT JOIN '.TABLE_DELCODE.' ON '.TABLE_BASKET_ALL.'.delcode_id = '.TABLE_DELCODE.'.delcode_id
      LEFT JOIN '.TABLE_MEMBER.' ON '.TABLE_BASKET_ALL.'.member_id = '.TABLE_MEMBER.'.member_id
      WHERE
        '.TABLE_MEMBER.'.member_id = "'.$member_id.'"
        AND '.TABLE_DELDATE.'.delivery_id = "'.$delivery_id.'"
      ';
  $result = @mysql_query($query, $connection) or die(mysql_error());
  $deldate = null;
  while ( $row = mysql_fetch_array($result) )
    {
      $deldate = $row['delivery_date'];
      $delcode = $row['delcode'];
      $deldesc = $row['deldesc'];
      $pickup_time = $row['pickup_time'];
      $offset = $row['delday_offset'];
      $deldate = get_delivery_from_offset($deldate, $offset, "l, j F Y");
    }

  // May be null if there is no delivery info yet
  return $deldate;
}
?>
