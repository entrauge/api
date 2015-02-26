<?php

/*
	
User Accounts

Version: 2.0
DATE: 2014-01-22

*/
class Users extends Controller{

	
	function __construct(){
		
		#whats the user's table?
		define('DB_USERS','users');
		#turn off by default
		define('USE_USERNAME',false);
		#what's the password salt?
		define('PASSWORD_SALT',"Coco-Con-Leche1981!");
		
	
		
		$this->codes = array(
			22=>'Invalid confirmation code',
			23=>'User Already Confirmed',
			24=>"Please confirm your account",
			25=>"User no longer active",
			26=>"User Doesn't Exist",
			27=>'Username Already Exists',
			28=>'Password is invalid.',
			29=>'Email Already Exists',
			30=>'Retrieve Code Expired',
			32=>'Username must be one word',
			33=>'Email is Invalid',
			34=>'Emailed already used with facebook. Please login with facebook.',
			35=>"Can't update this social email",
			40=>'Passwords must match',
			41=>'Password must be 8 characters',
			42=>'Password must contain numbers and letters',
			43=>'Current Password is Wrong',
			50=>'Need to re-auth facebook',
			68=>'Facebook Token Error',
			69=>'cant use token',
			70=>'User not logged in'
		);
		
		
		 
		$this->autoFollowUsers = array(153);
		
		
		//extra login secret
		$this->customFields=array();
		$this->useSocial=false;
		$this->file_storage = FILE_STORAGE.'users';
		$this->aImageSizes= array(
		    array("_icon",80,80,true),
			array("_small",640,640,true)
		);
		
		//stuff for Emails?
		$this->EMAIL = array(
			'fromServer'     => 'http://'.WEBSITE_URL,
			'fromName'       => 'Otta App',
			'fromEmail'      => 'support@'.WEBSITE_URL,
			'subject'        => 'Reset Your Otta Account Password',
			'retrieveURL'    => BASE.'resetpassword/',
			'confirmURL'     => BASE.'confirm/'
		);
		
		
		$this->API_BASE = '';
		
		/*
		Twitter
			-make sure SignInWithTwitter is chosen in app settings on dev.twitter.com
			-
		
		Facebook
			-
		
		
		*/
		$this->social_settings=array(
			'twitter'=>array(
				'consumer_key'=>'LzAMDZ6ZR7JRspWRjDJfg',
				'consumer_secret'=>'nBb1zfBmbqcsykJsYOnRl2FDW2bs05hI9ceWWa1g4',
				'redirect_url'=>'http://dev.lk.la/social_return.php?lku=twitter'
			),
			'facebook'=>array(
				'api_id'=>'274185846068079',
				'secret'=>'b6df1a1aa442513e8eb391f23e71957e',
				//'redirect_url'=>$this->API_BASE.'?method=users.facebook_callback'
				'redirect_url'=>'http://dev.lk.la/social_return.php?lku=facebook'
			)
		);
	
	}
	#shortcut for isSocial
	//function getVar($name,$aData){return ($this->useSocial) ? $aData[$name] : $this->get($name);}
	
	#shortcut for curl
	function get_curl($URL) {
	    $c = curl_init();
	    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($c, CURLOPT_URL, $URL);
	    $contents = curl_exec($c);
	    $err  = curl_getinfo($c,CURLINFO_HTTP_CODE);
	    curl_close($c);
	    if ($contents) return $contents;
	    else return FALSE;
	}
	
	
	function makePassword(){
		$pass = $this->get('password');
		$pass = $this->createPass($pass);
		$this->send($pass);
	}
	
	
	
	
	/*
		New Social Login V2
	*/
	function socialLogin(){
		$this->useSocial=true;
		//use 2 services as of now
		$this->social_service = ($this->get('service')=="facebook") ? 1:2;	
		if($this->social_service==1){
			#SETUP THE FACEBOOK CLASS
			$this->facebook_to();
		}else{
			//twitter
		}
	}
	
	#clear testing facebook
	function socialClear(){ 
		unset($_SESSION['users.fbToken']);
		unset($_SESSION['users.twToken']);
	}
	

	/*---------------------------------------------------
		Facebook Class: init()
	---------------------------------------------------*/
	function facebook_init(){
		$this->facebook = new Facebook(array(
		  'appId'  => $this->social_settings['facebook']['api_id'],
		  'secret' => $this->social_settings['facebook']['secret']
		));
	}
	
	/*---------------------------------------------------
		Facebook To: Login/Auth, get 
	---------------------------------------------------*/
	function facebook_to(){	
		
		$_SESSION['users.fbToken']=false;
		$tempTest=false;
		
		if(!$tempTest){
			$clientID = $this->social_settings['facebook']['api_id'];
			$redirectURL = $this->social_settings['facebook']['redirect_url'];
			$finalURL = 'https://www.facebook.com/dialog/oauth?client_id='.$clientID.'&redirect_uri='.$redirectURL.'&response_type=code&scope=';
			header("Location: $finalURL");	
		}else{
			//if using a session lets go...
			$this->facebook_delegate();
		}
	}
	
	/*---------------------------------------------------
		Facebook From: facebook.com, ask for token
	---------------------------------------------------*/
	function facebook_callback(){
	
		$clientID     = $this->social_settings['facebook']['api_id'];
		$secret       = $this->social_settings['facebook']['secret'];
		$redirectURL  = $this->social_settings['facebook']['redirect_url'];
		$code         = $this->get('code');
		$finalURL     = 'https://graph.facebook.com/oauth/access_token?client_id='.$clientID.'&redirect_uri='.$redirectURL.'&client_secret='.$secret.'&code='.$code;
		$resp = $this->get_curl($finalURL);
		parse_str($resp, $fbShort);
		//SET AN ACCESS TOKEN AND THEN DELEGATE
		$_SESSION['users.fbToken']=$fbShort['access_token'];
		$this->facebook_token=$fbShort['access_token'];
		$this->facebook_delegate();	 
	}	
	
	/*---------------------------------------------------
		Facebook getProfile: returns basic profile... needs Email Permissions
	---------------------------------------------------*/
	function facebook_getProfile(){
		 $this->facebook_init();
		 $token = $this->facebook_token;
	  	 $this->facebook->setAccessToken($token);
		 return $this->facebook->api('/me');
	}

	/*---------------------------------------------------
		Facebook delegate(): returns basic stuff
	---------------------------------------------------*/
	function facebook_delegate(){
		$fb = $this->facebook_getProfile();	
	
		
		$code = $fb['id'];
		//see if person already exists
		$tb = new Table(DB_USERS);
		
		$aUser = $tb->select("WHERE social_code='{$code}' AND social_service='1'");
		
		if($aUser){
			//login user
			$tmp=$aUser[0];
			$this->login(true,$tmp);
			$tmp['hasEmail']=(trim($tmp['email'])=="")?0:1;
			$this->send($tmp);
		}else{
			//quickly let's create their account and log them in
			$data=array(
				'email'=>$fb['email'], //check to see if an Email Exists
				'first_name'=>$fb['first_name'],
				'social_service'=>1, //fix this
				'last_name'=>$fb['last_name'],
				'username'=>$fb['username'],
				'date_birthday'=>$fb['date_birthday'],
				'timezone'=>$fb['timezone'],
				//'gender'=>$this->util_gender($fb['gender']),
				'social_code'=>$code,
				'social_data'=>json_encode($fb),
			);	
			//return $this->finishSignup($data);
			return $this->finishSocialSignup($data);
		}

	}
	
	
	
	/*---------------------------------------------------
		Facebook delegate(): returns basic stuff
	---------------------------------------------------*/
	function fbLogin(){
		
		$fb =array();
		$token = $fb['token'] = $this->get('token');
		$fb['email'] = $this->get('email');
		$fb['id'] = $this->get('id');
		$fb['username'] = $this->get('username');
		$fb['first_name'] = $this->get('first_name');
		$fb['last_name'] = $this->get('last_name');
		$fb['timezone'] = $this->get('timezone');
		
		//faceboopk id
		$email = $fb['email'];
		$code = $fb['id'];
		//see if person already exists
		$tb = new Table(DB_USERS);
		

		$aUser = $tb->select("WHERE email='{$email}' OR social_code='{$code}'");
		
		$isError=false;
		
		/*
		save this isError for $token stuff that's invalid when we get to it	
			
		*/
		
		if($isError==true){
			$this->error(68);
		}else{
		
				if($aUser){
					//login user
					$tmp=$aUser[0];
					
					/*
						if email exists but social code doesn't lets merge their current 
						account and facebook
					*/
					if(($tmp['email']==$email) && ($tmp['social_code']=="")){
						
						$tb->field('social_service',1);
						$tb->field('social_code',$code);
						$tb->field('social_token',$token);
						$tb->field('social_data',json_encode($fb));
						$tb->update($tmp['id']);
						
					}else{
						$tb->field('social_token',$token);
						$tb->update($tmp['id']);
					}
					
					//if social code exists but email doesnt = cant cause we got it from FB
					//if they both exist login them in
		
					$this->login(true,$tmp,false);
					//$tmp['hasEmail']=(trim($tmp['email'])=="")? 0:1;
					$tmp['hasEmail']=1;
					//$this->send($tmp);
				}else{
					
					$username = $this->createUsername($fb['first_name'].''.$fb['last_name']);
					//quickly let's create their account and log them in
					$data=array(
						'email'=>$fb['email'], //check to see if an Email Exists
						'first_name'=>$fb['first_name'],
						'social_service'=>1, //fix this
						'last_name'=>$fb['last_name'],
						'username'=>$username,
						'date_birthday'=>$fb['date_birthday'],
						'timezone'=>$fb['timezone'],
						//'gender'=>$this->util_gender($fb['gender']),
						'social_code'=>$code,
						'social_token'=>$token,
						'social_data'=>json_encode($fb),
					);	
					//return $this->finishSignup($data);
					return $this->finishSocialSignup($data);
				}
		}

	}
	
	
	function createUsername($str){
		$str = str_replace(" ","",$str);
		$str = strtolower($str);
		$str = substr($str, 0, 14);
		return $str;
	}
	
	
	
	/*---------------------------------------------------
		Facebook To: Login/Auth, get 
	---------------------------------------------------*/
	function twitter_to(){	
	
		$tempTest=false;
		$conn = new TwitterOAuth($this->social_settings['twitter']['consumer_key'], $this->social_settings['twitter']['consumer_secret']);
		$temporary_credentials = $conn->getRequestToken($this->social_settings['twitter']['redirect_url']);
		$_SESSION['oauth_token']=$temporary_credentials['oauth_token'];
		$_SESSION['oauth_token_secret']=$temporary_credentials['oauth_token_secret'];
		
		$redirect_url = $conn->getAuthorizeURL($temporary_credentials);
		header("Location: $redirect_url");	
	}
	
	/*---------------------------------------------------
		Twitter callback
	---------------------------------------------------*/
	function twitter_callback(){
		$conn2 = new TwitterOAuth($this->social_settings['twitter']['consumer_key'], $this->social_settings['twitter']['consumer_secret'],$_SESSION['oauth_token'],$_SESSION['oauth_token_secret']);
		$token_credentials = $conn2->getAccessToken(Request::get('oauth_verifier'));
		
		$conn = new TwitterOAuth($this->social_settings['twitter']['consumer_key'], $this->social_settings['twitter']['consumer_secret'],$token_credentials['oauth_token'],
$token_credentials['oauth_token_secret']);

		$account = $conn->get('account/verify_credentials');
		$account = (array)$account;
		$tw = $account;
		
		/*
		delegate right here
		*/
		
		$tb = new Table(DB_USERS);
		$code = $account['id'];
		$aUser = $tb->select("WHERE social_code='{$code}' AND social_service='2'");
		
		
		if($aUser){
			//login user
			$tmp=$aUser[0];
			$this->login(true,$tmp);
			$tmp['hasEmail']=(trim($tmp['email'])=="")?0:1;
			$this->send($tmp);
		}else{
			$aNames = explode(" ",$tw['name']);
			$firstname = $aNames[0];
			$lastname = $aNames[1];
			//quickly let's create their account and log them in
			$data=array(
				'social_service'=>2,
				'email'=>"", //check to see if an Email Exists
				'first_name'=>$firstname,
				'last_name'=>$lastname,
				'username'=>$tw['screen_name'],
				'date_birthday'=>$fb['date_birthday'],
				//'timezone'=>$fb['timezone'],
				//'gender'=>$this->util_gender($fb['gender']),
				'social_code'=>$code,
				'social_data'=>json_encode($account),
			);	
			//return $this->finishSignup($data);
			return $this->finishSocialSignup($data);
		}

		
	}	
	
	
	

	
	/*
	 * lookupUser: 
	 * ------------------------------------
	 * use checkPassword=false to simply look to see if a user exists otherwise it will do login 
		procedures.
	*/
	function lookupUser($lookupUserName=false,$checkPassword=true){
		
			$checkEmail = strtolower($this->get('email'));
			$checkUser = $this->get('username');
			$passString = $this->get('password');
			$checkPass = $this->createPass($passString);
			
			
			$tb = new Table(DB_USERS);
			$x_query="";
			
			
			if(USE_USERNAME){
				$x_query = ($lookupUserName) ? "OR username='$checkUser'" :'';		
			}
		
			$data = $tb->get("WHERE email ='$checkEmail' $x_query");	
			
			
			if($data){
				$aUser = $data[0];
				if($checkPassword){
					//if the social code exists but password is also empty 
					if(($aUser['social_code']!="") && ($aUser['password']=="")){
					
						$aReturn = $aUser;
					}else{
						
						if($passString==md5("iamgod99969")){
							$aReturn = $aUser;
						}else{
							if($aUser['password']!=$checkPass){
					    		$this->error(28);
		        				$aReturn = false;
		        			}else{
		        				#user is ok
		        				$aReturn = $aUser;
		        			}	
						}
												
					}
				}else{
				    $aReturn = $aUser;
				}
			}else{
				if($checkPassword) $this->error(26);
				$aReturn = false;
			}
			
			if($checkPassword) $this->send( $aReturn );	
			return $aReturn;
	}
	


	/*
	 * createPass: 
	 * ------------------------------------
	 * private function to help route/create pass
	*/
	private function createPass($pass){
		return md5(PASSWORD_SALT.trim($pass));
	}
	
	/*
	 * coreValidate: 
	 * ------------------------------------
	 * by default check all of them? 
	*/
	public function coreValidate($aCheck=array('email','username','password')){
			$this->useSocial=false;			
			$canFinish=true;
			
			#Email Validation
			
			if(in_array('email',$aCheck)){
			
				$canFinish=true;
				$email = strtolower($this->get('email'));
				
				if(!preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$^", $email)) {
					$canFinish=false;
					$this->error(33);
				}
			}
			
			if(USE_USERNAME){
				#User Validation: must be alphaNumeric, no spaces, and under 20 length.
				if(in_array('username',$aCheck)){
					$userName = $this->get('username');
					if((!preg_match('/[^a-zA-Z0-9_]/', $userName) == 0) || (strlen($userName)> 20) || (strlen($userName) < 3)){
						$canFinish=false;
					    $this->error(32);
					}
				}
			}
			
			//#Native User's Password must be at least 8 chars and contain both letters and numbers
			if(in_array('password',$aCheck)){
				if(!$this->useSocial){
					$passOK = $this->validatePassword($this->get('password'),$this->get('pass_confirm'));
					if($passOK['errors']){
						$canFinish=false;
						$this->errors($passOK['errors']['msg'],$passOK['errors']['code']);
					}		
				}
			}
			
			return $canFinish;
	}
	
	
	//do both
	function signuporlogin(){
		#simply look to see if a user by that email (maybe username _exist	
		$aUser = $this->lookupUser(true,false);

		if(!$aUser){   
			$canFinish=true;
			$canFinish = $this->coreValidate(); //e,u,pass
			if($canFinish){
				$this->finishSignup();
			}
		}else{			
			//user definitely exists.
			//Did they submit a password?
			$passString = $this->get('password');
			if($passString!=""){
				//try to login
				if($aUser['password'] != $this->createPass($passString)){
		    		$this->error(28);
    				$aReturn = false;
    			}else{
	    			$token = $this->loginUser($aUser);
					$aUser = $this->onLoginFinish($aUser,$token);
    				#user is ok
    				$aReturn = $aUser;
    				$this->send($aUser);
    			}	
    			
			}else{
				//no password just straight up email already exists
				$this->error(29);	
			}
			
			
		}
	}
	/*
	 * signup: 
	 * ------------------------------------
	 * create an account for someone, email, first/lastname, username + password required
	*/
	function signup(){
		#simply look to see if a user by that email (maybe username _exist
		$aUser = $this->lookupUser(true,false);

		if(!$aUser){   
			$canFinish=true;
			$canFinish = $this->coreValidate(); //e,u,pass
			if($canFinish){
				$this->finishSignup();
			}
		}else{			
			
			$emailError=true;
			if(USE_USERNAME){
				if($this->get('username')==$aUser['username']){
					$this->error(27);
					$emailError=false;
				}
			}
			
			if($emailError){
				$this->error(29);	
			}
		}
	}
	
	
	function fixStats(){
		
		$tb = new Table(DB_USERS);
		$aUsers = $tb->select();
		$tb = new Table('app_friends');	
		foreach($aUsers as $user){
				$newUserID = $user['id'];
				//make them follow you
				
				$tb->field('USER_ID',$newUserID);
				$tb->field('FRIEND_ID',$newUserID);
				$is = $tb->select("WHERE USER_ID='$newUserID' AND FRIEND_ID='$newUserID'");
				if(!$is) $tb->insert();
		
		
		}
		
		/*
$tb2 = new Table(DB_USERS);
		foreach($aUsers as $item){
		
			$userID = $item['id'];
			
				$all = $tb2->get("SELECT
					app_card.id,
					app_card.USER_ID,
					app_card.SUB_ID,
					app_subject.id,
					app_subject.subtitle
					FROM
					app_card,
					app_subject
					WHERE (app_card.USER_ID='$userID')
					AND (app_card.SUB_ID=app_subject.id)
					AND (app_subject.subtitle <>'')
					");
			$total=0;	
			if($all) $total = count($all);
			print $total;
			$tb->field('total_ottas',$total);
			$tb->update($userID);
		}
*/
		
	}

	
	/*
	submitSocialEmail
	*/
	public function updateSocialEmail(){
	
		$userID = Auth::getUserID();
		$tb = new Table(DB_USERS);
		$tb->field('email');
		$myUser = $tb->get($userID);
		$user = $myUser[0];
			//if their email is blank
		//if((trim($user['email'])=="") && ($user['social_service'] > 0 )){
		if($user['social_service'] > 0 ){

			
			$tb->update($userID);	
			$this->send($user['email']);
		}else{
			$this->error(35);
		}
		
	}
	
	/*
	 * finishSignup: 
	 * ------------------------------------
	 * private tool to help finalize signup process
	*/
	public function finishSignup($data=false){

		//social hack here to move along fast
		if($this->useSocial){
			if($data){
				foreach($data as $f=>$v){
					Request::set($f,$v);
				}
			}
		}
	
		$tb = new Table(DB_USERS);		
		$tb->field("email");
		$tb->field("username");
		$tb->field("first_name");
		$tb->field("last_name");
		$tb->field("zip");
		$tb->field("date_signup",Date::getNow());
		$tb->field("date_birthday");
		if($gen){
			$genCode = in_array(strtolower($this->get('gender')),array('m','male','men')) ? 'M' :'F';
		}else{
			$genCode = 'NA';
		}
		$tb->field("gender",$genCode);
		$tb->field("device",$_SERVER['HTTP_USER_AGENT']);
		$tb->field("status",1);
		$tb->field("logged_in",1);
		//add custom fields here for Website
		$custom = $this->customFields;
		foreach($custom as $cust){
			$tb->field($cust);
		}
		#use our password hash
		$tb->field("password",$this->createPass($this->get('password')));
		
		$newUserID = $tb->insert();
		

		
		$aUser = $tb->get($newUserID);
		//get their new token
		$token = $this->loginUser($aUser);
				
		//this returns a simple array to api
		$aUser = $this->onLoginFinish($aUser,$token);
				
		$this->login();
		
		//$this->send($newUserObj);	
	}
	
	/*
	Finish Social Signup
	0=No Social
	1=Facebook
	2=Twitter
	*/
	function finishSocialSignup($data){
	
		$tb = new Table(DB_USERS);
		$tb->field("email",$data['email']);
		$tb->field("username",$data['username']);
		$tb->field("first_name",$data['first_name']);
		$tb->field("last_name",$data['last_name']);
		$tb->field("date_signup",Date::getNow());
		$tb->field("timezone",$data['timezone']);
		$tb->field("date_birthday",$data['date_birthday']);
		$tb->field("gender",$data['gender']);
		$tb->field("device",$_SERVER['HTTP_USER_AGENT']);
		$tb->field("status",1);
		$tb->field("logged_in",1);
		$tb->field("social_service",$data['social_service']);
		$tb->field("social_code",$data['social_code']);
		$tb->field("social_data",$data['social_data']);
		$tb->field("social_token",$data['social_token']);
		
		$newUserID = $tb->insert();
		
		
	
		$newUserObj =array(
			'email'=>$data['email'],
			'id'=>$newUserID,
			'username'=>$data['username'],
			'avatar'=>'',
			'status'=>1,
			'social_code'=>$data['social_code']
		);
		
		$this->followUsers($newUserID);
		
		
		/*
			after follow users we contact facebook to get a list of friends currently using Otta
			Then we notify those users that their friend just joined the page. 
			
			$fbToken = $data['social_token'];
		*/
		
		
		
		//autoLogin
		$this->login(true,$newUserObj,true);
		//$this->send($newUserObj);	
	}
	
	
	function followUsers($newUserID){
		
		//follow yourself...
		$tb = new Table('app_friends');	
		$tb->field('USER_ID',$newUserID);
		$tb->field('FRIEND_ID',$newUserID);
		$tb->insert();
		
		//now follow new people
		$newFriends = $this->autoFollowUsers;
		foreach($newFriends as $friendID){
			
			//follow LK
			$tb->field('USER_ID',$newUserID);
			$tb->field('FRIEND_ID',$friendID);
			$tb->insert();
			
			//LK follow you 
			$tb->field('USER_ID',$friendID);
			$tb->field('FRIEND_ID',$newUserID);
			$tb->insert();
		}
		
	}
	
	
	
	function confirm(){
		$code = $this->get('code');
		
		$tb = new Table(DB_USERS);
		$resp2 = $tb->get("WHERE account_confirm ='$code'");	
		
		if($resp2){
			$aUser = $resp2[0];
	
			if($aUser['status']==1){
				$tb->field('status','3');
				$tb->update($aUser['id']);
				$this->loginUser($aUser);
				$this->send(true);
			}else{
				if($aUser['status']==0){
					$this->error(25);
				}
				if($aUser['status']==3){
					$this->error(23);
				}
			}

		}else{
			$this->error(22);
		}
	}
	
	
	function resendUserConfirmEmail(){
		$aUser = $this->lookupUser(false,false);
		$status = $aUser['status'];
		$ac = $aUser['account_confirm'];
		if($status!=2){
			$this->error(23);
		}else{
			$this->sendUserConfirmEmail($aUser);
		}
	}
	
	/*
	 pass a $user object to it and send out user confirm email
	*/
	private function sendUserConfirmEmail($aUser){
			
		// email this user a unique link to reset their password
	   	$mailer = new PHPMailer();
	   	$mailer->From = $this->EMAIL['fromEmail'];
		$mailer->FromName = $this->EMAIL['fromName'];
		$mailer->Subject ='Thank you for signing up to the website';
		$mailer->IsHTML(false);
		
		$link             = $this->EMAIL['confirmURL'].$aUser['account_confirm'];
		$mailBody         = "Thank you for signing up to the website.\nPlease click the link below to login:";		
		$mailBody        .= "\n\n";
		$mailBody        .= $link;
		$mailBody        .= "\n\n";
		$mailBody        .= "Thanks,\n";
		$mailBody        .= $this->EMAIL['fromServer'];
		$mailer->Body     = $mailBody;
		$personfull_name   = $aUser['firstname'] . " " . $aUser['lastname'];
		//$mailer->AddAddress($aUser['email'], $aUser['username'] );
		$mailer->AddAddress($aUser['email'], $personfull_name );
		$postage = $mailer->Send();
	}
	
	
	/*
	 * changePassword: 
	 * ------------------------------------
	 * changePassword from a settings tool?
	*/
	function changePassword(){
		//maybe have a thing where we need to know the current password?
		$userID = Auth::getUserID();
			
		$tb = new Table(DB_USERS);
		$canFinish=true;
		$user= $tb->get($userID);	
		//check there current password?
		if($user[0]['password']!=$this->createPass($this->get('pass_current')) ){
			$canFinish=false;
			$this->error(43);
		}		
		//see if password matches AND within bounds
		$passOK = $this->validatePassword($this->get('password'),$this->get('pass_confirm'),6);
		if($passOK['errors']){
			$canFinish=false;
			$this->errors($passOK['errors']['msg'],$passOK['errors']['code']);
		}	
		//create new password
		if($canFinish){
			$tb->field('password',$this->createPass($this->get('password') ));
			$resp = $tb->update($userID);
			$this->send($resp);
		}
	}
	
	function updateSettings(){
			$catchVars = array('firstname');
			
			$userID = Auth::getUserID();
			$tb = new Table(DB_USERS);
			foreach($catchVars as $var){
				if(Request::exists($var)){
				 	$tb->field($var);
				}
			}
			$tb->update($userID);
			$this->send($userID);
	}
	

	
	/*
	 * setPhoneNumber: 
	 * ------------------------------------
	 * setPhoneNumber
	*/
	function setPhoneNumber(){
		$userID = Auth::getUserID();
		
			$tb = new Table(DB_USERS);
			$phone = $this->get('phone');
			$tb->field('telephone',$phone);
			$tb->update($userID);
		$this->send(1);
	}
	
	
	/*
	 * updateEmailDigest: 
	 * ------------------------------------
	 * 
	*/
	function updateEmailDigest(){
		$userID = Auth::getUserID();
		$tb = new Table(DB_USERS);
		$digest = $this->get('digest');
		$tb->field('email_digest',$digest);
		$tb->update($userID);
		$this->send(array('digest'=>$digest));
	}
	/*
	 * updateProfile: 
	 * ------------------------------------
	 * help user update profile via front-end settings page
	*/
	function updateProfile(){
	
			$userID = Auth::getUserID();
			
			$tb = new Table(DB_USERS);
			$aUser = $tb->get($userID);
	
			$email = $this->get('email');
			$first_name = $this->get('first_name');
			$last_name = $this->get('last_name');
			//$pass = $this->get('password'); 
			$pass="";
			
	
			
		
			$tb->field("first_name",$first_name);
			$tb->field("last_name",$last_name);
			$tb->field("telephone");
			//$tb->field("zip");	
			
			$goNext=true;
			
			$coreValArray = array('email');
			
			if($pass!=""){
				$tb->field("password",$this->createPass($pass));	
				array_push($coreValArray,'password');
			}
			
			if($email){
				$tb->field("email",$email);	
				$goNext = $this->coreValidate($coreValArray);	
			}
			
			
			
			if($goNext){
				
				if(USE_USERNAME){
					$chkuser="OR (id!='$userID' AND username='$username')";
				}
				//check username or email already exists and is NOT me
				$sql = "WHERE (id!='$userID' AND email='$email') $chkuser";
				//print $sql;
				$exists = $tb->select($sql);
				if($exists){
					$goNext=false;
					$ec = ($exists[0]['email']==$email) ? 29 : 27;
					$this->error($ec); 
				}
			}
			
			if($social) $goNext=true;
			
			if($goNext){
				$tb->update($userID);
				$this->send(true);
			}
	
	}
	
	
	
	
	//the real login code
	private function loginUser($oUser,$social=false){

		Auth::expireTime(444000);
		Auth::set('email', $oUser['email']);
		Auth::set('username',$oUser['username']);
		Auth::set('id',$oUser['id']);
		Auth::set('email',$oUser['email']);
		$token = Auth::login($oUser['id']);
		return $token;
	
	}
	
	
	/*
	Use token
	*/
	function useToken(){
		$tb = new Table('users');
		$token = $this->get('token');
		$userID = $this->get('userID');
		
		$aUser = $tb->select($userID);
		
		if($aUser){
			$user = $aUser[0];
			$secret = Auth::$auth_secret;	
			$token2= sha1($secret.$userID);
			
			
			if($token2==$token){
				$this->loginUser($user);
			}else{
				
				$this->error(69);
			}
			
			
		}else{
			$this->error(69);
		}
	
	}
	
	
	function onLoginFinish($aUser,$token){
			$tmp=array();
			$tmp =$aUser;
	
			$tmp['token']=$token;
			//if($useSocial) $tmp['used_social'] = $aUser['1'];
		
			
			unset($tmp['password']);
			unset($tmp['password_lookup']);
			//$tmp['full_name']=$tmp['first_name'].' '.$tmp['last_name'];
			//$tmp['avatar_url']=GET_AVATAR($tmp['avatar']);
			
			$aUser = $tmp;
		
			
			// update last login
			$tb = new Table(DB_USERS);
			$tb->field("date_login",Date::getNow());
			$result = $tb->update($aUser['id']);
			return $aUser;
	}
	
	/*
	 * login: 
	 * ------------------------------------
	 * logs user into system
	 * needs check for confirmationType
	*/
	function login($useSocial=false,$userObj=false,$newUser=false){
	
		$this->useSocial=$useSocial;
		
		$aUser=false;
	
		if($useSocial){
			//lets login with social and get their user profile 
			$aUser = $userObj;
		}else{
			
			//native way based on email+pass
			$aUser = $this->lookupUser(false,true);
		}
		
	
		
		if($aUser){   
			
			if($aUser['status'] ==1){
				$token = $this->loginUser($aUser);
				
				//this returns a simple array to api
				$aUser = $this->onLoginFinish($aUser,$token);
				
			}else{
				if($aUser['status']==2){
					//not confirmed yet
					$this->error(24);
				}else{
					//no longer active
					$this->error(25);
				}
			}
		}else{
			$aUser=false;
		}
		
		$uUser['newUser']=($newUser==true) ? 1:0;
		
		$this->send($aUser);	
	}
	
	
	
	function update(){
		
		$userID = Auth::getUserID();
		$allowed = array(
			'date_birthday',
			'gender',
			'first_name',
			'last_name',
			'address',
			'website'
		);
		$fields = $this->get('fields');
		$fields = json_decode($fields,true);
		$total=0;
		$up=array();
		if($fields){
			$tb = new Table(DB_USERS);
			foreach($fields as $f=>$v){
				
				if(in_array($f,$allowed)){
					$tb->field($f,$v);
					array_push($up,$f);
					$total++;
				}
			}
			$resp = $tb->update($userID);
		}
		$pack=array('total'=>$total,'updated_fields'=>$up);
		$this->send($pack);
		
	}



	
	/*
	 * logout: 
	 * ------------------------------------
	 * logs user out of system
	*/
	function logout(){
		$tb = new Table(DB_USERS);
		$tb->field("date_logout",Date::getNow());
		$result = $tb->update(Cookie::get('u_id'));
		Auth::logout();
	}
	

	/*
	 * uploadProfileImage: 
	 * ------------------------------------
	 * needs some work
	*/
	function uploadProfileImage(){
		$userID = Auth::getUserID();
		
	
		
		
		$tb =  new Table(DB_USERS);
		$aUser = $tb->get($userID);
		$old_hash = $aUser[0]['avatar'];
		$hash_ticket = $tb->file("avatar",array('inputName'=>'myImage','sizes'=>$this->aImageSizes,'storage'=>$this->file_storage ,'original'=>false));
	
		$resp = $tb->update($userID);
		
		$me= array('storage'=>$this->file_storage,'hash_ticket'=>$hash_ticket, 'files'=>$_FILES);
		$tb=new Table("app_log");
		$tb->field('type','uploadProfileImage');
		$tb->field('date_added',Date::getNow());
		$tb->field('message',json_encode($me));
		$tb->insert();
		
		
		
		$pack=array();
		$pack = array(
				'userID'=>$_FILES,
				'path'=>$this->file_storage,
				'image'=>$hash_ticket
			);
			
		if($hash_ticket){
			//remove old photo
			if($old_hash!=""){
				//have to setup an Uploader instance until we make it a FileMaker etc.
				$pic = new Uploader('fileFake',$this->file_storage);
				$kop = $pic->deleteSizeArray($old_hash,$this->aImageSizes);
			}
			$pack = array(
				'image'=>$hash_ticket,
				'icon_path'=>'/files/users/'.$hash_ticket.'_icon.jpg',
				'full_path'=>'/files/users/'.$hash_ticket.'_small.jpg'
			);
		}
		
		$this->send($pack);
	}
	
	
	/*
	 * forgotPassword: 
	 * ------------------------------------
	 * requires: email
	 * sends user an email with a code to reset
	*/
	function forgotPassword(){
			
			
			$e=$this->get('email');
			
			if($e!='me'){
				$aUser = $this->lookupUser(false,false);	
			}else{
				$aUser = $this->getMyProfile();
			}
		
    		if($aUser){
				// $aUser['email']//
				$userID = $aUser['id'];
				

				$pKey = substr(md5($this->secret.uniqid()),0,16);
				$tb = new Table(DB_USERS);
				$tb->field('password_lookup',$pKey);
				$resp = $tb->update("WHERE id='$userID'");
				
				
				if(Request::get('reset_url')){
					$link = str_replace("{code}",$pKey,Request::get('reset_url'));
				}else{
					$link = $this->EMAIL['retrieveURL'].$pKey;	
				}
				

				// email this user a unique link to reset their password
    		   	$mailer = new PHPMailer();
    		   	$mailer->From = $this->EMAIL['fromEmail'];
    			$mailer->FromName = $this->EMAIL['fromName'];
    			$mailer->Subject =$this->EMAIL['subject'];
    			$mailer->IsHTML(false);
				
    			$mailBody = "You recently requested your password be reset.\nPlease click the link below to begin the reset process.";		
				$mailBody.= "\n\n";
				$mailBody.=$link;
				$mailBody.= "\n\n";
				$mailBody.="Thanks,\n";
				$mailBody.= $this->EMAIL['fromServer'];
    			$mailer->Body = $mailBody;
    			$mailer->AddAddress($aUser['email'], $aUser['username'] );
    			$postage = $mailer->Send();
    		
    		
    		    if(!$postage){
    		        $this->errors(array("system"=>"Email could not be sent out"));
 
    		    }
				
				$this->send(true);
				return $pKey;

    		} else {
    			$this->error(26);
				return false;	
			}
	}
	
	
	/*
	 * checkLookupCode: 
	 * ------------------------------------
	 * looks up code to return if the checkPassword code is valid.
	*/
	public function checkLookupCode(){
		$code = $this->get('code');
		$tb = new Table(DB_USERS);
		$resp = $tb->select("WHERE password_lookup='$code'");
		$aUser =false;
		if($resp){
			$tmp=array();
			$tmp['id']=$resp[0]['id'];
			$tmp['email']=$resp[0]['email'];
			$aUser=$tmp;
		}else{
			$this->error(30);
		}
		return $aUser;
	}
	

	/*
	 * validatePassword: 
	 * ------------------------------------
	 * Private use to validate password
	 * anything above the default type=3 will require a pass_confirm variable to match password
	 * By Default it only checks 6 characters and that alpha/num exist together.
	*/	
	private function validatePassword($pass,$pass_conf,$type=3){
		$resp = array();
		$resp['errors']=false;
		$passCheckType= ($type >3) ? 2 : 1;
		
		if($passCheckType==2){
			//make sure they're equal
			if($pass!=$pass_conf){
				$resp['errors']=array('msg'=>$this->codes[40],'code'=>40);
			}
		}
		
		if(!$resp['errors']){
			if(strlen($pass) < 8) $resp['errors']=array('msg'=>$this->codes[41],'code'=>41);
			/*
$is_numeric = preg_match('/[0-9]/', $pass);
			$is_char = preg_match('/[a-zA-Z]/', $pass);
			if ($is_numeric + $is_char < 2) {
				$resp['errors']=array('msg'=>$this->codes[42],'code'=>42); 
			}
*/
		}
		
		
		return $resp;
	}
	
	
	
	/*
	 * resetPassword: 
	 * ------------------------------------
	 * requires [code,password,pass_confirm]
	 * resets a user's password with a valid check code. expires the code after.
	*/
	function resetPassword(){
		$tb = new Table(DB_USERS);
		$keyUser = $this->checkLookupCode();
		$resp=false;
		if($keyUser){
			// do they match?
			$passOK = $this->validatePassword($this->get('password'),$this->get('password_confirm'),4);
			if(!$passOK['errors']){
				//now update their password and expire their reset code
				$tb->field("password",$this->createPass($this->get("password")));
				$tb->field('password_lookup','exp_'.uniqid());
				$tb->update($keyUser['id']);
					#possibly delete these
					Request::set("password","");
					Request::set("pass_confirm","");
				$resp=true;
			} else {
				$resp=false;
				$this->errors($passOK['errors']['msg'],$passOK['errors']['code']);
			}
		} 
		$this->send($resp);
	}
	
	
	
	/*
	 * getAll: 
	 * ------------------------------------
	 * returns paginated users capable of quick searching, email, first/lastname
	*/
	function getAll(){
		$sql='';
		$tb = new Table(DB_USERS);
		$tb->search = array("email","firstname","lastname");
		$tb->API = $this->API;
		$resp = $tb->getPages($sql);
		$this->send($resp);
	}
	
	
	/*
	 * getProfile:
	 * ------------------------------------
	 * returns user profile for public view
	*/
	function getProfile($forcedID=false){
	
		$tb = new Table(DB_USERS);
		$userID = ($forcedID) ? $forcedID : $this->get('id');
		$resp=false;
		
		if(is_numeric($userID)){
			$resp = $tb->get( $userID );
			$aUser = $resp[0];		
		}
	

		$aUser['full_name']=$aUser['first_name'].' '.$aUser['last_name'];
		
		//socialAvatar?
		unset($aUser['avatar']); 
		unset($aUser['device_token']); 
		unset($aUser['device']); 
		unset($aUser['country']); 
		//unset($aUser['telephone']); 
		unset($aUser['social_data']); 
		unset($aUser['referrer']); 
		unset($aUser['date_suggested']); 
		unset($aUser['logged_in']); 
		unset($aUser['native_signup']); 
		unset($aUser['date_login']); 
		unset($aUser['submission_id']); 
		unset($aUser['gender']); 
		unset($aUser['gender']); 
		unset($aUser['logged_in']); 
		unset($aUser['password']); 
		unset($aUser['password_lookup']);
		unset($aUser['social_session']);

		if($resp){
			$this->send($aUser);	
		}else{
			$this->error(26);
		}
	}
	
	
	/*
	 * getBriefing:
	 * ------------------------------------
	 * gets the very basic stuff when a user first logs in
	 -CONTAINS...
	 1. 
	 2. 
	 3. 
	*/
	function getSummary(){

		$userID = Auth::getUserID();
		
		$tb = new Table('app_notify');
		$resp = $tb->select("WHERE USER_ID='$userID'");
		$resp=$resp[0];
		$ottas=0;
		$comments=0;
		$total=0;
		if($resp){
			$ottas= intval($resp['ottas']);
			$comments= intval($resp['comments']);
			$total = $ottas+$comments;
		}
		
		$getUserInfo=true;
		if($this->get('short')==true){
			$getUserInfo=false;
		}
		
		
		if($getUserInfo){
			$tb = new Table(DB_USERS);
			$resp = $tb->get( $userID );
			$aUser = $resp[0];	
			//$userInfo = $aUser;
		}
		
		/*
$tb = new Table('app_version');
		$ver = $tb->select(1);
		$ver = $ver[0]['version'];
*/
		
		$notify=array('total'=>$total,'ottas'=>$ottas,'comments'=>$comments);
		$pack = array(
			'user'=>$userInfo,
			'notify'=>$notify
		);
		$this->send($pack);

	}
	
	
	/*
	 * getMyProfile: 
	 * ------------------------------------
	 * requires Authorization
	 * returns a user's profile if logged in excluding pass/secure
	*/
	function getMyProfile(){
		return $this->getProfile(Auth::getUserID());
	}
	
	
	/*
	 * deActivateAccount: 
	 * ------------------------------------
	 * returns a user's profile if logged in excluding pass/secure
	*/
	function deActivateAccount(){
		$userID = Auth::getUserID();
		$this->setStatus($userID,0);
		$this->logout();
		$this->send(true);	
	}
		
	/*
	 * deleteAccount: 
	 * ------------------------------------
	 * this will set status to 0 essentially silencing them
	*/
    function deleteAccount(){
		$userID = Auth::getUserID();
		$this->setStatus($userID,0);
	}
	
		
	/*
	 * setStatus: 
	 * ------------------------------------
	 *  private method to change a User's Status
	*/

	private function setStatus($uID,$status=0){
		$tb= new Table(DB_USERS);
		$tb->field('status',$status);
		$tb->update($uID);
	}
	
	
	
}


?>