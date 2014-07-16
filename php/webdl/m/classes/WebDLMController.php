<?php
/****
 * This class is the class where you ask for data from any DLM and
 * fetch data from any DLM
 **/
class WebDLMController {
    private static $dlms = array();
    private static $tree;
    private static $connectors;
    private static $initialized = false;

        
    private function __construct() {} // make construct private to prevent any objects of the controller.
    
    private static function initialize() {
        if (self::$initialized) return;
        $db = WebDLResourceManager::get("DB_MASTER_PDO");


        // Load every configured DLM
        $sql = "SELECT DLMID, ClassName FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLM";
        $sth = $db->prepare($sql);
        $sth->execute();

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            self::$dlms[$row['DLMID']] = new $row['ClassName']($row['DLMID']);
        }
                
        // Get a tree that contains all DLMs
        self::$tree = new WebDLMTree();

        // Get an array with all the connectors
        // Not sure how much I like how the array is put together.  Need to think
        // up a better way of doing this.
        self::$connectors = WebDLMConnector::get_connectors();
        
        self::$initialized = true;

    }
    
    public static function dlm_record($record) {
        return self::dlm_request($record->get_request());
    }
    
    public static function dlm_request($request) {
        self::initialize();
        
        // The result of the request.
        $result = new WebDLMResult();
        

        // All DLMs needed for this request
        $required_dlms = self::$tree->get_required_dlms($request);

        // There are special considerations that have not yet been figured out
        // for other types of requests.  See comments below.
        if ($request->get_type() == "GET") {
            
            //Are any connectors involved?
            $required_tables = self::$tree->get_required_tables($request);
            $needed_connectors = array();
            foreach ($required_tables as $t_id_p) {
                foreach ($required_tables as $t_id_f) {
                    if (isset(self::$connectors['BY_TABLE_TO_TABLE'][$t_id_p][$t_id_f]))
                        $needed_connectors[] = self::$connectors['BY_TABLE_TO_TABLE'][$t_id_p][$t_id_f];
                }
            }
            
            //Add any primary or foreign columns used by the connectors.
            //Make a lis of the DLMs that have connectors.
            $connector_dlms = array();
            foreach ($needed_connectors as $connectors) {
                foreach ($connectors as $connector) {
                    $connector_dlms[self::$tree->columns[$connector->c_id]->dlm_id] = self::$tree->columns[$connector->c_id]->dlm_id;
                    $connector_dlms[self::$tree->columns[$connector->c_id_f]->dlm_id] = self::$tree->columns[$connector->c_id_f]->dlm_id;
                    if (!$request->is_column_included($connector->c_id))
                        $request->push_column($connector->c_id);
                    if (!$request->is_column_included($connector->c_id_f))
                        $request->push_column($connector->c_id_f);
                }
            }
            
            
            //Run any dlms in which we are matching on fisrt.
            $connector_run = array();
            foreach (self::$tree->get_match_dlms($request) as $dlm_id) {
                $connector_run[] = $dlm_id;
            }
            //Put the DLMs in run order so we can filter out other data as we go.
            $have_all = false;
            while (!$have_all) {
                foreach ($needed_connectors as $connectors) {
                    foreach ($connectors as $connector) {
                        if (in_array(self::$tree->columns[$connector->c_id]->dlm_id, $connector_run)  && !in_array(self::$tree->columns[$connector->c_id_f]->dlm_id, $connector_run))
                            $connector_run[] = self::$tree->columns[$connector->c_id_f]->dlm_id;
                        if (in_array(self::$tree->columns[$connector->c_id_f]->dlm_id, $connector_run)  && !in_array(self::$tree->columns[$connector->c_id]->dlm_id, $connector_run))
                            $connector_run[] = self::$tree->columns[$connector->c_id]->dlm_id;
                    }
                }
                $have_all = true;
                foreach ($connector_dlms as $dlm_id) {
                    if (!in_array($dlm_id, $connector_run)) $have_all = false;
                }
            }

                        
            //Add any matches that need to link to other DLMs from our first results.
            foreach ($connector_run as $dlm_id) {
                if (isset($result->dlm_result[$dlm_id])) continue; // Don't rerun any dlm that has already been run
                $result = self::run_one_dlm($dlm_id, $request, $result);
                // Now look for keys that match connectors and add the matches to the request.
                foreach ($result->get_dlm_data($dlm_id) as $row) {
                    foreach ($needed_connectors as $connectors) {
                        foreach ($connectors as $connector) {
                            // Add the Primary Key links
                            if (isset($row[$connector->c_id])) {
                                foreach($connector->get_foreign_key($row[$connector->c_id]) as $val_f) {
                                    // Add each key as a match value so we get the needed rows when the other DLM is run.
                                    $request->push_match($connector->c_id_f, $val_f, 'OR');
                                    $result->push_column_link($connector->c_id_f, $val_f, $row);
                                }
                            }
                            // Add the Foreign Key Links
                            if (isset($row[$connector->c_id_f])) {
                                foreach($connector->get_primary_key($row[$connector->c_id_f]) as $val_p) {
                                    // Add each key as a match value so we get the needed rows when the other DLM is run.
                                    $request->push_match($connector->c_id, $val_p, 'OR');
                                    $result->push_column_link($connector->c_id, $val_p, $row);
                                }
                            }
                        }
                    }
                }
            }
                        
        } // END IF "GET"
        
        
        // Run any remaining DLMs
        foreach ($required_dlms as $dlm_id) {
            if (isset($result->dlm_result[$dlm_id])) continue; // Don't rerun any dlm that has already been run
            $result = self::run_one_dlm($dlm_id, $request, $result);
        }
        
        
        // Return the data
        return $result;
    }
    
    private static function run_one_dlm($dlm_id, $request, $result) {
        $type = strtolower($request->get_type());
        $is_online = self::$dlms[$dlm_id]->is_ready();
        if ($is_online) {
            $result->set_dlm_result($dlm_id, self::$dlms[$dlm_id]->$type($request));
        } else {
            $result->set_dlm_result($dlm_id, NULL, false);            
        }
        return $result;
    }
    
}
?>