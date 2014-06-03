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
 
    private $sqlite_filename;
    private $sqlite_db
    
    public function __construct($dlm_id) {
        parent::__construct($dlm_id);
        $this->sqlite_filename = $this->install_path."sqlite_dbs/db_".$dlm_id.".sqlite";
        $this->sqlite_db = new SQLite3($this->sqlite_filename);
    }

    public function fetch($key, $key_name, $columns = array()) {
        
    }

}
?>