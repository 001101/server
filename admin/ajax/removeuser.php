<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_USER::isLoggedIn() || !OC_GROUP::inGroup( $_SESSION['user_id'], 'admin' )){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

$name = $_POST["username"];

// Return Success story
if( OC_USER::deleteUser( $name )){
	echo json_encode( array( "status" => "success", "data" => array( "username" => $name )));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Unable to delete user" )));
}

?>
