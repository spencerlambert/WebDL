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
            AppMessage::output('Connection failed: ' . $e->getMessage());
            $this->pdo = false;
            return false;
        }
        
        return true;
        
    }
}
?>