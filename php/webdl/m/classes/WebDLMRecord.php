<?php
class WebDLMRecord {
    protected $r_id;
    protected $name;
    protected $has_a = array();
    protected $request;
    public $is_cached;
    protected $cache_working_filename;
    protected $cache_filename;
    
    public function __construct ($r_id) {
        $db = WebDLResourceManager::get("DB_MASTER_PDO");
        $this->r_id = $r_id;

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
        $this->is_cached = false;
        if ($row['Caching'] == 'On') {
            $this->is_cached = true;
            $this->cache_working_filename = WEBDL_ABSPATH."/webdl/cache/".$this->name."_working.sqlite";
            $this->cache_filename = WEBDL_ABSPATH."/webdl/cache/".$this->name.".sqlite";
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
            
            $this->has_a[$row['DLMTreeColumnID']] = $class;
        }
        
        $this->request = new WebDLMRequest();
        
        foreach ($this->has_a as $col) {
            $this->request->push_column($col->c_id);
        }
        
    }

    public function push_match($c_id, $m_val, $type='AND') {
        if (array_key_exists($c_id, $this->has_a)) {
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
    
    public function cache_request() {
        $and_ary = array();
        $or_ary = array();
        $like_ary = array();
        $where_ary = array();

        // Build the WHERE part of the query
        $r_matches = $this->request->get_matches();
        foreach ($r_matches as $match) {
            
            // Sort out the different parts of the WHERE statement
            switch ($match->type) {
                case "AND":
                    $and_ary[] = $this->tree->columns[$match->c_id]->c_name."='".SQLite3::escapeString$match->m_val)."'";
                    break;
                case "OR":
                    $or_ary[] = $this->tree->columns[$match->c_id]->c_name."='".SQLite3::escapeString$match->m_val)."'";
                    break;
                case "WILDCARD":
                    $like_ary[] = $this->tree->columns[$match->c_id]->c_name." LIKE '%".SQLite3::escapeString$match->m_val)."%'";
                    break;
            }
        }
        
        // Add the WHERE parts the the where array;
        if (count($and_ary) != 0)
            $where_ary[] = implode(' AND ', $and_ary);
        if (count($or_ary) != 0)
            $where_ary[] = "(".implode(' OR ', $or_ary).")";
        if (count($like_ary) != 0)
            $where_ary[] = implode(' AND ', $like_ary);

        $sql = "SELECT * FROM RECORD";
        if (count($where_ary) != 0)
            $sql .= " WHERE ".implode(' AND ', $where_ary);

        $db = new SQLite3($this->cache_filename);

        // Run the query
        $res = $db->query($sql);
        $data = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row
        }

        $result = new WebDLMResult();
        $result->set_dlm_result(0, $data);
        return $result;

    }

    public function update_cache() {
        if ($this->is_cached === false) return true;
        if (file_exists($this->cache_working_filename)) return false;

        $result = WebDLMController::dlm_request($this->request);

        $db = new SQLite3($this->cache_working_filename);

        $data = $result->get_joined_data();
        $cols = array();
        $sql = "CREATE TABLE RECORD (";
        foreach ($data[0] as $key => $value) {
            $sql .= $key. "varchar(255), ";
            $cols[] = $key;
        }
        $sql .= "CACHE_TIMESTAMP varchar(255) )";
        $cols[] = "CACHE_TIMESTAMP";
        $db->query($sql);
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");

        foreach ($data as $row) {
            $sql = "INSERT INTO RECORD (";
            $sql .= implode(",", $cols);
            $sql .= ") VALUES (";
            foreach ($row as $value) {
                $sql .= "'".SQLite3::escapeString($value)."',";
            }
            $sql .= "'".$timestamp."')";
            $db->query($sql);
        }
        $db->close();

        rename($this->cache_working_filename, $this->cache_filename); 

        return true;

    }


}
?>