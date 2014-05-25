<?php

class ResourceManager {
	private static $DB_MASTER_PDO;

	public static function get($resource, $options = false) {
		if (property_exists('ResourceManager', $resource)) {
			if (empty(self::$$resource)) {
				self::_init_resource($resource, $options);
			}
			if (!empty(self::$$resource)) {
				return self::$$resource;
			}
		}
		return null;
	}

	private static function _init_resource($resource, $options = null) {
		if ($resource == 'DB_MASTER_PDO') {
			$dsn = 'mysql:dbname='.MASTER_DB_NAME.';host='.MASTER_DB_HOST;
			$username = MASTER_DB_USER;
			$password = MASTER_DB_PASS;
                        /*
                         TODO: Add SSL Support.
                        $ssl = array(
                                    PDO::MYSQL_ATTR_SSL_KEY    =>'/etc/mysql/certs/client-key.pem',
                                    PDO::MYSQL_ATTR_SSL_CERT=>'/etc/mysql/certs/client-cert.pem',
                                    PDO::MYSQL_ATTR_SSL_CA    =>'/etc/mysql/certs/ca-cert.pem'
                                );
                        */
			try {
                            if (MASTER_DB_SSL) {
                                UserMessage::output("SSL SUPPORTED NOT YET ADDED FOR MYSQL, CHECK CONFIG.PHP", 'ResourceManager.php');
                                exit();
				//self::$DB_CATCHER = new PDO($dsn, $username, $password, $ssl);
                            } else {
				self::$DB_MASTER_PDO = new PDO($dsn, $username, $password);
                            }
			} catch (PDOException $e) {
				UserMessage::output('Connection failed: ' . $e->getMessage(), 'ResourceManager.php');
			}
		} elseif (class_exists($resource) && property_exists('ResourceManager', $resource)) {
			self::$$resource = new $resource($options);
		}
	}

}
?>