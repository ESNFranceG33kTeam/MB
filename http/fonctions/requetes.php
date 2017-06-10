<?php
ini_set("display_errors",0);
error_reporting(0);

include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

function db_connect(){	

	
	$tabIdBDD = $GLOBALS['SITE']->getIdBDD();
	
	$db = mysqli_connect($tabIdBDD['host'], $tabIdBDD['user'], $tabIdBDD['pass'], $tabIdBDD['name']) or die('Could not connect: ' . mysql_error());
		
	return $db;
}



function db_exec($bd, $requete){ //	SIMPLE EXECUTION

	mysqli_query($bd,"SET NAMES UTF8");
	$resultat = mysqli_query($bd, $requete);
	global $pageMessages;
	if($resultat==false)						{ array_push($pageMessages, array('type'=>'err', 'content'=>'Erreur SQL.')); return false; }

	else										{ return true; }

}

//	EXECUTE UNE REQUETE ET RETOURNE UN TABLEAU A 2 DIMENSIONS (lignes & colonnes)
function db_tableau($bd,$requete,$cle=null){

	mysqli_query($bd,"SET NAMES UTF8");
	$tab_resultat = array();
	$resultat = mysqli_query($bd,$requete);
	
	global $pageMessages;
	
	if($resultat==false)	{ array_push($pageMessages, array('type'=>'err', 'content'=>'Erreur SQL.')); return false; }
	else
	{
		// Tableau numérique / associatif
		if($cle==null){
			while($ligne = mysqli_fetch_array($resultat)){
				
				$tab_resultat[] = cleaner($ligne);
				
			}
		
		}else{
			while($ligne = mysqli_fetch_array($resultat)){
			
				$tab_resultat[$ligne[$cle]] = cleaner($ligne);
				
			}
		
		}
		
		
		
		
		return $tab_resultat;
	}
}

function db_ligne($bd, $requete){  // Retourne un tableau simple
	mysqli_query($bd,"SET NAMES UTF8");	
	$tab_resultat=array();
	$resultat = mysqli_query($bd, $requete);
	global $pageMessages;

	if($resultat==false){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Erreur SQL.')); return false; }
	else{
		while($ligne = mysqli_fetch_array($resultat))	{ $tab_resultat = cleaner($ligne); break; }
		return $tab_resultat;
	}
}

function db_colonne($bd, $requete){ //	EXECUTE UNE REQUETE ET RETOURNE UN TABLEAU DE VALEURS SUR UNE SEULE CLE (liste d'identifiants par exemple)
	mysqli_query($bd,"SET NAMES UTF8");
	$tab_resultat=array();
	$resultat = mysqli_query($bd, $requete);
	global $pageMessages;
	
	if($resultat==false){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Erreur SQL.'));
		return false;
	}else{
		while($ligne = mysqli_fetch_array($resultat)){
			$tab_resultat[] = cleaner($ligne[0]);
		}
		return $tab_resultat;
		}
}
		

function db_valeur($bd, $requete){ //	EXECUTE UNE REQUETE ET RETOURNE UNE VALEUR

	mysqli_query($bd,"SET NAMES UTF8");
	$resultat = mysqli_query($bd, $requete);
	global $pageMessages;
	
	if($resultat==false){ 
		array_push($pageMessages, array('type'=>'err', 'content'=>'Erreur SQL.'));
		return false; 
		
	}elseif(mysqli_num_rows($resultat) > 0){ 
		$ligne = mysqli_fetch_array($resultat);
		return cleaner($ligne[0]);
	}
}

	
function db_lastId($bd){ //Récupère le dernier id inseré automatiquement par un Auto_Increment
	return mysqli_insert_id($bd);
}

function db_close($bd){ //FERME LA CONNEXION
	mysqli_close($bd);
}

function cleaner($data){


	if(is_array($data)){
		
		foreach ($data as $k => $v){
		
			$data[$k] = strip_tags($v, '');
		
		}
		
		return $data;
	
	
	}else{

		return strip_tags($data);
	
	}

}
?>