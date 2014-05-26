<?php
/****
 * This class is the class where you ask for data from any DLM and
 * fetch data from any DLM
 **/
class WebDLMController {
    private $dlms = array();
    private $keys = array();
    private $columns = array();
        
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
        
        $this->columns = WebDLMColumn::get_columns();

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
        $required_dlms = $this->get_required_dlms($request_array);
        if ($required_dlms === false) return false;
        
        $data = array();
        foreach ($required_dlms as $id) {
            $data[$id] = array();
            $data[$id]['IS_ONLINE'] = $this->dlms[$id]->is_ready();
            $data[$id]['DATA'] = "";
            if ($data[$id]['IS_ONLINE'] === true) 
                $data[$id]['DATA'] = $this->dlms[$id]->get($request_array);
        }
        
        // dumping for testing...
        var_dump($data);
        
    }
    
    public function post_dlm_data($request_array) {
        $required_dlms = $this->get_required_dlms($request_array);
        if ($required_dlms === false) return false;

    }    

    public function delete_dlm_data($request_array) {
        $required_dlms = $this->get_required_dlms($request_array);
        if ($required_dlms === false) return false;

    }    

    protected function get_required_dlms($request_array) {
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
        
        return $required_dlms;
    }
    
}
?>