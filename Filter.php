<?php

define('DATE_FORMAT', 'd/M/Y:G:i:s');

class Filter{

	private static $instance;
	private static $column_width;

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
		self::$column_width = `tput cols`;
		$type = self::identifyLine($line);
		$details = self::traceDetails($line, $type);
		$start = " Start ";
		$end = " End ";

		echo self::center($start, "'");

		switch($type){
			case 'error':
				
				/** Determine error header **/
				$temp = "PHP {$details['Type']}";
				$space = self::$column_width - (strlen($temp) + strlen($details['Date']));
				$message[0] = "$temp" . self::character($space, " ") . $details['Date'];
			
				$temp = "{$details['Source']} {$details['Line Number']}";
				$space = self::$column_width - (strlen($temp) + strlen($details['IP']));
				$message[1] = "$temp" . self::character($space, " ") . $details['IP'] . "\n";
				
				$message[2] = $details['Message'];

				for($i = 0; $i < count($message); $i++) echo $message[$i];
				echo "\n\n";
				
				break;

			case 'access':
				print_r($details);
				break;
			default:
				print "$line\n";
		}
		echo self::center($end, "'") . "\n\n";
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
			$details["HTTP Request"] = $temp[0];			
			$details["URI Request"] = $temp[1];
			$details["Protocol"] = $temp[2];
			$details["URL"] = $raw_details[1];
			$details["Client Information"] = $raw_details[2];
			$details += self::traceAccessResponse($line, $details["Protocol"]);
		}else if($line_type == 'error'){
			$position = strpos($line, "PHP Warning");
			$details["Type"] = ($position === false) ? "Fatal error" : "Warning";
			//$details["Position"] = ($position === false) ? strpos($line, "PHP Fatal error") : $position;
			
			/** Tracing the error message **/
			$string = "PHP {$details["Type"]}: ";
			$start = strpos($line, $string) + strlen($string) + 1;
			$length = strlen($line) - ((strlen($line) - (strpos($line, ' in /home/'))) + ($start));
			$details["Message"] = substr($line, $start, $length);

			/** Trace error source **/
			$location = explode(" ", substr(substr($line, strpos($line, " in /home")), 4));
			$details["Source"] = $location[0];
			$details["Line Number"] = "{$location[2]} " . substr($location[3], 0, strlen($location[3])-1);
			
		}else{
			return $line;
		}

		$details["IP"] = self::traceIP($line, $line_type);
		$details["Date"] = self::convertDate(self::traceDate($line, $line_type));
		ksort($details);

		return $details;
	}

	function traceAccessResponse($line, $protocol){
		$index = strpos($line, $protocol);
		$pointer = $index + strlen($protocol) + 2;
		$temp = "";
		for($i = $pointer; substr($line, $i, 1) != '"'; $i++){
			$temp .= substr($line, $i, 1);
		}
		$raw_details = explode(" ", $temp);
		$details["HTTP Response"] = $raw_details[0];
		$details["Size"] = $raw_details[1];

		return $details;
	}
	
	function character($count, $character){
		$output = "";
		for($i = 0; $i < $count; $i++)
			$output .= $character;

		return $output;
	}

	function center($message, $character){
		$space = self::$column_width/2 - (strlen($message)/2);
        $message = self::character($space,$character) . $message;
        $space = self::$column_width - strlen($message);
        $message .= self::character($space, $character);

		return $message;
	}

}

Filter::getInstance();
