<?
require_once('../system.php');

$me = new API();
if(Request::get('showTime')==true){
	$me->show_time=true;
}
$me->mode='external';
$ret = $me->callMethod();

?>