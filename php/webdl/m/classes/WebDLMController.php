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
        $db = WebDLResourceManager::get("DB_MASTER_PDO");


        // Load every configured DLM
        $sql = "SELECT DLMID, ClassName FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLM";
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
        $row_links = array();

        // All DLMs needed for this request
        $required_dlms = $this->tree->get_required_dlms($request);

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
            
            //Add any primary or foreign columns used by the connectors.
            //Make a lis of the DLMs that have connectors.
            $connector_dlms = array();
            foreach ($needed_connectors as $connectors) {
                foreach ($connectors as $connector) {
                    $connector_dlms[$this->tree->columns[$connector->c_id]->dlm_id] = $this->tree->columns[$connector->c_id]->dlm_id;
                    $connector_dlms[$this->tree->columns[$connector->c_id_f]->dlm_id] = $this->tree->columns[$connector->c_id_f]->dlm_id;
                    if (!$request->is_column_included($connector->c_id))
                        $request->push_column($connector->c_id);
                    if (!$request->is_column_included($connector->c_id_f))
                        $request->push_column($connector->c_id_f);
                }
            }
            
            
            //Run any dlms in which we are matching on fisrt.
            $connector_run = array();
            foreach ($this->tree->get_match_dlms($request) as $dlm_id) {
                $connector_run[] = $dlm_id;
            }
            //Put the DLMs in run order so we can filter out other data as we go.
            $have_all = false;
            while (!$have_all) {
                foreach ($needed_connectors as $connectors) {
                    foreach ($connectors as $connector) {
                        if (in_array($this->tree->columns[$connector->c_id]->dlm_id, $connector_run)  && !in_array($this->tree->columns[$connector->c_id_f]->dlm_id, $connector_run))
                            $connector_run[] = $this->tree->columns[$connector->c_id_f]->dlm_id;
                        if (in_array($this->tree->columns[$connector->c_id_f]->dlm_id, $connector_run)  && !in_array($this->tree->columns[$connector->c_id]->dlm_id, $connector_run))
                            $connector_run[] = $this->tree->columns[$connector->c_id]->dlm_id;
                    }
                }
                $have_all = true;
                foreach ($connector_dlms as $dlm_id) {
                    if (!in_array($dlm_id, $connector_run)) $have_all = false;
                }
            }

                        
            //Add any matches that need to link to other DLMs from our first results.
            foreach ($connector_run as $dlm_id) {
                if (isset($data[$dlm_id])) continue; // Don't rerun any dlm that has already been run
                $data[$dlm_id] = $this->run_one_dlm($dlm_id, $request);
                // Now look for keys that match connectors and add the matches to the request.
                foreach ($data[$dlm_id]['DATA'] as $row) {
                    foreach ($needed_connectors as $connectors) {
                        foreach ($connectors as $connector) {
                            // Add the Primary Key links
                            if (isset($row[$connector->c_id])) {
                                foreach($connector->get_foreign_key($row[$connector->c_id]) as $val_f) {
                                    // Add each key as a match value so we get the needed rows when the other DLM is run.
                                    $request->push_match($connector->c_id_f, $val_f, 'OR');
                                    if (!isset($row_links[$connector->c_id_f."-".$val_f]))
                                        $row_links[$connector->c_id_f."-".$val_f] = array();
                                    $row_links[$connector->c_id_f."-".$val_f][] = $row;
                                }
                            }
                            // Add the Foreign Key Links
                            if (isset($row[$connector->c_id_f])) {
                                foreach($connector->get_primary_key($row[$connector->c_id_f]) as $val_p) {
                                    // Add each key as a match value so we get the needed rows when the other DLM is run.
                                    $request->push_match($connector->c_id, $val_p, 'OR');
                                    if (!isset($row_links[$connector->c_id."-".$val_p]))
                                        $row_links[$connector->c_id."-".$val_p] = array();
                                    $row_links[$connector->c_id."-".$val_p][] = $row;
                                }
                            }
                        }
                    }
                }
            }
                        
        } // END IF "GET"
        
        
        // Run any remaining DLMs
        foreach ($required_dlms as $dlm_id) {
            if (isset($data[$dlm_id])) continue; // Don't rerun any dlm that has already been run
            $data[$dlm_id] = $this->run_one_dlm($dlm_id, $request);
        }
        
        // Join the data if needed.
        // TODO: This is really not working...  Need to get it right.
        // TODO: This will only join two DLMs, need to add logic for joining more together.
        $join = array();
        if (count($row_links) != 0) { 
            foreach ($required_dlms as $dlm_id) {
                foreach ($data[$dlm_id]['DATA'] as $row) {
                    foreach ($row as $c_id=>$c_val) {
                        $name = $c_id."-".$c_val;
                        if (isset($row_links[$name])) {
                            if (!isset($join[$dlm_id]))
                                $join[$dlm_id] = array();
                            foreach ($row_links[$name] as $id=>$link) {
                                $join[$dlm_id][] = array_merge($row, $link);                            
                            }
                        }
                    }
                }
            }
        }// end if count()
        
        // Merge the rows that match the connector values.
        $merge = array();
        foreach ($join as $dlm_id=>$m_ary) {
            foreach ($join as $dlm_id_f=>$m_ary_f) {
                if ($dlm_id == $dlm_id_f) continue;
                foreach ($m_ary as $row) {
                    foreach($m_ary_f as $row_f) {
                        //if all the rows in one array match the other, then merge the extra data.
                        $all_match = true;
                        foreach ($row as $col=>$val) {
                            if (isset($row_f[$col]))
                                if ($row_f[$col] !== $val)
                                    $all_match = false;
                        }
                        if ($all_match)
                            $merge[] = array_merge($row, $row_f);
                    }
                }
            }
        }

        // Make sure the rows with the most columns are reviewed first,
        // this keeps the rows with partially data out.
        usort($merge, "WebDLMController::sort_by_array_size");

        // Filter only the rows that have unique values.
        $filter = array();
        if (count($merge) > 0) {
            // pre-load the filter array with the top returned value, this gives us something to compare with.
            $k_sort = $merge[0];
            ksort($k_sort);
            $filter[] = $k_sort;
            foreach ($merge as $row_m) {
                $is_uniqe_row = true;
                foreach($filter as $row_f) {
                    $test = array_diff_assoc($row_m, $row_f);
                    // If there are no real differences, then we don't have a new unique row.
                    if (count($test) == 0) {
                        $is_uniqe_row = false;
                        break;
                    }
                }
                // We have a new row to add to our list
                if ($is_uniqe_row) {
                    $k_sort = $row_m;
                    ksort($k_sort);
                    $filter[] = $k_sort;
                }
            }
        }
        
        // TODO: This has only been tested on a three DLM join, it may not fully join more DLMs into a single row.
        
        $data['JOIN']['DATA'] = $filter;
        
        // Return the data
        return $data;
    }
    
    static public function sort_by_array_size($a, $b) {
        return count($a) < count($b);
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