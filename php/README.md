### webdl folder

The webdl folder contains all the classes used for a WebDL project.  It's designed in a single folder to 
allow for installation into exsisting PHP projects.

Here is an example for calling a pb (Page Block) within a PHP project.

<?php
    // Tell WebDL where it's been installed.
    define('WEBDL_ABSPATH', dirname(__FILE__).'/');
    // Call the init.php file.
    require_once(WEBDL_ABSPATH.'/webdl/setup/init.php');

    // After those two items any WebDL class can be called.

    // Create a Table Page Block with the name "my_table"
    $table = new WebDLPBFromRequestTable('my_table');
    // add some coluumns 
    $table->push_column('account_name', 'Name');
    $table->push_column('account_phone', 'Phone Number');
    // Tell the Page Block you are finished
    $table->finish();

    // display the table.
    echo $table->get_html();
?>


### .htaccess

This is a sample htaccess file that will rewrite the URL and pass it to the webdl.php file.  Use this option if building a
new WebDL project.

### webdl.php

This file sets up the WebDL project and loads the requeted controller.  It then calls the load_page() function
of the controller.  The URL is parsed to figure out the controller type and the requested page.

Example 1:

`http://myproject.com/app/mypage/`

In this example "app" is the controller name, while "mypage" is the page that will be displayed.

Example 2:

`http://myproject.com/app/account/view/?account_id=123`

Again, "app" is the controller, while "/account/view/" is the page, and it will load account_id 123.  Note: "/account/view/"
is not the location of the page, but the name associated with that page.

Example 3:

`http://myproject.com/webdl.php/ajax/WebDLPBFromRequest/return_ajax/`

This is an Ajax call back request, the webdl.php is being called directly, rather than relying on the redirect.  This is 
a design choice to allow for better compatibility when a .htaccess redirect is not being used. The controller this time is 
"ajax" and the rest of the URL specifies the call back class name, WebDLPBFromRequest, and the function, return_ajax. 


