<?


#----------------------------------------------------------------------
# test api Version 2
#----------------------------------------------------------------------
CodeMap::addFamily("test");
CodeMap::addMethod('getList',false);


#----------------------------------------------------------------------
# USERS API
#----------------------------------------------------------------------
CodeMap::addFamily("users");
CodeMap::addMethod('makePassword',array('password'),3);

	#--Typical Login
CodeMap::addMethod('signup',array('email','password'),3);
CodeMap::addMethod('login',array('email','password'),3);
CodeMap::addMethod('signuporlogin',array('email','password'),3); //both

CodeMap::addMethod('logout',false,3);
	#--Social Login Stuff
CodeMap::addMethod('fbLogin',array('email'),3); //
CodeMap::addMethod('socialClear',false,3);
CodeMap::addMethod('updateSocialEmail',array('email'),3,2);
CodeMap::addMethod('facebook_to',false,3);
CodeMap::addMethod('facebook_callback',false,3);
CodeMap::addMethod('twitter_to',false,3);
CodeMap::addMethod('twitter_callback',false,3);

	#--Get Profile, Lists of users etc..
CodeMap::addMethod('getAll',false,3);
CodeMap::addMethod('getMyProfile',false,3,2);
CodeMap::addMethod('getProfile',array('id'),3);
CodeMap::addMethod('getSummary',false,3);
CodeMap::addMethod('fixStats',false,3);
	#--Make changes
CodeMap::addMethod('update',false,3,2); //generic...pass which fields
CodeMap::addMethod('updateProfile',false,3,2);
CodeMap::addMethod('setPhoneNumber',array('phone'),3);
CodeMap::addMethod('updateAccountInfo',false,3,2);
CodeMap::addMethod('uploadProfileImage',false,3);
	#confirming users and changing/forgot passwords
CodeMap::addMethod('confirm',array('code'),3);
CodeMap::addMethod('useToken',array('token'),3);
CodeMap::addMethod('updateEmailDigest',array('digest'),3);
CodeMap::addMethod('testParseID',false,3);
CodeMap::addMethod('resendUserConfirmEmail',array('email'),3);
CodeMap::addMethod('forgotPassword',array('email'),3);
CodeMap::addMethod('checkLookupCode',array('code'),3);
CodeMap::addMethod('resetPassword',array('code','password','password_confirm'=>'Confirm your password'),3);
CodeMap::addMethod('changePassword',array('pass_current'=>'Your current password is required','password'=>'A new password is required','pass_confirm'=>'Verify your new password'),3,2);





?>
