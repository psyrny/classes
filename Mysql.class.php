<?php
/**
 * Description of mysql class
 *  rok 2010
 * @author pesy petr.syrny@kulturne.com
 */
class Mysql {
	private $link; ///< spojeni k databazi
	private $lastQuery; ///< posledni polozeny dotaz
	public $error; ///< zprava o chybe MySQL dotazu
	public $debug = false; ///< zapnut/vypnut debugovaci mod
					/**< V debugovacim modu vypisuje trida MySQL errory na
							   screen, aby byly jasne videt. Mod se vypina, pokud
							   chceme, aby byly errory pouze logovany a ukladany do
							   DB a ne aby je videl uzivatel.

							   \note Casta chyba pri nahazovani bety na ostrou je,
							   ze se zapomina vypnout debug mod a pak MySQL chyby 
							   otravuji uzivatele
					   */

	/**
	 *	Funkce vytvori trvale (perzistentni) spojeni k databazi.
	 *	Pouziva se funkce mysql_pconnect. Pokud spojeni z nejakeho duvodu nemuze
	 *	byt navazano, vypise funkce na obrazovku HTML kod o chybe a omluvu.
	 *	\note Funkce jeste zapise cas vypadnuti spojeni do souboru sql.error. 
	 *				Ten slouzil k odeislani e-mailu o vypnutem spojeni. Nejsem si jist
	 *				zdali jeste slouzi, pravdepodobne jiz funguje jiny mechanismus 
	 *				upozornovani.
	 *	@param $host host
	 *	@param $user uzivatel databaze
	 *	@param $password heslo
	 *	@param $db jmeno databaze
	 *	@return Nic, maximalne vypisuje na screen udaj o chybe pripojeni(HTML hlavicka atp. jsou "zadratovane")
	 *	@author Pesy
	 */
	public function __construct($host, $user, $password, $db) {
		if (($link = @mysql_connect($host, $user, $password)) != false) {
			$this->link =  $link;
			$this->setDatabase($db);
		} else {
			echo "<html>";
			echo "<title>";
			echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
			echo "</title";
			echo "<body>";
			echo "<h2>Nepodařilo se připojit k SQL databázi</h2>";
			echo "<br /><br />Na závadě se již pracuje, omluvte prosím dočasnou nefunkčnost stránek a zkuste svůj požadavek za pár minut.";
			echo "</body>";
			$handle = fopen("sql.error", "r");
			$last_time = fgets($handle);
			fclose($handle);
			if (($last_time + 900) < time()) {
				$handle = fopen("sql.error", "w");
				fputs($handle, time());
			}
			$this->error();
			exit();
		}	
	}

	/**
	 *  Vybere databazi, jez je specifikovana v konstruktoru tridy mysql. 
	 *	Navic nastavi veskerou komunikaci do utf8. 
	 *	@see konstruktor #mysql() 
	 *	@param $db jmeno databaze
	 *	@return nic
	 *	@author pesy, uprava kodovani Honza
	 */
	private function setDatabase($db) {
		if (mysql_select_db($db, $this->link) == false) {
			$this->error();
		}
		mysql_query("SET NAMES 'utf8'") or $this->error($error['character_set'], mysql_error());
		mysql_query("SET character_set_results='utf8'") or $this->error($error['character_set'], mysql_error());
		mysql_query("SET character_set_connection='utf8'") or $this->error($error['character_set'], mysql_error());
		mysql_query("SET character_set_client='utf8'") or $this->error($error['character_set'], mysql_error());
	}

	/**
	 *  Funkce zpracovava chybu MySQL.
	 *	Chyby jsou logovany do tabulky chyb (errors). V pripade debug modu jsou
	 *		navic vypisovany na borazovku.
	 *	@param $query dotaz, ktery chybu vyvolal
	 *	@param $logError pokud je true, chyba se uklada do tabulky chyb, v opacnem
	 *										pripade se chyba neuklada.
	 *									  defaultni hodnota je true
	 *	@return nic
	 *	@author Honza a Pesy
	 */
	private function error($query='', $logError=true) {
		$this->error = mysql_errno()." ".mysql_error();
		if ($this->debug) {
			$exception = new LinkedException();
			echo $exception->showStackTrace();
			echo "<pre style='color: red'>\nerror: $this->error \n\nquery: $query</pre>";
		}	
		
		// log do tabulky chyb    
		if (!$logError) return;
		/*$page = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$ipaddress = $_SERVER['REMOTE_ADDR']; 
		$date= date('Y-m-d H:i:s');
		mysql_query("INSERT INTO error (page, error_type, errno, error_text, dt_insert, ipaddress) 
													   VALUES ('".$page."', 
																 'MySQL-Backend', 
																		 '".mysql_errno()."', 
																		 '".substr(str_replace("'", "\"", mysql_error()."||".$query), 0, 2048)."',
																		 '".$date."',
																		 '".$ipaddress."'
									)");*/
	}

	/**
	 *Funkce polozi dotaz databazi a v druhem parametru vraci vysledek.
	*@see mysql::error();
	 *	@return 
	 *          - v pripade SELECT dotazu vraci pocet vracenych radek
	 *          - v pripade UPDATU, DELETU pocet ovlivnenych radek
	 *	@param $query text dotazu
	 *	@param &$result navratovy parametr vraci "resource" funkce mysql_query()
	 *	@param $logError je predan funkci error v pripade chyby
	 *	@author Pesy, log chyb Honza
	 */
	public function query($query, &$result, $logError = true) {
		$this->lastQuery = $query;
		if ($this->debug) echo "<pre>$query</pre>";
		//$result = mysql_query($query, $this->link);	
		//$result = mysql_query($query);	
		//return $result;
		if (($result = mysql_query($query, $this->link)) != false) {
			if (preg_match("/^select/i", $query)) {
				return mysql_num_rows($result);
			} else {
				return mysql_affected_rows($this->link);
			}
		} else {
			$this->error($query, $logError);
			return false;
		}
	}

	/**
	 *  Funkce v poli vraci jeden radek vysledku dotazu.
	 *
	 *	@see mysql::query()
	 *	@param $res resource z phpfce mysql_query()
	 *	@return mysql_fetch_array($res) 
	 *	@author Pesy	
	 */
	public function fetchArray($res) {
		if(!empty($res)) {
			return mysql_fetch_array($res);
		} else {
			return '';
		}
	}

	/**
	* Funkce umoznuje definovani jak se pole vracene funkci fetchArray() nadefinuje, zobrazi. viz MYSQL_ASSOC,MYSQL_BOTH 
	* Pole se proste prekope (indexy, hodnoty) podle nadefinovane hodnoty $return_in
	*	@param $array - pole, jehoz vystup se ma predefinovat
	*	@param $return_in - definice vystupu, jak se pole zpracuje (MYSQL_ASSOC, MYSQL_BOTH, MYSQL_NUM)
	*	@return array() - upravene pole
	*	@author Pesy
	*/
	public function fetchArrayResult($array, $return_in = MYSQL_ASSOC) {
		if (!empty($array)) {
			return mysql_fetch_array($array, $return_in);
		} else {
			return '';
		}
	}

	/** 
	 * 	Vraci prvni sloupec prvniho radku z vysledku 
	 *	@see mysql::query()
	 *	@param $res resource z phpfce mysql_query()
	 *	@return prvni sloupec prvniho radku. Provede 
	 *					$data = $this->fetchArray() a vrati $data[0]
	 *	@author Pesy
	 */
	public function getSingleValue($res) {
		if (($data = $this->fetchArray($res)) && is_array($data)) {
			return $data[0];
		}
	}

	/**
	 *  Vraci vysledek php fce mysql_insert_id().
	 *	@param nic
	 *	@return mysql_insert_id($this->link);
	 *	@author Pesy
	 */
	public function getInsertId() {
		return mysql_insert_id($this->link);
	}
		
	/**
	 *  Vraci defaultni hodnotu v danem sloupci dane tabulky.
	 *	@param $table jmeno tabulky
	 *	@param $field jmeno sloupce
	 *	@return defaultni hodnota
	 *	@retval false nejaka chyba
	 *	@author Honza
	 */
	public function getDefaultValue($table, $field) {
		if ($this->query("SHOW COLUMNS FROM $table", $query)) {
			while ($row = mysql_fetch_assoc($query)) {
				if ($row['Field'] == $field) {
					return $row['Default'];
				}
			}
		}
		return false;
	}

	/**
	 *	Funkce vrati posledni provedeny dotaz
	 *	@return dotaz
	 */
	public function getLastQuery() {
		return $this->lastQuery;
	}
}
