<?php

class Cookie {
	
	function __construct(){
	
	}
	//tells if 1 particular cookie exists
	public static function exists($cName=false){
	    if(isset($_COOKIE[$cName])){
	        return ($_COOKIE[$cName]=="") ? false : true ;
	    }else{
	        return false;
	    }
	}
	
	public static function equals($cName,$cVal){
		if(Cookie::exists($cName)){
			$v = ($_COOKIE[$cName]==$cVal and ($_COOKIE[$cName]!="")) ? true : false;
		}else{
			$v = false;
		}
		return $v;
	}
	
	//says any cookie array even exists
	public static function lives() {
		return isset($_COOKIE);
	}
	
	//shoot back our cookie jar array
	public static function jar() {
		return ( Cookie::lives() ) ? $_COOKIE : NULL;
	}
	
	//simple function to get the value of a cookie
	public static function get($key, $default = "") {
		return ( Cookie::exists($key)) ? $_COOKIE[$key] : $default;
	}
		
	//simple function to set the value
	public static function set($cName, $cData,$cTime ='',$cPath="/",$cDomain="") {
		
		if($cTime=='') $cTime = time() + 44326;
		
		//check for local
		if($cDomain==".localhost"){
			//$cDomain = 'NULL';
		}
		
		 setcookie($cName,$cData,$cTime,$cPath,$cDomain);
		 $_COOKIE[$cName]=$cData;
	}
	
	//kills the value of a cookie
	public static function kill($cName,$cPath="/",$cDomain=""){
		$cDomain = ($cDomain=="") ? Auth::$cookie_domain : $cDomain;
		$cDomain  = str_replace("www.",".",$cDomain);
		//print $cDomain;
		setcookie($cName,false,time()-443265,$cPath,$cDomain);
		$_COOKIE[$cName]= false;
		
	}


}

?>