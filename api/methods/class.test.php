<?php

/*
Class: Test
DATE: 2015-02-26	
	
	
Test extends the Controller 
-----
inherits methods:
-get
-send
-error
*/
class Test extends Controller{
	
	function __construct(){
		
		//error codes
		$this->codes = array(
			20=>'Custom Test Error'
		);
	}

	//our data list
	private function getData(){
		$list =array();
		for($i=0;$i<=20;$i++){
			$tmp=array('name'=>'Test #'.$i,'value'=>$i);
			array_push($list,$tmp);
		}
		return $list;
	}
	
	function getList(){
		$myData = $this->getList();
		$this->send($myData);
	}
		
		
}

?>