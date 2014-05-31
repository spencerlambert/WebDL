<?php
class WebDLMTreeTable {
    public $t_id;
    public $t_name;
    public $dlm_id;
    public $c_ids = array();
    
    public function __construct() {
        
    }
    
    static public function get_tables($dlmid = null) {
        $db = ResourceManager::get("DB_MASTER_PDO");
        
        $tables = array();
        
        if ($dlmid === null) {
            // Match all tables
            $sql = "SELECT        
                        t.DLMTreeTableID,
                        t.DLMID,
                        t.Name as TName
                    FROM
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t";
            $sth = $db->prepare($sql);
            $sth->execute();
        } else {
            // Match all tables to a DLM
            $sql = "SELECT        
                        t.DLMTreeTableID,
                        t.DLMID,
                        t.Name as TName
                    FROM
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t
                    WHERE
                        t.DLMID=:id";
            $params = array(':id'=>$dlmid);
            $sth = $db->prepare($sql);
            $sth->execute($params);            
        }

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $class = new WebDLMTreeTable();
            $class->t_id = $row['DLMTreeTableID'];
            $class->dlm_id = $row['DLMID'];
            $class->t_name = $row['TName'];
            $tables[$row['DLMTreeTableID']] = $class;            
        }
        
        // Gat an array of all the table ids, to make a SQL query that gets all the column ids.
        $t_ids = array();
        foreach ($tables as $id=>$class) {
            $t_ids[] = $id;
        }
        $sql = "SELECT
                    c.DLMTreeColumnID,
                    c.DLMTreeTableID
                FROM
                    ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c
                WHERE
                    c.DLMTreeTableID IN (".implode(',',$t_ids).")";
        $sth = $db->prepare($sql);
        $sth->execute();
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tables[$row['DLMTreeTableID']][$row['DLMTreeColumnID']] = $row['DLMTreeColumnID'];            
        }

        return $tables;

    }
}
?>