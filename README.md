WebDL
=====

WebDL (Web Data Link) - Work with disparate data sources, like API's, Databases, and Files, as if they were one source.

## Example Usage - RESTful

After installing the PHP and setting up the WebDL database, start accessing records using the following format.

```
http://webdl.domain.com/rest/account/id/1
```

`rest` tells the url parser that you are accessing the RESTful interface.  Technically the `rest` can be removed, as it's the default interface.

`account` specifies the record type you want returned.

`id` specifies the record column to filter on.

`1` gives the value to match the specified column by.

## Example Usage - Inline PHP

The following is an example for retrieving a record from within a PHP application.

```php
<?php
  // Tell WebDL where it has been installed in relation to this file.
  define('WEBDL_ABSPATH', dirname(__FILE__).'/');
  // Initialize WebDL.
  require_once(WEBDL_ABSPATH.'webdl/setup/init.php');

  // Create a record object of type account.
  $record = new WebDLMRecord('account');
  // Specify what to match on.
  $record->push_match('id', 1);

  // Process the record with the WebDLMController
  // The controller figures out what data sources need to be called, calls each, and joins the data, returning the result.
  $result = WebDLMController::dlm_record($record);
  
  // Echo the data
  echo $result->get_joined_data();
?>
```
