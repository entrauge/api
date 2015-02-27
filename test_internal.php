<?

//require the system 
require_once('api/system.php');


/*
In Internal mode as such, the callMethod will always
return the data you send back from your method. In this
scenario I'm returning an array.

If format is set like so: $api->format="json", a large json object will be returned
	
*/
$api = new API();
$data = $api->callMethod('test.getList');

//print out the response, codes etc..
//print_r($api->response);
//print out the data
//print_r($data);

?>
<html>
	<body style="padding:20px;">
		
		<h4>Printing out the api response, codes etc.</h4>
		<pre>
			<?print_r($api->response)?>
		</pre>
		<h4>Printing out the data</h4>
		<pre>
			<?print_r($data)?>
		</pre>
	</body>
</html>