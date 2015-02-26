<?
/*

madrid@entrauge.com
TODO
2015-26-02

Removed old stuff

*/

//auto include
function include_class($fn){
	///see if family exist
	if(CodeMap::familyExists($fn)){
		$c = dirname(__FILE__)."/../methods/class.".$fn.".php";
	}else{
		$c = dirname(__FILE__)."/class.".$fn.".php";
	}
	$exists = file_exists($c);
	if($exists) require_once($c);
	return $exists;
}

spl_autoload_register(function ($class)
{	
	$f = strtolower($class);
	include_class($f);
});


class API{
	
	var $api_family=false;
	var $api_action=false;
	
	var $mode = "internal"; // use interal or external
	var $page =  array(); // for page array
	var $aRequiredFields = array();
	var $aPageErrors = array();
	var $format = "default";
	var $pageErrorSuffix = "is required";
	var $response = array();
	var $performed = false;
	var $STATUS_CODES = array();
	var $rsp_code = null;
	var $theAPI = null;
	var $aMain = array();
	var $method = '';
	var $insert_id = NULL;
	var $_vars =array();
	var $rspData =false;
	var $statusSet =false;
	var $methodAction=false;
	var $methodClass=false;
	var $API_RESULTS=false;

	//execute times
	var $show_time = 1; 
	var $execute_time=NULL;
	var $execute_start=NULL;

	###############################################################
	function API(){
	
		//gets the config api setup from code map already defined in other script
		$this->theAPI = CodeMap::getMap();
		
		//API Status Codes
		$this->STATUS_CODES[99] ="Success";
		$this->STATUS_CODES[100] ="API Key is Required";
		$this->STATUS_CODES[101] ="A Method is Required";
		$this->STATUS_CODES[102] ="Method is Invalid";
		$this->STATUS_CODES[103] ="Method Access Is Private";
		$this->STATUS_CODES[104] ="Required Fields Are Missing";
		$this->STATUS_CODES[105] ="Method doesn't exist in library";
		$this->STATUS_CODES[108] ="Response API Format Unknown";
		$this->STATUS_CODES[200] ="Data returned false";
		$this->STATUS_CODES[300] ="Must Be Logged In";
		
	}
	
	
    /*
	Timer functions for in and out.    
	*/
    function getmicrotime(){ 
        list($msec, $sec) = explode(" ",microtime()); 
        $v = ((float)$msec + (float)$sec);
        return $v; 
    }
    function timeIn(){
       $this->execute_start =  $this->getmicrotime();
       return $this->execute_start;
    }
    function timeOut(){
		$time = $this->getmicrotime() - $this->execute_start;
		$this->execute_time = $time;
		return $this->execute_time;
     }


	//params needs to be re-made
	##############################################################
	function params($aData){
		if(is_array($aData)){
			foreach($aData as $field=>$value){
				if($this->param_overide!=true){
					//for special vars
					$this->set($field, $value);
				}else{
					//override and set the global Requester
					$this->set($field, $value);
					Request::set($field,$value);
				}
			}
		}
	}
	
	/*
	###############################################################
	## getter setters  for our custom _vars setup
	###############################################################
		API->set('total',35);
		data::get('total'); //will search out for _vars[count] before it
		
		Use like
		1. outside
			callMethod('comments.setLikes',array('total'=>30)); 
		2. inside the class.comments.php
			$total = data::get('total');
		
	*/
	//this is for special vars//getter
	function get($key,$def=false){
		$rKey = strtolower($key);
		//need a force request thing here
		$v = (isset($this->_vars[$key])) ? $this->_vars[$key] :  Request::get($key,$def);
		return $v;
	}
	//this is for special vars//setter
	function set($n,$v,$def=false){
		$this->_vars[$n]=$v;
	}
	
	
	###############################################################
	function callMethod($runMethod="",$aParams=NULL,$overide=false){
		      
            //start the exe time
            $this->timeIn();
        
			//set the method
			$uMethod = ($this->mode!="internal") ? Request::get('method',false) : $runMethod;
			$this->method = strtolower($uMethod);
			
			//see if that file exists, class exists, function exists.
			if($this->isValidMethod()){
				
				//use params if sent
				if($aParams!=NULL){
					$this->param_overide=$overide;
					$this->params($aParams);
				}
					
				$short = $this->theAPI[$this->api_family];
				$aAction = $short['methods'][$this->api_action];
				$this->authType = $aAction['auth_type'];
				$this->methodAccess = $aAction['access'];
				//# end
				
				if($this->checkAccess($this->methodAccess)){
					
					if($this->checkRequired($aAction)){
					
						$this->perform();
						
						$this->status(1,99);
		
						$dat = ($this->rspData) ? $this->API_RESULTS : "";
						$this->setRSP($dat);
						
						if($this->mode=="internal"){
							//if they used $api->format="json" return json else array
							if($this->format!='default'){
								return $this->formatResponse(false);
							}else{
								return $this->API_RESULTS;
							}
							
						}
					}
					
				}
			}

			#RUN API RESPONSES
			if($this->mode!="internal"){
				
				if($this->performed){
	
					if(($this->response['failed']==false) and (!$this->API_RESULTS)) {
						$this->status(0,200);
						$this->setRSP();
					}
				}
				
				$this->formatResponse(true);
				
			}
		
	}

	
	###############################################################
	
	function formatResponse($dump=false){
		
		$json_str ='';
		$na = array();
		$aOutput = array_merge($this->response,$na);
		if($this->response['failed']!=true){	
			if(!Request::equals('data','false')) $aOutput['data'] = $this->API_RESULTS;
		}			
		if(function_exists('json_encode')){
			 $json_str .= json_encode($aOutput);
		}else{
			require_once("class.json.php");
			$json_str .= FastJSON::encode($aOutput);
		}
		
		if($dump){
			print $json_str;
		}else{
			return $json_str;
		}
	}
	

	###############################################################
	
	function varMissing($var){
		$missing=true;
		if($this->get($var)){
			$missing=false;
		}else{
			$missing=Request::isEmpty($var);
		}
		
        return $missing;
	}
	
	###############################################################

	function checkAccess(){
		
		//first ask if authorized?
		$result= $this->isAuthorized();
		
		if($result){
			//now does it have private vs public access ??
			$result =  (($this->methodAccess == 1) and ($this->mode=="external")) ? false : true;
			if(!$result){
				#EXTERNAL ERRORS
				$this->status(0,103);
				$this->setRSP();
			}
		}
		return $result;
	}
	
	
	###############################################################
	
	function isAuthorized(){
		$proceed = ($this->authType==1) ? Auth::check() : true;
		if(!$proceed){
			$this->status(0,300);
			$this->setRSP();
		}
		return $proceed;
	}


	###############################################################
	
	function checkRequired($aAction){
		
			$aReq = $aAction['required'];

			$sErrors = 0;
			if($aReq){
				foreach($aReq as $field=>$pageError){
					$fieldErrorText=false;
					//use to determine if that field page error exists
					$eval_field = (is_numeric($field)) ? $pageError : $field;
					if(stristr("_",$eval_field)){
						$eval_field = split("_",$eval_field);
						$eval_field = $eval_field[0];
					}

					$fieldErrorText = (is_numeric($field)) ? ucfirst($eval_field)." $this->pageErrorSuffix" : $pageError;
		
					if($this->varMissing($eval_field)){
							//array_push($this->aRequiredFields,$eval_field);
						if($fieldErrorText){
							array_push($this->aPageErrors,$fieldErrorText);
						}
						
						$sErrors++;
					}else{
						
					}
				}
			}
			
			$result =  ($sErrors==0) ? true:false;
			if(!$result){
				#EXTERNAL ERRORS
				$this->status(0,104);
				$this->setRSP();
			}
			return $result;
	}
	
	
	
	###############################################################
	function pushErrors($pfErrors=NULL,$code=0,$msg="Method Failed"){
			
			if($pfErrors!=NULL){
				foreach($pfErrors as $field=>$pageError){
					$eval_field = (is_numeric($field)) ? $pageError : $field;
						//$fieldErrorText = (is_numeric($field)) ? ucfirst($eval_field)." $this->pageErrorSuffix" : $pageError;
						//array_push($this->aRequiredFields,$eval_field);
						$fieldErrorText=$pageError;
						if($fieldErrorText){
							array_push($this->aPageErrors,$fieldErrorText);
						}
				}
			}
			
			//
			$this->status(0,$code,$msg);
	}
	

	function status($stat=0,$code=0,$customMsg=""){
		if($this->statusSet==false){
			if($stat==0){ 
				$status="fail";
				if($code==1){$code=0;} 
			}else{ 
				$status="ok"; 
				if(!$code){ $code=1; }
			}
			
			$this->rsp_status =$status;
			$this->rsp_code= $code;
			$this->stat_MESSAGE = ($customMsg!="") ? $customMsg : $this->STATUS_CODES[$code];
			$this->statusSet=true;
		}
	}
	

	
	###############################################################
	
	function setRSP($passData=""){
	    	
			$this->timeOut();	
		
			if((!$this->rsp_status) and($passData)){
				$this->status(1);
			}
					
			$this->aMain['failed'] = ($this->rsp_status=='fail') ? true : false;
			
			$this->aMain['code'] = $this->rsp_code;
			
			$this->aMain['msg'] = $this->stat_MESSAGE;
		
			$this->aMain['errors'] = (!empty($this->aPageErrors)) ? $this->aPageErrors : false;
			
			if($this->show_time) $this->aMain['time'] = $this->execute_time;
			
			if($passData){
				$this->aMain['data'] = $passData;
			}
		
			$this->response = $this->aMain;
			
			$this->errors = $this->response['errors'];
			
			$this->checkResponse();
			
			return $this->response;
	}
	
	
	//o->getResponse('message)
	function getResponse($rType){
	 	return isset($this->response[$rType]) ? $this->response[$rType] : false;
	}
	
	function checkResponse(){
	
		if($this->response['code']==99){
			$this->errors = false;
			return true;
		}else{
			//maybe change this back to regular.. but having the api error message is nice
			$this->errors = ($this->response['errors']!=false) ? $this->response['errors'] : array("API: ".$this->response['message']);	
			return false;
		}
	
	}
	
	
	function result($aData,$dbObj=NULL){
	
		$this->API_RESULTS = $aData;

		//inherit the insert id of newly added content
		if(isset($dbObj->insert_id)) $this->insert_id = $dbObj->insert_id;

	
		if(!$this->API_RESULTS){
			//if database object exists
			if($dbObj!=NULL){
				
				$dbError = $dbObj->doctor();
			
				if($dbError!=false){
					$this->pushErrors(array($dbError),"50".$dbObj->errorCode,$dbObj->errorMsg);
				}else{
					if($this->statusSet==false){
						$this->status(0,200);
					}
					
				}
			}
		}
	}

	###############################################################
	
	function perform(){
	
		$this->performed=true;	
		$action = $this->methodAction;
	 	$this->API_RESULTS_RETURN = $this->methodClass->$action();
	
		if(!$this->API_RESULTS){
			$this->API_RESULTS = $this->API_RESULTS_RETURN;
		}
	}
	

	###############################################################
	
	function isValidMethod(){
		
		$family_exists = false;
		$action_exists = false;
			
		if($this->method){	
	
			if(stristr($this->method,".")){
				list($this->api_family,$this->api_action) = explode(".",$this->method);
			}
			
			if(isset($this->api_family)){
				$class = $this->theAPI[$this->api_family]['classname'];
				$family_exists = include_class($this->api_family);
			}

			if($family_exists){
				$this->methodClass = new $class;
				$this->methodClass->API = $this;
				$aMethod = $this->theAPI[$this->api_family]['methods'];
				$inLib =false;
				
				if(isset($aMethod[$this->api_action])){
					$inLib=true;
				}
				$this->methodAction = $this->api_action;
				if($inLib){
					if(method_exists($this->methodClass,$this->api_action)){
						$action_exists=true;
					}
				}
			}	
		}else{	
			#EXTERNAL ERRORS - method required
			$this->status(0,101);
			$this->setRSP();
		}
			
		$result =  ($family_exists AND $action_exists) ? true : false;
		
		if(!$result){
			#EXTERNAL ERRORS - method does not exist in our php class files
			$this->status(0,105);
			$this->setRSP();
		}
		return $result;

	}

}

/*
CodeMap Setup
*/
class CodeMap{
	
	public static $map;
	public static $cf;
	
	public static function staticCodeMap(){
		CodeMap::$map = array();
	}
	
	public static function getMap(){
		return CodeMap::$map;
	}
	
	public static function familyExists($fn){
		$e = false;
		if(CodeMap::$map){
			foreach(CodeMap::$map as $f=>$v){
				if($f==strtolower($fn)){
					$e = true;
				} 
			}
			return $e;	
		}
	}

	/*
		CodeMap::addFamily('news');
	*/
	public static function addFamily($familyName){
		CodeMap::$map = CodeMap::getMap();
		CodeMap::$cf = strtolower($familyName);
		CodeMap::$map[CodeMap::$cf]['title']= CodeMap::$cf;
		CodeMap::$map[CodeMap::$cf]['classname']= CodeMap::$cf;
		CodeMap::$map[CodeMap::$cf]['methods'] = array();
	}
	
	/*
		CodeMap::addMethod('update',array('id'),array('private'=>0));
	*/
	public static function addMethod($sTitle,$aRequired=NULL,$aParams=array()){

		$iPrivate = (isset($aParams['private'])) ? $aParams['private'] : 1;
		$iAuth = (isset($aParams['auth'])) ? $aParams['auth'] : 0;
		$sTitle = strtolower($sTitle);
		
	 	$aMethod = array(
		 	'title'=>$sTitle,
		 	'auth_type'=>$iAuth,
		 	'required'=>$aRequired,
		 	'access'=>$iPrivate
	 	);
		CodeMap::$map[CodeMap::$cf]['methods'][$sTitle] = $aMethod;	
	}
}

?>