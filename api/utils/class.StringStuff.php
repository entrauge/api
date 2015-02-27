<?php
/*

Methods:

$seo = StringStuff->clean()
StringStuff->slug();
*/



class StringStuff {
	
	function __construct(){
		
	}
	
	function slug($str){
			$str = Request::get($str,$str);
			$temp = $str;
			// Lower case
			$temp = strtolower($temp);
			$temp = trim($temp);
			// Replace spaces with a '-'
			$temp = str_replace(" - ", "-", $temp);
			$temp = str_replace(" ", "-", $temp);
			$temp = str_replace("&","and",$temp);
			$temp = str_replace("&eacute;","e",$temp);
			$temp = Seo::replaceChars($temp);
			// Loop through string
			$result = '';
			for ($i=0; $i<strlen($temp); $i++) {
				if (preg_match('([0-9]|[a-z]|-)', $temp[$i])) {
					$result = $result . $temp[$i];
				}
			}
			// Return filename
			return $result;
	}
	
	function replaceChars($str){	
		$search = explode(",","ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u");
		$replace = explode(",","c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u");
		$urlTitle = str_replace($search, $replace, $str);
	    return $urlTitle;
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