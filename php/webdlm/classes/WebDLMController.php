<?php
/****
 * This class is the class where you ask for data from any DLM and
 * fetch data from any DLM
 **/
class WebDLMController {
    private $dlms = array();
    private $tree;
        
    public function __construct() {
        $db = ResourceManager::get("DB_MASTER_PDO");


        // Load every configured DLM
        $sql = "SELECT DLMID, ClassName FROM ".MASTER_DB_NAME_WITH_PREFIX."DLM";
        $sth = $db->prepare($sql);
        $sth->execute();

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->dlms[$row['DLMID']] = new $row['ClassName']($row['DLMID']);
        }
                
        // Get a tree that contains all DLMs
        $this->tree = new WebDLMTree();

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
        $required_dlms = $this->tree->get_required_dlms($request_array);
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
    
}
?>