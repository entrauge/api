<?php

/*
 * Table  Mysql Class
 * REQUIRED CLASSES: Request,
 * 
 * Last Updates: 
 
 
 
------------------------
 Nov 4th 2014 
------------------------
 Edited functions 
 field(), prepSave() == vs === type
 issue was causing a bug to not accept zero's
 manually ex: field('total',0);
 
------------------------
 Feb 14th 2013 
------------------------
 -added select(); to replace get();
 
 
 Mar 25th 2009
------------------------Developer notes -------------------------------
1. cleaned up some fat. removed batch array clauses because they were never used
2. changed get() to automatically be ready for $db->search/keywords, perpage LIMIT X,Y 
	inherits all the old getPages stuff but 
3. most significant. added optional parameter for $API object to be passed since most
of the time we're using the class.api.php so now they can talk to each other.
most importantly within the pages auto techology.
4. changed name from Interact to Data
5. added $db->paging ="advanced/simple" so pagination only runs 1 query on simple mode
---------------------WISHLIST/CLEANUP
1. file() and delete() use file options that aren't really ideal and could be cleaner. 
figure out how to remove this or make it organized



*/
class Table{

	var $devmode = false;
    var $query;
    var $dbHost         = NULL;
    var $dbUser         = NULL;
    var $dbPass         = NULL;
    var $dbName         = NULL;
    var $dbConn         = NULL;
    var $connectError;	
	var $errorMsg ='';
	var $errorCode      = 99; //99 success
	var $table 			= NULL;	
	var $primary_key 	= NULL;
	var $id 			= NULL;
	var $db_fields 		= array();
	var $db_defaults 	= array();
	var $db_strict      = array();
	var $db_required    = array();
	var $db_types       = array();
	var $paging	= "simple";
	var $record_limit = 20;
	var $searchPrefix = "AND";
	var $strict=false;
	
	
	//var pagingation and search stuff. api required
	var $API = NULL; //api object for help
	var $extras			= false;


	
	function Table($table="",$optAPI=NULL) {
		$this->API = $optAPI;
		$this->table = $table;
		$this->connect();
		if($table!=""){
			$this->clear();
		}
	}
	
	function getmicrotime(){ 
        list($msec, $sec) = explode(" ",microtime()); 
        $v = ((float)$msec + (float)$sec);
        return $v; 
    }
	
	
    # Establishes connection to MySQL and selects a database
    function connect() {
		
	      	global $dbLink;
	
			if($dbLink > 0 ){
				//this is already connected
				$this->dbConn= $dbLink;
			  	$this->connectError =false;
				 @mysql_ping($this->dbConn);
			}else{
	
				$this->dbHost 	= DB_HOST;
				$this->dbUser 	= DB_USERNAME;
				$this->dbPass	= DB_PASSWORD;
				$this->dbName	= DB_NAME;
			
				if (!$this->dbConn = @mysql_connect($this->dbHost, $this->dbUser, $this->dbPass)) {
					$this->errorMsg = 'Could not connect to server';
					//$this->errorDetails = mysql_error($this->dbConn);
					$this->errorCode = 0;
					$this->connectError = true;
				
				// Select database
				} else if ( !@mysql_select_db($this->dbName,$this->dbConn) ) {
					$this->errorMsg = 'Could not select database';
					//$this->errorDetails = mysql_error($this->dbConn);
					$this->errorCode = 1;
					$this->connectError = true;
					
				}
			
				$dbLink = $this->dbConn;
			}

		return $this->connectError;
    }
	
    # Checks for MySQL errors
    function isError() {
		if ($this->connectError ) { return true; }
        $this->errorMsg = mysql_error($this->dbConn);
   		return (empty($this->errorMsg)) ? false : true;
    }

    # Doctor return errors
    function doctor() {
		return ($this->errorMsg!='') ? $this->errorMsg : false;
    }
	
	//evaluates our sql result and gives us data
	function evalResult($aData){
	
		if($this->errorCode!=99 and $aData==false){
			return false;
		}else{
			return $aData;
		} 
	}
    # Returns an instance of MySQLResult to fetch rows with
    function  query($sql) {
        $this->execute_start = $this->getmicrotime();
		if($this->devmode){ print $sql; }
		 $final=NULL;
     	if(!$this->connectError){
     	
	        if (!$this->query = mysql_query($sql,$this->dbConn)) {
		
					$me =  mysql_error($this->dbConn);
					$this->errorDetails = $me;
					
		            if(stristr($me,'Table')){
						$this->errorMsg ="Mysql table doesn't exist";
						$this->errorCode = 4;
					}elseif(stristr($me,'Duplicate')){
						$this->errorMsg = 'Duplicate Entry';
						$this->errorCode = 6;
					}else{
						$this->errorMsg = 'Mysql query fail';
						$this->errorCode = 3;
					}
				
			}else{
				$final = $this->query;				
			}
		}
		  $this->time = $this->getmicrotime() - $this->execute_start;
		 return $final;
    }
	
    # Fetches a row from t he result
    function fetchRow() {
        if ( $row = @mysql_fetch_array($this->query,MYSQL_ASSOC) ) {
            return $row;
        } else if ( $this->numRows() > 0 ) {
            @mysql_data_seek($this->query,0);
            return false;
        } else {
            return false;
        }
    }

    # Returns the number of rows selected
    function numRows() {
        return @mysql_num_rows($this->query);
    }
	
	 # WasAffected
    function wasAffected() {
        return mysql_affected_rows();
    }

    # Returns the ID of the last row inserted
    function getInsertID() {
        return @mysql_insert_id($this->dbConn);
    }
 
    # Clears out class and resets it to default values
	function clear() {
		$this->db_fields = array();
		$this->db_strict = array();
		$this->db_strict_fields = array();
		$this->db_types = array();
		$this->db_defaults = array();
		$this->primary_key 	= NULL;
		
		// describe the table in a query result
		$sql = "DESCRIBE $this->table";
		$result = $this->query($sql);
		
		// loop through all the columns
		while ($row = $this->fetchRow()) {
			// set object variables = default value in database
			$this->$row['Field'] = $row['Default'];
			// add field to fields array
			array_push($this->db_fields, $row['Field']);
			array_push($this->db_defaults, $row['Default']);
			array_push($this->db_types, $row['Type']);
			
			// set primary key
			if ($row['Key'] == 'PRI') {
				$this->primary_key = $row['Field'];
			}
		}
	}
	
	
	// this is a helper for default values so we can use one parameter for functions
	function getParam($obj,$val,$def){
        return (isset($obj[$val])) ? $obj[$val] : $def;
    }
    	

	 # Sweeps Data before entering db
    function sweep($string) {
		if (get_magic_quotes_gpc()) {
			$string = mysql_real_escape_string(stripslashes($string), $this->dbConn);
		}else{
			$string = mysql_real_escape_string(($string), $this->dbConn);
		}
		return $string;
    }

	//bulk adding
	function fields($aFields){
		if(is_array($aFields)){
			foreach($aFields as $f=>$v){
				 $field = (is_numeric($f)) ? $v : $f;
		         $value = ($field==$v) ? false : $v;
				 $this->field($field,$value);
			}
		}
	}
	
	function emptyField($f){
		$this->db_strict[$f] = "";
		array_push($this->db_strict_fields,$f);
	}

	
	/*
		this is attempt to make this class available to 
		anyone not using a $this->API->get() for retrieving passed variables
		so if no API is set... it defaults to the Request::get object via http vars
		!!! might use the defValue later but not now
	*/
	function getRequestVar($key,$def=false){
		$v=NULL;

		if($this->API!=NULL){
		
			if(isset($this->API->_vars[$key])){
				$v =  $this->API->get($key,$def);	
			}
		}else{
			
			if(Request::exists($key)){
				$v = Request::get($key,$def);	
				
			}
		}
		return $v;
	}
	
	function forceField($f,$v){
		if(in_array($f,$this->db_fields)){
			$this->db_strict[$f] = $v;
			array_push($this->db_strict_fields,$f);
		}
	}
	//
	function field($f,$data=NULL) {
		if(in_array($f,$this->db_fields)){
				
				//watch for zeros
				if(is_numeric($data)){ 
						$df=true; 
					}else{ 
						$df= ($data==NULL) ? false : true;	
				}
			
				if($df==false){
					
					$v = $this->getRequestVar($f); 
					
					//if user passes a key then we assume to update it.
					if($v != NULL){ //new update
						
						if($v!=false){
							if(is_array($v))  $v = implode(",",$v);
					        $this->db_strict[$f] = $v ;
					    }else{
							if(is_numeric($v)){
								$this->db_strict[$f] = $v;
							}else{
								$v=false;
								$this->db_strict[$f] = $v;
							}
					    }
							
					}else{	
					
						//new for a form that's being edited so it saves the other
						if(isset($_REQUEST[$f])){
							if($_REQUEST[$f]==""){
								$this->db_strict[$f] = $v;
							}
						}
						//manually setting
						if(!is_null($data)){
							
							//print 'yes someone is forcing something';
							$this->db_strict[$f] = $v;
						}else{
							//print 'it was set but its null';
						}
						
						//user didn't submit anything
					}
					
				}else{
					
				    $v = $data;
				    $this->db_strict[$f] = $v;
				}
				
				if(is_numeric($v)){ 
					$vf=true;
				}else{ 
					$vf=($v==false) ? false: true;	
				}
				
				/*
					if $vf value 1=false then we push it through to update	
				*/
				if($vf != false){
					//Confirmed that -0- is going into total
					//print "Confirmed that -$v- is going into $f";
					array_push($this->db_strict_fields,$f);
				}else{
					//user didnt submit anything "";					
				}
		}
	}
    
	
	/*
	
		changed FileMaker class to Uploader
	
    //the FileMaker class is required to use this
	//do we really need this?
	//idea $m->file("avatar",array('sizes'=>$this->aSizes,'request'=>'id','filepath'=>$fp))
	*/
    function file($fileUpload,$params=array()){
		
		$inputName = $this->getParam($params,'inputName',$fileUpload);
		$file_storage = $this->getParam($params,'storage',FILE_STORAGE);
		$request_key = $this->getParam($params,'request','id');
		$sizeArray = $this->getParam($params,'sizes',NULL);
		$saveOriginal = $this->getParam($params,'original',false);

      	$tmpFile = new Uploader($inputName,$file_storage);
      	$tmpFile->copy_original=$saveOriginal;
      	
        $hash_ticket = NULL;

        if($tmpFile->isUploading()){
	
                //this is typical for a backend for editing a file but it shouldn't be just relied on that
              	if($this->getRequestVar($request_key)){
                      $hi = $this->getAttribute( $this->getRequestVar($request_key) ,$fileUpload);
						
                      if($hi!=''){
	                      $tmpFile->deleteSizeArray($hi,$sizeArray);
	                  }
            	}
        
            	//our FileMaker standard SizeArray = sizes = array($a,$b,$c); $a= array("_m",640,480,true) etc..
            	if(is_array($sizeArray)){
            	    foreach($sizeArray as $v){ $tmpFile->addSize($v[0],$v[1],$v[2],$v[3]); }
            	}
          		$r = $tmpFile->upload();
              	$hash_ticket = $tmpFile->rawname;
              	
              		/*
$tb=new Table("app_log");
					$tb->field('type','$tmpFile');
					$tb->field('date_added',Date::getNow());
					$tb->field('message',json_encode($tmpFile));
					$tb->insert();
*/
        }
        
        //this will add it to the insert() or update() que if it exists
        if($hash_ticket!=NULL){  
            $this->field($fileUpload,$hash_ticket);
        }
		return $hash_ticket;
    }





	/**
    * getRowData
    */
	function getRowData(){
		if ($this->numRows() > 0) {
			while ($row = $this->fetchRow()) {
				$this->aRowData[] = $row;
			}
			return $this->aRowData;
		} else {
			return false;
		}
	}
	
	
	
	/*
    	getAttribute: Gets a column attribute
		This relies on the function get()
		----------------------------
		getAttribute(1981,"name");  // returns:String= "Michael"
    */
	function getAttribute($id,$att){
		
		$data = $this->get($id);
		$deliver = null;
		if($data){
			foreach($data as $d){
				$deliver = $d[$att];
			}
		}
		return $deliver;
	}
	
	//
	/*
    	mergeWidth is like our join
    */
	function merge($rtable,$rid,$rfields,$extra_clause="",$myid="id"){
		$ofields = "";
		$tfields ="";
		foreach($this->db_fields as $field) {
			$ofields .="$this->table.$field,";
		}
		$ofields = substr($ofields, 0, strlen($ofields) - 1); //pop the ,
		if($rfields){
			foreach($rfields as $f=>$v){
				if(is_numeric($f)){
					$uf = $v;
					$as = "";
				}else{
					$uf = $f;
					$as = "as $v";
				}
				$tfields .=",$rtable.$uf $as";
			}
		}
		$sql = "SELECT $ofields $tfields FROM $this->table, $rtable WHERE ($this->table.$rid = $rtable.$myid) $extra_clause";
		return $this->get($sql);
	}

	
	

	
	
	function getPages($clause=NULL){
		$this->paging="advanced";
		return $this->get($clause,array('pages'=>true));
	}
	/**
    * get()
	* 
    */
   
	function getByID($clause){
		$me =  $this->get($clause,$params);
		return $me[0];
	}
	function select($clause=NULL,$params=array()){
		return $this->get($clause,$params);
	}
	function custom($clause=NULL){
		$this->strict=true;
		return $this->get($clause);
	}
	function get($clause=NULL,$params=array()) {
		

		
		//set a few things
		$this->aRowData = array();
		$ret=false;
	    //set our params
        $table_key = $this->getParam($params,'key',$this->primary_key);
   		$this->usePaging = $this->getParam($params,'pages',false);

				if(is_numeric($clause)){
					//**** SINGLES
					$sql = "SELECT * FROM $this->table WHERE $table_key = '$clause' LIMIT 1";
					$result = $this->query($sql);	
					$this->getRowData();
					$this->total = count($this->aRowData);
					$ret = ($this->total > 0) ? $this->aRowData : false;
					
				}else{
					
							//are we using a custom SELECT query ??
							$custom_query = (stristr($clause,'SELECT')) ? true : false;
					
							
							//--------------SEARCHING--------------
							$sql_search = "";
							$keywords = $this->getRequestVar('keywords');
							$search_total = (!empty($this->search)) ? count($this->search) : 0;
							if($keywords and ($search_total > 0 )){
								$i=0;
								$search_query="";
								foreach($this->search as $field) {
									$or_extra = ($i== ($search_total-1)) ? "":"OR ";
									$keywords = trim($keywords);
									//clean this up, sweep it for weird stuff
									$keywords = $this->sweep($keywords);
									$keywords = str_replace(" ","%",$keywords);
									$search_query .="($field LIKE '%$keywords%') $or_extra";
									$i++;
								}
								$sql_search = "($search_query)";
							}
								
							//-------------------------- PAGE START CODE ------------------------
							//if($this->usePaging){
						
									//----set important variables ----
									#page
									$page = $this->getRequestVar('page',1);
									$page = (is_numeric($page) and ($page > 0) ) ? $page:1;
									#perpage
									$perpage = $this->getRequestVar('perpage',$this->record_limit);
									$perpage = (is_numeric($perpage) and ($perpage > 0) ) ? $perpage : $this->record_limit;
									
			
									//------ FIX THE WHERE stuff later
									if($clause!=NULL){
										 $and_extra = (!$sql_search) ? "": $this->searchPrefix;
										  //this means they are doing a custom SELECT
										    if($custom_query){
												if(stristr($clause,'WHERE')){

													if(stristr($clause,"ORDER")){
														$parts = explode("ORDER",$clause);
														$sql_where = $parts[0]." $and_extra $sql_search "." ORDER".$parts[1];	
													}else{
														if(stristr($clause,"{search}")){
																$sql_where  = "$clause";
															
																$sql_where = str_replace("{search}",$sql_search,$sql_where);
																
														}else{
															$sql_where  = "$clause $and_extra $sql_search";
														}
														
													}

												}else{
													$sql_where  = ($sql_search=="") ?  $clause : "$clause WHERE $sql_search";
												}
										    }else{
										        //if there is a quick WHERE catch
										        if(stristr($clause,'WHERE')){
										            //1. make sure to put the sql search before all the other junk
										            $sql_where = str_replace('WHERE',"WHERE $sql_search $and_extra", $clause);
										        }else{
													//fix the ORDER BY in case
										            $sql_where =  (!$sql_search) ? $clause : "WHERE $sql_search $clause";
										        }

										    }
	
									}else{
										$sql_where = (!$sql_search) ? "":"WHERE $sql_search";
									}
					
						
									//--------------ORDERING--------------
									$sql_order = "";
									$sort = $this->getRequestVar('sort'); 
								
									if($sort){
											$sort = (in_array($sort,$this->db_fields)) ? $sort : $this->primary_key;
										$sql_sort_dir = ($this->getRequestVar('dir')=="asc") ? "ASC" :"DESC";
										$sql_order = "ORDER BY $sort $sql_sort_dir";
									}
						
									//--------------PAGE--------------
									$page_total=1;
									if($this->paging=="advanced"){
										$sql_count_query = (!$custom_query) ? "SELECT * FROM $this->table $sql_where" : "$sql_where";
										$result_count = $this->query($sql_count_query);
										$this->total = $this->numRows();
										$page_total = ceil($this->total/$perpage);
										if($page > $page_total) $page=1;
									}

						
									#start logic page help code
								
									$the_start = ($page * $perpage) - $perpage;
									$the_end   = $the_start + $perpage;
									
									if($this->usePaging){
										#prep final sql query
										if(!$custom_query){
											$extra_limit = (!stristr($clause,"LIMIT")) ? "LIMIT $the_start,$perpage" : "";
											$sql_final = "SELECT * FROM $this->table $sql_where $sql_order $extra_limit";
										}else{
											$sql_final = (stristr($clause,"LIMIT")) ? "$sql_where $sql_order" : "$sql_where $sql_order LIMIT $the_start,$perpage";
										}
									}else{
										if(!$custom_query){
											$sql_final = "SELECT * FROM $this->table $sql_where $sql_order";
											
										}else{
											$sql_final = "$sql_where $sql_order" ;
										}
									}
									
									
									if($this->strict){
										$sql_final = $clause;
									}
									
								
									#run the final sql query
									$result_main = $this->query($sql_final);
									$this->totalB = $this->numRows();
									$aRowDataPages= false;
									if ($this->totalB > 0) {
											while ($row = $this->fetchRow()) {
											 	$aRowDataPages[] = $row;
											}
									}
									
									$this->total = ($this->paging=="advanced") ? $this->total : $this->totalB;
									
									
									#run some logic over here
									if($the_end > $this->total){
										$offset = $this->total - $the_start;
										$the_end = $the_start + $offset;
										if($the_end==0){ $the_end=1;}
									}
									$alt_start = $the_start +1;
									

									$this->page['start'] = $alt_start;
									$this->page['end'] = $the_end;
									$this->page['count'] = $this->total;
									$this->page['pages'] = $page_total;
									$this->page['page'] = $page;
									$this->page['sort'] = $this->getRequestVar('sort');
									$this->page['dir'] = $this->getRequestVar('dir');
									$this->page['keywords'] = $this->getRequestVar('keywords');
									
									
									//set the page vars
									if($this->API!=NULL){
										$this->API->page = $this->page;
									}
									
									
									//return paging data
									$ret = ($this->total > 0) ? $aRowDataPages : false;
								//-------------------------- PAGE END CODE ------------------------
							
					

				}
				
				//RETURN
				return $this->evalResult($ret);	
	}
	



	/**
    * prepSave helper for update()
    */
	function prepSave(){
			
			$sql = "UPDATE $this->table SET ";
			$a=0;
		
			foreach($this->db_strict as  $field => $value) {
					
				if ($field != $this->primary_key) {
					
					if($value==="++"){ 
						$sql .= "$field= $field +1 ,";
					}elseif($value==="--"){
						$sql .= "$field= $field -1 ,";
						//check to see if its really a datetime
					}elseif(strtoupper($value)=== "NOW()"){
						$sql .= "$field = NOW() ,";
					}else{
						$sql .= "$field='" .$this->sweep($value). "' ,";
					}
				}
				$a++;
			}
			$sql = substr($sql, 0, strlen($sql) - 1);
	
		return $sql;
	
	}

    /*
        Inserting into database 
    */
    function insert($clause="",$params=array()){
        
		$sql = "INSERT INTO $this->table VALUES ('',";
		$a=0;
		foreach($this->db_fields as $field) {
				//if we've already set this feild then set it here
				if(in_array($field,$this->db_strict_fields)){
					$value = $this->db_strict[$field];
					if ($field != $this->primary_key) {
						if(strtoupper($value)=="AUTO_INCREMENT"){
							$noid = $this->getNextOrderID("",$field);
							$sql .= "'" . $noid . "',";
						}elseif(strtoupper($value)=="NOW()"){
							$sql .= "NOW(),";
						}else{
							$sql .= "'" . $this->sweep($value) . "',";
						}
					}
				}else{
					//otherwise make it what it's default was
					if ($field != $this->primary_key) {
						$type = $this->db_types[$a];
						#plugin for datetime
						if($type=="datetime"){
							$sql .= "NOW(),";
						}else{
							$value = $this->db_defaults[$a];
							$sql .= "'". $this->sweep($value) ."',";
						}
					}
				}
				$a++;
		}
		$sql = substr($sql, 0, strlen($sql) - 1);
		$sql .= ") ";
		$result = $this->query($sql);
		$this->insert_id = $this->getInsertID();
		$final_return = ($this->wasAffected() < 0) ? false : $this->insert_id;
		return  $this->evalResult($final_return);
    }


    /*	
		Updates the database
         ------
		 examples:
		 update(1);
		 update("WHERE citizen_id = '1981' ",array('autoInsert'=>true))  // typically used for something like a profile hit
		 update("WHERE email='madrid@email.com ");
     */
    function update($clause="",$params=array()){
        
        	//set our params
        	$table_key = $this->getParam($params,'key',$this->primary_key);
        	$autoInsert = $this->getParam($params,'autoInsert',NULL);
			
            $final_return=false;
			if(is_numeric($clause)){
			    //update one item : save(4);
				$sql = $this->prepSave();
				$sql .= "WHERE $table_key = '$clause'  LIMIT 1 ";
				
				$result = $this->query($sql);
				$wa = $this->wasAffected();
				$final_return = ($wa==0) ? false : true;
				if(($wa==0) and ($autoInsert)){
					$final_return = $this->insert();		
				}
    				
			}elseif(stristr($clause,'WHERE') or stristr($clause,'ALL')){
			
				//batch for ALL records :  save("WHERE active='y'");
				$sql = $this->prepSave();
				if(stristr($clause,'WHERE')){
					$sql = $sql.$clause;
					//if($autoInsert){ $sql .= " LIMIT 1"; }
				}
				
				$result = $this->query($sql);
				$wa = $this->wasAffected();
				if(($wa==0) and ($autoInsert)){
					$this->insert();		
				}
				$final_return = ($wa==0) ? false : true;
			}

            //return results
			return $this->evalResult($final_return);
    }
	

	//Inserts or updates a row in the table : see manual
	function save($clause="",$params=array()) {
		if ($clause=="") {
		    return $this->insert($clause,$params);
		}else{
		    return $this->update($clause,$params);
		}
	}
	

	//useful for getting the next-in-line "order_id"
	function getNextOrderID($clause="",$row_name="order_id"){
	    $rtn = $this->get("$clause ORDER BY $row_name DESC LIMIT 1");
	    return $rtn[0][$row_name] + 1 ;
	}

	/*
    	Order records in a table
    	-------------------------------
		$me = new Table('blogs');
		$me->order( array(19,20,5,3,7))
		Ex: order($_POST['array'],'order_id');
		
    */
	function order($aOrder="",$params=array()) {
     
        //set our params
         $row_name = $this->getParam($params,'rowName','order_id');
         $startKey = $this->getParam($params,'start',1);
         $clause = $this->getParam($params,'clause',NULL);
         $table_key = $this->getParam($params,'key',$this->primary_key);
  
		if(is_array($aOrder)){
		    $useOrder = $aOrder;
		}else if(stristr($aOrder,',')){
		    $useOrder = explode(",",$aOrder);
		}else{
		    $useOrder =false;
		}
		
		if($useOrder){
			$a=$startKey;
			foreach ($useOrder as $val) {
				 $oid=$a;
				 if($clause!=NULL){
				 	$sql = "UPDATE $this->table SET $row_name ='$oid' $clause AND $table_key = '$val'";
				 }else{
				 	$sql = "UPDATE $this->table SET $row_name ='$oid' WHERE $table_key = '$val'";
				 }	
				$result = $this->query($sql);
				$a++;
			}
			return $this->evalResult(true);	
	    }else{
		    return $this->evalResult(false);
		}
	
	}
	
	
	
	/*
	 Delete function 
	 		:can accept a file array/sizeArray, altKey for params
			delete(1)
			delete("WHERE name='John Doe'");
			delete(3,array('key'=>'ref_id'));  -- WHERE ref_id = 3;
			delete("ALL"); removes everything!
			
			
	*/
    function delete($clause="",$params=array()){
         //set our params
        $fileArray = $this->getParam($params,'files',NULL);
        $sizeArray = $this->getParam($params,'sizes',NULL);
		$fileStorage = $this->getParam($params,'storage',FILE_STORAGE);
		$ext = $this->getParam($params,'ext',"");
        
		//this is a plugin for deleting files when you delete records too
        if($fileArray!=NULL){
            $fileArray = (is_array($fileArray)) ? $fileArray : array($fileArray);
            $aRecords = $this->get($clause);
            if($aRecords){
                foreach($aRecords as $obj){
                    $tmpID = $obj['id'];
                    //loop file array
                    foreach($fileArray as $fileName){
                        $ticketName = $obj[$fileName];
							
                        $fileCommand = new Uploader($fileName,$fileStorage);
						//$fileCommand->ext = $ext;
						if($sizeArray!=NULL){
							$fileCommand->deleteSizeArray($ticketName,$sizeArray);
						}else{
							$fileCommand->delete($ticketName.$ext);
						}
 
                    }   
                    //now delete each file by primary id
                    return $this->deleteFinal($tmpID,$params);
                }
            }
        }else{
            return $this->deleteFinal($clause,$params);
        }
    }
    
	
	/**
    * delete a record/s in the table  
    */
	function deleteFinal($clause="",$params=array()) {
        
         //set our params
        $table_key = $this->getParam($params,'key',$this->primary_key);
		$result=false;
		if($clause!=""){
			if(is_numeric($clause)){
				#delete a single primary id
				$sql = "DELETE FROM $this->table WHERE $table_key = '$clause'";
				$result = $this->query($sql);
			
			}elseif(stristr($clause,'WHERE') or stristr($clause,'ALL')){
				#This will delete every record in this table when passed ALL
				if($clause=='ALL') $clause="";
	
				$sql = " DELETE FROM $this->table $clause";
				$result = $this->query($sql);
			}
		}
		
		return $this->evalResult($result);;
	}

}
?>