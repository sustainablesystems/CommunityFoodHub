<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
validate_user();
$authorization = get_auth_types($_SESSION['auth_type']);

$time_now = time ();

if ( $action == "open" )
  {
    $sqlop = '
      UPDATE
        '.TABLE_CURDEL.'
      SET
        open = "1"';
    $resultop = @mysql_query($sqlop,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
 }
elseif ( $action == "close" )
  {
    $sqlop = '
      UPDATE
        '.TABLE_CURDEL.'
      SET
        open = "0"';
    $resultop = @mysql_query($sqlop,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
  }
$date_today = date("F j, Y");

$sqlp = '
  SELECT
    member_id,
    producer_id,
    pending as pending_producer
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    member_id = "'.$member_id.'"
  AND
    donotlist_producer = 0';
$result = @mysql_query($sqlp, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($result) )
  {
    $pending_producer = $row['pending_producer'];
    if ( $result )
      {
        $is_producer = true;
      }
  }

$sql4 = '
  SELECT
    member_id,
    delivery_id,
    basket_id,
    finalized,
    submitted
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    delivery_id = "'.$current_delivery_id.'"
    AND member_id = "'.$member_id.'"';
$result4 = @mysql_query($sql4,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$num4 = mysql_numrows($result4);
while ( $row = mysql_fetch_array($result4) )
  {
    $basket_id = $row['basket_id'];
    $finalized = $row['finalized'];
    $submitted = $row['submitted'];
  }
if ( $num4 == "1" )
  {
    $order_started = "yes";
    session_register("basket_id");
  }
else
  {
   $order_started = "";
   // Remove any existing basket_id (for instance the user may have just cancelled an order)
   session_unregister("basket_id");
  }


$sql = '
  SELECT
    '.TABLE_PRODUCT.'.product_id,
    '.TABLE_PRODUCT.'.donotlist,
    '.TABLE_PRODUCT.'.producer_id,
   '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.donotlist_producer
  FROM
    '.TABLE_PRODUCT.',
    '.TABLE_PRODUCER.'
  WHERE
    '.TABLE_PRODUCT.'.donotlist = 0
    AND '.TABLE_PRODUCT.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_PRODUCER.'.donotlist_producer != 1
  GROUP BY
    product_id';
$result = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$prod_count = mysql_numrows($result);

  // Set the delivery date according to the deivery location
  // for the current order (if the member has selected one)
  include_once('../func/delivery_funcs.php');
  $deldate = get_delivery_date_for_member(
          $_SESSION['member_id'],
          $_SESSION['current_delivery_id'],
          &$delcode, &$deldesc, &$pickup_time);
?>

<?php include("template_hdr_orders.php");?>
<h1>Welcome <?php echo $show_name;?>!</h1>

<!-- CONTENT BEGINS HERE -->
<div align="center"><?php echo $font;?>

<b>Ordering Closes: <font color="#770000" size="+1"><?php echo $order_cycle_closed;?></font></b><br>
<?php
  if ($deldate != null)
  {
    echo '<br><b>Your Collection is <font color="#770000">'.$deldate
      .', '.$pickup_time.'</font> from the <font color="#770000">'.$delcode
      .'</font></b><br>'.$deldesc;
  }
?>

<?php
if (!$order_started
    && $authorization['member'] == true
    && (( $time_now > $date_open && $time_now < $date_closed ) == true)
    && $pending != 1)
  {
    include("../func/mem_select_delivery.php");
    if ( $show_page == "no" )
      {
      }
    else
      {
        echo $display;
      }
  }
elseif ( $pending == 1 )
  {
    echo '<br/><font color="#770000"><b>Your account set up is in progress, please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> with any questions.</b></font><br/>';
  }
if ( $saved == "yes" )
  {
    $message3 = '<font color="#770000"><b>Thank you - Your order has been saved.<br>
      You can come back and edit it at any time until '.$order_cycle_closed.'.</b></font><br><br>';
  }

?>

<br><?php echo $message3;?>
<table width="800" cellpadding="7" cellspacing="2" border="0">

<?php

if ( $time_now > $date_open && $time_now < $date_closed )
  {
    echo '
      <tr>
        <td colspan="3" valign="middle" align="center">
          '.$notification_message.'
        </td>
      </tr>
      <tr>
      <tr>
        <td colspan="3" bgcolor="#AEDE86" valign="bottom" align="left">'
          .$font.'<b>Available Products</b>
        </td>
      </tr>      
      <tr>
        <td bgcolor="#DDDDDD" valign="middle">'
          .$font.'<b><a href="category_list_full.php">Products by Category</a></b></font>
        </td>
        <td bgcolor="#DDDDDD" valign="middle">'
          .$font.'<a href="category_list_full.php#Organic%20Boxes">Veg and Fruit Boxes</a></font>
        </td>
        <td bgcolor="#DDDDDD" valign="middle">&nbsp;</td>
      </tr>
      <tr>
        <td colspan="3"><br></td></tr>';
  }

if ( $authorization['member'] === true)
  {
    echo '
      <tr>
        <td colspan="3" bgcolor="#AEDE86" valign="bottom" align="left">'.$font.'<b>My Orders</b></td>
      </tr>
      <tr>
        <td bgcolor="#DDDDDD" valign="top" align="left">'.$font;
    if ( ( $order_started ) && ( $time_now < $date_closed ) )
      {
        echo '<b><a href="orders_current.php">View Basket</a></b><br>
          <a href="orders_current.php#checkout">Checkout</a>';
      }
    // elseif ( ($order_started ) && ( $time_now > $date_closed ) )
    //   {
    //     echo 'Order now closed';
    //   }
    elseif ( $time_now > $date_closed )
      {
        echo '<b>Order now closed</b>';
      }
    elseif ( (! $order_started ) && $time_now < $date_closed )
      {
        echo '<b>No basket is open</b>';
      }
    else
      {
        echo '';
      }
    echo '</font></td>
      <td bgcolor="#DDDDDD" valign="middle" align="left">
        '.$font.'<a href="orders_current.php?open#prior">Previously Ordered Items</a></font>
      </td>
      <td bgcolor="#DDDDDD" valign="middle" align="left">'.$font;
    // Only give a link to the invoice here once
    // the order has also been submitted
    if ( $order_started && !$finalized && $submitted )
      {
        echo '<b><a href="orders_invoice.php?final=false" target="_new">Current Invoice</a></b><br>';
      }
    elseif ( ( $order_started ) && ( $finalized ) )
      {
        echo '<b><a href="orders_invoice.php?final=true" target="_new">View Final Invoice</a></b><br>';
      }
    echo '<a href="orders_past.php">Past Invoices</a></font></td>
      </tr>';

    echo '
      <tr>
        <td colspan="3"><br></td>
      </tr>
      <tr>
        <td colspan="3" bgcolor="#AEDE86" valign="bottom" align="left">'.$font.'<b>My Account</b></td>
      </tr>
      <tr>
        <td bgcolor="#DDDDDD" align="left">
          '.$font
          .'<a href="member_form.php">Update Contact Details</a><br>
            <a href="reset_password.php">Change Password</a>'
          .(PRDCR_FORM_AVAIL ? '<br><br><a href="producer_form.php">Register as Producer</a>' : '').'
          </font>
        </td>
        <td bgcolor="#DDDDDD" colspan="2" align="left" width="67%">'.$font;
    if (VOLUNTEER_ROTA_ENABLED)
    {
      echo '<font color="#770000"><b>Volunteers please read:</b> the volunteer rota uses a Google calendar.
        If you have a Google email account, please
        <a href="https://accounts.google.com/Logout" target="_new">log out</a> before continuing.</font><br>
        <a href="volunteer_rota.php">
          <img src="../grfx/volunteer.gif" alt="Volunteer" style="vertical-align:middle;border:10px solid transparent"/>
        </a> - <b>username</b>: "fguangels@gmail.com", <b>password</b>: "password"<br>
        <font color="#770000">A printable "How To" guide is also available:</font>
        <a href="'.PATH.'pdf/How to volunteer using the Local Food Coop website.pdf">
        <img src="'.DIR_GRAPHICS.'pdficon_small.gif" border="0"/>
        How to volunteer using the LFC website</a>';
    }
      echo '
        </td>
      </tr>';
  }

if ( $producer_id_you && $is_producer == true && $pending_producer == 0 && $authorization['producer'] === true )
  {
    echo '
      <tr>
        <td colspan="3"><br></td>
      </tr>
      <tr>
        <td colspan="3" bgcolor="#ADB6C6" valign="bottom" align="left">'.$font.'<b>My Products and Customers</b></td>
      </tr>
      <tr>
        <td bgcolor="#DDDDDD" valign="top" align="left">'.$font.'<b>Delivery Day Labels:</b><br>
          <a href="../func/producer_labelsc.php?delivery_id='.$_SESSION['current_delivery_id'].'&producer_id='.$_SESSION['producer_id_you'].'">One Label per Customer</a><br>
          <a href="../func/producer_labels.php?delivery_id='.$_SESSION['current_delivery_id'].'&producer_id='.$_SESSION['producer_id_you'].'">One Label per Product</a></td>
        <td bgcolor="#DDDDDD" valign="top" align="left">'.$font.'<b>Producer Invoices:</b><br>
          <a href="orders_prdcr_cust.php">by Customer</a><br>
          <a href="orders_prdcr_cust_storage.php">by Storage/Customer</a><br>
          <a href="orders_prdcr.php">by Product</a></b><br>
          <a href="orders_prdcr_multi.php">Multi-sort / Mass-update</a><br><br>
          <b><a href="order_summary.php">Wholesale Report</a></b><br><br>
          <a href="orders_saved2.php">Past Producer Invoices</a></td>
        <td bgcolor="#DDDDDD" valign="top" align="left">'.$font.'<b>Edit My Products:</b><br>
          [<b><a href="edit_product_list.php?producer_id='.$producer_id_you.'&a=retail">Listed&nbsp;Retail</a></b>]
          [<a href="edit_product_list.php?producer_id='.$producer_id_you.'&a=wholesale">Listed&nbsp;Wholesale</a>]
          [<a href="edit_product_list.php?producer_id='.$producer_id_you.'&a=unlisted">Unlisted</a>]
          [<a href="edit_product_list.php?producer_id='.$producer_id_you.'&a=archived">Archived</a>]<br>
          [<a href="edit_product_list.php?producer_id='.$producer_id_you.'">Full Product List</a>]<br><br>
          [<b><a href="add_products.php?producer_id='.$producer_id_you.'">Add New Product</a></b>]<br><br>
          <a href="edit_producer_info.php">Edit My Public Info</a></td>
      </tr>';
  }
// Check if auth_type = administrator
if ( $authorization['administrator'] === true )
  {
    echo '
      <tr>
        <td colspan=\"3\"><br></td>
      </tr>
      <tr bgcolor="#ADB6C6">
        <td colspan="3" valign="bottom" align="left">'.$font.'<b>Admin Producers and Products</b></td>';


// if($open==1)
//   {
//     echo "<b>Currently Open</b> &nbsp;&nbsp; <b><a href='$PHP_SELF?action=close'>Close Order</a></b>";
//   }
// else
//   {
//     echo "<b><a href='$PHP_SELF?action=open'>Open Order</a></b> &nbsp;&nbsp; <b>Currently Closed</b>";
//   }

    echo '
      </tr>
      <tr>
        <td bgcolor="#DDDDDD" valign="top" align="left">'.$font.'
          <a href="generate_invoices.php">Customer and Producer Invoices</a><br>
          <a href="list_prodnew.php">New Products this Cycle</a><br>
          <a href="list_prodchanged.php">Changed Products this Cycle</a><br>
          <b><a href="../admin/price_list_full.php?cycle=curr&pricing=full&user=producer">Full '
            .SITE_NAME_SHORT.' Price List (showing margins)</a></b>
        </font></td>
        <td bgcolor="#DDDDDD" valign="top" align="left">'.$font.'
          <a href="edit_prdcr_list.php">Add/Edit Products by Producer</a><br>
          <a href="edit_info_list.php">Edit Producers</a>
        </font></td>
        <td bgcolor="#DDDDDD" valign="center" align="center"><font size="+1">
          <b><a href="edit_prdcr_list.php?prep=live">Make Product Changes LIVE!</a></b>
        </font></td>
      </tr>';
  }
?>
</table>

</div>

<!-- CONTENT ENDS HERE -->

<?php include("template_footer_orders.php");?>
