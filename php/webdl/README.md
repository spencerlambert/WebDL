### classes folder

The use of folders named "classes" is used through out the WebDL code.  It's used for the class auto loader, which will
look for the class in any folder named "classes".  The file that contains a class must be named [CLASSNAME].php.  Example:
the WebDLMBase class is in the WebDLMBase.php file.

### controller folder

These are the controllers that take input from the URL/GET/POST, then call the correct parts of WebDL, and finailly return
the web page.  The two main controllers are called "app" and "ajax".  The "app" controller is the default controller and
handles drawing the main web pages.  The "ajax" controller is called using Javascript, and returns json data to be 
used on the main pages.

There are other controllers that will be created in the future like "soap", which will provide a "soap" structure for calling
the DLMs (Data Link Module).

To add a new controller, simple create a new folder under the controller folder.  The new controller class needs to be in a
"classes" folder and named "WebDLController[CONTROLLER NAME]".  It also needs to extend the "WebDLControllerBase" class.

### m folder

The "m" stands for module as in Data Link Module.  These are the set of classes used to communicate with, SOAP APIs, 
Databases, and any other things that keep data.  To communicate with a DLM, you'll use the WebDLMRequest class.  With it 
you can push columns to get and add matches to filter data.

### pb folder

"pb" is for Page Block.  These are standard ways to draw HTML on web pages.  They can be created and called to build full
working pages.

## setup folder

This contains the config.php, which you should configure to your specific setup, it also has the class auto loader and some
other setup stuff.

## template folder

Here is where you confiure the CSS, header, footer and the look of your web application.
