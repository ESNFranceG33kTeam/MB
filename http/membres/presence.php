<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

define('TITRE_PAGE',"Feuilles de présence");



if(isset($_POST['idAdd'])){


	if(empty($_POST['nomAdd'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom</em>.'));
	}
	if(mb_strlen($_POST['nomAdd'])>150){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom</em> ne doit pas dépasser 150 caractères.'));
	}

	if(!is_numeric($_POST['idAdd'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'ID du groupe invalide.'));
	}
	
	
	if(empty($pageMessages)){
		
		
		$bd = db_connect();
		
		$_POST['nomAdd'] = mysqli_real_escape_string($bd, $_POST['nomAdd']);
		$_POST['idAdd'] = mysqli_real_escape_string($bd, $_POST['idAdd']);
		
		
		//Recup droits
		$valeurs = db_ligne ($bd, "SELECT * FROM membres_presence_feuilles WHERE id='".$_POST['idAdd']."'");
		
		
		if(checkDroits($valeurs['visibility']) && ($valeurs['droits']=="self" || $valeurs['droits']=="all" || $valeurs['droits']==ID || checkDroits("bureau"))){
			
			$addPresence = db_exec($bd, "
						INSERT INTO membres_presence_feuilles(idGroupe, nom, droits, visibility, affiche, choixRep)
						VALUES('".$_POST['idAdd']."','".$_POST['nomAdd']."','".$valeurs['droits']."','".$valeurs['visibility']."','".$valeurs['affiche']."','".$valeurs['choixRep']."')");
			
			$idPres = db_lastId($bd);
			db_close($bd);
				
			if($addPresence !== false){
				array_push($pageMessages, array('type'=>'ok', 'content'=>"La feuille a bien été ajoutée."));
				header('Location: http://'.$_SERVER['HTTP_HOST'].'/presence-'.$idPres);
			}	

		}else{
			array_push($pageMessages, array('type'=>'err', 'content'=>"Vous n'avez pas les droits nécéssaires."));
		}	
	}
}


if(isset($_POST['idEdit'])){


	if(empty($_POST['nomEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom</em>.'));
	}
	if(mb_strlen($_POST['nomEdit'])>150){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom</em> ne doit pas dépasser 150 caractères.'));
	}

	if(!is_numeric($_POST['idEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'ID du groupe invalide.'));
	}
	
	
	if(empty($pageMessages)){
		
		
		$bd = db_connect();
		
		$_POST['nomEdit'] = mysqli_real_escape_string($bd, $_POST['nomEdit']);
		$_POST['idEdit'] = mysqli_real_escape_string($bd, $_POST['idEdit']);
		
		
		//Recup droits
		$valeurs = db_ligne ($bd, "SELECT * FROM membres_presence_feuilles WHERE id='".$_POST['idEdit']."'");
		
		
		if(checkDroits($valeurs['visibility']) && ($valeurs['droits']==ID || checkDroits("bureau"))){
			
			$edit = db_exec($bd, "UPDATE membres_presence_feuilles SET nom = '".$_POST['nomEdit']."' 
									WHERE id = '".$_POST['idEdit']."'");

			db_close($bd);
				
			if($edit !== false){
				array_push($pageMessages, array('type'=>'ok', 'content'=>"Le nom de la feuille a bien été modifié."));
			}	

		}else{
			array_push($pageMessages, array('type'=>'err', 'content'=>"Vous n'avez pas les droits nécéssaires."));
		}	
	}
}


//Suppr feuille
if(isset($_POST['idSup'])){

	//Verif droits
	requireDroits("bureau");
		
	$bd = db_connect();
		
	$_POST['idSup'] = mysqli_real_escape_string($bd, $_POST['idSup']);	
	
	// Verif Groupe ou feuille
		
	$valeurs = db_ligne ($bd, "SELECT idGroupe, visibility, droits FROM membres_presence_feuilles WHERE id='".$_POST['idSup']."'");
	
	if(checkDroits($valeurs['visibility']) && ($valeurs['droits']==ID || checkDroits("bureau"))){
	
	
		if($valeurs['idGroupe'] == -1){
				
			$sup1 = db_exec($bd, "
					DELETE FROM membres_presence_feuilles
					WHERE id='".$_POST['idSup']."' OR idGroupe='".$_POST['idSup']."'");
					
			$rep = "Le groupe de feuilles a bien été supprimé.";

			
		}else{
			
			$sup1 = db_exec($bd, "
					DELETE FROM membres_presence_feuilles
					WHERE id='".$_POST['idSup']."'
					LIMIT 1");
			
			$rep = "La feuille a bien été supprimée.";
		}
		
		$sup2 = db_exec($bd, "
			DELETE FROM membres_presence_inscrits
			WHERE idFeuille='".$_POST['idSup']."'");
			
			
		
		db_close($bd);
		
		if($sup1!==false && $sup2!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=> $rep));
		}

	}else{
		array_push($pageMessages, array('type'=>'err', 'content'=>"Vous n'avez pas les droits nécéssaires."));
	}
}//fin suppr feuille




//MAJ des présences
if(isset($_POST['editPresence'])){
	
	
	$feuilles = explode('@@',$_POST['editPresence'],-1);
	
	$bd = db_connect();

	for($feuil=0; $feuil<count($feuilles); $feuil++){
		
		
		$dataFeuille = explode('#',$feuilles[$feuil],2);
		
		$idFeuil = $dataFeuille[0];
		
		
		if(isset($dataFeuille[1])){
			$dataLignes = explode('//', $dataFeuille[1],-1);
			
		}else{
			$dataLignes = "";
		}
		
		
		$idFeuil = mysqli_real_escape_string($bd, $idFeuil);
		
		//Recup infos feuille
		$valeurs = db_ligne ($bd, "SELECT * FROM membres_presence_feuilles WHERE id='".$idFeuil."'");

		
		for($li=0; $li<count($dataLignes); $li++){
		
		
			$dataLi = explode('-',$dataLignes[$li],2);
			
			$idMembre = $dataLi[0];
		
			if(isset($dataLi[1])){
				$reponse = $dataLi[1];
				
			}else{
				$dataLi[1] = "";
			}
			
			if($reponse != "O" && $reponse != "N" && $reponse != "P" && $reponse != "R" && $reponse != "SUPPR"){
				db_close($bd);
				die();
			} 
			
			if(checkDroits($valeurs['visibility']) && (($idMembre == ID && $valeurs['droits']=="self") || $valeurs['droits']=="all" || $valeurs['droits']==ID || ($valeurs['droits']=="bureau" && checkDroits("bureau")))){

				$idMembre = mysqli_real_escape_string($bd, $idMembre);
				$reponse = mysqli_real_escape_string($bd, $reponse);
			
				if($reponse == "SUPPR"){
					$edit = db_exec($bd, "
						DELETE FROM membres_presence_inscrits
						WHERE idFeuille='".$idFeuil."' AND idMembre='".$idMembre."'
						LIMIT 1");
					
				}else{
					$edit = db_exec($bd, "
						INSERT INTO membres_presence_inscrits(idFeuille, idMembre, reponse)
						VALUES('".$idFeuil."','".$idMembre."','".$reponse."')
						ON DUPLICATE KEY UPDATE reponse='".$reponse."'");

				}
				
				if($edit===false){
					db_close($bd);
					die($edit);
				}
			
			}
		}
	}
	array_push($pageMessages, array('type'=>'ok', 'content'=>"Les modifications ont bien été enregistrées."));
}
	
	


//Récupération Liste feuilles
$bd = db_connect();
$feuilles = db_tableau ($bd, "SELECT * FROM membres_presence_feuilles ORDER BY idGroupe DESC, id DESC");
db_close($bd);
	if($feuilles === false){
		die();
	}



//Mise en forme liste + tableaux feuilles

$lstGrFeuilles = "";
$lstFeuilles = "";
$tabFeuilles = "";
$initAffFeuille = "";


$tempIdGroupe = -1;
$arrayStats = array();
$typeFeuille = "";


	for($i=0; $i<count($feuilles); $i++){
		
		if(checkDroits($feuilles[$i]['visibility'])){
			
			
			//LISTE
			if($feuilles[$i]['visibility']=="probatoire"){
				$visible = "Tous les membres";
			}elseif($feuilles[$i]['visibility']=="membre"){
				$visible = "Membres actifs";
			}elseif($feuilles[$i]['visibility']=="bureau"){
				$visible = "Membres du bureau";
			}
			
			if($feuilles[$i]['droits']=="self"){
				$edit = "Soi-même seulement";
			}elseif($feuilles[$i]['droits']=="bureau"){
				$edit = "Membres du bureau";
			}elseif($feuilles[$i]['droits']=="all"){
				$edit = "Tous les membres";
			}else{
				
				$bd = db_connect();
				$membre = db_ligne($bd, "
										SELECT prenom, nom
										FROM membres_benevoles
										WHERE id = '".$feuilles[$i]['droits']."'");
				db_close($bd);
				
				if($membre===false || empty($membre)){
					$edit = "<i>Membre supprimé</i>";
				}else{
					$edit = $membre['prenom'] . " " . $membre['nom'];
				}

			}
			
			if($feuilles[$i]['idGroupe'] == -1){
				
				
				if(isset($_GET['idFeuille'])){
					if($_GET['idFeuille'] == $feuilles[$i]['id']){
						$initAffFeuille = 'affFeuillesGr('.$feuilles[$i]['id'].',true);';
						
					}
				}
				

				
				$lstGrFeuilles .= '<tr><td id="tdLst-'.$feuilles[$i]['id'].'"><div style="float:left; margin-top:2px; overflow:hidden; width:300px; font-weight:bold;"><a onclick="affFeuillesGr('.$feuilles[$i]['id'].')">'.$feuilles[$i]['nom'].'</a></div>';
									

				if($feuilles[$i]['droits']=="self" || $feuilles[$i]['droits']=="all" || $feuilles[$i]['droits']==ID || checkDroits("bureau")){
					$lstGrFeuilles .= '<div style="float:right; width:90px; font-size:12px; line-height:15px; text-align:right; font-weight:bold"><a onclick="affAddFeuille('.$feuilles[$i]['id'].')"><img src="/../template/images/add.png" style="vertical-align:middle;height:12px; margin-right:3px">Ajouter une feuille au groupe</a></div>';
				}			
									
				$lstGrFeuilles .= 	'</td>'.
							'<td>'.$visible.'</td><td>'.$edit.'</td>'.
							((checkDroits("bureau") || $feuilles[$i]['droits']==ID)?'
							<td class="edit" id="cellEditFeuille'.$feuilles[$i]['id'].'" onclick="editFeuille('.$feuilles[$i]['id'].')"></td>
							<td class="suppr" id="cellRemoveFeuille'.$feuilles[$i]['id'].'" onclick="supprGroupe('.$feuilles[$i]['id'].')"></td>':'').'</tr>';
		
		
				if($feuilles[$i]['droits']=="self" || $feuilles[$i]['droits']=="all" || $feuilles[$i]['droits']==ID || checkDroits("bureau")){
					$lstGrFeuilles .= 	'<tr id="trNewFeuille-'.$feuilles[$i]['id'].'" style="display:none"><td colspan=3 style="padding-left:25px"><span>
										Nom de la nouvelle feuille : <input type="text" id="inputNewFeuille-'.$feuilles[$i]['id'].'" style="margin-bottom:0" maxlength=150/>
										<input type="button" onclick="submitNewFeuille('.$feuilles[$i]['id'].')" id="submitNewPresence-'.$feuilles[$i]['id'].'" value="valider" style="display:inherit;margin-top:2px;"/>
										</span>
										</td></tr><tbody>';
				}
		
				$trouve = false;
				
				for($g=0; $g<count($feuilles); $g++){ //Recherche feuilles correspondantes au groupe
				
				
					if(isset($_GET['idFeuille'])){
						if($_GET['idFeuille'] == $feuilles[$g]['id'] && empty($initAffFeuille)){
							$initAffFeuille = 'changeFeuille('.$feuilles[$g]['id'].','.$feuilles[$g]['idGroupe'].');';
						}
					}

				
					if($feuilles[$g]['idGroupe'] == $feuilles[$i]['id']){
						$trouve = true;
						
						$lstGrFeuilles .= '<tr id="trLstGr-'.$feuilles[$i]['id'].'-'.$feuilles[$g]['id'].'"  style="line-height:20px; font-size:15px; display:none"><td colspan=3 id="tdLst-'.$feuilles[$g]['id'].'" ><div style="padding-left:25px; overflow:hidden; width:730px; font-weight:bold"><a onclick="changeFeuille('.$feuilles[$g]['id'].','.$feuilles[$g]['idGroupe'].')">'.$feuilles[$g]['nom'].'</a></div></td>'.
							((checkDroits("bureau") || $feuilles[$i]['droits']==ID)?'
							<td class="edit" id="cellEditNomFeuille'.$feuilles[$g]['id'].'" onclick="editNomFeuille('.$feuilles[$g]['id'].')"></td>
							<td class="suppr" id="cellRemoveFeuille'.$feuilles[$g]['id'].'" onclick="supprFeuille('.$feuilles[$g]['id'].')"></td>':'').'</tr>';
					}
					
					if($trouve && $feuilles[$g]['idGroupe'] != $feuilles[$i]['id']){
						break;
					}
				
				}
				$lstGrFeuilles .= '</tbody>';
				
			}elseif(empty($feuilles[$i]['idGroupe'])){
				
				
				if(isset($_GET['idFeuille'])){
					if($_GET['idFeuille'] == $feuilles[$i]['id']){
						$initAffFeuille = 'changeFeuille('.$feuilles[$i]['id'].',\'\');';
					}
				}
				
				
				$lstFeuilles .= '<tr><td id="tdLst-'.$feuilles[$i]['id'].'"><div style="overflow:hidden; width:395px; font-weight:bold"><a onclick="changeFeuille('.$feuilles[$i]['id'].',\'\')">'.$feuilles[$i]['nom'].'</a></div>'.
								'</td>'.
								'<td>'.$visible.'</td><td>'.$edit.'</td>'.
								((checkDroits("bureau") || $feuilles[$i]['droits']==ID)?'
								<td class="edit" id="cellEditFeuille'.$feuilles[$i]['id'].'" onclick="editFeuille('.$feuilles[$i]['id'].')"></td>
								<td class="suppr" id="cellRemoveFeuille'.$feuilles[$i]['id'].'" onclick="supprFeuille('.$feuilles[$i]['id'].')"></td>':'').'</tr>';
			}
			
			
			//TABLEAU
			
			//Condition affichage membres
			
			if($feuilles[$i]['idGroupe'] != -1){

				//Stats
				
				if($feuilles[$i]['idGroupe'] != $tempIdGroupe && !(empty($arrayStats))){
				
					$nomRep = array('O' => 'Oui', 'N' => 'Non', 'P' => 'Peut-être', 'R' => 'En retard');
				
				
					//Création feuille stats
					
					$tabFeuilles .= '<div id="feuille-'.$tempIdGroupe.'" class="anim" style="display:none;opacity:0">
						<div class="blocText">
							Filtrer par prénom ou nom : <input type="text" id="filtre-'.$tempIdGroupe.'" style="margin-top:2px; margin-bottom:2px; width:280px" onkeyup="filtering('.$tempIdGroupe.')" value="" autocomplete="off"/>
							
							<a id ="aPrint" onclick="print(\''.$tempIdGroupe.'-stats\')" target="_blank" style="float:right; margin-top:7px">
								<img src="../template/images/printer.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Version imprimable de la page
							</a>
						
						
						</div>
						<table style="table-layout:fixed"><thead><th style="width:50%">Nom</th>';
						
					for($char=0; $char < iconv_strlen($typeFeuille) ; $char++){
						
						$lettre = substr($typeFeuille, $char, 1 );
						$tabFeuilles .= '<th style="width:'.(50/iconv_strlen($typeFeuille)).'%">'.$nomRep[$lettre].'</th>';
					}
					
					$tabFeuilles .= '</thead><tbody id="tbody-'.$tempIdGroupe.'">';
					
					usort($arrayStats,'triStats');
					
					for($m=0; $m<count($arrayStats); $m++){
						
					$tabFeuilles .= '<tr><td>'.$arrayStats[$m]['nom'].'</td>';
						
						for($char=0; $char < iconv_strlen($typeFeuille) ; $char++){
						
							$lettre = substr($typeFeuille, $char, 1);
							$tabFeuilles .= '<td class="center">'.((isset($arrayStats[$m][$lettre]))?$arrayStats[$m][$lettre]:0).'</td>';
						}
						
						$tabFeuilles .= '</tr>';
						
					}
					
	
					$tabFeuilles .='</tbody></table></div>';				
					$arrayStats = array();
				}
				
				if($feuilles[$i]['idGroupe'] > 0){
					
					$tempIdGroupe = $feuilles[$i]['idGroupe'];
					$typeFeuille = $feuilles[$i]['choixRep'];
				}
			
			
			
				$bits = $feuilles[$i]['affiche'];
				$types = array();

				if($bits & 1) $types[] = 'probatoire';
				if($bits & 2) $types[] = 'membre';
				if($bits & 4) $types[] = 'bureau';

				$types = array_map(function($t) { return 'drt.general="'.$t.'"'; }, $types);

				$where = '(' . implode($types, ' OR ') . ')';
				
			
				//Recup membres		
				$bd = db_connect();
				$membres = db_tableau($bd, "
										SELECT ben.id, ben.prenom, ben.nom, drt.general , inscr.reponse
										FROM membres_benevoles AS ben
										LEFT JOIN membres_droits AS drt ON ben.id = drt.id
										LEFT JOIN membres_presence_inscrits AS inscr ON (inscr.idMembre = ben.id AND inscr.idFeuille = '".$feuilles[$i]['id']."')
										WHERE ".$where."
										ORDER BY ben.prenom ASC, ben.nom ASC");
										
				db_close($bd);
				
				if($membres === false || empty($membres)){
					die();
				}


				$tabFeuilles .= '<div id="feuille-'.$feuilles[$i]['id'].'" class="anim" style="display:none;opacity:0">
									<div class="blocText">
										Filtrer par prénom ou nom : <input type="text" id="filtre-'.$feuilles[$i]['id'].'" style="margin-top:2px; margin-bottom:2px; width:280px" onkeyup="filtering('.$feuilles[$i]['id'].')" value="" autocomplete="off"/>
										<a id ="aPrint-'.$feuilles[$i]['id'].'" onclick="affPrint('.$feuilles[$i]['id'].')" style="float:right; margin-top:7px">
											<img src="../template/images/printer.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Version imprimable de la page
										</a>
									</div>
									<div class="blocText" id="personnesPrint-'.$feuilles[$i]['id'].'" style="display:none"> Faire apparaître les personnes : ';
										
				for($char=0; $char < iconv_strlen($feuilles[$i]['choixRep']) ; $char++){
					
					$lettre = substr($feuilles[$i]['choixRep'], $char, 1 );
					
					if($lettre == 'O'){
						$tabFeuilles .= '<input type="checkbox" id="print-'.$feuilles[$i]['id'].'-O" name="printO" checked>
										<label class="checkbox" for="print-'.$feuilles[$i]['id'].'-O" style="margin-bottom:2px">Présentes</label>';
					}elseif($lettre == 'N'){
						$tabFeuilles .= '<input type="checkbox" id="print-'.$feuilles[$i]['id'].'-N" name="printN" checked>
							<label class="checkbox" for="print-'.$feuilles[$i]['id'].'-N" style="margin-bottom:2px">Non présentes</label>';
					}elseif($lettre == 'P'){
						$tabFeuilles .= '<input type="checkbox" id="print-'.$feuilles[$i]['id'].'-P" name="printP" checked>
							<label class="checkbox" for="print-'.$feuilles[$i]['id'].'-P" style="margin-bottom:2px">Peut-être présentes</label>';
						
					}elseif($lettre == 'R'){
						$tabFeuilles .= '<input type="checkbox" id="print-'.$feuilles[$i]['id'].'-R" name="printR"  checked>
							<label class="checkbox" for="print-'.$feuilles[$i]['id'].'-R" style="margin-bottom:2px">En retard</label>';
					}
				}
									
									
				$tabFeuilles .= '<input type="checkbox" id="print-'.$feuilles[$i]['id'].'-U" name="printU"  checked>
							<label class="checkbox" for="print-'.$feuilles[$i]['id'].'-U" style="margin-bottom:2px">Indéfini</label></div>
								<table style="table-layout:fixed"><thead><th style="width:50%">Nom</th><th colspan='.iconv_strlen($feuilles[$i]['choixRep']).' style="width:50%">Présent</th></thead>
								<tbody id="tbody-'.$feuilles[$i]['id'].'">';
				
				
				$firstLi = "";
				$otherLi = "";
				
				
				
				for($m=0; $m<count($membres); $m++){
					
					//Init Stats de groupe
		
					if($feuilles[$i]['idGroupe'] > 0){
					
						if(count($arrayStats) < count($membres)){
							$arrayStats[$m]['nom'] = $membres[$m]['prenom'].' '.$membres[$m]['nom'];
						}
						
						if(isset($arrayStats[$m][$membres[$m]['reponse']])){
							
							$arrayStats[$m][$membres[$m]['reponse']] ++;
							
						}else{
							
							$arrayStats[$m][$membres[$m]['reponse']] = 1;
							
						}
					}
					
					
					
					$tdsChoix = "";
					$classTDnom = "";
					
					
					for($char=0; $char < iconv_strlen($feuilles[$i]['choixRep']) ; $char++){
						
						$class = "";
						$lettre = substr($feuilles[$i]['choixRep'], $char, 1 );
						
						
						if($lettre == 'O'){
							
							if($membres[$m]['reponse'] == $lettre){
								$class = "green";
								$classTDnom = "green";								
							}
							$txtTD = "Oui";
							
						}elseif($lettre == 'N'){
							
							if($membres[$m]['reponse'] == $lettre){
								$class = "red";
								$classTDnom = "red";
							}
							$txtTD = "Non";
		
							
						}elseif($lettre == 'P'){
							
							if($membres[$m]['reponse'] == $lettre){
								$class = "orange";
								$classTDnom = "orange";
							}
							$txtTD = "Peut-être";
							
						}elseif($lettre == 'R'){
							if($membres[$m]['reponse'] == $lettre){
								$class = "orange";
								$classTDnom = "orange";
							}
							$txtTD = "En retard";
						}
						
						
						//Verif droits modif
						
						
						if(($membres[$m]['id'] == ID && $feuilles[$i]['droits']=="self") || $feuilles[$i]['droits']=="all" || $feuilles[$i]['droits']==ID || ($feuilles[$i]['droits']=="bureau" && checkDroits("bureau"))){
							
							$modif = 'onclick="changePresence('.$feuilles[$i]['id'].','.$membres[$m]['id'].',\''.$lettre.'\')" style="cursor:pointer; text-align:center"';
							$tdsChoix .= '<td id="td-'.$feuilles[$i]['id'].'-'.$membres[$m]['id'].'-'.$lettre.'" class="'.$class.'" '.$modif.'>'.$txtTD.'</td>';
							
						}elseif($membres[$m]['reponse'] == $lettre){
							$tdsChoix .= '<td colspan='.iconv_strlen($feuilles[$i]['choixRep']).' id="td-'.$feuilles[$i]['id'].'-'.$membres[$m]['id'].'-'.$lettre.'" class="'.$class.'" style="text-align:center">'.$txtTD.'</td>';
						
						}elseif($membres[$m]['reponse']==""){
							$tdsChoix .= '<td colspan='.iconv_strlen($feuilles[$i]['choixRep']).' style="text-align:center">Indéfini</td>';
							break;
						}
					}
					
					
					if($membres[$m]['id'] == ID){
						
						$firstLi .= '<tr><td class="'.$classTDnom.' gras">'.$membres[$m]['prenom'].' '.$membres[$m]['nom'].'</td>'.$tdsChoix.'</tr>';
						
					}else{

						$otherLi .= '<tr><td class="'.$classTDnom.'">'.$membres[$m]['prenom'].' '.$membres[$m]['nom'].'</td>'.$tdsChoix.'</tr>';
					}
				}
			
				$tabFeuilles .= $firstLi . $otherLi . '</tbody></table><input type="hidden" id="inputPre-'.$feuilles[$i]['id'].'" value="" /></div>';
				
				
			}
		}
	}	

//Création de la derniere page de stats

if(!(empty($arrayStats))){

	$nomRep = array('O' => 'Oui', 'N' => 'Non', 'P' => 'Peut-être', 'R' => 'En retard');


	//Création feuille stats
	
	$tabFeuilles .= '<div id="feuille-'.$tempIdGroupe.'" class="anim" style="display:none;opacity:0">
		<div class="blocText">
			Filtrer par prénom ou nom : <input type="text" id="filtre-'.$tempIdGroupe.'" style="margin-top:2px; margin-bottom:2px; width:280px" onkeyup="filtering('.$tempIdGroupe.')" value="" autocomplete="off"/>
			
			<a id ="aPrint" onclick="print(\''.$tempIdGroupe.'-stats\')" target="_blank" style="float:right; margin-top:7px">
				<img src="../template/images/printer.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Version imprimable de la page
			</a>
		
		
		</div>
		<table style="table-layout:fixed"><thead><th style="width:50%">Nom</th>';
		
	for($char=0; $char < iconv_strlen($typeFeuille) ; $char++){
		
		$lettre = substr($typeFeuille, $char, 1 );
		$tabFeuilles .= '<th style="width:'.(50/iconv_strlen($typeFeuille)).'%">'.$nomRep[$lettre].'</th>';
	}
	
	$tabFeuilles .= '</thead><tbody id="tbody-'.$tempIdGroupe.'">';
	
	usort($arrayStats,'triStats');
	
	for($m=0; $m<count($arrayStats); $m++){
		
	$tabFeuilles .= '<tr><td>'.$arrayStats[$m]['nom'].'</td>';
		
		for($char=0; $char < iconv_strlen($typeFeuille) ; $char++){
		
			$lettre = substr($typeFeuille, $char, 1);
			$tabFeuilles .= '<td class="center">'.((isset($arrayStats[$m][$lettre]))?$arrayStats[$m][$lettre]:0).'</td>';
		}
		
		$tabFeuilles .= '</tr>';
		
	}
	
	$tabFeuilles .='</tbody></table></div>';				
	$arrayStats = array();
}



include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<h3>Liste des feuilles</h3>
<?php
if(!empty($lstGrFeuilles) || !empty($lstFeuilles)){

	if(!empty($lstGrFeuilles)){
		echo '<table>';
		echo '<tr><th>Groupe de feuilles</th><th style="width:150px">Visible par</th><th style="width:150px">Modifications par</th></tr>';
		echo $lstGrFeuilles;
		echo '</table>';
	}
	if(!empty($lstFeuilles)){
		echo '<table>';
		echo '<tr><th>Feuille</th><th style="width:150px">Visible par</th><th style="width:150px">Modifications par</th></tr>';
		echo $lstFeuilles;
		echo '</table>';
	}
	
}else{
	echo "<br/>Aucune feuille de présence créée.<br/>";
}
	echo '<table><tbody><tr><td colspan=3><a href="http://'.$_SERVER['HTTP_HOST'].'/membres/addPresence.php"><b>Créer un groupe ou une feuille de présence</b></a></td></tr></tbody></table>';
?>
<span id="tab"></span>
<form  method=post action="/membres/presence.php" id="formAddFeuille" style="display:none">
<input type="hidden" id="idAdd" name="idAdd" value=""/>
<input type="hidden" id="nomAdd" name="nomAdd" value=""/>
</form>

<form  method=post action="/membres/presence.php" id="formRemoveFeuille" style="display:none">
<input type="hidden" id="idSup" name="idSup" value=""/>
</form>

<form  method=post action="/membres/presence.php" id="formEditNomFeuille" style="display:none">
<input type="hidden" id="idEdit" name="idEdit" value=""/>
<input type="hidden" id="nomEdit" name="nomEdit" value=""/>
</form>

<form  method=post action="/membres/editPresence.php" id="formEditFeuille" style="display:none">
<input type="hidden" id="idE" name="idEdit" value=""/>
</form>

<?php
if(!empty($tabFeuilles)){
?>


	<h3 id="h3Feuille" style="display:none">Feuille de présence</h3>
	<div id="divPage" class="blocText" style="display:none">
	<a id="aPagePre" style="margin-right:30px">Afficher la feuille précédente</a>
	<span id="spanNoPre" style="margin-right:30px; color:#aaa">Afficher la feuille précédente</span>
	<span id="spanNumPages" >Feuille 1/4</span>
	<span id="spanNoSuiv" style="margin-left:30px; color:#aaa">Afficher la feuille suivante</span>
	<a id="aPageSuiv" style="margin-left:30px">Afficher la feuille suivante</a>
	
	
	<a id ="aStats" onclick="changeFeuille(idGroupe, idGroupe)" style="float:right">
		<img src="../template/images/table.png" style="vertical-align:sub; height:16px; margin-right:8px"/>Afficher les statistiques du groupe
	</a>
	</div>

	<div id="Feuilles" style="clear:both">
	

<?php
	echo $tabFeuilles;
	echo '</div>';
	echo '<form id="formEditPresence" method=post action="presence.php" id="formEditPresence">
			<br/>
			<input type="button" onclick="submEditPresence()" id="submitPresence" value="valider les modifications" style="display:none"/>
			<input type="hidden" id="editPresence" name="editPresence" value="" />
			</form>';
}
?>



<script type="text/javascript">


<?php echo $initAffFeuille; ?>

function affFeuillesGr(id, affStats){
	affStats = affStats || false;
	
	if(document.getElementById("trNewFeuille-"+id) != null){
		document.getElementById("trNewFeuille-"+id).style.display = "none";
	}
	
	tagsTR = document.getElementsByTagName("TR");
	
	idFirstFeuille = "";
	
	var pasFeuille = true;

	
	for(i=0; i<tagsTR.length; i++){
	
		if(tagsTR[i].id.indexOf("trLstGr-"+id) != -1){
			var pasFeuille = false;
			if(tagsTR[i].style.display == "none"){
				tagsTR[i].style.display = "";
				
				if(idFirstFeuille == ""){
					idFirstFeuille = tagsTR[i].id.replace("trLstGr-"+id+"-","");
				}

			}else{
				affStats = true;
			}
			
		}else if(tagsTR[i].id.indexOf("trLstGr-") != -1){
			tagsTR[i].style.display= "none";
		}
	}
	document.getElementById("tdLst-"+id).className = "";
	
	if(affStats){

		changeFeuille(id, id);

	}else if(idFirstFeuille != ""){

		changeFeuille(idFirstFeuille, id);

	}
	
	
	if(pasFeuille){
		alert("Ce groupe ne contient encore aucune feuille.");
	}
	
}


function affAddFeuille(id){
	
	if(document.getElementById("trNewFeuille-"+id).style.display == "none"){
		tagsTR = document.getElementsByTagName("TR");

		for(i=0; i<tagsTR.length; i++){
		
			if(tagsTR[i].id.indexOf("trLstGr-"+id) != -1){
				tagsTR[i].style.display= "none";
			}
		}
		
		document.getElementById("trNewFeuille-"+id).style.display = "";
		document.getElementById("inputNewFeuille-"+id).focus();

	}else{
		document.getElementById("trNewFeuille-"+id).style.display = "none";
	}
}



function submitNewFeuille(id){
	
	if(document.getElementById('inputNewFeuille-'+id).value == ""){
		
		alert("Veuillez remplir le nom de la feuille.");
	
	}else{

		document.getElementById("formAddFeuille").action = "http://<?php echo $_SERVER['HTTP_HOST'] ?>/presence-"+id;
	
		document.getElementById('idAdd').value = id;
		document.getElementById('nomAdd').value = document.getElementById('inputNewFeuille-'+id).value;
	
		document.getElementById('submitNewPresence-'+id).disabled=true;
		document.getElementById('submitNewPresence-'+id).value = "Patientez...";
		document.getElementById('submitNewPresence-'+id).onclick="";
		document.getElementById('formAddFeuille').submit();
	
	}
}

function editNomFeuille(id){
	
	nom = document.getElementById('tdLst-'+id).childNodes[0].childNodes[0].innerHTML;
	document.getElementById('tdLst-'+id).innerHTML = 'Nouveau nom : <input type="text" id="inputEditNom-'+id+'" style="margin-bottom:0" value="'+nom+'" autocomplete="off" maxtength:150/>';
	document.getElementById('cellEditNomFeuille'+id).onclick = function(){submitEditNomFeuille(id);};
	document.getElementById('cellEditNomFeuille'+id).className = "tick";
	document.getElementById('inputEditNom-'+id).focus();
}

function submitEditNomFeuille(id){

	if(document.getElementById('inputEditNom-'+id).value != ""){
		
		document.getElementById("formEditNomFeuille").action = "http://<?php echo $_SERVER['HTTP_HOST'] ?>/presence-"+id;
		document.getElementById('cellEditNomFeuille'+id).onclick="";
		document.getElementById('idEdit').value = id;
		document.getElementById('nomEdit').value = document.getElementById('inputEditNom-'+id).value;
		document.getElementById('formEditNomFeuille').submit();
	}else{
		alert("Veuillez remplir le nom de la feuille.");
	}
}

function editFeuille(id){

	document.getElementById('cellEditFeuille'+id).onclick="";
	document.getElementById('idE').value = id;
	document.getElementById('formEditFeuille').submit();

}



function supprFeuille(id){
	if(confirm("Voulez-vous vraiment supprimer cette feuille ?")){
		document.getElementById('cellRemoveFeuille'+id).onclick="";
		document.getElementById('idSup').value = id;
		document.getElementById('formRemoveFeuille').submit();
	}
}



function supprGroupe(id){
	if(confirm("Voulez-vous vraiment supprimer ce groupe et toutes les feuilles qu'il contient ?")){
		document.getElementById('cellRemoveFeuille'+id).onclick="";
		document.getElementById('idSup').value = id;
		document.getElementById('formRemoveFeuille').submit();
	}
}

function changeFeuille(id, idGroupe){
	
	//Recup planning affiché
	
	idAffich= -1;
	
	tagsDivs = document.getElementsByTagName("DIV");

	for(i=0; i<tagsDivs.length; i++){
	
		if(tagsDivs[i].id.indexOf("feuille-") != -1){
			
			if(tagsDivs[i].style.display == ""){
				idAffich= tagsDivs[i].id.replace("feuille-","");
				break;
			}
		}
	}
	

	
	if(idGroupe != ""){
	
		//groupe

		//Recup infos groupe
		
		tagsTR = document.getElementsByTagName("TR");

		var countTRGr = 0;
		var iPage = 0;
		
		var idPagePre = 0;
		var idPageSuiv = 0;
		var idFirstPage = 0;
		
		for(i=0; i<tagsTR.length; i++){
	
			if(tagsTR[i].id.indexOf("trLstGr-"+idGroupe) != -1){
				countTRGr ++;
				tagsTR[i].style.display="";
				
				if(idFirstPage == 0){
					idFirstPage = tagsTR[i].childNodes[0].id.replace("tdLst-","");
				}
				
				if(idPageSuiv == 0 && iPage != 0){
					idPageSuiv = tagsTR[i].childNodes[0].id.replace("tdLst-","");
				}

				if(tagsTR[i].id == "trLstGr-"+idGroupe+"-"+id){
					iPage = countTRGr;
				}
				
				if(iPage == 0){
					idPagePre = tagsTR[i].childNodes[0].id.replace("tdLst-","");
				}
			}
		}

		if(id != idGroupe){
		
			document.getElementById("spanNumPages").innerHTML = "Feuille " + iPage + "/" + countTRGr;
			document.getElementById("aStats").innerHTML = '	<a id ="aStats" onclick="changeFeuille('+idGroupe+', '+idGroupe+')" style="float:right"><img src="../template/images/table.png" style="vertical-align:sub; height:16px; margin-right:8px"/>Afficher les statistiques du groupe</a>';

			
			if(iPage == 1){
				document.getElementById("aPagePre").style.display = "none";
				document.getElementById("spanNoPre").style.display = "";
			}else{
				document.getElementById("aPagePre").style.display = "";
				document.getElementById("spanNoPre").style.display = "none";
				
				document.getElementById("aPagePre").onclick = function(){changeFeuille(idPagePre,idGroupe);};
			}
			if(iPage == countTRGr){
				document.getElementById("aPageSuiv").style.display = "none";
				document.getElementById("spanNoSuiv").style.display = "";
			}else{
				document.getElementById("aPageSuiv").style.display = "";
				document.getElementById("spanNoSuiv").style.display = "none";
				
				document.getElementById("aPageSuiv").onclick = function(){changeFeuille(idPageSuiv,idGroupe);};
			}
		
		}else{
			document.getElementById("aPagePre").style.display = "none";
			document.getElementById("spanNoPre").style.display = "none";
			document.getElementById("aPageSuiv").style.display = "none";
			document.getElementById("spanNoSuiv").style.display = "none";
			
			document.getElementById("spanNumPages").innerHTML = "Statistiques du groupe";
			document.getElementById("aStats").innerHTML = '	<a id ="aStats" onclick="changeFeuille('+idFirstPage+', '+idGroupe+')" style="float:right"><img src="../template/images/list.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Afficher la première feuille du groupe</a>';

		}

		document.getElementById("divPage").style.display = "";
		
	}else{
		
		//Cache groupe developpé
		
		tagsTR = document.getElementsByTagName("TR");
	
		for(i=0; i<tagsTR.length; i++){
	
			if(tagsTR[i].id.indexOf("trLstGr-") != -1){
				tagsTR[i].style.display = "none";
				tagsTR[i].childNodes[0].className = "";
			}
		}
		
		tagsTDList = document.getElementsByTagName("TD");
	
		for(i=0; i<tagsTDList.length; i++){
			if(tagsTDList[i].id.indexOf("tdLst-") != -1){
				tagsTDList[i].className = "";
			}
		}

		document.getElementById("divPage").style.display = "none";
	}
	
	//Selection dans liste + changement titre
	if(idAffich != -1){
		document.getElementById("tdLst-"+idAffich).className = "";
	}
	document.getElementById("tdLst-"+id).className = "green";
	document.getElementById("h3Feuille").innerHTML = "Feuille de présence - " + document.getElementById("tdLst-"+id).childNodes[0].childNodes[0].innerHTML;

	
	
	//On masque le planning affiché et on affiche celui demandé
	
	if(idAffich != -1){
		document.getElementById("feuille-"+idAffich).style.opacity = 0;
		setTimeout(function(){document.getElementById("feuille-"+idAffich).style.display = "none";},150)
	}
	
	
	
	setTimeout(function(){document.getElementById("feuille-"+id).style.display = "";},150)
	setTimeout(function(){document.getElementById("feuille-"+id).style.opacity = 1;},250)

	setTimeout(function(){document.getElementById("h3Feuille").style.display = "";},250)
	setTimeout(function(){document.getElementById("submitPresence").style.display = "";},250)
	setTimeout(function(){document.getElementById("submitPresence").style.display = "";},250)
	
	//setTimeout(function(){self.location.hash="#tab";},400)
	
	//Changement URL
	if(window.history.replaceState){
		window.history.replaceState('Object', 'Title', '/presence-'+id);
	}

	document.getElementById("formEditPresence").action = "http://<?php echo $_SERVER['HTTP_HOST'] ?>/presence-"+id;
	document.getElementById("formRemoveFeuille").action = "http://<?php echo $_SERVER['HTTP_HOST'] ?>/presence-"+id;

	
}




function filtering(id){

	lignes = document.getElementById('tbody-'+id).childNodes;
	
	
	if(document.getElementById('filtre-'+id).value.length>0){
	
		for(var i=0; i<lignes.length; i++){
			
			td = lignes[i].childNodes[0];
			
			if(td.innerHTML.toLowerCase().indexOf(document.getElementById('filtre-'+id).value.toLowerCase())==-1){
				lignes[i].style.display = "none";

			}else{
				lignes[i].style.display = "";
			}
		}
	
	}else{
	
		for(var i=0; i<lignes.length; i++){
			lignes[i].style.display = "";
		}

	}
}

function affPrint(id){
	document.getElementById('personnesPrint-'+id).style.display = "";
	document.getElementById('aPrint-'+id).innerHTML = '<a id ="aPrint-'+id+'" onclick="print('+id+')" style="float:right;"><img src="../template/images/printer.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Valider et afficher la version imprimable</a>';						
}

function print(id){

	id= ""+id+"";
	
	if(id.indexOf("-stats") == -1){

		var rep = "";
		
		enfantsDivPrint = document.getElementById("personnesPrint-"+id).childNodes;

		for(i=0; i<enfantsDivPrint.length; i++){
			
			if(typeof enfantsDivPrint[i].id !== 'undefined'){
				if(enfantsDivPrint[i].id.indexOf("print-"+id) != -1){
					if(enfantsDivPrint[i].checked == true){
						rep += enfantsDivPrint[i].id.replace("print-"+id+"-", "");
					}
				}
			}
		}
		
		if(rep == ""){
			alert("Veuillez choisir des personnes à faire apparaître sur la feuille imprimable.");
		}else{
			window.open("http://<?php echo $_SERVER['HTTP_HOST'] ?>/membres/printPresence.php?id="+id+"&rep="+rep, '_blank');
		}
	
	}else{
		window.open("http://<?php echo $_SERVER['HTTP_HOST'] ?>/membres/printPresence.php?id="+id.replace("-stats", "")+"&rep=stats", '_blank');
	}
	
}

function changePresence(idTab, idLi, present){
	
	var td = document.getElementById('td-'+idTab+'-'+idLi+'-'+present);
	var tds = td.parentNode.childNodes;
	var couleur = "";
	
	if(td.className == "" || td.className == "resetPresence"){
		
		if(present == "O"){
			couleur = "green";
			
		}else if(present == "N"){
			couleur = "red";
			
		}else if(present == "P"){
			couleur = "orange";
		
		}else if(present == "R"){
			couleur = "orange";
		}
		
		
		for(i=1; i<tds.length; i++){
			tds[i].className = "";
		}

		tds[0].className = couleur;
		td.className = couleur;
	
	}else{
		
		tds[0].className = "";
		td.className = "resetPresence";
		
	}
	if(document.getElementById('filtre-'+idTab).value != ""){
		document.getElementById('filtre-'+idTab).value = "";
		document.getElementById('filtre-'+idTab).focus();
	}
	
	//Edit input
	
	var lignes = document.getElementById('tbody-'+idTab).childNodes;
	
	document.getElementById('inputPre-'+idTab).value = "";
	
	for(i=0; i<lignes.length; i++){
		
		for(t=1; t<lignes[i].childNodes.length; t++){
			
			if(lignes[i].childNodes[t].className != ""){
				
				id = lignes[i].childNodes[t].id.split("-");
				
				if(lignes[i].childNodes[t].className=="resetPresence"){
					document.getElementById('inputPre-'+idTab).value += id[2] + "-SUPPR//"
				}else{
					document.getElementById('inputPre-'+idTab).value += id[2] + "-" + id[3] +"//"
				}
				
				break;
			}
		}
	}
}



function submEditPresence(){
	
	document.getElementById("editPresence").value = "";
	tagsINPUT = document.getElementsByTagName("INPUT");
	
	for(i=0; i<tagsINPUT.length; i++){
		
		if(tagsINPUT[i].id.indexOf("inputPre-") != -1){

			idFeuille = tagsINPUT[i].id.replace("inputPre-", "");
			document.getElementById("editPresence").value += idFeuille + "#" + tagsINPUT[i].value + "@@";
			
		}
	}
	
	document.getElementById('submitPresence').disabled=true;
	document.getElementById('submitPresence').value = "Patientez...";
	document.getElementById('submitPresence').onclick="";
	document.getElementById('formEditPresence').submit();
	
}


</script> 
<?php
function triStats($a, $b){
	
	$ordre = 'ORPN';
	
	for($char=0; $char < iconv_strlen($ordre) ; $char++){

		$lettre = substr($ordre, $char, 1);
		
		if(isset($a[$lettre]) && isset($b[$lettre])){
		
			if($a[$lettre] != $b[$lettre]){
		
				if($lettre == 'N'){
					return ($a[$lettre] < $b[$lettre]) ? -1 : 1;
				}else{
					return ($a[$lettre] > $b[$lettre]) ? -1 : 1;
				}
			}
			
		}elseif(isset($a[$lettre])){
			return ($lettre == 'N')?1:-1;
			
		}elseif(isset($b[$lettre])){
			return ($lettre == 'N')?-1:1;
			
		}
	}
	
	if(strnatcmp ($a['nom'] , $b['nom'] ) > 0){
		return 1;
	}else{
		return -1;
	}
}


echo $footer;
?>