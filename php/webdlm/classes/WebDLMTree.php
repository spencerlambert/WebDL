<?php
class WebDLMTree {
    protected $tables = array();
    protected $columns = array();
    protected $links = array();
    protected $dlmid;
    
    // If you don't give a DLM ID, then you get a tree that contains ALL data trees.
    public function __construct($dlmid = null) {
        
        $this->dlmid = $dlmid;
        
        $this->columns = WebDLMTreeColumn::get_columns($this->dlmid);
        $this->tables = WebDLMTreeTable::get_tables($this->dlmid);
        $this->links = WebDLMTreeLink::get_links($this->dlmid);
        
    }
    
    
    public function get_required_dlms($request_array) {

        // This function should only be called when the tree contains all trees.
        if ($this->dlmid !== null) {
            UserMessage::output("WebDLMTree::get_required_dlms() is being called by a tree that does not contain all DLM trees.  Can't do that...", "WebDLMTree.php");
            return false;
        }

        // Get a list of all the DLMs we need to make requests to.
        $required_dlms = array();
        if (isset($request_array['COLUMN_ID'])) {
            foreach ($request_array['COLUMN_ID'] as $id) {
                $required_dlms[$this->columns[$id]->dlm_id] = $this->columns[$id]->dlm_id;
            }
        } else {
            UserMessage::output('No COLUMN_ID in the $request_array, at least one column id needs to be included.', 'WebDLMController.php');
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