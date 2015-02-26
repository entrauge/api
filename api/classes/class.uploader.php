<?php

//taking a hit

ini_set("post_max_size","100M");
ini_set("upload_max_filesize","100M");
ini_set("memory_limit","128M");


/*
Upload File Class
madrid@entrauge.com
Updated:   Tuesday March 27th, 2009
---------------------------------------
*This class's goal is to:
-do easy multi-size jpg/file uploads (flickr-style)
-do easy file uploads with custom filenames,hash filenames, and increment original names

----------------------------------------
-Reminders and Checklists:
----------------------------------------
HTML; enctype="multipart/form-data"
FLASH; mime type is: application/octet-stream
PHP; ini_set("memory_limit","15M");
FLASH; httpError302 (Check PHP headers + $auth() login scripts)
 
----------------------------------------
-Example
----------------------------------------
$newPhoto = new Upload();
$newPhoto->upload_name = "mikemadrid";
$newPhoto->upload_path= "files/";
//$newPhoto->copy_original=false;
$newPhoto->addSize('_s',60,60,true);
$newPhoto->addSize('_m',300,200,false);
$r = $newPhoto->upload();
print "<br>My Hash Ticket ".$newPhoto->hash_ticket;



ToDo: 
	-clean up code
	-add a createSwatch() function for custom flash icons
*/


class Uploader {
	
	var $ext = ".jpg";
    var $sizes = array(); // used for addSize(), flickr style uploading
    var $upload_name ="file"; //the default name for the $_FILES['file']
	var $upload_path =""; // the path to upload file to
	var $upload_result = false; // the result of our file upload
	var $use_filehash = true; 
	var $hash_length=10;
	var $list_name='';
	var $hash_ticket = null;
	var $copy_original = true;
    var $file_name  =false;
	var $file_name_append=true;
	var $finalname = false;
	var $rawname = '';
    var $upload_typical=true;
	var $status="Success";
	var $jpg_quality = 90;
	var $fast_quality = 3;
	var $max_img_width = 8000;
	var $max_bytes = NULL;
	//1-5
	var $use_niceformat = true;
	var $check_mimes = false;
	var $crop_portrait = false;
	var $crop_x = 0;
	var $crop_y = 0;
	
	//-----ffmpeg built in//took out because its not that awesome
	//set $this->ffmpeg=true; and regular video files will attempt to convert
	
	var $ffmpeg = false;
	var $ffmpeg_path = '';
	var $ffmpeg_size = '1000';
	var $ffmpeg_poster =false;
	var $ffmpeg_limit_h = 360;
	var $ffmpeg_limit_w = 640;
	

	function Uploader($fun="file",$fup="",$ufh=false){
		$this->upload_name = $fun;
		$this->upload_path = $fup;
		//$this->use_filehash = $ufh;
		$this->_FILES_OBJECT = isset($_FILES[$this->upload_name]) ? $_FILES[$this->upload_name] : NULL;
		$this->file_tmp_name = $this->_FILES_OBJECT['tmp_name'];
		$this->file_org_name = $this->_FILES_OBJECT['name'];
		$this->file_size = $this->_FILES_OBJECT['size'];
		$this->upload_path = $this->fixFilePath();
		$this->ext = $this->guessExtension();
	}
	
	
	//is uploading lets us know if we are uploading.. 
	function isUploading(){
	   return  ($this->_FILES_OBJECT['error']==4) ? false : true;	
	}
	
	
	
	function upload(){
	    
	    if($this->_FILES_OBJECT['error']==0){
			//check for max size in here
			return $this->startUpload();
		
		}else{
			//check more errors here
			if($this->_FILES_OBJECT['error']==4){
				$this->status = "A File is required";
			}else{
				$this->status = "File upload error";
			}
			return false;
		}

	}
	
	//helps us convert to a different size array
	function convertSizeArray($a){
		$na = array();
		$x=0;
		foreach($a as $v){
				$na[$x]['size'] = $v[0];
				$na[$x]['w'] =$v[1];
				$na[$x]['h'] =$v[2];
				$na[$x]['crop'] = $v[3];
			$x++;
		}
		return $na;
	}
	
	function addSize($sizeName="_o",$maxWidth=false,$maxHeight=false,$crop=false){
			//immediately change the typical type of upload becasue it's not
			if($this->ffmpeg==false) $this->upload_typical=false;
			$a = count($this->sizes);
			$this->sizes[$a]['size'] = $sizeName;
			$this->sizes[$a]['w'] = $maxWidth;
			$this->sizes[$a]['h'] = $maxHeight;
			$this->sizes[$a]['crop'] = $crop;
	}
	
	//guesses our extensions
	function guessExtension(){
		$v = $this->file_org_name;
		
		if($v!=""){
			$vc = explode(".",$v);
			if(is_array($vc)){
				$vd = array_reverse($vc);
				$ext = ($vd[0]==$v) ? "" : $vd[0];
			}else{
				$ext="";
			}
			$this->ext = ($ext=="") ? $ext : ".".$ext;
			//fix .jpeg
			$this->ext = ($this->ext==".jpeg") ? ".jpg" : $this->ext;
		}else{
			//$ft filetype
		}

		
		return strtolower($this->ext );
	}
	
	function fixFilePath(){
		$v = $this->upload_path;
		$a = substr($v,-1);
		$b = substr($v,-2);
		$path = ($a=='/') ? $v : ((($a!="/") and ($b!="")) ?  $v."/" : $v);
		return $path;
	}
	
	
	function checkMIME(){
	    
		$file_type = $this->_FILES_OBJECT['type'];
		
		
		if($this->upload_typical){
			if($this->check_mimes){
				$aAllowedFiles = array();
				if(!is_array($this->check_mimes)){ 
					array_push($aAllowedFiles,$this->check_mimes);
				}else{
					$aAllowedFiles = $this->check_mimes;
				}
			
				$mime_ok = (in_array($file_type,$aAllowedFiles)) ? true : false;
			
				return  (!$mime_ok) ? false : true;
			}else{
				return true;	
			}
		}else{
		    //"application/octet-stream"
			$aAllowedImages = array("text/plain","image/gif","image/png","image/x-jpeg","image/jpeg","image/pjpeg","application/octet-stream"); 
			$mime_ok = (in_array($file_type,$aAllowedImages)) ? true : false;
			return  (!$mime_ok) ? false : true;
		}
	}
	
	function startUpload(){	
				
			if($this->checkMIME()){
				
				#If typical is false we can create our images
				if(!$this->upload_typical){
						$mass_success = true;
						$tmp_mass_success =false;
						
						
						$org_dim = getimagesize($this->file_tmp_name);
						if($org_dim[0] <= $this->max_img_width){
						     foreach ($this->sizes as $size => $value){
										//added the source
        							$tmp_mass_success =  $this->saveImage($this->file_tmp_name,$this->file_tmp_name,$value['size'],$this->upload_path, $value['w'], $value['h'], $value['crop']);
        							if(!$tmp_mass_success){
        								$mass_success = false;
        							}
        					 }
        					
        					//remember to save original if needed
        					if($this->copy_original){
    							$this->saveFile();
    						}
						}else{
						    $this->status = "Image width too large";
						    $mass_success = false;
						    //exit();
						}
					       
						return $mass_success;
						
				}elseif($this->ffmpeg==true){
					$result =  $this->saveFFVideo();
					if(!$result){
						$this->status = "File upload failed";
					}
					return $result;
				}else{
					#We upload a normal file	
					$result =  $this->saveFile();
					if(!$result){
						$this->status = "File upload failed";
					}
					return $result;
				}
				
			}else{
				$this->status="Invalid MIME";
				return false;
			}
	}


	//used to generate 49r394uf  type hash
	function getFileHash($source_name,$pID=0){
		$s_unique = date("F j, Y, g"); 
		$hash_string = md5($source_name.$s_unique.$pID);
		$hash_length = ($this->hash_length <=0) ? 10 : $this->hash_length;
		return  substr($hash_string, 0, $hash_length);
	}


	//evaluates the final name we are choosing
	function evalFinalName($pName,$ext=NULL,$size=""){
		//fix extensions here
		if($ext==NULL){
			$ue = ($this->ext==".jpeg") ? ".jpg" : $this->ext;
		}else{
			$ue = $ext;
		}
		
		$fn ="$pName$size$ue" ;
		return $fn;
	}
	
	//gets nice format for file 
	function getNiceFormat($pName,$per=false){
		$rep_name = str_replace(" ","_",$pName);
		if($per){
			$rep_name = str_replace(".","_",$rep_name);
		}
		$rep_name = str_replace("&","n",$rep_name);
		return $rep_name;
	}
	
	//fixes prenames
	function getPreExtName($inc=false){
		$ext_len = strlen($this->ext);
		$sub_text = -($ext_len);
		$pre_name = ($ext_len==0) ? $this->file_org_name : substr($this->file_org_name,0,$sub_text);
		$final_pre_name = (!$inc) ? $pre_name : $pre_name."_".$inc;
		if($this->use_niceformat){
			$final_pre_name = $this->getNiceFormat($final_pre_name);
		}
		return $final_pre_name;
	}
	
	//main function to generate the name
	function getFileName($source,$ext=NULL,$optionalSize=""){
			/*
			Check to see if filename needs to be unique. By default YES.
			If file already exists..we make this more unique
			*/
			$this->size_name = $optionalSize;
			//finalname includes extension and/or sizes
			
			//if you don't pass a photo it does a hash like 2b4j53j4_s.jpg, or 2b4j53j4.mp3
			if(!$this->file_name){
				//non-typicals must use hash system
				if(($this->use_filehash) or (!$this->upload_typical)){
					$this->hash_ticket = $this->getFileHash($source);
					$this->finalname = $this->evalFinalName($this->hash_ticket,$ext,$this->size_name);
					$this->rawname = $this->hash_ticket;
					$i=0;
					 while(file_exists($this->upload_path.$this->finalname)){ 
	                    $i++; 
						$this->hash_ticket = $this->getFileHash($source,$i);
						$this->finalname = $this->evalFinalName($this->hash_ticket,$ext,$this->size_name);
						$this->rawname = $this->hash_ticket;
	                }
				}else{
					//non-hash increment type like madrid.mp3,madrid1.mp3
					$pre_name = $this->getPreExtName();
					$this->finalname = $this->evalFinalName($pre_name);
					$this->rawname = $this->finalname;
					$i=0;
					 while(file_exists($this->upload_path.$this->finalname)){ 
	                    $i++; 
						$pre_name = $this->getPreExtName($i);
						$this->finalname = $this->evalFinalName($pre_name,$ext);
						$this->rawname = $this->finalname;
	                }
					
				}
				
			}else{
				//if you do pass a photo it does "myFileName[_size].jpg" otherwise it does myFileName.mp3 etc..
				//and it will over-write the old file!
				$this->rawname = $this->file_name;
				$this->finalname = $this->evalFinalName($this->file_name,$ext);
				if($this->use_niceformat){
					$this->finalname = $this->getNiceFormat($this->finalname,false);
					
				}
			}
			
		return $this->finalname;	
	}
	
	//attempts to delete the file
	function delete($aFileName=false){
	
		$r = false;
		if($aFileName!=false){
			$filepack = $this->upload_path.$aFileName;
			while(file_exists($filepack)){ 
	          	$r = unlink($filepack);
	        }
		}
			
		return $r;
	}
	
	//deletes files connected to sizearrays
	function deleteSizeArray($hash=false,$aSizes=false){
		$r = false;
		$rs = '';
		$ext = (Request::get('ext')!='') ? Request::get('ext') : $this->ext;
		if((is_array($aSizes)) and ($hash)){
			array_push($aSizes,"");
			foreach($aSizes as $size){
			    //this checks for two different types of arrays aSizes could be
			    $size = (is_array($size)) ? $size[0] : $size;
				$filepack = $this->upload_path.$hash.$size.$ext;
				$rs.= '--'.$filepack;
		
				while(file_exists($filepack)){ 
		          	$r = unlink($filepack);
					
		        }
			}
		}
		
		return $rs;
	}
	
	//deletes files connected to sizearrays
	function cropSizeArray($cords,$hash=false,$aSizes=false,$canvas=""){
		$rtn = false;
		$ext = (Request::get('ext')!='') ? Request::get('ext') : $this->ext;

		
		if((is_array($aSizes)) and ($hash)){
			//array_push($aSizes,"");
			//print_r($aSizes);
			foreach($aSizes as $size){
	
			    	$size_name =  $size[0] ;
					$targ_w = $size[1];
					$targ_h = $size[2];
						
						//you got coords from this canvas scaled version
						$canvas_copy =  $this->upload_path.$hash."_canvas.jpg";
						$size_copy = getimagesize($canvas_copy);
						$canvas_w= $size_copy[0];
						$canvas_h= $size_copy[1];
						
						//you pull data from this file
						$src = $this->upload_path.$hash."_hires.jpg";
						$size_src = getimagesize($src);
						$src_w= $size_src[0];
						$src_h= $size_src[1];
						
						
					$cords_x = ceil( ($cords['x'] * $src_w) / $canvas_w );
					$cords_y = ceil( ($cords['y'] * $src_h) / $canvas_h );
					
					$cords_w = ceil( ($cords['w'] * $src_w) / $canvas_w );
					$cords_h = ceil( ($cords['h'] * $src_h) / $canvas_h );
					
					//$cords['x'] = $cords_x
					
					
					$targ_image = $this->upload_path.$hash.$size_name.".jpg";
				
					// Resample
					$temp_image = imagecreatetruecolor($targ_w, $targ_h);
					$image = imagecreatefromjpeg($src);
					imagecopyresampled($temp_image, $image, 0, 0, $cords_x ,  $cords_y, $targ_w, $targ_h, $cords_w, $cords_h);
					// Output
					// $this->jpg_quality
					$rtn = imagejpeg($temp_image,$targ_image, $this->jpg_quality);
					imagedestroy($temp_image);
					
				
			}
		}
		
		return $rtn;
	}
	
	//cropsFromImage Name passed files connected to sizearrays
	function cropFromImage($cords,$src=false,$hash,$aSizes=false){
		
		$rtn = false;
		$ext = (Request::get('ext')!='') ? Request::get('ext') : $this->ext;

		
		if((is_array($aSizes)) and ($src)){
			//array_push($aSizes,"");
			//print_r($aSizes);
			foreach($aSizes as $size){
	
			    	$size_name =  $size[0] ;
					$targ_w = $size[1];
					$targ_h = $size[2];

						
					$cords_x = $cords['x'];
					$cords_y =  $cords['y'];
					
					$cords_w = $cords['w'];
					$cords_h =  $cords['h'];

					$targ_image = $this->upload_path.$hash.$size_name.".jpg";
			 
					// Resample
					$temp_image = imagecreatetruecolor($targ_w, $targ_h);
			
					$image = imagecreatefromjpeg($src);
					imagecopyresampled($temp_image, $image, 0, 0, $cords_x ,  $cords_y, $targ_w, $targ_h, $cords_w, $cords_h);
					// Output
					// $this->jpg_quality
					$rtn = imagejpeg($temp_image,$targ_image, $this->jpg_quality);
					imagedestroy($temp_image);

			}
		}
		
		return $rtn;
	}
	
	//gets dimenions and returns based on limit w and limit h
	function getDimensions($frameWidth,$frameHeight,$limit_w,$limit_h){
		$new_w = $limit_w;
		$new_h = $limit_h;
		if($frameWidth >= $limit_w  || $frameHeight >= $limit_h){
			// set sizes
			$new_w = ($frameWidth * $limit_h) / $frameHeight;
			if ($new_w > $limit_w) {
				$new_w = $limit_w;
				$new_h = ($frameHeight * $new_w) / $frameWidth;
			} else {
				$new_h = $limit_h;
			}
		}
		$dim = array();
		$dim[0]= ceil($new_w);
		if(($dim[0] % 2)!=0)  $dim[0] = $limit_w;
		$dim[1]= ceil($new_h);
		return $dim;
	}
	
	
	function saveFFVideo(){
					//force it
					$this->use_filehash=true;
					$this->newFileName = $this->getFileName($this->file_tmp_name);
					if(is_uploaded_file($this->file_tmp_name)){
						$newfile = $this->upload_path.$this->newFileName;
						$targhash = $this->upload_path.$this->hash_ticket;
						$this->upload_result =  (copy($this->file_tmp_name, $newfile)) ? true : false;
						// Maintains aspect ratio with dynamic Y dimension
					
						$poster = new ffmpeg_movie($newfile);
						$frame = $poster->getFrame();
					
						if($this->ffmpeg_poster==true){	
						
							$tempimg = $frame->toGDImage();
							$poster_image = $targhash."_poster.jpg";
							$tmpvideo = imagejpeg($tempimg,$poster_image,90);
					
							//makes all the sizes for poster frames
							if($this->sizes){
								foreach ($this->sizes as $size => $value){
											//added the source
										$tmp_mass_success =  $this->saveImage($this->file_tmp_name,$poster_image,$value['size'],$this->upload_path, $value['w'], $value['h'], $value['crop']);
										 usleep(100000);
								
								 }
							}
							//now remove the original frame gd
							unlink($poster_image);
						}
					
					 	//CONTSTRAING OUR HEIGHT
						$frameHeight = $frame->getHeight();
						$frameWidth = $frame->getWidth();
				
					
						$new_w = $frameWidth;
						$new_h = $frameHeight;
					
				
					
						//high quality
						$dim = $this->getDimensions($frameWidth,$frameHeight,640,360);
						$pixels = $dim[0]."x".$dim[1];
						$params = "-y -f flv -s $pixels -ar 44100 -vcodec flv -r 24 -b 1000k";
						$str = "ffmpeg -i ".$newfile." $params ".$targhash."_high.flv";
						exec($str,$output);
					
						//low quality
						$dim = $this->getDimensions($frameWidth,$frameHeight,356,200);
						$pixels = $dim[0]."x".$dim[1];
						$params = "-y -f flv -s $pixels -ar 44100 -vcodec flv -r 24 -b 550k";
						$str = "ffmpeg -i ".$newfile." $params ".$targhash."_low.flv";
						exec($str,$output);
					
						/*
						$pixels = $new_w."x".$new_h;
						//$pixels = "640x360";
						$params = "-y -f flv -s $pixels -vcodec flv -ar 44100 -ab 128 -r 29 -b 2000k";
						$str = "ffmpeg -i ".$newfile." $params $targhash.flv";
						exec($str,$output);
						*/
						$this->upload_result = $output;
					}else{
						$this->upload_result = false;
					}		
				return $this->upload_result;
			}
	
	
	function saveFile(){
			
			$this->newFileName = $this->getFileName( $this->file_tmp_name );
			if(is_uploaded_file($this->file_tmp_name)){
				$this->upload_result =  (copy($this->file_tmp_name, $this->upload_path.$this->newFileName)) ? true : false;
			}else{
				$this->upload_result = false;
			}		
		return $this->upload_result;
	}
	
	
	function fastimagecopyresampled($dst_image=false,$src_image=false,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h){
        if($dst_image==false or $src_image==false){
            return false;
        }
        $quality = $this->fast_quality;
        if($quality <=1 ){
            $temp = imagecreatetruecolor($dst_w + 1, $dst_h + 1);
            imagecopyresized($temp,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w+1,$dst_h+1,$src_w,$src_h);
            imagecopyresized($dst_image,$temp,0,0,0,0,$dst_w,$dst_h,$dst_w,$dst_h);
            imagedestroy($temp);
        }elseif( ($quality < 5) and   ( (($dst_w * $quality) < $src_w) or (($dst_h *$quality) < $src_h)) ){
            $tmp_w = $dst_w * $quality;
            $tmp_h = $dst_h * $quality;
            $temp = imagecreatetruecolor($tmp_w + 1,$tmp_h +1);
            imagecopyresized($temp,$src_image,$dst_x * $quality,$dst_y * $quality,$src_x,$src_y,$tmp_w+1,$tmp_h+1,$src_w,$src_h);
            imagecopyresampled($dst_image,$temp,0,0,0,0,$dst_w,$dst_h,$tmp_w,$tmp_h);
			imagedestroy($temp);
        }else{
            imagecopyresampled($dst_image,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h);
        }

    }
	
	function saveImage($carbon_name,$file_source,$size_name ="_o", $filepath ="", $limit_w =false, $limit_h =false, $crop =false,$overName=false){
			//retrieve our filename
			if($overName==false){
				$new_name = $this->getFileName($carbon_name,'.jpg',$size_name);
			}else{
				$new_name = $overName.$size_name.".jpg";
			}
			
			

			if($limit_w != false){
				
				switch($this->ext){
					case ".jpg":
						$src_img = imagecreatefromjpeg($file_source);
					break;
					case ".png":
						$src_img = imagecreatefrompng($file_source);
					break;
					case ".gif":
						$src_img = imagecreatefromgif($file_source);
					break;
					default:
						$src_img = imagecreatefromjpeg($file_source);
					break;
				}
				
				$org_dim = getimagesize($file_source);
				
				if(is_bool($crop)){
					$cropType = ($crop==false) ? 2 : 1;
				}else{
					$cropType = ($crop=="height") ? 3 : 4;
				}
				
				$new_w = $org_dim[0];
				$new_h = $org_dim[1];
				
				if($cropType==1){ // crop to fit exactly
					
					// set sizes
					$new_w = ($org_dim[0] * $limit_h) / $org_dim[1]; // ? = width  so that  height = $limit_h
					if ($new_w < $limit_w) {
						$new_w = $limit_w;
						$new_h = ($org_dim[1] * $new_w) / $org_dim[0]; // ? = height  so that  width = $limit_w
					} else {
						$new_h = $limit_h;
					}
					// set crop
					// 
					
					if($this->crop_portrait!=false){
						
						if($new_h != $limit_h){
							$off_y=	($org_dim[1] > $org_dim[0]) ? (($new_h - $limit_h)/ $this->crop_portrait ) : ($new_h/2) - ($limit_h/2);
						}else{
							$off_y=0;
						}
						
					}else{
						$off_y = ($new_h != $limit_h) ? ($new_h/2) - ($limit_h/2) : 0 ;
					}
					
					
					$off_x = ($new_w != $limit_w) ? ($new_w/2) - ($limit_w/2) : 0 ;
					
		
					// make image
					$dst_img = imagecreatetruecolor($new_w,$new_h); 
					$this->fastimagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $org_dim[0], $org_dim[1]);
					$new_img = imagecreatetruecolor($limit_w,$limit_h); 
					
					$this->fastimagecopyresampled($new_img, $dst_img, 0, 0, $off_x, $off_y, $limit_w, $limit_h, $limit_w, $limit_h);
					$this->list_name = $this->upload_path.$new_name;
					$this->upload_result = imagejpeg($new_img, $this->list_name, $this->jpg_quality);
					
					//take out the trash
					imagedestroy($src_img);
					imagedestroy($dst_img);
					imagedestroy($new_img);
					
				}elseif($cropType==2){ // keep orig dimensions within
					
				
					if($org_dim[0] >= $limit_w || $org_dim[1] >= $limit_h){
						// set sizes
						$new_w = ($org_dim[0] * $limit_h) / $org_dim[1];
						if ($new_w > $limit_w) {
							$new_w = $limit_w;
							$new_h = ($org_dim[1] * $new_w) / $org_dim[0];
						} else {
							$new_h = $limit_h;
						}
						// make image
						$dst_img = imagecreatetruecolor($new_w,$new_h); 
						/*
						if($crop_portrait!=false){
							$this->crop_y = (($org_dim[1]-$new_h)/4) * 1;
						}
						*/
						$this->fastimagecopyresampled($dst_img, $src_img, 0, 0, $this->crop_x, $this->crop_y, $new_w, $new_h, $org_dim[0], $org_dim[1]);
						$this->upload_result = imagejpeg($dst_img, $this->upload_path.$new_name, $this->jpg_quality);
						//take out the trash
					}else{
						
						$this->upload_result = (copy($file_source, $this->upload_path.$new_name)) ? true : false;
					}
					imagedestroy($src_img);
					if(isset($dst_img)){
						imagedestroy($dst_img);
					}
					
				
				//crop is really like force height
				}elseif($cropType==3){
						
						/*
						if($org_dim[1] <= $limit_h){
							$new_h = $limit_h;
						}
						*/
						$new_h = $limit_h;
						// set sizes
						$new_w = ($org_dim[0] * $limit_h) / $org_dim[1];
						
						//check max width
						if ($new_w > $limit_w) {
							$new_w = $limit_w;
						}
						
						// make image
						$dst_img = imagecreatetruecolor($new_w,$new_h); 
						$this->fastimagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $org_dim[0], $org_dim[1]);
						$this->upload_result = imagejpeg($dst_img, $this->upload_path.$new_name, $this->jpg_quality);
						//take out the trash
						imagedestroy($src_img);
						imagedestroy($dst_img);
				
				//crop is really like force width		
				}elseif($cropType==4){
				
						
						$new_w = $limit_w;
						// set sizes
						//$new_w = ($org_dim[0] * $limit_h) / $org_dim[1];
						$new_h = ($org_dim[1] * $new_w) / $org_dim[0];
						
						//check max width
						if ($new_h > $limit_w) {
							$new_h = $limit_h;
						}
					
						// make image
						$dst_img = imagecreatetruecolor($new_w,$new_h); 
						$this->fastimagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $org_dim[0], $org_dim[1]);
						$this->upload_result = imagejpeg($dst_img, $this->upload_path.$new_name, $this->jpg_quality);
						//take out the trash
						imagedestroy($src_img);
						imagedestroy($dst_img);
				}
				
			}else{
				// just copy the original file
				$this->upload_result = (copy($file_source, $this->upload_path.$new_name)) ? true : false;	
			}
			
			return $this->upload_result;
	}


} //END OF CLASS

?>