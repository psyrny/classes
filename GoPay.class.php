<?php
/*TODO - class autoload*/
include_once('Logger.class.php');
/**
 * Description of GoPay
 *
 * @author pesy
 * 
 * Zruseni opakovane platby - vypne se premium clenstvi v momente, kdy se mela ztrhavat dalsi platba
 * https://doc.gopay.com/cs/?_ga=1.232229151.1006017523.1456391276#zru%C5%A1en%C3%AD-opakov%C3%A1n%C3%AD-platby
 * 
 * Vraceni platby - vypne se premium clenstvi okamzite
 * https://doc.gopay.com/cs/?_ga=1.232229151.1006017523.1456391276#refundace-platby-(storno)
 * 
 * Testovaci platby a nastaveni
 * https://help.gopay.com/cs/tema/integrace-platebni-brany/integrace-nova-platebni-brany/provadeni-plateb-v-testovacim-prostredi
 */


class GoPay extends Base {
  
	/**@var $payment_state - definition of payment state*/
	public static $payment_state = array('CREATED'=>'Platba založena',
								'PAYMENT_METHOD_CHOSEN'=>'Platební metoda vybrána',
								'PAID'=>'Platba zaplacena',
								'AUTHORIZED'=>'Platba pøedautorizována',
								'CANCELED'=>'Platba zrušena',
								'TIMEOUTED'=>'Vypršela platnost platby',
								'REFUNDED'=>'Platba refundována',
								'PARTIALLY_REFUNDED'=>'Platba èásteènì refundována');
	
	/**@var $payment_cycle - definition of payment cycle*/														
	public static $payment_cycle = array('M'=>'MONTH',
								'W'=>'WEEK',
								'D'=>'DAY',
								'OD'=>'ON_DEMAND');																		
	
	/**@var $payment_data - data payment variable*/														
	private $payment_data;
  
	/**@var $client_id - client id, gateway, test - kulturne.com*/
	private $client_id = 1694553993;
	/**@var $client_secret- client secret, gateway, test - kulturne.com*/
	private $client_secret = '5GTJzZkw';
	/**@var $goid- gopay id, gateway, test - kulturne.com*/
	private $goId = 8480919755;
  
	/**@var $sandbox_gopay_url - gopay sandbox url*/
	private $sandbox_gopay_url = 'https://gw.sandbox.gopay.com/api/';
	/**@var $sandbox_gopay_token_url - gopay sandbox token url*/
	private $sandbox_gopay_token_url = "https://testgw.gopay.cz/api/oauth2/token";
	/**@var $sandbox_gopay_payment_url  - gopay sandbox payment url*/
	private $sandbox_gopay_payment_url = "https://testgw.gopay.cz/api/payments/payment";
	/**@var $sandbox_gopay_js_embed - gopay sandbox js url*/
	private $sandbox_gopay_js_embed = "https://testgw.gopay.cz/gp-gw/js/embed.js";
  
	/**@var $gopay_token_url - gopay production token url*/
	private $gopay_token_url = "https://gate.gopay.cz/api/oauth2/token";
	/**@var $gopay_payment_url - gopay production payment url*/
	private $gopay_payment_url = "https://gate.gopay.cz/api/payments/payment";
	/**@var $gopay_js_embed - gopay production js url*/
	private $gopay_js_embed = "https://gate.gopay.cz/gp-gw/js/embed.js";

	/**@var $config  - variable for config definition*/
	private $config;
  
	/**@var $log - variable to  initialization logger class and work with  through*/
	private $log;
  
	/**
	 * Constructor of class
	 * @todo parent function getGopayConfig(), parametert to config.class
	 */
	public function __construct($isSandbox = false) {
		$path_file = __DIR__.'/logs';
		$this->log = new Logger($path_file);
		/*if ($isSandbox) {
			$this->config = parent::getGopayConfig();
		} 
		$this->config = parent::getGopayConfig(false);*/
	}
  
	/**
	 * Standard token - payment-create only token
	 * @return array - json data array of result   
	 */
	public function getStandardToken() {
		$ch = curl_init();
		$credentials = $this->client_id.':'.$this->client_secret;
		$data = 'grant_type=client_credentials&scope=payment-create';
		curl_setopt($ch, CURLOPT_URL, $this->sandbox_gopay_token_url);
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded', 'Accept: application/json', "Authorization: Basic " . base64_encode($credentials)));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);     
		ob_start(); 
		// grab URL and pass it to the browser
		$result = curl_exec($ch);
		if ($result === false) {
			echo "Chyba v zasilani GoPay requestu!";
		} else {
		// close cURL resource, and free up system resources
			curl_close($ch);
			$str = ob_get_clean();
			//echo $str; 
			return json_decode($str);
		}			
	}
  
	/**
	* Utility token - payment-all - for other operation (state, refund, payment)
	* @return array - json data array of result	
	*/
	public function getPaymentToken() {
		$ch = curl_init();
		$credentials = $this->client_id.':'.$this->client_secret;
		$data = "grant_type=client_credentials&scope=payment-all";
		curl_setopt($ch, CURLOPT_URL, $this->sandbox_gopay_token_url);
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded', 'Accept: application/json', "Authorization: Basic " . base64_encode($credentials)));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		ob_start(); 
		// grab URL and pass it to the browser
		$result = curl_exec($ch);
		if ($result === false) {
			echo "Chyba v zasilani GoPay requestu!";
		} else {
		// close cURL resource, and free up system resources
			curl_close($ch);
			$str = ob_get_clean();
			//echo $str; 
			return json_decode($str);
		}			
	}  

	/**
	* Evocation outside the class, somewhere in process script
	* @param array - definition of paymet data values, that are used in payment functions
	* 			-set data for payment into variable $payment_data
	* @see getPaymentData 									
	*/
	public function setPaymentData(array $paymentData) {
		if (!$paymentData) return;
		$this->payment_data = $paymentData;
	}

	/**
	* Get canned array of payment
	* @return array - variable $payment_data
	* @see setPaymentData	
	*/	
	public function getPaymentData() {
		if (!is_array($this->payment_data)) return;
		return $this->payment_data;
	}

	/**
	 * Function that create base gopay payment
	 * @param array $paymentData - array of important data to create payment
	 * @return json - json_decode array, important is value of gw_url (for evocation of payment gateway)
	 * @see gateWayInline, gateWayRedirect	  	 	 	
	 */
	public function createPayment() {
		//if (!$paymentData) return;
		$path_file = __DIR__.'/logs/gopay_create_payment.log';
		$paymentToken = $this->getPaymentToken();
		$pd = $this->getPaymentData();
		$data = array(
			"payer" => array(
				"default_payment_instrument"=>"PAYMENT_CARD", // defaulten nastavene, ale lze prepnout platebni metodu - nahore nad oknem!
				"allowed_payment_instruments"=>array("PAYMENT_CARD"), // vsechny platebni metody mohou byt v poli - array("PAYMENT_CARD", "MPAYMENT")
				"contact" => array(
					"first_name"=>$pd['contact']['first_name'],
					"last_name"=>$pd['contact']['last_name'],
					"email"=>$pd['contact']['email'],
					"phone_number"=>$pd['contact']['phone_number'],
					"city"=>$pd['contact']['city'],
					"street"=>$pd['contact']['street'],
					"postal_code"=>$pd['contact']['postal_code'],
					"country_code"=>$pd['contact']['country_code']
					)
			),
			"target" => array(
				"type"=>"ACCOUNT",
				"goid"=>$this->goId
			),
			"amount"=> '166600', // cena v halerich
			"currency"=> 'CZK',
			"order_number"=> '10000004',
			"order_description" => 'testovaci platba z API',
			"items"=> array(
				//array("name"=>"item01","amount"=>"1000"),
				//array("name"=>"item02","amount"=>"666")
			),
			// !!! POZOR nemenit poradi, zapisuji podle indexu !!!
			"additional_params" => array(
				/*array("name"=>"from","value"=>'5.4.2016'),
				array("name"=>"to","value"=>'5.5.2016'),
				array("name"=>"months","value"=>'1'),
				array("name"=>"druh_platby","value"=>'standard')*/
			), 
			"callback"=> array(
				//"return_url"=> 'http://www.petrsyrny.cz/utils/gopay/return.php',
				//"notification_url"=> 'http://www.petrsyrny.cz/utils/gopay/notify.php',
			),
			"lang"=>'cs'
		);
		$this->log->logit("debug","GOPAY createPayment gopay-payment_url: ".$this->sandbox_gopay_payment_url, $path_file);
		$this->log->logit("debug","GOPAY createPayment data: ".serialize($data), $path_file);
		$data_string = json_encode($data);
		// create a new cURL resource
		$ch = curl_init();
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $this->sandbox_gopay_payment_url);
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-Type: application/json',                                                                                
			'Authorization: Bearer '.$paymentToken->access_token
			//'Content-Length: ' . strlen($data_string)
			)
		);
		ob_start(); 
		// grab URL and pass it to the browser
		curl_exec($ch);
		// close cURL resource, and free up system resources
		curl_close($ch);
		$str = ob_get_clean(); 
		//echo $str;
		$this->log->logit("debug","GOPAY createPayment syrove : $str", $path_file);
		return json_decode($str);
	}
  
  
	/**
	 * Creation of reccurence payment
	 * @param array $paymentData - array of important data to create payment
	 * @return json - json_decode array, important is value of gw_url (for evocation of payment gateway)
	 * @see gateWayInline, gateWayRedirect	  	 	 	
	 */  
	public function createRecurrencePayment() {
		$path_file = __DIR__.'/logs/gopay_create_payment.log';
		$paymentToken = $this->getPaymentToken();
		$pd = $this->getPaymentData();
		$data = array(
			"payer" => array(
				"default_payment_instrument"=>"PAYMENT_CARD", // defaulten nastavene, ale lze prepnout platebni metodu - nahore nad oknem!
				"allowed_payment_instruments"=>array("PAYMENT_CARD"), // vsechny platebni metody mohou byt v poli - array("PAYMENT_CARD", "MPAYMENT")
				"contact" => array(
					"first_name"=>$pd['contact']['first_name'],
					"last_name"=>$pd['contact']['last_name'],
					"email"=>$pd['contact']['email'],
					"phone_number"=>$pd['contact']['phone_number'],
					"city"=>$pd['contact']['city'],
					"street"=>$pd['contact']['street'],
					"postal_code"=>$pd['contact']['postal_code'],
					"country_code"=>$pd['contact']['country_code']
					)
			),
			"target" => array(
				"type"=>"ACCOUNT",
				"goid"=>$this->goId
			),
			"amount"=> '1900', // cena v halerich
			"currency"=> 'CZK',
			"order_number"=> '10000005',
			"order_description" => 'testovaci opakujici se platba z API',
			"items"=> array(
				//array("name"=>"item01","amount"=>"1000"),
				//array("name"=>"item02","amount"=>"666")
			),
			// !!! POZOR nemenit poradi, zapisuji podle indexu !!!
			"additional_params" => array(
				/*array("name"=>"from","value"=>'5.4.2016'),
				array("name"=>"to","value"=>'5.5.2016'),
				array("name"=>"months","value"=>'1'),
				array("name"=>"druh_platby","value"=>'standard')*/
			), 
			"recurrence" => array(
			"recurrence_cycle" => $pd['recurrence']['recurrence_cycle'], // MONTH, WEEK, DAY, ON_DEMAND | ON_DEMAND
			"recurrence_period" => $pd['recurrence']['recurrence_period'], // 1 - kazdy mesic, tyden, den | 2 - kazdy 2. mesic, 2. tyden, 2. den!
			"recurrence_date_to"=> $pd['recurrence']["recurrence_date_to"] // do kdy se plati {format: 2015-12-31}
		),			
			"callback"=> array(
				"return_url"=> 'http://www.petrsyrny.cz/utils/gopay/return.php',
				"notification_url"=> 'http://www.petrsyrny.cz/utils/gopay/notify.php',
			),
			"lang"=>'cs'
		);
		$this->log->logit("debug","GOPAY createRecurrencePayment gopay-payment_url: ".$this->sandbox_gopay_payment_url, $path_file);
		$this->log->logit("debug","GOPAY createRecurrencePayment data: ".serialize($data), $path_file);
		$data_string = json_encode($data);
		// create a new cURL resource
		$ch = curl_init();
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $this->sandbox_gopay_payment_url);
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-Type: application/json',                                                                                
			'Authorization: Bearer '.$paymentToken->access_token
			//'Content-Length: ' . strlen($data_string)
			)
		);
		ob_start(); 
		// grab URL and pass it to the browser
		curl_exec($ch);
		// close cURL resource, and free up system resources
		curl_close($ch);
		$str = ob_get_clean(); 
		//echo $str;
		$this->log->logit("debug","GOPAY createRecurrencePayment  : $str", $path_file);
		return json_decode($str);  
  
	}
  
  
	/**
	* Inline gateway
	* - to show gateway, do echo of this function (show button)	
	* @param string $createPaymentType - type of payment type (classic payment or recurrence payment)
	 * @todo not HTML in function, change to php
	* @see createPayment, createRecurrencePayment	 	
	*/
	public function gateWayInline($createPaymentType) {
		ob_start();
		$createPayment = $createPaymentType;
		?>
		<!-- platebni brana -->
		<form action="<?echo $createPayment->gw_url;?>" method="post" id="gopay-payment-button">
			<button name="pay" type="submit">Zaplatit</button>
			<script type="text/javascript" src="<?php echo $this->sandbox_gopay_js_embed?>"></script>
		</form>
		<!-- /platebni brana -->
		<?
		return ob_get_clean();
	}
	
	/**
	* Redirect gateway
	* @param string $createPaymentType - type of payment type (classic payment or recurrence payment)
	 * @todo not HTML in function, change to php
	* @see createPayment, createRecurrencePayment		
	*/
	public function gateWayRedirect($createPaymentType) {
		ob_start();
		$createPayment = $createPaymentType;
		?>
		<!-- platebni brana -->
		<form action="<?echo $createPayment->gw_url;?>" method="post">
			<button name="pay" type="submit">Zaplatit</button>
		</form>
		<!-- /platebni brana -->
		<?
		return ob_get_clean();
	}		  

	/**
	 * Get state of payment
	 * @param $idpayment - unique id payment
	 * @return json to work with
	 */
	public function getPaymentState($idpayment) {
		$path_file = __DIR__.'/logs/gopay_notify_state.log';
		$this->log->logit("log", "PLATBA - GoPay->getPaymentState ID=: ".$idpayment, $path_file);
		$getPaymentToken = $this->getPaymentToken();
		$ch = curl_init();
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $this->sandbox_gopay_payment_url."/".$idpayment);
		curl_setopt($ch, CURLOPT_HTTPGET, 1); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Bearer '.$getPaymentToken->access_token
			//'Content-Length: ' . strlen($data_string)
			)
		);
		ob_start(); 
		// grab URL and pass it to the browser
		curl_exec($ch);
		// close cURL resource, and free up system resources
		curl_close($ch);
		$str = ob_get_clean(); 
		$this->log->logit("log", "PLATBA - GoPay->getPaymentState CURL: ".$str, $path_file);
		return json_decode($str);
	} 
  
}
