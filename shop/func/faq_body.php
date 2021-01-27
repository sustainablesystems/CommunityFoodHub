<?php

$sql = '
  SELECT
    order_cycle_closed
  FROM
    '.TABLE_CURDEL;
$result = @mysql_query($sql);
$dates = mysql_fetch_assoc($result);
?>

<h1>Frequently Asked Questions</h1>

<div align="center">
  <table width="800">
    <tr>
      <td align="left" valign="top"><?php echo $font;?>

        <b>Click on the question to see an answer:</b>
        <ul>
          <li> <a href="#order1">How do I order using this website?</a>
          <li> <a href="#order2">How do I order NOT using this website?</a>
          <li> <a href="#order3">Can I change my order?</a>
          <li> <a href="#order4">When does ordering end for this cycle?</a>
          <br><br>
          <li> <a href="#pay1">How do I pay?</a>
          <br><br>
          <li> <a href="#del1">Where and when can I collect my items?</a>
          <li> <a href="#del2">Can I change my collection location once I have chosen it?</a>
          <!-- Disable producer functionality
          <br><br>
          <li> <a href="#prdcr1">I am a producer, where do I send my product updates?</a>
          -->
          <br><br>
          <li> <a href="#web1">I am getting an error on a web page, what do I do?</a>
          <li> <a href="#web2">I have a suggestion on how to make this website easier to use.</a>
          <br><br>
          <li> <a href="#contact_details">How do I update my contact information?</a>
          <li> <a href="#password">How do I change my password?</a>
          <br><br>
          <li> <a href="#q1">What if I have questions that are not covered in this list?</a>
        </ul>
      </td>
      <td align="left" valign="top"><?php echo $font;?>
        <b>Printable "How To" guides:</b>
        <ul>
          <li><a href="<?php echo PATH; ?>pdf/How to register on the Local Food Coop website.pdf">
              <img src="<?php echo DIR_GRAPHICS; ?>pdficon_small.gif" border="0"/>
              How to register on the LFC website</a>
          <li><a href="<?php echo PATH; ?>pdf/How to order from the Local Food Coop website.pdf">
              <img src="<?php echo DIR_GRAPHICS; ?>pdficon_small.gif" border="0"/>
              How to order from the LFC website</a>
        <?php if (VOLUNTEER_ROTA_ENABLED) { ?>
          <li><a href="<?php echo PATH; ?>pdf/How to volunteer using the Local Food Coop website.pdf">
              <img src="<?php echo DIR_GRAPHICS; ?>pdficon_small.gif" border="0"/>
              How to volunteer using the LFC website</a>
        <?php } ?>
        </ul>
      </td>
    </tr>

    <tr>
      <td align="left" valign="top" colspan="2"><?php echo $font;?>
        <div id="order1"></div>
        <b>Q: How do I order using this website?</b>
        <br>
        <b>A:</b> The shop login page is <?php echo '<a href="'.BASE_URL.PATH.'members/">'.BASE_URL.PATH.'members/</a>' ?>.
        When you log in, if you have not started an order, it will ask you to choose a collection location and a payment method.
        Then click the button to start your order. You can then select products you want to buy:
        <ol>
        <LI>You can browse through the product lists, find a product to order, change the quantity (default is 1), and click
        the "Add to Cart" button. When you do this, the system adds the quantity of items you selected to your shopping basket.
        When you are done, you can visit the checkout and submit your order - remember to do this before order closing time.<br>
        <br>

        <li>To change the quantity of a product after adding it to your basket, click on "View Basket".
        Place the cursor in the quantity box for the item you wish to order, and change the number to however many you plan to buy.
        Then press the "Update" button.<br>
        <br>

        <LI>To remove a product from your basket, click on "View Basket".
        Press the "Remove" button for the product you wish to remove.<br>
        <br>

        <LI>You can change and resubmit your order up until the order closing time.
        The time of closing is announced by email at the beginning of the order period,
        and you will receive a reminder as the deadline approaches.
        To edit your order (add or remove items, change quantities),
        log in at <A href="<?php echo BASE_URL.PATH;?>members/"><?php echo BASE_URL.PATH;?>members/</A>.
        You can use the methods above to add items, change quantities, or remove items -
        click on "View Basket" and make your changes. Then resubmit your order.
        </ol>

        <div id="order2"></div>
        <br>

        <b>Q: How do I order NOT using this website?</b>
        <br>
        <b>A:</b> For ordering NOT using this website,
        please email <a href="mailto:<?php echo ORDER_EMAIL; ?>"><?php echo ORDER_EMAIL; ?></a>
        to ask if there is a &quot;computer buddy&quot; who can take your order by phone.

        <div id="order3"></div>
        <br><br>

        <b>Q: Can I change my order?</b>
        <br>
        <b>A:</b> You can log in and change your order until the order closing time:
        <strong><?php echo $dates['order_cycle_closed']; ?></strong>. Between then and the collection day,
        suppliers will be putting your order together.
        You can view your provisional invoice during that time by logging in.
        Note that the final invoice you receive on collection day may differ
        if there are last minute changes in product availability.

        <div id="order4"></div>
        <br><br>

        <b>Q: When does ordering end for this cycle?</b>
        <br>
        <b>A:</b> You can log in and change your order until the order closing time: 
        <strong><?php echo $dates['order_cycle_closed']; ?></strong>.

        <div id="pay1"></div>
        <br><br>

        <b>Q: How do I pay?</b>
        <br>
        <b>A:</b> You will receive a paper copy of your invoice with your order on collection day with the final total owed.
        Then you can pay in cash or write a cheque made payable to "<?php echo SITE_NAME; ?>".
        You will also be able to view your finalised invoice by logging in to the shop after collection day.

        <div id="del1"></div>
        <br><br>

        <b>Q: Where and when can I collect my items?</b>
        <br>
        <b>A:</b> When you make an order, you will be able to select a collection
        location from the <a href="<?php echo PATH; ?>locations.php">list of collection locations</a>.
        Your provisional invoice will then show your individual collection location and time.
        This invoice is emailed to you after you submit an order, and can also be
        viewed in the <a href="<?php echo PATH; ?>members/index.php">My Account</a> area of the website.

        <div id="del2"></div>
        <br><br>

        <b>Q: Can I change my collection location once I have chosen it?</b>
        <br>
        <b>A:</b> Contact us at <a href="mailto:<?php echo ORDER_EMAIL;?>"><?php echo ORDER_EMAIL;?></a>
        to change your collection location.

        <div id="web1"></div>
        <br><br>

        <b>Q: I am getting an error on a web page, what do I do?</b>
        <br>
        <b>A:</b> Please copy and paste the text of the error into an email 
        along with what page it is and send it to
        <a href="mailto:<?php echo WEBMASTER_EMAIL;?>"><?php echo WEBMASTER_EMAIL;?></a>.
        Please also explain what happened before that error occurred.
        Thank you for your help in keeping this website working smoothly.

        <div id="web2"></div>
        <br><br>

        <b>Q: I have a suggestion on how to make this website easier to use.</b>
        <br>
        <b>A:</b> Please send your suggestions to 
        <a href="mailto:<?php echo WEBMASTER_EMAIL;?>"><?php echo WEBMASTER_EMAIL;?></a>.
        Thank you for your help in keeping this website working smoothly.

        <div id="contact_details"></div>
        <br><br>

        <b>Q: How do I update my contact information?</b>
        <br>
        <b>A:</b> Log in, then go to "Update Contact Details" near the bottom of the page.

        <div id="password"></div>
        <br><br>

        <b>Q: How do I change my password?</b>
        <br>
        <b>A:</b> Log in, then go to "Change Password" near the bottom of the page.

        <div id="q1"></div>
        <br><br>

        <b>Q: What if I have questions that are not covered in this list?</b>
        <br>
        <b>A:</b> You can contact the appropriate person by looking on the <a href="contact.php">Contact Us</a> page.
        <br><br>

      </td>
    </tr>
  </table>
</div>