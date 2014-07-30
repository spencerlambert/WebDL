<?php
    define('WEBDL_ABSPATH', dirname(__FILE__).'/');
    require_once(WEBDL_ABSPATH.'webdl/setup/init.php');
    

    $sql = "SELECT RecordModelID FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."DLMRecordModel";
    $sth = $db->prepare($sql);
    $sth->execute();
    foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $row) {
    	$record = new WebDLMRecord($row['RecordModelID']);
    	$record->update_cache();
    }
?>
