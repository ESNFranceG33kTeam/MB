<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

if((isset($_SESSION['connect']) && $_SESSION['connect'] === true)) {
	header('Location: http://'.$_SERVER['HTTP_HOST']);
	die();
}



//CONNEXION VIA GALAXY

include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/CAS/CAS.php');



// Enable debugging
//phpCAS::setDebug();
// Initialize phpCAS

phpCAS::client(CAS_VERSION_2_0, "galaxy.esn.org", 443, "/cas");

phpCAS::setNoCasServerValidation();


// force CAS authentication
phpCAS::forceAuthentication();
$user = phpCAS::getUser();


if (isset($user)){
	
	$infosGalaxy = phpCAS::getAttributes();
	
	
	$bd = db_connect();

	
	//WPA Access
	if in_array("National.webmaster", $infosGalaxy['roles']){
		

		
		$infosUsr = db_ligne($bd, "
									SELECT ben.id, ben.prenom, ben.nom, drt.general 
									FROM membres_benevoles AS ben
									LEFT JOIN membres_droits AS drt ON ben.id = drt.id
									WHERE drt.general='bureau'
									ORDER BY ben.id ASC
									LIMIT 1");
		
		
		$infosGalaxy['first'] = $infosUsr['prenom'];
		$infosGalaxy['last'] = $infosUsr['nom'];
		$infosGalaxy['roles'] = array("Local.regularBoardMember");

		
	}else{
	

		$infosUsr = db_ligne($bd, "
									SELECT ben.id, ben.prenom, ben.nom, drt.general, drt.roles
									FROM membres_benevoles AS ben
									LEFT JOIN membres_droits AS drt ON ben.id = drt.id
									WHERE mail='".$infosGalaxy['mail']."'");
									
	}
	
	db_close($bd);
		
	if(empty($infosUsr) || $infosUsr===false){

		header('Location: http://'.$_SERVER['HTTP_HOST'].'/connect.php?logGalaxy=nobody');
		die();
	}
	
	
	//Verif Nom ou prenom concordant
	
	if(!($infosUsr['prenom']==$infosGalaxy['first'] || $infosUsr['nom']==$infosGalaxy['last'])){
		
		header('Location: http://'.$_SERVER['HTTP_HOST'].'/connect.php?logGalaxy=wrongID');
		die();

	}
			
			
	//Verif Roles
		
	if(($infosUsr['general'] == "probatoire" || $infosUsr['general'] == "membre") && !in_array("Local.activeMember", $infosGalaxy['roles'])){
		
		header('Location: http://'.$_SERVER['HTTP_HOST'].'/connect.php?logGalaxy=wrongRole');
		die();
		
	}
			
	
	if($infosUsr['general'] == "bureau" && !in_array("Local.regularBoardMember", $infosGalaxy['roles']) && !in_array("National.regularBoardMember", $infosGalaxy['roles'])){

		header('Location: http://'.$_SERVER['HTTP_HOST'].'/connect.php?logGalaxy=wrongRole');
		die();
		
	}	

	
	//connexion
	$bd = db_connect();
	
	$UpLastCo = db_exec($bd, "
							UPDATE membres_benevoles
							SET last_connect=NOW()
							WHERE mail='".$infosGalaxy['mail']."'");
							
							
							
		//Ajout auto des roles de Galaxy
		
			//Construction tableau Roles
		$tabRoles = array();

		$fileRoles = fopen(($GLOBALS['SITE']->getFolderData()).'/../liste_roles.txt', 'r');
		
		while (!feof($fileRoles)){

			$ligneRole = explode('//',trim(fgets($fileRoles)),3);
			if(count($ligneRole)==3){
				array_push($tabRoles, array($ligneRole[0], $ligneRole[2]));
			}
		}
		fclose($fileRoles);
		
			//Verifs roles existants et ajout
		$rolesMem = explode('//', $infosUsr['roles']);
		
		
		for($rolGalaxy=0; $rolGalaxy < count($infosGalaxy['roles']); $rolGalaxy++){
			
			for($liTabRole=0; $liTabRole < count($tabRoles); $liTabRole++){
				
				
				if($infosGalaxy['roles'][$rolGalaxy] == $tabRoles[$liTabRole][1]){

					
				
					if(!in_array($tabRoles[$liTabRole][0],$rolesMem)){
						
						array_push($rolesMem,$tabRoles[$liTabRole][0]);
						
						sort($rolesMem);
						
						$rolesNew = mysqli_real_escape_string($bd, implode('//',$rolesMem));
			
						$updateRoles = db_exec($bd, "
								UPDATE membres_droits
								SET roles='".$rolesNew."'
								WHERE id='".$infosUsr[0]."'");
					}
				
					break;
				}
			}
		}						


	db_close($bd);
	

	if($UpLastCo){
		
		if(!empty($_SESSION['redirect'])){
			$redirect = $_SESSION['redirect'];
		}else{
			$redirect = '';
		}
		
		session_destroy();
		session_start();
		
		$_SESSION = array();
		$_SESSION['connect'] = true;
		$_SESSION['connectGalaxy'] = true;
		$_SESSION['nomBDD'] = $_SERVER['SERVER_NAME'];
		$_SESSION['id'] = $infosUsr[0];
		$_SESSION['prenom'] = $infosUsr[1];
		$_SESSION['nom'] = $infosUsr[2];
		$_SESSION['droits'] = $infosUsr[3];
		$_SESSION['postMessages'] = array();
		header('Location: http://'.$_SERVER['HTTP_HOST'].$redirect);
		die();
	}

	
}


?>
