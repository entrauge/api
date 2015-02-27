<?php 
#header("Access-Control-Allow-Origin: *");
#Errors on/off
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors',1);

if(!isset($_SESSION)){
	//session_start();
}

#----------------------------------------------------------------------
# 1. Setup Vars, Prefs etc.. that API files sometimes rely on
#----------------------------------------------------------------------
define('USE_DATABASE',1);
define('LOCALHOST',0);
define('STORAGE_FOLDER','files');
define('USER_ACCOUNT_TYPE',1);


$db=array('host'=>'localhost','name'=>'testdb','username'=>'root','password'=>'root');

define("DB_HOST",$db['host']);
define("DB_NAME",$db['name']);
define("DB_USERNAME",$db['username']);
define("DB_PASSWORD",$db['password']);	

#----------------------------------------------------------------------
# 2. AutoSetup Paths and Setup Datbase Information
#----------------------------------------------------------------------
$cp = explode('api',realpath(dirname(__FILE__)));
define('SERVER_NAME',$_SERVER['SERVER_NAME']);
//define('BASE','http://'.SERVER_NAME);
define('ROOT_PATH',$cp[0]);
define('FILE_STORAGE',ROOT_PATH.STORAGE_FOLDER.'/');

//INCLUDE THE ADMIN SETTINGS
if(!defined('DB_HOST')){
	require_once(ROOT_PATH.'admin_settings/include.php');
	require_once(ROOT_PATH.'admin/includes/db_functions.php');	
}

#----------------------------------------------------------------------
# 3. class files required for this api system to work
#----------------------------------------------------------------------
require_once('classes/class.api.php');
require_once('classes/class.controller.php');
require_once('classes/class.auth.php');
require_once('classes/class.cookie.php');
require_once('classes/class.request.php');
require_once('classes/class.table.php');


#----------------------------------------------------------------------
# 4. 3rd party helper and other.
#----------------------------------------------------------------------
require_once('utils/class.StringUtils.php'); 
require_once('utils/class.uploader.php');
#require_once('classes/class.log.php'); 


#---------------------------------------------------------------------
# 5. Auto setup paths and include the main setup.php 
#----------------------------------------------------------------------

#---------------------------------------------------------------------
# 6. include the main setup.php 
#----------------------------------------------------------------------
require_once('setup.php');


$AUTH_BYPASS=false;
//easy listen to logout here below
if (Request::get("action") == "logout"){
	$logout = Auth::logout();	
	$loggedOut = true;
}	


?>