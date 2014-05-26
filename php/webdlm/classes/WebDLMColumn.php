<?php
class WebDLMColumn (


    static public function get_columns($dlmid = null) {
        $db = ResourceManager::get("DB_MASTER_PDO");
        
        if ($dlmid === null) {
            // Match all columns to a DLM
            $sql = "SELECT        
                        c.DLMTreeColumnID,
                        c.DLMTreeTableID,
                        t.DLMID,
                        t.Name as TName,
                        c.Name as CName,
                        c.Type,
                        c.IsKey
                    FROM
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t,
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c
                    WHERE
                        t.DLMTreeTableID=c.DLMTreeTableID";
            $sth = $db->prepare($sql);
            $sth->execute();
        } else {
            // Match all columns to a DLM
            $sql = "SELECT        
                        c.DLMTreeColumnID,
                        c.DLMTreeTableID,
                        t.DLMID,
                        t.Name as TName,
                        c.Name as CName,
                        c.Type,
                        c.IsKey
                    FROM
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t,
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c
                    WHERE
                        t.DLMTreeTableID=c.DLMTreeTableID AND
                        t.DLMID=:id";
            $params = array(':id'=>$dlmid);
            $sth = $db->prepare($sql);
            $sth->execute($params);            
        }

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $class = new stdClass();
            $class->c_id = $row['DLMTreeColumnID'];
            $class->t_id = $row['DLMTreeTableID'];
            $class->dlm_id = $row['DLMID'];
            $class->c_name = $row['CName'];
            $class->t_name = $row['TName'];
            $class->type = $row['Type'];
            $class->is_key = $row['IsKey'];
            $this->columns[$row['DLMTreeColumnID']] = $class;
            if (!isset($this->columns_by_dlm[$row['DLMID']]))
                $this->columns_by_dlm[$row['DLMID']] = array();
            $this->columns_by_dlm[$row['DLMID']][$row['DLMTreeColumnID']] = $class;
            
        }
    }
   
)
?>