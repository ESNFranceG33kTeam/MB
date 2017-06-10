<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');


ini_set("display_errors",0);
error_reporting(0);

date_default_timezone_set ("Europe/Paris");



//Recupération configuration

$bd = db_connect();

$nbNewFiles = db_valeur($bd, "SELECT valeur FROM gestion_onedrive_config WHERE champ='nb_newfiles'");
$affNoms = db_valeur($bd, "SELECT valeur FROM gestion_onedrive_config WHERE champ='aff_noms'");

$tabFolders = db_tableau($bd, "SELECT id, type, idFolder
							FROM gestion_onedrive_folders
							ORDER BY type");		
db_close($bd);

include_once('getToken.php');
$fileTok = fopen(($GLOBALS['SITE']->getFolderData()).'/tokenOneDrive.txt', 'r');
$accessTok = fgets($fileTok);
fclose($fileTok);

if(!empty($accessTok)){define('ACCESS_TOKEN', $accessTok);}

define('FILE_EX',($GLOBALS['SITE']->getFolderData()).'/exFiles.txt');
define('FILE_NEW',($GLOBALS['SITE']->getFolderData()).'/NewFiles.html');
define('FILE_NEW_BOARD',($GLOBALS['SITE']->getFolderData()).'/NewFilesBoard.html');

define('REFRESH_NEWFILES', 60); //nombre de minutes toutes lesquelles le script s'execute
define('NB_NEWFILES', $nbNewFiles); //nombre d'entrées à conserver


//Construction tableaux de fichiers

$IDracine = "";
$tabExclusions = array();
$tabBoardOnly = array();

if($tabFolders !== false){
	for($i=0; $i<count($tabFolders); $i++){

		if($tabFolders[$i]['type']=="racine"){
			$IDracine = $tabFolders[$i]['idFolder'];
		
		}elseif($tabFolders[$i]['type']=="exclus"){
			array_push($tabExclusions,$tabFolders[$i]['idFolder']);
		
		}elseif($tabFolders[$i]['type']=="board"){
			array_push($tabBoardOnly,$tabFolders[$i]['idFolder']);
		}
	}
}else{ die(); }


if(defined('ACCESS_TOKEN')){

//Récuperation espace restant et disponible
$storage=getData("quota");

$fichierStorage = fopen(($GLOBALS['SITE']->getFolderData()).'/storageSpaceOneDrive.txt', 'r+');
fseek($fichierStorage, 0);
ftruncate($fichierStorage, 0);
fputs($fichierStorage, $storage['available']."\n".$storage['quota']);
fclose($fichierStorage);

	
//CheckNewFile

$racine = proprietesRacine($IDracine);
$elements=getData($IDracine);

$dossiers = array();
$fichiers = array();

//INITIALISATION avec le dossier racine

for($i=0;$i<count($elements['data']);$i++){

	if(!in_array($elements['data'][$i]['id'],$tabExclusions)){
		
		if(in_array($elements['data'][$i]['id'],$tabBoardOnly)){
			$elements['data'][$i]['boardOnly']=true;
		}else{
			$elements['data'][$i]['boardOnly']=false;
		}
	
		if(($elements['data'][$i]['type']=='folder') || ($elements['data'][$i]['type']=='album')){
			array_push($dossiers,$elements['data'][$i]);

			
		}elseif(!empty($elements['data'][$i]['type'])){
			array_push($fichiers,tabFichier($elements['data'][$i],array('name' => $racine['name'], 'link' => $racine['link'])));

		}
	}
}
unset($elements);
unset($i);
$compt = count($dossiers);
$ind = 0;	

//Recherche récursive en largeur
while($compt!=0){

	$chemin = $dossiers[$ind]['id'];
	$elements=getData($chemin);

	for($i=0 ; $i < count($elements['data']) ; $i++){

		if(!in_array($elements['data'][$i]['id'],$tabExclusions)){
		
			//Propagation de la restriction aux sous dossiers et fichiers
			if(in_array($elements['data'][$i]['id'],$tabBoardOnly) || $dossiers[$ind]['boardOnly']==true){
				$elements['data'][$i]['boardOnly']=true;
			}else{
				$elements['data'][$i]['boardOnly']=false;
			}
			
			//Ajout aux tableaux
			if(($elements['data'][$i]['type'] == 'folder') || ($elements['data'][$i]['type'] == 'album')) {
				
				array_push($dossiers, $elements['data'][$i]);
				$compt++;

			}elseif(!empty($elements['data'][$i]['type'])) {
				
				array_push($fichiers, tabFichier($elements['data'][$i], $dossiers[$ind]));

			}
		}
	}

	unset($elements);
	$ind++;
	$compt--;

}//fin boucle while	


unset($vale);
unset($compt);

$tabTemp = json_decode(file_get_contents(FILE_EX), true);

if(!empty($tabTemp)){

	
	//Comparaison:

	$tabModifFiles = array();
	
	$testNew = true;
	$testModif = true;
	foreach($fichiers as $fic){
		foreach($tabTemp as $fict){
			if($fic['id'] == $fict['id']){
				$testNew = false;
				if($fic['date'] != $fict['date']){ 
					$testModif = true;
				
					if($fic['name'] != $fict['name']){ //renommé
				
						$fic['modif']="renom";
						$fic['ex']=$fict['name'];
						array_push($tabModifFiles, $fic);
						$testModif = false;
					}
					if($fic['parent']['lien'] != $fict['parent']['lien']){ //déplacé
				
						$fic['modif']="dep";
						$fic['ex']=array('name' => $fict['parent']['name'], 'lien' => $fict['parent']['lien']);
						array_push($tabModifFiles, $fic);
						$testModif = false;
					}
					if($testModif){ //modifié
				
						$fic['modif']="modif";
						array_push($tabModifFiles, $fic);
						
					}
				}	
			}
		}
		if($testNew){ //nouveau fichier
		
			$fic['modif']="new";
			array_push($tabModifFiles, $fic);
		
		}
		$testNew = true;
	}
	
	unset ($fict);
	unset ($fic);
	
	if(count($fichiers)>0){
		
		foreach($tabTemp as $fic){
			$testSup=true;
			foreach($fichiers as $ficf){
				if($fic['id'] == $ficf['id']){
					$testSup=false;
					break;
				}
			}
			if($testSup){
				$fic['modif']="suppr";
				$fic['date']=time()-60*REFRESH_NEWFILES;
				array_push($tabModifFiles, $fic);
			}
			
		}
		unset ($fic);
		unset ($ficf);
	}
	//Fin des comparaisons
	
	
	if(count($tabModifFiles)>0){ //si modifs à faire
	
		uasort($tabModifFiles, 'compareDate');
		$newModifs = HTMLisation($tabModifFiles);
		fillFile(FILE_NEW,$newModifs,false);
		fillFile(FILE_NEW_BOARD,$newModifs,true);	

		
		//mise à jour du fichier temp:
		$fichierTemp = fopen(FILE_EX, 'r+');
		$jsonfichiers = json_encode($fichiers);
		fseek($fichierTemp, 0);
		ftruncate($fichierTemp, 0);
		fputs($fichierTemp, $jsonfichiers);
		fclose($fichierTemp);


	} //fin if si pas de modifs
	
	
}else{ //construction du fichier temp si vide

	$tabModifFiles = array();

	foreach($fichiers as $fic){
		$fic['modif']="new";
		array_push($tabModifFiles, $fic);
	}

	uasort($tabModifFiles, 'compareDate');
	$newModifs = HTMLisation($tabModifFiles);
	fillFile(FILE_NEW,$newModifs,false);
	fillFile(FILE_NEW_BOARD,$newModifs,true);
	
	
	//mise à jour du fichier temp:
	$fichierTemp = fopen(FILE_EX, 'r+');
	$jsonfichiers = json_encode($fichiers);
	fseek($fichierTemp, 0);
	ftruncate($fichierTemp, 0);
	fputs($fichierTemp, $jsonfichiers);
	fclose($fichierTemp);
}


}//fin if defined access_token

function getData($chemin){
	
	$rep="";
	$nbRequest=0;
	$elements=array();
	
	if($chemin == "quota"){
		$url = 'https://apis.live.net/v5.0/me/skydrive/quota?access_token='.ACCESS_TOKEN;
		$keyVerif="quota";
	
	}else{
		$url = 'https://apis.live.net/v5.0/'.$chemin.'/files?access_token='.ACCESS_TOKEN;
		$keyVerif="data";
	}
	
	while(empty($rep) && $nbRequest < 3){
		$rep = file_get_contents($url);
		$nbRequest++;
		
		if(empty($rep)){
			sleep(1);
		}else{
			$elements = json_decode($rep,true);
			
			if(!isset($elements[$keyVerif])){
				$rep="";
				$elements=array();
				sleep(1);
			}
		}
	}

	if(empty($elements)){
		die();//Termine l'execution du script en cas de non réponse
	}else{
		return $elements;
	}
}

function proprietesRacine($idRacine){

	$rep="";
	$nbRequest=0;
	$racine=array();

	$url = 'https://apis.live.net/v5.0/'.$idRacine.'?access_token='.ACCESS_TOKEN;
	
	while(empty($rep) && $nbRequest < 3){
		$rep = file_get_contents($url);
		$nbRequest++;
		
		if(empty($rep)){
			sleep(1);
		}else{
			$racine = json_decode($rep,true);
			
			if(!isset($racine['name']) || !isset($racine['link'])){
				$rep="";
				$racine=array();
				sleep(1);
			}
		}
	}
	
	if(empty($racine)){
		die();//Termine l'execution du script en cas de non réponse
	}else{
		return($racine);
	}

}

function tabFichier($fich,$parent){


	$papa = array('name' => $parent['name'], 'lien' => $parent['link']);

	$dateCrea = date_create($fich['created_time']);
	date_timezone_set($dateCrea, timezone_open("Europe/Paris"));
	$dateUpd = date_create($fich['client_updated_time']);
	date_timezone_set($dateUpd, timezone_open("Europe/Paris"));
	$dateSysUpd = date_create($fich['updated_time']);
	date_timezone_set($dateSysUpd, timezone_open("Europe/Paris"));

	$mostRecent1 = ($dateSysUpd > $dateUpd) ? $dateSysUpd : $dateUpd;
	$mostRecent = date_timestamp_get(($dateCrea > $mostRecent1) ? $dateCrea : $mostRecent1);


	$fic = array('id' => $fich['id'],
				'name'=> $fich['name'],
				'from' => $fich['from']['name'],
				'parent' => $papa,
				'lien' => $fich['link'],
				'date' => $mostRecent,
				'boardOnly' => $fich['boardOnly'],
				);
	return $fic;
}


// Fonction de comparaison
function compareDate($a, $b) {
    return ($a['date'] <= $b['date']) ? 1 : -1;
}


function HTMLisation($tabModifs){
	$modifsHTML = array();
	$i=0;
		foreach($tabModifs as $modif){
			
			$modifsHTML[$i]= array();
			
			$modifsHTML[$i]['boardOnly']=$modif['boardOnly'];
			
			$modifsHTML[$i]['text']='<span><span class="italic">'.date('d/m', $modif['date']).'</span>&nbsp;&nbsp;';
		
			
			
		
				if($modif['modif']=="new"){
					
					if($affNoms != "Non"){
					
						$modifsHTML[$i]['text'].=$modif['from'].' a ajouté le fichier <a href="'.$modif['lien'].'" target="_blank">'.$modif['name'].'</a> '.
														'dans le dossier <a href="'.$modif['parent']['lien'].'" target="_blank">'.$modif['parent']['name'].'</a>.';
				
					}else{
						
						
						$modifsHTML[$i]['text'].='Le fichier <a href="'.$modif['lien'].'" target="_blank">'.$modif['name'].'</a> '.
														'a été ajouté dans le dossier <a href="'.$modif['parent']['lien'].'" target="_blank">'.$modif['parent']['name'].'</a>.';						
							
					}
				
				
				}elseif($modif['modif']=="renom"){
					$modifsHTML[$i]['text'].='Le fichier '.$modif['ex'].' du dossier <a href="'.$modif['parent']['lien'].'" target="_blank">'.$modif['parent']['name'].'</a> '.
											'a été renommé <a href="'.$modif['lien'].'" target="_blank">'.$modif['name'].'</a>.';
				
				
				}elseif($modif['modif']=="dep"){
					$modifsHTML[$i]['text'].='Le fichier <a href="'.$modif['lien'].'" target="_blank">'.$modif['name'].'</a> a été déplacé du dossier '.
											'<a href="'.$modif['ex']['lien'].'" target="_blank">'.$modif['ex']['name'].'</a> vers le dossier '.
											'<a href="'.$modif['parent']['lien'].'" target="_blank">'.$modif['parent']['name'].'</a>.';
				
				}elseif($modif['modif']=="modif"){
					$modifsHTML[$i]['text'].='Le fichier <a href="'.$modif['lien'].'" target="_blank">'.$modif['name'].'</a> du dossier '.
											'<a href="'.$modif['parent']['lien'].'" target="_blank">'.$modif['parent']['name'].'</a> a été modifié.';
				
				
				}elseif($modif['modif']=="suppr"){
					$modifsHTML[$i]['text'].='Le fichier  '.$modif['name'].' du dossier '.
											'<a href="'.$modif['parent']['lien'].'" target="_blank">'.$modif['parent']['name'].'</a> a été supprimé.';
				
				
				}
				
		
			$modifsHTML[$i]['text'].="</span><br />";
			$i++;
		
		}

	return $modifsHTML;
}

function fillFile($file, $tabModifs, $board){
	
	$fileModifs = fopen($file, 'r+');
	$exModifs = array();
	$i=1;
	$textModifs = "";
	$lastLine = "";

	
	while (!feof($fileModifs)){
		array_push($exModifs, fgets($fileModifs)); 
	}

	
	foreach($tabModifs as $NewLine){
	
		//AJout au fichier normal si pas de restriction
		if($board || !$NewLine['boardOnly']){
			if($NewLine != $lastLine){ //Permet d'éviter les doublons
				$textModifs .= $NewLine['text'];
				if($i==NB_NEWFILES){break;}else{$textModifs .= "\n";}
				$i++;
				$lastLine = $NewLine['text'];
			}		
		}
	}
	
	if($i<NB_NEWFILES){
		foreach($exModifs as $ExLine){
			if($ExLine != $lastLine){
				$textModifs .= $ExLine;
				if($i==NB_NEWFILES){break;}
				$i++;
				$lastLine = $ExLine;
			}
		}
	}
	
	fseek($fileModifs, 0);
	ftruncate($fileModifs, 0);
	fputs($fileModifs, $textModifs);
	fclose($fileModifs);
}


?>