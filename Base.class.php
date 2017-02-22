<?php
/**
 * Description of Base abstract class
 *
 * @author pesy petr.syrny@kulturne.com
 */
abstract class Base extends Config {
  
	/**@var $db - for definition mysql instance*/
	protected $db;
  
	/**
	 * Method to make connection to DB
	 * @return object instance of mysql (parameters from parent class Config)
	 * @author pesy
	 */
	protected function createDbConnection() {
		$conn_params = parent::getDbCredentials();
		$this->db = new Mysql($conn_params['s'], $conn_params['u'], $conn_params['p'], $conn_params['d']);
		return $this->db;
	}
  
	/**
	* Procces sql query and return data array
	* @param string $query - query to execute
	* @return array - result of query, data array
	* @author pesy 
	*/
	public function getQuery($query) {
		if (!$query) return;
		$this->db->query($query, $result);
		while ($res__ = $this->db->fetchArrayResult($result,MYSQL_ASSOC)) {
			$data[] = $res__;
		}
		return $data;   		
	}	    
  
	/** 
	 * Escape string, from input, insert to database etc.
	 * @param string $string value to escape
	 * @return string escaped string
	 * @author pesy 
	 */
	public function toSafeData($string) {
		if (get_magic_quotes_gpc()) {
			$string_conv = stripslashes($string);
		}
		$string_conv = mysql_real_escape_string(htmlspecialchars($string));
		return trim($string_conv);
	}     
  
	/**
	 * Keep value of form input, mainly for POST
	* @param string $form_element - value (name) of element $_POST['']
	* @return string value of input, only for type = 'text' || area
	 * @author pesy 
	*/
	public function keepValueRet($form_element) {
		if (isset($form_element)) { return (string)$form_element; }
	}  
  
}