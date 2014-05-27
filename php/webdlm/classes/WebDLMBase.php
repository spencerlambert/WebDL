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
        $db = ResourceManager::get("DB_MASTER_PDO");
        $this->install_path = ABSPATH.'webdlm/'.get_class($this).'/';
        $this->dlm_id = $dlm_id;
        
        // Load all the config values
        $sql = "SELECT * FROM ".MASTER_DB_NAME_WITH_PREFIX."DLMConfig WHERE DLMID=:id";
        $params = array(':id'=>$this->dlm_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->config[$row['Name']] = $row['Value'];
        }        
        
        // See if any configs are missing.
        foreach ($this->config_list as $name) {
            if (!isset($this->config[$name])) {
                AppMessage::output('Config item '.$name.' is missing for DLM '.$this->dlm_id.', things will break!');
            }
        }
        
        $this->tree = new WebDLMTree($this->dlm_id);
        
    }

    /*******
     * $request_array:
     * Two dimensional array with following indexes
     * COLUMN_ID: An array of the column ids to be returned.
     * AND_MATCH: An array of column ids and matched to preform for fetching the row(s).
     * OR_MATCH: Like AND_MATCHES, but with the OR operator between each.
     * WILDCARD_MATCH: Uses the % char as a wildcard, working like the LIKE MySQL operator.
     **/    
    // This function is called by the DLM Controller, prior to fetching any data.
    // If the connection is good to the data source, the it needs to return TRUE.
    abstract public function is_ready();
    abstract public function get($request);  // Retrives data
    abstract public function post($request); // Both adds and updates data
    abstract public function delete($request); // Removed data

    

    
    public function get_dlm_id() {
        return $this->dlm_id;
    }

    public function get_dlm_name() {
        $db = ResourceManager::get("DB_MASTER_PDO");

        $sql = "SELECT * FROM ".MASTER_DB_NAME_WITH_PREFIX."DLM WHERE DLMID=:id";
        $params = array(':id'=>$this->dlm_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        return $row['Name'];
    }

}