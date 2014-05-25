<?php
/**
 * All DLMs need to extend this class
 */
class WebDLMBase () {
    protected $install_path;
    protected $dlm_id;
    protected $config_list = array();
    protected $config = array();
    protected $connectors = array();
    protected $connectors_by_key = array();
    protected $connectors_by_foreign_key = array();
    
    protected __construct($dlm_id) {
        $db = ResourceManager::get("DB_MASTER_PDO");
        $this->install_path = ABSPATH.'dlm/'.get_class($this).'/';
        $this->dlm_id = $dlm_id;
        
        $sql = "SELECT * FROM ".MASTER_DB_NAME_WITH_PREFIX."DLMConfig WHERE DLMID=:id";
        $params = array(':id'=>$this->dlm_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->config[$row['Name']] = $row['Value'];
        }

        $sql = "SELECT
                    con.ConnectorID
                FROM
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMConnector as con,
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn as t,
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable as c,
                WHERE
                    t.DLMID=:id AND
                    t.DLMTreeTableID=c.DLMTreeTableID AND
                    con.DLMTreeColumnIDPrimary=c.DLMTreeColumnID";
        $params = array(':id'=>$this->dlm_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->connectors[$row['ConnectorID']] = new WebDLMConnector($row['ConnectorID']);
            
            if (!isset($this->connectors_by_key[$this->connectors[$row['ConnectorID']]->get_primary_key_name]))
                $this->connectors_by_key[$this->connectors[$row['ConnectorID']]->get_primary_key_name] = array();
            $this->connectors_by_key[$this->connectors[$row['ConnectorID']]->get_primary_key_name][$row['ConnectorID']] = $this->connectors[$row['ConnectorID']];

            if (!isset($this->connectors_by_foreign_key[$this->connectors[$row['ConnectorID']]->get_foreign_key_name]))
                $this->connectors_by_foreign_key[$this->connectors[$row['ConnectorID']]->get_foreign_key_name] = array();
            $this->connectors_by_foreign_key[$this->connectors[$row['ConnectorID']]->get_foreign_key_name][$row['ConnectorID']] = $this->connectors[$row['ConnectorID']];

        }
        
        foreach ($this->config_list as $name) {
            if (!isset($this->config[$name])) {
                AppMessage::output('Config item '.$name.' is missing for DLM '.$this->dlm_id.', things will break!');
            }
        }
        
    }
    
    // This function is called by the DLM Controller, prior to fetching any data.
    // If the connection is good to the data source, the it needs to return TRUE.
    abstract public function is_ready();
    abstract public function fetch_data($col_ids, $where_ids, $tree);
    
    

    
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