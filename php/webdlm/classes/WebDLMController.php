<?php
/****
 * This class is the class where you ask for data from any DLM and
 * fetch data from any DLM
 **/
class WebDLMController {
    private $dlms = array();
    private $keys = array();
    private $columns = array();
    private $columns_by_dlm = array();
        
    public function __construct() {
        $db = ResourceManager::get("DB_MASTER_PDO");


        // Load every configured DLM
        $sql = "SELECT DLMID, ClassName FROM ".MASTER_DB_NAME_WITH_PREFIX."DLM";
        $sth = $db->prepare($sql);
        $sth->execute();

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->dlms[$row['DLMID']] = new $row['ClassName']($row['DLMID']);
        }
        
        // Match all keys to a DLM
        $sql = "SELECT
                    DLMTreeColumnID,
                FROM
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c
                WHERE
                    c.IsKey = 'Yes'";
        $sth = $db->prepare($sql);
        $sth->execute();

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->keys[$row['DLMTreeColumnID']] = $row['DLMTreeColumnID'];
        }
        

        // Match all columns to a DLM
        $sql = "SELECT        
                    c.DLMTreeColumnID,
                    c.DLMTreeTableID,
                    t.DLMID,
                    t.Name as TName,
                    c.Name as CName,
                    c.Type,
                    c.IsKey
                FROM
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t,
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c
                WHERE
                    t.DLMTreeTableID=c.DLMTreeTableID";
        $sth = $db->prepare($sql);
        $sth->execute();

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $class = new stdClass();
            $class->c_id = $row['DLMTreeColumnID'];
            $class->t_id = $row['DLMTreeTableID'];
            $class->dlm_id = $row['DLMID'];
            $class->c_name = $row['CName'];
            $class->t_name = $row['TName'];
            $class->type = $row['Type'];
            $class->is_key = $row['IsKey'];
            $this->columns[$row['DLMTreeColumnID']] = $class;
            if (!isset($this->columns_by_dlm[$row['DLMID']]))
                $this->columns_by_dlm[$row['DLMID']] = array();
            $this->columns_by_dlm[$row['DLMID']][] = $class;
            
        }

    }
    
    public function fetch_data($return_ids, $where_ids) {
        $required_dlms = array();
        foreach ($return_ids as $id) {
            $required_dlms[$this->columns[$id]->dlm_id] = $this->columns[$id]->dlm_id;
        }
        foreach ($where_ids as $id=>$val) {
            $required_dlms[$this->columns[$id]->dlm_id] = $this->columns[$id]->dlm_id;
        }
        
        $data = array();
        foreach ($required_dlms as $id) {
            $data[$id] = array();
            $data[$id]['IS_ONLINE'] = $this->dlms[$id]->is_ready();
            $data[$id]['DATA'] = "";
            if ($data[$id]['IS_ONLINE'] === true) 
                $data[$id]['DATA'] = $this->dlms[$id]->fetch_data($return_ids, $where_ids, $this->columns_by_dlm[$id]);
        }
        
        // dumping for testing...
        var_dump($data);
        
    }

    public function update_data($update_ids, $where_ids) {
        
    }
    
}
?>