<?
###############################################################
/*

madrid@entrauge.com
TODO
-make databaseToXML be able to be custom to the array names instead of generic items
-function:  customGate function you can add ..maybe more than 1  that gates
-function: customCallback when api is finished?



CHANGELOG
3.10.11
-cleaned up callMethod, perform, valid functions...

*/

###############################################################
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

	var $useAuth=true;
	var $mode = "internal"; // use interal or external
	var $page =  array(); // for page array
	var $format = "default";
	var $aFormats = array("rest","php_serial","json");
	var $aRequiredFields = array();
	var $aPageErrors = array();
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
	var $camelDB= array();
	var $customTime=false;

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
		$this->STATUS_CODES[200] ="Method data returned false";
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
    function punchIn(){
       $this->execute_start =  $this->getmicrotime();
       return $this->execute_start;
    }
    function punchOut(){
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
					//keep this as legacy for specialized.take off for others
					if(!Request::exists($field)){
					//	Request::set($field,$value);
					}
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
            $this->punchIn();
        
			if($this->mode!="internal"){
				$uMethod = Request::get('method',false);
				if(Request::get('format')){
					$this->format=Request::get('format');
				}else{
					//?
					//$this->format=$this->format;
				}
			}else{
				$uMethod = $runMethod;
			}
			//lowercase
			$this->method = strtolower($uMethod);
		
			if($this->isValidMethod()){

				#use params if sent /./foix
				if($aParams!=NULL){
					$this->param_overide=$overide;
					$this->params($aParams);
				}
					
				/*
				#start
					could be cleaned up because some of this is set elsewhere in isValidMethod()
				*/
				list($family,$action) = explode(".",$this->method);
				$short = $this->theAPI[$family];
				$aAction = $short['methods'][$action];
				$this->authType = $aAction['auth_type'];
				$this->methodAccess = $aAction['access'];
				//# end
				
				if($this->checkAccess($this->methodAccess)){
					
					if($this->checkRequired($aAction)){
					
						$this->perform();
						
						$this->status(1,99);
						//is it ok?

		
					//	$this->rspData=true;
						$dat = ($this->rspData) ? $this->API_RESULTS : "";
						$this->setRSP($dat);
						
						if($this->mode=="internal"){
							//route them thru stuff such as json if needed er
							if($this->format!='default'){
								return $this->apiResponse(false);
							}else{
								return $this->API_RESULTS;
							}
							
						}
					}
					
				}
			}

			#RUN API RESPONSES
			if($this->mode!="internal"){
				
				$this->format='json';
				
				if($this->performed){
					
					if(($this->response['failed']==false) and (!$this->API_RESULTS)) {
						$this->status(0,200);
						$this->setRSP();
					}
				}
				
				#check response format
				if(!$this->formatExists()){
					$this->status(0,108);
					$this->setRSP();
					#return back to rest for errors
					$this->format="rest";	
				}
				
				
				$this->apiResponse(true);
				
			}else{
				
				
				if($this->performed){
					
				}
			}
		
	}
	###############################################################
	
	function formatExists(){
	 	return in_array($this->format,$this->aFormats);
	}
	
	function camel($str){
		$aStr = explode("_",$str);
		$n='';
		$x=0;
		foreach($aStr as $s){
			if($x==0){
				$n .=$s;
			}else{
				$n .= ($s=="id") ? strtoupper($s) : ucfirst($s);
			}
			$x++;
		}
		return $n;
	}
	//this is for our rest xml from database
	function databaseToXML($arr,$r=true){
		$tmp='';
		if(is_array($arr)){
			foreach($arr as $node=>$nVal){
				$att_home = "";
				if(is_numeric($node)){
					$nodeName = "item";
					$att_home = 'order="'.$node.'"';
					$att_home = "";
				}else{
					$nodeName = $node;
					$att_home = "";
				}

				if(is_array($nVal)&&$r==true){

					$tmp .= "<$nodeName$att_home>\n";
					$tmp .= $this->databaseToXML($nVal);
					$tmp .= "</$nodeName>\n";

				}else{

					$inPack = (stristr(Request::get('strict'), $node)); 
					if(Request::exists('strict') && $inPack){
						$go = true;
					}else{
						$go = ( Request::exists('strict') ) ? false : true;
					}
					//pass
					if($go){
						if(is_numeric($nVal) || (strlen($nVal) < 2)){
							$valContent = $nVal;
							
						}else{
							$valContent = ($nVal!="") ? '<![CDATA['.$nVal.']]>' : '';
						}
						$node = is_numeric($node) ? "item" : $node ;
						//$node = $this->camel($node);
						$tmp .= "	<$node>$valContent</$node>\n";
					}
				}

			}
			
		}else{
			
				if(is_numeric($arr) || (strlen($arr) < 2)){
					$valContent = $arr;
				}else{
					$valContent = ($arr!="") ? '<![CDATA['.$arr.']]>' : '';
				}
				$tmp .= "$valContent\n";
		}
		return $tmp;
	}
	###############################################################
	### responses
	###############################################################
	function apiResponseOpen(){
		$html ='';
		switch(strtolower($this->format)){
			//need to work on data delivery here
			#Rest Format Response
			case "rest":
				header('Content-Type: text/xml');
				$a = $this->response;
				$html .='<?xml version="1.0" encoding="utf-8" ?>';
				$html .="\n";
				$etime = ($this->show_time) ? 'time="'.$a['time'].'"' : "";
				$html .= '<rsp failed="'.$a['failed'].'" '.$etime.' code="'.$a['code'].'"  msg="'.$a['message'].'">';
			
				//
				$html .="\n";
				#if it hasnt failed, inlcuded the meat
					if($this->response['failed']!=true){
						//print_r($this->API_RESULTS);
						if(!Request::equals('data','false')){
							$html .="<data>\n";
							$html .= $this->databaseToXML( $this->API_RESULTS );
							$html .="</data>\n";
						}
	
					}
	
			break;
			
			#Php Serial Format Response
			case "php_serial":
				$na = array();
				$aOutput = array_merge($this->response,$na);
				if($this->response['failed']!=true){
					
					if(!Request::equals('data','false')) $aOutput['data'] = $this->API_RESULTS;
				}
				 $html .= serialize($aOutput);
			break;
			
			#Json Format Response
			case "json":
	
				$na = array();
				$aOutput = array_merge($this->response,$na);
				if($this->response['failed']!=true){
					
					if(!Request::equals('data','false')) $aOutput['data'] = $this->API_RESULTS;
				}
				
				if(function_exists('json_encode')){
					 $html .= json_encode($aOutput);
				}else{
					require_once("class.json.php");
					$html .= FastJSON::encode($aOutput);
				}
	
			break;
		}
		return $html;
	}
	
	###############################################################
	function apiResponseClose(){
		$html ='';
		switch(strtolower($this->format)){
			case "rest":
				$html .= '</rsp>';
			break;
		}
		return $html;
	}
	
	###############################################################
	
	function apiResponse($dump=false){
		#rest header and init
		$tmpHTML ='';
		$tmpHTML .= $this->apiResponseOpen();
		#footer
		$tmpHTML .= $this->apiResponseClose();
		if($dump){
			print $tmpHTML;
		}else{
			return $tmpHTML;
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
			//now does it have acces??
			$result =  (($this->methodAccess ==1) and ($this->mode=="external")) ? false : true;
			if(!$result){
				#EXTERNAL ERRORS
				$this->status(0,103);
				$this->setRSP();
			}
		}
		return $result;
	}
	
	
	function checkAuth(){
		
		if($this->useAuth){
			$result = Auth::check();
		}else{
			return false;
		}
		return $result;
	}
	
	
	
	###############################################################
	function isAuthorized(){
		
		$auth = $this->authType ;
		
		switch($auth){
			case 0:
				#no authorization needed
				$proceed =true;
			break;
			case 1:
				#1: check if user is logged in
				$proceed = $this->checkAuth();
			break;
			case 2:
				#2: Proprietary to the user that is logged in
				$proceed = $this->checkAuth();

			break;
		}
		
		if(!$proceed){
			#EXTERNAL ERRORS
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
	
	
	###############################################################
	function hasErrors(){
	    return $this->aPageErrors;
	}
	###############################################################
	function clear(){
	   $this->statusSet=false;
	}
	###############################################################
	
	function clearErrors(){
		 $this->statusSet=false;
		 $this->rsp_code=99;
		 $this->aPageErrors=array();
		 $this->status(0,99);
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
	    	
			$this->punchOut();	
		
			if((!$this->rsp_status) and($passData)){
				$this->status(1);
			}
			
			
			$this->aMain['failed'] = ($this->rsp_status=='fail') ? true : false;
			
			//$this->aMain['valid']= (!empty($this->aRequiredFields)) ? false : true;
			
			$this->aMain['code'] = $this->rsp_code;
			
			$this->aMain['msg'] = $this->stat_MESSAGE;
		
			$this->aMain['errors'] = (!empty($this->aPageErrors)) ? $this->aPageErrors : false;
			
			if($this->customTime) $this->aMain['query_time'] =$this->customTime;
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
			
			$pass=0;
			/*
			if(is_numeric($this->API_RESULTS)) $pass++;
			if($this->API_RESULTS!="") $pass++;
		 	if($this->API_RESULTS!=NULL) $pass++;
		 	*/
			if(!$this->API_RESULTS){
				
				if($dbObj!=NULL){
					
					$dbError = $dbObj->doctor();
				
					if($dbError!=false){
						$this->pushErrors(array($dbError),"50".$dbObj->errorCode,$dbObj->errorMsg);
					}else{
						if($this->statusSet==false){
							$this->status(0,200);
						}
						
					}
				}else{
					//not using a database on that method
				}
			}
	}

	###############################################################
	
	function perform(){
	
			$this->performed=true;	
			//set tmp
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
					list($family,$action) = explode(".",$this->method);
				}

				if(isset($family)){
					$class = $this->theAPI[$family]['classname'];
					$family_exists = include_class($family);
					
				}
				
				
				if($family_exists){
					$this->methodClass = new $class;
					$this->methodClass->API = $this;
					$aMethod = $this->theAPI[$family]['methods'];
					$inLib =false;
					
					if(isset($aMethod[$action])){
					//	$action_exists=$inLib;
						$inLib=true;
					}
					$this->methodAction = $action;
					if($inLib){
						if(method_exists($this->methodClass,$action)){
							$action_exists=true;
						}
					}
				}	
			}else{
				#EXTERNAL ERRORS - method required
				$this->status(0,101);
				$this->setRSP();
			}
			
		$result =   ($family_exists and $action_exists) ? true : false;
		
		if(!$result){
			#EXTERNAL ERRORS - method doesn't exist in our php class files
			$this->status(0,105);
			$this->setRSP();
		}
		return $result;

	}
	
	###############################################################
	
	function returnAPI(){
		return $this->theAPI;
	}
	
	###############################################################
	
	function printAPI(){
		print_r( $this->theAPI );
	}

	
}

/*
	How api setup works...
*/
class CodeMap{
	
	public static $map;
	public static $cf; //curr family
	
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

	public static function addFamily($familyName,$requires=null){
		CodeMap::$map = CodeMap::getMap();
		CodeMap::$cf = strtolower($familyName);
		CodeMap::$map[CodeMap::$cf]['title']= CodeMap::$cf;
		CodeMap::$map[CodeMap::$cf]['classname']= CodeMap::$cf;
		CodeMap::$map[CodeMap::$cf]['methods'] = array();
		CodeMap::$map[CodeMap::$cf]['requires'] =$requires;

	}

	public static function addMethod($title,$req=NULL,$private=1,$auth=false){
	        //add params later
			$r_title = $title;
			if($req!=NULL){
				$req = (is_array($req) ) ? $req : array($req);
			}
			$title = strtolower($title);
		 	$aMethod = array('title'=>$title,'r_title'=>$r_title,'auth_type'=>$auth,'required'=>$req,'access'=>$private);
			CodeMap::$map[CodeMap::$cf]['methods'][$title] = $aMethod;
			//$this->map		
	}
}

?>