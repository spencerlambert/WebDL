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
                        $ssl = array(
                            PDO::MYSQL_ATTR_SSL_KEY     => MASTER_DB_SSL_KEY_PATH,
                            PDO::MYSQL_ATTR_SSL_CERT    => MASTER_DB_SSL_CERT_PATH,
                            PDO::MYSQL_ATTR_SSL_CA      => MASTER_DB_SSL_CA_PATH
                        );
			try {
                            if (MASTER_DB_SSL) {
				self::$DB_MASTER_PDO = new PDO($dsn, $username, $password, $ssl);
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