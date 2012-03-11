<?php

class Filter{

	private static $instance;

	protected function __construct(){
		$stdin = fopen ('php://stdin', 'r');
    	ob_implicit_flush (true); // Use unbuffered output

   		while ($line = fgets ($stdin))
   		self::processLine($line);
	}

	public static function getInstance(){
		if(empty(self::$instance)) self::$instance = new Filter();
		return self::$instance;
	}

	function processLine($line){
		echo "$line";
	}

}

Filter::getInstance();
