<?php
class WebDLMTree {
    public $tables = array();
    public $columns = array();
    public $links = array();
    public $dlmid;
    
    // If you don't give a DLM ID, then you get a tree that contains ALL data trees.
    public function __construct($dlmid = null) {
        
        $this->dlmid = $dlmid;
        
        $this->columns = WebDLMTreeColumn::get_columns($this->dlmid);
        $this->tables = WebDLMTreeTable::get_tables($this->dlmid);
        $this->links = WebDLMTreeLink::get_links($this->dlmid);
        
    }
    
    
    public function get_required_dlms($request) {
        return $this->get_column_dlms($request) + $this->get_match_dlms($request);        
    }
    
    
    public function get_matched_dlms($request) {
        $dlms = array();
        foreach ($this->get_match_tables($request) as $t_id) {
            $dlms[$this->tables[$t_id]->dlm_id] = $this->tables[$t_id]->dlm_id;
        }
        return $dlms;
    }

    public function get_column_dlms($request) {
        $dlms = array();
        foreach ($this->get_column_tables($request) as $t_id) {
            $dlms[$this->tables[$t_id]->dlm_id] = $this->tables[$t_id]->dlm_id;
        }
        return $dlms;
    }
    
    public function get_required_tables($request) {
        // Return combined arrays
        return $this->get_column_tables($request) + $this->get_match_tables($request);
    }
    
    public function get_column_tables($request) {
        return $this->get_tables_from_columns($request->get_columns());        
    }
    
    public function get_match_tables($request) {
        return $this->get_tables_from_columns($request->get_matches());
    }
    
    private function get_tables_from_columns($request_columns) {
        $tables = array();
        foreach ($request_columns as $col) {
            if ($this->dlmid === null) {
                $tables[$this->columns[$col->c_id]->t_id] = $this->columns[$col->c_id]->t_id;
            } else {
                if ($this->dlmid == $this->columns[$col->c_id]->dlm_id)
                    $tables[$this->columns[$col->c_id]->t_id] = $this->columns[$col->c_id]->t_id;
            }
        }
        
        return $tables;        
    }
    
}
?>