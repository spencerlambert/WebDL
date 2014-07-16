<?php
/**
 * This class pulls data out of a Google Doc
 * spreadsheet for use as a DLM.
 * It caches the data inside a SQLite file. It first
 * looks for the info in the SQLite DB, if it's not
 * there, then it will pull an update.
 *
 * If the cache is X minutes old it will also sync
 * the sqlite file with the sqlite db.
 *
 * The sqlite files are in a directory that needs to not
 * allow web access, but needs PHP read/write access.
 * 
 */

class WebDLMGoogleDocSpreadsheet extends WebDLMBase {
 
//     private $sqlite_filename;
//     private $sqlite_db;
    protected $client = false;
    protected $spreadsheet_service =  null;
    protected $has_connected = false;
    
    public function __construct($dlm_id) {
        // Add configuration items to the list.
        // These must be set in the DLMConfig table, or else...
        $this->config_list[] = "GDATA_USER";
        $this->config_list[] = "GDATA_PASS";
        $this->config_list[] = "GDATA_SPREADSHEET_KEY";
        //$this->config_list[] = "GDATA_WORKSHEET_ID";
        // NORMAL or TABS_AS_COLUMN
        // NORMAL = The tabs are referenced by name as a table in the DLM Tree
        // TABS_AS_COLUMN = Only one DLMTreeTable is created, and the tab name in the google doc because a match on value, and all tabs must contain the same header.  Use TABS_AS_COLUMN as the DLMTreeColumn name.
        $this->config_list[] = "GDATA_MODE";

        parent::__construct($dlm_id);
//         $this->sqlite_filename = $this->install_path."sqlite_dbs/db_".$dlm_id.".sqlite";
//         $this->sqlite_db = new SQLite3($this->sqlite_filename);
    }

    public function is_ready() {
        return $this->connect();
    }

    protected function connect() {

        if ($this->has_connected === true) return true;

        require_once 'Zend/Loader.php';
        Zend_Loader::loadClass('Zend_Gdata_Spreadsheets');
        Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

        try {
            $this->client = Zend_Gdata_ClientLogin::getHttpClient($this->config['GDATA_USER'], $this->config['GDATA_PASS'], Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME);
            $this->spreadsheet_service = new Zend_Gdata_Spreadsheets($this->client);
        } catch (Exception $e) {
            $this->client = false;
            $this->spreadsheet_service = null;
            WebDLUserMessage::output('Google Spreadsheet Connection failed: ' . $e->getMessage());
            return false;
        }

        $this->has_connected = true;
        return true;
    }

    public function get($request) {
        $and_ary = array();
        $or_ary = array();
        $col_ary = array();
        $join_ary = array();
        
        $where_ary = array();

        // TODO: Get joins across table working, right now it only pulls data from a single table.
        // Alternativly, make each Spreadsheet Tab into a DLM and the DLMConnector will do the join.
        $table_name = "";
        $tabs_id = "";
        
        // Build the WHERE part of the query
        $r_matches = $request->get_matches();
        foreach ($r_matches as $match) {
            // Check if this part of the request applies to this DLM instance.
            if (!isset($this->tree->columns[$match->c_id]))
                continue;

            if ($this->config['GDATA_MODE'] == "TABS_AS_COLUMN") {
                if ($this->tree->columns[$match->c_id]->c_name == "TABS_AS_COLUMN") {
                    $table_name = $match->m_val;
                    $tabs_id = $match->c_id;
                    continue;
                }
            } else {
                // TODO: Get joins across table working, right now it only pulls data from a single table.
                $table_name = $this->tree->columns[$match->c_id]->t_name;                
            }


            // Sort out the different parts of the WHERE statement
            switch ($match->type) {
                case "AND":
                    if (is_numeric($match->m_val)) {
                        $and_ary[] = trim(str_replace(' ', '', strtolower($this->tree->columns[$match->c_id]->c_name))) . "=".$match->m_val;
                    } else {
                        $and_ary[] = trim(str_replace(' ', '', strtolower($this->tree->columns[$match->c_id]->c_name))) . "=\"{$match->m_val}\"";                        
                    }
                    break;
                case "OR":
                    if (is_numeric($match->m_val)) {
                        $or_ary[] = trim(str_replace(' ', '', strtolower($this->tree->columns[$match->c_id]->c_name))) . "=".$match->m_val;
                    } else {
                        $or_ary[] = trim(str_replace(' ', '', strtolower($this->tree->columns[$match->c_id]->c_name))) . "=\"{$match->m_val}\"";
                    }
                    break;
                case "WILDCARD":
                    WebDLUserMessage::output('Google Spreadsheet query doesn\'t support WILDCARD matching, ignoring ' . $match->c_id . ' LIKE ' . $match->val, 'WebDLMGoogleDocSpreadsheet.php');
                    break;
            }
        }
        

        // Figure out which table to use
        // TODO: Get joins across table working, right now it only pulls data from a single table.        
        $doc = new Zend_Gdata_Spreadsheets_DocumentQuery();
        $doc->setSpreadsheetKey($this->config['GDATA_SPREADSHEET_KEY']);
        $feed = $this->spreadsheet_service->getWorksheetFeed($doc);

        foreach ($feed as $sheet) {
            if ($table_name == $sheet->getTitle()->__toString()) break;
        }        

        // Get the worksheet id
        // Don't like parsing the sting, but can't find a Zend function that gives me what ListQuery wants. :(
        $tmp = explode('/', $sheet->getId()->__toString());
        $worksheet_id = $tmp[count($tmp)-1];

        $query = new Zend_Gdata_Spreadsheets_ListQuery();
        $query->setSpreadsheetKey($this->config['GDATA_SPREADSHEET_KEY']);
        $query->setWorksheetId($worksheet_id);

        // Add the WHERE parts the the where array;
        if (count($and_ary) != 0)
            $where_ary[] = implode(' AND ', $and_ary);
        if (count($or_ary) != 0)
            $where_ary[] = "(".implode(' OR ', $or_ary).")";

        //if (WEBDL_DEBUG)    echo "DLM ID: " . $this->dlm_id . PHP_EOL;
        //if (WEBDL_DEBUG)    echo "Where " . print_r($where_ary, true);

        // Build the SELECT columns part of the query.
        $r_columns = $request->get_columns();
        $g_col_to_dlm_col = array();
        foreach ($r_columns as $col) {
            // Check if this part of the request applies to this DLM instance.
            if (!isset($this->tree->columns[$col->c_id]))
                continue;

            $col_ary[$col->c_id] = trim(str_replace(' ', '', strtolower($this->tree->columns[$col->c_id]->c_name)));
            $g_col_to_dlm_col[$col_ary[$col->c_id]] = $col->c_id;
        }
        //if (WEBDL_DEBUG)    echo "col " . print_r($col_ary, true);
        
        
        // Complete the query
        if (count($where_ary) != 0) {
            $q = implode(' AND ', $where_ary);
            $query->setSpreadsheetQuery($q);
        }

        // Run the query
        $data = array();
        $listFeed = $this->spreadsheet_service->getListFeed($query);

        foreach($listFeed as $list_entry) {
            $tmp = array();
            $row = $list_entry->getCustom();
            foreach ($row as $cell) {
                if (in_array($cell->getColumnName(), $col_ary))
                    $tmp[$g_col_to_dlm_col[$cell->getColumnName()]] = $cell->getText();
            }
            if ($this->config['GDATA_MODE'] == "TABS_AS_COLUMN")
                $tmp[$tabs_id] = $table_name;
            if (count($tmp) != 0)
                $data[] = $tmp;
        }
        return $data;
    }

    public function post($request) {
        return true;
    }

    public function delete($request) {
        return true;
    }

}
?>