<?php

// Build how-heard select options
function get_how_heard_options($how_heard_id_curr)
{
  global $connection;

  $how_heard_options = '
        <option value="0">Choose One</option>';
  $query = '
    SELECT
      *
    FROM
      '.TABLE_HOW_HEARD.'
    WHERE 1';
  $sql =  @mysql_query($query, $connection) or die("You found a bug. <b>Error:</b> Select Delivery Types Query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
  while ($row = mysql_fetch_object($sql))
    {
      $selected = '';
      if ($how_heard_id_curr == $row->how_heard_id)
        {
          $selected = ' selected';
          $how_heard_text = $row->how_heard_name;
        }
      $how_heard_options .= '
        <option value="'.$row->how_heard_id.'"'.$selected.'>'.$row->how_heard_name.'</option>';
    }

  return $how_heard_options;
}
?>
