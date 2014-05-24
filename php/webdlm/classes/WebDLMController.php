<?php
/****
 * This class is the class where you ask for data from any DLM and
 * fetch data from any DLM
 **/
class WebDLMController {
    private $dlms = array();
    private $key_to_dlmid = array();
    private $column_to_dlmid = array();
        
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
                    DLMID
                FROM
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t,
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c
                WHERE
                    t.DLMTreeTableID=c.DLMTreeTableID AND
                    c.isKey = 'Yes'";
        $sth = $db->prepare($sql);
        $sth->execute();

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->key_to_dlmid[$row['DLMTreeColumnID']] = $row['DLMID'];
        }
        

        // Match all columns to a DLM
        $sql = "SELECT
                    DLMTreeColumnID,
                    DLMID
                FROM
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t,
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c
                WHERE
                    t.DLMTreeTableID=c.DLMTreeTableID";
        $sth = $db->prepare($sql);
        $sth->execute();

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->column_to_dlmid[$row['DLMTreeColumnID']] = $row['DLMID'];
        }

    }
    
    public function fetch_data($col_ids, $matches) {
        
    }

    public function update_data($col_vals, $matches) {
        
    }
    
}
?>