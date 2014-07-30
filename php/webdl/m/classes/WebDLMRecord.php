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
            $this->cache_working_filename = WEBDL_ABSPATH."/webdl/cache/".$this->r_id."_working.sqlite";
            $this->cache_filename = WEBDL_ABSPATH."/webdl/cache/".$this->r_id.".sqlite";
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
        // If there is no cache file, then return from live data
        if (!file_exists($this->cache_filename)) return WebDLMController::dlm_request($this->request);

        $and_ary = array();
        $or_ary = array();
        $like_ary = array();
        $where_ary = array();

        $params = array();
        $param_id = 0;

        // Build the WHERE part of the query
        $r_matches = $this->request->get_matches();
        foreach ($r_matches as $match) {
            
            // Sort out the different parts of the WHERE statement
            switch ($match->type) {
                case "AND":
                    $and_ary[] = "_".$match->c_id."=:".$param_id;
                    break;
                case "OR":
                    $or_ary[] = "_".$match->c_id."=:".$param_id;
                    break;
                case "WILDCARD":
                    $like_ary[] = "_".$match->c_id." LIKE :".$param_id;
                    break;
            }
            // Set and increment the param name, so that each is unique.
            $params[":".$param_id] = $match->m_val;
            $param_id++;

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

        $db = new PDO('sqlite:'.$this->cache_filename);

        $sth = $db->prepare($sql);
        $sth->execute($params);

        // Run the query
        $data = array();
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tmp = array();
            foreach ($row as $name=>$val) {
                $tmp[trim($name, "_")] = $val;
            }
            $data[] = $tmp;
        }

        $result = new WebDLMResult();
        $result->set_dlm_result(0, $data);
        return $result;

    }

    public function update_cache() {
        if ($this->is_cached === false) return true;
        if (file_exists($this->cache_working_filename)) return false;

        $result = WebDLMController::dlm_request($this->request);

        $db = new PDO('sqlite:'.$this->cache_working_filename);

        $data = $result->get_joined_data();
        $cols = array();
        $sql = "CREATE TABLE RECORD (";
        foreach ($data[0] as $key => $value) {
            $sql .= "_".$key. " varchar(255), ";
            $cols[] = "_".$key;
        }
        $sql .= "CACHE_TIMESTAMP varchar(255) )";
        $cols[] = "CACHE_TIMESTAMP";
        $db->exec($sql);
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");

        $sql = "INSERT INTO RECORD (";
        $sql .= implode(",", $cols);
        $sql .= ") VALUES (";
        $param_ids = array();
        foreach ($cols as $i => $val) {
            $param_ids[] = ":".$i;
        }
        $sql .= implode(",", $param_ids);
        $sql .= ")";

        $sth = $db->prepare($sql);

        foreach ($data as $row) {
            $pram_id = 0;
            $params = array();
            foreach ($row as $value) {
                $params[':'.$pram_id] = $value;
                $pram_id++;
            }
            $params[':'.$pram_id] = $timestamp;
            $sth->execute($params);
        }
        $db = null;

        rename($this->cache_working_filename, $this->cache_filename); 

        return true;

    }


}
?>