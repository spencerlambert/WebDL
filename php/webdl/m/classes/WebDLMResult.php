<?php
class WebDLMResult {

    public $dlm_result = array();
    protected $row_links = array();
    
    public function __construct() {
        
    }
    
    public function set_dlm_result($dlm_id, $data, $is_online=true) {
        // Save the data returned by the DLM for later reterval or joining.
        if (!isset($this->dlm_result[$dlm_id])) {
            $class = new stdClass();
            $class->is_online = $is_online;
            $class->data = $data;
            $this->dlm_result[$dlm_id] = $class;
        }
    }
    
    public function push_column_link($c_id, $val, $data) {
        if (!isset($this->row_links[$c_id."-".$val]))
            $this->row_links[$c_id."-".$val] = array();
        $this->row_links[$c_id."-".$val][] = $data;        
    }
    
    public function get_dlm_data($dlm_id) {
        if (!isset($this->dlm_result[$dlm_id])) return false;
        if (!$this->dlm_result[$dlm_id]->is_online) return false;
        return $this->dlm_result[$dlm_id]->data;
    }
    
    public function get_dlm_ids() {
        return array_keys($this->dlm_result);
    }
    
    public function get_joined_data() {
    
        // Join the data if needed.
        // TODO: This is really not working...  Need to get it right.
        // TODO: This will only join two DLMs, need to add logic for joining more together.
        $join = array();
        if (count($this->row_links) != 0) { 
            foreach ($this->dlm_result as $dlm_id=>$result) {
                if (!$result->is_online) continue;
                foreach ($result->data as $row) {
                    foreach ($row as $c_id=>$c_val) {
                        $name = $c_id."-".$c_val;
                        if (isset($this->row_links[$name])) {
                            if (!isset($join[$dlm_id]))
                                $join[$dlm_id] = array();
                            foreach ($this->row_links[$name] as $id=>$link) {
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
        usort($merge, "WebDLMResult::sort_by_array_size");

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
        
        // If there is no filtered data and only a sinlge DLM involded in the request, then return the data from that DLM.
        if (count($filter) == 0 && count($this->dlm_result) == 1) {
            $dlm_ids = $this->get_dlm_ids();
            return $this->get_dlm_data($dlm_ids[0]);
        }
        
        // TODO: This has only been tested on a three DLM join, it may not fully join more DLMs into a single row.
        
        return $filter;
    }

    static public function sort_by_array_size($a, $b) {
        return count($a) < count($b);
    }
    
}
?>