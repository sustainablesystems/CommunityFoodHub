<?php
// SERVER AND SITE SETUP

                          // Version is used to keep you alerted to new versions and updates of the software
$current_version        = '1.5.4';

                          // Some common timezone options in the United States:
                          //    Pacific:  America/Los_Angeles
                          //    Mountain: America/Denver
                          //    Central:  America/Chicago
                          //    Eastern:  America/New_York
$local_time_zone        = 'Europe/London';

                          // Show currency according to symbol below
$currency_symbol        = '&pound;';  // HTML '$', '&euro;', '&yen;' ...
$currency_symbol_plain_text = '£';    // Plain text, e.g. for emails

                          // Base url is used for web references.  Do NOT include a trailing slash.
//$site_url               = 'http://fgu.ttkingston.org';
$site_url               = 'http://localhost';

                          // This may be different from the site URL
//$homepage_url           = 'http://ttkingston.org/fgu';
$homepage_url           = 'http://localhost/foodcoop';

                          // Directory for the local food coop software on the website
$food_coop_store_path   = '/foodcoop/shop/';

                          // Internal file path to document_root
//$file_path              = '/home/sites/ttkingston.org/public_html/fgu';
$file_path              = '/xampp/htdocs';

                          // Domain name -- used for email references
//$domainname             = 'fgu.ttkingston.org';
$domainname             = 'localhost';

                          // Name of your organization -- used in some textual messages
//$site_name              = 'From the Ground Up';
$site_name              = 'Local Food Coop';

                          // Name of your organization -- shortened or abbreviated
//$site_name_short        = 'FGU';
$site_name_short        = 'LFC';

                          // Type of your organisation -- used in some textual messages
$org_type               = 'Food Coop';

                          // Contact information used for textual reference
//$site_contact_info      = 'Kingston-upon-Thames, London';
$site_contact_info      = 'Somewhere, UK';

                          // Mailing address
$site_mailing_address   = 'Some Number, Some Road, Somewhere, UK';

                          // Directory for graphic files for the coop section of your website
$site_graphics          = '/foodcoop/shop/grfx/';

                          // Filename of your favicon (in the root directory)
$favicon                = '/favicon.ico';

                          // Typical period of ordering cycle
$days_per_cycle         = 7;

                          // End-of-order window for institutional buyers (seconds) Set $institution_window
                          // value high to allow institutional buyers all the time and set to zero to prevent
                          // any use. NOTE: 3600 * 24 = 1 day in seconds.
$institution_window     = (3600 * 24) * 0;

                          // TRUE = apply a margin to the cost price to calculate the selling price
                          // charged to customers; FALSE = the unit price entered by the
                          // producer/supplier is the selling price that customers are charged.
                          // The margin to use is specified using $universal_margin, below.
$show_actual_price      = true;

                          // If this is true, producer contact information will be shown on the public pages
                          // otherwise set to false to only shown for logged-in members.
$prdcr_info_public      = true;

                          // If this is true, the producer sign-up form will be available (to logged-in members).
$prdcr_form_available   = true;

/*********************** VOLUNTEER ROTA/CALENDAR ***********************/

// Link to external Google calendar to display on calendar page
$calendar_url = 'https://www.google.com/calendar/embed?showTitle=0&amp;showPrint=0&amp;showTabs=0&amp;showCalendars=0&amp;showTz=0&amp;height=600&amp;wkst=2&amp;hl=en_GB&amp;bgcolor=%23FFFFFF&amp;src=fguangels@gmail.com&amp;color=%23875509&amp;ctz=Europe%2FLondon';

// Link to external Google calendar to display on homepage
$calendar_url_homepage = 'https://www.google.com/calendar/embed?showTitle=0&amp;showPrint=0&amp;showTabs=0&amp;showCalendars=0&amp;showTz=0&amp;mode=AGENDA&amp;height=250&amp;wkst=2&amp;hl=en_GB&amp;bgcolor=%23FFFFFF&amp;src=fguangels@gmail.com&amp;color=%232F6309&amp;ctz=Europe%2FLondon';

// If this is true, the Google Calendar based volunteer rota functionality
// will be enabled (a link on the member homepage to the rota will be displayed)
// For configuration of the volunteer rota, see google/src/config.php
$volunteer_rota_enabled = false;

// Calendar address can be found by going to the Calendar's "Calendar settings" in Google Calendar
$volunteer_rota_calender = 'fguangels@gmail.com';

// The maximum volunteers for any event.  Crude, but suits our needs at the moment.
// This could be applied only to recurring events (assuming these are collections),
// or read from our database and configurable through admin pages.
$volunteer_rota_max_volunteers = 5;

/**************************************************************/

                          // Set custom paging directives for htmldoc here
$htmldoc_paging         = '<!-- MEDIA DUPLEX NO --><!-- MEDIA TOP 0.3in --><!-- MEDIA BOTTOM 0.3in -->';

$valid_auth_types       = 'member,producer,administrator,sysadmin,institution';

// EXTERNAL FILE SETUP

                          // membership standards
$page_terms_of_service  = $food_coop_store_path.'terms_and_conditions.php';

                          // pickup and deilvery locations:
$page_locations         = $food_coop_store_path.'locations.php';

                          // list of producers in the coop
$page_coopproducers     = $food_coop_store_path.'coopproducers.php';

                          // path to invoices
$invoice_web_path       = $food_coop_store_path.'members/invoices/';
$invoice_file_path      = $file_path.$invoice_web_path;

                          // path to common functionality directory
$func_file_path         = $file_path.$food_coop_store_path.'func/';


// DATABASE SETUP

                          // Enter the db host
$db_host                = 'localhost';

                          // Enter the username for db access
$db_user                = 'root';

                          // Enter the password for db access
$db_pass                = '';

                          // Enter the database name
$db_name                = 'food_coop_db';

                          // This is probably blank
$db_prefix              = '';

                          // If you want to use a master password to access all member accounts
                          // enter the MD5 of master password as generated by mysql
$md5_master_password    = '';


// DISPLAY SETUP

                          // Configure this to reflect your desired routing code template: The following
                          // values will be auto-filled from like-named variables in the scripts used
                          // to create the routing templates.  For example, !BASKET_ID! is replaced
                          // with the contents of the $basket_id variable.
                          //
                          //   !BASKET_ID!       customer basket id
                          //   !MEMBER_ID!       member id number
                          //   !FIRST_NAME!      customer first name
                          //   !LAST_NAME!       customer last name
                          //   !SHOW_MEM!        customer name in "Last, First" format
                          //   !SHOW_MEM2!       customer_name in "First Last" format
                          //   !BUSINESS_NAME!   customer business name -- may not exist
                          //   !HUB!             the delivery hub
                          //   !TRUCK_CODE!      routing truck code
                          //   !DELCODE_ID!      delivery code id (the abbreviation)
                          //   !DELCODE!         delivery code (long form of name)
                          //   !DELTYPE!         delivery type (H:home, W:work, P:pickup)
                          //   !A_BUSINESS_NAME! producer business name
                          //   !PRODUCT_ID!      product numeric id
                          //   !PRODUCT_NAME!    full product name
                          //   !ITEM_PRICE!      item price per pricing-unit (not the total)
                          //   !ORDERING_UNIT!   units used for ordering
                          //   !QUANTITY!        quantity of ordering units that were ordered
                          //   !STORAGE_CODE!    product storage code (may not always apply)
$route_code_template =    '!HUB!-!MEMBER_ID!-!DELCODE_ID! !TRUCK_CODE! [!STORAGE_CODE!]';

                          // Font face used in various locations
$fontface =               'arial';

                          // Another font declaration used in other locations
$font =                   '<font size="-1" face="'.$fontface.'">';

                            // Some longer listings use this value for pagination
$default_results_per_page = 50;

                          // Percentage charged to producers
//$producer_markdown =      0.1;
$producer_markdown =      0.0;

                          // Margin (percentage of selling price that is "profit")
                          // This must be < 1 as 100% margin is impossible.
                          // This is NOT a markup - markup is percentage of cost
                          // price added to cost price to get selling price.
$universal_margin =       0.1;

                          // Percentage charged to institutions
$institution_markup =     0.05;

                          // Change this if your organization is, i.e. a "partnership".  This is used in
                          // various textual places.  i.e. "Welcome to the ******"
$organization_type =      'cooperative';

                          // Use this to enable producer confirmation settings (NOT FULLY TESTED)
$req_prdcr_confirm =      false;

                          // Use this to control whether paypal fees are passed to customers.  Please note
                          // that it is of questionable legality to pass along paypal or credit-card fees.
                          // Also note that this ability will probably be deprecated in future versions so
                          // it is strongly suggested NOT to use this setting.  If paypal charges will not
                          // be passed on to customers, then set this value to zero.  To always use paypal
                          // surcharges, set this to a very large number -- like 1000000
$delivery_no_paypal =     0;

                          // Don't rely on this to be completely fool-proof, but it is a beginning.
$state_tax =              0.0;

                          // Show logo in the header?
$show_header_logo =       true;

                          // Show site name in the header?
$show_header_sitename =   false;

                          // Enable/disable pdf generation by htmldoc
$use_htmldoc =            false;

                          // 1: if new producers should be pending; 0: if new producers should have immediate access
$new_producer_pending =   '1';

                          // Possible values for calculating charges for items with random weights:
                          // ZERO : Use a zero charge for the items
                          // AVG  : Use an average cost for the two weights
                          // MAX  : Use maximum costs
                          // MIN :  Use minimum costs
                          // Does not affect DISPLAY (see customer_invoice_template
                          // Only affects calculations of totals/costs
$random_calc =            'ZERO';

                          // true or false if membership should be a taxable quantity
$membership_taxed =       false;

                          // Set according to whether the co-op fee is taxable.  Choose from:
                          // For everything that has a co-op fee:       'always'
                          // Only for things that are already taxed:    'on taxable items'
                          // The coop fee is never taxed for anything:  'never'
$coop_fee_taxed =         'on taxable items';


// CONTACT EMAIL SETUP

                          // Set up your site email addresses here.  The software uses all of these email aliases
                          // however you can point them all to just a few (or one) address if you desire.

/*$email_customer         = 'customer@'.$domainname;
$email_general          = 'info@'.$domainname;
$email_help             = 'help@'.$domainname;
$email_membership       = 'accounts@'.$domainname;
$email_orders           = 'orders@'.$domainname;
$email_paypal           = 'paypal@'.$domainname;
$email_pricelist        = 'pricelist@'.$domainname;
$email_problems         = 'problems@'.$domainname;
$email_producer_care    = 'producer-care@'.$domainname;
$email_software         = 'software@'.$domainname;
$email_standards        = 'standards@'.$domainname;
$email_treasurer        = 'treasurer@'.$domainname;
$email_volunteer        = 'volunteers@'.$domainname;
$email_supply           = 'supply@'.$domainname;
$email_webmaster        = 'web@'.$domainname;*/

$email_customer         = 'root@'.$domainname;
$email_general          = 'root@'.$domainname;
$email_help             = 'root@'.$domainname;
$email_membership       = 'root@'.$domainname;
$email_orders           = 'root@'.$domainname;
$email_paypal           = 'root@'.$domainname;
$email_pricelist        = 'root@'.$domainname;
$email_problems         = 'root@'.$domainname;
$email_producer_care    = 'root@'.$domainname;
$email_software         = 'root@'.$domainname;
$email_standards        = 'root@'.$domainname;
$email_treasurer        = 'root@'.$domainname;
$email_volunteer        = 'root@'.$domainname;
$email_supply           = 'root@'.$domainname;
$email_webmaster        = 'root@'.$domainname;

                          // The membership form will be sent to these email address(es) -- separate with commas
                          // Use "SELF" to send an email copy to the member who is filling out the form.
$email_member_form      = 'SELF,'.$email_membership;

                          // The producer form will be sent to these email address(es) -- separate with commas
                          // The "SELF" term does not function with this form.
$email_producer_form    = 'SELF,'.$email_standards;              // Where new producer emails notifications are sent

                          // Name of the membership coordinator or other official contact person (plain-text only).
                          // This is used e.g. for signing the member welcome letter (Use double-quotes so the
                          // newline character will be preserved)
$authorized_person      = "Anon\nPresident";


// GATHER CONFIGURATION OVER-RIDES FROM AN EXTERNAL FILE
@include_once ("config_override.php"); // Include override values only if the file exists


// ______ DEFNINITION OF CONSTANTS _________

// Highly unlikely that you will need to modify anything below this point

date_default_timezone_set($local_time_zone);
define('CURRENT_VERSION' ,      $current_version);
define('DB_NAME' ,              $db_name);
define('HOST_NAME' ,            $db_host);
define('MYSQL_USER' ,           $db_user);
define('MYSQL_PASS' ,           $db_pass);
define('MD5_MASTER_PASSWORD' ,  $md5_master_password);
define('PRODUCER_MARKDOWN' ,    $producer_markdown);
define('UNIVERSAL_MARGIN' ,     $universal_margin);
define('INSTITUTION_MARKUP' ,   $institution_markup);
define('ORGANIZATION_TYPE' ,    $organization_type);
define('REQ_PRDCR_CONFIRM' ,    $req_prdcr_confirm);
define('DELIVERY_NO_PAYPAL' ,   $delivery_no_paypal);
define('STATE_TAX' ,            $state_tax);
define('SHOW_HEADER_LOGO' ,     $show_header_logo);
define('SHOW_HEADER_SITENAME' , $show_header_sitename);
define('FAVICON' ,              $favicon);
define('USE_HTMLDOC' ,          $use_htmldoc);
define('DAYS_PER_CYCLE' ,       $days_per_cycle);
define('INSTITUTION_WINDOW' ,   $institution_window);
define('SHOW_ACTUAL_PRICE' ,    $show_actual_price);
define('PRDCR_INFO_PUBLIC' ,    $prdcr_info_public);
define('PRDCR_FORM_AVAIL' ,     $prdcr_form_available);
define('CALENDAR_URL' ,         $calendar_url);
define('CALENDAR_URL_HOME' ,    $calendar_url_homepage);
define('VOLUNTEER_ROTA_ENABLED',$volunteer_rota_enabled);
define('VOLUNTEER_ROTA_CAL_NAME',$volunteer_rota_calender);
define('VOLUNTEER_ROTA_MAX_VOLS',$volunteer_rota_max_volunteers);
define('HTMLDOC_PAGING' ,       $htmldoc_paging);
define('VALID_AUTH_TYPES' ,     $valid_auth_types);
define('NEW_PRODUCER_PENDING' , $new_producer_pending);
define('RANDOM_CALC' ,          $random_calc);
define('MEMBERSHIP_IS_TAXED' ,  $membership_taxed);
define('COOP_FEE_IS_TAXED' ,    $coop_fee_taxed);

//General page information
define('BASE_URL' ,           $site_url);
define('HOMEPAGE_URL' ,       $homepage_url);
define('PATH' ,               $food_coop_store_path);
define('FILE_PATH' ,          $file_path);
define('INVOICE_FILE_PATH' ,  $invoice_file_path);
define('INVOICE_WEB_PATH' ,   $invoice_web_path);
define('FUNC_FILE_PATH' ,     $func_file_path);
define('DOMAIN_NAME',         $domainname);
define('SITE_NAME' ,          $site_name);
define('SITE_NAME_SHORT' ,    $site_name_short);
define('ORG_TYPE' ,           $org_type);
define('SITE_CONTACT_INFO' ,  $site_contact_info);
define('SITE_MAILING_ADDR' ,  $site_mailing_address);

define('CURSYM' ,   $currency_symbol);
define('CURSYMT' ,  $currency_symbol_plain_text);

// Pages OUTSIDE of the FoodCoop application
define('TERMS_OF_SERVICE',        $page_terms_of_service); //to refer membership for terms of use standards
define('LOCATIONS_PAGE',          $page_locations);
define('COOP_PRODUCERS_PAGE',     $page_coopproducers);
define('DIR_GRAPHICS',            $site_graphics);
define('SELF',                    $_SERVER['PHP_SELF']);
define('PER_PAGE' ,               $default_results_per_page); //default number of search results per page
define('ROUTE_CODE_TEMPLATE',    $route_code_template);

// table names as variables
$table_auth_level       = 'authentication_levels';
$auth_table_name        = 'auth_users_c';
$table_cat              = 'categories';
$table_curdate          = 'current_delivery';
$table_basket           = 'customer_basket_items';
$table_basket_all       = 'customer_basket_overall';
$table_customer_adj     = 'customer_adjustments';
$table_customer_tax     = 'customer_salestax';
$table_delcode          = 'delivery_codes';
$table_deldate          = 'delivery_dates';
$table_deltypes         = 'delivery_types';
$table_how_heard        = 'how_heard';
$table_mem              = 'members';
$table_membership_types = 'membership_types';
$table_pay              = 'payment_method';
$table_prdcr            = 'producers';
$table_prdcr_logos      = 'producers_logos';
$table_prdcr_reg        = 'producers_registration';
$table_prodtype         = 'production_types';
$table_product_img      = 'product_images';
$table_products         = 'product_list';
$table_products_temp    = 'product_list_a';
$table_prep             = 'product_list_prep';
$table_previous         = 'product_list_previous';
$table_compound_products        = 'compound_product_list';
$table_compound_prep            = 'compound_product_list_prep';
$table_compound_previous        = 'compound_product_list_previous';
$table_origin                   = 'origin';
$table_brand                    = 'brand';
$table_product_store    = 'product_storage_types';
$table_rt               = 'routes';
$table_subcat           = 'subcategories';
$table_trans            = 'transactions';
$table_trans_type       = 'transactions_types';

// note: $table_prod is sometimes TABLE_PRODUCER_REG and sometimes TABLE_PRODUCT
// these are set in the other config files, as needed.

//Table aliases
define('TABLE_AUTH' ,                 $db_prefix.$auth_table_name);
define('TABLE_AUTH_LEVELS' ,          $db_prefix.$table_auth_level);
define('TABLE_BASKET' ,               $db_prefix.$table_basket);
define('TABLE_BASKET_ALL' ,           $db_prefix.$table_basket_all);
define('TABLE_CUSTOMER_ADJ' ,         $db_prefix.$table_customer_adj);
define('TABLE_CATEGORY' ,             $db_prefix.$table_cat);
define('TABLE_CURDEL' ,               $db_prefix.$table_curdate);
define('TABLE_CUSTOMER_SALESTAX' ,    $db_prefix.$table_customer_tax);
define('TABLE_DELCODE' ,              $db_prefix.$table_delcode);
define('TABLE_DELDATE' ,              $db_prefix.$table_deldate);
define('TABLE_DELTYPE' ,              $db_prefix.$table_deltypes);
define('TABLE_HOW_HEARD' ,            $db_prefix.$table_how_heard);
define('TABLE_MEMBER' ,               $db_prefix.$table_mem);
define('TABLE_MEMBERSHIP_TYPES' ,     $db_prefix.$table_membership_types);
define('TABLE_PAY' ,                  $db_prefix.$table_pay);
define('TABLE_PRODUCER' ,             $db_prefix.$table_prdcr);
define('TABLE_PRODUCER_LOGOS' ,       $db_prefix.$table_prdcr_logos);
define('TABLE_PRODUCER_REG' ,         $db_prefix.$table_prdcr_reg);
define('TABLE_PRODUCT' ,              $db_prefix.$table_products);
define('TABLE_PRODUCT_IMAGES' ,       $db_prefix.$table_product_img);
define('TABLE_PRODUCT_PREP' ,         $db_prefix.$table_prep);
define('TABLE_PRODUCT_TEMP' ,         $db_prefix.$table_products_temp);
define('TABLE_PRODUCT_PREV' ,         $db_prefix.$table_previous);
define('TABLE_COMPOUND_PRODUCT' ,       $db_prefix.$table_compound_products);
define('TABLE_COMPOUND_PRODUCT_PREP' ,  $db_prefix.$table_compound_prep);
define('TABLE_COMPOUND_PRODUCT_PREV' ,  $db_prefix.$table_compound_previous);
define('TABLE_ORIGIN' ,                 $db_prefix.$table_origin);
define('TABLE_BRAND' ,                  $db_prefix.$table_brand);
define('TABLE_PRODUCT_TYPES' ,        $db_prefix.$table_prodtype);
define('TABLE_PRODUCT_STORAGE_TYPES', $db_prefix.$table_product_store);
define('TABLE_ROUTE' ,                $db_prefix.$table_rt);
define('TABLE_SUBCATEGORY' ,          $db_prefix.$table_subcat);
define('TABLE_TRANS',                 $db_prefix.$table_trans);
define('TABLE_TRANSACTIONS' ,         $db_prefix.$table_trans);
define('TABLE_TRANS_TYPES' ,          $db_prefix.$table_trans_type);
define('TABLE_TTYPES',                $db_prefix.$table_trans_type);

//field aliases for Security.class
define('FIELD_USER' ,       'username_m');
define('FIELD_PASS' ,       'password');
define('FIELD_AUTH_TYPE' ,  'auth_type');

// contact e-mail addresses
define('CUSTOMER_EMAIL' ,       $email_customer);
define('GENERAL_EMAIL' ,        $email_general);
define('HELP_EMAIL' ,           $email_help);
define('MEMBERSHIP_EMAIL' ,     $email_membership);
define('ORDER_EMAIL' ,          $email_orders);
define('PAYPAL_EMAIL' ,         $email_paypal);
define('PRICELIST_EMAIL' ,      $email_pricelist);
define('PROBLEMS_EMAIL' ,       $email_problems);
define('PRODUCER_CARE_EMAIL' ,  $email_producer_care);
define('SOFTWARE_EMAIL' ,       $email_software);
define('STANDARDS_EMAIL' ,      $email_standards);
define('SUPPLY_EMAIL' ,         $email_supply);
define('TREASURER_EMAIL' ,      $email_treasurer);
define('VOLUNTEER_EMAIL' ,      $email_volunteer);
define('WEBMASTER_EMAIL' ,      $email_webmaster);

define('MEMBER_FORM_EMAIL' ,    $email_member_form);
define('PRODUCER_FORM_EMAIL' ,  $email_producer_form);

define('AUTHORIZED_PERSON' ,    $authorized_person);

$table_prod = TABLE_PRODUCER_REG;

$connection = @mysql_connect(HOST_NAME, MYSQL_USER, MYSQL_PASS) or die("Couldn't connect: \n".mysql_error());
$db = @mysql_select_db(DB_NAME, $connection) or die(mysql_error());
//die("Connected to DB");

// This function validates a login session and redirects the user to the login screen for unauthorized access
function validate_user() 
  {
    global $user_type;
    if ($user_type == 'valid_c' && ! $_SESSION['valid_c'])
      {
        header( "Location: show_login.php?call=".$_SERVER['REQUEST_URI']);
        exit;
      }
    elseif ($user_type == 'valid_m' && ! $_SESSION['valid_m'])
      {
        header( "Location: orders_login.php?call=".$_SERVER['REQUEST_URI']);
        exit;
      }
  }
?>
