<?

/*

------- Examples
Login:
Auth::login();


*/


class Auth{
		
		public static  $quit_key ="logout";
		public static  $login_time=NULL;
		public static  $default_time = 443265;
		public static  $authorized=false;
		public static  $level="secure";
		public static  $auth_key = "u_id";
		public static  $auth_hash = "u_hash";
		public static  $auth_type = "u_type";
		public static  $login_name = "u_name";
		public static  $auth_secret = "fx5Jlap6uZY3";
		public static  $cookies_exclude = array("email","username");
		public static  $cookie_path = "/";
		public static  $cookie_folder = "private";
		public static  $cookie_domain = false;
	
		####################################
		
		function __construct(){
				
		
		}
		
		public static function setup(){
			if(defined('AUTH_KEY')){
				Auth::$auth_key = AUTH_KEY;
			}
			if(defined('AUTH_HASH')){
				Auth::$auth_hash = AUTH_HASH;
			}
			if(defined('AUTH_SECRET')){
				Auth::$auth_secret = AUTH_SECRET;
			}
			if(defined('COOKIE_PATH')){
				Auth::$cookie_path = COOKIE_PATH;
			}
			if(defined('COOKIE_DOMAIN')){
				Auth::$cookie_domain = COOKIE_DOMAIN;
			}else{
				$serv = $_SERVER['SERVER_NAME'];
				$serv = str_replace('www.',"", $serv);
				Auth::$cookie_domain= ".".$serv;
			}
			
			
			
		}
		####################################
		public static function check(){
		
			Auth::setup();
			
			if(Auth::$authorized){
				Auth::$authorized=true;	
			}else{
	
				if(Auth::cookiesExist()){
				
					$ts = Auth::$auth_secret;
					$tk = Cookie::get(Auth::$auth_key);
					$th = Cookie::get(Auth::$auth_hash);
					$try = sha1($ts.$tk);
					Auth::$authorized = ($try==$th) ? true : false;
				}else{
				
					if(Auth::$level=="secure"){
						Auth::$authorized=false;	
					}
				}
			}
			return Auth::$authorized;
		}
	
		#Page Listener to see if their ok/or logout
		####################################
		public static function listen($secure=true,$location=false){
			$send = ($secure) ? $location : false; 
		    
			if(Request::equals(Auth::$quit_key,"true")){
				Auth::$authorized=false;
				Auth::logout($send);
			}else{
				if(Auth::check()){
				
				}else{
					Auth::logout($send);
				}
			}
		
		}
		
		public static function setType($key_val){
			//Auth::$login_time = $log_time;
			$secret = Auth::$auth_secret;
			//Auth::cookieBake(Auth::$auth_type,sha1($secret.$key_val));
			Auth::cookieBake(Auth::$auth_type,$key_val);
		}
		
		
		public static function getType(){
			return Cookie::get(Auth::$auth_type);
		}
		
		
		public static function isType($typeID){
			if(Auth::cookiesExist()){
				$ts = Auth::$auth_secret;
				$tt = Cookie::get(Auth::$auth_type);
				$try = sha1($ts.$typeID);
				//return ($try==$tt);
				return ($typeID==$tt);
			}else{
				return false;
			}
		}
		
		####################################
		public static function restrictType($typeID=1,$page='',$logout=true){
			//lol = logout or location
			
			if(!Auth::isType($typeID)){
				if($logout==true){
					Auth::logout($page);
				}else{
					//send to another page without loggin out
					header("Location: $page");
				}
			}	
		}
		
		####################################
		public static function expireTime($t){
			Auth::$login_time = $t;
		}
		
		####################################
		
		public static function cookiesExist(){
			return (  Auth::cookieIs(Auth::$auth_key) and Auth::cookieIs(Auth::$auth_hash) );
		}
		
		public static function isLoggedIn(){
			return Auth::cookiesExist();
		}
		
		#####################################

		public static function setUsername($str){
			Auth::cookieBake(Auth::$login_name,$str);
		}
			
		#####################################
		
		public static function login($key_val=NULL,$log_time=36000){
			//Auth::$login_time = $log_time;
			$secret = Auth::$auth_secret;
			Auth::cookieBake(Auth::$auth_key,$key_val);
			$token= sha1($secret.$key_val);
			Auth::cookieBake(Auth::$auth_hash,$token);
			return $token;
		}
		
		#####################################
		public static function logout($send=false){
		    
			
			
			Auth::setup();
		
			Auth::cookieDelete(Auth::$auth_key);
			Auth::cookieDelete(Auth::$auth_hash);
		
			#delete all cookies except for the cookies_exclude array
			if(Cookie::lives()){
				$jar = Cookie::jar();
			    foreach($jar as $n=>$v){
					//print $n."-vs-";
    			    if(!in_array($n,Auth::$cookies_exclude)){
    			        Auth::cookieDelete($n);
    			     
    			    }
    			}
			}
			
			if($send){
				header("Location: $send");
			}
		}
		
		#match up a user that is logged in
		#####################################
		public static function keymatch($data){
			return (Cookie::equals(Auth::$auth_key,$data));
		}
		
		public static function cookieIs($name=false){
			if(Cookie::exists($name) ){
				return (Cookie::get($name)=="") ? false : true;
			}else{
				return false;
			}
		}
		
		#Bakes a cookie by name
		#####################################
		public static function cookieBake($cName,$cData,$kill=false){
				
				$useTime = (Auth::$login_time!=NULL) ? Auth::$login_time : Auth::$default_time;
				$ut = time() + $useTime;
				
				Auth::setup();
				
				if($kill==false){
					
					Cookie::set( $cName,$cData,$ut,Auth::$cookie_path, Auth::$cookie_domain );
        
				}else{
					Cookie::kill($cName, Auth::$cookie_path, Auth::$cookie_domain );
					Cookie::kill($cName, Auth::$cookie_path, $_SERVER['SERVER_NAME'] );
					Cookie::kill($cName, Auth::$cookie_path, ".".$_SERVER['SERVER_NAME'] );
				}
		}
		
		#set a cookie, adds to cookie array and more
		#####################################
		public static function setCookie($cName,$cData){
			Auth::cookieBake($cName,$cData);
		}
		
		#SAME as above setCookie. 
		public static function set($cName,$cData){
			Auth::setCookie($cName,$cData);
		}
			
		#Get Cookie, adds to cookie array and more
		#####################################
		public static function get($cName,$default=""){
			return Cookie::get($cName,$default);
		}
		
		#Deletes a cookie by name
		#####################################
		public static function cookieDelete($cName){
			Auth::cookieBake($cName,false,true);
		}
		
		#SHORTCUTS
		//userID, username shortcuts
		public static function getUserID(){
			return Auth::get(Auth::$auth_key);
		}
		public static function username(){
			return Auth::get(Auth::$login_name);
		}
	

}


?>