<?php

/*******************************************************************************

NOTES ON USING THIS TEMPLATE FILE...

This file produces a plain text version of the HTML invoice

The heredoc convention is used to simplify quoting.
The noteworthy point to remember is to escape the '$' in
variable names.  But functions pass through as expected.

The short php if-else format is also useful in this context
for inline display (or not) of content elements:
([condition] ? [true] : [false])

All variables in this file are loaded at include-time and interpreted later
so there is no required ordering of the assignments.

All system constants from the configuration file are available to this template




********************************************************************************
Model for the overall invoice display page might look something like this:

 -- OVERALL INVOICE DISPLAY -------------
|                                        |
|     -- ROUTING DISPLAY SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     -- PRODUCT DISPLAY SECTION ---     |
|    |                              |    |
|    |                              |    |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     -- ADJUSTMENT DISPLAY SECT ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     -- MEMBERSHIP DISPLAY SECT ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     -- TOTALS DISPLAY SECT -------     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|                                        |
 ----------------------------------------

|                                        |
|         ADMIN DISPLAY SECTION          |
|                                        |



The PRODUCT DISPLAY SECTION is composed of several subsections as shown here

 -- PRODUCT DISPLAY SECTION -------------
|                                        |
|       -- PRODUCER 1 ------------       |
|      |                          |      |
|      | PRODUCER DISPLAY (OPEN)  |      |
|      |                          |      |
|      |   PRODUCT DISPLAY 1      |      |
|      |   PRODUCT DISPLAY 2      |      |
|      |         ...              |      |
|      |   PRODUCT DISPLAY n      |      |
|      |                          |      |
|      | PRODUCER DISPLAY (CLOSE) |      |
|      |                          |      |
|       --------------------------       |
|                                        |
|       -- PRODUCER 2 ------------       |
|      |                          |      |
|      | PRODUCER DISPLAY (OPEN)  |      |
|      |                          |      |
|      |   PRODUCT DISPLAY n+1    |      |
|      |         ...              |      |
|      |                          |      |
|      | PRODUCER DISPLAY (CLOSE) |      |
|      |                          |      |
|       --------------------------       |
|                                        |
|                   ...                  |
|                                        |
|       -- PRODUCER M ------------       |
|      |                          |      |
|      | PRODUCER DISPLAY (OPEN)  |      |
|      |                          |      |
|      |   PRODUCT DISPLAY etc    |      |
|      |         ...              |      |
|      |                          |      |
|      | PRODUCER DISPLAY (CLOSE) |      |
|      |                          |      |
|       --------------------------       |
|                                        |
 ----------------------------------------


Finally, the PRODUCT DISPLAY SECTIONs include some markup that
is interpreted from the PRODUCT DISPLAY PRICE SECTION which can be used
to customize display of that column

In general, the OVERALL INVOICE DISPLAY ($overall_invoice_display_output) below
will contain the other sections with possible additional levels of embedding: 

$overall_invoice_display_output Markup for the entire customer invoice
$product_display_output         Markup for the entire product listing section, sans the header
$product_display_price_output   Markup for the pricing information for a particular product
$adjustment_display_output      Markup for the adjustments section
$membership_display_output      Markup for all historic membership accounting information
$totals_display_output          Markup for the totals section on the invoice page
$routing_display_output         Markup for the routing portion of the invoice (customer info and such)
$admin_display_output           Markup for the admin section (not part of the "official" invoice)


********************************************************************************

The following variables are available to the product listing section:

FROM THE DATABASE:

$a_business_name                Producer business name
$a_first_name                   Producer first name
$a_last_name                    Producer last name
$category_id                    Product category id
$taxable_cat                    0 or 1 if the category is taxable
$taxable_subcat                 0 or 1 if the subcategory is taxable
$product_id                     Numeric product id
$producer_id                    Five-char producer id
$product_name                   Short-textual product name
$item_price                     Price for each item (or for each pricing unit quantity)
$pricing_unit                   Units used for pricing of random-weight items
$quantity                       Quantity ordered
$ordering_unit                  Units used for ordering the item
$out_of_stock                   0 or 1 if this item is out of stock
$random_weight                  0 or 1 if this is a random-weight item
$min_weight                     Minimum number of pricing-units for random-weight items
$max_weight                     Maximum number of pricing-units for random-weight items
$total_weight                   Actual weight for random-weight items -- indeterminate until after ordering
$extra_charge                   Extra charges for each item ordered (no tax, no fee)
$notes                          Notes from the customer to the producer
$future_delivery_id             The delivery id in which this item is expected to be delivered (not used)
$storage_code                   Special storage coding (e.g. REF, FROZ, NON, EGGS)
$auth_type                      Invoice-owner auth_type for the owner of this invoice
$last_name                      Invoice-owner last name
$first_name                     Invoice-owner first name
$business_name                  Invoice-owner business name -- if available
$first_name_2                   Invoice-owner secondary first name
$last_name_2                    Invoice-owner secondary last name
$address_line1                  Invoice-owner home address -- line 1
$address_line2                  Invoice-owner home address -- line 2
$city                           Invoice-owner home address -- city
$county                         Invoice-owner home address -- county
$state                          Invoice-owner home address -- state
$zip                            Invoice-owner home address -- zip
$work_address_line1             Invoice-owner work address -- line 1
$work_address_line2             Invoice-owner work address -- line 2
$work_city                      Invoice-owner work address -- city
$work_state                     Invoice-owner work address -- state
$work_zip                       Invoice-owner work address -- zip
$email_address                  Invoice-owner primary email address
$email_address_2                Invoice-owner secondary email address
$home_phone                     Invoice-owner home phone number
$work_phone                     Invoice-owner work phone number
$mobile_phone                   Invoice-owner mobile/cell phone number
$fax                            Invoice-owner fax number
$mem_taxexempt                  0 or 1 if invoice owner is exempted from paying taxes
$mem_delch_discount             0 or 1 if invoice owner is exempted from paying delivery charges


CALCULATED AND OTHER VALUES:

$fontface                       Legacy value from the config file
$show_name                      Member modified name
$product_display_price_output   Markup for the pricing information for a particular product
$full_extra_charge              Total of extra charges for this item (because of multiples ordered)
$min_price                      Minimum price for random-weight item
$max_price                      Maximum price for random-weight item
$stock_image                    Url for out-of-stock image -- only defined when out_of_stock == 1
$weight_unit                    Weight units for random-weight item
$display_business_name          Producer modified business name
$display_price                  Formatted as e.g. $3.50/gallon and $4.00 / bucket *
                                (* indicates taxable item; $3.50 is regular part; 4.00 is extra-charge part)
$display_weight_actual          Either actual weight (after producer input) or show zero with $display_weight_actual_text comment preceding
$display_weight_pending         Either actual weight (after producer input) or show pending message from $display_weight_pending_text
$display_weight_both            Either actual weight (after producer input) or show min/max weights with $display_weight_both_text comment between
$display_weight_average         Either actual weight (after producer input) or display average weight with $display_weight_average_text preceding
$display_weight_minimum         Either actual weight (after producer input) or show min weight with $display_weight_minimum_text preceding
$display_weight_maximum         Either actual weight (after producer input) or show max weight with $display_weight_maximum_text preceding
$effective_price                Price that will be used for this item until actual weight is entered based upon RANDOM_CALC configuration
                                (e.g. if min price is $3.00 and max price is $5.00; if using AVG then totals will be calculated using $4.00;
                                If using MIN then totals will be calculated using $3.00; if using MAX then totals will be calculated using $5.00)
$message_incomplete             Message used if random weight is not yet entered
                                (taken from the appropriate of: $message_incomplete_zero, $message_incomplete_avg, $message_incomplete_min, $message_incomplete_max)
$extra_cost                     Running total of $full_extra_charge values (c.f.)
$exempt_product_cost            Running total of non-taxable product prices (not including extra-charges)
$number_of_products             Number of different products ordered

*******************************************************************************/

// Miscellaneous markup elements for the product-list section

// This is the code used between the price/pricing_unit AND the extra_charge/ordering_unit
$pricing_ordering_separator = '\n+ ';

// This is for display of the random weights when the weight is not yet known
$display_weight_actual_text  = 'Using ';                               // e.g. "Using 3.4 pounds"
$display_weight_pending_text = 'Pending\nweight';  // e.g. "Producer has not yet entered weight"
$display_weight_both_text    = '-';                              // e.g. "2.3-5.6 pounds"
$display_weight_average_text = 'Est. ';                              // e.g. "Approx 4.2 pounds"
$display_weight_minimum_text = 'More than ';                           // e.g. "More than 2.6 pounds"
$display_weight_maximum_text = 'Less than ';                           // e.g. "Less than 6.8 pounds"

$message_incomplete_zero = 'Totals do not include any cost for unfilled random-weight items';
$message_incomplete_avg = 'Totals are based upon the average weight for unfilled random-weight items';
$message_incomplete_min = 'Totals are based upon minimum weights for unfilled random-weight items';
$message_incomplete_max = 'Totals are based upon maximum weights for unfilled random-weight items';

$taxable_product_flag = ' * '; // Flag to be attached to taxable products
$out_of_stock_checkmark = 'out of stock';


/************************* PRODUCER DISPLAY (OPEN) ****************************/

// Don't display producer for suppliers
$producer_display_section_open = <<<EOT
'\n'.
(\$is_supplier ? '' : \$display_business_name.'\n')
EOT;

/************************* PRODUCER DISPLAY (CLOSE) ***************************/

$producer_display_section_close = <<<EOT
EOT;

/************************* PRODUCT DISPLAY SECTION ****************************/

// This is used to interpret each product line-item
$product_display_section = <<<EOT
(\$stock_image != '' ? 'Out of stock ' : '')
  .\$quantity.' x '
  .\$product_name.' at '
  .\$product_display_price_output.' - '
  .CURSYMT.' '.number_format (round (\$effective_price + \$full_extra_charge, 2), 2).'\n'
.(\$notes != '' ? \$future.' *Customer note:* '.\$notes.'\n' : '')
EOT;

/************************* SUBPRODUCT DISPLAY SECTION ****************************/

// This is used to display each subproduct of a compound product (e.g. box/hamper)
$subproduct_display_section = <<<EOT
'\t'.(\$quantity > 1 ? \$quantity." x " : "").implode("\n\t".(\$quantity > 1 ? \$quantity." x " : ""), \$subprod_names).'\n'
EOT;

/************************** PRODUCT DISPLAY PRICE SECTION *********************/

$product_display_price_section = <<<EOT
(\$item_price != 0 ? \$taxable_product.CURSYMT.number_format (round (\$item_price, 2), 2).'/'.\$ordering_unit : '').
(\$item_price != 0 && \$extra_charge != 0 ? \$pricing_ordering_separator : '').
(\$extra_charge != 0 ? CURSYMT.number_format (round (\$extra_charge, 2), 2).'/'.Inflect::singularize (\$ordering_unit) : '')
EOT;


/*******************************************************************************
The following variables are available to the ADJUSTMENT DISPLAY SECTION and MEMBERSHIP DISPLAY SECTIONs:

FROM THE DATABASE:

$transaction_id                      Unique numeric transaction ID
$transaction_type                    Numeric value of transaction type
$transaction_name                    Human-readable transaction name
$transaction_amount                  Dollar amount of transaction (may be negative)
$transaction_user                    Username of person who posted transaction
$transaction_taxed                   0 or 1 if the transaction is taxed
$transaction_timestamp               Date-time the transaction was posted
$transaction_batchno                 Integer field
$transaction_memo                    20-character field
$transaction_comments                200-character field
$transaction_method                  Single character matching the payment method


CALCULATED VALUES:

$taxable_product                     Asterisk (* ) for display if product is taxable
$adjustments_exist                   0 or 1 if there are adjustments to display
$taxed_adjustment_cost               Running total of taxable adjustments on this invoice
$exempt_adjustment_cost              Running total of non-taxable adjustments on this invoice
$membership_this_exist               0 or 1 if there is membership accounting on THIS order
$membership_cost                     Running total of membership fees/payments (excluding order_cost)

*************************** ADJUSTMENT DISPLAY SECTION ************************/

$adjustment_display_section = <<<EOT
  '\t'.\$transaction_name.'\n'.\$transaction_comments.'\t'
  .CURSYMT.\$transaction_amount.'\n'
EOT;

/************************** MEMBERSHIP DISPLAY SECTION ************************/

$membership_display_section = <<<EOT
  '\t'.\$transaction_name.'\n'.\$transaction_comments.'\t'
  .CURSYMT.number_format(\$transaction_amount, 2).'\n'
EOT;




/*******************************************************************************
The following variables are available to the TOTALS DISPLAY SECTION, ROUTING DISPLAY SECTION,
OVERALL INVOICE, and ADMIN DISPLAY SECTIONs:

FROM THE DATABASE:

$msg_all                             Message from database to display at top of invoices
$msg_bottom                          Message from database to display at bottom of invoices
$disclaimer                          Message from database to display at very bottom
$deltype                             D or P for delivery or pickup respectively
$delcode_id                          Delcode ID where this order should be routed
$delcode                             Delcode (description)
$delcharge                           Charges for delivering to this delcode from delivery_codes table
$transcharge                         Transportation charges from delivery_codes table
$delivery_date                       Delivery date for this order cycle
$payment_method                      C or P for check or paypal respectively from payment_method table
$payment_desc                        Textual description of payment method
$msg_unique                          Message to specific customer for this invoice
$route_name                          Route name from the routes table corresponding to route_id in the delivery_codes table
$route_desc                          Route description from the routes table corresponding to route_id in the delivery_codes table
$deldesc                             Verbose description of delivery code -- often with address and contact inforamtion -- from delivery_codes table
$hub                                 Hub where this order should be sorted
$truck_code                          Truck code that will transport this order
$special_order                       0 or 1 for special orders (not currently implemented)
$current_delivery_id                 Delivery ID for this order
$auth_type                           Authorization type for the owner of this invoice
$membership_type_id                  Membership type ID from the membership_types table
$order_cost                          Per-order cost -- from membership_types table (output as "Admin & Packing")


CALCULATED VALUES:

$previous_balance                    Unpaid balance due prior to this order
$city_name                           Name of city for tax purposes
$copo_city                           Tax code for city
$city_tax_rate                       Tax rate in this city
$county_name                         Name of county for tax purposes
$copo_county                         Tax code for county
$county_tax_rate                     Tax rate in this county
$state_id                            Tax ID for this state
$state_tax_rate                      Tax rate for this state
$coop_fee_basis                      Amount upon which co-op fee will be assessed
                                     (taxed products + taxed adjustments + exempt products)
$coop_fee                            Amount of the co-op fee that is assessed
$total_taxable_cost                  Total taxable costs (products + adjustments + membership?)
$total_exempt_cost                   Total non-taxable costs (exempt products + extra-charges + membership?)
$total_basket_cost                   Basket total (taxed_product_cost + exempt_product_cost + extra-charges)
$total_current_cost                  (taxable costs + exempt costs)
$total_tax_rate                      Overall tax rate (city, county, state)
$city_sales_tax                      Amount of tax needed by city
$county_sales_tax                    Amount of tax needed by county
$state_sales_tax                     Amount of tax needed by state
$total_tax                           Total of taxes needed by city, county, state
$subtotal_1                          $total_current_cost + $total_tax + $delcharge + $coop_fee
$potential_paypal_fee                The paypal fee that WOULD BE CHARGED if the person had chosen to pay by paypal
$paypal_fee                          The actual paypal fee -- based upon how the member chose to pay
$subtotal_2                          $total_current_cost + $total_tax + $delcharge + $coop_fee + $potential_paypal_fee - $membership_cost;
$grand_total                         $total_current_cost + $total_tax + $delcharge + $coop_fee + $exempt_adjustment_cost + $paypal_fee;
$grand_total_coop                    $grand_total - $total_tax - $paypal_fee;
$pay_this_amount                     $subtotal_2 + $order_cost - $potential_paypal_fee + $paypal_fee + $previous_balance + $membership_cost
$pay_this_amount_zero_min            Either the same as $pay_this_amount or ZERO, whichever is greater
$most_recent_payment_amount          Amount of the members most recent payment applied to a shopping cart (could be partial)
$most_recent_payment_date            Timestamp of most recent payment entered
$most_recent_payment_order           Delivery_id to which most recent payment was applied
$taxable_membership_cost             Membership costs (if taxable)
$exempt_membership_cost              Membership costs (if exempt)
$taxable_coop_fee                    Amount of the coop fee that is applied to taxable items
$exempt_coop_fee                     Amount of the coop fee that is applied to exempt items

***************************** TOTALS DISPLAY SECTION **************************/

$totals_display_section = <<<EOT
(\$previous_balance != 0 && strpos (\$auth_type, 'institution') === false 
  ? '\n'.'____ Previous Credits or Unpaid Balances: '
    .CURSYMT.' '.number_format(\$previous_balance, 2).' '.(\$previous_balance < 0 ? 'Credit' : 'Owed')
    .'\n'
  : '').

(\$adjustments_exist != '' 
  ? '____ Adjustments\n'
    .\$adjustment_display_output
  : '').

'\nOrder Subtotal\t\t'.CURSYMT.' '.number_format(round (\$total_basket_cost, 2), 2).'\n'.

(\$delivery_id >= DELIVERY_NO_PAYPAL && SHOW_ACTUAL_PRICE != true && \$coop_markup > 0.0 
  ? '* Co-op Fee on '.CURSYMT.number_format(\$coop_fee_basis, 2).' at '.(\$coop_markup * 100).'%\t'
    .CURSYMT.' '.number_format(\$coop_fee, 2).'\n'
  : '').
  
(\$taxed_adjustment_cost != 0
  ? (\$state_tax_rate > 0.0 ? "Taxable " : "").'Adjustments\t'
    .CURSYMT.' '.number_format(\$taxed_adjustment_cost, 2).'\n'
  : '').

(\$total_tax != 0 
  ? 'Sales tax on taxable sales'.\$taxable_product_flag.'\t'
    .CURSYMT.' '.number_format(\$total_tax, 2).'\n'
: '').

(\$delivery_id < DELIVERY_NO_PAYPAL && SHOW_ACTUAL_PRICE != true
  ? (\$delivery_id < DELIVERY_NO_PAYPAL ? 'Shipping and Handling' : '')
    .(SHOW_ACTUAL_PRICE != true ? '+ '.number_format(\$coop_markup * 100, 0).'% Co-op Fee' : '').'\t'
    .CURSYMT.' '.number_format(\$coop_fee + \$potential_paypal_fee, 2).'\n'
: '').

(\$special_order != "1" && \$delcharge != 0
  ? 'Extra Charge for Home or Work Delivery\t'
    .CURSYMT.' '.number_format(\$delcharge, 2).'\n'
: '').

(\$membership_cost != 0
  ? '\n'.'Membership fees\t'.CURSYMT.' '.number_format(\$membership_cost, 2).'\n'
  : '').

(\$order_cost != 0
  ? 'Admin and Packing\t'.CURSYMT.' '.number_format(\$order_cost, 2).'\n'
  : '').

(\$exempt_adjustment_cost != 0
  ? (\$state_tax_rate > 0.0 ? "Non-taxed " : "")
    .'Adjustments'.CURSYMT.' '.number_format(\$exempt_adjustment_cost, 2).'\n'
  : '').

(\$delivery_id < DELIVERY_NO_PAYPAL
  ? 'LESS Cash discount: '.CURSYMT.' -'.number_format(\$potential_paypal_fee, 2).'\n'
  : '').

((\$previous_balance != 0 && strpos (\$auth_type, 'institution') !== false)
  ? '\nREMIT THIS AMOUNT: '.(\$unfilled_random_weight ? \$display_weight_pending_text
    : CURSYMT.' '.number_format(round (\$total_basket_cost + \$exempt_adjustment_cost + \$membership_cost + \$order_cost, 2), 2)).'\n'
: '').

(strpos (\$auth_type, 'institution') === false
  ? 'Previous '.(\$previous_balance < 0 ? 'Credit\t' : 'Balance Due').'\t'
    .CURSYMT.' '.number_format(\$previous_balance, 2)
    .'\n\n*PAY THIS AMOUNT*\t'.(\$unfilled_random_weight ? \$display_weight_pending_text
      : CURSYMT.' '.number_format (\$pay_this_amount_zero_min, 2)).'\n'
: '').

(\$invoice_payment[\$delivery_id - 1] != 0
  ? 'Thank you for your previous payment of '
    .CURSYMT.' '.number_format (\$invoice_payment[\$delivery_id - 1], 2).'\n'
: '').

(\$pay_this_amount < 0
  ? CURSYMT.' '.number_format(-1 * \$pay_this_amount, 2).' CREDIT) Nothing is due at this time\n'
  : '')
EOT;

/*************************** ROUTING DISPLAY SECTION **************************/

$routing_display_section = <<<EOT
(\$final ? ''
  : '\nTHIS IS A PROVISIONAL INVOICE.
     \nYou can update and resubmit your order until '.\$order_cycle_closed.'.
     \n')
.'\n'.\$first_name.' '.\$last_name
.'\nInvoice No. '.\$basket_id
.(strpos (\$auth_type, 'institution') !== false ? ' (Wholesale)' : '')
.'\n'.(\$email_address != '' ? 'Email: '.\$email_address.'\n' : '')
.(\$mobile_phone != '' ? 'Phone: '.\$mobile_phone .'\n' : '')
.(\$deltype == 'H' || \$deltype == 'W'
  ? '\n'.(\$deltype == 'H'
    ? 'Home address:\n'.str_replace (' ', '&nbsp;', \$address_line1).'\n'.
      (\$address_line2 != ''
        ? str_replace (' ', '&nbsp;', \$address_line2).'\n'
        : '')
      .str_replace (' ', '&nbsp;', \$city.', '.\$state.', '.\$zip).'\n'
    : '')
  .(\$deltype == 'W'
    ? 'Work address:\n'.str_replace (' ', '&nbsp;', \$work_address_line1).'\n'
      .(\$work_address_line2 != ''
        ? str_replace (' ', '&nbsp;', \$work_address_line2).'\n'
        : '')
      .str_replace (' ', '&nbsp;', \$work_city.', '.\$work_state.', '.\$work_zip)
    : '')
  : '')
.'\nCollect your order from:\n\n'.\$delcode
.'\n'.date('l, j F Y', strtotime (\$delivery_date)).', '.\$pickup_time
.'\n'.'A *map* of '.\$delcode.' can be found here:\nhttp://'.DOMAIN_NAME.LOCATIONS_PAGE
.'\n'.(\$msg_all != '' && \$use == "members" ? '\n'.\$msg_all.'\n' : '')
.(\$msg_unique != '' && \$use == "members" ? '\n'.\$msg_unique : '')
EOT;


/************************** OVERALL INVOICE DISPLAY ***************************/

$overall_invoice_display_section = <<<EOT

\$routing_display_output.'\n'
.(\$number_of_products > 0 
  ? '\nOrder Details\n'
    .\$product_display_output
  : '\nEMPTY INVOICE\nNothing ordered\n\n')
.\$totals_display_output
.(\$pay_this_amount > 0 && strlen (\$message_incomplete) == 0 && \$use == "members" 
  ? '\nP A Y M E N T  O P T I O N S\nPay '.CURSYMT.number_format(\$pay_this_amount, 2)
    .' on collection by cash or cheque payable to "'.SITE_NAME.'"\n\n'
    .\$msg_bottom.'\n\n'
    .\$disclaimer.'\n\n--------------\nYou are receiving this email from '.ORDER_EMAIL.' because you placed an order on our website: http://'.DOMAIN_NAME.PATH.'. If you would like to unsubscribe from '.SITE_NAME.' please email '.MEMBERSHIP_EMAIL.'. '.SITE_MAILING_ADDR.'.'
  : '')
EOT;


/**************************** ADMIN DISPLAY SECTION ***************************/

$admin_display_section = <<<EOT
(\$unfilled_random_weight != 1 && \$use == 'admin'
  ? '\nInvoice Administration\n\nTax information\nExempt Purchases:\t'
    .CURSYMT.number_format (round (\$exempt_product_cost, 2), 2)
    .(round (\$exempt_product_cost * \$coop_markup, 2) != 0
      ? '\nMarkup on Exempt Total:\t'
        .CURSYMT.number_format (round (\$exempt_product_cost * \$coop_markup, 2), 2)
      : '')
    .(\$extra_cost != 0
      ? '\nTotal Extra:\t'
        .CURSYMT.number_format (round (\$extra_cost, 2), 2)
      : '')
    .(\$exempt_membership_cost != 0
      ? '\nExempt Membership Fees:\t'
        .CURSYMT.number_format (round (\$exempt_membership_cost, 2), 2)
      : '')
    .(round (\$exempt_adjustment_cost, 2) != 0
      ? '\nExempt Adjustments:\t'
        .CURSYMT.number_format (round (\$exempt_adjustment_cost, 2), 2)
      : '')
    .(\$taxable_membership_cost != 0
      ? '\nExempt Membership Fees:\t'
        .CURSYMT.number_format (round (\$taxable_membership_cost, 2), 2)
      : '')
    .'\nEXEMPT TOTAL:\t'.CURSYMT.number_format (\$total_exempt_cost, 2).'\n'
    .'Taxable Purchases:\t'.CURSYMT.number_format (\$taxed_product_cost, 2).
    (\$taxed_adjustment_cost != 0
      ? '\nTaxable Adjustments:\t'
        .CURSYMT.number_format (\$taxed_adjustment_cost, 2)
      : '')
    .(round ((\$taxed_product_cost + \$taxed_adjustment_cost) * \$coop_markup, 2) != 0
      ? '\nMarkup on Taxable Total:\t'
        .CURSYMT.number_format (round ((\$taxed_product_cost + \$taxed_adjustment_cost) * \$coop_markup, 2), 2)
      : '')
    .'\nTAXABLE TOTAL:\t'.CURSYMT.number_format (\$total_taxable_cost, 2)
    .'\nCOPO City:\t'.\$copo_city
    .'\nCOPO County:\t'.\$copo_county
    .'\nCurrent city rate ('.\$city_name.'):\t'.number_format ((\$city_tax_rate * 100), 2).'%'
    .'\nCurrent county rate ('.\$county_name.'):\t'.number_format ((\$county_tax_rate * 100), 2).'%'
    .'\nCurrent state rate ('.\$state_id.'):\t'.number_format ((\$state_tax_rate * 100), 2).'%'
    .'\nCollected State Tax:\t'.CURSYMT.number_format(round(\$state_sales_tax, 2), 2)
    .'\nCollected City Tax:\t'.CURSYMT.number_format(round(\$city_sales_tax, 2), 2)
    .'\nCollected County Tax:\t'.CURSYMT.number_format( round(\$county_sales_tax, 2), 2)
    .'\nTotal Sales Tax:\t'.CURSYMT.number_format( round(\$total_tax, 2), 2)
  : '')
EOT;


/******************************************************************************/
