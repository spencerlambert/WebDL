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
            $this->columns_by_dlm[$row['DLMID']][$row['DLMTreeColumnID']] = $class;
            
        }

    }
    
    /*******
     * $request_array:
     * Two dimensional array with following indexes
     * COLUMN_ID: An array of the column ids to be returned.
     * AND_MATCH: An array of column ids and matched to preform for fetching the row(s).
     * OR_MATCH: Like AND_MATCHES, but with the OR operator between each.
     * WILDCARD_MATCH: Uses the % char as a wildcard, working like the LIKE MySQL operator.
     **/
    public function get_dlm_data($request_array) {
        // Get a list of all the DLMs we need to make requests to.
        $required_dlms = array();
        if (isset($request_array['COLUMN_ID'])) {
            foreach ($request_array['COLUMN_ID'] as $id) {
                $required_dlms[$this->columns[$id]->dlm_id] = $this->columns[$id]->dlm_id;
            }
        } else {
            UserMessage::output('No COLUMN_ID in the $request_array', 'WebDLMController.php');
            return false;
        }
        if (isset($request_array['AND_MATCH'])) {
            foreach ($request_array['AND_MATCH'] as $id=>$val) {
                $required_dlms[$this->columns[$id]->dlm_id] = $this->columns[$id]->dlm_id;
            }
        }
        if (isset($request_array['OR_MATCH'])) {
            foreach ($request_array['OR_MATCH'] as $id=>$val) {
                $required_dlms[$this->columns[$id]->dlm_id] = $this->columns[$id]->dlm_id;
            }
        }
        if (isset($request_array['WILDCARD_MATCH'])) {
            foreach ($request_array['WILDCARD_MATCH'] as $id=>$val) {
                $required_dlms[$this->columns[$id]->dlm_id] = $this->columns[$id]->dlm_id;
            }
        }

        
        $data = array();
        foreach ($required_dlms as $id) {
            $data[$id] = array();
            $data[$id]['IS_ONLINE'] = $this->dlms[$id]->is_ready();
            $data[$id]['DATA'] = "";
            if ($data[$id]['IS_ONLINE'] === true) 
                $data[$id]['DATA'] = $this->dlms[$id]->get($request_array, $this->columns_by_dlm[$id]);
        }
        
        // dumping for testing...
        var_dump($data);
        
    }

    public function update_data($update_ids, $where_ids) {
        
    }
    
}
?>