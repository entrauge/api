<?php
/*

Log
*/


class log {
	
	function __construct(){
		
	}
	
	public static function save($str="",$other=""){
		$tb = new Table('app_log');
		$tb->field('message',$str);
		$tb->insert();
	}

}

?>