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
        $this->config_list[] = "MYSQL_SSL_TF";
        $this->config_list[] = "MYSQL_SSL_CERT_PATH";
        $this->config_list[] = "MYSQL_SSL_KEY_PATH";
        $this->config_list[] = "MYSQL_SSL_CA_PATH";
        
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

        try {
            if (strtoupper($this->config['MYSQL_SSL_TR']) == "TRUE") {
                $ssl = array(
                    PDO::MYSQL_ATTR_SSL_KEY     => $this->config['MYSQL_SSL_KEY_PATH'],
                    PDO::MYSQL_ATTR_SSL_CERT    => $this->config['MYSQL_SSL_CERT_PATH'],
                    PDO::MYSQL_ATTR_SSL_CA      => $this->config['MYSQL_SSL_CA_PATH']
                );
                $this->pdo = new PDO($dsn, $username, $password, $ssl);
            } else {
                $this->pdo = new PDO($dsn, $username, $password);                
            }
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
    public function get($request) {
        $and_ary = array();
        $or_ary = array();
        $like_ary = array();
        $table_ary = array();
        $col_ary = array();
        $join_ary = array();
        
        $where_ary = array();
        
        $params = array();
        $param_id = 0;
        
        // Build the WHERE part of the query
        $r_matches = $request->get_matches();
        foreach ($r_matches as $match) {
            // Check if this part of the request applies to this DLM instance.
            if (!isset($this->tree->columns[$match->c_id]))
                continue;
            
            // Sort out the different parts of the WHERE statement
            switch ($match->type) {
                case "AND":
                    $and_ary[] = $this->tree->columns[$match->c_id]->t_name.".".$this->tree->columns[$match->c_id]->c_name."=:".$param_id;
                    break;
                case "OR":
                    $or_ary[] = $this->tree->columns[$match->c_id]->t_name.".".$this->tree->columns[$match->c_id]->c_name."=:".$param_id;
                    break;
                case "WILDCARD":
                    $like_ary[] = $this->tree->columns[$match->c_id]->t_name.".".$this->tree->columns[$match->c_id]->c_name." LIKE :".$param_id;
                    break;
            }
            // Make a list of needed tables;
            $table_ary[$this->tree->columns[$match->c_id]->t_id] = $this->tree->columns[$match->c_id]->t_name;
            
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

        // Build the SELECT columns part of the query.
        $r_columns = $request->get_columns();
        foreach ($r_columns as $col) {
            // Check if this part of the request applies to this DLM instance.
            if (!isset($this->tree->columns[$col->c_id]))
                continue;
            
            $col_ary[$col->c_id] = $this->tree->columns[$col->c_id]->t_name.".".$this->tree->columns[$col->c_id]->c_name." as _".$col->c_id;
            $table_ary[$this->tree->columns[$col->c_id]->t_id] = $this->tree->columns[$col->c_id]->t_name;
        }

        foreach ($this->tree->links['BY_TABLE'] as $links) {
            // Check if we are getting data from a foreign table that needs linking.
            foreach ($links as $link) {
                if (array_key_exists($this->tree->columns[$link->c_id_f]->t_id, $table_ary))
                    $join_ary[] = $this->tree->columns[$link->c_id]->t_name.".".$this->tree->columns[$link->c_id]->c_name." = ".$this->tree->columns[$link->c_id_f]->t_name.".".$this->tree->columns[$link->c_id_f]->c_name;
            }
        }
        if (count($join_ary) != 0)
            $where_ary[] = implode(' AND ', $join_ary);
        
        
        // Complete the query
        $sql = "SELECT ".implode(', ', $col_ary)." FROM ".implode(', ', $table_ary);
        if (count($where_ary) != 0)
            $sql .= " WHERE ".implode(' AND ', $where_ary);
        $sth = $this->pdo->prepare($sql);
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
        
        return $data;
        
    }
    public function post($request) {
        return false;
    }
    public function delete($request) {
        return false;
    }
    
    
}
?>