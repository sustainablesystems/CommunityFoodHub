<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();
include_once ("balance.php");

$message = '';

// DELETE AN ADJUSTMENT
if ( $delete_adjustment == 'yes' )
  {
    /*
     * Rather than "delete" an adjustment transaction by creating
     * another that cancels it out, let's just delete it and update the
     * balance.  Only adjustments that haven't yet been applied to a
     * customer can be deleted anyway.
     */
    if ( $_POST['transaction_id_passed'] )
      {
        $del_adj_type = $_GET["adj_type"];

        if ($del_adj_type == "customer")
        {
          $sql_delete_adj = '
            DELETE FROM
              '.TABLE_CUSTOMER_ADJ.'
            WHERE
              transaction_id = "'.$_POST['transaction_id_passed'].'"';
          $result_del = @mysql_query($sql_delete_adj,$connection) or die(mysql_error());
        }
        
        $sqldelete = '
          DELETE FROM transactions
          WHERE transaction_id = "'.$_POST['transaction_id_passed'].'"';
        $resultdelete = @mysql_query($sqldelete,$connection) or die(mysql_error());

        // Also apply the adjustment to the current member/producer balance
        $adj_amount = -( $_POST['adjustment_amount_passed'] );
        updateBalance($del_adj_type, $adj_amount, $_POST["receiver_id"]);

        $message = ": <font color=\"#FFFFFF\">Adjustment Removed</font>";
      }
  }

// DO A NEW ADJUSTMENT
elseif ( $adjustment_submitted == "yep" && $adjt_id && $adj_amount &&
        (($_GET['customer_id'] && $adj_type=="customer") || ($_GET['producer_id'] && $adj_type=="producer")) )
  {
    $sql_select = "SELECT ttype_creditdebit, ttype_name, ttype_taxed FROM transactions_types WHERE ttype_id = '$adjt_id' limit 1";
    $result_select = @mysql_query($sql_select, $connection) or die("".mysql_error()."");
    $row = mysql_fetch_array($result_select);
    if ( ($row['ttype_creditdebit'] == 'credit' && $adj_type == 'customer') || ($row['ttype_creditdebit'] == 'debit' && $adj_type== 'producer') )
      {
        $adj_amount = preg_replace("/[^0-9\.\-]/","",$adj_amount);
        $adj_amount = $adj_amount * (-1);
      }
    else
      {
        $adj_amount = preg_replace("/[^0-9\.\-]/","",$adj_amount);
      }

    $member_id = -1;
    if ( $adj_type == 'customer' )
      {
        $member_id = $_GET['customer_id'];

        // Apply the adjustment to the current customer balance
        updateBalance($adj_type, $adj_amount, $_GET['customer_id']);
      }
    // Get the member ID for any producer
    else if ( $adj_type=="producer" )
      {
        $sql2 = mysql_query('
          SELECT
            member_id
          FROM
            producers
          WHERE producer_id = "'.$_GET['producer_id'].'"');
        $row2 = mysql_fetch_array($sql2);
        $member_id = $row2['member_id'];

        // Apply the adjustment to the current producer balance
        updateBalance($adj_type, $adj_amount, $_GET['producer_id']);
      }

    $sql_insert = '
      INSERT INTO transactions
        (
          transaction_type,
          transaction_name,
          transaction_amount,
          transaction_user,
          transaction_producer_id,
          transaction_member_id,
          transaction_taxed,
          transaction_timestamp,
          transaction_comments,
          transaction_method)
      VALUES
        (
          "'.$adjt_id.'",
          "'.$row["ttype_name"].'",
          "'.$adj_amount.'",
          "'.$_SESSION['valid_c'].'",
          "'.$_GET['producer_id'].'",
          "'.$member_id.'",
          "'.$row['ttype_taxed'].'",
          now(),
          "'.$_POST['adj_desc'].'",
          "'.$_POST['payment_method'].'"
        )';
    $result_insert = @mysql_query($sql_insert,$connection) or die(mysql_error());

    if ( $adj_type == 'customer' )
    {
      // Add the adjustment to the customer adjustments table
      $adj_transaction_id = mysql_insert_id();
      $sql_add_adj = '
        INSERT INTO '.TABLE_CUSTOMER_ADJ.'
        (
          member_id,
          ttype_id,
          transaction_id,
          comments,
          amount,
          applied)
        VALUES
        (
          "'.$member_id.'",
          "'.$adjt_id.'",
          "'.$adj_transaction_id.'",
          "'.$_POST['adj_desc'].'",
          "'.$adj_amount.'",
          "0")';
      $result_insert = @mysql_query($sql_add_adj,$connection) or die(mysql_error());
    }

    $message = ": <font color=\"#FFFFFF\">Adjustment Added</font>";
  }
elseif ( $adjustment_submitted=="yep" &&
        (!$adjt_id || (!$_GET['customer_id'] && $adj_type=="customer") || ($adj_type=="producer" && !$_GET['producer_id'])) )
  {
    $message = ": <font color=\"#FFFFFF\">Please select an invoice type, member and adjustment type.</font>";
  }

// END UPDATING QUERY

if ( $_POST['adj_type'] )
  {
    $adj_type = $_POST['adj_type'];
  }
else
  {
    $adj_type = $_GET['adj_type'];
  }
  
$customer_id = $_GET['customer_id'];
$producer_id = $_GET['producer_id'];

if ( $adj_type == 'customer' )
  {
    // Modified to get members as adjustments are now applied to member balances, not baskets
    $q2 = mysql_query('
      SELECT
        '.TABLE_MEMBER.'.member_id,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.business_name
      FROM
        '.TABLE_MEMBER.'
      WHERE
        '.TABLE_MEMBER.'.membership_discontinued = "0"
      ORDER BY
        last_name ASC');
    while ( $row = mysql_fetch_array($q2) )
      {
        $member_id = $row['member_id'];
        $show_name = stripslashes($row['last_name']).', '.stripslashes($row['first_name']);

        $display_customers .= '<option value="'.$member_id.'"'
          .($member_id == $customer_id ? " selected" : "")
          .'>'.$show_name.' #'.$member_id.'</option>';

        if ($member_id == $customer_id)
        {
          /*
           * Now we get adjustments for the member, not for the basket,
           * and limit that to current transactions (that have yet to be
           * applied, or are in the process of being applied).
           * Only adjustments that have yet to be applied can be deleted.
           * If this is a producer transaction for the member, ignore it
           * - the producer lookup will show it.
           */
          $sql_select_adj = '
            SELECT
              '.TABLE_CUSTOMER_ADJ.'.transaction_id AS adj_transaction_id,
              '.TABLE_CUSTOMER_ADJ.'.amount AS adj_amount,
              '.TABLE_CUSTOMER_ADJ.'.comments AS adj_comments,
              '.TABLE_CUSTOMER_ADJ.'.applied AS adj_applied,
              '.TABLE_TRANS_TYPES.'.ttype_name AS adj_name
            FROM
              '.TABLE_CUSTOMER_ADJ.'
            LEFT JOIN
              '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_CUSTOMER_ADJ.'.ttype_id
            WHERE
              '.TABLE_CUSTOMER_ADJ.'.member_id = "'.$member_id.'"
            ORDER BY
              '.TABLE_CUSTOMER_ADJ.'.applied ASC,
              '.TABLE_CUSTOMER_ADJ.'.adjustment_id ASC';
          $result_adj = @mysql_query($sql_select_adj, $connection) or die(mysql_error());

          // Get previous adjustments for customer
          while ( $row = mysql_fetch_array($result_adj) )
          {
            $display .= '<tr bgcolor="#CCCCCC"><td align="left">';

            $display .= $show_name.' (Mem # '.$member_id.')</a><br>
              '.$row['adj_name'].': '.CURSYM.$row['adj_amount'].'<br>
              '.stripslashes($row['adj_comments']).'</td>';

            // If the adjustment has already been applied to a basket it is read-only
            // but can be deleted if the customer has yet to see it.
            $display .= '<td>';
            $display .= ($row['adj_applied']
              ? 'If you want to remove this adjustment, please create a new adjustment that cancels it out.'
              : '<form action="'.$PHP_SELF.'?adj_type='.$adj_type.'&customer_id='.$customer_id.'" method="post">
              <input type="hidden" name="transaction_id_passed" value="'.$row['adj_transaction_id'].'"/>
              <input type="hidden" name="adjustment_amount_passed" value="'.$row['adj_amount'].'"/>
              <input type="hidden" name="delete_adjustment" value="yes"/>
              <input type="hidden" name="receiver_id" value="'.$member_id.'"/>
              <input type="submit" name="where" value="Remove Adjustment"/>
              </form>');
            $display .= '</td></tr>';
          }

          // Get current balance for customer
          $display_balance = getBalance($adj_type, $customer_id);
        }
      }
  }
elseif ( $adj_type == 'producer' )
  {
    // Modified to get producers as adjustments are now applied to producer balances, not baskets
    $q4 = mysql_query('
      SELECT
        '.TABLE_PRODUCER.'.producer_id,
        '.TABLE_PRODUCER.'.member_id,
        '.TABLE_MEMBER.'.business_name
      FROM
        '.TABLE_PRODUCER.'
      INNER JOIN
        '.TABLE_MEMBER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
      ORDER BY
        business_name ASC');
    while ( $r4 = mysql_fetch_array($q4) )
      {
        $producer_id_curr = $r4['producer_id'];
        $business_name = $r4['business_name'];
        $producer_member_id = $r4['member_id'];
        
        $display_producers .= '<option value="'.$producer_id_curr.'"'
          .($producer_id_curr == $producer_id ? " selected" : "")
          .'>'.$producer_id_curr.' : '.stripslashes($business_name).'</option>';

        // Get adjustments to producer balance from last 30 days
        if ($producer_id_curr == $producer_id)
        {
          $q2 = mysql_query('
            SELECT
              t.transaction_id,
              t.transaction_amount,
              t.transaction_comments,
              tt.ttype_name
            FROM
              '.TABLE_TRANSACTIONS.' t,
              '.TABLE_TRANS_TYPES.' tt
            WHERE
              t.transaction_timestamp >= DATE_SUB(now(), INTERVAL 30 DAY)
              AND t.transaction_producer_id = "'.$producer_id_curr.'"
              AND t.transaction_type = tt.ttype_id
              AND
                (
                  tt.ttype_parent = "20"
                  OR tt.ttype_parent = "40"
                )
            ORDER BY
              t.transaction_id ASC');

          // Get previous adjustments for producer
          while ( $row = mysql_fetch_array($q2) )
            {
              $display .= '<tr bgcolor="#CCCCCC"><td align="left">';
              
              $display .= $producer_id.' : '.stripslashes($business_name).' (Mem # '.$producer_member_id.')</a><br>
                '.$row['ttype_name'].': '.CURSYM.number_format($row['transaction_amount'],2).'<br>
                '.stripslashes($row['transaction_comments']).'</td>';
              $display .= '<td><form action="'.$PHP_SELF
                  .'?adj_type='.$adj_type
                  .'&producer_id='.$producer_id
                  .'" method="post">
                <input type="hidden" name="transaction_id_passed" value="'.$row['transaction_id'].'"/>
                <input type="hidden" name="adjustment_amount_passed" value="'.$row['transaction_amount'].'"/>
                <input type="hidden" name="delete_adjustment" value="yes"/>
                <input type="hidden" name="receiver_id" value="'.$producer_id.'"/>
                <input type="submit" name="where" value="Remove Adjustment"/>
                </form>
                </td></tr>';
            }

          // Get current balance for producer
          $display_balance = getBalance($adj_type, $producer_id);
        }
      }
  }

$sql_adjt = '
  SELECT
    *
  FROM
    '.TABLE_TRANS_TYPES.'
  WHERE
    ttype_status = "1"
    AND
      (
        ttype_parent="20"
        OR ttype_parent="40"
      )
  ORDER BY ttype_whereshow ASC,
    ttype_creditdebit ASC,
    ttype_name ASC';
$result_adjt = @mysql_query($sql_adjt, $connection) or die("".mysql_error()."");
while ( $row = mysql_fetch_array($result_adjt) )
  {
    $display_adjt .= '
      <tr bgcolor="#DDDDDD" style="font-size:9pt;font-family:Arial;">
        <td align="center">'.ucfirst($row['ttype_whereshow']).'</td>
        <td>'.stripslashes($row['ttype_name']).'</td>
        <td>'.stripslashes($row['ttype_desc']).'</td>
        <td align="center">'.$row['ttype_creditdebit'].'</td>
      </tr>';
  }
$sql_adjt = '
  SELECT
    *
  FROM
    '.TABLE_TRANS_TYPES.'
  WHERE
    ttype_status = "1"
    AND
      (
        ttype_parent="20"
        OR ttype_parent="40"
      )
    AND ttype_whereshow = "'.$adj_type.'"
  ORDER BY
    ttype_name ASC';
$result_adjt = @mysql_query($sql_adjt, $connection) or die("".mysql_error()."");
while ( $row = mysql_fetch_array($result_adjt) )
  {
    $display_adjt_dropdownbox .= '
      <option value="'.$row['ttype_id'].'">'.stripslashes($row['ttype_name']).'</option>';
  }

include("template_hdr.php");?>

<script language="javascript"  type="text/javascript">
<!--
function Load_id()
  {
    var adj_type = document.invoice_types.adj_type.options[document.invoice_types.adj_type.selectedIndex].value
    var adj_txt = "?adj_type="
    location = adj_txt + adj_type
  }

function Load_customer(cust_id)
  {
    var adj_type = document.invoice_types.adj_type.options[document.invoice_types.adj_type.selectedIndex].value
    var adj_txt = "?adj_type="
    var cust_txt = "&customer_id="
    location = adj_txt + adj_type + cust_txt + cust_id
  }

function Load_producer(prod_id)
  {
    var adj_type = document.invoice_types.adj_type.options[document.invoice_types.adj_type.selectedIndex].value
    var adj_txt = "?adj_type="
    var prod_txt = "&producer_id="
    location = adj_txt + adj_type + prod_txt + prod_id
  }
-->
</script>

<!-- CONTENT BEGINS HERE -->

<div align="center">
<h1>Invoice Adjustments</h1>

<table cellpadding="7" cellspacing="2" border="0">
  <tr>
    <td colspan="2" bgcolor="#AE58DA" align="left"><b>Add an Adjustment</b> <?php echo $message;?></td>
  </tr>
  <tr>
    <td colspan="2" align="left" bgcolor="#CCCCCC">      
      <table cellpadding="1" cellspacing="1" border="0">
        <tr>
          <td>Type of invoice to apply it to:</td>
          <td>
            <form action="<?php echo $PHP_SELF;?>" method="post" name="invoice_types">
              <select name="adj_type" onChange="Load_id()">
                <option value='0'>Please select a type</option>
<?php
function listEnum($fieldname, $table_name)
  {
    $mysql_datatype_field = 1;
    if (!$result = mysql_query ("SHOW COLUMNS FROM $table_name LIKE '".$fieldname."'") )
      {
        $output=0;
        echo mysql_error();
      }
    else
      {
        $mysql_column_data = mysql_fetch_row( $result );
        if ( !$enum_data= $mysql_column_data[$mysql_datatype_field] )
          {
            $output=0;
          }
        elseif ( !$buffer_array=explode("'", $enum_data) )
          {
            $output = 0;
          }
        else
          {
            $i = 0;
            reset ($buffer_array);
            while (list(, $value) = each ($buffer_array))
              {
                if ( $i % 2 ) $output[stripslashes($value)] = stripslashes($value);
                ++$i;
              }
          }
      }
    return $output;
  }
$types = listEnum('ttype_whereshow','transactions_types');
foreach ( $types as $key => $type )
  {
    if ( $type!='')
      {
        $selected_type = ($type == $adj_type)? "SELECTED":"";
        echo '<option value="'.$key.'" '.$selected_type.'>'.ucfirst($type).'</option>';
      }
  }

?>
              </select>
            </form>
          </td>
          <td rowspan="5">
            <table cellspacing="1" cellpadding="2" border="0">
              <tr bgcolor='#DDDDDD'>
                <td align=center><b>Invoice type</b></td>
                <td align=center><b>Adjustment type</b></td>
                <td align=center><b>Example</b></td>
                <td align=center><b>Credit or Debit</b></td></tr>
                <?php echo $display_adjt;?>
            </table>
          </td>
        </tr>
        <tr>
          <td>Select member: </td>
          <td>
<?php
if ( $adj_type == 'customer' )
  {
?>
            <form action="<?php echo $PHP_SELF;?>" method="post" name="customers">
              <input type="hidden" name="adj_type" value="customer"/>
              <select name="customer_id" onChange="Load_customer(this.options[selectedIndex].value)">
                <option value='0'>Please select a customer</option>
                <?php echo $display_customers; ?>
              </select>
            </form>
<?php
  }
elseif ( $adj_type=='producer' )
  {
?>
            <form action="<?php echo $PHP_SELF;?>" method="post" name="producers">
              <input type="hidden" name="adj_type" value="producer"/>
              <select name="producer_id"  onChange="Load_producer(this.options[selectedIndex].value)">
                <option value='0'>Please select a producer</option>
                <?php echo $display_producers; ?>
              </select>
            </form>
<?php
  }
?>
          </td>
        </tr>
        <tr>
          <td>Member balance: </td>
          <td><?php echo CURSYM." ".$display_balance; ?></td>
        </tr>
        <form action="<?php
          echo $PHP_SELF
            .($_GET['adj_type'] ? '?adj_type='.$_GET['adj_type']
              .($_GET['customer_id'] ? '&customer_id='.$_GET['customer_id'] :
                ($_GET['producer_id'] ? '&producer_id='.$_GET['producer_id'] : ""))
              : "");
          ?>" method="post" name="adjustments">
          <tr>
            <td>Type of Adjustment: </td>
            <td>
                <select name="adjt_id">
                  <option value="">Select Type of Adjustment</option>
                  <?php echo $display_adjt_dropdownbox;?>
                </select>
            </td>
          </tr>
          <tr>
            <td valign=top>Amount: </td>
            <td valign=top><?php echo CURSYM; ?>
              <input type="text" name="adj_amount" size="5" maxlength="6"/><br>
              Do not add a plus or minus, it will be added for you.
            </td>
          </tr>
          <tr>
            <td valign=top>Description:</td>
            <td valign=top>
              <textarea name="adj_desc" rows="2" cols="30"></textarea>
              <input type="hidden" name="adjustment_submitted" value="yep"/>
              <input type="submit" name="where" value="Apply Adjustment"/>
            </td>
          </tr>
        </form>
      </table>
    </td>
  </tr>
<?php echo $display;?>
</table>
</div>
  <!-- CONTENT ENDS HERE -->
<?php include("template_footer.php");?>
