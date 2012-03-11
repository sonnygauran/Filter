<?php

class Filter{

	private static $instance;

	protected function __construct(){
		$stdin = fopen ('php://stdin', 'r');
    	ob_implicit_flush (true); // Use unbuffered output

   		while ($line = fgets ($stdin)){
   			self::processLine($line);
		}
	}

	public static function getInstance(){
		if(empty(self::$instance)) self::$instance = new Filter();
		return self::$instance;
	}

	function processLine($line){
		$type = self::identifyLine($line);
		switch($type){
			case 'access':
				break;
			case 'error':
				break;
			default:
				print "$line\n";
		}
	}

	function identifyLine($line){
		$char = substr($line, 0, 1);
		return ($char == '[') ? 'error' : ((is_numeric($char) ? 'access' : 'other'));
	}
}

Filter::getInstance();
