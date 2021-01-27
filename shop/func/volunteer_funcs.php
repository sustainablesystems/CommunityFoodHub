<?php

require_once "../google/src/apiClient.php";
require_once "../google/src/contrib/apiCalendarService.php";
include_once ('config_foodcoop.php');

class VolunteerCal
{
  private $service;
  private $events;
  private $volunteers;
  private $member_id;
  private $member_email;
  private $member_name;

  function __construct($member_id)
  {
    $apiClient = new apiClient();
    $this->service = new apiCalendarService($apiClient);

    if (isset($_SESSION['oauth_access_token'])) {
      $apiClient->setAccessToken($_SESSION['oauth_access_token']);
    } else {
      $token = $apiClient->authenticate();
      $_SESSION['oauth_access_token'] = $token;
    }

    // Get all pages of events
    $event_list_pages = array();
    $event_list_curr = $this->service->events->listEvents(VOLUNTEER_ROTA_CAL_NAME);
    array_push($event_list_pages, $event_list_curr);

    while ($pageToken = $event_list_curr->getNextPageToken())
    {
      $optParams = array('pageToken' => $pageToken);
      $event_list_curr = $this->service->events->listEvents(VOLUNTEER_ROTA_CAL_NAME, $optParams);
      array_push($event_list_pages, $event_list_curr);
    }

    // Create the event list, expanding recurrent events
    $this->events = array();
    foreach ($event_list_pages as $event_list)
    {
      foreach ($event_list->getItems() as $event)
      {
        // Recurring event template
        if ($event->getRecurrence())
        {
          $recurring_id = $event->getId();
          $recurrings = $this->service->events->instances(VOLUNTEER_ROTA_CAL_NAME, $recurring_id);

          $items = $recurrings->getItems();
          if (is_array($items))
          {
            $this->events = array_merge($this->events, $items);
          }
        }
        else // Not a recurring event template
        {
          /* Don't process "exceptions" - individual events that were created from
           * a recurring event (a modified instance); these will be processed above. */
          /* Google calendar got confused when I moved the first event in a recurring series to another
           * fguangels calendar, then back again, then deleted it, then undid the deletion.  Now there's some
           * ghost event that has an ID but no data alongside it, so also ignore anything that has no start date
           */
          if ( (null == $event->getRecurringEventId())
            && (null != $event->getStart()))
          {
            //echo 'Event summary: '.$event->getSummary().', ID: '.$event->getID().', R.ID: '.$event->getRecurringEventId().'<br>';
            array_push($this->events, $event);
          }
        }
      } // For each event
    } // For each event list

    // Remove events that do not need volunteers
    $this->events = array_filter($this->events, "VolunteerCal::is_volunteer");
    
    // Set the display names to use for attendees (this also sets all volunteer email addresses)
    $success = VolunteerCal::get_attendee_names($this->events);
    if (!$success)
    {
      exit("Error: unable to get volunteer names.  Please contact the webmaster.");
    }

    // Sort the event list by date
    // Note: we do an initial sort in PHP, even though we have a javascript function
    // that will sort if the user clicks on the column headings, as we don't
    // want to rely on the user having javascript enabled.
    usort($this->events, "VolunteerCal::compare_date");

    // Get member's name and email address
    global $connection;
    $mem_query = '
      SELECT
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.email_address
      FROM
        '.TABLE_MEMBER.'
      WHERE
        '.TABLE_MEMBER.'.member_id = '.$member_id;
    $result_select = @mysql_query($mem_query, $connection) or die("".mysql_error()."");
    if ( $row = mysql_fetch_array($result_select) )
    {
      $this->member_email = $row['email_address'];
      $this->member_name = stripslashes($row['first_name']).' '.stripslashes($row['last_name']);
      $this->member_id = $member_id;
    }
    else
    {
      exit("Error: unable to get member name and email.  Please contact the webmaster.");
    }
  }

  // This is a volunteering event if the first word of the description is "Volunteers"
  private static function is_volunteer($event)
  {
    $text = explode(' ', $event->getDescription());
    return ( strtolower($text[0]) == 'volunteers' );
  }

  private static function compare_date($event1, $event2)
  {
    $date1 = VolunteerCal::get_event_date($event1);
    $date2 = VolunteerCal::get_event_date($event2);

    if ($date1 == $date2)
    {
      return 0;
    }
    else
    {
      return ($date1 < $date2) ? -1 : 1;
    }
  }

  private static function compare_venue($event1, $event2)
  {
    $venue1 = $event1->getSummary();
    $venue2 = $event2->getSummary();

    if ($venue1 == $venue2)
    {
      return 0;
    }
    else
    {
      return ($venue1 < $venue2) ? -1 : 1;
    }
  }

  /*
   * Creates a HTML table of volunteers for all upcoming dates in the Google calendar
   */
  function table()
  {
    $display .= '<form action="'.$PHP_SELF.'" method="post">';
    $display .= '<table cellspacing="0" cellpadding="0" id="volunteer_table">';
    $display .= '<thead>
                   <tr>
                     <th>Date</th><th>Venue</th><th>Times</th><th>Lead</th>
                     <th nowrap="nowrap">
                     Who\'s Volunteering (max '.VOLUNTEER_ROTA_MAX_VOLS.')
                     </th><th align="center">Me?</th>
                   </tr>
                 </thead>';
    $display .= '<tbody>';

    // Loop through available events
    foreach ($this->events as $event)
    {
      $display .= $this->display_event($event);
    }
    // N.B. The tfoot is ignored by the javascript column sort
    $display .= '</tbody>
      <tfoot>
        <tr>
          <td colspan="5" align="right"><font color="red">N.B. Update may take ~30 seconds to complete.&nbsp;</font></td>
          <td align="center">
            <input name="action" type="submit" value="Update" target="_top"/>
            <input name="member_name" type="hidden" value="'.$this->member_name.'"/>
            <input name="member_email" type="hidden" value="'.$this->member_email.'"/>
          </td>
        </tr>
      </tfoot>';
    $display .= '</table>';
    $display .= '<script type="text/javascript">
      window.addEvent( \'domready\', function(){
        new SortingTable(
          \'volunteer_table\',
          { last_row: false }
        );
      })</script>';

    $display .= '</form>';
 
    return $display;
  }


  // Generate a form that can be used to email all the other volunteers
  public function email_form($email_sent)
  {
    $display .= '<a name="emailform"/>';
    $display .= '<h1>Contact Volunteers</h1>';
    $display .= '<form method="post" action="#emailform">';
    $display .= '<table width="800" cellspacing="0" cellpadding="0" border="0">';

    if ($email_sent)
    {
      $display .= '<tr><td colspan="2" align="center"><font color="blue"><p><b>
          Your message was sent successfully.
          A copy was sent to your email address.
        </b></p></font></td></tr>';
    }
    
    $display .= '<tr><td valign="top" width="50%">
        You can send an email to all '.SITE_NAME.' volunteers using the form opposite.
        <p>Use this form if you want to swap dates with someone, for example.</p>
        <p><i>If you would like to lead a particular shift or can no longer lead, and for all other
        volunteering enquiries, please email: <a href="mailto:'.VOLUNTEER_EMAIL.'">'.VOLUNTEER_EMAIL.'</a>
          </i></p></td>';
    
    $display .= '<td valign="bottom" align="right">
        Subject:&nbsp;<input type="text" name="subject" maxlength="100" size="60"/>
        <textarea rows="8" cols="60" name="message"></textarea>
      </td></tr>';

    $display .= '<tr><td colspan="2" align="right"><input name="action" type="submit" value="Send Email"/>';

    $display .= ' To ';
    $display .= '<select name="recipients">';
    $display .= '<option value="everyone" selected>all volunteers</option>';
    $event_index = 0;
    foreach ($this->events as $event)
    {
      if ($event->getAttendees() != null)
      {
        $dateTime = VolunteerCal::get_event_date($event);
        $display .= '<option value="'.$event_index.'">volunteers for '.$dateTime->format("D, j M Y").'</option>';        
      }
      $event_index++;
    }
    $display .= '</select>';

    $display .= '</td></tr></table>';
    $display .= '</form>';
    
    return $display;
  }


  public function send_email($subject, $message, $recipients)
  {
    //echo 'Recipient event index: '.$recipients;

    $email_from = $this->member_name.' <'.$this->member_email.'>';
    
    // Generate recipient list
    $volunteer_emails = array();
    if ($recipients == 'everyone')
    {
      $volunteer_emails = array_keys($this->volunteers);
    }
    else // Sending email to volunteers for specific event
    {
      $event = $this->events[intval($recipients)];
      $volunteers = $event->getAttendees();

      // Should always be some recipients, otherwise the event wouldn't have been selectable
      if ($volunteers != null)
      {
        foreach ($volunteers as $vol)
        {
          array_push($volunteer_emails, $vol->getEmail());
        }
      }
    }

    if (!empty($volunteer_emails))
    {
      $emails_with_name = array();
      foreach ($volunteer_emails as $vol_email)
      {
        array_push($emails_with_name, $this->volunteers[$vol_email].' <'.$vol_email.'>');
      }
      // Add the member's email, if it's not there already
      // (it won't be if they haven't volunteered for anything yet)
      if (false == array_search($this->member_name, $this->volunteers))
      {
        array_push($emails_with_name, $email_from);
      }
      $email_to = implode(',', $emails_with_name);

      $email_headers  = "From: ".$email_from."\n";
      $email_headers .= "CC: ".VOLUNTEER_EMAIL."\n";
      $email_headers .= "Errors-To: ".WEBMASTER_EMAIL."\n";
      $email_headers .= "MIME-Version: 1.0\n";
      $email_headers .= "Content-type: text/plain; charset=us-ascii\n";
      $email_headers .= "Message-ID: <".md5(uniqid(time()))."@".DOMAIN_NAME.">\n";
      $email_headers .= "X-Mailer: PHP ".phpversion()."\n";
      $email_headers .= "X-Priority: 3\n";
      $email_headers .= "X-AntiAbuse: This is a machine-generated response to a user-submitted form at http://".DOMAIN_NAME.PATH.".\n\n";

      $email_body .= trim($message)."\n";

      $email_subject .= SITE_NAME_SHORT.' volunteers: '.trim($subject);

      /*echo "<br>Mail to: ".$email_to;
      echo "<br>Message: ".$email_body;
      echo "<br>Subject: ".$email_subject;
      echo "<br>Headers: ".$email_headers;
      return true;*/

      return mail($email_to, $email_subject, $email_body, $email_headers);
    }
    else // No volunteers have signed up yet
    {
      return false;
    }
  }


  private function get_attendee_names($all_events)
  {
    /*  Function works as follows:
     *
     * 1) Get array of all the email addresses of volunteers and an array of all volunteers.
     * 2) remove duplicate emails
     * 3) Get names for the email addresses from our database
     * 4) Create an associative array of names indexed by emails
     * 5) Walk through the array of all volunteers and use their emails
     *    to get the names using the associative array.
     *    Then use the name to set the volunteer DisplayName.
     */

    // Get volunteers and their email addresses
    $volunteers = array();
    $volunteer_emails = array();
    foreach ($all_events as $event)
    {
      $event_volunteers = $event->getAttendees();
      if ($event_volunteers != null)
      {
        foreach ($event_volunteers as $vol)
        {
          array_push($volunteers, $vol);
          array_push($volunteer_emails, $vol->getEmail());
        }
      }
    }

    // Remove duplicates
    $unique_emails = array_unique($volunteer_emails);

    $unique_email_list = "'".implode("','", $unique_emails)."'";

    // Get the names for the email addresses
    global $connection;
    $names_for_emails = array();
    $mem_query = '
      SELECT
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.email_address
      FROM
        '.TABLE_MEMBER.'
      WHERE
        '.TABLE_MEMBER.'.email_address
      IN ('.$unique_email_list.')';
    $result_select = @mysql_query($mem_query, $connection) or die("".mysql_error()."");
    while ($row = mysql_fetch_array($result_select))
    {
      $member_email = $row['email_address'];
      $member_name = stripslashes($row['first_name']).' '.stripslashes($row['last_name']);

      $names_for_emails[$member_email] = $member_name;
    }

    // Go through the array of volunteers setting their display names
    $success = array_walk($volunteers, 'VolunteerCal::set_display_name', $names_for_emails);

    // Save the full volunteer email list
    $this->volunteers = $names_for_emails;
    
    return $success;
  }

  
  private function set_display_name($attendee, $array_key, $names_for_emails)
  {
    // Use the EventAttendee's email address to look up
    // and then set their DisplayName using the names_for_emails associative array
    $attendee_email = $attendee->getEmail();
    $attendee_name = $names_for_emails[$attendee_email];
    if (null != $attendee_name)
    {
      $attendee->setDisplayName($attendee_name);
    }
  }


  private function get_event_date($event)
  {
    $dateStr = $event->getStart()->getDate();
    if ($dateStr == "")
    {
      $dateStr = $event->getStart()->getDateTime();
    }
    $dateTime = date_create( $dateStr );

    return $dateTime;
  }

  
  private function display_event($event)
  {
    $dateTime = VolunteerCal::get_event_date($event);
    $is_old_event = ($dateTime->getTimestamp() < time());
    $font_start = ($is_old_event ? '<font color="grey">' : '<b>');
    $font_end = ($is_old_event ? '</font>' : '</b>');

    $display .= '<tr>';
    
    $display .= '<td nowrap="nowrap">'.$font_start.$dateTime->format("D, j M Y").$font_end.'</td>';
    $display .= '<td>'.$font_start.$event->getSummary().$font_end.'</td>';
    //$display .= '<td>'.$event->getSummary().', ID: '.$event->getID().', R.ID: '.$event->getRecurringEventId().'</td>';

    // Extract volunteer times and lead from the description string
    list($volunteer_times, , $volunteer_lead) = mb_split(',|:', $event->getDescription());

    // Remove "volunteers" from the start of the times string
    $volunteer_times = str_ireplace('volunteers', '', $volunteer_times);

    $display .= '<td>'.$font_start.trim($volunteer_times).$font_end.'</td>';
    $display .= '<td>'.$font_start.($volunteer_lead ? trim($volunteer_lead) : '<i>?</i>').$font_end.'</td>';

    $volunteers = $event->getAttendees();
    if ($volunteers != null)
    {
      $volunteer_names = array();
      $found_member = false;
      $member_name = null;
      foreach ($volunteers as $volunteer)
      {
        $volunteer_email = $volunteer->getEmail();
        $volunteer_display_name = $volunteer->getDisplayName();

        $volunteer_name = ($volunteer_display_name != ''
                ? $volunteer_display_name : $volunteer_email);

        // If this is the member who is logged in and viewing the calendar
        if ($volunteer_email == $this->member_email)
        {
          $found_member = true;
        }
        else
        {
          array_push($volunteer_names, $volunteer_name);
        }
      }
      
      if ($found_member)
      {
        // Add the member name at the start of the array
        rsort($volunteer_names);
        array_push($volunteer_names, '<font color="red">'.$this->member_name.'</font>');
        $volunteer_names = array_reverse($volunteer_names);
      }
      else
      {
        sort($volunteer_names);
      }
      
      $display .= '<td>'.$font_start.implode(', ', $volunteer_names).$font_end.'</td>';
    }
    else // No volunteers (yet)
    {
      $display .= '<td>'.($is_old_event ? '&nbsp;' : '<i>Tick box to sign up</i>').'</td>';
    }
    
    if ( (count($volunteers) < VOLUNTEER_ROTA_MAX_VOLS) || $found_member)
    {
      $display .= '<td align="center">
            <input name="volunteer_event_ids[]" type="checkbox"
              value="'.$event->getID().'"'
              .($is_old_event ? ' disabled="disabled"' : '')
              .($found_member ? ' checked="checked"' : '').'/>
          </td>';
    }
    else
    {
      $display .= '<td align="center">'.$font_start.'<i>Full</i>'.$font_end.'</td>';
    }

    $display .= '</tr>';

    return $display;
  }


  public function update($volunteer_event_ids)
  {
    //echo "UPDATING: ".$member_name.$member_email;

    // Loop through available events and update each individually (slow - multiple PUT requests).
    // Note: batch updating of events is not currently supported by Google
    // This is a feature request for v3 of the Calendar API.  See discussion:
    // https://groups.google.com/forum/#!msg/google-calendar-api/Avu8sg_01qc/IWR9EIAm8aIJ
    foreach ($this->events as $event)
    {
      // Don't process old events - they're read-only
      $dateTime = VolunteerCal::get_event_date($event);
      $is_old_event = ($dateTime->getTimestamp() < time());
      if (!$is_old_event)
      {
        if ($this->update_attendance($event, $volunteer_event_ids))
        {
          $this->service->events->update(VOLUNTEER_ROTA_CAL_NAME, $event->getId(), $event);
        }
      }
    }

    return VolunteerCal::email_attendance($volunteer_event_ids);
  }


  private function update_attendance($event, $volunteer_event_ids)
  {
    $attendance_changed = false;

    $was_attending = $this->remove_member($event);

    //echo "Count of event IDs in the array: ".count($volunteer_event_ids);

    // If the volunteer is attending, add them
    if (!empty($volunteer_event_ids) && in_array($event->getID(), $volunteer_event_ids))
    {
      $this->add_member($event);

      // Only update the event if the volunteer wasn't previously attending
      if (!$was_attending)
      {
        $attendance_changed = true;
      }
    }
    else // Volunteer isn't attending
    {
      // Only update the event if the volunteer was previously attending
      if ($was_attending)
      {
        $attendance_changed = true;
      }
    }

    return $attendance_changed;
  }


  private function remove_member($event)
  {
    //echo "REMOVING: ".$member_name.$member_email;

    $was_attending = false;

    // Remove the volunteer from the event
    $volunteers_old = $event->getAttendees();
    if ($volunteers_old != null)
    {
      $volunteers_new = array();
      foreach ($volunteers_old as $volunteer)
      {
        if ($volunteer->getEmail() == $this->member_email)
        {
          $was_attending = true;
        }
        else
        {
          array_push($volunteers_new, $volunteer);
        }
      }
      $event->setAttendees($volunteers_new);
    }

    return $was_attending;
  }


  private function add_member($event)
  {
    //echo "ADDING: ".$member_name.$member_email;
 
    // Create a new attendee for the volunteer
    $attendee = new EventAttendee();
    $attendee->setEmail($this->member_email);
    $attendee->setDisplayName($this->member_name);
    $attendee->setResponseStatus('accepted');

    // Add the volunteer to the event
    $volunteers = $event->getAttendees();
    if (null == $volunteers)
    {
      $volunteers = array();
    }
    array_push($volunteers, $attendee);
    $event->setAttendees($volunteers);
  }

  /*
   * Send member an email confirming their volunteering dates
   */
  private function email_attendance($volunteer_event_ids)
  {
    $body_text .= "Dear ".$this->member_name.",\n\n";
    
    if(empty($volunteer_event_ids))
    {
      $body_text .= "You are not volunteering at any future ".SITE_NAME." events.\n\n";
    }
    else
    {
      $body_text .= "Your future volunteering ".SITE_NAME." events are:\n\n";

      foreach ($this->events as $event)
      {
        // Don't include past events
        $dateTime = VolunteerCal::get_event_date($event);
        $is_old_event = ($dateTime->getTimestamp() < time());

        if (!$is_old_event && in_array($event->getID(), $volunteer_event_ids))
        {
          $body_text .= " * ".$dateTime->format("D, j M Y")." -- ";
          $body_text .= $event->getSummary()." -- ";
          $body_text .= $event->getDescription()."\n";
        }
      }

      $body_text .= "\nThank you for volunteering for ".SITE_NAME."!\n\n";
    }

    $body_text .= "To update your volunteering activities and email other volunteers,";
    $body_text .= " please log in to the ".SITE_NAME." website: http://".DOMAIN_NAME.PATH.".\n\n";
    $body_text .= "If you would like to lead a particular shift or can no longer lead, and for";
    $body_text .= " all other volunteering enquiries, please email: ".VOLUNTEER_EMAIL;

    $email_to = $this->member_email.','.VOLUNTEER_EMAIL;

    $email_subject = SITE_NAME.' volunteering - '.$this->member_name;

    $email_headers  = "From: ".VOLUNTEER_EMAIL."\n";
    $email_headers .= "Errors-To: ".WEBMASTER_EMAIL."\n";
    $email_headers .= "MIME-Version: 1.0\n";
    $email_headers .= "Content-type: text/plain; charset=us-ascii\n";
    $email_headers .= "Message-ID: <".md5(uniqid(time()))."@".DOMAIN_NAME.">\n";
    $email_headers .= "X-Mailer: PHP ".phpversion()."\n";
    $email_headers .= "X-Priority: 3\n";
    $email_headers .= "X-AntiAbuse: This is a machine-generated response to a user-submitted form at http://".DOMAIN_NAME.PATH.".\n\n";

    /*echo "<br>Mail to: ".$email_to;
    echo "<br>Message: ".$email_body;
    echo "<br>Subject: ".$email_subject;
    echo "<br>Headers: ".$email_headers;
    return true;*/

    return mail($email_to, $email_subject, $body_text, $email_headers);
  }
}
?>
