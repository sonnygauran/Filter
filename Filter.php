<?php

define('DATE_FORMAT', 'd/M/Y:G:i:s');

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
				$details = self::traceDetails($line, 'access');
				echo self::traceIP($line, 'access') . "\n";
				echo self::traceDate($line, 'access') . "\n";
				print_r($details);
				break;
			case 'error':
				echo self::traceIP($line, 'error') . "\n";
				echo self::convertDate(self::traceDate($line, 'error')) . "\n";
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

	function convertDate($date){
		return date(DATE_FORMAT, strtotime($date));
	}

	function identifyLine($line){
		$char = substr($line, 0, 1);
		return ($char == '[') ? 'error' : ((is_numeric($char) ? 'access' : 'other'));
	}

	function traceDetails($line, $line_type){
		$startTracing = false;
		$raw_details = array();
		$details =array();
		$detail = "";
		if($line_type == 'access'){
			for($i = 0; $i < strlen($line); $i++){
				$character = substr($line, $i, 1);
				if($character == '"' ){
					if($startTracing){
						$raw_details[count($raw_details)] = substr($detail, 1);
						$detail = "";
					}
					$startTracing = !$startTracing;
				}
				if($startTracing) $detail .= $character;
			}
			$temp = explode(" ", $raw_details[0]);
			$details["http_request"] = $temp[0];			
			$details["uri_request"] = $temp[1];
			$details["protocol"] = $temp[2];
			$details["url"] = $raw_details[1];
			$details["client_details"] = $raw_details[2];
		}
		return $details;
	}
}

Filter::getInstance();
