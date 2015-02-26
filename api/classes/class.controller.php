<?php

/*
This is a shortcut class for using within section method classes and interacting
with the API, and Table class faster.
*/
class Controller {
	

	function __construct(){
		
	}
	
	//will set a variable for the API on the api var stack
	function set($n,$v,$def=false){
		$this->API->set($n,$v,$def);
	}
	
	//will get a var from the api var stack
	function get($v,$def=false){
	 	$fv =  $this->API->get($v);
		return ($fv) ? $fv : $def;
	}
	
	//will send a response to it 
	function send($a,$b=NULL){
		$sendTable = isset($this->tb) ? $this->tb : false;
		$c = ($b==NULL) ? $sendTable: $b;
		$this->API->result($a,$c);
	}
	
	function time($a){
		$this->API->customTime =$a;
	}

	//new way of sending one error code and using the customControllers $codes api for err messages
	function error($code,$extra=88){
		
		//added this extra='api' for passing errors from an api inside an api
		if($extra=='api'){
			$obj = $code->response['errors'];
			$code = $code->response['code'];
		}else{
		 	if(is_numeric($code)){
	 			if(isset($this->codes[$code])){
					$obj=$this->codes[$code];
				}else{
					if(isset($this->API->STATUS_CODES[$code])){
						$obj =$this->API->STATUS_CODES[$code];
					}else{
						$obj ='API Error Code Not Found';	
					}
				     
				}	
		 	}else{
		 		//it's a string;
			 	$obj = $code;
			 	$code=$extra;
		 	}
		}
		//this added so only one error code at a time can be sent out
		if($this->API->rsp_code < 2){
			if(is_array($obj)==false) $obj = array($obj);
			$this->API->pushErrors($obj,$code);
		}
	}
	
}

?>