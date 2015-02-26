<?php
/*

Static Comma Class


ex:
$source = '1,2,4';

$an = Comma::add($source,4);
//1,2,4,4

$an = Comma::add($source,array(12,34,45));
//1,2,4,12,34,45

$an = Comma::add($source,'5');
//1,2,4,5
	
*/



class Comma {

	
	function exists($c,$d,$u){
	
		if($u==false){
			return false;
		}else{
			return in_array($d,$c);
		}
	}
	
	//returns new array
	function clean($a,$makeArray=false){
		if($makeArray){
			$a = explode(',',$a);
		}
		$t = array();
		if($a){
			foreach($a as $k){
				if($k!=''){
					array_push($t,$k);
				}
			}
		}
		return $t;
	}

	function add($source, $addMe, $unique=true,$sort=true){
		
		$a = explode(',',$source);
		$b = Comma::clean($a);
	
		if(is_array($addMe)){
			foreach($addMe as $id){
				if(!Comma::exists($a,$id,$unique)) array_push($b,$id);
			}
		}else{
			if(!Comma::exists($a,$addMe,$unique)) array_push($b,$addMe);
		}
		$an = implode(",",$b);
		$at = Comma::clean($an,true);
		if($sort) sort($at,SORT_NUMERIC);
		$an = implode(",",$at);
		return $an;

	}
	
}

?>