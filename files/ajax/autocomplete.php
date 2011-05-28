<?php
//provide auto completion of paths for use with jquer ui autocomplete


// Init owncloud
require_once('../../lib/base.php');

// We send json data
// header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_USER::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

// Get data
$query = $_GET['term'];
$dirOnly=(isset($_GET['dironly']))?($_GET['dironly']=='true'):false;

if($query[0]!='/'){
	$query='/'.$query;
}

if(substr($query,-1,1)=='/'){
	$base=$query;
}else{
	$base=dirname($query);
}

$query=substr($query,strlen($base));
$queryLen=strlen($query);

// echo "$base - $query";

$files=array();

if(OC_FILESYSTEM::is_dir($base)){
	$dh = OC_FILESYSTEM::opendir($base);
	if(substr($base,-1,1)!='/'){
		$base=$base.'/';
	}
	while (($file = readdir($dh)) !== false) {
		if ($file != "." && $file != ".."){
			if(substr($file,0,$queryLen)==$query){
				$item=$base.$file;
				if((!$dirOnly or OC_FILESYSTEM::is_dir($item))){
					$files[]=(object)array('id'=>$item,'label'=>$item,'name'=>$item);
				}
			}
		}
	}
}
echo json_encode($files);

?>
