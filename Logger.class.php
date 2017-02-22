<?php
/**
 * Description of Logger class
 * - logger class can log actions into file or database
 * @author pesy
 */
class Logger extends Base {
  
	/*@var $tbl_logger - table logger*/
	private $tbl_logger = 'logger';
	/*@var $path - define save logger directory path*/
	private $path;
	/*@var $levels - array of logger level state*/
	private $levels = array('debug', 'error', 'info', 'warning', 'test', 'log');
	/*@var $filename - variable filename of logger, where to save*/
	private $filename;
  
	/**
	 * Initialize logger
	 * @param string $path - save directory path, default = NULL = Config::CONF_BASE_DIR.Config::LOGS_DIR_NAME
	 * @param string $filename - name of filename, where to save, default = logit.log
	 */
	public function __construct($path = NULL, $filename = 'logit.log') {
		 $this->path = ($path != NULL ) ? $path : Config::CONF_BASE_DIR.Config::LOGS_DIR_NAME;
		$this->filename = $filename;
	}
  
	/**
	* Function get logger levels
	* @return array - array of logger level or nothing
	*/  
	private function getLevels() {
		if (!is_array($this->levels)&&(count($this->levels)==0)) return;      
		return $this->levels;
	}
  
	/**
	 * Public function to log different states into file
	 * @param string $level - type of logger state, from $levels
	 * @param string $text - description, text to log
	 * @param string $file - name of file to save, default is null = logit.log, else use variable filename
	 * @return void
	 */    
	public function logit($level, $text, $file = NULL) {
		if(!is_dir($this->path)) {
			umask(0000); 
			mkdir($this->path,0777);
		}  
		$file_ = ($file == NULL ) ? $this->path.$this->filename : $file;
		if(!is_file($file_)) {
			touch($file_); 
			@chmod($file_, 0666);
		}
		if (!in_array($level, $this->getLevels())) return;
		$f = fopen($file_,'a');
		fwrite($f,"[".date('Y-m-d H:i:s')."-".microtime().", $level, ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['PHP_SELF']." ".$_SERVER['HTTP_USER_AGENT']."], ".$text);
		fwrite($f,"\r\n");
		fclose($f);
	}  
  
  
	/**
	 * @todo log action and data info database
	 */ 
	public function logitDb() {
		parent::createDbConnection(); 
	}
  
}