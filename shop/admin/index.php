<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
include_once ('general_functions.php');
session_start();
validate_user();

// Display admin workflows
$display_workflow = '
  <span class="large">'.SITE_NAME_SHORT.' Workflows</span>
  <div class="menuBox2">
    <table width="100%" class="compact">
      <tr valign="top">
        <td align="left" width="50%">
          <img src="grfx/kcron.png" width="32" height="32" align="left" hspace="2"><br>
          <b>After ordering closes</b>
          <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
            <li><a href="orders_list.php?delivery_id='.$current_delivery_id.'">Finalise/Edit Customer Orders</a>
            <li><a href="cashier_report.php?delivery_id='.$current_delivery_id.'">Cashier Report</a>
            <li><a href="price_list_full.php?cycle=curr">'.SITE_NAME_SHORT.' Price List</a>
              | <a href="price_list_full.php?cycle=curr&pricing=full">'.SITE_NAME_SHORT.' Price List (showing margins)</a>
            <li><a href="who_ordered_what.php?delivery_id='.$current_delivery_id.'">Who Ordered What</a>
            <li><a href="orders_prdcr_list.php?delivery_id='.$current_delivery_id.'">Wholesale Report/Producer Invoice</a>
          </ul>
        </td>
        <td align="left" width="50%">
          <img src="grfx/kcron.png" width="32" height="32" align="left" hspace="2"><br>
          <b>Before next cycle opens</b>
          <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
            <li><a href="orders_prdcr_list.php?delivery_id='.$current_delivery_id.'">Finalise Producer Invoice</a>
            <li><a href="ctotals_onebutton.php?delivery_id='.$current_delivery_id.'">Record Payments</a>
            <li><a href="adjustments.php">Apply Adjustments</a>
            <li><a href="delcode_activation.php">Activate/Add/Edit Collection Locations</a>
            <li><a href="prep_cycle.php">Start/Update Cycle</a> (be very careful with this function!)
          </ul>
        </td>
      </tr>
    </table>
  </div>';


$display_admin .= '
  <span class="large">Administrators</span>
  <div class="menuBox2">
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">
        <img src="grfx/bottom.png" width="32" height="32" align="left" hspace="2" alt="Membership Information"><br />
        <b>Membership Information</b>
        <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
          <li><a href="report_members.php?p=0">View Members/Producers</a></li>
          <li><a href="member_interface.php">Add/Edit Members/Producers</a></li>
          <li><a href="members_list_email.php">Email Mailing Lists</a></li>
          <li><a href="member_balances_outstanding.php">Member Balances Outstanding</a></li>
          <br />
          <li><a href="pending_members_list.php">Pending and Unpaid Members</a></li>
          <li><a href="producers_pending.php">Pending Producers</a></li>
          <li><a href="producer_applications.php">Producer Applications</a></li>
        </ul>
        <img src="grfx/gnome2.png" width="32" height="32" align="left" hspace="2" alt="Areas"><br />
        <b>Collection Locations</b>
        <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
          <li><a href="delivery.php">Orders by Collection Location</a></li>
          <li><a href="delcode_activation.php">Activate/Add/Edit Collection Locations</a></li>
        </ul>
          <img src="grfx/kchart.png" width="32" height="32" align="left" hspace="2" alt="Reports"><br />
          <b>Reports</b>
          <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
            <li><a href="report_financial.php">Financial Report</a></li>
            <li><a href="transaction_report.php">Transaction Report</a></li>
            <br />
            <li><a href="orders_perhub.php">Sales by Collection Location</a></li>
            <li><a href="report_per_subcat.php">Sales by Subcategory</a></li>
            <li><a href="report.php">Sales by Order Cycle</a></li>
          </ul>
      </td>
      <td align="left" width="50%">
        <img src="grfx/launch.png" width="32" height="32" align="left" hspace="2" alt="Current Order Cycle Functions"><br />
        <b>Current Order Cycle Functions</b>
        <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
          <li><a href="orders_selectmember.php">Make an Order for a Customer</a></li>
          <li><a href="delivery.php">Orders by Collection Location (change order location)</a></li>
          <li><a href="orders_list.php?delivery_id='.$current_delivery_id.'">Customers with Orders this Cycle</a></li>
          <li><a href="members_list_emailorders.php?delivery_id='.$current_delivery_id.'">Customer Email Addresses this cycle</a></li>
          <li><a href="orders_prdcr_list.php?delivery_id='.$current_delivery_id.'">Producers with Customers this Cycle</li>
          <br />
          <li><a href="printprod_new.php">New Products</a></li>
          <li><a href="printprod_changed.php">Changed Products</a></li>
          <li><a href="printprod_deleted.php">Unlisted Products</a></li>
          <li><a href="printprod_list_all.php">Full Product List</a></li>
          <li><a href="price_list_full.php?cycle=curr">'.SITE_NAME_SHORT.' Price List</a></li>
          <li><a href="who_ordered_what.php?delivery_id='.$current_delivery_id.'">Who Ordered What</a></li>';
$display_admin .= '
          </ul>
          <img src="grfx/kcron.png" width="32" height="32" align="left" hspace="2" alt="Previous Order Cycle Functions"><br />
          <b>Previous Order Cycle Functions</b>
          <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
            <li><a href="history_saved.php">View/Edit Past Order Cycles</a></li>
            <li><a href="unfinalized.php">All Previous Unfinalised Invoices</a></li>
          </ul>
          <img src="grfx/admin.png" width="32" height="32" align="left" hspace="2" alt="Admin Maintenance"><br />
          <b>Admin Maintenance</b>
          <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
            <li><a href="category_list_edit.php">Edit Categories and Subcategories</a></li>
            <li><a href="invoice_edittext.php">Edit Invoice Messages</a></li>
          </ul>
        </td>
      </tr>
    </table>
    </div>';

$display_admin2 = '
  <span class="large">Cashiers</span>
  <div class="menuBox2 compact" align="left">
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">
        <img src="grfx/ksirc.png" width="32" height="32" align="left" hspace="2" alt="Helpful PDF Forms for Download"><br />
        <b>Helpful PDF Forms for Download</b>
        <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
          <li><a href="pdf/payments_received.pdf" target="_blank">Payments Received Form</a></li>
          <li><a href="pdf/invoice_adjustments.pdf" target="_blank">Invoice Adjustments Chart</a></li>
        </ul>
      </td>
      <td align="left" width="50%">
        <img src="grfx/kspread.png" width="32" height="32" align="left" hspace="2" alt="Cashier and Adjustment Information"><br />
        <b>Cashier and Adjustment Information</b>
        <ul style="list-style-image: url(./grfx/nav_spacer_4dots.gif);">
          <li><a href="adjustments.php">Invoice Adjustments</a></li>
          <li><a href="ctotals_onebutton.php?delivery_id='.$current_delivery_id.'">Receive Payments</a></li>
          <li><a href="cashier_report.php?delivery_id='.$current_delivery_id.'">Cashier Report</a></li>
        </ul>
      </td>
    </tr>
  </table>
  </div>';

if ($authorization['administrator'] === false )
  {
    $display = "<p><b>You do not have administrator authentication</b></p>";
  }
// Otherwise display all info.
else
  {
    $display = $display_workflow;
    $display .= $display_admin;
    $display .= $display_admin2;
  }
$date_today = date("F j, Y");

// Show number of open baskets
$sql = '
  SELECT COUNT(basket_id)
  FROM '.TABLE_BASKET_ALL.'
  WHERE delivery_id="'.$current_delivery_id.'"';
$result = @mysql_query($sql,$connection) or die("Couldn't execute count query.");
$open_basket_count = mysql_result($result, 0);

?>
<?php include("template_hdr.php");?>
<div align="center">
  <h1>Welcome to the Admin Area!</h1>
  <h3>As of <?php echo $date_today;?>, there are <font color="#770000"><?php echo $open_basket_count;?></font> basket(s) open</h3>
  <b>Ordering Closes: <font color="#770000"><?php echo $order_cycle_closed;?></font></b>
  <br><br>
  <div id="yellowWrapper" align="center">
    <div style="width: 98%;">
      <?php echo $display;?>
    </div>
  </div>
</div>
  <!-- CONTENT ENDS HERE -->
<?php include("template_footer.php"); ?>
