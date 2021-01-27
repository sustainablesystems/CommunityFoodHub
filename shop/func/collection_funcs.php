<?php

class CollectionLocationForm
{
  const EDIT_MODE_SELECT = 1;
  const EDIT_MODE_ADD = 2;
  const EDIT_MODE_UPDATE = 3;

  const FINAL_COLLECTION_OFFSET_MAX = 10;
  
  private $activation_states;
  private $edit_mode;
  private $conn;

  // Current collection location ID (may be null if adding new record)
  private $delcode_id_curr;

  // Editable collection location data
  private $delcode_id;
  private $delcode;
  private $delday_offset;
  private $pickup_time;
  private $pickup_day;
  private $deldesc;
  private $route_id;
  private $inactive;
  
  function __construct($connection)
  {
    $this->activation_states = array(0 => "Active Site", 1 => "INACTIVE", 2 => "Standby Site");
    $this->conn = $connection;

    $this->resetCollectionLocationData();
    $this->edit_mode = self::EDIT_MODE_SELECT;
  }

  private function resetCollectionLocationData()
  {
    $this->delcode_id_curr = null;
    
    $this->delcode_id = null;
    $this->delcode = null;
    // If there is no current offset, set it to something invalid
    // (null won't work as it will be treated as 0 - which is a valid offset)
    $this->delday_offset = self::FINAL_COLLECTION_OFFSET_MAX + 1;
    $this->pickup_time = null;
    $this->pickup_day = null;
    $this->deldesc = null;
    $this->route_id = null;
    $this->inactive = count($this->activation_states);
  }

  // Get data from the database to fill in the form
  function startEdit($delcode_id)
  {
    $mem_query = '
      SELECT *
      FROM
        '.TABLE_DELCODE.'
      WHERE
        delcode_id = "'.$delcode_id.'"';
    $result_select = @mysql_query($mem_query, $this->conn)
            or die("Error getting collection location data: ".mysql_error());
    if ( $row = mysql_fetch_array($result_select) )
    {
      $this->delcode_id = $row['delcode_id'];
      $this->delcode = stripslashes($row['delcode']);
      // Offsets are stored as negative numbers, but we display as positive numbers
      $this->delday_offset = -($row['delday_offset']);
      $this->pickup_time = stripslashes($row['pickup_time']);
      $this->pickup_day = stripslashes($row['pickup_day']);
      $this->deldesc = stripslashes($row['deldesc']);
      $this->route_id = $row['route_id'];
      $this->inactive = $row['inactive'];
    }
    else
    {
      exit("Error: unable to get collection location (".$delcode_id."). Please contact the webmaster.");
    }

    $this->delcode_id_curr = $delcode_id;
    $this->edit_mode = self::EDIT_MODE_UPDATE;
  }

  // Send location to the database
  function saveLocation($delcode_id, $location_data, $as_new)
  {
    // Get the current location ID (if any)
    $this->delcode_id_curr = $delcode_id;

    // Get the new data (for redisplay)
    $this->delcode_id = $location_data['delcode_id'];
    $this->delcode = $location_data['delcode'];
    $this->delday_offset = $location_data['delday_offset'];
    $this->pickup_time = $location_data['pickup_time'];
    $this->pickup_day = $location_data['pickup_day'];
    $this->deldesc = $location_data['deldesc'];
    $this->route_id = $location_data['route_id'];
    $this->inactive = $location_data['inactive'];

    // Check the data and commit if ok
    $errors = $this->validateLocation($as_new);
    if (null == $errors)
    {
      $sql_location_data = "
        delcode_id = '".$this->delcode_id."',
        delcode = '".mysql_real_escape_string($this->delcode)."',
        deltype = 'P',
        delday_offset = ".($this->delday_offset > 0 ? "-" : "").$this->delday_offset.",
        pickup_time = '".mysql_real_escape_string($this->pickup_time)."',
        pickup_day = '".mysql_real_escape_string($this->pickup_day)."',
        deldesc = '".mysql_real_escape_string($this->deldesc)."',
        delcharge = 0,
        transcharge = 0,
        route_id = ".$this->route_id.",
        copo_city = 0,
        special_loc = 0,
        inactive = ".$this->inactive;
      
      if (true == $as_new)
      {
        // Do Insert
        $sql_insert = '
          INSERT INTO
            '.TABLE_DELCODE.'
          SET
            '.$sql_location_data;
        $query = mysql_query($sql_insert)
          or die("Error adding new collection location: ".mysql_error());

        $display .= '<p align="center"><b>Collection location \''.$this->delcode_id.'\' added successfully.</b></p>';
      }
      else
      {
        // Do Update
        $sql_update = '
          UPDATE
            '.TABLE_DELCODE.'
          SET
            '.$sql_location_data.'
          WHERE
            delcode_id = "'.$this->delcode_id.'"';
        $query = mysql_query($sql_update)
          or die("Error updating collection location: ".mysql_error());

        $display .= '<p align="center"><b>Collection location \''.$this->delcode_id.'\' updated successfully.</b></p>';
      }
      
      // Record saved ok, redisplay collection location menu
      // (with the just-added location as the default choice for editing)
      $this->delcode_id_curr = null;
      $this->edit_mode = self::EDIT_MODE_SELECT;
    }
    else
    {
      $display .= '<p align="center"><font color="red"><b>Please correct the following errors:</b><br>';
      $display .= $errors;
      $display .= '</font></p>';

      // No change in edit mode, form will be redisplayed for error correction
      if (null == $this->delcode_id_curr)
      {
        $this->edit_mode = self::EDIT_MODE_ADD;
      }
      else
      {
        $this->edit_mode = self::EDIT_MODE_UPDATE;
      }
    }

    return $display;
  }

  // Get the data the user entered and check it (same order as on the form)
  private function validateLocation($as_new)
  {
    $display_errors = null;
    
    // Check the delcode if it's a new location
    // or we're changing the delcode of an existing location.
    if ( $as_new || ($this->delcode_id != $this->delcode_id_curr) )
    {
      // Check new delcode_id is unique
      $sql_delcode_id = "
        SELECT
          COUNT(delcode_id)
        FROM
          ".TABLE_DELCODE."
        WHERE delcode_id = '".$this->delcode_id."'";
      $result_delcode_id = mysql_query($sql_delcode_id)
        or die("Error validating delcode_id: ".mysql_error());
      $delcode_id_count = mysql_result($result_delcode_id, 0);
      if ($delcode_id_count > 0)
      {
        $display_errors .= "The collection location ID '".$this->delcode_id."' is already in use.<br>";
      }

      // Check new delcode_id is alphanumeric
      if (!ctype_alnum( $this->delcode_id ))
      {
        $display_errors .= "The collection location ID must be alphanumeric.<br>";
      }

      // Check new delcode_id is three characters long
      if (strlen( $this->delcode_id ) != 3)
      {
        $display_errors .= "The collection location ID must be 3 characters long.<br>";
      }
    }

    if (null == $this->delcode)
    {
      $display_errors .= "Please enter a collection location name.<br>";
    }
    if (null == $this->route_id)
    {
      $display_errors .= "Please enter an Area or Route.<br>";
    }
    if (null == $this->pickup_day)
    {
      $display_errors .= "Please enter a collection day.<br>";
    }
    if (null == $this->pickup_time)
    {
      $display_errors .= "Please enter a collection time.<br>";
    }

    // Check offset
    if ( ($this->delday_offset < 0) || ($this->delday_offset > self::FINAL_COLLECTION_OFFSET_MAX) )
    {
      $display_errors .= "The final collection day offset must be between 0 and "
        .self::FINAL_COLLECTION_OFFSET_MAX.".<br>";
    }

    // Note: location description (deldesc) can be null - don't check it

    // Check activation state
    if ( !array_key_exists($this->inactive, $this->activation_states) )
    {
      $display_errors .= "Please enter an activation state.<br>";
    }

    return $display_errors;
  }

  function startAdd()
  {    
    $this->resetCollectionLocationData();

    $this->edit_mode = self::EDIT_MODE_ADD;
  }

  function display()
  {
    $display .= '<form action="'.$_SERVER['PHP_SELF'].(null != $this->delcode_id_curr ?
            '?delcode_id='.$this->delcode_id_curr : '').'" method="post">';

    switch ($this->edit_mode)
    {
      case self::EDIT_MODE_SELECT:
        $display .= '<h1 align="center">Add or Edit Collection Location</h1>';
        $display .= '<div align="center" width="800">';
        $display .= '<select name="delcode_id">'.$this->getLocations().'</select>';
        $display .= '&nbsp;<input name="action" type="submit" value="Edit"/><br>';
        $display .= '<b>or</b><br><input name="action" type="submit" value="Add New Location"/>';
        $display .= '</div>';
        break;

      case self::EDIT_MODE_ADD:
        $display .= '<h1 align="center">Add Collection Location</h1>';
        $display .= $this->displayData();
        $display .= '<p align="center">
          <input type="submit" name="action" value="Save"/>&nbsp;
          <input type="reset" value="Reset Form"/>
          </p>';
        break;

      case self::EDIT_MODE_UPDATE:
        $display .= '<h1 align="center">Edit Collection Location</h1>';
        $display .= $this->displayData();
        $display .= '<p align="center">
          <input type="submit" name="action" value="Update"/>&nbsp;
          <input type="submit" name="action" value="Save As New"/>&nbsp;
          <input type="reset" value="Reset Form"/>
          </p>';
        break;
      
      default:
        $display .= '<p align="center">Error: unrecognised collection location form state.</p>';
        break;
    }
    $display .= '</form>';
    
    return $display;
  }

  private function displayData()
  {
    $display .= '<font face="arial"><table width="800" cellspacing="0" cellpadding="2" border="1" align="center">';
    $display .= '
      <tr>
        <th>&nbsp;</th>
        <th align="center">Data</th>
        <th align="center">Notes</th>
      </tr>
      <tr>
        <td><b>ID</b></td>
        <td bgcolor="#CCCCCC"><input name="delcode_id" type="text" size="3" maxlength="3" value="'.$this->delcode_id.'"/></td>
        <td>Three character ID code, e.g. "KEC".</td>
      </tr>
      <tr>
        <td><b>Location name</b></td>
        <td bgcolor="#CCCCCC"><input name="delcode" type="text" size="30" maxlength="35" value="'.$this->delcode.'"/></td>
        <td>Name of the collection location, e.g. "Kingston Environment Centre".</td>
      </tr>
      <tr>
        <td><b>Area or Route</b></td>
        <td bgcolor="#CCCCCC"><select name="route_id">'.$this->getRoutes().'</select></td>
        <td>The area or route to which this collection location belongs.</td>
      </tr>
      <tr>
        <td><b>Collection day</b></td>
        <td bgcolor="#CCCCCC"><input name="pickup_day" type="text" size="30" maxlength="35" value="'.$this->pickup_day.'"/></td>
        <td>Usual day of week/month of the collection displayed to members, e.g. "Saturdays, fortnightly".</td>
      </tr>
      <tr>
        <td><b>Collection time</b></td>
        <td bgcolor="#CCCCCC"><input name="pickup_time" type="text" size="15" maxlength="20" value="'.$this->pickup_time.'"/></td>
        <td>Usual time of day of the collection displayed to members, e.g. "11am to 12 noon".</td>
      </tr>
      <tr>
        <td><b>Final collection day offset</b></td>
        <td bgcolor="#CCCCCC"><select name="delday_offset">'.$this->getOffsets().'</select></td>
        <td>How many days does this collection take place before the end of the order cycle (final collection)?
        For example, if the final collection(s) of the cycle take place on Saturday, but this collection takes place
        on Friday, then the final collection day offset is "1".</td>
      </tr>
      <tr>
        <td><b>Location description</b></td>
        <td bgcolor="#CCCCCC"><textarea name="deldesc" cols="40" rows="5">'.$this->deldesc.'</textarea></td>
        <td>A description of the collection location, which may include HTML such as a link to a map.</td>
      </tr>
      <tr>
        <td><b>Activation state</b></td>
        <td bgcolor="#CCCCCC"><select name="inactive">'.$this->getActivationStates().'</select></td>
        <td>An active site is visible to members of the public on the locations page,
        and members can choose it when starting an order.  Standby sites are visible on the
        locations page, but cannot be selected by members starting an order.  Inactive sites
        are only visible to admins.</td>
      </tr>';

    $display .= '</table></font>';

    return $display;
  }

  private function getLocations()
  {
    $display_locations .= '<option value="">Select Location</option>';

    // Get all sites - including inactive ones, as user may wish to edit them
    // and then reactivate them
    $sql_locations = '
      SELECT
        delcode,
        delcode_id
      FROM
        '.TABLE_DELCODE.'
      ORDER BY
        delcode ASC';
    $result_locations = @mysql_query($sql_locations, $this->conn) or die("".mysql_error()."");
    while ( $row = mysql_fetch_array($result_locations) )
    {
      $delcode_id = $row['delcode_id'];

      $display_locations .= '
        <option value="'.$delcode_id.'"'
          .($delcode_id == $this->delcode_id ? ' selected="selected"' : '').'>'
          .stripslashes($row['delcode']).' ['.$delcode_id.']</option>';
    }

    return $display_locations;
  }

  private function getRoutes()
  {
    $display_routes .= '<option value="">Select Area or Route</option>';

    $sql_routes = '
      SELECT
        route_name,
        route_id
      FROM
        '.TABLE_ROUTE.'
      WHERE
        inactive = 0
      ORDER BY
        route_name ASC';
    $result_routes = @mysql_query($sql_routes, $this->conn) or die("".mysql_error()."");
    while ( $row = mysql_fetch_array($result_routes) )
    {
      $route_id = $row['route_id'];

      $display_routes .= '
        <option value="'.$route_id.'"'
          .($route_id == $this->route_id ? ' selected="selected"' : '').'>'
          .stripslashes($row['route_name']).'</option>';
    }

    return $display_routes;
  }

  private function getOffsets()
  {
    // Set this to something invalid
    // (value="" gives an offset of null, which will be treated as 0 -  a valid offset)
    $display_offsets .= '<option value="'.(self::FINAL_COLLECTION_OFFSET_MAX + 1).'">Select Offset</option>';

    for ($i = 0; $i <= self::FINAL_COLLECTION_OFFSET_MAX; $i++)
    {
      $display_offsets .= '<option value="'.$i.'"'
        .($i == $this->delday_offset ? ' selected="selected"' : '').'>'
        .$i.'</option>';
    }
    
    return $display_offsets;
  }

  private function getActivationStates()
  {
    $states_count = count($this->activation_states);

    // Set this to something invalid
    // (value="" gives a state of null, which will be treated as 0 -  a valid state)
    $display_states .= '<option value="'.$states_count.'">Select State</option>';

    for ($i = 0; $i < $states_count; $i++)
    {
      $display_states .= '<option value="'.$i.'"'
        .($i == $this->inactive ? ' selected="selected"' : '').'>'
        .$this->activation_states[$i].'</option>';
    }

    return $display_states;
  }
}
?>