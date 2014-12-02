<?php
/**
 * This class helps give you a head start when
 * creating a DLM that pulls from a MySQL DB.
 */

class WebDLMLDAP extends WebDLMBase {
    
    protected $ldapconn = false;
 
    public function __construct($dlm_id) {
        // Add configuration items to the list.
        // These must be set in the DLMConfig table, or else...
        $this->config_list[] = "LDAP_BIND_RDN";
        $this->config_list[] = "LDAP_BIND_PASS";
        $this->config_list[] = "LDAP_BASE_DN";
        $this->config_list[] = "LDAP_SERVER";
        $this->config_list[] = "LDAP_PORT";

        parent::__construct($dlm_id);
        
    }
    
    // This is called prior to any data select
    public function is_ready() {
        return $this->connect();
    }

    protected function connect() {
        
        // Only setup the connection if it is not already, otherwise return true as we have a connection.
        if ($this->ldapconn !== false) return true;
        
        $this->ldapconn = ldap_connect($this->config['LDAP_SERVER'], $this->config['LDAP_PORT']);

        if ($this->ldapconn === false) return false;

        return true;
        
    }

    public function get($request) {
        $and_ary = array();
        $col_ary = array();
        $join_ary = array();
        
        $where_ary = array();
        
        // Build the Filter
        $r_matches = $request->get_matches();
        foreach ($r_matches as $match) {
            // Check if this part of the request applies to this DLM instance.
            if (!isset($this->tree->columns[$match->c_id]))
                continue;
            
            // Sort out the different parts of the WHERE statement
            switch ($match->type) {
                case "AND":
                    $and_ary[] = '('.$this->tree->columns[$match->c_id]->c_name."='".$match->m_val."')";
                    break;
                case "OR":
                    //Not Implemented
                    break;
                case "WILDCARD":
                    $and_ary[] = '('.$this->tree->columns[$match->c_id]->c_name."='*".$match->m_val."*')";
                    break;
            }
            
        }

        // Build the SELECT columns part of the query.
        $r_columns = $request->get_columns();
        foreach ($r_columns as $col) {
            // Check if this part of the request applies to this DLM instance.
            if (!isset($this->tree->columns[$col->c_id]))
                continue;
            
            $col_ary[] = $this->tree->columns[$col->c_id]->c_name;
        }


        // Fetch results
        if (count($and_ary) == 0) {
            // Selecting all records, doing a special fetch.
            $filter = "(sAMAccountName=a*)";
            $rows = $this->search($filter, $col_ary);
        } else {
            $filter = "(&".implode('', $and_ary).")";
            $rows = $this->search($filter, $col_ary);
        }
        
        return $rows;

    }

    private function search($filter, $col_ary) {

        $rows = array();
        $ldapbind = ldap_bind($this->ldapconn, $this->config['LDAP_BIND_RDN'], $this->config['LDAP_BIND_PASS']);
        $res = ldap_search($this->ldapconn, $this->config['LDAP_BASE_DN'], $filter, $col_ary);
        $vals = ldap_get_entries($this->ldapconn, $res);

        foreach ($vals as $row) {
            $process_row = array();
            foreach ($row as $key => $value) {
                foreach ($col_ary as $col_name) {
                    if (strtolower($col_name) == $key)
                        $process_row[$col_name] = $value[0];
                }
            }
            $rows[] = $process_row;
        }

        return $rows;

    }
    public function post($request) {
        return false;
    }
    public function delete($request) {
        return false;
    }
    
    
}
?>