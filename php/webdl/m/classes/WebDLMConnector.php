<?php
class WebDLMConnector {
    public $con_id;
    public $name;
    public $c_id;
    public $c_id_f;
    
    protected $type;
    protected $key_type;
    
    protected $sql_key_table;
        
    public function __construct($con_id) {
        $db = WebDLResourceManager::get("DB_MASTER_PDO");

        $this->con_id = $con_id;

        $sql = "SELECT * FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMConnector WHERE ConnectorID=:id";
        $params = array(':id'=>$this->con_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);

        $row = $sth->fetch(PDO::FETCH_ASSOC);
        if (isset($row['ConnectorID'])) {
            $this->name = $row['ConnectorName'];
            $this->c_id = $row['DLMTreeColumnIDPrimary'];
            $this->c_id_f = $row['DLMTreeColumnIDForeign'];
            $this->type = $row['Type'];
            $this->key_type = $row['KeyType'];
            $this->sql_key_table = WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMConnector".$this->type.$this->key_type;
        } else {
            //No connector in DB
            WebDLUserMessage::output('No DLMConnector with the ID of '.$this->con_id, 'WebDLMConnectorBase.php');
        }
    }
    
    // Creates an array of all connectors sorted in various ways for the DLM controller.
    static public function get_connectors() {        
        $db = WebDLResourceManager::get("DB_MASTER_PDO");


        $connectors = array();
        $connectors['BY_ID'] = array();
        $connectors['BY_TABLE_TO_TABLE'] = array();
        $connectors['BY_COLUMN_TO_COLUMN'] = array();

        $sql = "SELECT
                    con.ConnectorID,
                    t.DLMID,
                    con.DLMTreeColumnIDPrimary as c_id_p,
                    con.DLMTreeColumnIDForeign as c_id_f,
                    t.DLMTreeTableID as t_id_p,
                    f.t_id_f as t_id_f    
                FROM
                    ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMConnector as con,
                    ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn as c,
                    ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable as t,
                    (
                        SELECT
                            con_f.ConnectorID as con_id_f,
                            t_f.DLMTreeTableID as t_id_f
                        FROM
                            ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMConnector as con_f,
                            ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn as c_f,
                            ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable as t_f
                        WHERE
                            t_f.DLMTreeTableID=c_f.DLMTreeTableID AND
                            con_f.DLMTreeColumnIDForeign=c_f.DLMTreeColumnID
                    ) as f
                WHERE
                    t.DLMTreeTableID=c.DLMTreeTableID AND
                    con.DLMTreeColumnIDPrimary=c.DLMTreeColumnID AND
                    con.ConnectorID=f.con_id_f";
        $sth = $db->prepare($sql);
        $sth->execute();
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $con_obj = new WebDLMConnector($row['ConnectorID']);
            $connectors['BY_ID'][$row['ConnectorID']] = $con_obj;
            
            if (!isset($connectors['BY_TABLE_TO_TABLE'][$row['t_id_p']])) $connectors['BY_TABLE_TO_TABLE'][$row['t_id_p']] = array();
            if (!isset($connectors['BY_TABLE_TO_TABLE'][$row['t_id_p']][$row['t_id_f']])) $connectors['BY_TABLE_TO_TABLE'][$row['t_id_p']][$row['t_id_f']] = array();
            $connectors['BY_TABLE_TO_TABLE'][$row['t_id_p']][$row['t_id_f']][$row['ConnectorID']] = $con_obj;
            
            if (!isset($connectors['BY_COLUMN_TO_COLUMN'][$row['c_id_p']])) $connectors['BY_COLUMN_TO_COLUMN'][$row['c_id_p']] = array();
            if (!isset($connectors['BY_COLUMN_TO_COLUMN'][$row['c_id_p']][$row['c_id_f']])) $connectors['BY_COLUMN_TO_COLUMN'][$row['c_id_p']][$row['c_id_f']] = array();
            $connectors['BY_COLUMN_TO_COLUMN'][$row['c_id_p']][$row['c_id_f']][$row['ConnectorID']] = $con_obj;
        }
        
        return $connectors;
        
    }
    
    
    public function update($key, $foreign_key) {
        
        // Special type where keys are not tracked        
        if ($this->key_type == 'IdenticalValues') return true;
        
        $db = WebDLResourceManager::get("DB_MASTER_PDO");

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
        
        $params = array(':id'=>$this->con_id, ':p_key'=>$key, ':f_key'=>$foreign_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        return true;
    }
    
    public function get_foreign_key($primary_key) {
        
        // Special type where Primary and Foreign Keys are the same
        if ($this->key_type == 'IdenticalValues') return array($primary_key);
        
        $key_set = array();
        $db = WebDLResourceManager::get("DB_MASTER_PDO");
        $sql = "SELECT DISTINCT
                    ForeignKey
                FROM
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    PrimaryKey=:p_key";
        $params = array(':id'=>$this->con_id, ':p_key'=>$primary_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key_set[] = $row['ForeignKey'];
        }
        
        return $key_set;
    }
    
    public function get_primary_key($foreign_key) {

        // Special type where Primary and Foreign Keys are the same
        if ($this->key_type == 'IdenticalValues') return array($foreign_key);

        $key_set = array();
        $db = WebDLResourceManager::get("DB_MASTER_PDO");
        $sql = "SELECT DISTINCT
                    PrimaryKey
                FROM
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    ForeignKey=:f_key";
        $params = array(':id'=>$this->con_id, ':f_key'=>$foreign_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key_set[] = $row['PrimaryKey'];
        }
        
        return $key_set;        
    }
    
    public function remove_all($primary_key) {

        // Special type where keys are not tracked
        if ($this->key_type == 'IdenticalValues') return true;

        $db = WebDLResourceManager::get("DB_MASTER_PDO");
        $sql = "DELETE FROM         
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    PrimaryKey=:p_key";
        $params = array(':id'=>$this->con_id, ':p_key'=>$primary_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);                    
    }
    
    public function remove_all_foreign($foreign_key) {

        // Special type where keys are not tracked
        if ($this->key_type == 'IdenticalValues') return true;

        $db = WebDLResourceManager::get("DB_MASTER_PDO");
        $sql = "DELETE FROM         
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    ForeignKey=:f_key";
        $params = array(':id'=>$this->con_id, ':f_key'=>$foreign_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);                    
    }
    
    public function remove_one($key, $foreign_key) {

        // Special type where keys are not tracked
        if ($this->key_type == 'IdenticalValues') return true;

        $db = WebDLResourceManager::get("DB_MASTER_PDO");
        $sql = "DELETE FROM         
                    ".$this->sql_key_table."
                WHERE
                    ConnectorID=:id AND
                    PrimaryKey=:p_key AND
                    ForeignKey=:f_key";
        $params = array(':id'=>$this->con_id, ':p_key'=>$key, ':f_key'=>$foreign_key);
        $sth = $db->prepare($sql);
        $sth->execute($params);
    }
    
}
?>