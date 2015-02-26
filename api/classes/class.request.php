<?php

/*

Static Request 
madrid@entrauge.com
Last Update:  JAN 21 2014

About:
This class is a for accessing the HTTP request GET and POST all in one
It omits the COOKIE request and orders the merged object depending on its nature

*/

class Request {
	
	
	public static	function getObject(){
	    //pushes them in a certain order.
	    $merged = ($_POST) ? $_POST + $_GET: $_GET + $_POST;
	    $_REQUEST = $merged;
	    return $merged;
	}
	
	//simple function to get the value
	public static function get($key, $default = NULL) {
	    $_REQUEST_OBJ = Request::getObject();
		return ( Request::exists($key)) ? Request::clean($_REQUEST_OBJ[$key]) : $default;
	}
	
	//simple function to get the value
	public static function string() {
	    $_REQUEST_OBJ = Request::getObject();
		$str ="";
		foreach($_REQUEST_OBJ as $f=>$v){
			$str .="$f=$v";
		}
		return $str;
	}
	
	public static function clear() {
		if($_POST){
			foreach($_POST as $f=>$v){
				$_POST[$f]=NULL;
			}
		}
		if($_GET){
			foreach($_GET as $f=>$v){
				$_GET[$f]=NULL;
			}
		}
	}
	
	public static function getArray($str){

		if(is_array(Request::get($str) )){
			return  Request::get($str);
		}else{
			$a =  explode(",",Request::get($str));
			$f = array();
			foreach($a as $v){ 
				if($v!=""){ $f[] = $v; }
			}
			return $f;
		}
	}
	
	//short cut for the request id
	public static function id($useClaim=true){
		$resid = false;
		return Request::get("id");
	}
	
	//sets our request object with a value
	public static function set($aKey,$value=null){
		if(is_array($aKey)){
			foreach($aKey as $f=>$v){
				$_GET[$f]=$v;
			}
		}else{
			$_GET[$aKey]=$value;
		}
	}
	

	// this cleans our request object for pages
	public static function clean ($value, $default = null) {
		
	    if(is_array($value)){
			$aTMP = array();
			foreach($value as $f=>$v){
				if (get_magic_quotes_gpc()){
					
					$f = trim(stripslashes($f)); 
					$v = trim(stripslashes($v)); 
				}else{
					//added new
					$f = trim($f); 
					$v = trim($v);
				}

				
				$aTMP[$f]=$v;
			}
			$value = $aTMP;
	    }else{
			if (get_magic_quotes_gpc()){
				$value = trim(stripslashes($value));  
			}else{
				$value = trim($value);  
			}
	     	
	    }
		return ($value == '') ? $default : $value;
	}
	
	
	//returns if something was posted
	public static function POST(){
		return $_POST;
	}

	
	//checks to see if a request key exists
	public static function exists($key){
	    $_REQUEST_OBJ = Request::getObject();
		if(isset($_REQUEST_OBJ[$key])){
			$rs = ($_REQUEST_OBJ[$key]!='') ? true : false;
			//$rs= true; //strict mode
		}else{
			$rs = false;
		}
		return $rs;
	}
	
	//checks to see if its empty.. 
	public static function isEmpty($var){
		
		if(is_array(Request::get($var))){
			$result =  (count(Request::get($var))==0) ? true : false;
		}else{
			$v = Request::get($var);
			//revisit empty vars
			//$result =  ((trim($v)==NULL) && ($v==!0) ) ? true : false;
			$result =  ((trim($v)==NULL)  ) ? true : false;
		}
	    return $result;
	}
	
	//checks to see if a request object equals what you pass as the value
	public static function equals($key,$val){
	     $_REQUEST_OBJ = Request::getObject();
		if(Request::exists($key)){
			$v = ($_REQUEST_OBJ[$key]==$val and ($_REQUEST_OBJ[$key]!="NULL")) ? true : false;
		}else{
			$v = false;
		}
		return $v;
	}
}

?>