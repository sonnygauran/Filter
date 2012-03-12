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
				echo self::traceIP($line, 'access') . "\n";
				echo self::traceDate($line, 'access') . "\n";
				break;
			case 'error':
				echo self::traceIP($line, 'error') . "\n";
				echo self::traceDate($line, 'error') . "\n";
				break;
			default:
				print "$line\n";
		}
	}

	function traceIP($line, $line_type){
		$client = "";
		$character = null;
		$startTracing = false;
		for($i = 0; $i < strlen($line); $i++){
			$character = substr($line, $i, 1);
			if($line_type == 'access'){
				if($character != '.' && !is_numeric($character)) break;
				$client .= $character;
			}else{
				if($character == ']') $startTracing = false;
				if($startTracing) $client .= $character;
				if($i == 42) $startTracing = true;
			}
		}
		return $client;
	}

	function traceDate($line, $line_type){
		$date = null;
		$startTracing = false;
		if($line_type == 'error'){
			$date = substr($line, 1, 24);
		}else{
			for($i = 0; $i < strlen($line); $i++){
				$character = substr($line, $i, 1);
				if($character == '+') $startTracing = false;
				if($startTracing) $date .= $character;
				if($character == '[') $startTracing = true;
			}
		}
		return $date;
	}

	function identifyLine($line){
		$char = substr($line, 0, 1);
		return ($char == '[') ? 'error' : ((is_numeric($char) ? 'access' : 'other'));
	}
}

Filter::getInstance();
