### Adding a new DLM

To create your own DLM, add a new folder using the same name as your DLM class.  Your new class must extend the WebDLMBase class or another DLM, like the WebDLMSoap class.  There are four functions that need to be implemented by your new DLM.

```php
    /*******
     * These are the functions that every WebDLM must implement.
     *
     * is_ready() is called by the WebDLMController prior to sending a request.
     * It needs to return true or false.
     *
     * get() is called to retrieve data from the WebDLM, for a database connection
     * this is a SELECT call.
     *
     * post() is for both adds and updates.  The WebDLM implementation is
     * responsible for figuring out which one it needs to do.  Works like a
     * database INSERT and UPDATE. The DLM may return false if adds or updates are
     * not allowed.
     *
     * delete() will delete a record from the data source.  The DLM can return false
     * if the delete feature is not implmented or allowed.
     **/    
    abstract public function is_ready();
    abstract public function get($request);  // Retrives data
    abstract public function post($request); // Both adds and updates data
    abstract public function delete($request); // Removed data
```
