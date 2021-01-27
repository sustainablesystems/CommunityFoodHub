<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
include_once('../func/delivery_funcs.php');
session_start();
validate_user();

/////////////////////////////////////////////////////////////////////////////////////
///                                                                               ///
///                                  FUNCTIONS                                    ///
///                                                                               ///
/////////////////////////////////////////////////////////////////////////////////////

function getTableList () {
  $tables = mysql_list_tables(DB_NAME);
  $table_names = array ();
  for($i = 0; $i < mysql_num_rows($tables); $i++) {
    $table = mysql_tablename ($tables, $i);
    array_push ($table_names, $table);
    }
  return ($table_names);
  }

$action = $_POST['action'];

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                     BEGIN NEW PAGE - NO SUBMITTED DATA                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////


if (!$action)
{
  // This is a new page visit so start out with the forms
  $query = '
    SELECT
      * 
    FROM
      '.TABLE_CURDEL;
  $result = @mysql_query($query, $connection) or die("<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href=\"mailto:webmaster@$domainname\">webmaster@$domainname</a><br><br><b>Error:</b> Current Delivery Cycle " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
  $row = mysql_fetch_array($result); // Only need the first row
  $current_delivery_delivery_id = $row['delivery_id'];
  $current_delivery_date_open = date_create($row['date_open']);
  $current_delivery_date_closed = date_create($row['date_closed']);
  $current_delivery_delivery_date = date_create($row['delivery_date']);
  $current_delivery_order_cycle_closed = stripslashes($row['order_cycle_closed']);
  $current_delivery_msg_all = stripslashes ($row['msg_all']);
  $current_delivery_msg_bottom = stripslashes ($row['msg_bottom']);
  $current_delivery_disclaimer = stripslashes ($row['disclaimer']);
  $current_delivery_special_order = $row['special_order'];

  // Find out if there is a database table named for the current delivery date.
  // TODO: Review.  Won't work if this is run to create the very first
  // cycle at first installation
  $prior_delivery = date_format($current_delivery_delivery_date, "Y-m-d");
  if ($prior_delivery)
  {
    $target_table = "product_list_".$prior_delivery;
  }
  else
  {
    $target_table = "NO PREVIOUS PRODUCT LIST - NEW INSTALLATION (FIXME)";
  }

  $table_list = getTableList();
  if (!array_search  ($target_table, $table_list))
  {
    echo 'Error: an expected table '.$target_table.' does not exist. Please contact webmaster.';
    exit (1);
  }

  // Check if the order cycle has finished.
  // If so, allow the user to start a new one.
  // Otherwise only allow them to change dates rather than create new tables etc.
  if ( $current_delivery_delivery_date < date_create("now") )
  {
    $cycle_ended = true;
    $display .= '<h1 align="center">Start New Cycle</h1>';
  }
  else
  {
    $cycle_ended = false;
    $display .= '<h1 align="center">Update Current Cycle</h1>';
  }

  $cycle_time = date_interval_create_from_date_string(''
          .($cycle_ended ? DAYS_PER_CYCLE : '0').' days');

  $display .= '
  <form action="'.$_SERVER['PHP_SELF'].'" method="POST">
    <input type="hidden" name="delivery_id" value="'
    .($cycle_ended ? ($current_delivery_delivery_id + 1) : $current_delivery_delivery_id).'">
    <input type="hidden" name="special_order" value="0">
    <input type="hidden" name="coopfee" value="0.0">
    <input type="hidden" name="open" value="0">
    <input type="hidden" name="prior_product_list" value="'.$target_table.'">';

  // Note: the "order_cycle_closed" text will be set based on the
  // suggested "date_closed" for new cycles, but rounded down to the hour.
  // So 10.10am becomes 10am, as does 10.55am.
  $display .= '
  <p align="center">Please check the data for the '
  .($cycle_ended ? 'next' : 'current').' order cycle, and amend as appropriate.<br>
  <font color="red">Note: click on the calendar icon to change the dates if they are incorrect.</font></p>
  <table border="1" width="800" align="center" cellspacing="0" cellpadding="2">
    <tr>
      <th valign="top">&nbsp;</th>'
      .($cycle_ended ? '
        <th valign="top">Previous cycle</th>
        <th valign="top">Next cycle</th>'
      : '<th valign="top">Current cycle</th>').'
      <th valign="top" width="35%">Notes</th>
    </tr>
    <tr>
      <td valign="top"><b>Cycle number</b></td>
      <td valign="top">'.$current_delivery_delivery_id.'</td>'
      .($cycle_ended ? '
        <td valign="top">'.($current_delivery_delivery_id + 1).'</td>'
      : '').'
      <td valign="top">&nbsp;</td>
    </tr>
    <tr>
      <td valign="top"><b>Cycle opening</b></td>'
      .($cycle_ended ? '
        <td valign="top">'.date_format($current_delivery_date_open, 'd-m-Y @ H:i').'</td>'
      : '').'
      <td valign="top">
        <input name="date_open" type="text" readonly="readonly" value="'
          .date_format(date_add($current_delivery_date_open, $cycle_time), 'd-m-Y @ H:i')
          .'" class="date datetime_toggled" style="display: inline"/>
        <img src="../grfx/calendar.gif" class="datetime_toggler" style="position: relative; top: 3px; margin-left: 4px;" />
      </td>
      <td valign="top">Date and time when the order automatically opens.</td>
    </tr>
    <tr>
      <td valign="top"><b>Order closing</b></td>'
      .($cycle_ended ? '
        <td valign="top">'.date_format($current_delivery_date_closed, 'd-m-Y @ H:i').'</td>'
      : '').'
      <td valign="top">
        <input name="date_closed" type="text" readonly="readonly" value="'
          .date_format(date_add($current_delivery_date_closed, $cycle_time), 'd-m-Y @ H:i')
          .'" class="date datetime_toggled" style="display: inline"/>
        <img src="../grfx/calendar.gif" class="datetime_toggler" style="position: relative; top: 3px; margin-left: 4px;" />
      </td>
      <td valign="top">Date and time when the order automatically closes.</td>
    </tr>
    <tr>
      <td valign="top"><b>Order closing text</b></td>'
      .($cycle_ended ? '
        <td valign="top">'.$current_delivery_order_cycle_closed.'</td>'
      : '').'
      <td valign="top"><input name="order_cycle_closed" size="33" maxlength="50" value="'
      .($cycle_ended ?
        date_format($current_delivery_date_closed, 'l, j M @ ga')
      : $current_delivery_order_cycle_closed).'"></td>
      <td valign="top">Date and time of order closing actually displayed to customers on the website and invoices.
      <br><br>(For example, customers may be told that the order closes 10 minutes before the automatic closing time to cater for stragglers.)</td>
    </tr>
    <tr>
      <td valign="top"><b>Final collection date</b></td>'
      .($cycle_ended ? '
        <td valign="top">'.date_format($current_delivery_delivery_date, 'd-m-Y').'</td>'
      : '').'
      <td valign="top">
        <input name="delivery_date" type="text" readonly="readonly" value="'
          .date_format(date_add($current_delivery_delivery_date, $cycle_time), 'd-m-Y')
          .'" class="date date_toggled" style="display: inline"/>
        <img src="../grfx/calendar.gif" class="date_toggler" style="position: relative; top: 3px; margin-left: 4px;" />
      </td>
      <td valign="top">Date of the final collection of the cycle.</td>
    </tr>
    <tr>
      <td valign="top"><b>Invoice message top</b></td>'
      .($cycle_ended ? '
        <td valign="top">'.$current_delivery_msg_all.'</td>'
      : '').'
      <td valign="top"><textarea name="msg_all" rows="10" cols="25">'.preg_replace ('/\<br\>/', "\n", $current_delivery_msg_all).'</textarea></td>
      <td valign="top">This message appears prominently near the top of customer invoices.
      <br><br>It can be used to alert customers to any news or changes relating to
      the current cycle, for example a new payment method.  Alternatively it could
      be used to give customers a seasonal greeting.</td>
    </tr>
    <tr>
      <td valign="top"><b>Invoice message bottom</b></td>'
      .($cycle_ended ? '
        <td valign="top">'.$current_delivery_msg_bottom.'</td>'
      : '').'
      <td valign="top"><textarea name="msg_bottom" rows="10" cols="25">'.preg_replace ('/\<br\>/', "\n", $current_delivery_msg_bottom).'</textarea></td>
      <td valign="top">This message appears near the bottom of customer invoices.</td>
    </tr>
    <tr>
      <td valign="top"><b>Invoice message disclaimer</b></td>'
      .($cycle_ended ? '
        <td valign="top">'.$current_delivery_disclaimer.'</td>'
      : '').'
      <td valign="top"><textarea name="disclaimer" rows="10" cols="25">'.preg_replace ('/\<br\>/', "\n", $current_delivery_disclaimer).'</textarea></td>
      <td valign="top">This disclaimer appears at the very bottom of customer invoices.</td>
    </tr>
  </table>'
  .($cycle_ended ? '
    <p align="center"><input type="submit" name="action" value="Start New Cycle"/></p>'
  : '<p align="center"><input type="submit" name="action" value="Update Current Cycle"/></p>').'
  </form>';
}

  // Use this for per-order fee, messages top/bottom (add disclaimer)
  /*echo '
    <tr>
      <td valign="top">coopfee</td>
      <td valign="top">'.CURSYM.number_format ($prior_delivery_dates_coopfee, 2).'</td>
      <td valign="top">'.CURSYM.' <input name="coopfee" size="10" maxlength="5" value="'.number_format ($prior_delivery_dates_coopfee, 2).'"></td>
      <td valign="top">Admin and packing fee per order</td>
    </tr>';
   */

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                        BEGIN PROCESSING SUBMITTED PAGE                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

elseif ($action == "Start New Cycle" || $action == "Update Current Cycle")
{
  // Make sure all submitted data looks good...
  // Assume everything will go well
  unset ($error);

  // We should have all values and they should agree.
  $prior_product_list = $_POST['prior_product_list'];
  $coopfee = $_POST['coopfee'];
  $special_order = $_POST['special_order'];
  $delivery_id = $_POST['delivery_id'];
  $open = $_POST['open'];

  $date_obj = date_create_from_format('d-m-Y @ H:i', $_POST['date_open']);
  $date_open = date_format($date_obj, 'Y-m-d H:i:s');
  
  $date_obj = date_create_from_format('d-m-Y', $_POST['delivery_date']);
  $delivery_date = date_format($date_obj, 'Y-m-d');

  $order_cycle_closed = $_POST['order_cycle_closed'];

  // Use date_closed for closing timestamp
  $date_obj = date_create_from_format('d-m-Y @ H:i', $_POST['date_closed']);
  $closing_timestamp = date_format($date_obj, 'YmdHis');

  $date_closed = date_format($date_obj, 'Y-m-d H:i:s');

  $msg_all = $_POST['msg_all'];
  $msg_bottom = $_POST['msg_bottom'];
  $disclaimer = $_POST['disclaimer'];
  

///                                                                          ///
///                                  STEP ONE                                ///
///                                                                          ///

  $product_list = 'product_list_'.trim ($delivery_date);
  if (strtotime ($delivery_date) < 1000)
  {
    $error_array[0] .= "Invalid delivery date!<br>\n";
  }

  if ($action == "Start New Cycle")
  {
    $query_array[0] = '
      CREATE TABLE
        `'.DB_NAME.'`.`'.$product_list.'`
      SELECT
        *
      FROM
        `'.DB_NAME.'`.`'.TABLE_PRODUCT.'`';
  }
  else // Update Current Delivery
  {
    $query_array[0] = '
      ALTER TABLE
        `'.$prior_product_list.'`
      RENAME `'.$product_list.'`';
  }
  
///                                                                          ///
///                                  STEP TWO                                ///
///                                                                          ///

  if ($action == "Start New Cycle")
  {
    $table_list = getTableList();
    if (! array_search ($prior_product_list, $table_list)) {
      $error_array[1] .= "Prior product table: $prior_product_list does not exist!<br>\n";
      }
    $query_array[1] = '
      DROP TABLE
        `'.TABLE_PRODUCT_PREV.'`';
    $query_array[2] = '
      ALTER TABLE
        `'.$prior_product_list.'`
      RENAME `'.TABLE_PRODUCT_PREV.'`';
  }

///                                                                          ///
///                                 STEP THREE                               ///
///                                                                          ///

  if (strtotime ($delivery_date) < 1000) {
   $error_array[3] .= "Invalid delivery date!<br>\n";
     }
     
  if ($action == "Start New Cycle")
  {
    if ($special_order != 1 && $special_order != 0) {
      $error_array[3] .= "Special order is not 0 or 1!<br>\n";
        }
    if (! is_numeric ($coopfee)) {
      $error_array[3] .= "Coop fee is not a number!<br>\n";
        }

      $query_array[3] = '
        INSERT INTO
          `'.TABLE_DELDATE.'`
            (
              `delivery_id`,
              `delivery_date`,
              `special_order`,
              `coopfee`
            )
          VALUES
            (
              "'.$delivery_id.'",
              "'.$delivery_date.'",
              "'.$special_order.'",
              "'.$coopfee.'"
            )';
  }
  else // Update Current Cycle
  {
    $query_array[3] = '
        UPDATE
          `'.TABLE_DELDATE.'`
        SET
          delivery_date = "'.$delivery_date.'"
        WHERE
          delivery_id = "'.$delivery_id.'"';
  }

///                                                                          ///
///                                 STEP FOUR                                ///
///                                                                          ///

  // Update the current delivery table for both Start New Cycle and Update Current Cycle
  $query = '
    SELECT
      MAX(delivery_id) AS last_delivery_id
    FROM
      `'.TABLE_DELDATE.'`';
  $result = @mysql_query($query, $connection) or $error_array[4] = "SQL Error while retrieving next delivery id from $table_deldate!\n";
  $row = mysql_fetch_array($result); // Only need the first row
  if (($action == "Start New Cycle" ? $row['last_delivery_id'] + 1 : $row['last_delivery_id']) != $delivery_id) {
    $error_array[4] .= "Incorrect delivery_id!<br>\n";
    }
  if (strtotime ($date_open) < 1000) {
    $error_array[4] .= "Invalid date_open date!<br>\n";
    }
  if (strtotime ($closing_timestamp) < 1000) {
    $error_array[4] .= "Invalid closing_timestamp date!<br>\n";
    }
  if (strtotime ($date_closed) < 1000) {
    $error_array[4] .= "Invalid date_closed date!<br>\n";
    }
  if (strtotime ($delivery_date) < 1000) {
    $error_array[4] .= "Invalid delivery_date date!<br>\n";
    }

  $query_array[4] = '
    UPDATE
      `'.TABLE_CURDEL.'`
    SET
      delivery_id = "'.$delivery_id.'",
      open = "0",
      date_open = "'.$date_open.'",
      closing_timestamp = "'.$closing_timestamp.'",
      date_closed = "'.$date_closed.'",
      delivery_date = "'.$delivery_date.'",
      order_cycle_closed = "'.mysql_real_escape_string($order_cycle_closed).'",
      msg_all = "'.mysql_real_escape_string ($msg_all).'",
      msg_bottom = "'.mysql_real_escape_string ($msg_bottom).'",
      disclaimer = "'.mysql_real_escape_string ($disclaimer).'",
      special_order = "0"';

///                                                                          ///
///                                 STEP FIVE                                ///
///                                                                          ///

  if ($action == "Start New Cycle")
  {
    // We do all the queries together so this table hasn't been renamed yet,
    // Otherwise we would be querying product_list_previous.
    $query = '
      SELECT
        MAX( product_id ) AS max_id
      FROM
        `'.$prior_product_list.'`';
    $result = @mysql_query($query, $connection) or $error_array[5] = "SQL Error while retrieving maximum former product id!\n";
    $row = mysql_fetch_array($result); // Only need the first row
    $max_id = $row['max_id'];

    $query_array[5] = '
      UPDATE
        '.TABLE_PRODUCT.'
      SET
        new="0",
        changed="0"
      WHERE
        product_id <= "'.$max_id.'"';
    $query_array[6] = '
      UPDATE
        '.TABLE_PRODUCT_PREP.'
      SET
        new="0",
        changed="0"
      WHERE
        product_id <= "'.$max_id.'"';
  }

///                                                                          ///
///                                 STEP SEVEN                               ///
///                                                                          ///

  // Create query to copy compound product table to dated version
  $compound_prod_list = 'compound_product_list_'.trim ($delivery_date);
  if (strtotime ($delivery_date) < 1000) {
    $error_array[3] .= "Invalid delivery date!<br>\n";
      }

  if ($action == "Start New Cycle")
  {
    $query_array[7] = '
      CREATE TABLE
          `'.DB_NAME.'`.`'.$compound_prod_list.'`
      SELECT
        *
      FROM
        `'.DB_NAME.'`.`'.TABLE_COMPOUND_PRODUCT.'`';
  }
  else // Update Current Cycle
  {
    $query_array[7] = '
      ALTER TABLE
        `compound_'.$prior_product_list.'`
      RENAME `'.$compound_prod_list.'`';
  }

///                                                                          ///
///                                  STEP EIGHT                              ///
///                                                                          ///

  if ($action == "Start New Cycle")
  {
    $table_list = getTableList();
    if (! array_search ('compound_'.$prior_product_list, $table_list)) {
      $error_array[8] .= "Prior compound product table: compound_$prior_product_list does not exist!<br>\n";
      }
    $query_array[8] = '
      DROP TABLE
        `'.TABLE_COMPOUND_PRODUCT_PREV.'`';
    $query_array[9] = '
      ALTER TABLE
        `compound_'.$prior_product_list.'`
      RENAME `'.TABLE_COMPOUND_PRODUCT_PREV.'`';
  }

///                                                                          ///
///                                  STEP NINE                               ///
///                                                                          ///

  if ($action == "Start New Cycle")
  {
    // Remove any non-submitted baskets from the system
    $query_array[10] = '
      DELETE FROM '.TABLE_BASKET.'
      WHERE basket_id IN
        (SELECT basket_id
        FROM '.TABLE_BASKET_ALL.'
        WHERE submitted = 0)';
    $query_array[11] = '
      DELETE FROM
        '.TABLE_BASKET_ALL.'
      WHERE submitted = 0';
  }

///                                                                          ///
///                                   FINISH                                 ///
///                                                                          ///

  // Attempt to log results to file - if something went wrong we need to capture it properly
  $logFile = ($action == "Start New Cycle" ? "start_new" : "update_current")."_cycle.html";
  $fh = fopen($logFile, 'w+');

  $is_error = false;
  for ($step = 0; $step <= 11; $step++ )
  {
    if ($error_array[$step] == '')
    {
      $error_array[$step] = '-- NO ERRORS --';
    }
    else
    {
      $is_error = true;
    }
    $display .= '<font style="color:#a00000;">ERROR ['.$step.']: '.$error_array[$step].'</font><br>'."\n";
    $display .= 'QUERY ['.$step.']: '.$query_array[$step].'<br><hr>'."\n";
  }

  // TODO: This process is ugly.
  // MySQL now supports transactions (with InnoDB storage engine), we should use them, particularly here.
  if ($is_error)
  {
    $error_text = "<h1>ERRORS WERE FOUND... EXECUTION STOPPED BEFORE DATABASE UPDATE.  Please contact webmaster.</h1>";
    $error_text .= '<p>New cycle was NOT set up successfully. <a href="index.php">Return to Admin Home</a></p>';
    $error_text .= $display;

    echo $error_text;
    if($fh)
    {
      fwrite($fh, $error_text);
    }
    exit(1);
  }
  else
  {
    for ($step = 0; $step <= 11; $step++ )
    {
      if ($query_array[$step] != '')
      {
        $result = @mysql_query($query_array[$step], $connection);
        if (!$result)
        {
          $error_text = "<h1>PROCESS FAILED TO COMPLETE STEP $step! This is a very serious error - please contact webmaster as soon as possible.</h1>";
          $error_text .= "<p>MySQL Error: ".mysql_error()."<br>MySQL Error No: ".mysql_errno()."</p>";
          $error_text .= '<p>New cycle was NOT set up successfully. <a href="index.php">Return to Admin Home</a></p>';
          $error_text .= $display;

          echo $error_text;
          if($fh)
          {
            fwrite($fh, $error_text);
          }
          exit(1);
        }

        $display .= "STEP $step COMPLETED SUCCESSFULLY<br>";
      }
      else
      {
        $display .= "STEP $step SKIPPED<br>";
      }
    }
  }

  // Refresh session variables for the current delivery
  update_session_current_delivery();

  if($fh)
  {
    fwrite($fh, $display);
    fclose($fh);
  }
}
?>

<html>
  <head>
    <title>Start or Update Cycle</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en-uk">
    <meta name="google-site-verification" content="C4BGfodhuPP-Io9DFbv5D640QqsCZWP7Czfx6pi-3uw" />

    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <link rel='stylesheet' type='text/css' href='../css/datepicker.css' />

    <script type='text/javascript' src='../js/mootools-1.2.5-core-nc.js'></script>
    <script type='text/javascript' src='../js/datepicker.js'></script>
    <script type='text/javascript'>
      window.addEvent('load', function() {
	new DatePicker('.datetime_toggled', {
		pickerClass: 'datepicker',
                timePicker: true, format: 'd-m-Y @ H:i',
                inputOutputFormat: 'd-m-Y @ H:i',
		toggleElements: '.datetime_toggler'
	});
      });
      window.addEvent('load', function() {
	new DatePicker('.date_toggled', {
		pickerClass: 'datepicker',
                inputOutputFormat: 'd-m-Y',
		toggleElements: '.date_toggler'
	});
      });
    </script>
  </head>

  <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-bottom:1px solid #000000;background-color:#FFFFFF;">
      <tr>
        <td align="center">
          <div>
            <a href="<?php echo PATH; ?>admin/">
                <img src="<?php echo DIR_GRAPHICS; ?>logo.gif" border="0" alt="Food cooperative" align="center">
            </a>
            <br/>
          </div>
        </td>
      </tr>
    </table>
    <br>
    
<?php include("template_header_footer_common.php");?>

    <font face="arial">    
<?php
  if ($action == "Start New Cycle")
  {
    echo '<h1 align="center">Start New Cycle</h1>';
    echo '<p align="center">New cycle set up successfully. <a href="index.php">Return to Admin Home</a></p>';
  }
  else if ($action == "Update Current Cycle")
  {
    echo '<h1 align="center">Update Current Cycle</h1>';
    echo '<p align="center">Cycle updated successfully. <a href="index.php">Return to Admin Home</a></p>';
  }
  else
  {
    echo $display;
  }

  include("template_footer.php");
?>