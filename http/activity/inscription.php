<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

define('TITRE_PAGE','Inscription');
//Récupération liste activités
$inscrOpen=false;

$bd = db_connect();
$activities = db_tableau($bd, "SELECT id, nom, dte, tme, infos, spots, spotsSold, prix, paiementStatut, consent
						FROM activity_activities
						WHERE DATEDIFF(dte,CURDATE())>=0");

$tabOptions = db_tableau($bd, "
					SELECT o.id, o.idAct, o.opt, o.prixOpt
					FROM activity_options AS o
					LEFT JOIN activity_activities AS act ON act.id = o.idAct
					WHERE DATEDIFF(act.dte,CURDATE())>=0
					ORDER BY o.id ASC");	
					
$consentements = db_tableau($bd, "		
					SELECT id, obligatoire, defaut, texte, texteCase
					FROM gestion_consentements
					WHERE cible=2 OR cible=3
					ORDER BY id ASC");								

					
db_close($bd);

if(!empty($activities)&&$activities!==false){
	$inscrOpen=true;
	$lstActJS="";
	
	//Mise en forme
	$mois = Array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
	for($i=0;$i<count($activities);$i++){
		
		$dte = explode('-',$activities[$i]['dte'],3);
		$activities[$i]['dateText']=(($dte[2]{0}=="0")?$dte[2]{1}:$dte[2]).' '.$mois[intval($dte[1])-1].' '.$dte[0];
		if(!empty($activities[$i]['tme'])){
			$activities[$i]['dateText'] .= '<br /><span style="font-size:0.85em">'.$activities[$i]['tme'].'</span>';
		}

		$spotsSold = explode('//',$activities[$i]['spotsSold'],2);
		$activities[$i]['spotsSold']=array($spotsSold[0],$spotsSold[1]);	
		
		$lstActJS.= 'lstActJS['.$i.']=new Array("'.$activities[$i]['id'].'","'.$activities[$i]['prix'].'","'.$activities[$i]['nom'].'");';
	}
}

if($inscrOpen){
	
	$reselect="";	
	
	//Inscription
	if(isset($_POST['idInscr'])&&isset($_POST['idsAct'])&&isset($_POST['iAct'])){
	
		//Récupération liste d'activités
		$tempActsInscr = explode('//',$_POST['idsAct'],-1);
		$tempIActs = explode('//',$_POST['iAct'],-1);
		
		$listeActsInscr = array();
		$i=0;
		foreach($tempActsInscr as $idAct){
		
		
			$postFullPaid = (isset($_POST['fullPaid'.$idAct])&&$_POST['fullPaid'.$idAct]==true)?1:0;
			$postPaid = (!empty($_POST['paid'.$idAct]))?$_POST['paid'.$idAct]:0;
			$postRecu = (!empty($_POST['recu'.$idAct]))?$_POST['recu'.$idAct]:0;
			
			array_push($listeActsInscr,array("id"=>$idAct, "i"=>$tempIActs[$i], "fullPaid"=>$postFullPaid, "paid"=>$postPaid, "recu"=>$postRecu));
		$i++;
		}

		unset($idAct);
		unset($i);


		$i=0;
		if(count($listeActsInscr)>0){
			
			//Si erreur on reselectionne la personne parcequ'on est est sympa
				$reselect="select('".$_POST['idInscr']."','".$_POST['iInscr']."','".$_POST['nameInscr']."');";
		
		
			$options = explode('//',$_POST['options'],-1);
			
			
			foreach($listeActsInscr as $actInscr){
				
				
				//Calcul prix total avec options
				$prixTotalAct = $activities[intval($actInscr['i'])]['prix'];


				for($opt=0; $opt<count($options); $opt++){
					
					for($lstOpt=0; $lstOpt<count($tabOptions); $lstOpt++){
						
						if($options[$opt] == $tabOptions[$lstOpt]['id'] && $tabOptions[$lstOpt]['idAct']==$actInscr['id']){
							$prixTotalAct += $tabOptions[$lstOpt]['prixOpt'];
							break;
						}
					}
				}
				
				//Init consentements apres POST pour conserver les choix + Verif consentements obligatoires

				if($consentements!==false && !empty($consentements) && !empty($activities[intval($actInscr['i'])]['consent'])){
				
					
					$tabConsentAct = explode('///',$activities[intval($actInscr['i'])]['consent'],-1);
					
					for($consent=0; $consent<count($consentements); $consent++){
						
						if(in_array($consentements[$consent]['id'],$tabConsentAct)){
						
							if(isset($_POST['caseConsent-'.$consentements[$consent]['id'].'-'.$actInscr['id']])){
								
								
								$reselect.='document.getElementById("caseConsent-'.$consentements[$consent]['id'].'-'.$actInscr['id'].'").checked=true;';
								
							}else{
								
								
								$reselect.='document.getElementById("caseConsent-'.$consentements[$consent]['id'].'-'.$actInscr['id'].'").checked=false;';

							}

							if($consentements[$consent]['obligatoire'] && !isset($_POST['caseConsent-'.$consentements[$consent]['id'].'-'.$actInscr['id']])){
								array_push($pageMessages, array('type'=>'err', 'content'=>'Vous devez accepter les clauses obligatoires.'));
							}
						}
					}
				}
				
				if($prixTotalAct < 0){
					array_push($pageMessages, array('type'=>'err', 'content'=>"Le prix d'une activité ne peut pas être négatif."));
				
				
				}elseif(!empty($actInscr['paid'])){
				//Verifs

				
					if(mb_strlen($actInscr['paid'])>7){
						array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>'.$activities[intval($actInscr['i'])]['nom'].' - Somme payée</em> ne doit pas dépasser 7 caractères.'));
					}	
					if(!is_numeric($actInscr['paid'])){
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$activities[intval($actInscr['i'])]['nom'].' - Somme payée</em> n\'est pas valide.'));
					}elseif($actInscr['paid']<0){
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$activities[intval($actInscr['i'])]['nom'].' - Somme payée</em> n\'est pas valide.'));
					}
					
					if($actInscr['paid']!=0){
						if(mb_strlen($actInscr['recu'])>3){
							array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>'.$activities[intval($actInscr['i'])]['nom'].' - Numéro reçu</em> ne doit pas dépasser 3 caractères.'));
						}
						if(empty($actInscr['recu'])){
							array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>'.$activities[intval($actInscr['i'])]['nom'].' - Numéro reçu</em>.'));
						}
						elseif (!is_numeric($actInscr['recu'])){
							array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$activities[intval($actInscr['i'])]['nom'].' - Numéro reçu</em> n\'est pas valide.'));
						}elseif($actInscr['recu']<0){
							array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$activities[intval($actInscr['i'])]['nom'].' - Numéro reçu</em> n\'est pas valide.'));
						}
					}
				}
				
				if($prixTotalAct == 0){
					$listeActsInscr[$i]['fullPaid'] = 1;
				}
				$listeActsInscr[$i]['prixTotalAct'] = $prixTotalAct;
			
				$i++;
			}//fin foreach verifs
			unset($actInscr);	
			unset($i);
			//deja inscrit ?
			$bd = db_connect();
			foreach($listeActsInscr as $actInscr){
			
				$isInscr = db_ligne($bd, "		
									SELECT idAct
									FROM activity_participants
									WHERE idAct='".$actInscr['id']."' AND idAdh='".$_POST['idInscr']."'");
		
				if($isInscr===false){die();}
				if(!empty($isInscr)){
					array_push($pageMessages, array('type'=>'err', 'content'=>'La personne est déjà inscrite à l\'activité <em>'.$activities[intval($actInscr['i'])]['nom'].'</em>.'));
				}
			}
			unset($actInscr);
			db_close($bd);
			
			//Carte ESN encore valide lors de l'activité ?
			$bd = db_connect();
				
			foreach($listeActsInscr as $actInscr){
				
				$dteFinInscr = db_valeur($bd, "		
							SELECT dateFinInscr
							FROM membres_adherents
							WHERE id='".$_POST['idInscr']."'");
							
							
				if($dteFinInscr===false){die();}
				if(!empty($dteFinInscr)){
					if(date_create($dteFinInscr) < date_create($activities[intval($actInscr['i'])]['dte'])){
						array_push($pageMessages,array('type'=>'err', 'content' => "La carte ESN de ".$_POST['nameInscr']." ne sera plus valide au moment de l'activité <em>".$activities[intval($actInscr['i'])]['nom']."</em>."));
					}
				}else{
					array_push($pageMessages,array('type'=>'err', 'content' => "La carte ESN de ".$_POST['nameInscr']." ne sera plus valide au moment de l'activité <em>".$activities[intval($actInscr['i'])]['nom']."</em>."));
				}
			}				
			db_close($bd);
			unset($actInscr);
			
			//finalisation des updates + inscription si pas d'erreur
			if(empty($pageMessages)){
				$bd = db_connect();
					
				foreach($listeActsInscr as $actInscr){
			
					$spotsSold = $activities[intval($actInscr['i'])]['spotsSold'];	
					
					if($activities[intval($actInscr['i'])]['spots']!=0 && (intval($activities[intval($actInscr['i'])]['spots'])-intval($spotsSold[0]))<= 0){
						$attente=1;
						$spotsSold[1] = intval($spotsSold[1])+1;
					}else{
						$attente=0;
						$spotsSold[0] = intval($spotsSold[0])+1;
					}
					
					
					$tabPaiements = explode('//',$activities[intval($actInscr['i'])]['paiementStatut'],2);	
					if($actInscr['fullPaid']==0 && $attente==0){
						$tabPaiements[0]=intval($tabPaiements[0])+1;
					}elseif($attente==1 && $actInscr['paid']>0){//Ajout d'un remboursement probable
						$tabRemboursement = explode('/',$tabPaiements[1],2);	
						$tabPaiements[1]=$tabRemboursement[0].'/'.(intval($tabRemboursement[1])+1);
					}
						
					$addParticipant = db_exec($bd, "
						INSERT INTO activity_participants(idAct, idAdh, paid, fullPaid, recu, listeAttente, dateInscr, inscrBy)
						VALUES('".$actInscr['id']."','".$_POST['idInscr']."','".$actInscr['paid']."','".$actInscr['fullPaid']."','".$actInscr['recu']."',
						'".$attente."',NOW(),'".PRENOM." ".NOM."')");
						
						
					//Ajout des options
					$idPart = db_lastId($bd);
					
					for($opt=0; $opt<count($options); $opt++){
						
						for($lstOpt=0; $lstOpt<count($tabOptions); $lstOpt++){
							
							if($options[$opt] == $tabOptions[$lstOpt]['id'] && $tabOptions[$lstOpt]['idAct']==$actInscr['id']){
								$addOpt	= db_exec($bd, "
												INSERT INTO activity_options_participants(idPart, idOpt) 
												VALUES(".$idPart.",".$tabOptions[$lstOpt]['id'].")");
							
								if($addOpt === false){
									die("Erreur ajout option");
								}
							}
						}
					}
						
					//Ajout consentements
				
					for($consnt=0; $consnt<count($consentements); $consnt++){
						if(isset($_POST['caseConsent-'.$consentements[$consnt]['id'].'-'.$actInscr['id']])){
							
							$addConsent = db_exec($bd, "
									INSERT INTO gestion_consentements_accepted(idAdh, idConsent, idAct)
									VALUES(".$_POST['idInscr'].",".$consentements[$consnt]['id'].",".$actInscr['id'].")
									ON DUPLICATE KEY UPDATE idAdh=idAdh");

							if($addConsent===false){die("Erreur ajout consentement.");}
						}
					}
			
			
			
					if($addParticipant!==false){

						$updateActivity = db_exec($bd, "
							UPDATE activity_activities
							SET spotsSold='".$spotsSold[0]."//".$spotsSold[1]."', paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
							WHERE id='".$actInscr['id']."'");

						if($updateActivity!==false){
							array_push($pageMessages,array('type'=>'ok', 'content' => $_POST['nameInscr']." a bien été inscrit à l'activité <em>".$activities[intval($actInscr['i'])]['nom']."</em>."));
							
							
							//Modif caisse
							if($actInscr['paid']>0){
								
								
								$complementDescr = "";
										
										
								if(!$actInscr['fullPaid'] && $actInscr['paid']<$actInscr['prixTotalAct']){
									$complementDescr = " (Paiement incomplet)";
								
								}elseif($actInscr['paid']>$actInscr['prixTotalAct']){
									$complementDescr = " (Paiement excédentaire)";
								
								}elseif($activities[intval($actInscr['i'])]['prix'] != $actInscr['prixTotalAct']){
									$complementDescr = " (Options : ".($actInscr['prixTotalAct'] - $activities[intval($actInscr['i'])]['prix'])."€)";
								}
								
								
								$descrCaisse = $activities[intval($actInscr['i'])]['nom']." : Inscription de ".$_POST['nameInscr'].$complementDescr;
								addCaisse($descrCaisse, $actInscr['paid'], $actInscr['recu'], 'local', $actInscr['id']);
							}
						}							
					}					
				}//fin du dernier foreach
				unset($actInscr);
				$reselect="";
				
				//Actualisation infos activités apres inscription
				$activities = db_tableau($bd, "SELECT id, nom, dte, tme, infos, spots, spotsSold, prix, paiementStatut
										FROM activity_activities
										WHERE DATEDIFF(dte,CURDATE())>=0");
					
				if(!empty($activities)&&$activities!==false){
					$inscrOpen=true;
					$lstActJS="";
					
					//Mise en forme
					for($i=0;$i<count($activities);$i++){
						
						$dte = explode('-',$activities[$i]['dte'],3);
						$activities[$i]['dateText']=(($dte[2]{0}=="0")?$dte[2]{1}:$dte[2]).' '.$mois[intval($dte[1])-1].' '.$dte[0];
						if(!empty($activities[$i]['tme'])){
							$activities[$i]['dateText'] .= '<br /><span style="font-size:0.85em">'.$activities[$i]['tme'].'</span>';
						}
						
						$spotsSold = explode('//',$activities[$i]['spotsSold'],2);
						$activities[$i]['spotsSold']=array($spotsSold[0],$spotsSold[1]);	
						
						$lstActJS.= 'lstActJS['.$i.']=new Array("'.$activities[$i]['id'].'","'.$activities[$i]['prix'].'","'.$activities[$i]['nom'].'");';
					}
				}
				db_close($bd);
			}			
		}else{
			array_push($pageMessages, array('type'=>'err', 'content'=>'Pas d\'activité sélectionnée.'));
		}
	}	
	
	//Récupération liste membres
	$bd = db_connect();

	$tabAdh = db_tableau($bd, "
						SELECT adh.id, adh.idesn, adh.prenom, adh.nom, adh.pays, adh.divers
						FROM membres_adherents AS adh
						ORDER BY adh.prenom ASC, adh.nom ASC");
	db_close($bd);
	
	$lstAdh="";
	$lstAdhJS="";
	
	if($tabAdh!==false){

		for($i=0; $i<count($tabAdh); $i++){
			
			$lstAdh.='<tr id="line'.$i.'" style="display:none"><td class="gras">'.$tabAdh[$i]['prenom'].' '.$tabAdh[$i]['nom'].'</td>
						<td>'.$tabAdh[$i]['pays'].'</td><td>'.$tabAdh[$i]['idesn'].'</td>
						<td id="cell'.$i.'" class="add" onclick="select('.$tabAdh[$i]['id'].','.$i.',\''.str_replace("'","\'", $tabAdh[$i]['prenom']).' '.str_replace("'","\'", $tabAdh[$i]['nom']).'\')"></td></tr>';
						
			$lstAdhJS.= 'lstAdhJS['.$i.']=new Array("'.strtolower($tabAdh[$i][2]).'","'.strtolower($tabAdh[$i][3]).'","'.strtolower($tabAdh[$i][1]).'","'.str_replace('"','\"', str_replace(array("\r\n", "\r", "\n"), '<br />', $tabAdh[$i]['divers'])).'");';

			//Pré selection du membre apres inscription
			if(isset($_GET['idAdh'])){
				if($_GET['idAdh']==$tabAdh[$i]['id']){
					$reselect='select('.$tabAdh[$i]['id'].','.$i.',\''.$tabAdh[$i]['prenom'].' '.$tabAdh[$i]['nom'].'\')';
				}
			}
		}
	}
	
}//FIN VERIF Inscription ouvertes

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<?php if($inscrOpen){ ?>

<h3>Selection de l'adhérent</h3>
<table id="champsFilter" class="invisible"><tbody><tr><td>
<label for="nom">prénom ou nom</label>
<input type="text" id="nom" name="nom" onkeyup="filtering()" value="" autocomplete="off"/>
</td><td>
<label for="carteesn" id="labelCarteesn">numero carte esn</label>
<input type="text" id="carteesn" name="carteesn" onkeyup="filtering()" style="width:120px" value="" autocomplete="off"/>
</td></tr></tbody></table>
<table id="listeAdh" style="display:none"><tbody><th style="width:255px">Nom</th><th>Pays</th><th style="width:100px">Carte ESN</th><th id="lastTHAdh" style="width:70px" >Inscrire</th>
<?php echo $lstAdh; ?>
</tbody></table>



<div id="diversAdh" style="display:none">
<h3>Informations sur l'adhérent</h3>
<div class="blocText" id="textDiversAdh"></div>
</div>



<h3>Inscrire à</h3>
<br />
<?php 
foreach($activities as $act){
	echo '<input type="checkbox" id="boxAct'.$act['id'].'" name="boxAct'.$act['id'].'" onchange="selectAct()">
			<label class="checkbox" for="boxAct'.$act['id'].'" style="margin-bottom:10px">'.$act['nom'].'</label><br />';
}
unset($act);
?>

<form method=post action="http://<?php echo $_SERVER['HTTP_HOST']; ?>/activity/inscription.php" id="formInscr" style="display:none">
<?php


foreach($activities as $act){
	echo '<div id="infoAct'.$act['id'].'" style="display:none">';
	echo '<h3>Informations - '.$act['nom'].'</h3>';

	//Alert file d'attente?
	if($act['spots']!=0&&(intval($act['spots'])-intval($act['spotsSold'][0]))<=0){
		echo '<div class="blocText gras center"><img class="iconeAlert" src="../template/images/alert.png"/>Attention : la personne sera inscrite en liste d\'attente.</div><br/>';
	}

	//Tableau
	echo '<table class="activity"><tbody>';
	if($act['spots']==0){
		echo'<tr><th rowspan=2 style="font-size:1.25em;">'.$act['dateText'].'</th>
			<th style="width:150px"><b>Places</b></th>
			<th style="width:300px"><b>Inscrits</b></th>
			<th style="width:150px"><b>Prix</b></th></tr>
			
			<tr><td>Illimitées</td>
			<td>'.$act['spotsSold'][0].'</td>
			<td>'.(($act['prix']==0)?"Gratuit":$act['prix']."€").'</td></tr>';
	}else{
		echo'<tr><th rowspan=2 style="font-size:1.25em;">'.$act['dateText'].'</th>
			<th style="width:150px"><b>Places</b></th>
			<th style="width:150px"><b>Places restantes</b></th>
			<th style="width:150px"><b>Inscrits</b></th>
			<th style="width:150px"><b>Prix</b></th></tr>
			
			<tr><td>'.$act['spots'].'</td>
			<td>'.((intval($act['spots'])-intval($act['spotsSold'][0])>0)?(intval($act['spots'])-intval($act['spotsSold'][0])):'<font color="yellow">Complet</font>').'</td>
			<td>'.(($act['spotsSold'][1]==0)?$act['spotsSold'][0]:$act['spotsSold'][0].' + '.$act['spotsSold'][1].' en attente').'</td>
			<td>'.(($act['prix']==0)?"Gratuit":$act['prix']."€").'</td></tr>';
	}
	echo '</tbody></table>';

	//infos
	echo'<br/><div class="blocText">'.bbCodeToHTML($act['infos']).'</div>';	
	
	//Liste d'Options
	$tableOptions = "";
	$comptOpt = 0;
	
	for($opt=0; $opt<count($tabOptions); $opt++){
		
		if($tabOptions[$opt]['idAct'] == $act['id']){
		
			if($tabOptions[$opt]['prixOpt'] > 0 ){
				$textPrix = " (Supplément : " .$tabOptions[$opt]['prixOpt']. "€)";
				
			}else if($tabOptions[$opt]['prixOpt'] < 0){
				$textPrix = " (Réduction : " .$tabOptions[$opt]['prixOpt']. "€)";
				
			}else{
				$textPrix ="";
			}
			$tableOptions .= '<tr><td>'.$tabOptions[$opt]['opt'].'<div style="float:right">'.$textPrix.'</div>'.
								'<input type="hidden" id="idOpt'.$comptOpt.'idAct'.$act['id'].'"  value="'.$tabOptions[$opt]['id'].'"/>'.
								'<input type="hidden" id="prixOpt'.$comptOpt.'idAct'.$act['id'].'" value="'.$tabOptions[$opt]['prixOpt'].'"/>'.
								'</td>'.
								'<td id="tdSelectOpt'.$comptOpt.'idAct'.$act['id'].'" onclick="selectOpt('.$comptOpt.','.$act['id'].')" class="checkN" style="width:90px"></td></tr>';
			$comptOpt++;
		}
	}
	

	if(!empty($tableOptions)){
		echo '<br/><table style="margin-top:5px; width:88%">';
		echo '<th>Options</th><th>Choix</th>';
		echo '<tbody id="tbodyOptions'.$act['id'].'">'.$tableOptions.'</tbody>';
		echo '</table>';
	}
	
	
	//Mise en forme case consentements
	
	$tabConsentementsObligJS="";
	$listeConsentements="";

	if($consentements!==false && !empty($consentements) && !empty($act['consent'])){
	
		$tabConsentAct = explode('///',$act['consent'],-1);
		$listeConsentements='<div id="divConsent'.$act['id'].'" style="padding:0; margin-top:5px; width:88%"><table style="width:100%; margin:0">';


		for($i=0; $i<count($consentements); $i++){
			
			if(in_array($consentements[$i]['id'],$tabConsentAct)){
			
				$listeConsentements.='<tr><td style="padding-left8px; cursor:pointer" onclick="checkCaseConsent('.$consentements[$i]['id'].','.$act['id'].')"><input type="checkbox" id="caseConsent-'.$consentements[$i]['id'].'-'.$act['id'].'" name="caseConsent-'.$consentements[$i]['id'].'-'.$act['id'].'" '.(($consentements[$i]['defaut'])?'checked':'').'>
				<label class="checkbox '.(($consentements[$i]['obligatoire'])?'required':'').'" for="caseConsent-'.$consentements[$i]['id'].'-'.$act['id'].'" style="margin-bottom:6px; display:inline">'.$consentements[$i]['texteCase'].'</label>(<a onclick="affConsent('.$consentements[$i]['id'].','.$act['id'].')" id="aConsent-'.$consentements[$i]['id'].'-'.$act['id'].'">afficher</a>)</div>
				<div id="divTextConsent-'.$consentements[$i]['id'].'-'.$act['id'].'" style="display:none;padding-left:4px; margin-bottom:10px; width:95%;">'.bbCodeToHTML($consentements[$i]['texte']).'</div></td></tr>';
				
				
				if($consentements[$i]['obligatoire']){
					$tabConsentementsObligJS.= 'tabConsentementsObligJS.push("'.$consentements[$i]['id'].'-'.$act['id'].'");';
				}
			}
		}
		
		$listeConsentements.='</table></div>';
	}
	
	
	if(!empty($listeConsentements)){ echo $listeConsentements;}
	
	
	
	
	echo '<div id="divSommeDue'.$act['id'].'" class="blocText" style="display:none; width:88%; margin-top:5px">Prix pour cette activité : '.
				'<span id="sommeDue'.$act['id'].'" style="font-weight:bold">'.$act['prix'].'€</span></div>';
	
	echo '<br/></div>'; //fin div infos activité
}
unset($act);
?>



<h3>Paiement</h3>
	<input type="hidden" id="idInscr" name="idInscr" />
	<input type="hidden" id="nameInscr" name="nameInscr" />
	<input type="hidden" id="iInscr" name="iInscr" />
	<input type="hidden" id="idsAct" name="idsAct" />
	<input type="hidden" id="iAct" name="iAct" />
	<input type="hidden" id="options" name="options" />

 
	<table id="payGroup" style="width:88%;">
	<tr class="thPaye"><th colspan=3>Récapitulatif</th></tr>
	<tr><td>
		
		<table class="invisible" style="margin:0; width:100%; height:66px;"><tbody><tr>
		
		<td style="text-align:center; width:240px">Prix pour toutes les activités :<br/><span id="sommeDueTotaleTable" style="font-weight:bold">0€</span></td>
		
		<td style="vertical-align:bottom; text-align:left; width:325px"><input type="checkbox" id="allFullPaid" name="allFullPaid" onchange="putFullPaid()"> 
			<label class="checkbox" for="allFullPaid">Toutes les activités entièrement payées</label></td>
		
		<td>
			<label for="recu">numero reçu</label>
			<input type="text" id="uniqueRecu" name="uniqueRecu" onkeyup="putRecu()" style="width:70px" maxlength=3 autocomplete="off"></td>
		
		</tr></tbody></table>
		
	</td></tr></table><br/>
	
	
	<table id="payGroupGratuit" style="width:88%;">
	<tr class="thPaye"><th colspan=2>Récapitulatif</th></tr>
	<tr><td>
		
		<table class="invisible" style="margin:0; width:100%; height:60px;"><tbody><tr>
		
		<td style="text-align:center; width:240px"><span id="textPrixTotalActGratuit">Prix pour toutes les activités :</span><br/><span style="font-weight:bold">0€</span></td>
		
		<td><center><input type="button" onclick="submInscr(this.id)" id="submitInscrGratuit" value="valider" style="margin:0; width:70%"/></center></td>
		

		
		</tr></tbody></table>
		
	</td></tr></table>
	
	

	<?php foreach($activities as $act){ ?>
	
	
		<div id="divPaiement<?php echo $act['id']; ?>" style="padding-left:35px; box-sizing: border-box; width:88%; margin-bottom:5px;">
		<table><tr class="thPaye"><th><?php echo $act['nom']; ?></th></tr>
		<tr><td id="tabPaiement<?php echo $act['id']; ?>" class="grisé">
			<table class="invisible" style="margin:0; width:100%; height:50px"><tbody>
			
			<tr>
				<td style="text-align:center; width:205px">Prix pour cette activité :<br/><span id="sommeDueTable<?php echo $act['id']; ?>" style="font-weight:bold">0€</span></td>

				<td id="tdFullPaid<?php echo $act['id']; ?>" style="vertical-align:bottom; text-align:left" >
					<input type="checkbox" id="fullPaid<?php echo $act['id']; ?>" name="fullPaid<?php echo $act['id']; ?>" onchange="putPrix(<?php echo $act['id']; ?>)"> 
					<label class="checkbox" for="fullPaid<?php echo $act['id']; ?>" style="margin-bottom:14px">Entièrement payé</label>
				</td>
				
				<td id="tdPaid<?php echo $act['id']; ?>">
					<label for="paid<?php echo $act['id']; ?>">somme payée</label>
					<input type="text" id="paid<?php echo $act['id']; ?>" name="paid<?php echo $act['id']; ?>" onkeyup="setSommeTotale()" class="euro" style="width:70px; margin-bottom:6px" maxlength=7 autocomplete="off"> 
				</td>
				<td id="tdRecu<?php echo $act['id']; ?>" >
				<label for="recu<?php echo $act['id']; ?>" >numero reçu</label>
				<input type="text" id="recu<?php echo $act['id']; ?>" name="recu<?php echo $act['id']; ?>" style="width:70px; margin-bottom:6px" maxlength=3 autocomplete="off"> 
				</td>
				
				<td id="tdSubmit<?php echo $act['id']; ?>" style="min-width:120px"><center><input type="button" onclick="submInscr(this.id)" id="submitInscr-<?php echo $act['id']; ?>" value="valider" style="margin-top:0;"/><center></td>
			</tr>
			
			
			
			<?php if($act['spots']!=0&&(intval($act['spots'])-intval($act['spotsSold'][0]))<=0){ ?>
				<tr id="trInfosListeAttente<?php echo $act['id']; ?>"><td colspan="100%" style="padding-top:5px; padding-bottom:3px; font-size:0.8em; line-height:1em" >En liste d'attente, la personne peut tout de même payer l'inscription afin qu'elle ne soit pas obligée de repasser au local si son inscription est validée.<br/>Si son inscription n'est pas validée, la personne sera automatiquement placée dans une liste spéciale en attendant son remboursement.</td></tr>
			<?php } ?>
			
			</tbody></table>
		</td></tr></table>
		</div>
		
		
	<?php unset($act); }?>
<div class="blocText gras" style="margin-left:35px; margin-top:16px; width:235px" id="sommePayeeTotale"></div>
<input type="button" onclick="submInscr(this.id)" id="submitInscr" value="valider" style="margin-left:35px; margin-top:8px; width:235px"/>
</form>


<script type="text/javascript">


var lstAdhJS=new Array();
<?php echo $lstAdhJS; ?>

var lstActJS=new Array();
<?php echo $lstActJS; ?>

<?php echo $reselect; ?>

var tabConsentementsObligJS=new Array();
<?php echo $tabConsentementsObligJS;?>

function filtering(){

	if(document.getElementById('carteesn').value.length>2 || document.getElementById('nom').value.length>1){
		document.getElementById('listeAdh').style.display="";
	
		for(var i=0; i<lstAdhJS.length; i++){
			var nom = lstAdhJS[i][0]+" "+lstAdhJS[i][1];
			if(lstAdhJS[i][2].indexOf(document.getElementById('carteesn').value.toLowerCase())==-1 || nom.indexOf(document.getElementById('nom').value.toLowerCase())==-1){
				document.getElementById('line'+i).style.display = "none";

			}else{
				document.getElementById('line'+i).style.display = "";
			}
		}
	}else{
	
		for(var i=0; i<lstAdhJS.length; i++){
			document.getElementById('line'+i).style.display = "none";
		}
		document.getElementById('listeAdh').style.display="none";
	}
}

function select(id,i,name){

	if(document.getElementById('line'+i).className == "selected"){
		document.getElementById('line'+i).className = "";
		document.getElementById('cell'+i).className = "add";
		document.getElementById('lastTHAdh').innerHTML="Inscrire";

		document.getElementById('idInscr').value="";
		document.getElementById('nameInscr').value="";
		document.getElementById('iInscr').value="";
				
		document.getElementById('champsFilter').style.display="";
		
		document.getElementById('diversAdh').style.display="none";
		document.getElementById('textDiversAdh').innerHTML="";
		
		filtering();
	
	}else{
		document.getElementById('line'+i).className = "selected";
		document.getElementById('cell'+i).className = "remove";
		document.getElementById('lastTHAdh').innerHTML="Annuler";

		document.getElementById('idInscr').value=id;
		document.getElementById('nameInscr').value=name;
		document.getElementById('iInscr').value=i;
		
		document.getElementById('champsFilter').style.display="none";
		
		if(lstAdhJS[i][3] != ""){
			document.getElementById('diversAdh').style.display="";
			document.getElementById('textDiversAdh').innerHTML=lstAdhJS[i][3];
		}
		

		document.getElementById('listeAdh').style.display="";
		for(var a=0; a<lstAdhJS.length; a++){
			if(a!=i){
				document.getElementById('line'+a).style.display = "none";
			}else{
				document.getElementById('line'+a).style.display = "";				
			}
		}
	}
}

function selectAct(){
	document.getElementById('idsAct').value="";
	document.getElementById('iAct').value="";
	document.getElementById('formInscr').style.display = "none";


	for(var i=0; i<lstActJS.length; i++){
		if(document.getElementById('boxAct'+lstActJS[i][0]).checked==true){
			document.getElementById('idsAct').value+=lstActJS[i][0]+"//";
			document.getElementById('iAct').value+=i+"//";
			document.getElementById('formInscr').style.display = "";
		
			//Affichage infos + options
			document.getElementById('infoAct'+lstActJS[i][0]).style.display = "";

		}else{
			//On masque l'activité
			document.getElementById('infoAct'+lstActJS[i][0]).style.display = "none";
			
			//On remet à zéro les options, si y'en a
			
			document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML = lstActJS[i][1] + "€";
			
			if(!!document.getElementById('tbodyOptions'+lstActJS[i][0])){ //Y'a des options ou pas?
		
				var tbodyOptions = document.getElementById('tbodyOptions'+lstActJS[i][0]).childNodes;
				
				for(opt=0; opt<(tbodyOptions.length); opt++){
					tbodyOptions[opt].className="";
					document.getElementById('tdSelectOpt'+opt+'idAct'+lstActJS[i][0]).className="checkN";
				}
			
				majValueOptions();
			
			}
		}
	}
	createPaiementTable();
}


function selectOpt(opt, idAct){
	
	var prixActTotal = 0; 
	var prixAct = 0;
	
	for(var act=0; act<lstActJS.length; act++){ //Recherche prix base activité

		if(idAct == lstActJS[act][0]){
			prixActTotal =  parseFloat(lstActJS[act][1]);
			prixAct =  parseFloat(lstActJS[act][1]);
			break;
		}
	}
	
	var tbodyOptions = document.getElementById('tbodyOptions'+idAct).childNodes;
	
	
	if(tbodyOptions[opt].className != "selected"){
	
		prixActTotal += parseFloat(document.getElementById('prixOpt'+opt+'idAct'+idAct).value);
		
		for(var i=0; i<(tbodyOptions.length); i++){
			if(tbodyOptions[i].className=="selected"){
				prixActTotal += parseFloat(document.getElementById('prixOpt'+i+'idAct'+idAct).value);
			}
		}
		
		if(prixActTotal >= 0){
			tbodyOptions[opt].className="selected";
			document.getElementById('tdSelectOpt'+opt+'idAct'+idAct).className="checkO";
			document.getElementById('sommeDue'+idAct).innerHTML = prixActTotal + "€";
			majValueOptions();
			if(document.getElementById('fullPaid'+idAct).checked==true){
				document.getElementById('paid'+idAct).value=document.getElementById('sommeDue'+idAct).innerHTML.replace("€","");
			}
			createPaiementTable();
			
			if(prixActTotal != prixAct){
				document.getElementById('divSommeDue'+idAct).style.display = "";
			}
			
			
			
		}else{
			
			alert("Le prix de l'activité ne peut pas être négatif.");
		}
	
	}else{
		
		tbodyOptions[opt].className="";
		document.getElementById('tdSelectOpt'+opt+'idAct'+idAct).className="checkN";


		for(var i=0; i<(tbodyOptions.length); i++){
			if(tbodyOptions[i].className=="selected"){
				prixActTotal += parseFloat(document.getElementById('prixOpt'+i+'idAct'+idAct).value);
			}
		}
		
		if(prixActTotal >= 0){

			document.getElementById('sommeDue'+idAct).innerHTML = prixActTotal + "€";
			majValueOptions();
			if(document.getElementById('fullPaid'+idAct).checked==true){
				document.getElementById('paid'+idAct).value=document.getElementById('sommeDue'+idAct).innerHTML.replace("€","");
			}
			createPaiementTable();
			
			if(prixActTotal != prixAct){
				document.getElementById('divSommeDue'+idAct).style.display = "";
			}
			
			
		}else{
			
			alert("Le prix de l'activité ne peut pas être négatif.");
		}
	}
}



function majValueOptions(){
	
	var options = "";
	
	for(var a=0; a<lstActJS.length; a++){ //pour touts les activités

		if(!!document.getElementById('tbodyOptions'+lstActJS[a][0])){ //Y'a des options ou pas?
		
			var tbodyOptions = document.getElementById('tbodyOptions'+lstActJS[a][0]).childNodes;
		
			for(var i=0; i<(tbodyOptions.length); i++){
				if(tbodyOptions[i].className=="selected"){
					options += document.getElementById('idOpt'+i+'idAct'+lstActJS[a][0]).value +"//";
				}
			}
		}
	}

	document.getElementById('options').value = options;
	
}



function affConsent(id,act){
	
	//Annulation click sur le td
	checkCaseConsent(id,act);
	
	if(document.getElementById('divTextConsent-'+id+'-'+act).style.display == "none"){
		document.getElementById('divTextConsent-'+id+'-'+act).style.display = "";
		document.getElementById('aConsent-'+id+'-'+act).innerHTML = "masquer";

	}else{
		document.getElementById('divTextConsent-'+id+'-'+act).style.display = "none";
		document.getElementById('aConsent-'+id+'-'+act).innerHTML = "afficher";
	}
	
}

function checkCaseConsent(id,act){
	
	
	if(document.getElementById('caseConsent-'+id+'-'+act).disabled == false){
	
		if(document.getElementById('caseConsent-'+id+'-'+act).checked == true){
			document.getElementById('caseConsent-'+id+'-'+act).checked = false;

		}else{
			document.getElementById('caseConsent-'+id+'-'+act).checked = true;
		}
	}
}



function createPaiementTable(){
	
	//On compte le nombre d'activités cochées et cochées payantes

	var nbActPayantChecked = 0;
	var sommeTotale = 0;
	var nbActChecked = 0;
	
	
	for(var i=0; i<lstActJS.length; i++){
		if(document.getElementById('boxAct'+lstActJS[i][0]).checked==true){
		
			nbActChecked++;
			sommeDueAct = parseFloat(document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML.replace("€",""));
			
			document.getElementById('sommeDueTable'+lstActJS[i][0]).innerHTML = sommeDueAct+"€";
			
			if(sommeDueAct > 0){
				nbActPayantChecked ++;
				sommeTotale += sommeDueAct;
			}
		}
	}

	document.getElementById('sommeDueTotaleTable').innerHTML = sommeTotale+"€";
	
	if(nbActChecked == 1){ //une seule act cochée
	
		document.getElementById('payGroup').style.display = "none";

	
		//On vire les en-tetes
		for(var th=0; th<document.getElementsByClassName('thPaye').length; th++){ 
			document.getElementsByClassName('thPaye')[th].style.display="none";
		}
		
		
		//On adapte le texte somme totale
		document.getElementById('textPrixTotalActGratuit').innerHTML = "Prix pour cette activité :"
		
		
		for(var i=0; i<lstActJS.length; i++){
			//On place correctement les modules de paiement + changement class
			document.getElementById('divPaiement'+lstActJS[i][0]).style.paddingLeft = "0";
			document.getElementById('tabPaiement'+lstActJS[i][0]).className ="";	
			
			//On ajoute le bouton valider en derniere colonne des modules de paiement
			document.getElementById('tdSubmit'+lstActJS[i][0]).style.display ="";	
			
		}
		

		//On vire le dernier bouton valider et somme payee totale
		document.getElementById('submitInscr').style.display = "none";
		document.getElementById('sommePayeeTotale').style.display = "none";
		
	}else{
		
		document.getElementById('payGroup').style.display = "";

		
		//On met les en-tetes
		for(var th=0; th<document.getElementsByClassName('thPaye').length; th++){ 
			document.getElementsByClassName('thPaye')[th].style.display="";
		}
		

		//On adapte le texte somme totale
		document.getElementById('textPrixTotalActGratuit').innerHTML = "Prix pour toutes les activités :"
		
		
		for(var i=0; i<lstActJS.length; i++){
			//On place correctement les modules de paiement + changement class
			document.getElementById('divPaiement'+lstActJS[i][0]).style.paddingLeft = "35px";
			document.getElementById('tabPaiement'+lstActJS[i][0]).className ="grisé";	
			
			//On ajoute le bouton valider en derniere colonne des modules de paiement
			document.getElementById('tdSubmit'+lstActJS[i][0]).style.display ="none";
		}
		
		//On ajoute le dernier bouton valider et somme payée totale
		document.getElementById('submitInscr').style.display = "";
		document.getElementById('sommePayeeTotale').style.display = "";
		
	}
	
	
	
	if(nbActPayantChecked == 0){ //Pas d'activités payantes
		
		document.getElementById('payGroupGratuit').style.display = "";
		document.getElementById('payGroup').style.display = "none";
		document.getElementById('submitInscr').style.display = "none";
		document.getElementById('sommePayeeTotale').style.display = "none";
		
		for(var i=0; i<lstActJS.length; i++){
			//On vire tous les modules de paiement
			document.getElementById('divPaiement'+lstActJS[i][0]).style.display = "none";
		}
		
	}else{
		
		document.getElementById('payGroupGratuit').style.display = "none";
		document.getElementById('allFullPaid').checked=true;
		
		for(var i=0; i<lstActJS.length; i++){
			//On affiche les modules de paiement ou les activités sont cochées
						
			if(document.getElementById('boxAct'+lstActJS[i][0]).checked==true){
				
				document.getElementById('divPaiement'+lstActJS[i][0]).style.display = "";
				
				if(parseFloat(document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML.replace("€","")) > 0 ){
					
					document.getElementById('tdFullPaid'+lstActJS[i][0]).style.display ="";
					document.getElementById('tdPaid'+lstActJS[i][0]).style.display ="";
					document.getElementById('tdRecu'+lstActJS[i][0]).style.display ="";
					
					
					//On affiche l'info paiement en liste d'attente si elle est la
					if(!!document.getElementById('trInfosListeAttente'+lstActJS[i][0])){
						document.getElementById('trInfosListeAttente'+lstActJS[i][0]).style.display="";
					}
					
					
					if(document.getElementById('fullPaid'+lstActJS[i][0]).checked==false){
						document.getElementById('allFullPaid').checked=false;
					}
					
					
					
				}else{
					
					document.getElementById('tdFullPaid'+lstActJS[i][0]).style.display ="none";
					document.getElementById('tdPaid'+lstActJS[i][0]).style.display ="none";
					document.getElementById('tdRecu'+lstActJS[i][0]).style.display ="none";
					
					//On vire l'info paiement en liste d'attente si elle est la
					if(!!document.getElementById('trInfosListeAttente'+lstActJS[i][0])){
						document.getElementById('trInfosListeAttente'+lstActJS[i][0]).style.display="none";
					}
					
				}
				
				
				
			}else{
				
				document.getElementById('divPaiement'+lstActJS[i][0]).style.display = "none";
				document.getElementById('fullPaid'+lstActJS[i][0]).checked=false;
				document.getElementById('paid'+lstActJS[i][0]).value="";
				document.getElementById('recu'+lstActJS[i][0]).value="";
				
			}
			
		}
		
		setSommeTotale();
		
	}
}



function putFullPaid(){
	
	var sommePayee = 0;
	
	for(var i=0; i<lstActJS.length; i++){
		if(parseFloat(document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML.replace("€","")) > 0){
			if(document.getElementById('allFullPaid').checked==true){
				
				if(document.getElementById('boxAct'+lstActJS[i][0]).checked==true){
					document.getElementById('fullPaid'+lstActJS[i][0]).checked=true;
					document.getElementById('paid'+lstActJS[i][0]).value=parseFloat(document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML.replace("€",""));
					sommePayee += Number(document.getElementById('paid'+lstActJS[i][0]).value);
				}
				
			}else{
				document.getElementById('fullPaid'+lstActJS[i][0]).checked=false;
				document.getElementById('paid'+lstActJS[i][0]).value="";
			}
		}
	}
	
	
	setSommeTotale();
	
}


function putRecu(){
	for(var i=0; i<lstActJS.length; i++){
		if(parseFloat(document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML.replace("€","")) >0){
			document.getElementById('recu'+lstActJS[i][0]).value=document.getElementById('uniqueRecu').value;
		}
	}
}


function putPrix(idAct){
	
	var sommePayee = 0;

	document.getElementById('allFullPaid').checked=true;

	if(document.getElementById('fullPaid'+idAct).checked==true){
		document.getElementById('paid'+idAct).value=parseFloat(document.getElementById('sommeDue'+idAct).innerHTML.replace("€",""));
		document.getElementById('recu'+idAct).focus();
		

		for(var i=0; i<lstActJS.length; i++){
			if(document.getElementById('boxAct'+lstActJS[i][0]).checked==true && parseFloat(document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML.replace("€",""))>0){
				if(document.getElementById('fullPaid'+lstActJS[i][0]).checked==false){
					document.getElementById('allFullPaid').checked=false;
				}
				sommePayee += Number(document.getElementById('paid'+lstActJS[i][0]).value);
			}
		}

		
	}else{
		document.getElementById('paid'+idAct).value="";
		document.getElementById('paid'+idAct).focus();
		document.getElementById('allFullPaid').checked=false;
	}
	setSommeTotale();

}

function setSommeTotale(){
	var sommeTotale=0;
	for(var i=0; i<lstActJS.length; i++){
		if(parseFloat(document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML.replace("€",""))>0){
			if(!isNaN(document.getElementById('paid'+lstActJS[i][0]).value)){
					sommeTotale += Number(document.getElementById('paid'+lstActJS[i][0]).value);
			}
		}
	}
	document.getElementById('sommePayeeTotale').innerHTML = "Somme totale payée : "+sommeTotale+"€";
	

}





function submInscr(idSubmit){
	var nbAct = 0;
	
	for(var i=0; i<lstActJS.length; i++){
		if(document.getElementById('boxAct'+lstActJS[i][0]).checked==true){
			
			var sommeAct = parseFloat(document.getElementById('sommeDue'+lstActJS[i][0]).innerHTML.replace("€",""));
			
			if(sommeAct > 0){
			
				if(document.getElementById('paid'+lstActJS[i][0]).value > 0 && document.getElementById('recu'+lstActJS[i][0]).value==""){
					alert('Veuillez remplir le champ "'+lstActJS[i][2]+' - Numéro reçu".');
					return;
				}
					
				if(document.getElementById('fullPaid'+lstActJS[i][0]).checked==true){
					if(!isNaN(document.getElementById('paid'+lstActJS[i][0]).value) && Number(document.getElementById('paid'+lstActJS[i][0]).value >= 0)){
						if(Number(document.getElementById('paid'+lstActJS[i][0]).value) > sommeAct){
							if(!confirm('Le prix payé est supérieur au prix de l\'activité "'+lstActJS[i][2]+'". Est-ce normal ?')){
								return;
							}
						}else if(Number(document.getElementById('paid'+lstActJS[i][0]).value) < sommeAct){
							if(!confirm('Le prix payé est inférieur à celui de l\'activité "'+lstActJS[i][2]+'". Est-ce normal ?')){
								return;
							}
						}
					}else{
						alert('La valeur donnée dans le champ "'+lstActJS[i][2]+' - Somme payée" n\'est pas valide.');
						return;
					}
				}else{
					if(!isNaN(document.getElementById('paid'+lstActJS[i][0]).value) && Number(document.getElementById('paid'+lstActJS[i][0]).value >= 0)){
						if(Number(document.getElementById('paid'+lstActJS[i][0]).value) > sommeAct){
							if(!confirm('Le prix payé est supérieur au prix de l\'activité "'+lstActJS[i][2]+'". Est-ce normal ?')){
								return;
							}
						}	
						if(Number(document.getElementById('paid'+lstActJS[i][0]).value) >= sommeAct){
							if(!confirm('La case "Entièrement payé" de l\'activité "'+lstActJS[i][2]+'" n\'est pas cochée. Est-ce normal ?')){
								return;
							}
						}
					}else{
						alert('La valeur donnée dans le champ "'+lstActJS[i][2]+' - Somme payée" n\'est pas valide.');
						return;
					}
				}
			}
			
			//Verif consentements obligatoires
			for(var c=0;c<tabConsentementsObligJS.length;c++){
				if(document.getElementById('caseConsent-'+tabConsentementsObligJS[c]).checked==false){
					alert("Vous devez accepter les clauses obligatoires.");
					return;
				}
			}
			
			
			nbAct++;
		}
	}
	if(nbAct>0 && document.getElementById('idInscr').value!=""){
		//Si on a pas echappé la fonction : go pour inscription !
		document.getElementById(idSubmit).disabled=true;
		document.getElementById(idSubmit).value = "Patientez...";
		document.getElementById(idSubmit).onclick="";
		document.getElementById('formInscr').submit();
	}else{
		alert('Sélectionnez un adhérent et/ou une activité.');
	}
}

</script>

<?php }else{ echo "Pas d'inscriptions en cours.";}?>
<?php
echo $footer;
?>