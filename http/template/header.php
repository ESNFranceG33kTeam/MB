<?php
session_start();
$pageMessages=array();

//Verif Connexion
if(!(isset($_SESSION['connect']) && $_SESSION['connect'] === true)) {

	if(defined('NEED_CONNECT') && NEED_CONNECT==false){
		define('IS_CONNECT',false);
		define('DROITS','AUCUN');	
	
	}else{
		$_SESSION = array();
		session_destroy();
		session_start();
		$_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
		header('Location: http://'.$_SERVER['HTTP_HOST'].'/connect.php');
		die();
	}
	
}else{

	define('IS_CONNECT',true);
	define('NOM_BDD',$_SESSION['nomBDD']);
	define('ID',$_SESSION['id']);	
	define('PRENOM',$_SESSION['prenom']);	
	define('NOM',$_SESSION['nom']);		
	define('DROITS',$_SESSION['droits']);	
	
	foreach($_SESSION['postMessages'] as $mess){
		if(isset($mess['type'])){
			array_push($pageMessages, array('type'=> $mess['type'], 'content'=> $mess['content']));
		}else{
			array_push($pageMessages, array('type'=> $mess[0], 'content'=> $mess[1]));
		}
	}
	
	unset($mess);
	$_SESSION['postMessages'] = array();	
	$affMenu = true;
}

//Config générale
include_once($_SERVER['DOCUMENT_ROOT'].'/../data/SelectSite.php');
global $SITE;
$SITE= new SelectSite();

include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/requetes.php');


$bd = db_connect();
$tabChamps = db_tableau($bd, "SELECT champ, valeur FROM gestion_config_general","champ");
db_close($bd);


//fonctions récurrentes
function requireDroits($droits){

	$ok = false;

		switch ($droits) {
		
			case "probatoire":
			
				$ok = ((DROITS == "bureau") || (DROITS == "membre") || (DROITS == "probatoire")) ? true : false;
				break;

			case "membre":
			
				$ok = ((DROITS == "bureau") || (DROITS == "membre")) ? true : false;
				break;
				
			case "bureau":

				$ok = (DROITS == "bureau") ? true : false;
				break;

		}

	if(!$ok){
		array_push($_SESSION['postMessages'],array("err", "Vous n'avez pas les droits nécessaires."));
		$redir= (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : 'http://'.$_SERVER['HTTP_HOST'].'/connect.php';
		header('Location: '.$redir);
		die();
	}
	
}

function checkDroits($droits){
	$ok = false;

		switch ($droits) {
		
			case "probatoire":
			
				$ok = ((DROITS == "bureau") || (DROITS == "membre") || (DROITS == "probatoire")) ? true : false;
				break;

			case "membre":
			
				$ok = ((DROITS == "bureau") || (DROITS == "membre")) ? true : false;
				break;
				
			case "bureau":

				$ok = (DROITS == "bureau") ? true : false;
				break;

		}

	return $ok;
}

function addCaisse($descr, $somme, $recu, $typeMessage, $idRef){
	$bdd = db_connect();
	
	//recup id periode courante
	$idPeriode = db_valeur($bdd, "		
					SELECT id
					FROM gestion_caisse_periodes
					WHERE dteEnd IS NULL
					ORDER BY id DESC");	
					
					
	if(empty($idPeriode)){		
		$addCaissePeriode = db_exec($bdd, "INSERT INTO gestion_caisse_periodes(dteStart) VALUES(NOW())");
	}
					
	if($idPeriode!==false && is_numeric($somme) && $somme !==0){
	
		$descr = mysqli_real_escape_string($bdd, $descr);

		$addCaisse = db_exec($bdd, "
						INSERT INTO gestion_caisse_log (idPeriode, idRef, dte, descr, somme, recu, addBy)
						VALUES('".$idPeriode."',".$idRef.", NOW(),'".$descr."','".$somme."','".$recu."','".PRENOM." ".NOM."')");	
		
		if($addCaisse!==false){
		
			$pluriel = (abs($somme)>=2)?true:false;
			
			switch ($typeMessage) {

				case "local":
					global $pageMessages;
					array_push($pageMessages,array('type'=>'cash', 'content' => abs($somme)."€ ".(($pluriel)?"ont":"a")." été ".(($somme>0)?"ajouté":"retiré").(($pluriel)?"s ":" ").(($somme>0)?"à":" de ")." la caisse."));
					break;
					
				case "ext":
					array_push($_SESSION['postMessages'],array("cash", abs($somme)."€ ".(($pluriel)?"ont":"a")." été ".(($somme>0)?"ajouté":"retiré").(($pluriel)?"s ":" ").(($somme>0)?"à":" de ")." la caisse."));
					break;
					
				case "none":
					break;
			}
		}	
	}			
	db_close($bdd);
}

?>