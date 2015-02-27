<?php
/*

Methods:

$seo = StringStuff->clean()
StringStuff->slug();
*/
//setlocale(LC_ALL, 'en_US.UTF8');


class StringUtils {
	
	function __construct(){
		
	}
	
	function cleanURL($str,$replace=array(),$delimiter='-'){
		$str = Request::get($str,$str);
		if( !empty($replace) ) {
			$str = str_replace((array)$replace, ' ', $str);
		}
	
		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
	
		return $clean;
	}
	
		
	
	function validPhone($number){
		$regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";	
		$valid = preg_match( $regex, $number ) ? true:false;
		return $valid;
	}
	
	function formatPhone($number){
		$f = substr($number,0,2);
		$on =array("1-","1(","1 ","1+");
		$useNumber = $number;
		if(in_array($f,$on)) $useNumber = substr($number,2);
		return  "+1".preg_replace("/[^0-9]/", "",$useNumber);
	}
	
}

?>