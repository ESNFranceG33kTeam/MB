<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');




//Définition des messages d'erreurs


if(isset($_POST['usrname']) && isset($_POST['pass'])){ //si pas d'erreur : verif pseudo/pass

	$bd = db_connect();
	
	$_POST['usrname'] = mysqli_real_escape_string($bd, $_POST['usrname']);

	$infosUsr = db_ligne($bd, "
								SELECT ben.id, ben.prenom, ben.nom, drt.general 
								FROM membres_benevoles AS ben
								LEFT JOIN membres_droits AS drt ON ben.id = drt.id
								WHERE login='".$_POST['usrname']."' 
								AND pass='".crypt($_POST['pass'], '$2a$07$esnnancy4everthebest$')."'");
	db_close($bd);


	if(!empty($infosUsr) && $infosUsr!==false){
	
		$bd = db_connect();
	
		$UpLastCo = db_exec($bd, "
								UPDATE membres_benevoles
								SET last_connect=NOW()
								WHERE login='".$_POST['usrname']."'");
		db_close($bd);
		
		
		if($UpLastCo){
			$exp_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 day'));
			//Création du token
			
			$TOKHeader = base64_encode ('{"alg": "HS256","typ": "JWT"}');
			$TOKPayLoad = base64_encode ('{"nomBDD": "'.$_SERVER['SERVER_NAME'].'","id": "'.$infosUsr[0].'","username": "'.$_POST['usrname'].'","droits": "'.$infosUsr[3].'","expat": "'.$exp_date.'","iat": "'.date('Y-m-d H:i:s').'"}');
			
			$TOKSignature = base64_encode (hash_hmac('sha256', $TOKHeader . "." . $TOKPayLoad, 'esnnancy4everthebestAPI', true));
			
			echo $TOKHeader . "." . $TOKPayLoad . "." . $TOKSignature;
			
		}
	}
}
