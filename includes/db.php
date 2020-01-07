<?php
//needed for PearDatabase
//must use getUniqueId to get crmid for adding comments
include_once 'libraries/adodb/adodb.inc.php';
require_once 'libraries/adodb/adodb-xmlschema.inc.php';

class db{

	function __construct() {
		$this->vtigerOptions = get_option('wvi_api_settings');
		$this->vtigerDbUsername = $this->vtigerOptions['wvi_db_username'];
		$this->vtigerDbPassword = $this->vtigerOptions['wvi_db_pw'];
		$this->vtigerDbName = $this->vtigerOptions['wvi_db_name'];
		$this->logFile = WOO_VTIGER_INT . 'error_log.txt';


	}


	function connect() {
		$host = 'localhost';
		$user = $this->vtigerDbUsername;
		$pw = $this->vtigerDbPassword;
		$db = $this->vtigerDbName;

		$conn = new mysqli($host, $user, $pw, $db);
		if($conn) {
			return $conn;
		} else {
			
				$logMessage = date('Y-m-d h:i:sa').': Failed to connect to MySQL: '.$mysqli->connect_error .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
				

		}
	}
}

class PearDatabase{

	function __construct() {
		$this->vtigerOptions = get_option('wvi_api_settings');
		$this->vtigerDbUsername = $this->vtigerOptions['wvi_db_username'];
		$this->vtigerDbPassword = $this->vtigerOptions['wvi_db_pw'];
		$this->vtigerDbName = $this->vtigerOptions['wvi_db_name'];
		$this->logFile = WOO_VTIGER_INT . 'error_log.txt';


	}

    var $database = null;
    var $dieOnError = false;
    var $dbType = null;
    var $dbHostName = 'localhost';
    var $dbName = null;
    var $dbOptions = null;
    var $userName= null;
    var $userPassword= null;
    var $query_time = 0;
    var $log = null;
    var $lastmysqlrow = -1;
    var $enableSQLlog = false;
    var $continueInstallOnError = true;



     function getUniqueID($seqname) {
    		//$this->checkConnection();
    		return $this->database->GenID($seqname."_seq",1);
    	}

        function connect($dieOnError = false) {
		global $dbconfigoption,$dbconfig;
		$this->dbType = 'mysqli';

		$this->dbName = $this->vtigerDbName;
		$this->userName = $this->vtigerDbUsername;
		$this->userPassword = $this->vtigerDbPassword;
	
		$this->database = ADONewConnection($this->dbType);
	
		// Setting client flag for Import csv to database(LOAD DATA LOCAL INFILE.....)
		if ($this->database->clientFlags == 0 && isset($dbconfigoption['clientFlags'])) {
			$this->database->clientFlags = $dbconfigoption['clientFlags'];
		}

		if ($this->dbType == 'mysqli') {
			$optionFlags = array();
			if ($this->database->optionFlags) {
				$optionFlags = $this->database->optionFlags;
			}

			$optionFlags = array_merge($optionFlags, array(array(MYSQLI_OPT_LOCAL_INFILE, true)));
			$this->database->optionFlags = $optionFlags;
		}
		// End

		$result = $this->database->PConnect($this->dbHostName, $this->userName, $this->userPassword, $this->dbName);
		if ($result) {
			//$this->database->LogSQL($this->enableSQLlog);

			// // 'SET NAMES UTF8' needs to be executed even if database has default CHARSET UTF8
			// // as mysql server might be running with different charset!
			// // We will notice problem reading UTF8 characters otherwise.
			// if($this->isdb_default_utf8_charset) {
			// 	$this->executeSetNamesUTF8SQL(true);
			// }
		} else {
					$logMessage = date('Y-m-d h:i:sa').': Failed to coneect to database in PearClass '.PHP_EOL;
			error_log($logMessage, 3, $this->logFile);
		}
	}
}
