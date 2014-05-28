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

        // This function should only be called when the tree contains all trees.
        if ($this->dlmid !== null) {
            UserMessage::output("WebDLMTree::get_required_dlms() is being called by a tree that does not contain all DLM trees.  Can't do that...", "WebDLMTree.php");
            return false;
        }
        
        $r_columns = $request->get_columns();
        $r_matches = $request->get_matches();

        // Get a list of all the DLMs we need to make requests to.
        $required_dlms = array();
        foreach ($r_columns as $col) {
            $required_dlms[$this->columns[$col->c_id]->dlm_id] = $this->columns[$col->c_id]->dlm_id;
        }
        
        if (count($required_dlms) == 0) {
            UserMessage::output('No COLUMN_ID in the WebDLMRequest object, at least one column id needs to be pushed.', 'WebDLMTree.php');
            return false;            
        }
        
        foreach ($r_matches as $col) {
            $required_dlms[$this->columns[$col->c_id]->dlm_id] = $this->columns[$col->c_id]->dlm_id;
        }
        
        return $required_dlms;
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