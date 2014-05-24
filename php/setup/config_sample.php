<?php
//Database items
define("MASTER_DB_HOST","localhost");
define("MASTER_DB_USER","root");
define("MASTER_DB_PASS","mysqlpass");
define("MASTER_DB_NAME","master");
// SSL is not supported in PDO at this time
define("MASTER_DB_SSL",false);

define("MASTER_DB_TABLE_PREFIX", '');
define("MASTER_DB_NAME_WITH_PREFIX", MASTER_DB_NAME.".".MASTER_DB_TABLE_PREFIX); //Used in MySQL statements;

define("TEMPLATE_NAME", 'default');
define("DEBUG",false);

?>