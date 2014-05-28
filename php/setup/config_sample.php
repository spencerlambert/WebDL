<?php
//Database items
define("MASTER_DB_HOST","localhost");
define("MASTER_DB_USER","root");
define("MASTER_DB_PASS","mysqlpass");
define("MASTER_DB_NAME","master");
// SSL is not implemented in the ResourceManager class at this time
define("MASTER_DB_SSL",false);
define("MASTER_DB_SSL_CERT_PATH",'/etc/mysql/ssl/client-cert.pem');
define("MASTER_DB_SSL_KEY_PATH",'/etc/mysql/ssl/client-key.pem');
define("MASTER_DB_SSL_CA_PATH",'/etc/mysql/ssl/ca-cert.pem');

define("MASTER_DB_TABLE_PREFIX", '');

define("TEMPLATE_NAME", 'default');
define("DEBUG",false);

?>