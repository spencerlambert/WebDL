<?php
class WebDLMConnector {
    protected $connector_id;
    public $name;
    public $c_id;
    public $c_id_f;
    
    protected $type;
    protected $key_type;
    
    protected $sql_key_table;
        
    public function __construct($connector_id) {
        $db = ResourceManager::get("DB_MASTER_PDO");

        $this->connector_id = $connector_id;

        $sql = "SELECT * FROM ".MASTER_DB_NAME_WITH_PREFIX."DLMConnector WHERE ConnectorID=:id";
        $params = array(':id'=>$this->connector_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);

        $row = $sth->fetch(PDO::FETCH_ASSOC);
        if (isset($row['ConnectorID'])) {
            $this->name = $row['ConnectorName'];
            $this->c_id = $row['DLMTreeColumnIDPrimary']
            $this->c_id_f = $row['DLMTreeColumnIDForeign'];
            $this->type = $row['Type'];
            $this->key_type = $row['KeyType'];
            $this->sql_key_table = MASTER_DB_NAME_WITH_PREFIX."DLMConnector".$this->type.$this->key_type;
        } else {
            //No connector in DB
            UserMessage::output('No DLMConnector with the ID of '.$this->connector_id, 'WebDLMConnectorBase.php');
        }
    }
    
    // Creates an array of all connectors sorted in various ways for the DLM controller.
    static public function get_connectors($dlm_id) {        
        $db = ResourceManager::get("DB_MASTER_PDO");


        // TODO: Thinking about how to best proceed with the connectors.
        // TODO: This function still needs work and needs to be converted over from when it was in the WebDLMBase class
        $connectors = array();
        $connectors['BY_ID'] = array();
        $connectors['BY_TABLE_TO_TABLE'] = array();

        $sql = "SELECT
                    con.ConnectorID,
                    t.DLMID,
                    con.DLMTreeColumnIDPrimary
                FROM
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMConnector as con,
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn as t,
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable as c,
                WHERE
                    t.DLMTreeTableID=c.DLMTreeTableID AND
                    con.DLMTreeColumnIDPrimary=c.DLMTreeColumnID";
        $sth = $db->prepare($sql);
        $sth->execute();
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $connectors['BY_ID'][$row['ConnectorID']] = new WebDLMConnector($row['ConnectorID']);
            
            if (!isset($this->connectors_by_key[$this->connectors[$row['ConnectorID']]->get_primary_key_name]))
                $this->connectors_by_key[$this->connectors[$row['ConnectorID']]->get_primary_key_name] = array();
            $this->connectors_by_key[$this->connectors[$row['ConnectorID']]->get_primary_key_name][$row['ConnectorID']] = $this->connectors[$row['ConnectorID']];

            if (!isset($this->connectors_by_foreign_key[$this->connectors[$row['ConnectorID']]->get_foreign_key_name]))
                $this->connectors_by_foreign_key[$this->connectors[$row['ConnectorID']]->get_foreign_key_name] = array();
            $this->connectors_by_foreign_key[$this->connectors[$row['ConnectorID']]->get_foreign_key_name][$row['ConnectorID']] = $this->connectors[$row['ConnectorID']];

        }
        
        return $connectors;
        
    }
    
    
    public function update($key, $foreign_key) {
        $db = ResourceManager::get("DB_MASTER_PDO");

        switch ($this->type) {
            case "OneToOne":
                $sql = "INSERT INTO
                            ".$this->sql_key_table."
                        (
                            ConnectorID,
                            PrimaryKey,
                            ForeignKey
                        ) VALUES (
                            :id,
                            :p_key,
                            :f_key
                        ) ON DUPLICATE KEY UPDATE
                            ForeignKey=:f_key";
                break;
            case "OneToMany":
                $sql = "INSERT INTO
                            ".$this->sql_key_table."
                        (
                            ConnectorID,
                            PrimaryKey,
                            ForeignKey
                        ) VALUES (
                            :id,
                            :p_key,
                            :f_key
                        ) ON DUPLICATE KEY UPDATE
                            PrimaryKey=:p_key";
                break;
            case "ManyToMany":
                $sql = "INSERT INTO
                            ".$this->sql_key_table."
                        (
                            ConnectorID,
                            PrimaryKey,
                            ForeignKey
                        ) VALUES (
                            :id,
                            :p_key,
                            :f_key
                        )";
                break;
        }
        
        $params = array(':id'=>$this->connector_id, ':p_key'=>$key, ':f_key'=>$foreign_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
    }
    
    public function get_foreign_key($primary_key) {
        $key_set = array();
        $db = ResourceManager::get("DB_MASTER_PDO");
        $sql = "SELECT DISTINCT
                    ForeignKey
                FROM
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    PrimaryKey=:p_key";
        $params = array(':id'=>$this->connector_id, ':p_key'=>$primary_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC as $row)) {
            $key_set[] = $row['ForeignKey'];
        }
        
        return $key_set;
    }
    
    public function get_primary_key($foreign_key) {
        $key_set = array();
        $db = ResourceManager::get("DB_MASTER_PDO");
        $sql = "SELECT DISTINCT
                    PrimaryKey
                FROM
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    ForeignKey=:f_key";
        $params = array(':id'=>$this->connector_id, ':f_key'=>$foreign_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC as $row)) {
            $key_set[] = $row['PrimaryKey'];
        }
        
        return $key_set;        
    }
    
    public function remove_all($primary_key) {
        $db = ResourceManager::get("DB_MASTER_PDO");
        $sql = "DELETE FROM         
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    PrimaryKey=:p_key";
        $params = array(':id'=>$this->connector_id, ':p_key'=>$primary_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);                    
    }
    
    public function remove_all_foreign($foreign_key) {
        $db = ResourceManager::get("DB_MASTER_PDO");
        $sql = "DELETE FROM         
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    ForeignKey=:f_key";
        $params = array(':id'=>$this->connector_id, ':f_key'=>$foreign_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);                    
    }
    
    public function remove_one($key, $foreign_key) {
        $db = ResourceManager::get("DB_MASTER_PDO");
        $sql = "DELETE FROM         
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    PrimaryKey=:p_key AND
                    ForeignKey=:f_key";
        $params = array(':id'=>$this->connector_id, ':p_key'=>$key, ':f_key'=>$foreign_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);
    }
    
}
?>