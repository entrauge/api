<?php



class Form {

	var $errors = false; //legacy saved
	var $aErrors = array();
	var $editing=false;
	var $tabIndex = 1;
	var $state_list = array ('Alabama'=>"AL",
			'Alaska'=>"AK",
			'Arizona'=>"AZ",
			'Arkansas'=>"AR",
			'California'=>"CA",
			'Colorado'=>"CO",
			'Connecticut'=>"CT",
			'Delaware'=>"DE",
			'District Of Columbia'=>"DC",
			'Florida'=>"FL",
			'Georgia'=>"GA",
			'Hawaii'=>"HI",
			'Idaho'=>"ID",
			'Illinois'=>"IL",
			'Indiana'=>"IN",
			'Iowa'=>"IA",
			'Kansas'=>"KS",
			'Kentucky'=>"KY",
			'Louisiana'=>"LA",
			'Maine'=>"ME",
			'Maryland'=>"MD",
			'Massachusetts'=>"MA",
			'Michigan'=>"MI",
			'Minnesota'=>"MN",
			'Mississippi'=>"MS",
			'Missouri'=>"MO",
			'Montana'=>"MT",
			'Nebraska'=>"NE",
			'Nevada'=>"NV",
			'New Hampshire'=>"NH",
			'New Jersey'=>"NJ",
			'New Mexico'=>"NM",
			'New York'=>"NY",
			'North Carolina'=>"NC",
			'North Dakota'=>"ND",
			'Ohio'=>"OH",
			'Oklahoma'=>"OK",
			'Oregon'=>"OR",
			'Pennsylvania'=>"PA",
			'Rhode Island'=>"RI",
			'South Carolina'=>"SC",
			'South Dakota'=>"SD",
			'Tennessee'=>"TN",
			'Texas'=>"TX",
			'Utah'=>"UT",
			'Vermont'=>"VT",
			'Virginia'=>"VA",
			'Washington'=>"WA",
			'West Virginia'=>"WV",
			'Wisconsin'=>"WI",
			'Wyoming'=>"WY");

	// this is backwards, flip before using
	var $country_list = array(
			  "GB" => "United Kingdom",
			  "US" => "United States",
			  "AF" => "Afghanistan",
			  "AL" => "Albania",
			  "DZ" => "Algeria",
			  "AS" => "American Samoa",
			  "AD" => "Andorra",
			  "AO" => "Angola",
			  "AI" => "Anguilla",
			  "AQ" => "Antarctica",
			  "AG" => "Antigua And Barbuda",
			  "AR" => "Argentina",
			  "AM" => "Armenia",
			  "AW" => "Aruba",
			  "AU" => "Australia",
			  "AT" => "Austria",
			  "AZ" => "Azerbaijan",
			  "BS" => "Bahamas",
			  "BH" => "Bahrain",
			  "BD" => "Bangladesh",
			  "BB" => "Barbados",
			  "BY" => "Belarus",
			  "BE" => "Belgium",
			  "BZ" => "Belize",
			  "BJ" => "Benin",
			  "BM" => "Bermuda",
			  "BT" => "Bhutan",
			  "BO" => "Bolivia",
			  "BA" => "Bosnia And Herzegowina",
			  "BW" => "Botswana",
			  "BV" => "Bouvet Island",
			  "BR" => "Brazil",
			  "IO" => "British Indian Ocean Territory",
			  "BN" => "Brunei Darussalam",
			  "BG" => "Bulgaria",
			  "BF" => "Burkina Faso",
			  "BI" => "Burundi",
			  "KH" => "Cambodia",
			  "CM" => "Cameroon",
			  "CA" => "Canada",
			  "CV" => "Cape Verde",
			  "KY" => "Cayman Islands",
			  "CF" => "Central African Republic",
			  "TD" => "Chad",
			  "CL" => "Chile",
			  "CN" => "China",
			  "CX" => "Christmas Island",
			  "CC" => "Cocos (Keeling) Islands",
			  "CO" => "Colombia",
			  "KM" => "Comoros",
			  "CG" => "Congo",
			  "CD" => "Congo, The Democratic Republic Of The",
			  "CK" => "Cook Islands",
			  "CR" => "Costa Rica",
			  "CI" => "Cote D'Ivoire",
			  "HR" => "Croatia (Local Name: Hrvatska)",
			  "CU" => "Cuba",
			  "CY" => "Cyprus",
			  "CZ" => "Czech Republic",
			  "DK" => "Denmark",
			  "DJ" => "Djibouti",
			  "DM" => "Dominica",
			  "DO" => "Dominican Republic",
			  "TP" => "East Timor",
			  "EC" => "Ecuador",
			  "EG" => "Egypt",
			  "SV" => "El Salvador",
			  "GQ" => "Equatorial Guinea",
			  "ER" => "Eritrea",
			  "EE" => "Estonia",
			  "ET" => "Ethiopia",
			  "FK" => "Falkland Islands (Malvinas)",
			  "FO" => "Faroe Islands",
			  "FJ" => "Fiji",
			  "FI" => "Finland",
			  "FR" => "France",
			  "FX" => "France, Metropolitan",
			  "GF" => "French Guiana",
			  "PF" => "French Polynesia",
			  "TF" => "French Southern Territories",
			  "GA" => "Gabon",
			  "GM" => "Gambia",
			  "GE" => "Georgia",
			  "DE" => "Germany",
			  "GH" => "Ghana",
			  "GI" => "Gibraltar",
			  "GR" => "Greece",
			  "GL" => "Greenland",
			  "GD" => "Grenada",
			  "GP" => "Guadeloupe",
			  "GU" => "Guam",
			  "GT" => "Guatemala",
			  "GN" => "Guinea",
			  "GW" => "Guinea-Bissau",
			  "GY" => "Guyana",
			  "HT" => "Haiti",
			  "HM" => "Heard And Mc Donald Islands",
			  "VA" => "Holy See (Vatican City State)",
			  "HN" => "Honduras",
			  "HK" => "Hong Kong",
			  "HU" => "Hungary",
			  "IS" => "Iceland",
			  "IN" => "India",
			  "ID" => "Indonesia",
			  "IR" => "Iran (Islamic Republic Of)",
			  "IQ" => "Iraq",
			  "IE" => "Ireland",
			  "IL" => "Israel",
			  "IT" => "Italy",
			  "JM" => "Jamaica",
			  "JP" => "Japan",
			  "JO" => "Jordan",
			  "KZ" => "Kazakhstan",
			  "KE" => "Kenya",
			  "KI" => "Kiribati",
			  "KP" => "Korea, Democratic People's Republic Of",
			  "KR" => "Korea, Republic Of",
			  "KW" => "Kuwait",
			  "KG" => "Kyrgyzstan",
			  "LA" => "Lao People's Democratic Republic",
			  "LV" => "Latvia",
			  "LB" => "Lebanon",
			  "LS" => "Lesotho",
			  "LR" => "Liberia",
			  "LY" => "Libyan Arab Jamahiriya",
			  "LI" => "Liechtenstein",
			  "LT" => "Lithuania",
			  "LU" => "Luxembourg",
			  "MO" => "Macau",
			  "MK" => "Macedonia, Former Yugoslav Republic Of",
			  "MG" => "Madagascar",
			  "MW" => "Malawi",
			  "MY" => "Malaysia",
			  "MV" => "Maldives",
			  "ML" => "Mali",
			  "MT" => "Malta",
			  "MH" => "Marshall Islands",
			  "MQ" => "Martinique",
			  "MR" => "Mauritania",
			  "MU" => "Mauritius",
			  "YT" => "Mayotte",
			  "MX" => "Mexico",
			  "FM" => "Micronesia, Federated States Of",
			  "MD" => "Moldova, Republic Of",
			  "MC" => "Monaco",
			  "MN" => "Mongolia",
			  "MS" => "Montserrat",
			  "MA" => "Morocco",
			  "MZ" => "Mozambique",
			  "MM" => "Myanmar",
			  "NA" => "Namibia",
			  "NR" => "Nauru",
			  "NP" => "Nepal",
			  "NL" => "Netherlands",
			  "AN" => "Netherlands Antilles",
			  "NC" => "New Caledonia",
			  "NZ" => "New Zealand",
			  "NI" => "Nicaragua",
			  "NE" => "Niger",
			  "NG" => "Nigeria",
			  "NU" => "Niue",
			  "NF" => "Norfolk Island",
			  "MP" => "Northern Mariana Islands",
			  "NO" => "Norway",
			  "OM" => "Oman",
			  "PK" => "Pakistan",
			  "PW" => "Palau",
			  "PA" => "Panama",
			  "PG" => "Papua New Guinea",
			  "PY" => "Paraguay",
			  "PE" => "Peru",
			  "PH" => "Philippines",
			  "PN" => "Pitcairn",
			  "PL" => "Poland",
			  "PT" => "Portugal",
			  "PR" => "Puerto Rico",
			  "QA" => "Qatar",
			  "RE" => "Reunion",
			  "RO" => "Romania",
			  "RU" => "Russian Federation",
			  "RW" => "Rwanda",
			  "KN" => "Saint Kitts And Nevis",
			  "LC" => "Saint Lucia",
			  "VC" => "Saint Vincent And The Grenadines",
			  "WS" => "Samoa",
			  "SM" => "San Marino",
			  "ST" => "Sao Tome And Principe",
			  "SA" => "Saudi Arabia",
			  "SN" => "Senegal",
			  "SC" => "Seychelles",
			  "SL" => "Sierra Leone",
			  "SG" => "Singapore",
			  "SK" => "Slovakia (Slovak Republic)",
			  "SI" => "Slovenia",
			  "SB" => "Solomon Islands",
			  "SO" => "Somalia",
			  "ZA" => "South Africa",
			  "GS" => "South Georgia, South Sandwich Islands",
			  "ES" => "Spain",
			  "LK" => "Sri Lanka",
			  "SH" => "St. Helena",
			  "PM" => "St. Pierre And Miquelon",
			  "SD" => "Sudan",
			  "SR" => "Suriname",
			  "SJ" => "Svalbard And Jan Mayen Islands",
			  "SZ" => "Swaziland",
			  "SE" => "Sweden",
			  "CH" => "Switzerland",
			  "SY" => "Syrian Arab Republic",
			  "TW" => "Taiwan",
			  "TJ" => "Tajikistan",
			  "TZ" => "Tanzania, United Republic Of",
			  "TH" => "Thailand",
			  "TG" => "Togo",
			  "TK" => "Tokelau",
			  "TO" => "Tonga",
			  "TT" => "Trinidad And Tobago",
			  "TN" => "Tunisia",
			  "TR" => "Turkey",
			  "TM" => "Turkmenistan",
			  "TC" => "Turks And Caicos Islands",
			  "TV" => "Tuvalu",
			  "UG" => "Uganda",
			  "UA" => "Ukraine",
			  "AE" => "United Arab Emirates",
			  "UM" => "United States Minor Outlying Islands",
			  "UY" => "Uruguay",
			  "UZ" => "Uzbekistan",
			  "VU" => "Vanuatu",
			  "VE" => "Venezuela",
			  "VN" => "Viet Nam",
			  "VG" => "Virgin Islands (British)",
			  "VI" => "Virgin Islands (U.S.)",
			  "WF" => "Wallis And Futuna Islands",
			  "EH" => "Western Sahara",
			  "YE" => "Yemen",
			  "YU" => "Yugoslavia",
			  "ZM" => "Zambia",
			  "ZW" => "Zimbabwe"
			);
	
	function __construct(){
		$this->editing=(Request::exists('id')) ? true : false;
	}

	//out is for a form page
	function val($key, $def="",$pr=true) {
		$v=NULL;
		if( Request::is($key) ){
			$v = Request::get($key,$def);
			$v = htmlspecialchars($v);
		}else{
			$tmpvar = "tmp_".$key;	
			$v = (isset($this->$tmpvar)) ? $this->$tmpvar : $def;
		}
		
		if($pr){ print $v; }else{ return $v; }
	}
	
	//sets a tmp var
	function set($var,$val=""){
		$tmpvar = "tmp_".$var;
		$this->$tmpvar = $val;
	}
	//gets a tmp var
	function get($var){	
		return $this->val($var,"",false);
	}
	
	function setAPI($api,$sm='',$lockPost=false){
		$this->successMsg = ($sm!='') ? $sm : $this->successMsg;
		if($api->errors) $this->aErrors = $api->errors;
		if(Request::POST() || ($this->aErrors!=false)){
			$this->processed=true;
		}
	}
	
	//successful?
	function success(){
		return ($this->aErrors) ? false : true;
	}
	
	function addingSuccess($page,$edit_id=""){
		if($this->success() && (!$this->editing)){
			header("Location: $page?success=yes&id=$edit_id");
		}
	}
	
	function addError($str){
		$this->processed=true;
		array_push($this->aErrors,$str);
	}
	
	function displayHelpPrompts($time=3){
		if($this->stickytime==0){
			$this->stickytime = $time * 1000;
		}
		if($this->processed){
			if($this->aErrors){
				$this->displayErrorDiv();
			}else{
				$this->displaySuccessDiv();
			}
		}
	}
	
	function checkErrors($api){
		
		if($api->response['errors']){		
			print'<ul class="errors" style="">';
				foreach($api->response['errors'] as $f=>$v){
					print'<li>'.$v.'</li> ';
					print "\n";
				}
			print'</ul>';
		}
	}
	

	function outCookie($var,$def=""){
	    if(Page::cookieExists($var)){
	        if( Request::is($var) ){
    			$v = Request::get($var,$def);
    		}else{
    		    $v = $_COOKIE[$var];
    		}
    		print $v;
	    }else{
	        Page::out($var);
	    }
	}

	function setPage($aData){
		if($aData){
			foreach($aData as $f=>$v){
				Request::set($f,$v);
			}
		}
	}
	

	function setDate($name,$set=true){
		//name is request name
		$day = Request::get($name."_d");
		$month = Request::get($name."_m");	
		$year = Request::get($name."_y");
		$posted = "$month-$day-$year";
		//"n-j-Y" month-day-year format
		if(Date::getNow("n-j-Y")==$posted){
				$the_date = Date::getNow();
		}else{
			$timedate = Date::getNow();
			//'2005-05-23 19:52:25'
			$hr = substr($timedate, 11, 2);
			$mn = substr($timedate, 14, 2);
			$sc = substr($timedate, 17, 2);
			$the_date = date("Y-m-d H:i:s", mktime($hr,$mn,$sc,$month,$day,$year));
		}
		if($set){
			Request::set($name,$the_date);
		}
		return $the_date;
	}

	
	function dateEditor($name,$tmstring=false,$sy="minten",$ey='curr'){
		
			$when = (!$tmstring) ? time() : Date::toTime($tmstring);
	        echo "<select name=\"{$name}_m\" class=\"date_select \">\n";
	        for($i=1; $i<=12; $i++){
				if(Request::is($name."_m")){
					$tryme = Request::get($name."_m");
				 	$sel = ($i ==$tryme)?' selected':'';
				 }else{
					 $sel = ($i == date('m', $when))?' selected':'';
				}
	            $label = date('F',mktime(0,0,0,$i,1,2000));
	            echo "<option value=\"$i\"$sel>$label</option>\n";
	        }
	        echo "</select>\n";
			echo "<select name=\"{$name}_d\" class=\"date_select \">\n";
	        for($i=1; $i<=31; $i++){
	
				if(Request::exists($name."_d")){
					$tryme = Request::get($name."_d");
				 	$sel = ($i ==$tryme)?' selected':'';
				 }else{
	            	$sel = ($i == date('d', $when))?' selected':'';
				}
	            $label = date('jS',mktime(0,0,0,1,$i,2000));
	            echo "<option value=\"$i\"$sel>$label</option>\n";
	        }
	        echo "</select>\n";
	        echo "<select name=\"{$name}_y\" class=\"date_select \">\n";
			$curryear = ($ey=="curr") ? date('Y') : $ey;
			$startyear = ($sy=="minten") ? $curryear - 10 : $sy;
			$whenyear = date('Y', $when);
			$startyear = ($whenyear < $startyear) ? $whenyear : $startyear;
	        for($i=$startyear; $i<=$curryear; $i++){
				if(Request::exists($name."_y")){
					$tryme = Request::get($name."_y");
				 	$sel = ($i ==$tryme)?' selected':'';
				 }else{
	            	$sel = ($i == $whenyear) ?' selected':'';
				}
	            echo "<option value=\"$i\"$sel>$i</option>\n";
	        }
	        echo "</select>\n";
	 }

	function evalFormExtras(){
			if($this->editing){
				print '<input name="id" type="hidden"  id="id" value="'.Request::id().'">';
			}
	}
	//<textarea name="copy" rows="14"  class="inputMed" id="details"></textarea>
	//formField
	function textInput($key,$defaultVal="",$class="",$custom=""){
		print '<input name="'.$key.'" type="text" '.$custom.' class="'.$class.'" id="'.$key.'" value="'.$this->val($key,$defaultVal,false).'" tabIndex="'.$this->tabIndex.'">';
		$this->tabIndex++;
	}

	function passwordInput($key,$defaultVal="",$class="",$custom=""){
		print '<input name="'.$key.'" type="password" '.$custom.' class="'.$class.'" id="'.$key.'" value="'.$this->val($key,$defaultVal,false).'" tabIndex="'.$this->tabIndex.'">';
		$this->tabIndex++;
	}
	function textHidden($key,$defaultVal=""){
		$outValue = Request::get($key);
		print ''.$outValue.'<input name="'.$key.'" type="hidden" id="'.$key.'" value="'.$this->val($key,$defaultVal,false).'" >';
	}
	
	function textArea($key,$class="inputLrg",$defaultVal="",$custom='rows="5"'){
		print'<textarea name="'.$key.'" '.$custom.' class="'.$class.'"  id="'.$key.'" tabIndex="'.$this->tabIndex.'">'.$this->val($key,$defaultVal,false).'</textarea>';
		$this->tabIndex++;
	
	}
	
	
	function radioToggle($name,$default="y",$y="Yes",$n="No"){
			$var = (Request::is($name)) ? Request::get($name) : $default;
			$sel_y = ($var =="y")?' checked="checked"':'';
			$sel_n = ($var =="n")?' checked="checked"':'';
			echo'<div id="radiobuttons" class="radio"><input name="'.$name.'" id="'.$name.'_y" type="radio" value="y" '.$sel_y.' tabIndex="'.$this->tabIndex.'" />'.$y.'</div>';
		    echo'<div id="radiobuttons2" class="radio"><input name="'.$name.'" id="'.$name.'_n" type="radio" value="n" '.$sel_n.' tabIndex="'.$this->tabIndex.'"  />'.$n.'</div>';
			$this->tabIndex++;
	}
	
	
	function checkboxArray($formName,$aData,$name="name",$id="id"){
		$checkcode = 'checked="checked"';
		if($aData){
			$x=0;
			$ReqestChecks = Request::getArray($formName);
			
			foreach($aData as $obj){
				$a = $obj[$name]; 
				$b = $obj[$id];
				print '<div style="margin-right:14px;" class="checkholder">';
					$checked = (in_array($b,$ReqestChecks)) ? "$checkcode" :  "";
					echo'<input name="'.$formName.'[]" id="'.$formName.'_'.$x.'" type="checkbox" value="'.$b.'" '.$checked.' tabIndex="'.$this->tabIndex.'"/> ';
				print $a."</div>";
				$x++;
				$this->tabIndex++;
			}
		}
	}
	
	function checkbox($name,$val="yes",$defaultCheck=false,$class="checkbox"){
	        //if its checked
			$checkcode = 'checked="checked"';
			if(Request::exists($name)){
			    $checked = (Request::equals($name,$val)) ? "$checkcode" :  "";
			}else{
			    $checked = ($defaultCheck==false) ? "" : "$checkcode";
			}
			echo'<input name="'.$name.'" id="'.$name.'" type="checkbox" class="'.$class.'" value="'.$val.'" '.$checked.' tabIndex="'.$this->tabIndex.'"/> ';
			$this->tabIndex++;
	}
	
	function radioButtons($rName,$rButtons,$default="y",$class="radio"){
			$final = (Request::is($rName)) ? Request::get($rName) : $default;
			$bad_default = (!in_array($default,$rButtons)) ? true : false;
			if(is_array($rButtons)){
				$a=0;
				foreach($rButtons as $f=>$v){
					$uname = $rName.'_'.$a;
					if($bad_default and ($final==$default)){
						$sel = ($a==0) ? ' checked="checked"':'';
					}else{
						$sel = ($final ==$v)? ' checked="checked"':'';
					}
					echo '<input class="'.$class.' inline" name="'.$rName.'" id="'.$uname.'" type="radio" value="'.$v.'" '.$sel.' tabIndex="'.$this->tabIndex.'" />
						<span class="'.$class.' inline">'.$f.'</span>';
					$a++;
					$this->tabIndex++;
				}
			}
	}
	
	function jumpMenu($jName,$jArray,$def=false,$starter=false,$onChange="",$class=""){
			$final = (Request::is($jName)) ? Request::get($jName) : $def;
			$class = ($class=="") ? "jumpmenu" : $class;
			
			if(is_array($jArray)){
				echo "<select name=\"{$jName}\" id=\"{$jName}\" class=\"$class taField \" onChange=\"".$onChange."\" tabIndex=\"".$this->tabIndex."\">\n";
				$a=0;
				if($starter){
					if (!$final){ $sel = " selected"; } else { $sel = ""; }
					echo $final;
					echo '<option value="" '.$sel.' />'.$starter.'';
					//echo '<option value="" />';
				}
				foreach($jArray as $f=>$v){
					$sel = ($final ==$v)? ' selected':'';
					echo '<option value="'.$v.'" '.$sel.' />'.$f.'';
					echo "\n";
					$a++;
				}
				echo "</select>\n";
				$this->tabIndex++;
			}
			
	}
	function selectState($sName, $def=false,$starter=false,$class=""){
		$this->jumpMenu($sName,$this->state_list,$def,$starter,"",$class);
	}
	function selectCountry($sName, $def=false,$starter=false,$class=""){
		// real quick, switch the country list so the keys and value are switch
		$newCountryList = array_flip($this->country_list);
		$tb = new Table("xcart_countries");
		$aClist = $tb->select();
		$pack = array();
		if($aClist){
			foreach($aClist as $c){
				$pack[$c['name']]=$c['code'];
			}
		}
		$this->jumpMenu($sName,$pack,'US',$starter,"",$class);
	}
	
		
}

?>