<?php
class WebDLMConnector {
    protected $connector_id;
    protected $name;
    protected $dlm_column_primary;
    protected $dlm_column_foreign;
    
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
            $this->dlm_column_primary = $row['DLMTreeColumnIDPrimary']
            $this->dlm_column_foreign = $row['DLMTreeColumnIDForeign'];
            $this->type = $row['Type'];
            $this->key_type = $row['KeyType'];
            $this->sql_key_table = MASTER_DB_NAME_WITH_PREFIX."DLMConnector".$this->type.$this->key_type;
        } else {
            //No connector in DB
            UserMessage::output('No DLMConnector with the ID of '.$this->connector_id, 'WebDLMConnectorBase.php');
        }
    }
    
    public function get_primary_key_name() {
        return $this->primary_key_name;
    }

    public function get_foreign_key_name() {
        return $this->foreign_key_name;
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