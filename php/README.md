### webdl folder

The webdl folder contains all the classes used for a WebDL project.  It's designed in a single folder to 
allow for installation into existing PHP projects, or work as a standalone RESTful service.

### .htaccess

This is a sample htaccess file that will rewrite the URL and pass it to the webdl.php file.  Use this option if building a
new WebDL project.

### webdl.php

This file sets up the WebDL project and loads the requeted controller, by default the RESTful controller.  It then calls the load() function of the controller.  The URL is parsed to figure out the rest of the requested page.
