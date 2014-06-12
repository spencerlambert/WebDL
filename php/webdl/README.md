### classes folder

The use of folders named "classes" is used through out the WebDL code.  It's used for the class auto loader, which will
look for the class in any folder named "classes".  The file that contains a class must be named [CLASSNAME].php.  Example:
the WebDLMBase class is in the WebDLMBase.php file.

### controller folder

Currently there is just the RESTful controller, however the controller can easily be extending to new types, like SOAP.

To add a new controller, simple create a new folder under the controller folder.  The new controller class needs to be in a
"classes" folder and named "WebDLController[CONTROLLER NAME]".  It also needs to extend the "WebDLControllerBase" class.

### m folder

The "m" stands for module as in Data Link Module.  These are the set of classes used to communicate with, SOAP APIs, 
Databases, and any other things that keep data.  

### setup folder

This contains the config.php, which you must configure to your specific setup, it also has the class auto loader and some
other setup stuff.
