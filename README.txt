-----------------------------------------------------
README.txt for Community Food Hub web-ordering system
-----------------------------------------------------

Requirements
------------

MySQL 5.5+
PHP 5 (up to version 5.3, not compatible with later versions)

N.B. Community Food Hub relies on register_globals being on, so will NOT work with PHP 5.4 onwards.  See http://www.php.net/manual/en/ini.core.php#ini.register-globals for more information.


Local Installation
------------------

You may wish to install the Community Food Hub locally on a home computer to try the software out and customise it before installing on a web server.  If so, download and install XAMPP 1.7.7 (last version with PHP 5.3) (http://www.apachefriends.org/en/xampp.html).  Once the installation is complete, start Apache, MySQL and Mercury Mail services.  Some helpful instructions for configuring MercuryMail for local use can be found here: https://www.zoe.vc/2008/mercury-mail-transport-system-fur-externe-mail-konfigurieren/


Installation Instructions
-------------------------

1) Obtain the latest version of the Community Food Hub software

This can be downloaded here: http://sourceforge.net/projects/foodhub.  Extract the zip file to a temporary folder on your computer.


2) Copy or FTP the PHP source files to your web server's websites folder (e.g. htdocs or public_html)

Note that the "foodcoop" PHP source folder is set to work in:

C:\xampp\htdocs\

To place it elsewhere, edit the foodcoop\shop\.htaccess or foodcoop\shop\php.ini files as approriate (these can be found in most of the website's subfolders), and update:

foodcoop\local_food_include\config_foodcoop.php

with the correct paths and database access information for your website.


3) Install the database

Go to phpMyAdmin via the website's control panel (XAMPP - https://localhost/xampp/index.php).  Create a new database called "food_coop_db" and import the "food_coop_db.sql" file.

This version of the database contains two anonymous users:

Email: admin@admin.com, Password: password
Email: producer@producer.com, Password: password

The email addresses and passwords can be changed by logging in to the website (/foodcoop/shop/index.php) and clicking "Update Contact Details".

An example product list is included for the anonymous producer.

Only the anonymous admin can log in to the admin area of the website (/foodcoop/shop/admin/index.php), where new order cycles can be configured, new collection locations added, etc..  An example order cycle and example collection locations are included in the database.


4) Check the shop homepage

Go to /foodcoop/shop (e.g. http://localhost/foodcoop/shop) in your web browser.  You should see the shop homepage.


5) Log in to admin area and start an ordering cycle

Go to /foodcoop/shop/admin (e.g. http://localhost/foodcoop/shop/admin) in your web browser.  You should see the admin area log in page.

Enter the admin username and password - username: admin@admin.com, password: password

Click on "Start/Update Cycle" under "Before next cycle opens" on the right-hand side.

Fill in the form following the instructions and click the "Start New Cycle" button at the bottom.


6) Create a new user and start an order

Go to http://localhost/foodcoop/shop in your web browser.  You should see the shop homepage.

Click on "Register for account" to the top left.

Fill in the form and click the "Submit" button.

If your local web server is set up correctly, you will receive a welcome email.

Log in from the shop homepage using the email address and password you entered above (note that you can do this even if you have not received the welcome email).

Select a collection location, add products to your basket, and go to the checkout.  When you submit the order, you should be able to view an invoice, which will also be emailed to you.  Check that the invoice is correct.  The order will also show up in the admin area.


7) You can also log in as a producer

Go to /foodcoop/shop (e.g. http://localhost/foodcoop/shop) in your web browser.

Enter the producer username and password - username: producer@producer.com, password: password

To edit products, go to "[Listed Retail]" under "Edit My Products".

N.B. New or changed products are not immediately available to customers.  To do this, the new product list must be published.  This can only be done by the admin - log as the admin using the shop homepage (NOT the admin area this time), and click "Make Product Changes LIVE!" in the bottom right corner.

Note that the admin can also edit products - for any producer - by logging in through the shop homepage and selecting the "Add/Edit Products by Producer" option.


Troubleshooting
---------------

1) Problems importing the database SQL file (food_coop_db.sql) into MySQL.

You may need to change the values of upload_max_filesize, memory_limit and post_max_size in your php.ini file.

(Run phpinfo() from your Control Panel to see the location of your php.ini file, for example C:\xampp\php\php.ini.)

See here for more information: https://phpmyadmin.readthedocs.org/en/latest/faq.html#i-cannot-upload-big-dump-files-memory-http-or-timeout-problems


2) You may see some "Notice" or "Deprecated" messages when viewing Community Food Hub webpages in your web browser if you are viewing a local installation of the website (on a remote webserver such messages will normally be hidden by default).

Ideally someone should fix these, but if you want to hide them for the time being edit your php.ini and set:

error_reporting = E_ALL & ~(E_NOTICE | E_DEPRECATED)


Ian Henderson
22:44 19/05/2013
