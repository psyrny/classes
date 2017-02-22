<?php
/**
 * Description of SmartEmailing class
 * API: https://app.smartemailing.cz/api/v2
 * @author pesy petr.syrny@kulturne.com
 */
class SmartEmailing extends Base {

	/*@var static $pesy_test_list_id - id of testing contact list in SE account*/
	public static $pesy_test_list_id = 94;
	/*@var static $premium_list_id - id of premium contact list - users with premium*/
	public static $premium_list_id = 69; // SS production = 69!
	/*@var static $premium_cancel_id - id of premium cancel contact list - premium cancel request*/
	public static $premium_cancel_id = 95;
	/*@var $log - instance of logger object*/
	private $log;
	/*@var $log_filename - variable definition of logger save filename*/
	private $log_filename = 'smartemailing.log';  
	private $api_url = 'https://app.smartemailing.cz/api/v2';
  
	/**
	 * Constructor of class
	 * - initialize of logger class - default saving directory
	 */
	public function __construct() {
		$this->log = new Logger('',$this->log_filename);    
	}  
  
	/**
	 * Show result
	 */
	private function v($result) {
		echo $result;
		//print_r($result);
		die();
	}  
  
	/**
	 * Function to set (CONFIRM) user email into new contact list {$listto} and REMOVE from orifinal {$listfrom}
	 * Credentials for SE connection are delivered by parent method getSECredentials()
	 * @param string $email - user email
	 * @param integer $listfrom - remove email from contactlist ID
	 * @param integer $listto - confirm email to contactlist ID
	 * @return boolean|string - false if email is not set, string when success via sendRequest
	 * @see sendRequest()
	 */
	public function removeEmailFromTo($email, $listfrom, $listto) {
		if (!$email) {return false;}
		$credentials = parent::getSECredentials();
		$xml = "
		  <xmlrequest>
			<username>{$credentials['username']}</username>
			<usertoken>{$credentials['token']}</usertoken>
			<requesttype>Contacts</requesttype>
			<requestmethod>createupdate</requestmethod>
			<details>
			  <emailaddress>".$email."</emailaddress>
			  <language>cs_CZ</language> <!-- cs_CZ | sk_SK | en_GB -->
			  <contactliststatuses>
				<item>
				  <id>".intval($listfrom)."</id>
				  <status>removed</status>
				</item>
				<item>
				  <id>".intval($listto)."</id>
				  <status>confirmed</status>
				  <!-- unconfirmed | confirmed | unsubscribed | banned | removed -->              
				</item>            
			  </contactliststatuses>
			</details>
		  </xmlrequest>
		  ";   
		 return $this->sendRequest($xml);   
	}
  
	/**
	 * Function that process SE API requests
	 * @param string $xml - xml type request
	 * @return boolean|string
	 */
	protected function sendRequest($xml) {
		if(!$xml) {return false;}
		$ch = curl_init($this->api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		$result = curl_exec($ch);
		if ($result === false) {
			//echo "Chyba v zasilani XML requestu!";
			$this->log->logit("debug","smartmailing sendRequest: Chyba v zasilani XML requestu!");	
		} else {
			header ("Content-Type:text/xml");
			//$this->v($result);
			$xml_doc = @simplexml_load_string($result); // intentionally @
			 $this->log->logit("debug","smartmailing sendRequest:".error_log($xml_doc));	
			if (!$xml_doc) {
			  $this->log->logit("debug","smartmailing ERR in request");	
			  return "ERR in request";
			  //$this->v($result);
			}
			//echo 'Status is ' . $xml_doc->status . PHP_EOL;
			if ($xml_doc->status == 'SUCCESS') {
				$this->log->logit("debug","smartmailing SUCCESS");	
				return true;			
			} else {
				$this->log->logit("debug","smartmailing err:".$xml_doc->errormessage);	
				return false;
			}
		}
	}  
  
}