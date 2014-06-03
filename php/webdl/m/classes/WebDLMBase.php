<?php
/**
 * All DLMs need to extend this class
 */
abstract class WebDLMBase {
    protected $install_path;
    protected $dlm_id;
    protected $config_list = array();
    protected $config = array();
    protected $tree;
    
    protected function __construct($dlm_id) {
        $db = WebDLResourceManager::get("DB_MASTER_PDO");
        $this->install_path = WEBDL_ABSPATH.'webdl/m/'.get_class($this).'/';
        $this->dlm_id = $dlm_id;
        
        // Load all the config values
        $sql = "SELECT * FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMConfig WHERE DLMID=:id";
        $params = array(':id'=>$this->dlm_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->config[$row['Name']] = $row['Value'];
        }        
        
        // See if any configs are missing.
        foreach ($this->config_list as $name) {
            if (!isset($this->config[$name])) {
                WebDLUserMessage::output('Config item '.$name.' is missing for DLM '.$this->dlm_id.', things will break!', 'WebDLMBase.php');
            }
        }
        
        // Get the data tree for this DLM.  The data tree helps the DLM figure out
        // how to join data with other tables.
        $this->tree = new WebDLMTree($this->dlm_id);
        
    }

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

    

    
    public function get_dlm_id() {
        return $this->dlm_id;
    }

    public function get_dlm_name() {
        $db = ResourceManager::get("DB_MASTER_PDO");

        $sql = "SELECT * FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLM WHERE DLMID=:id";
        $params = array(':id'=>$this->dlm_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        return $row['Name'];
    }

}