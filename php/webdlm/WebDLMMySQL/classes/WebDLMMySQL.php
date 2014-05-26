<?php
/**
 * This class helps give you a head start when
 * creating a DLM that pulls from a MySQL DB.
 */

class WebDLMMySQL extends WebDLMBase {
    
    protected $pdo = false;
 
    public function __construct($dlm_id) {
        // Add configuration items to the list.
        // These must be set in the DLMConfig table, or else...
        $this->config_list[] = "MYSQL_HOST";
        $this->config_list[] = "MYSQL_USER";
        $this->config_list[] = "MYSQL_PASS";
        $this->config_list[] = "MYSQL_DATABASE";
        
        parent::__construct($dlm_id);
        
    }
    
    // This is called prior to any data select
    public function is_ready() {
        return $this->connect();
    }
    
    protected function connect() {
        
        // Only setup the connection if it is not already, otherwise return true as we have a connection.
        if ($this->pdo !== false) return true;
        
        $dsn = 'mysql:dbname='.$this->config['MYSQL_DATABASE'].';host='.$this->config['MYSQL_HOST'];
        $username = $this->config['MYSQL_USER'];
        $password = $this->config['MYSQL_PASS'];
        /*
         TODO: Add SSL Support.
        $ssl = array(
                    PDO::MYSQL_ATTR_SSL_KEY    =>'/etc/mysql/certs/client-key.pem',
                    PDO::MYSQL_ATTR_SSL_CERT=>'/etc/mysql/certs/client-cert.pem',
                    PDO::MYSQL_ATTR_SSL_CA    =>'/etc/mysql/certs/ca-cert.pem'
                );
        */
        try {
            $this->pdo = new PDO($dsn, $username, $password);
        } catch (PDOException $e) {
            AppMessage::output('MySQL Connection failed: ' . $e->getMessage());
            $this->pdo = false;
            return false;
        }
        
        return true;
        
    }

    /*******
     * $request_array:
     * Two dimensional array with following indexes
     * COLUMN_ID: An array of the column ids to be returned.
     * AND_MATCH: An array of column ids and matched to preform for fetching the row(s).
     * OR_MATCH: Like AND_MATCHES, but with the OR operator between each.
     * WILDCARD_MATCH: Uses the % char as a wildcard, working like the LIKE MySQL operator.
     *
     * 
     **/    
    // TODO: Make the WHERE part smarted to know required table joins.
    // TODO: WHERE also needs to include the Connector Logic.
    public function get($request_array) {
        $and_ary = array();
        $or_ary = array();
        $like_ary = array();
        $table_ary = array();
        $col_ary = array();
        
        $where_ary = array();
        
        $params = array();
        $param_id = 0;
        
        if (isset($request_array['AND_MATCH'])) {
            foreach ($request_array['AND_MATCH'] as $id=>$val) {
                if (isset($this->columns[$id])) {
                    $and_ary[] = $this->columns[$id]->t_name.".".$this->columns[$id]->c_name."=:".$param_id;
                    $params[":".$param_id] = $val;
                    $param_id++;
                    $table_str[$this->columns[$id]->t_id] = $this->columns[$id]->t_name;
                }
            }
            $where_ary[] = implode(' AND ', $and_ary);
        }

        if (isset($request_array['OR_MATCH'])) {
            foreach ($request_array['OR_MATCH'] as $id=>$val) {
                if (isset($this->columns[$id])) {
                    $or_ary[] = $this->columns[$id]->t_name.".".$this->columns[$id]->c_name."=:".$param_id;
                    $params[":".$param_id] = $val;
                    $param_id++;
                    $table_str[$this->columns[$id]->t_id] = $this->columns[$id]->t_name;
                }
            }
            $where_ary[] = "(".implode(' OR ', $or_ary).")";
        }

        if (isset($request_array['WILDCARD_MATCH'])) {
            foreach ($request_array['WILDCARD_MATCH'] as $id=>$val) {
                if (isset($this->columns[$id])) {
                    $like_ary[] = $this->columns[$id]->t_name.".".$this->columns[$id]->c_name." LIKE :".$param_id;
                    $params[":".$param_id] = $val;
                    $param_id++;
                    $table_str[$this->columns[$id]->t_id] = $this->columns[$id]->t_name;
                }
            }
            $where_ary[] = implode(' AND ', $like_ary);
        }

        
        foreach ($request_array['COLUMN_ID'] as $id) {
            if (isset($this->columns[$id])) {
                $col_ary[] = $this->columns[$id]->t_name.".".$this->columns[$id]->c_name." as _".$id;
                $table_str[$this->columns[$id]->t_id] = $this->columns[$id]->t_name;
            }
        }
        
        
        
        $sql = "SELECT ".implode(', ', $col_ary)." FROM ".implode(', ', $table_str);
        if (count($where_str) != 0)
            $sql .= " WHERE ".implode(' AND ', $where_ary);
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);

        $data = array();
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tmp = array();
            foreach ($row as $name=>$val) {
                $tmp[trim($name, "_")] = $val;
            }
            $data[] = $tmp;
        }
        
        return $data;
        
    }
    public function post($request_array) {
        return false;
    }
    public function delete($request_array) {
        return false;
    }
    
    
}
?>