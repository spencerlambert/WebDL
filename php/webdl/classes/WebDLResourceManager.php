<?php

class WebDLResourceManager {
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
			$dsn = 'mysql:dbname='.WEBDL_MASTER_DB_NAME.';host='.WEBDL_MASTER_DB_HOST;
			$username = WEBDL_MASTER_DB_USER;
			$password = WEBDL_MASTER_DB_PASS;
			try {
                            if (WEBDL_MASTER_DB_SSL) {
                                $ssl = array(
                                    PDO::MYSQL_ATTR_SSL_KEY     => WEBDL_MASTER_DB_SSL_KEY_PATH,
                                    PDO::MYSQL_ATTR_SSL_CERT    => WEBDL_MASTER_DB_SSL_CERT_PATH,
                                    PDO::MYSQL_ATTR_SSL_CA      => WEBDL_MASTER_DB_SSL_CA_PATH
                                );
				self::$DB_MASTER_PDO = new PDO($dsn, $username, $password, $ssl);
                            } else {
				self::$DB_MASTER_PDO = new PDO($dsn, $username, $password);
                            }
			} catch (PDOException $e) {
				WebDLUserMessage::output('Connection failed: ' . $e->getMessage(), 'WebDLResourceManager.php');
			}
		} elseif (class_exists($resource) && property_exists('ResourceManager', $resource)) {
			self::$$resource = new $resource($options);
		}
	}

}
?>