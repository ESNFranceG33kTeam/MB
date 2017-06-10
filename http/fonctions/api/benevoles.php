<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/api/fonctionsAPI.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/requetes.php');

$headers = apache_request_headers();

if(verifToken($headers['Authorization'])){
	
	
//recup données
$bd = db_connect();
$tabUsr = db_tableau($bd, "
						SELECT ben.prenom, ben.nom, ben.fb, ben.tel, ben.mail, ben.dob, ben.adresse, ben.etudes, ben.voiture, drt.general, drt.roles
						FROM membres_benevoles AS ben
						LEFT JOIN membres_droits AS drt ON ben.id = drt.id
						ORDER BY ben.prenom ASC, ben.nom ASC");
db_close($bd);
	
	
echo json_encode($tabUsr);

	
}else{
	
	echo "Jeton invalide";
	
}

die();

?>