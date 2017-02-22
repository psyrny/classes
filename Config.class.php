<?php
/**
 * Description of Config class
 * - content of important parameters and setting, using inside child classes 
 * - child and its method can use config methods to gain right parameters and work with 
 * - give me what I need (no global)
 * @author petr.syrny@kulturne.com
 */
class Config {
  
	/**@const CONF_HOSTFOLDER - folder hostname url*/
	const CONF_HOSTFOLDER = 'dev-pesy/'; // localhost 
	/**@const LOGS_DIR_NAME - folder name for logger*/
	const LOGS_DIR_NAME = "logs/"; // localhost
	/**@const SITE_NAME - site name*/
	const SITE_NAME = '';
	/**@const VERSION - version number*/
	const VERSION = '1.0';

	/* socialsprinters - deployment test, pesy*/
	const CONF_BASE_DIR = "";
	const CONF_HOSTNAME = '';

	/**@var $cf_db_server- mysql connection server*/
	private $cf_db_server = 'localhost';
	/**@var $cf_db_user- mysql connection user*/
	private $cf_db_user = 'root';
	/**@var $cf_db_password- mysql connection password*/
	private $cf_db_password = '';
	/**@var $cf_db_database- mysql connection database*/
	private $cf_db_database = 'socialsp2_test';

	  /**@var $cf_se_token - token to access API SE, credentials*/
	private $cf_se_token = '';
	/**@var $cf_se_username - username to access API SE, credentials*/
	private $cf_se_username = '';  
  
	
	/**
	 * Get database credentials data
	 * @return array array of connection values (server, user, password, database)
	 * @author pesy
	 */
	protected function getDbCredentials() {
		return array("s"=>$this->cf_db_server,"u"=>$this->cf_db_user,"p"=>$this->cf_db_password,"d"=>$this->cf_db_database);
	}

	/**
	 * Get SmartEmailing credentials data
	 * @return array array of credential values (token, username)
	 * @author pesy
	 */  
	protected function getSECredentials() {
	  return array("token"=>$this->cf_se_token,"username"=>$this->cf_se_username);
	}  
  
	/**
	 * Get Gopay credentials data
	 * @return array array of credential values 
	 * @author pesy
	 */  	
	protected function getGopayConfig($production = true) {
		if ($production) {
			return array("token"=>$this->cf_se_token,"username"=>$this->cf_se_username);		
		} else {
			return array("token"=>$this->cf_se_token,"username"=>$this->cf_se_username);			
		}
	}
  
}