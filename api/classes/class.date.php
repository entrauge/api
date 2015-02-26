<?php
/*

Static Class Date
madrid@entrauge.com
Last Update: Dec 6 2007

About:
This class is typically used for working with the mysql datetime format

Possibly Contstants:
- DATE_HOURDIFF





Methods:

getHourDiff()
	RETURNS: the hour difference constant	
	
getNow($format="Y-m-d H:i:s")
	RETURNS: the current datetime in a format
	EXAMPLE:  getNow("Y-m-d")  outputs-> 2007-12-04 
	
toArray($datetime)
	RETURNS:  an array of the dt 
	EXAMPLE:  toArray('2007-12-04 16:20:47')  outputs-> $a['min'],$['hour'] etc..
	
toTime($datetime)
	RETURNS: a time string based on the datetime you give it
	EXAMPLE:  toTime('2007-12-04 16:20:47') outputs-> 1196806847
	
toString($datetime,$format="F j, Y")
	RETURNS:  a formatted datetime string of your choice
	EXAMPLE:  toString('2007-12-04 16:20:47') outputs-> December 4, 2007
	
isWithin($datetime,"2 weeks",$past=true);
	RETURNS:  true/false of if a datetime is within the phase you give it. can do past or future tense
	EXAMPLE:  isWithin('2007-12-04 16:20:47',"2 years")	outputs-> true 
	
time_toPhrase(2342345);
	RETURNS:  a phrase of date/time based on timestring you give it
	EXAMPLE:  time_toPhrase(2342345,"weeks")	outputs-> 3 weeks 
	
time_fromPhrase("2 years");
	RETURNS:  a timestring based on the phrase you give it
	EXAMPLE:  time_fromPhrase("2 weeks")  outputs-> 1209600 
	
*/


class Date {

	public static function getHourDiff(){
			return (defined('DATE_HOURDIFF')) ? DATE_HOURDIFF : 0;
	}
	public static function getNow($format="Y-m-d H:i:s") {
		//default is datetime string
		return date($format, time() + ((Date::getHourDiff()) * 60 * 60));
	}
	public static function date_toAgo($timestamp,$type=2){
		 $diff = time() - strtotime($timestamp);
	
	    if ($diff == 0) 
	         return 'just now';
	
			
	    $intervals1 = array
	    (
	        1                   => array('year',    31556926),
	        $diff < 31556926    => array('month',   2628000),
	        $diff < 2629744     => array('week',    604800),
	        $diff < 604800      => array('day',     86400),
	        $diff < 86400       => array('hour',    3600),
	        $diff < 3600        => array('minute',  60),
	        $diff < 60          => array('second',  1)
	    );
	    
	    $intervals2 = array
	    (
	        1                   => array('y',    31556926),
	        $diff < 31556926    => array('month',   2628000),
	        $diff < 2629744     => array('w',    604800),
	        $diff < 604800      => array('d',     86400),
	        $diff < 86400       => array('h',    3600),
	        $diff < 3600        => array('m',  60),
	        $diff < 60          => array('s',  1)
	    );
	    
	    
	    if($type==1){
		    $intervals= $intervals1;
		    $value = floor($diff/$intervals[1][1]);
		    $val2 = $value.' '.$intervals[1][0].($value > 1 ? 's' : '').' ago';
	    }else{
		    $intervals= $intervals2;
		    $value = floor($diff/$intervals[1][1]);
		    $val2 = $value.$intervals[1][0];
	    }
		
	     
	     return $val2;
	     //return $value.$intervals[1][0];
	}
	public static function toArray($datetime){
		$dt = array();
		$dt['year'] = substr($datetime, 0, 4);
		$dt['month'] = substr($datetime, 5, 2);
		$dt['day'] = substr($datetime, 8, 2);
		$dt['hour'] = substr($datetime, 11, 2);
		$dt['min'] = substr($datetime, 14, 2);
		$dt['sec'] = substr($datetime, 17, 2);
		return $dt;
	}
	
	public static function toTime($datetime){
		$dta = Date::toArray($datetime);
		$date = mktime($dta['hour'], $dta['min'], $dta['sec'], $dta['month'], $dta['day'], $dta['year']);
		return $date;
	}
	public static function toTimeTwitter($rfc){
		$aRFC = explode(" ",$rfc);
		$rfc_date = $aRFC[0].", ".$aRFC[2]." ".$aRFC[1]." ".$aRFC[5]." ".$aRFC[3]." ".$aRFC[4];
		$dta = strptime($rfc_date,"r");
		$date = mktime($dta['tm_hour'], $dta['tm_min'], $dta['tm_sec'], $dta['tm_mon'], $dta['tm_mday'], $dta['tm_year']);
		return $date;
	}
	public static function toString($datetime,$format="F j, Y"){
		$dta = Date::toArray($datetime);
		$date = date($format,mktime($dta['hour'], $dta['min'], $dta['sec'], $dta['month'], $dta['day'], $dta['year']));
		return $date;
	}
	
	public static function isWithin($dt,$phrase,$past=true){
	    //this is the relative time from the dt given
	    $date_posted = substr(Date::toTime($dt), 0, 6);
	    $curr_time = mktime();
	    $curr_date = substr($curr_time,0,6);
	  
	    $extratime = Date::time_fromPhrase($phrase);
	    
	    if($past){
	        $period = substr($curr_time - $extratime,0,6);
	        $result = (($date_posted >= $period) and ($date_posted <= $curr_date)) ? true : false;
	    }else{
	      $period = substr($curr_time + $extratime,0,6);
	       $result = (($date_posted >= $curr_date) and ($date_posted <= $period) ) ? true : false;
	    }
	    return $result;
	}
	
		//$ft = time_fromPhrase("20 Minutes"); outputs-> 1200;
	public static function time_fromPhrase($str){
		$ts = strtoupper($str);
		$pArray = array("MINUTE"=>60,"HOUR"=>3600,"DAY"=>86400,"WEEK"=>604800,"MONTH"=>2592000,"YEAR"=>31104000);
		$digit = NULL;
		//catch the digits
		$t = strlen($ts);
		for($a=0;$a<$t;$a++){
			$c = $ts[$a];
			if(is_numeric($c)){ $digit.=$c; }
		}
		$fulltime=false;
		if($digit!=NULL){
			//figure out the amount
			foreach($pArray as $p=>$time){
				if(stristr($ts,$p)){
					$fulltime = $digit * $time;
				}
			}
		}
		return $fulltime;		
	}
	
		//$ft = time_toPhrase(115200,'hours'); outputs-> 32 hours
	public static function time_toPhrase($tm,$phrase,$round=true){
		$ts = strtoupper($phrase);
		$fulltime=false;
		$pArray = array("MINUTE"=>60,"HOUR"=>3600,"DAY"=>86400,"WEEK"=>604800,"MONTH"=>2592000,"YEAR"=>31104000);
		foreach($pArray as $p=>$time){
			if(stristr($ts,$p)){
				$fulltime = $tm /$time;
				$phrase = strtolower($p);
			}
		}
		//possibly round it
		if($round){ $fulltime = floor($fulltime); } 
		if($fulltime>1){ $phrase = $phrase.="s"; }
		$phrase =  "$fulltime $phrase";
		return $phrase;
	}
	
	public static function time_toAgo($tm,$round=true){
		$pArray = array("Minute"=>60,"Hour"=>3600,"Day"=>86400,"Week"=>604800,"Month"=>2592000,"Year"=>31104000);
		$word = "Minute";
		$fulltime =false;
		$nowTime = time();
		$date_past= $nowTime - $tm;
	
		foreach($pArray as $pWord=>$pTime){
			if($date_past < $pTime){
		
				$word = $pWord;
				
				$fulltime = $date_past /$pTime;
			
				
			}
		}
		
		//possibly round it
		if($round){ $fulltime = floor($fulltime); } 
		if($fulltime>1){ $word = $word.="s"; }
		$phrase =  "$fulltime $word";
		return $phrase;
	}

}

?>