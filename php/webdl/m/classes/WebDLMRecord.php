<?php
class WebDLMRecord {
    protected $r_id;
    protected $name;
    protected $has_a = array();
    protected $request;
    
    public function __construct ($r_id) {
        $db = WebDLResourceManager::get("DB_MASTER_PDO");
        $this->r_id = $record_id;

        $sql = "SELECT * FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMRecordModel WHERE RecordModelID=:id";
        $params = array(':id'=>$this->r_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        if (isset($row['RecordModelID'])) {
            $this->name = $row['Name'];            
        } else {
            $this->naem = "INVALID";
        }
        
        $sql = "SELECT * FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMRecordModelHasA WHERE RecordModelID=:id";
        $params = array(':id'=>$this->r_id);
        $sth = $db->prepare($sql);
        $sth->execute($params);
        
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $class = new stdClass();
            $class->c_id = $row['DLMTreeColumnID'];
            $class->name = $row['Name'];
            $class->count = $row['Count'];
            
            $has_a[$row['DLMTreeColumnID']] = $class;
        }
        
        $this->request = new WebDLMRequest();
        
        foreach ($this->has_a as $col) {
            $this->request->push_column($col->c_id);
        }
        
    }

    public function push_match($c_id, $m_val, $type='AND') {
        if (array_key_exists($c_id, $this->has_a) {
            $this->request->push_match($c_id, $m_val, $type);
        } else {
            return false;
        }
        return true;
    }
    
    public function set_type($type) {
        $this->request->set_type($type);
    }
    
    public function get_request() {
        return $this->request;
    }
    
}
?>