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
    
    // TODO: Make the WHERE part smarted to know required table joins.
    // TODO: WHERE also needs to include the Connector Logic.
    public function fetch_data($col_ids, $where_ids, $tree) {
        $where_str = array();
        $table_str = array();
        $params = array();
        $param_id = 0;
        foreach ($where_ids as $id=>$val) {
            if (isset($tree[$id])) {
                $where_str[] = $tree[$id]->t_name.".".$tree[$id]->c_name."=:".$param_id;
                $params[":".$param_id] = $val;
                $param_id++;
                $table_str[$tree[$id]->t_id] = $tree[$id]->t_name;
            }
        }
        
        $col_str = array();
        foreach ($col_ids as $id) {
            if (isset($tree[$id])) {
                $col_str[] = $tree[$id]->t_name.".".$tree[$id]->c_name." as _".$id;
                $table_str[$tree[$id]->t_id] = $tree[$id]->t_name;
            }
        }
        
        $sql = "SELECT ".implode(', ', $col_str)." FROM ".implode(', ', $table_str);
        if (count($where_str) != 0)
            $sql .= " WHERE ".implode(' AND ', $where_str);
        $sth = $db->prepare($sql);
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
        
    };
    
}
?>