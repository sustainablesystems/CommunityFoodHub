<?php
include_once('../func/delivery_funcs.php');
include_once('../func/compound_products.php');

function geninvoice ($member_id, $basket_id, $delivery_id, $use, $is_html, $final)
  {
    global $font, $fontface, $connection;

    // These globals are used for the tax_rates function in tax_functions.php
    global // For delivery to home address:
           $address_line1, $address_line2, $city, $county, $state, $zip,
           // For delivery to work address:
           $work_address_line1, $work_address_line2, $work_city, $work_state, $work_zip,
           // For pickup at the pickup location (not yet implemented in v1.5.x):
           $delcode_address_line1, $delcode_address_line2, $delcode_city, $delcode_state, $delcode_zip,
           // Other relevant variables:
           $delcode_id, $deltype, $order_cycle_closed;

    // Globalize variables from the customer_invoice_template
    global $pricing_ordering_separator,
           $display_weight_actual_text,
           $display_weight_pending_text,
           $display_weight_both_text,
           $display_weight_average_text,
           $display_weight_minimum_text,
           $display_weight_maximum_text,
           $message_incomplete_zero,
           $message_incomplete_avg,
           $message_incomplete_min,
           $message_incomplete_max,
           $taxable_product_flag,
           $producer_display_section_open,
           $producer_display_section_close,
           $product_display_price_section,
           $product_display_section,
           $subproduct_display_section,
           $adjustment_display_section,
           $membership_display_section,
           $totals_display_section,
           $admin_display_section,
           $routing_display_section,
           $overall_invoice_display_section;

    // Initialize variables
    // These values will be clobbered if needed; otherwise they should be zero
    $exempt_product_cost = 0;
    $exempt_adjustment_cost = 0;
    $taxed_product_cost = 0;
    $taxed_adjustment_cost = 0;
    $city_tax_rate = 0;
    $county_tax_rate = 0;
    $state_tax_rate = 0;
    $city_sales_tax = 0;
    $county_sales_tax = 0;
    $state_sales_tax = 0;
    $total_extra = 0;
    $previous_balance = 0.0;

    include_once ('general_functions.php');
    
    if ($is_html)
    {
      include_once ('customer_invoice_template.php');
    }
    else
    {
      include_once ('customer_invoice_template_plain_text.php');
    }

    // This is used to redirect back to the originating page
    $referrer_page = $_SERVER['HTTP_REFERER'];



////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                                                                          ///
///                    PREPARE PRODUCT-LISTING SECTION                       ///
///                                                                          ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

    // Get member information from the database
    $query = '
      SELECT
        *
      FROM
        '.TABLE_MEMBER.'
      WHERE
        member_id = "'.$member_id.'"
      ';
    $result = @mysql_query($query, $connection) or die("Couldn't execute query -m.");
    while ( $row = mysql_fetch_array($result) )
      {
        $auth_type = $row['auth_type'];
        $last_name = $row['last_name'];
        $first_name = $row['first_name'];
        $business_name = $row['business_name'];
        $first_name_2 = $row['first_name_2'];
        $last_name_2 = $row['last_name_2'];
        $address_line1 = $row['address_line1'];
        $address_line2 = $row['address_line2'];
        $city = $row['city'];
        $county = $row['county'];
        $state = $row['state'];
        $zip = $row['zip'];
        $work_address_line1 = $row['work_address_line1'];
        $work_address_line2 = $row['work_address_line2'];
        $work_city = $row['work_city'];
        $work_state = $row['work_state'];
        $work_zip = $row['work_zip'];
        $email_address = $row['email_address'];
        $email_address_2 = $row['email_address_2'];
        $home_phone = $row['home_phone'];
        $work_phone = $row['work_phone'];
        $mobile_phone = $row['mobile_phone'];
        $fax = $row['fax'];
        $mem_taxexempt = $row['mem_taxexempt'];
        $mem_delch_discount = $row['mem_delch_discount'];
        include("show_name_last.php");
        $authorization = get_auth_types($auth_type);
      }

    // Initialize variables for product-listing section
    $producer_id_prior = '';
    $product_display_output = '';
    $unfilled_random_weight = 0;

    // Get information about the products ordered
    $sql2 = '
      SELECT
        '.TABLE_BASKET.'.*,
        '.TABLE_BASKET_ALL.'.prev_balance,
        '.TABLE_MEMBER.'.member_id,
        '.TABLE_MEMBER.'.business_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_PRODUCER.'.is_supplier,
        '.TABLE_SUBCATEGORY.'.subcategory_id,
        '.TABLE_SUBCATEGORY.'.category_id,
        '.TABLE_SUBCATEGORY.'.taxable AS taxable_subcat,
        '.TABLE_CATEGORY.'.taxable AS taxable_cat,
        '.TABLE_PRODUCT.'.*,
        '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN '.TABLE_BASKET.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
      LEFT JOIN '.TABLE_PRODUCT.' ON '.TABLE_PRODUCT.'.product_id = '.TABLE_BASKET.'.product_id
      LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.TABLE_PRODUCT.'.producer_id
      LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.TABLE_PRODUCT.'.subcategory_id
      LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
      LEFT JOIN '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_PRODUCER.'.member_id
      LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id = '.TABLE_PRODUCT.'.storage_id
      WHERE
        '.TABLE_BASKET.'.product_id IS NOT NULL
        AND '.TABLE_BASKET_ALL.'.member_id = "'.$member_id.'"
        AND
          (
            '.TABLE_BASKET_ALL.'.delivery_id = "'.$delivery_id.'"
            OR '.TABLE_BASKET.'.future_delivery_id = "'.$delivery_id.'"
          )
      ORDER BY
        '.TABLE_CATEGORY.'.category_name ASC,
        '.TABLE_PRODUCT.'.product_name ASC';

    $result2 = @mysql_query($sql2, $connection) or die(mysql_error());
    $number_of_products = mysql_numrows($result2);
    while ( $row = mysql_fetch_array($result2) )
      {
        // Figure out how much markup to show on products
        $product_margin = $row['margin'];
        if (SHOW_ACTUAL_PRICE)
          {
              $price_multiplier = 1 / (1 - (UNIVERSAL_MARGIN + $product_margin));
          }
        else
          {
              $price_multiplier = 1;
          }

        $a_business_name = stripslashes ($row['business_name']);
        $a_first_name = stripslashes ($row['first_name']);
        $a_last_name = stripslashes ($row['last_name']);
        $is_supplier = $row['is_supplier'];
        $category_id = $row['category_id'];
        $taxable_cat = $row['taxable_cat'];
        $taxable_subcat = $row['taxable_subcat'];
        $product_id = $row['product_id'];
        $is_compound = $row['is_compound'];
        $producer_id = $row['producer_id'];
        $product_name = stripslashes ($row['product_name']);
        $product_name = wordwrap($product_name, 25, "<br>");
        $item_price = round ($row['item_price'] * $price_multiplier, 2);
        $pricing_unit = stripslashes ($row['pricing_unit']);
        $quantity = $row['quantity'];
        $ordering_unit = stripslashes ($row['ordering_unit']);
        $out_of_stock = $row['out_of_stock'];
        $random_weight = $row['random_weight'];
        $min_weight = $row['minimum_weight'];
        $max_weight = $row['maximum_weight'];
        $total_weight = $row['total_weight'];
        $extra_charge = $row['extra_charge'];
        $notes = stripslashes($row['customer_notes_to_producer']);
        $future_delivery_id = $row['future_delivery_id'];
        $storage_code = $row['storage_code'];
        $previous_balance = $row['prev_balance'];

        // Initialize variables for this product line-item
        // "$full_" values include all pieces and the extra charges for them
        // NOTE that variables with "price" refer to a line-item product
        // NOTE that variables with "cost" refer to the entire invoice
        $full_extra_charge = 0;
        $min_price = 0;
        $max_price = 0;
        $stock_image = '';
        $weight_unit = '';
        $display_business_name = '';
        $product_display_price_output = '';
        $display_weight_actual = '';
        $display_weight_pending = '';
        $display_weight_both = '';
        $display_weight_average = '';
        $display_weight_minimum = '';
        $display_weight_maximum = '';
        $message_incomplete = '';

        // Calculate min and max weights and prices
        $full_extra_charge = $quantity * $extra_charge;
        if ( $random_weight == 1 && $out_of_stock != 1) // Random-weight item
          {
            $weight_unit = $pricing_unit;
            if ( $total_weight == 0 ) // Random weight not yet entered
              {
                $unfilled_random_weight = 1; // Flag that says "there is at least one random-weight item"
                $min_price = round ($item_price * $min_weight * $quantity, 2);
                $max_price = round ($item_price * $max_weight * $quantity, 2);
                $display_weight_actual = $display_weight_actual_text.'0 '.Inflect::pluralize ($weight_unit);
                $display_weight_pending = $display_weight_pending_text;
                $display_weight_both = $min_weight.$display_weight_both_text.$max_weight.Inflect::pluralize ($weight_unit);
                $display_weight_average = $display_weight_average_text.(($min_weight + $max_weight) / 2).' '.Inflect::pluralize_if ((($min_weight + $max_weight) / 2), $weight_unit);
                $display_weight_minimum = $display_weight_minimum_text.$min_weight.' '.Inflect::pluralize_if ($min_weight, $weight_unit);
                $display_weight_maximum = $display_weight_maximum_text.$max_weight.' '.Inflect::pluralize_if ($max_weight, $weight_unit);
                // Get the effective price for this random-weight item
                if ( RANDOM_CALC == 'ZERO' )
                  {
                    $effective_price = 0;
                    $message_incomplete = $message_incomplete_zero;
                  }
                elseif (RANDOM_CALC == 'AVG' )
                  {
                    $effective_price = round (($min_price + $max_price) / 2, 2);
                    $message_incomplete = $message_incomplete_avg;
                  }
                elseif (RANDOM_CALC == 'MIN' )
                  {
                    $effective_price = round ($min_price, 2);
                    $message_incomplete = $message_incomplete_min;
                  }
                elseif (RANDOM_CALC == 'MAX' )
                  {
                    $effective_price = round ($max_price, 2);
                    $message_incomplete = $message_incomplete_max;
                  }
              }
            else // Random weight has been entered
              {
                $min_weight = $total_weight;
                $min_price = round ($item_price * $total_weight, 2);
                $max_weight = $total_weight;
                $max_price = round ($item_price * $total_weight, 2);
                $display_weight_actual = $total_weight.' '.Inflect::pluralize_if ($total_weight, $weight_unit);
                $display_weight_pending = $display_weight_actual;
                $display_weight_both = $display_weight_actual;
                $display_weight_average = $display_weight_actual;
                $display_weight_minimum = $display_weight_actual;
                $display_weight_maximum = $display_weight_actual;
                $effective_price = round ($item_price * $total_weight, 2);

              }
          }
        else // Not a random-weight item
          {
            $min_weight = 0;
            $max_weight = 0;
            $min_price = round ($item_price * $quantity, 2);
            $max_price = round ($item_price * $quantity, 2);
            $effective_price = round ($item_price * $quantity, 2);
          }

        if ( $future_delivery_id == $delivery_id )
          {
            $full_extra_charge = 0; // Clobber any extra prices
            $min_weight = 0;
            $min_price = 0;
            $max_weight = 0;
            $max_price = 0;
          }

        if ( $out_of_stock == 1 )
          {
            $full_extra_charge = 0; // Clobber any extra prices
            $effective_price = 0;
            $min_weight = 0;
            $min_price = 0;
            $max_weight = 0;
            $max_price = 0;
            $stock_image = $out_of_stock_checkmark;
          }

        $extra_cost = $extra_cost + $full_extra_charge;

        if ($mem_taxexempt != 1 && ($taxable_cat == 1 || $taxable_subcat == 1)) // TAXABLE SUB/CATEGORY
          {
            $taxed_product_cost = $taxed_product_cost + $effective_price;
            $taxable_product = $taxable_product_flag;
          }
        else // NOT TAXABLE SUB/CATEGORY
          {
            $exempt_product_cost = $exempt_product_cost + $effective_price; // Already includes any tax-deductable extra_charge
            $taxable_product = '';
          }

        if ( $a_business_name != '' )
          {
            $display_business_name = $a_business_name;
          }
        else
          {
            $display_business_name = $a_first_name.' '.$a_last_name;
          }

        if ( $producer_id != $producer_id_prior )
          {
            // First "close" the former producer section if there is one already "open"
            if ( $producer_id_prior != '' )
              {
                $product_display_output .= eval ("return $producer_display_section_close;");
              }
            // Then "open" the next producer section
            $product_display_output .= eval ("return $producer_display_section_open;");
          }

        $product_display_price_output = eval ("return $product_display_price_section;");

        $product_display_output .= eval ("return $product_display_section;");

        // Check if this is a box or hamper - if so display subproducts
        if ( $is_compound )
        {
          $subprod_names = subprod_details_get(
                  TABLE_PRODUCT, TABLE_COMPOUND_PRODUCT, $product_id);
          
          $product_display_output .= eval ("return $subproduct_display_section;");
        }

        // Update "prior" producer so we know when the producer has changed in order to begin a new producer section
        $producer_id_prior = $producer_id;
      }
    // Need a final "close" for the last producer section at the end of the loop.
    $product_display_output .= eval ("return $producer_display_section_close;");


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                                                                          ///
///    GET INFORMATION ON DEBITS AND CREDITS (ADJUSTMENTS AND MEMBERSHIP)    ///
///                                                                          ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

    // The prev_balance contains any adjustments.  We still keep a
    // transaction history, but we don't trawl through it every single time we
    // want an account balance as this is not scalable!

    // Initialize variables for this section
    $adjustment_display_output = '';
    $adjustments_exist = 0; // Flag that adjustments exist for this order
    $membership_this_exist = 0; // Flag that membership information exists for this order

    // Get adjustments that are included in the prev_balance.
    // These are adjusments that were added by an admin before the customer 
    // opened the basket, but have not been shown to the customer on an earlier invoice.
    $sql_select_adj = '
      SELECT
        '.TABLE_CUSTOMER_ADJ.'.amount AS adj_amount,
        '.TABLE_CUSTOMER_ADJ.'.comments AS adj_comments,
        '.TABLE_TRANS_TYPES.'.ttype_name AS adj_name
      FROM
        '.TABLE_CUSTOMER_ADJ.'
      LEFT JOIN
        '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_CUSTOMER_ADJ.'.ttype_id
      WHERE
        '.TABLE_CUSTOMER_ADJ.'.member_id = "'.$member_id.'"
      AND
        '.TABLE_CUSTOMER_ADJ.'.applied = "1"
      ORDER BY
        '.TABLE_CUSTOMER_ADJ.'.adjustment_id ASC';
    $result_adj = @mysql_query($sql_select_adj, $connection) or die(mysql_error());
    
    while ( $row = mysql_fetch_array($result_adj) )
      {
        $adjustments_exist = 1;
        $transaction_name = $row["adj_name"];
        $transaction_amount = $row["adj_amount"];
        $transaction_comments = $row["adj_comments"];
        
        $adjustment_display_output .= eval ("return $adjustment_display_section;");
      }


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                                                                          ///
///               COLLECT MEMBER AND ROUTING INFORMATION                     ///
///                                                                          ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

    // Get invoice messages for this order
    $query = '
      SELECT
        msg_all,
        msg_bottom,
        disclaimer
      FROM
        '.TABLE_CURDEL;
    $result = @mysql_query($query, $connection) or die(mysql_error());
    if ( $row = mysql_fetch_array ($result) )
      {
        $msg_all = stripslashes ($row['msg_all']);
        $msg_bottom = stripslashes ($row['msg_bottom']);
        $disclaimer = stripslashes ($row['disclaimer']);
      }

    // Get delivery information for this specific order
    $query = '
      SELECT
        '.TABLE_BASKET_ALL.'.basket_id,
        '.TABLE_BASKET_ALL.'.delcode_id,
        '.TABLE_BASKET_ALL.'.payment_method,
        '.TABLE_ROUTE.'.route_id,
        '.TABLE_ROUTE.'.route_desc,
        '.TABLE_PAY.'.*,
        '.TABLE_ROUTE.'.route_name,
        '.TABLE_DELCODE.'.*,
        '.TABLE_BASKET_ALL.'.delivery_cost,
        '.TABLE_BASKET_ALL.'.deltype AS ddeltype,
        '.TABLE_BASKET_ALL.'.transcharge AS basket_transcharge,
        '.TABLE_DELDATE.'.delivery_id,
        '.TABLE_DELDATE.'.delivery_date,
        '.TABLE_DELDATE.'.special_order,
        '.TABLE_MEMBER.'.auth_type,
        '.TABLE_MEMBER.'.membership_type_id,
        '.TABLE_MEMBERSHIP_TYPES.'.order_cost
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN '.TABLE_DELDATE.' ON '.TABLE_BASKET_ALL.'.delivery_id = '.TABLE_DELDATE.'.delivery_id
      LEFT JOIN '.TABLE_DELCODE.' ON '.TABLE_BASKET_ALL.'.delcode_id = '.TABLE_DELCODE.'.delcode_id
      LEFT JOIN '.TABLE_ROUTE.' ON '.TABLE_DELCODE.'.route_id = '.TABLE_ROUTE.'.route_id
      LEFT JOIN '.TABLE_PAY.' ON '.TABLE_BASKET_ALL.'.payment_method = '.TABLE_PAY.'.payment_method
      LEFT JOIN '.TABLE_MEMBER.' ON '.TABLE_BASKET_ALL.'.member_id = '.TABLE_MEMBER.'.member_id
      LEFT JOIN '.TABLE_MEMBERSHIP_TYPES.' ON '.TABLE_MEMBER.'.membership_type_id = '.TABLE_MEMBERSHIP_TYPES.'.membership_type_id
      WHERE
        '.TABLE_MEMBER.'.member_id = "'.$member_id.'"
        AND '.TABLE_DELDATE.'.delivery_id = "'.$delivery_id.'"
      ';
    $result = @mysql_query($query, $connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($result) )
      {
        $deltype = $row['ddeltype'];
        $delcode_id = $row['delcode_id'];
        $delcode = $row['delcode'];
        $delcharge = $row['delivery_cost'];
        $transcharge = $row['basket_transcharge'];
        $delivery_date = $row['delivery_date'];
        $payment_method = $row['payment_method'];
        $payment_desc = $row['payment_desc'];
        $msg_unique = $row['msg_unique'];
        $route_name = $row['route_name'];
        $route_desc = $row['route_desc'];
        $deldesc = $row['deldesc'];
        $truck_code = $row['truck_code'];
        $special_order = $row['special_order'];
        $current_delivery_id = $row['current_delivery_id'];
        $auth_type = $row['auth_type'];
        $membership_type_id = $row['membership_type_id'];
        $order_cost = $row['order_cost'];
        $authorization = get_auth_types($auth_type);
        $pickup_time = $row['pickup_time'];
        $offset = $row['delday_offset'];
        $delivery_date = get_delivery_from_offset($delivery_date, $offset, "l, j F Y");
      }

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                                                                          ///
///               DO CALCULATION OF TOTALS AND OTHER VALUES                  ///
///                                                                          ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

    // We don't use this so zero it.  All the stuff below about tax needs review.
    $coop_markup = 0;
    
    // Calculate totals for the invoice
    $coop_fee_basis = $taxed_product_cost + $taxed_adjustment_cost + $exempt_product_cost;
    $coop_fee = round ($coop_markup * $coop_fee_basis, 2);

    $total_taxable_cost = $taxed_product_cost + $taxed_adjustment_cost;
    $total_exempt_cost = $exempt_product_cost + $extra_cost; // $exempt_adjustment_cost is added AFTER all other totals

    // Note the $total_current_cost does not include $membership_cost, added below
    $total_current_cost = $total_taxable_cost + $total_exempt_cost;

    // Add membership_fee as taxable (or not)
    if (MEMBERSHIP_IS_TAXED == true)
      {
        $taxable_membership_cost = $membership_cost + $order_cost;
        $exempt_membership_cost = 0;
        $total_taxable_cost = $total_taxable_cost + $membership_cost + $order_cost;
      }
    else
      {
        $taxable_membership_cost = 0;
        $exempt_membership_cost = $membership_cost + $order_cost;
        $total_exempt_cost = $total_exempt_cost + $membership_cost + $order_cost;
      }


    // Handle whether the coop_fee is taxable (or not)
    if (COOP_FEE_IS_TAXED == 'always')
      {
        // Add the $coop_markup amount to the entire co-op fee
        $taxable_coop_fee = $coop_fee;
        $total_taxable_cost = $total_taxable_cost + $taxable_coop_fee;
        $exempt_coop_fee = 0;
      }
    elseif (COOP_FEE_IS_TAXED == 'on taxable items')
      {
        // Add the $coop_markup amount to the taxable portion of the co-op fee
        $taxable_coop_fee = round ($coop_markup * ($taxed_product_cost + $taxed_adjustment_cost), 2);
        $total_taxable_cost = $total_taxable_cost + $taxable_coop_fee;
        $exempt_coop_fee = round ($coop_markup * $exempt_product_cost, 2);
        $total_exempt_cost = $total_exempt_cost + $exempt_coop_fee;
      }
    elseif (COOP_FEE_IS_TAXED == 'never')
      {
        // Do nothing
        $exempt_coop_fee = 0;
      }

    $total_basket_cost = $taxed_product_cost + $exempt_product_cost + $extra_cost;

    // Calculate taxes
    $total_tax_rate = $state_tax_rate + $county_tax_rate + $city_tax_rate;
    $state_sales_tax = round ($state_tax_rate * ($total_taxable_cost), 2);
    $county_sales_tax = round ($county_tax_rate * ($total_taxable_cost), 2);
    $city_sales_tax = round ($city_tax_rate * ($total_taxable_cost), 2);
    $total_tax = $state_sales_tax + $county_sales_tax + $city_sales_tax;

    // Adjust delivery charge
    // Delivery charge is zero if there are no products in the basket
    // or if the member gets the delivery charge discount
    if ( $mem_delch_discount == 1 || $number_of_products == 0)
      {
        $delcharge = 0;
        $order_cost = 0;
      }

    $subtotal_1 = $total_current_cost + $total_tax + $delcharge + $coop_fee + $membership_cost; // Legacy: does NOT include $previous_balance

    // Set up paypal fee -- if passing along paypal fees and if paying with paypal
    // $potential_paypal_fee is the fee that *would* be paid to paypal regardless of whether it is used or not
    // while the $paypal_fee is the actual amount we set aside for paypal, which could be nothing in not paying by paypal.
    $paypal_fee = 0;
    $potential_paypal_fee = round (((($subtotal_1 + $exempt_adjustment_cost + 0.30) / 0.971) * 0.029) + 0.30, 2);

    // Don't apply a paypal fee if there are no other charges or if we are not using paypal any more
    if ($potential_paypal_fee == 0.31 || $delivery_id >= DELIVERY_NO_PAYPAL)
      {
        $potential_paypal_fee = 0;
      }
    if ($delivery_id < DELIVERY_NO_PAYPAL && $payment_method == 'P')
      {
        $paypal_fee = $potential_paypal_fee;
      }

    $subtotal_2 = $total_current_cost + $total_tax + $delcharge + $coop_fee + $potential_paypal_fee;

    $grand_total = $total_current_cost + $total_tax + $delcharge + $coop_fee + $exempt_adjustment_cost + $paypal_fee + $membership_cost;
    $grand_total_coop = $grand_total - $total_tax - $paypal_fee;

    // The $pay_this_amount variable is here for convenience and is *probably* the "bottom line" for most invoices
    $pay_this_amount = round ($subtotal_2 + $order_cost - $potential_paypal_fee + $paypal_fee + $previous_balance + $membership_cost + $exempt_adjustment_cost, 2);
    $pay_this_amount_zero_min = $pay_this_amount;
    if ($pay_this_amount_zero_min <= 0)
      {
        $pay_this_amount_zero_min = 0;
      }

    // Apply the remaining templates to generate the final invoice
    $routing_display_output = eval ("return $routing_display_section;");
    $totals_display_output = eval ("return $totals_display_section;");
    $overall_invoice_display_output = eval ("return $overall_invoice_display_section;");

    // The admin display section formats the key administrative elements on the page which
    // are passed back along with the rest of the invoice for display, but are kept separate
    // so they do not get saved into the database with the finalized invoice.

    // Don't display this tax information for the moment as it's not relevant for us
    //eval ("return $admin_display_section;");


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                                                                          ///
///                DO FINALIZATION IF THAT HAS BEEN INITIATED                ///
///                                                                          ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

    if ( $use == 'adminfinalize' )
      {
        $invoice_content = addslashes($invoice_content);
        // Note the transcharge code was deprecated before leaving Oklahoma
        $sqlo = '
          UPDATE
            '.TABLE_BASKET_ALL.'
          SET
            subtotal = "'.($total_current_cost).'",
            sh = "'.$coop_fee.'",
            transcharge = "0",
            delivery_cost = "'.$delcharge.'",
            invoice_content = "'.mysql_real_escape_string($overall_invoice_display_output).'",
            grand_total = "'.($grand_total + $order_cost).'",
            grand_total_coop = "'.($grand_total_coop + $order_cost).'",
            surcharge_for_paypal = "'.$paypal_fee.'",
            finalized = "1"
          WHERE
            basket_id = "'.$basket_id.'"
            AND member_id = "'.$member_id.'"';
        $resulto = @mysql_query($sqlo,$connection) or die(mysql_error());
        $message = 'Invoice saved - adminfinalize in gen_invoice.php';
        $sqlselect = '
          SELECT
            basket_id
          FROM
            '.TABLE_CUSTOMER_SALESTAX.'
          WHERE
            basket_id = "'.$basket_id.'"';
        $resultselect = @mysql_query($sqlselect,$connection) or die(mysql_error());
        $number_of_rows = mysql_numrows($resultselect);
        if ( $number_of_rows != 0 )
          {
            $sqltx = '
              UPDATE
                '.TABLE_CUSTOMER_SALESTAX.'
              SET
                delivery_id = "'.$delivery_id.'",
                taxable_total = "'.$total_taxable_cost.'",
                exempt_total = "'.$total_exempt_cost.'",
                taxrate_state = "'.$state_tax_rate.'",
                collected_statetax = "'.$state_sales_tax.'",
                copo_city = "'.$copo_city.'",
                taxrate_city = "'.$city_tax_rate.'",
                collected_citytax = "'.$city_sales_tax.'",
                copo_county = "'.$copo_county.'",
                taxrate_county = "'.$county_tax_rate.'",
                collected_countytax = "'.$county_sales_tax.'",
                last_modified = now()
              WHERE
                basket_id = "'.$basket_id.'"';
            $resulttx = @mysql_query($sqltx,$connection) or die(mysql_error());
          }
        else
          {
            $sqlinsert = '
              INSERT INTO '.TABLE_CUSTOMER_SALESTAX.'
                (
                  basket_id,
                  delivery_id,
                  taxable_total,
                  exempt_total,
                  taxrate_state,
                  collected_statetax,
                  copo_city,
                  taxrate_city,
                  collected_citytax,
                  copo_county,
                  taxrate_county,
                  collected_countytax
                )
              VALUES
                (
                  "'.$basket_id.'",
                  "'.$delivery_id.'",
                  "'.$total_taxable_cost.'",
                  "'.$total_exempt_cost.'",
                  "'.$state_tax_rate.'",
                  "'.$state_sales_tax.'",
                  "'.$copo_city.'",
                  "'.$city_tax_rate.'",
                  "'.$city_sales_tax.'",
                  "'.$copo_county.'",
                  "'.$county_tax_rate.'",
                  "'.$county_sales_tax.'")';
            $resultinsert = @mysql_query($sqlinsert,$connection) or die(mysql_error());
          }
        $query = '
          SELECT
            ttype_name
          FROM
            '.TABLE_TRANS_TYPES.'
          WHERE
            ttype_id="27"';
        $sql = mysql_query($query);
        $row = mysql_fetch_array($sql);
        $query = '
          INSERT INTO '.TABLE_TRANS.'
            (
              transaction_type,
              transaction_name,
              transaction_amount,
              transaction_user,
              transaction_member_id,
              transaction_basket_id,
              transaction_delivery_id,
              transaction_timestamp
            )
          VALUES
            (
              "27",
              "'.$row['ttype_name'].'",
              "'.$total_current_cost.'", /* WAS: $total_basket_cost */
              "'.$_SESSION['valid_c'].'",
              "'.$member_id.'",
              "'.$basket_id.'",
              "'.$delivery_id.'",
              now()
              )';
        $sql = mysql_query($query);
        $query = '
          SELECT
            ttype_name
          FROM
            '.TABLE_TRANS_TYPES.'
          WHERE
            ttype_id="29"';
        $sql = mysql_query($query);
        $row = mysql_fetch_array($sql);
        $query = '
          INSERT INTO '.TABLE_TRANS.'
            (
              transaction_type,
              transaction_name,
              transaction_amount,
              transaction_user,
              transaction_member_id,
              transaction_basket_id,
              transaction_delivery_id,
              transaction_timestamp
            )
          VALUES
            (
              "29",
              "'.$row['ttype_name'].'",
              "'.$total_tax.'",
              "'.$_SESSION['valid_c'].'",
              "'.$member_id.'",
              "'.$basket_id.'",
              "'.$delivery_id.'",
              now()
            )';
        $sql = mysql_query($query);
        $query = '
          SELECT
            ttype_name
          FROM
            '.TABLE_TRANS_TYPES.'
          WHERE
            ttype_id="30"';
        $sql = mysql_query($query);
        $row = mysql_fetch_array($sql);
        $query = '
          INSERT INTO '.TABLE_TRANS.'
            (
              transaction_type,
              transaction_name,
              transaction_amount,
              transaction_user,
              transaction_member_id,
              transaction_basket_id,
              transaction_delivery_id,
              transaction_timestamp
            )
          VALUES
            (
              "30",
              "'.$row['ttype_name'].'",
              "'.$coop_fee.'",
              "'.$_SESSION['valid_c'].'",
              "'.$member_id.'",
              "'.$basket_id.'",
              "'.$delivery_id.'",
              now()
            )';
        $sql = mysql_query($query);
        $query = '
          SELECT
            ttype_name
          FROM
            '.TABLE_TRANS_TYPES.'
          WHERE
            ttype_id = "32"
        ';
        $sql = mysql_query($query);
        $row = mysql_fetch_array($sql);
        $query = '
          INSERT INTO '.TABLE_TRANS.'
            (
              transaction_type,
              transaction_name,
              transaction_amount,
              transaction_user,
              transaction_member_id,
              transaction_basket_id,
              transaction_delivery_id,
              transaction_timestamp
            )
          VALUES
            (
              "32",
              "'.$row['ttype_name'].'",
              "'.$paypal_fee.'",
              "'.$_SESSION['valid_c'].'",
              "'.$member_id.'",
              "'.$basket_id.'",
              "'.$delivery_id.'",
              now()
            )';
        $sql = mysql_query($query);
        $query = '
          SELECT
            ttype_name
          FROM
            '.TABLE_TRANS_TYPES.'
          WHERE
            ttype_id = "33"
          ';
        $sql = mysql_query($query);
        $row = mysql_fetch_array($sql);
        $query = '
          INSERT INTO '.TABLE_TRANS.'
            (
              transaction_type,
              transaction_name,
              transaction_amount,
              transaction_user,
              transaction_member_id,
              transaction_basket_id,
              transaction_delivery_id,
              transaction_timestamp
            )
          VALUES
            (
              "33",
              "'.$row['ttype_name'].'",
              "'.$delcharge.'",
              "'.$_SESSION['valid_c'].'",
              "'.$member_id.'",
              "'.$basket_id.'",
              "'.$delivery_id.'",
              now()
            )
          ';
        $sql = mysql_query($query);
        $query = '
          SELECT
            ttype_name
          FROM
            '.TABLE_TRANS_TYPES.'
          WHERE
            ttype_id = 34
        ';
        $sql = mysql_query($query);
        $row = mysql_fetch_array($sql);
        $query = '
          INSERT INTO '.TABLE_TRANS.'
            (
              transaction_type,
              transaction_name,
              transaction_amount,
              transaction_user,
              transaction_member_id,
              transaction_basket_id,
              transaction_delivery_id,
              transaction_timestamp
            )
          VALUES
            (
              "34",
              "'.$row['ttype_name'].'",
              "'.($pay_this_amount - $previous_balance).'", /* WAS $subtotal_2 */
              "'.$_SESSION['valid_c'].'",
              "'.$member_id.'",
              "'.$basket_id.'",
              "'.$delivery_id.'",
              now()
        )';
        $sql = mysql_query($query);

        // Add per-order "membership" fees if there are such
        if ($order_cost != 0)
          {
            // If there isn't an existing transaction to update, then add a new one
            $query = '
              INSERT INTO
                '.TABLE_TRANSACTIONS.'
              SET
                transaction_type = "64",
                transaction_name = (SELECT ttype_name FROM '.TABLE_TRANS_TYPES.' WHERE ttype_id = "64"),
                transaction_amount = '.$order_cost.',
                transaction_user = "'.$_SESSION['valid_c'].'",
                transaction_memo = "Per-order credit/fee",
                transaction_member_id = "'.$member_id.'",
                transaction_basket_id = "'.$basket_id.'",
                transaction_delivery_id = "'.$delivery_id.'",
                transaction_timestamp = now()
                ';
            $result = mysql_query($query);
          }
      }

    // Before sending any output, check to see if we need to bounce back to a referrer page
    if ($_POST['referrer_page'] != '')
      {
        header("Location: ".$_POST['referrer_page']);
        exit (1);
      }

    // Don't display this tax information for the moment as it's not relevant for us
    return $overall_invoice_display_output;//.$admin_display_output;
  }

?>
