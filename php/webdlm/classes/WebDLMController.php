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
    
    public function dlm_request($request) {
        $type = strtolower($request->get_type());
        $required_dlms = $this->tree->get_required_dlms($request);
        if ($required_dlms === false) return false;

        $data = array();
        foreach ($required_dlms as $id) {
            $data[$id] = array();
            $data[$id]['IS_ONLINE'] = $this->dlms[$id]->is_ready();
            $data[$id]['DATA'] = "";
            if ($data[$id]['IS_ONLINE'] === true) {
                $data[$id]['DATA'] = $this->dlms[$id]->$type($request);
            }
        }
        
        // dumping for testing...
        var_dump($data);
        
    }
    
}
?>