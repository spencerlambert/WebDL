<?php
/****
 * This class is the class where you ask for data from any DLM and
 * fetch data from any DLM
 **/
class WebDLMController {
    private $dlms = array();
    private $tree;
    private $connectors;
        
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

        // Get an array with all the connectors
        // Not sure how much I like how the array is put together.  Need to think
        // up a better way of doing this.
        $this->connectors = WebDLMConnector::get_connectors();

    }
    
    public function dlm_request($request) {
        
        // An array to keep all the data from the DLM requests.
        $data = array();

        // There are special considerations that have not yet been figured out
        // for other types of requests.  See comments below.
        if ($request->get_type() == "GET") {
            
            //Are any connectors involved?
            $required_tables = $this->tree->get_required_tables($request);
            $needed_connectors = array();
            foreach ($required_tables as $t_id_p) {
                foreach ($required_tables as $t_id_f) {
                    if (isset($this->connectors['BY_TABLE_TO_TABLE'][$t_id_p][$t_id_f]))
                        $needed_connectors[] = $this->connectors['BY_TABLE_TO_TABLE'][$t_id_p][$t_id_f];
                }
            }
            
            //Run any DLMs that have a primary key connectot table in the match first.
            $match_tables = $this->tree->get_match_tables($request);
            $first_dlms = array();
            foreach ($needed_connectors as $connector) {
                if (in_array($this->tree->columns[$connector->c_id]->t_id, $match_tables) {
                    $first_dlms[$this->tree->columns[$connector->c_id]->dlm_id] = $this->tree->columns[$connector->c_id]->dlm_id
                    // DANGER! If this was a POST it could wipe out all the data in a table, because of the
                    // empty value being used in push_column().  Need to rethink for POST connects.
                    if (!$request->is_column_included($connector->c_id)
                        $request->push_column($connector->c_id);
                    if (!$request->is_column_included($connector->c_id_f)
                        $request->push_column($connector->c_id_f);
                }
            }
            foreach ($first_dlms as $dlm_id) {
                if (isset($data[$dlm_id])) continue; // Don't rerun any dlm that has already been run
                $data[$dlm_id] = $this->run_one_dlm($dlm_id, $request);
                // Load any needed matches for connected table,
                foreach ($data[$dlm_id]['DATA'] as $row) {
                    foreach ($needed_connectors as $connector) {
                        if (isset($row[$connector->c_id])) {
                            foreach($connector->get_foreign_key($row[$connector->c_id]) as $val_f) {
                                // Add each key as a match value so we get the needed rows when
                                // the other DLM is run.
                                $request->push_match($connector->c_id_f, $val_f);
                            }
                        }
                    }
                }
            }
        }
        
        
        // Run request on all remaining required DLMs
        $required_dlms = $this->tree->get_required_dlms($request);
        if ($required_dlms === false) return false;

        $data = array();
        foreach ($required_dlms as $dlm_id) {
            if (isset($data[$dlm_id])) continue; // Don't rerun any dlm that has already been run
            $data[$dlm_id] = $this->run_one_dlm($dlm_id, $request);
        }
        
        // dumping for testing...
        var_dump($data);
        
    }
    
    private function run_one_dlm($dlm_id, $request) {
        $type = strtolower($request->get_type());
        $data = array();
        $data['IS_ONLINE'] = $this->dlms[$dlm_id]->is_ready();
        $data['DATA'] = "";
        if ($data['IS_ONLINE'] === true) {
            $data['DATA'] = $this->dlms[$dlm_id]->$type($request);
        }
        return $data;
    }
    
}
?>