<?php
class WebDLMTreeLink {
    public $c_id;
    public $c_id_f;
    public $dlm_id;
    public $t_id;
    public $type;
    
    public function __construct() {
        
    }
    
    static public function get_links($dlmid = null) {
        $db = ResourceManager::get("DB_MASTER_PDO");

        $links = array();
        $links['BY_DLM'] = array();
        $links['BY_TABLE'] = array();
        $links['BY_COLUMN'] = array();
        
        if ($dlmid === null) {
            // Match all links
            $sql = "SELECT
                        l.DLMTreeColumnID,
                        l.DLMTreeColumnIDForeign,
                        l.Type
                        c.DLMTreeTableID,
                        t.DLMID,
                    FROM
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeLink l,
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c,
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t
                    WHERE
                        l.DLMTreeColumnID=c.DLMTreeColumnID AND
                        c.DLMTreeTableID=t.DLMTreeTableID";
            $sth = $db->prepare($sql);
            $sth->execute();
        } else {
            // Match all links to a DLM
            $sql = "SELECT
                        l.DLMTreeColumnID,
                        l.DLMTreeColumnIDForeign,
                        l.Type
                        c.DLMTreeTableID,
                        t.DLMID,
                    FROM
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeLink l,
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeColumn c,
                        ".MASTER_DB_NAME_WITH_PREFIX."DLMTreeTable t
                    WHERE
                        l.DLMTreeColumnID=c.DLMTreeColumnID AND
                        c.DLMTreeTableID=t.DLMTreeTableID AND
                        t.DLMID=:id";
            $params = array(':id'=>$dlmid);
            $sth = $db->prepare($sql);
            $sth->execute($params);            
        }

        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $class = new WebDLMTreeLink();
            $class->c_id = $row['DLMTreeColumnID'];
            $class->c_id_f = $row['DLMTreeColumnIDForeign'];
            $class->t_id = $row['DLMTreeTableID'];
            $class->dlm_id = $row['DLMID'];
            $class->type = $row['Type'];

            if (!isset($links['BY_DLM'][$row['DLMID']]))
                $links['BY_DLM'][$row['DLMID']] = array();
            if (!isset($links['BY_TABLE'][$row['DLMTreeTableID']]))
                $links['BY_TABLE'][$row['DLMTreeTableID']] = array();
            if (!isset($links['BY_COLUMN'][$row['DLMTreeColumnID']]))
                $links['BY_COLUMN'][$row['DLMTreeColumnID']] = array();
            
            $links['BY_DLM'][$row['DLMID']][] = $class;            
            $links['BY_TABLE'][$row['DLMTreeTableID']][] = $class;            
            $links['BY_COLUMN'][$row['DLMTreeColumnID']][] = $class;            
        }
        
        return $links;
    }
}
?>