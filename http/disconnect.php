<?php

session_start();

if($_SESSION['connectGalaxy']){
	
	$source = "decoGalaxy";
	
}else{
	
	$source = "deco";
}


$_SESSION = array();
session_destroy();

header('Location: http://'.$_SERVER['HTTP_HOST'].'/connect.php?src='.$source);
die();
?>