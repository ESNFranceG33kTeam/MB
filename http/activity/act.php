<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

//VERIF ID ACT
$acces=false;
if(isset($_GET['idAct'])){

	$bd = db_connect();
	
	$_GET['idAct'] = mysqli_real_escape_string($bd, $_GET['idAct']);
	
	
	$act = db_ligne($bd, "SELECT id, nom, dte, tme, infos, spots, spotsSold, spotsResESN, prix, paiementStatut, code, consent
						FROM activity_activities
						WHERE id='".$_GET['idAct']."'");
						
	$tabOptions = db_tableau($bd, "
							SELECT id, opt, prixOpt
							FROM activity_options
							WHERE idAct='".$_GET['idAct']."'
							ORDER BY id ASC");		

	$tabOptionsChoisies = db_tableau($bd, "
						SELECT o.idPart, o.idOpt
						FROM activity_options_participants AS o
						JOIN activity_participants AS part ON o.idPart = part.id
						WHERE part.idAct='".$_GET['idAct']."'
						ORDER BY part.id ASC");		

	$consentements = db_tableau($bd, "		
						SELECT id, obligatoire, defaut, texte, texteCase, titre
						FROM gestion_consentements
						WHERE cible=2 OR cible=3
						ORDER BY id ASC");						
		
	$consentementsAccepted = db_tableau($bd, "		
						SELECT idAdh, idConsent
						FROM gestion_consentements_accepted
						WHERE idAct='".$_GET['idAct']."'
						ORDER BY idConsent ASC");	
	
	db_close($bd);

	if(empty($act) && $act!==false){
		define('TITRE_PAGE','Activité');
		array_push($pageMessages, array('type'=>'err', 'content'=>'Cette activité n\'existe pas.'));
	}elseif($act!==false){
		$acces=true;
	}
}else{ // Pas de code fourni
	define('TITRE_PAGE','Activité');
	array_push($pageMessages, array('type'=>'err', 'content'=>'Cette activité n\'existe pas.'));
}

if($acces){
	define('TITRE_PAGE',$act['nom']);
	
	
	$sort="";
	$order="";
	$reselect="";

	if (isset($_GET['sort']) && isset($_GET['order'])){
		switch($_GET['order']){
			case "asc" : $order="ASC,"; break;	
			case "dsc" : $order="DESC,"; break;	
		}
		if(!(empty($order))){
			switch($_GET['sort']){
				case "paie" : $sort="part.paid "; break;
				case "pays" : $sort="adh.pays "; break;
				case "inscr" : $sort="part.dateInscr "; break;
				default : $order = ""; $sort = "";
			}
		}
	}
	
	//Inscription
	
	if(isset($_POST['idInscr'])&&isset($_POST['typeInscr'])){
		if($_POST['typeInscr']=="Adh" || $_POST['typeInscr']=="ESN"){
			
			//Si erreur on reselectionne la personne et ses options parcequ'on est est sympa
				$reselect="select('".$_POST['typeInscr']."','".$_POST['idInscr']."','".$_POST['iInscr']."','".$_POST['nameInscr']."');";
			
			//Calcul prix total avec options
			$prixTotal = $act['prix'];

			$options = explode('//',$_POST['options'],-1);

			for($opt=0; $opt<count($options); $opt++){
				
				for($lstOpt=0; $lstOpt<count($tabOptions); $lstOpt++){
					
					if($options[$opt] == $tabOptions[$lstOpt]['id']){
						$prixTotal += $tabOptions[$lstOpt]['prixOpt'];
						$reselect .= "selectOpt(".$lstOpt.");";
						break;
					}
				}
			}
		
			
			if($prixTotal < 0){
				array_push($pageMessages, array('type'=>'err', 'content'=>"Le prix d'une activité ne peut pas être négatif."));
				
				
			}elseif($prixTotal > 0){
				//Verifs paiement
				if(!empty($_POST['paid'])){
				
					if(mb_strlen($_POST['paid'])>7){
						array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Somme payée</em> ne doit pas dépasser 7 caractères.'));
					}	
					if (!is_numeric($_POST['paid'])){
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Somme payée</em> n\'est pas valide.'));
					}elseif($_POST['paid']<0){
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Somme payée</em> n\'est pas valide.'));
					}
					
					if($_POST['paid']!=0){
						if(mb_strlen($_POST['recu'])>3){
							array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Numéro reçu</em> ne doit pas dépasser 3 caractères.'));
						}
						if (empty($_POST['recu'])){
							array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Numéro reçu</em>.'));
						}
						elseif (!is_numeric($_POST['recu'])){
							array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Numéro reçu</em> n\'est pas valide.'));
						}elseif($_POST['recu']<0){
							array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Numéro reçu</em> n\'est pas valide.'));
						}
					}
				}
				$paid=(!empty($_POST['paid']))?$_POST['paid']:0;
				$recu=(!empty($_POST['recu']))?$_POST['recu']:0;
				$fullpaid=0;
			}else{
				$paid=0;
				$recu=0;
				$fullpaid=1;
			}
			
			//deja inscrit ?
			$bd = db_connect();
		
			$isInscr = db_ligne($bd, "		
									SELECT idAct
									FROM activity_participants
									WHERE idAct='".$act['id']."' AND id".$_POST['typeInscr']."='".$_POST['idInscr']."'");
		
			db_close($bd);
			if($isInscr!==false){
				if(empty($isInscr)){
				
					//Carte ESN encore valide lors de l'activité ?
					if($_POST['typeInscr']=="Adh"){
					
						$bd = db_connect();
						$dteFinInscr = db_valeur($bd, "		
									SELECT dateFinInscr
									FROM membres_adherents
									WHERE id='".$_POST['idInscr']."'");
						db_close($bd);
						if($dteFinInscr!==false && !empty($dteFinInscr)){
							if(date_create($dteFinInscr) < date_create($act['dte'])){
								array_push($pageMessages,array('type'=>'err', 'content' => "La carte ESN de ".$_POST['nameInscr']." ne sera plus valide au moment de l'activité."));
							}
						}else{
							array_push($pageMessages,array('type'=>'err', 'content' => "La carte ESN de ".$_POST['nameInscr']." ne sera plus valide au moment de l'activité."));
						}
					}									
					
					$bd = db_connect();

					//Récupération du nombre d'inscrits ESN
					$tabNbInscrESN = db_colonne($bd, "		
									SELECT idESN
									FROM activity_participants
									WHERE idAct='".$act['id']."' AND idESN!='' AND fullPaid!='-1' AND listeAttente='0'");

					if($tabNbInscrESN!==false){
						$nbInscrESN = count($tabNbInscrESN);
						$updateSpots=($nbInscrESN>=$act['spotsResESN']||$_POST['typeInscr']=='Adh')?1:0;
						
						$spotsSold = explode('//',$act['spotsSold'],2);	
						
						
						if($act['spots']!=0 && $updateSpots!=0 && (intval($act['spots'])-intval($spotsSold[0]))<= 0){ //On permet aussi l'ajout hors liste d'attente d'ESN si il reste des places reservees
							$attente=1;
							$spotsSold[1] = intval($spotsSold[1])+1;
						}else{
							$attente=0;
							$spotsSold[0] = intval($spotsSold[0])+$updateSpots;
						}
						
						if(isset($_POST['fullPaid']) && $_POST['fullPaid']==true){
							$fullpaid=1;
						}
						
						$tabPaiements = explode('//',$act['paiementStatut'],2);	
						if($fullpaid==0 && $attente==0){//Ajout non payé
							$tabPaiements[0]=intval($tabPaiements[0])+1;
						}elseif($attente==1 && $paid>0){//Ajout d'un remboursement probable
							$tabRemboursement = explode('/',$tabPaiements[1],2);	
							$tabPaiements[1]=$tabRemboursement[0].'/'.(intval($tabRemboursement[1])+1);
						}
					
						
						//Init consentements apres POST pour conserver les choix + Verif consentements obligatoires

						if($consentements!==false && !empty($consentements) && !empty($act['consent']) && $_POST['typeInscr']=="Adh"){
						
							
							$tabConsentAct = explode('///',$act['consent'],-1);
							

							for($i=0; $i<count($consentements); $i++){
								
								if(in_array($consentements[$i]['id'],$tabConsentAct)){
								
								
									if(isset($_POST['caseConsent-'.$consentements[$i]['id']])){
										
										
										$reselect.='document.getElementById("caseConsent-'.$consentements[$i]['id'].'").checked=true;';
										
									}else{
										
										
										$reselect.='document.getElementById("caseConsent-'.$consentements[$i]['id'].'").checked=false;';
										
									}

									if($consentements[$i]['obligatoire'] && !isset($_POST['caseConsent-'.$consentements[$i]['id']])){
										array_push($pageMessages, array('type'=>'err', 'content'=>'L\'adhérent doit accepter les clauses obligatoires.'));
									}
								}
							}
						}	

					
						if(empty($pageMessages)){ //si pas d'erreur : go pour ajout

							$addParticipant = db_exec($bd, "
								INSERT INTO activity_participants(idAct, id".$_POST['typeInscr'].", paid, fullPaid, recu, listeAttente, dateInscr, inscrBy)
								VALUES('".$act['id']."','".$_POST['idInscr']."','".$paid."','".$fullpaid."','".$recu."',
								'".$attente."',NOW(),'".PRENOM." ".NOM."')");
								
							//Ajout des options
							$idPart = db_lastId($bd);
							
							for($opt=0; $opt<count($options); $opt++){
								
								for($lstOpt=0; $lstOpt<count($tabOptions); $lstOpt++){
									
									if($options[$opt] == $tabOptions[$lstOpt]['id']){
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
						
							for($i=0; $i<count($consentements); $i++){
								if(isset($_POST['caseConsent-'.$consentements[$i]['id']])){
									
									$addConsent = db_exec($bd, "
											INSERT INTO gestion_consentements_accepted(idAdh, idConsent, idAct)
											VALUES(".$_POST['idInscr'].",".$consentements[$i]['id'].",".$act['id'].")
											ON DUPLICATE KEY UPDATE idAdh=idAdh");

									if($addConsent===false){die("Erreur ajout consentement.");}
								}
							}
							
					
							if($addParticipant!==false){

								$updateActivity = db_exec($bd, "
									UPDATE activity_activities
									SET spotsSold='".$spotsSold[0]."//".$spotsSold[1]."', paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
									WHERE id='".$act['id']."'");

								if($updateActivity!==false){
									array_push($pageMessages,array('type'=>'ok', 'content' => $_POST['nameInscr']." a bien été inscrit."));
									//Ajout caisse
									if($paid>0){
										
										$complementDescr = "";
										
										
										if(!$fullPaid && $paid<$prixTotal){
											$complementDescr = " (Paiement incomplet)";
										
										}elseif($paid>$prixTotal){
											$complementDescr = " (Paiement excédentaire)";
										
										}elseif($act['prix'] != $prixTotal){
											$complementDescr = " (Options : ".($prixTotal - $act['prix'])."€)";
										}
										
										
										$descrCaisse = $act['nom']." : Inscription de ".$_POST['nameInscr'].$complementDescr;
										addCaisse($descrCaisse, $paid, $recu, 'local', $act['id']);
									}
									$reselect="";
				
								}							
							}					
						}
					}
					db_close($bd);
				}else{
					$reselect="";
					array_push($pageMessages, array('type'=>'err', 'content'=>'La personne est déjà inscrite.'));
				}			
			}
		}else{
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le type de membre n\'est pas valide.'));
		}
	}
	
	//EDITION
	if(isset($_POST['idEditPaid'])){
		
		
		//Calcul prix total avec options
			$prixTotal = $act['prix'];

			$options = explode('//',$_POST['optionsEdit'],-1);

			for($opt=0; $opt<count($options); $opt++){
				
				for($lstOpt=0; $lstOpt<count($tabOptions); $lstOpt++){
					
					if($options[$opt] == $tabOptions[$lstOpt]['id']){
						$prixTotal += $tabOptions[$lstOpt]['prixOpt'];
						break;
					}
				}
			}
		
		
		
		if($prixTotal < 0){
			array_push($pageMessages, array('type'=>'err', 'content'=>"Le prix d'une activité ne peut pas être négatif."));
				
				
		}elseif($prixTotal > 0){
			
			if(!empty($_POST['paidEdit'])){
		
				if(mb_strlen($_POST['paidEdit'])>7){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Somme payée</em> ne doit pas dépasser 7 caractères.'));
				}	
				if (!is_numeric($_POST['paidEdit'])){
					array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Somme payée</em> n\'est pas valide.'));
				}elseif($_POST['paidEdit']<0){
					array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Somme payée</em> n\'est pas valide.'));
				}
				
				if($_POST['paidEdit']!=0){
					if(mb_strlen($_POST['recuEdit'])>3){
						array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Numéro reçu</em> ne doit pas dépasser 3 caractères.'));
					}	
					if (empty($_POST['recuEdit'])){
						array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Numéro reçu</em>.'));
					}elseif (!is_numeric($_POST['recuEdit'])){
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Numéro reçu</em> n\'est pas valide.'));
					}elseif($_POST['recuEdit']<0){
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Numéro reçu</em> n\'est pas valide.'));
					}
				}
			}
			$paid=(!empty($_POST['paidEdit']))?$_POST['paidEdit']:0;
			$recu=(!empty($_POST['recuEdit']))?$_POST['recuEdit']:0;
			$fullpaid= (isset($_POST['fullPaidEdit']) && $_POST['fullPaidEdit']==true)?1:0;
		
		}else{
			$paid=0;
			$recu=0;
			$fullpaid=1;
		}
		
		
		if(empty($pageMessages)){ //si pas d'erreur : go pour modifs
		
			//Récupération état initial
			$bd = db_connect();
			
			$exPaid = db_ligne($bd, "
				SELECT fullPaid, paid, listeAttente, idAdh
				FROM activity_participants
				WHERE id='".$_POST['idEditPaid']."'");
			
			if($exPaid!==false){
			
				$tabPaiements = explode('//',$act['paiementStatut'],2);	
			
				if($exPaid['fullPaid']!=$fullpaid && $exPaid['listeAttente']==0){
					$tabPaiements[0]=($fullpaid==1)?intval($tabPaiements[0])-1:intval($tabPaiements[0])+1;
				
					$updateActivity = db_exec($bd, "
							UPDATE activity_activities
							SET paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
							WHERE id='".$act['id']."'");
							
							
				}elseif($exPaid['listeAttente']==1 && $exPaid['paid']!=$paid){
					$tabRemboursement = explode('/',$tabPaiements[1],2);
				
					if($exPaid['paid']==0 && $paid>0){ //Ajout d'un remboursement probable
						$tabPaiements[1]=$tabRemboursement[0].'/'.(intval($tabRemboursement[1])+1);
					
					}elseif(intval($exPaid['paid'])>0 && $paid==0){ //Suppression d'un remboursement probable
						$tabPaiements[1]=$tabRemboursement[0].'/'.(intval($tabRemboursement[1])-1);
					
					}
					$updateActivity = db_exec($bd, "
							UPDATE activity_activities
							SET paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
							WHERE id='".$act['id']."'");
	

				}
				
				$updateParticipant = db_exec($bd, "
					UPDATE activity_participants
					SET fullPaid='".$fullpaid."', paid='".$paid."', recu='".$recu."'
					WHERE id='".$_POST['idEditPaid']."'");
					
				
				//Modif options (suppression des anciennes et ajout des nouvelles)
					
				$supOptions = db_exec($bd, "
					DELETE FROM activity_options_participants
					WHERE idPart='".$_POST['idEditPaid']."'");			

	
				for($opt=0; $opt<count($options); $opt++){
					
					for($lstOpt=0; $lstOpt<count($tabOptions); $lstOpt++){
						
						if($options[$opt] == $tabOptions[$lstOpt]['id']){
							$addOpt	= db_exec($bd, "
											INSERT INTO activity_options_participants(idPart, idOpt) 
											VALUES(".$_POST['idEditPaid'].",".$tabOptions[$lstOpt]['id'].")");
						
							if($addOpt === false){
								die("Erreur ajout option");
							}
						}
					}
				}
					
					
				//MAJ consentements
				
				for($i=0; $i<count($consentements); $i++){
					if(isset($_POST['caseConsentEdit-'.$consentements[$i]['id']])){
						
						$addConsent = db_exec($bd, "
								INSERT INTO gestion_consentements_accepted(idAdh, idConsent, idAct)
								VALUES(".$exPaid['idAdh'].",".$consentements[$i]['id'].",".$act['id'].")
								ON DUPLICATE KEY UPDATE idAdh=idAdh");

						if($addConsent===false){die("Erreur ajout consentement.");}
						
						
					
					}else{ //suppression si pas obligatoire
						
						if(!$consentements[$i]['obligatoire']){
							
							$supConsent = db_exec($bd, "DELETE FROM gestion_consentements_accepted
														WHERE idConsent='".$consentements[$i]['id']."' AND idAdh='".$exPaid['idAdh']."' AND idAct='".$act['id']."'
														LIMIT 1");
							
							if($supConsent===false){die("Erreur suppression consentement.");}
						}
						
					}
				}

				if($updateParticipant!==false){
					array_push($pageMessages,array('type'=>'ok', 'content' =>"L'édition de l'inscription de ".$_POST['nameEditPaid']." a bien été prise en compte."));
					
					//Modif caisse
					if($paid>=0 && $exPaid['paid']!=$paid){
						$descrCaisse = $act['nom']." : Modification du paiement de ".$_POST['nameEditPaid'];
						addCaisse($descrCaisse, $paid-$exPaid['paid'], $recu, 'local', $act['id']);
					}
				}
			}
			db_close($bd);
		}
	}

	//Inscription liste attente -> liste principale
	if(isset($_POST['idInscrAttente'])){
	
		//Verif droits
		requireDroits("membre");


		$bd = db_connect();
		$tabPaiements = explode('//',$act['paiementStatut'],2);	
		
		$paid = db_ligne($bd, "
				SELECT idAdh, fullPaid, paid, listeAttente
				FROM activity_participants
				WHERE id='".$_POST['idInscrAttente']."'");
		
		if($paid!==false){
		
			//verif si la page n'a pas été reactualisée
			if($paid['listeAttente']==1){
			
			
				$type=($paid['idAdh']!='')?'Adh':'ESN';
						
				// Check paiement
				

				if($paid['fullPaid']==0){//Si non payé, on l'enregistre dans la bdd
					$tabPaiements[0]=intval($tabPaiements[0])+1;			
				}
				if(intval($paid['paid'])>0){//Suppression du remboursement probable, vu que la personne participe finalement a l'activité
					$tabRemboursement = explode('/',$tabPaiements[1],2);
					$tabPaiements[1]=$tabRemboursement[0].'/'.(intval($tabRemboursement[1])-1);
				}

					//MAJ spots
				//Récupération du nombre d'inscrits ESN
				$tabNbInscrESN = db_colonne($bd, "		
							SELECT idESN
							FROM activity_participants
							WHERE idAct='".$act['id']."' AND idESN!='' AND fullPaid!='-1' AND listeAttente='0'");

			
				if($tabNbInscrESN!==false && $paid!==false){
				
					$nbInscrESN = count($tabNbInscrESN);
					$updateSpots=($nbInscrESN>=$act['spotsResESN']||$type=='Adh')?1:0;
					
					$spotsSold = explode('//',$act['spotsSold'],2);	
					$spotsSold[0]=intval($spotsSold[0])+$updateSpots;
					$spotsSold[1]=intval($spotsSold[1])-1;
					
					$inscrAtt = db_exec($bd, "
						UPDATE activity_participants
						SET listeAttente='0'
						WHERE id='".$_POST['idInscrAttente']."'");	
						
						
					$updateActivity = db_exec($bd, "
						UPDATE activity_activities
						SET spotsSold='".$spotsSold[0]."//".$spotsSold[1]."', paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
						WHERE id='".$act['id']."'");
					
				
					if($inscrAtt!==false && $updateActivity!==false){
						array_push($pageMessages,array('type'=>'ok', 'content' => $_POST['nameInscrAttente']." a bien été inscrit sur la liste des participants."));
					}
				}
			}//verif reactualisation malencontreuse
		}
		db_close($bd);	
	}
	
	
	//Remboursement
	if(isset($_POST['idRembours'])){
	
		//Verif droits
		requireDroits("membre");
	
		//Récupération état initial
		$bd = db_connect();
		
		$exPaid = db_ligne($bd, "
			SELECT fullPaid, paid, listeAttente
			FROM activity_participants
			WHERE id='".$_POST['idRembours']."'");	
			
		$tabPaiements = explode('//',$act['paiementStatut'],2);	
		$tabRemboursement = explode('/',$tabPaiements[1],2);
		
		//Verif pas eu de reactualisation de la page
		if($exPaid !== false && !empty($exPaid) && $exPaid['paid']>0){
		
			//Soit liste d'attente : on conserve la personne dans la base(pour conserver les stats nb d'inscrits), soit desistement : on supprime la personne
			if($exPaid['fullPaid']==-1 && $exPaid['listeAttente']==0){
				$tabPaiements[1]=(intval($tabRemboursement[0])-1).'/'.$tabRemboursement[1];
			
				$updateActivity = db_exec($bd, "
								UPDATE activity_activities
								SET paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
								WHERE id='".$act['id']."'");
				
				$updateRemb = db_exec($bd, "
								DELETE FROM activity_participants
								WHERE id='".$_POST['idRembours']."'
								LIMIT 1");
			
			
			}elseif($exPaid['listeAttente']==1){
				$tabPaiements[1]=$tabRemboursement[0].'/'.(intval($tabRemboursement[1])-1);
				
				$updateActivity = db_exec($bd, "
								UPDATE activity_activities
								SET paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
								WHERE id='".$act['id']."'");
				
				$updateRemb = db_exec($bd, "
								UPDATE activity_participants
								SET fullPaid='0', paid='0'
								WHERE id='".$_POST['idRembours']."'");	
			}
		
			if($exPaid!==false && $updateRemb!==false && $updateActivity!==false){
				array_push($pageMessages,array('type'=>'ok', 'content' => "Le remboursement de ".$_POST['nameRembours']." a bien été validé."));
				//Ajout caisse
				$descrCaisse = $act['nom']." : Remboursement de ".$_POST['nameRembours'];
				addCaisse($descrCaisse, -1*$exPaid['paid'], 0, 'local', $act['id']);
			}
		}
		db_close($bd);	
	}
	
	
	//DESISTEMENT
	if(isset($_POST['idDesist'])){
	
		//Verif droits
		requireDroits("membre");
	
		//Récupération état initial
		$bd = db_connect();
		
		$exPaid = db_ligne($bd, "
			SELECT id, idAdh, fullPaid, paid, listeAttente, idAdh
			FROM activity_participants
			WHERE id='".$_POST['idDesist']."'");
		
		//verif pas eu de reactualisation de la page
		if($exPaid !== false && !empty($exPaid) && $exPaid['fullPaid']!=-1){
		
			$type=($exPaid['idAdh']!='')?'Adh':'ESN';

			//Mise a jour places vendues
				
				//Récupération du nombre d'inscrits ESN
			$tabNbInscrESN = db_colonne($bd, "		
							SELECT idEsn
							FROM activity_participants
							WHERE idAct='".$act['id']."' AND idESN!='' AND fullPaid!='-1' AND listeAttente='0'");

			if($tabNbInscrESN!==false){
				$nbInscrESN = count($tabNbInscrESN);
				$updateSpots=($nbInscrESN>$act['spotsResESN']||$type=='Adh')?1:0;
			}
			$spotsSold = explode('//',$act['spotsSold'],2);
			if($exPaid['listeAttente']==0){
				$spotsSold[0]=intval($spotsSold[0])-$updateSpots;
			}elseif($exPaid['listeAttente']==1){
				$spotsSold[1]=intval($spotsSold[1])-1;
			}
				
			//mise a jour paiementStatuts (nb non payé/remboursement)
			$tabPaiements = explode('//',$act['paiementStatut'],2);	
			$tabRemboursement = explode('/',$tabPaiements[1],2);
			
			if($exPaid['listeAttente']==0 && $exPaid['fullPaid']==0){
				$tabPaiements[0]=intval($tabPaiements[0])-1; //Un "non-payé" en moins
			}
			if(intval($exPaid['paid'])>0){ 
				if($exPaid['listeAttente']==0){
					$tabPaiements[1]=(intval($tabRemboursement[0])+1).'/'.$tabRemboursement[1]; //Un remboursement en plus
				}elseif($exPaid['listeAttente']==1){
					$tabPaiements[1]=(intval($tabRemboursement[0])+1).'/'.(intval($tabRemboursement[1])-1); //Un remboursement en plus et un remboursement probable en moins
				}
			}
		
			$updateActivity = db_exec($bd, "
						UPDATE activity_activities
						SET spotsSold='".$spotsSold[0]."//".$spotsSold[1]."', paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
						WHERE id='".$act['id']."'");
						
						
			//Suppression association des options	
			$supOptions = db_exec($bd, "
				DELETE FROM activity_options_participants
				WHERE idPart='".$exPaid['id']."'");			

				
			//supression consentements
				
			for($i=0; $i<count($consentements); $i++){

				$supConsent = db_exec($bd, "DELETE FROM gestion_consentements_accepted
											WHERE idConsent='".$consentements[$i]['id']."' AND idAdh='".$exPaid['idAdh']."' AND idAct='".$act['id']."'
											LIMIT 1");
				
				if($supConsent===false){die("Erreur suppression consentement.");}
			}

			
			
			
			if($updateActivity!==false && $supOptions!==false){
			
			
				if(intval($exPaid['paid'])>0){//Soit mise dans la liste remboursement, soit suppression directe
				
					$desist = db_exec($bd, "
						UPDATE activity_participants
						SET fullPaid='-1', listeAttente='0'
						WHERE id='".$_POST['idDesist']."'");
				
				}else{
				
					$desist = db_exec($bd, "
							DELETE FROM activity_participants
							WHERE id='".$_POST['idDesist']."'
							LIMIT 1");
				}
			
				if($desist!==false){
					array_push($pageMessages,array('type'=>'ok', 'content' => "Le désistement de ".$_POST['nameDesist']." a bien été validé."));
				}
			}
		}
	db_close($bd);	
	}	
	
	
 	//Actualisation infos activités apres modifs par POST
	if(count($_POST)>0){
		$bd = db_connect();
		$act = db_ligne($bd, "
			SELECT id, nom, dte, tme, infos, spots, spotsSold, spotsResESN, prix, paiementStatut, code, consent
			FROM activity_activities
			WHERE id='".$_GET['idAct']."'");
		
	
		$tabOptionsChoisies = db_tableau($bd, "
					SELECT o.idPart, o.idOpt
					FROM activity_options_participants AS o
					JOIN activity_participants AS part ON o.idPart = part.id
					WHERE part.idAct='".$_GET['idAct']."'
					ORDER BY part.id ASC");							
	
		$consentementsAccepted = db_tableau($bd, "		
					SELECT idAdh, idConsent
					FROM gestion_consentements_accepted
					WHERE idAct='".$_GET['idAct']."'
					ORDER BY idConsent ASC");
		
		db_close($bd);
		
	} 
	

	$dte = explode('-',$act['dte'],3);
	$mois = Array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
	$dateAct = (($dte[2]{0}=="0")?$dte[2]{1}:$dte[2]).' '.$mois[intval($dte[1])-1].' '.$dte[0];
	if(!empty($act['tme'])){
		$dateAct .= '<br /><span style="font-size:0.85em">'.$act['tme'].'</span>';
	}
	if(!empty($act['code'])){
		$inscrLibre = '<h3>Inscriptions via Internet</h3><div class="blocText"> Lien à partager : <a href="http://'.$_SERVER['HTTP_HOST'].'/inscrAct-'.$act['code'].'">http://'.$_SERVER['HTTP_HOST'].'/inscrAct-'.$act['code'].'</a></div>';
	}else{
		$inscrLibre = null;
	}
	
	//Etat paiements
	
		$paie = explode('//',$act['paiementStatut'],2);		
		$remb = explode('/',$paie[1],2);
		$nbRemb = (date_add(date_create($act['dte']), date_interval_create_from_date_string('1 day'))>date_create('now'))?intval($remb[0]):(intval($remb[0])+intval($remb[1]));
		
		if($paie[0]==0 && $nbRemb==0){
			$paieStat="";
		}else{
			$paieStat = '<div style="float:right; font-weight:bold;color: orangered;">';
			if($paie[0]!=0 && $nbRemb==0){
				$paieStat.="Non payé : ".$paie[0];
			}elseif($paie[0]==0 && $nbRemb!=0){
				$paieStat.="A rembourser : ".$nbRemb;
			}elseif($paie[0]!=0 && $nbRemb!=0){
				$paieStat.="Non payé : ".$paie[0]." &nbsp; | &nbsp; A rembourser : ".$nbRemb;
			}	
			$paieStat .= '</div>';
		}
	
	
	
	
	
	//Récupération liste membres
	$bd = db_connect();

	$tabAdh = db_tableau($bd, "
						SELECT adh.id, adh.idesn, adh.prenom, adh.nom, adh.pays, adh.divers
						FROM membres_adherents AS adh
						WHERE NOT EXISTS(	SELECT 0
											FROM activity_participants
											WHERE idAct=".$act['id']." AND idAdh = adh.id)
						ORDER BY adh.prenom ASC, adh.nom ASC
						");
	$tabESN = db_tableau($bd, "
						SELECT ben.id, ben.prenom, ben.nom
						FROM membres_benevoles AS ben
						WHERE NOT EXISTS(	SELECT 0
											FROM activity_participants
											WHERE idAct=".$act['id']." AND idESN = ben.id)
						ORDER BY ben.prenom ASC, ben.nom ASC");
						
	db_close($bd);
	
	$lstAdh="";
	$lstAdhJS="";
	$lstESN="";
	$lstESNJS="";
	
	if($tabAdh!==false&&$tabESN!==false){

		for($i=0; $i<count($tabAdh); $i++){
			
			$lstAdh.='<tr id="lineAdh'.$i.'" style="display:none"><td class="gras">'.$tabAdh[$i]['prenom'].' '.$tabAdh[$i]['nom'].'</td>
						<td>'.$tabAdh[$i]['pays'].'</td><td>'.$tabAdh[$i]['idesn'].'</td>
						<td id="cellAdh'.$i.'" class="add" onclick="select(\'Adh\','.$tabAdh[$i]['id'].','.$i.',\''.str_replace("'","\'", $tabAdh[$i]['prenom']).' '.str_replace("'","\'", $tabAdh[$i]['nom']).'\')"></td></tr>';
						
			$lstAdhJS.= 'lstAdhJS['.$i.']=new Array("'.strtolower($tabAdh[$i][2]).'","'.strtolower($tabAdh[$i][3]).'","'.strtolower($tabAdh[$i][1]).'","'.str_replace('"','\"', str_replace(array("\r\n", "\r", "\n"), '<br />', $tabAdh[$i]['divers'])).'");';
			
		}
		for($i=0; $i<count($tabESN); $i++){
			
			$lstESN.='<tr id="lineESN'.$i.'" style="display:none"><td class="gras">'.$tabESN[$i]['prenom'].' '.$tabESN[$i]['nom'].'</td>
						<td id="cellESN'.$i.'" class="add" onclick="select(\'ESN\','.$tabESN[$i]['id'].','.$i.',\''.str_replace("'","\'", $tabESN[$i]['prenom']).' '.str_replace("'","\'", $tabESN[$i]['nom']).'\')"></td></tr>';
						
			$lstESNJS.= 'lstESNJS['.$i.']=new Array("'.strtolower($tabESN[$i][1]).'","'.strtolower($tabESN[$i][2]).'");';
			
		}
	}
	
	
	//Récupération Liste Inscrits
	
	$bd = db_connect();
	
	$lstInscrESN = db_tableau($bd, "		
						SELECT ben.prenom, ben.nom, ben.mail, ben.tel, part.id, part.paid, part.fullPaid, part.recu, part.listeAttente, part.dateInscr, part.inscrBy
						FROM activity_participants AS part
						LEFT JOIN membres_benevoles AS ben ON part.idESN = ben.id
						WHERE part.idAct='".$_GET['idAct']."' AND part.idESN IS NOT NULL
						ORDER BY ben.prenom ASC, ben.nom ASC");
	$lstInscrAdh = db_tableau($bd, "								
						SELECT adh.prenom, adh.nom, adh.email, adh.tel, adh.pays, part.id, part.paid, part.fullPaid, part.recu, part.listeAttente, part.dateInscr, part.inscrBy, adh.id AS idAdh
						FROM activity_participants AS part
						LEFT JOIN membres_adherents AS adh ON part.idAdh = adh.id
						WHERE part.idAct='".$_GET['idAct']."' AND part.idAdh IS NOT NULL
						ORDER BY ".$sort.$order." adh.prenom ASC, adh.nom ASC");
	db_close($bd);

	$tabInscrESN = "";
	$tabInscrAdh = "";
	$lstInscrESNJS = "";
	$lstInscrAdhJS = "";
	$tempOptionsJS = "";
	$tempConsentJS = "";
	$lstAttente = array();
	$tabRemboursement = "";
	$comptInscritsESN = 0;
	$comptInscritsAdh = 0;
	$comptResteResaESN = 0;
	$isESNenAttente = false;
	$listeOptions = "";
	$tableOptions = "";
	$tableOptionsEdit = "";
	$comptConsent = array();
	$sommeEvent = 0;
				

	$actFullFree = ($act['prix']!=0)?false:true;
	
	
	if($actFullFree){
		//Test si options payantes
		for($o=0; $o<count($tabOptions); $o++){
			
			if($tabOptions[$o]['prixOpt']>0){
				$actFullFree = false;
				break;
			}
		}
	}

	
	//Ajout colonne nb pour chaque options
	for($o=0; $o<count($tabOptions); $o++){
		$tabOptions[$o]['nb'] = 0;
		$tabOptions[$o]['nbLA'] = 0;
	}
	
	
	//Consentements
	if($consentements!==false && !empty($consentements) && !empty($act['consent'])){
		$tabConsentAct = explode('///',$act['consent'],-1);
		
		for($c=0; $c<count($tabConsentAct); $c++){
			$comptConsent[$tabConsentAct[$c]]= 0;
		}
	}
	
	
	$spotsSold = explode('//',$act['spotsSold'],2);						
						
	//reste des places ?
	$isPlacesDispo = ($act['spots']==0 || ($act['spots']!=0 && (intval($act['spots'])-intval($spotsSold[0]))>0))?true:false;
	
	//reste des places reservees?
	for($i=0; $i<count($lstInscrESN); $i++){
		if($lstInscrESN[$i]['fullPaid']!=-1 && $lstInscrESN[$i]['listeAttente']==0){							
			$comptInscritsESN++;
		}if($lstInscrESN[$i]['listeAttente']==1){
			$isESNenAttente = true;		
		}
	}
	$isPlacesDispoESN = ($act['spots']==0 || ($act['spots']!=0 && intval($act['spotsResESN'])>$comptInscritsESN))?true:false;

	if($lstInscrESN!==false && $lstInscrAdh!==false){
		
		//INSCRIPTIONS BENEVOLES
		for($i=0; $i<count($lstInscrESN); $i++){
			
			$tempOptionsJS = "";
			$tempSommeOptions = 0;
			
			if(count($tabOptions) > 0){
				//Recup des options
				$arrayOpt = array();
				
				for($opti=0; $opti<count($tabOptionsChoisies); $opti++){
					
					if($tabOptionsChoisies[$opti]['idPart'] == $lstInscrESN[$i]['id']){
						
						//on cherche le numero de l'option
						for($o=0; $o<count($tabOptions); $o++){
							if($tabOptions[$o]['id'] == $tabOptionsChoisies[$opti]['idOpt']){
								
								array_push($arrayOpt, ($o+1));
								
								$tempSommeOptions += $tabOptions[$o]['prixOpt'];
								
								if($lstInscrESN[$i]['listeAttente']==0){
									$tabOptions[$o]['nb'] ++;
								}else{
									$tabOptions[$o]['nbLA'] ++;
								}
								
								$tempOptionsJS .= $o."//";
								break;
							}
						}
					}
				}
				//Mise en forme texte options
				sort($arrayOpt);
				
				if(count($arrayOpt) == 0){
					
					$textOpt = "Sans options";
					
				}elseif(count($arrayOpt) == 1){
					
					$textOpt = "Option : ".$arrayOpt[0];
					
				}else{
					
					$textOpt = "Options : ";
					
					for($txtO = 0; $txtO < count($arrayOpt); $txtO++){
						
						$textOpt .= $arrayOpt[$txtO]. ", ";
					}
					$textOpt = substr($textOpt, 0 , -2);
				}
				
				
			}else{
				$textOpt = "";
			}
			

	
			if(!$actFullFree){
				if($lstInscrESN[$i]['fullPaid']==0){
					
					if($lstInscrESN[$i]['paid']==0){
						$tdPaye='<td style="width:110px; font-weight:bold;color: orangered;">Non payé</td>';
					}else{
						$tdPaye='<td style="width:110px; font-weight:bold;color: orangered;">'.$lstInscrESN[$i]['paid'].'€<br /><div style="font-size:0.8em; line-height:1.15em">Reçu n°'.$lstInscrESN[$i]['recu'].'</div></td>';
					}
					
				}else{	
				
					if($lstInscrESN[$i]['paid']==0){
						
						if(($act['prix'] + $tempSommeOptions) == 0){
							$tdPaye='<td style="width:110px">Gratuit</td>';
						}else{
							$tdPaye='<td style="width:110px">OK (0€)</td>';
						}
						
					}else{
						$tdPaye='<td style="width:110px">OK ('.$lstInscrESN[$i]['paid'].'€)<br /><div style="font-size:0.8em; line-height:1.15em">Reçu n°'.$lstInscrESN[$i]['recu'].'</div></td>';
					}
				}
			

			}else{
				$tdPaye="";
			}
				
			$dtetmeInscr = explode(' ',$lstInscrESN[$i]['dateInscr'],2);
			$dteInscr = explode('-',$dtetmeInscr[0],3);
			$dateInscr = $dteInscr[2].'/'.$dteInscr[1].'/'.$dteInscr[0].' '.$dtetmeInscr[1];
			
			$tdEdit = '<td class="edit" onclick="editPaid(\'ESN\','.$i.','.$lstInscrESN[$i]['id'].',\''.str_replace("'","\'", $lstInscrESN[$i]['prenom']).' '.str_replace("'","\'", $lstInscrESN[$i]['nom']).'\','.($lstInscrESN[$i]['listeAttente']).')"></td>';
			
			
			if($lstInscrESN[$i]['listeAttente']==0){
			
				//Personne qui s'est désisté et en attente de remboursement ?
				if($lstInscrESN[$i]['fullPaid']==-1){
					
					$tabRemboursement.='<tr><td class="gras">'.$lstInscrESN[$i]['prenom'].' '.$lstInscrESN[$i]['nom'].'</td>
									<td style="font-size:0.9em">Tel : '.$lstInscrESN[$i]['tel'].'<br /><div class="hidden-inline" style="width:180px; font-size:0.8em; line-height:1.15em;"><a href="mailto:'.$lstInscrESN[$i]['mail'].'">'.$lstInscrESN[$i]['mail'].'</a></div></td>
									<td>'.$lstInscrESN[$i]['paid'].'€<br /><div style="font-size:0.8em; line-height:1.15em">Reçu n°'.$lstInscrESN[$i]['recu'].'</div></td>
									<td id="cellRembours'.$lstInscrESN[$i]['id'].'" class="tick" onclick="submRembours('.$lstInscrESN[$i]['id'].',\''.str_replace("'","\'", $lstInscrESN[$i]['prenom']).' '.str_replace("'","\'", $lstInscrESN[$i]['nom']).'\')"></td>
									</tr>';
				}else{
					if(!empty($lstInscrESN[$i]['nom'])){ 
						$tabInscrESN.='<tr><td class="gras">'.$lstInscrESN[$i]['prenom'].' '.$lstInscrESN[$i]['nom'].(!empty($textOpt)?'<br/><div style="float:right; font-size:0.8em; line-height:1.15em; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
									<td style="font-size:0.9em">Tel : '.chunk_split($lstInscrESN[$i]['tel'], 2, " ").'<br /><div class="hidden-inline" style="width:180px; font-size:0.8em; line-height:1.15em;"><a href="mailto:'.$lstInscrESN[$i]['mail'].'">'.$lstInscrESN[$i]['mail'].'</a></div></td>
									'.$tdPaye.'
									<td style="font-size:0.9em">'.$dateInscr.'<br /><div class="hidden-inline" style="width:185px" font-size:0.8em; line-height:1.15em;">Par : '.$lstInscrESN[$i]['inscrBy'].'</div></td>
									'.$tdEdit.'
									<td id="cellDesist'.$lstInscrESN[$i]['id'].'" class="suppr" onclick="submDesist('.$lstInscrESN[$i]['id'].',\''.str_replace("'","\'", $lstInscrESN[$i]['prenom']).' '.str_replace("'","\'", $lstInscrESN[$i]['nom']).'\')" title="Il est possible de désinscrire une personne après un désistement afin de libérer sa place.&#10;Si la personne avait déjà payé, elle sera placée dans une liste spéciale en attendant son remboursement."></td>
									</tr>';	
					}else{ //si bénévole suppr de la bdd
						$tabInscrESN.='<tr><td class="gras" colspan=2>Ce membre ne fait plus partie de la base de données'.(!empty($textOpt)?'<br/><div style="float:right; font-size:0.8em; line-height:1.15em; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
								'.$tdPaye.'
								<td style="font-size:0.9em">'.$dateInscr.'<br /><div class="hidden-inline" style="width:185px" font-size:0.8em; line-height:1.15em;">Par : '.$lstInscrESN[$i]['inscrBy'].'</div></td>
								</tr>';
					
					}
			
					$sommeEvent += $lstInscrESN[$i]['paid'];
				}

				
			}else{ //Mise en liste d'attente
			
				//Verif activité passée pour remboursement membres en liste d'attente
				if(date_add(date_create($act['dte']), date_interval_create_from_date_string('1 day'))<date_create('now')&&$lstInscrESN[$i]['paid']>0){
			
				$tabRemboursement.='<tr><td class="gras">'.$lstInscrESN[$i]['prenom'].' '.$lstInscrESN[$i]['nom'].'</td>
									<td style="font-size:0.9em">Tel : '.$lstInscrESN[$i]['tel'].'<br /><div class="hidden-inline" style="width:180px; font-size:0.8em; line-height:1.15em;"><a href="mailto:'.$lstInscrESN[$i]['mail'].'">'.$lstInscrESN[$i]['mail'].'</a></div></td>
									<td>'.$lstInscrESN[$i]['paid'].'€<br /><div style="font-size:0.8em; line-height:1.15em">Reçu n°'.$lstInscrESN[$i]['recu'].'</div></td>
									<td id="cellRembours'.$lstInscrESN[$i]['id'].'" class="tick" onclick="submRembours('.$lstInscrESN[$i]['id'].',\''.str_replace("'","\'", $lstInscrESN[$i]['prenom']).' '.str_replace("'","\'", $lstInscrESN[$i]['nom']).'\')"></td>
									</tr>';
				}
				
				if(!empty($lstInscrESN[$i]['nom'])){ 
					$tabAtt='<tr class="grisé"><td class="gras">'.$lstInscrESN[$i]['prenom'].' '.$lstInscrESN[$i]['nom'].(!empty($textOpt)?'<br/><div style="float:right; font-size:0.8em; line-height:1.15em; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
							<td style="font-size:0.9em">Tel : '.chunk_split($lstInscrESN[$i]['tel'], 2, " ").'<br /><div class="hidden-inline" style="width:180px; font-size:0.8em; line-height:1.15em;"><a href="mailto:'.$lstInscrESN[$i]['mail'].'">'.$lstInscrESN[$i]['mail'].'</a></div></td>
							'.$tdPaye.'
							<td style="font-size:0.9em">'.$dateInscr.'<br /><div class="hidden-inline" style="width:'.(($isPlacesDispo||$isPlacesDispoESN)?160:185).'px" font-size:0.8em; line-height:1.15em;">Par : '.$lstInscrESN[$i]['inscrBy'].'</div></td>
							'.(($isPlacesDispo||$isPlacesDispoESN)?"<td id=\"cellInscrAttente".$lstInscrESN[$i]['id']."\" class=\"add\" onclick=\"submInscrAttente(".$lstInscrESN[$i]['id'].",'".str_replace("'","\'", $lstInscrESN[$i]['prenom']).' '.str_replace("'","\'", $lstInscrESN[$i]['nom'])."')\"></td>":"").'
							'.$tdEdit.'
							<td id="cellDesist'.$lstInscrESN[$i]['id'].'" class="suppr" onclick="submDesist('.$lstInscrESN[$i]['id'].',\''.str_replace("'","\'", $lstInscrESN[$i]['prenom']).' '.str_replace("'","\'", $lstInscrESN[$i]['nom']).'\')" title="Il est possible de désinscrire une personne après un désistement afin de libérer sa place.&#10;Si la personne avait déjà payé, elle sera placée dans une liste spéciale en attendant son remboursement."></td>
							</tr>';	
					
					
				}else{ //si bénévole suppr de la bdd
					$tabAtt='<tr class="grisé"><td class="gras" colspan=2>Ce membre ne fait plus partie de la base de données'.(!empty($textOpt)?'<br/><div style="float:right; font-size:0.8em; line-height:1.15em; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
							'.$tdPaye.'
							<td style="font-size:0.9em">'.$dateInscr.'<br /><div class="hidden-inline" style="width:185px" font-size:0.8em; line-height:1.15em;">Par : '.$lstInscrESN[$i]['inscrBy'].'</div></td>
							'.(($isPlacesDispo||$isPlacesDispoESN)?"<td></td>":"").'</tr>';
					
				}	
					
				array_push($lstAttente, array($lstInscrESN[$i]['dateInscr'],$tabAtt));

			}
			$lstInscrESNJS.= 'lstInscrESNJS['.$i.']=new Array("'.$lstInscrESN[$i]['prenom'].' '.$lstInscrESN[$i]['nom'].'","'.$lstInscrESN[$i]['fullPaid'].'","'.$lstInscrESN[$i]['paid'].'","'.$lstInscrESN[$i]['recu'].'","'.$tempOptionsJS.'");';

		}
			
		for($i=$comptInscritsESN; $i<$act['spotsResESN']; $i++){
			$tabInscrESN.='<tr><td colspan=6>Place réservée pour un bénévole ESN.</td><tr>';
			$comptResteResaESN++;
		}
		
		if($comptResteResaESN==0){
			$comptResteResaESN = "";
		}else{
			$comptResteResaESN = " + ".$comptResteResaESN;
		}
		
		
		
		//INSCRIPTIONS ADHERENTS
		for($i=0; $i<count($lstInscrAdh); $i++){
			
			$tempOptionsJS = "";
			$tempSommeOptions = 0;
			
			if(count($tabOptions) > 0){
			//Recup des options
				$arrayOpt = array();
				
				for($opti=0; $opti<count($tabOptionsChoisies); $opti++){
					
					if($tabOptionsChoisies[$opti]['idPart'] == $lstInscrAdh[$i]['id']){
						
						//on cherche le numero de l'option
						for($o=0; $o<count($tabOptions); $o++){
							if($tabOptions[$o]['id'] == $tabOptionsChoisies[$opti]['idOpt']){
								
								array_push($arrayOpt, ($o+1));
								$tempSommeOptions += $tabOptions[$o]['prixOpt'];
								
								if($lstInscrAdh[$i]['listeAttente']==0){
									$tabOptions[$o]['nb'] ++;
								}else{
									$tabOptions[$o]['nbLA'] ++;
								}
								$tempOptionsJS .= $o."//";
								break;
							}
						}
					}
				}
				//Mise en forme texte options

				sort($arrayOpt);
				if(count($arrayOpt) == 0){
					
					$textOpt = "Sans options";
					
				}elseif(count($arrayOpt) == 1){
					
					$textOpt = "Option : ".$arrayOpt[0];
					
				}else{
					
					$textOpt = "Options : ";
					
					for($txtO = 0; $txtO < count($arrayOpt); $txtO++){
						
						$textOpt .= $arrayOpt[$txtO]. ", ";
					}
					$textOpt = substr($textOpt, 0 , -2);
				}
				
				
			}else{
				$textOpt = "";
			}	
			
			//Récup consentements acceptés
			
			for($consent=0; $consent<count($consentementsAccepted); $consent++){
				
				if($consentementsAccepted[$consent]['idAdh'] == $lstInscrAdh[$i]['idAdh']){
					$tempConsentJS .= $consentementsAccepted[$consent]['idConsent']."//";
					$comptConsent[$consentementsAccepted[$consent]['idConsent']] ++;
				}
			}

			
			if(!$actFullFree){
				if($lstInscrAdh[$i]['fullPaid']==0){
					
					if($lstInscrAdh[$i]['paid']==0){
						$tdPaye='<td style="width:110px; font-weight:bold;color: orangered;">Non payé</td>';
					}else{
						$tdPaye='<td style="width:110px; font-weight:bold;color: orangered;">'.$lstInscrAdh[$i]['paid'].'€<br /><div style="font-size:0.8em; line-height:1.15em">Reçu n°'.$lstInscrAdh[$i]['recu'].'</div></td>';
					}
					
				}else{	
				
					if($lstInscrAdh[$i]['paid']==0){
						if(($act['prix'] + $tempSommeOptions) == 0){
							$tdPaye='<td style="width:110px">Gratuit</td>';
						}else{
							$tdPaye='<td style="width:110px">OK (0€)</td>';
						}
					}else{
						$tdPaye='<td style="width:110px">OK ('.$lstInscrAdh[$i]['paid'].'€)<br /><div style="font-size:0.8em; line-height:1.15em">Reçu n°'.$lstInscrAdh[$i]['recu'].'</div></td>';
					}
				}

			
			}else{
				$tdPaye="";
			}
			
			$tdEdit = '<td class="edit" onclick="editPaid(\'Adh\','.$i.','.$lstInscrAdh[$i]['id'].',\''.str_replace("'","\'", $lstInscrAdh[$i]['prenom']).' '.str_replace("'","\'", $lstInscrAdh[$i]['nom']).'\','.($lstInscrAdh[$i]['listeAttente']).')"></td>';
			
			$dtetmeInscr = explode(' ',$lstInscrAdh[$i]['dateInscr'],2);
			$dteInscr = explode('-',$dtetmeInscr[0],3);
			$dateInscr = $dteInscr[2].'/'.$dteInscr[1].'/'.$dteInscr[0].' '.$dtetmeInscr[1];
			
			if($lstInscrAdh[$i]['listeAttente']==0){
			
				//Personne qui s'est désisté et en attente de remboursement ?
				if($lstInscrAdh[$i]['fullPaid']==-1){
					
					$tabRemboursement.='<tr><td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].'<br /><div style="font-size:0.8em; font-weight:normal; line-height:1.15em">Pays : '.$lstInscrAdh[$i]['pays'].'</div></td>
										<td style="font-size:0.9em">Tel : '.$lstInscrAdh[$i]['tel'].'<br /><div class="hidden-inline" style="width:180px; font-size:0.8em; line-height:1.15em;"><a href="mailto:'.$lstInscrAdh[$i]['email'].'">'.$lstInscrAdh[$i]['email'].'</a></div></td>
										<td>'.$lstInscrAdh[$i]['paid'].'€<br /><div style="font-size:0.8em; line-height:1.15em">Reçu n°'.$lstInscrAdh[$i]['recu'].'</div></td>
										<td id="cellRembours'.$lstInscrAdh[$i]['id'].'" class="tick" onclick="submRembours('.$lstInscrAdh[$i]['id'].',\''.str_replace("'","\'", $lstInscrAdh[$i]['prenom']).' '.str_replace("'","\'", $lstInscrAdh[$i]['nom']).'\')"></td>
										</tr>';
				}else{		
					if(!empty($lstInscrAdh[$i]['nom'])){ 
						$tabInscrAdh.='<tr><td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].'<br /><div style="font-size:0.8em; font-weight:normal; line-height:1.15em; float:left">Pays : '.$lstInscrAdh[$i]['pays'].'</div>'.(!empty($textOpt)?'<div style="float:right; font-size:0.8em; line-height:1.15em; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
									<td style="font-size:0.9em">Tel : '.$lstInscrAdh[$i]['tel'].'<br /><div class="hidden-inline" style="width:180px; font-size:0.8em; line-height:1.15em;"><a href="mailto:'.$lstInscrAdh[$i]['email'].'">'.$lstInscrAdh[$i]['email'].'</a></div></td>
									'.$tdPaye.'
									<td style="font-size:0.9em">'.$dateInscr.'<br /><div class="hidden-inline" style="width:185px" font-size:0.8em; line-height:1.15em;">Par : '.$lstInscrAdh[$i]['inscrBy'].'</div></td>
									'.$tdEdit.'
									<td id="cellDesist'.$lstInscrAdh[$i]['id'].'" class="suppr" onclick="submDesist('.$lstInscrAdh[$i]['id'].',\''.str_replace("'","\'", $lstInscrAdh[$i]['prenom']).' '.str_replace("'","\'", $lstInscrAdh[$i]['nom']).'\')" title="Si la personne avait payé, elle sera placée dans une liste spéciale en attendant son remboursement."></td>
									</tr>';	
					
					}else{ //si bénévole suppr de la bdd
						$tabInscrAdh.='<tr><td class="gras" colspan=2>Ce membre ne fait plus partie de la base de données'.(!empty($textOpt)?'<br/><div style="float:right; font-size:0.8em; line-height:1.15em; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
								'.$tdPaye.'
								<td style="font-size:0.9em">'.$dateInscr.'<br /><div class="hidden-inline" style="width:185px" font-size:0.8em; line-height:1.15em;">Par : '.$lstInscrAdh[$i]['inscrBy'].'</div></td>
								</tr>';
					
					}				
					$sommeEvent += $lstInscrAdh[$i]['paid'];
					$comptInscritsAdh++;
				}
									
			}else{//Mise en attente
			
				//Verif activité passée pour remboursement membres en liste d'attente
				if(date_add(date_create($act['dte']), date_interval_create_from_date_string('1 day'))<date_create('now')&&$lstInscrAdh[$i]['paid']>0){
				
					$tabRemboursement.='<tr><td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].'<br /><div style="font-size:0.8em; font-weight:normal; line-height:1.15em">Pays : '.$lstInscrAdh[$i]['pays'].'</div></td>
										<td style="font-size:0.9em">Tel : '.$lstInscrAdh[$i]['tel'].'<br /><div class="hidden-inline" style="width:180px; font-size:0.8em; line-height:1.15em;"><a href="mailto:'.$lstInscrAdh[$i]['email'].'">'.$lstInscrAdh[$i]['email'].'</a></div></td>
										<td>'.$lstInscrAdh[$i]['paid'].'€<br /><div style="font-size:0.8em; line-height:1.15em">Reçu n°'.$lstInscrAdh[$i]['recu'].'</div></td>
										<td id="cellRembours'.$lstInscrAdh[$i]['id'].'" class="tick" onclick="submRembours('.$lstInscrAdh[$i]['id'].',\''.str_replace("'","\'", $lstInscrAdh[$i]['prenom']).' '.str_replace("'","\'", $lstInscrAdh[$i]['nom']).'\')"></td>
										</tr>';
				}
			
				if(!empty($lstInscrAdh[$i]['nom'])){ 
					$tabAtt='<tr class="grisé"><td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].'<br /><div style="font-size:0.8em; font-weight:normal; line-height:1.15em; float:left">Pays : '.$lstInscrAdh[$i]['pays'].'</div>'.(!empty($textOpt)?'<div style="float:right; font-size:0.8em; line-height:1.15em; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
							<td style="font-size:0.9em">Tel : '.$lstInscrAdh[$i]['tel'].'<br /><div class="hidden-inline" style="width:180px; font-size:0.8em; line-height:1.15em;"><a href="mailto:'.$lstInscrAdh[$i]['email'].'">'.$lstInscrAdh[$i]['email'].'</a></div></td>
							'.$tdPaye.'
							<td style="font-size:0.9em">'.$dateInscr.'<br /><div class="hidden-inline" style="width:'.(($isPlacesDispo||$isPlacesDispoESN)?160:185).'px" font-size:0.8em; line-height:1.15em;">Par : '.$lstInscrAdh[$i]['inscrBy'].'</div></td>
							'.(($isPlacesDispo)?"<td id=\"cellInscrAttente".$lstInscrAdh[$i]['id']."\" class=\"add\" onclick=\"submInscrAttente(".$lstInscrAdh[$i]['id'].",'".str_replace("'","\'", $lstInscrAdh[$i]['prenom']).' '.str_replace("'","\'", $lstInscrAdh[$i]['nom'])."')\"></td>":(($isPlacesDispoESN && $isESNenAttente)?"<td></td>":"")).'
							'.$tdEdit.'
							<td id="cellDesist'.$lstInscrAdh[$i]['id'].'" class="suppr" onclick="submDesist('.$lstInscrAdh[$i]['id'].',\''.str_replace("'","\'", $lstInscrAdh[$i]['prenom']).' '.str_replace("'","\'", $lstInscrAdh[$i]['nom']).'\')" title="Si la personne avait payé, elle sera placée dans une liste spéciale en attendant son remboursement."></td>
							</tr>';	
				
				}else{ //si bénévole suppr de la bdd
					$tabAtt='<tr class="grisé"><td class="gras" colspan=2>Ce membre ne fait plus partie de la base de données'.(!empty($textOpt)?'<br/><div style="float:right; font-size:0.8em; line-height:1.15em; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
							'.$tdPaye.'
							<td style="font-size:0.9em">'.$dateInscr.'<br /><div class="hidden-inline" style="width:185px" font-size:0.8em; line-height:1.15em;">Par : '.$lstInscrAdh[$i]['inscrBy'].'</div></td>
							'.(($isPlacesDispo)?"<td></td>":"").'</tr>';
					
				}		
				array_push($lstAttente, array($lstInscrAdh[$i]['dateInscr'],$tabAtt));

			}
			$lstInscrAdhJS.= 'lstInscrAdhJS['.$i.']=new Array("'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].'","'.$lstInscrAdh[$i]['fullPaid'].'","'.$lstInscrAdh[$i]['paid'].'","'.$lstInscrAdh[$i]['recu'].'","'.$tempOptionsJS.'","'.$tempConsentJS.'");';
		}
	}
	
	//Affichage options
	for($opt=0; $opt<count($tabOptions); $opt++){
		
		
		if($tabOptions[$opt]['prixOpt'] > 0 ){
			$textPrix = " (Supplément : " .$tabOptions[$opt]['prixOpt']. "€)";
			
		}else if($tabOptions[$opt]['prixOpt'] < 0){
			$textPrix = " (Réduction : " .$tabOptions[$opt]['prixOpt']. "€)";
			
		}else{
			$textPrix ="";
		}
		
		if($tabOptions[$opt]['nbLA'] > 0){
			$nbOptLA = ' (+ '.$tabOptions[$opt]['nbLA'].' en liste d\'attente)';
		}else{
			$nbOptLA = "";
		}
		
		if($opt==0){
			
			$listeOptions .= '<tr><td class="center gras" style="width:5%;">'.($opt+1).'.</td><td style="">'.$tabOptions[$opt]['opt'].'<div style="float:right; font-size:0.8em">'.$textPrix.'</div></td>
				<td style="text-align:right; font-size:0.8em;">Nombre de personnes : '.$tabOptions[$opt]['nb'].$nbOptLA.'</td>
				</tr>';
			
			
		}else{
			$listeOptions .= '<tr><td class="center gras" style="width:5%; border-top:dotted 1px black">'.($opt+1).'.</td><td style="border-top:dotted 1px black">'.$tabOptions[$opt]['opt'].'<div style="float:right; font-size:0.8em">'.$textPrix.'</div></td>
						<td style="text-align:right; font-size:0.8em; border-top:dotted 1px black">Nombre de personnes : '.$tabOptions[$opt]['nb'].$nbOptLA.'</td>
						</tr>';
		}
	
	
		$tableOptions .= '<tr><td>'.$tabOptions[$opt]['opt'].'<div style="float:right">'.$textPrix.'</div>'.
							'<input type="hidden" id="idOpt'.$opt.'"  value="'.$tabOptions[$opt]['id'].'"/>'.
							'<input type="hidden" id="prixOpt'.$opt.'" value="'.$tabOptions[$opt]['prixOpt'].'"/>'.
							'</td>'.
							'<td id="tdSelectOpt'.$opt.'" onclick="selectOpt('.$opt.',false)" class="checkN" style="width:90px"></td></tr>';
							
			
		$tableOptionsEdit .= '<tr><td>'.$tabOptions[$opt]['opt'].'<div style="float:right">'.$textPrix.'</div>'.
							'<input type="hidden" id="idOptEdit'.$opt.'"  value="'.$tabOptions[$opt]['id'].'"/>'.
							'<input type="hidden" id="prixOptEdit'.$opt.'" value="'.$tabOptions[$opt]['prixOpt'].'"/>'.
							'</td>'.
							'<td id="tdSelectOptEdit'.$opt.'" onclick="selectOpt('.$opt.',true)" class="checkN" style="width:90px"></td></tr>';

	}
	
	
	//Mise en forme case consentements
	
	$listeConsentements="";
	$listeConsentementsEdit="";
	$tabConsentementsObligJS="";
	$tabConsentementsJS="";
	$defautConsentementsJS="";
	$listeRecapConsent="";
	if($consentements!==false && !empty($consentements) && !empty($act['consent'])){
		
		
		$listeConsentements='<div id="divConsent" style="display:none; padding:0; margin-top:5px; width:88%"><table style="width:100%; margin:0">';
		$listeConsentementsEdit='<div id="divConsentEdit" style="display:none; padding:0; margin-top:5px; width:88%"><table style="width:100%; margin:0">';
		

		for($i=0; $i<count($consentements); $i++){
			
			if(in_array($consentements[$i]['id'],$tabConsentAct)){
				
				$lienAffConsent='(<a href="http://'.$_SERVER['HTTP_HOST'].'/activity/actConsentements.php?idAct='.$act['id'].'&idConsent='.$consentements[$i]['id'].'" target="_blank"">voir</a>)';
				
				if($comptConsent[$consentements[$i]['id']] == 0){
					if($comptInscritsAdh > 0){
						$textAccepted = 'Accepté par aucun adhérents inscrits';
					}else{
						$textAccepted = '';
					}
				}elseif($comptConsent[$consentements[$i]['id']] < $comptInscritsAdh){
					$textAccepted = 'Accepté par '.$comptConsent[$consentements[$i]['id']].' adhérent'.(($comptConsent[$consentements[$i]['id']]>1)?'s':'').' inscrit'.(($comptConsent[$consentements[$i]['id']]>1)?'s':'').' sur '.$comptInscritsAdh.$lienAffConsent;
					
				}else{
					$textAccepted = 'Accepté par tous les adhérents inscrits '.$lienAffConsent;
					
				}
				
				if($i==0){
					$listeRecapConsent.='<tr>
										<td>'.$consentements[$i]['titre'].'<div style="display:inline; padding-left:12px;font-size:0.8em">'.(($consentements[$i]['obligatoire'])?'(obligatoire)':'').'</div></td>
										<td style="text-align:right; font-size:0.8em;">'.$textAccepted.'</td>
										</tr>';
					
				}else{
					
					$listeRecapConsent.='<tr>
										<td style="border-top:dotted 1px black">'.$consentements[$i]['titre'].'<div style="display:inline; padding-left:12px;font-size:0.8em">'.(($consentements[$i]['obligatoire'])?'(obligatoire)':'').'</div></td>
										<td style="text-align:right; font-size:0.8em;border-top:dotted 1px black">'.$textAccepted.'</td>
										</tr>';
					
				}
				
				
			
				$listeConsentements.='<tr><td style="padding-left8px; cursor:pointer" onclick="checkCaseConsent('.$consentements[$i]['id'].',false)"><input type="checkbox" id="caseConsent-'.$consentements[$i]['id'].'" name="caseConsent-'.$consentements[$i]['id'].'" '.(($consentements[$i]['defaut'])?'checked':'').'>
				<label class="checkbox '.(($consentements[$i]['obligatoire'])?'required':'').'" for="caseConsent-'.$consentements[$i]['id'].'" style="margin-bottom:6px; display:inline">'.$consentements[$i]['texteCase'].'.</label>(<a onclick="affConsent('.$consentements[$i]['id'].',false)" id="aConsent-'.$consentements[$i]['id'].'">afficher</a>)</div>
				<div id="divTextConsent-'.$consentements[$i]['id'].'" style="display:none;padding-left:4px; margin-bottom:10px; width:95%;">'.bbCodeToHTML($consentements[$i]['texte']).'</div></td></tr>';
				
				$listeConsentementsEdit.='<tr><td id="tdCaseConsentEdit-'.$consentements[$i]['id'].'" style="padding-left8px; cursor:pointer" onclick="checkCaseConsent('.$consentements[$i]['id'].',true)"><input type="checkbox" id="caseConsentEdit-'.$consentements[$i]['id'].'" name="caseConsentEdit-'.$consentements[$i]['id'].'">
				<label class="checkbox '.(($consentements[$i]['obligatoire'])?'required':'').'" for="caseConsentEdit-'.$consentements[$i]['id'].'" style="margin-bottom:6px; display:inline">'.$consentements[$i]['texteCase'].'.</label>(<a onclick="affConsent('.$consentements[$i]['id'].',true)" id="aConsentEdit-'.$consentements[$i]['id'].'">afficher</a>)</div>
				<div id="divTextConsentEdit-'.$consentements[$i]['id'].'" style="display:none;padding-left:4px; margin-bottom:10px; width:95%;">'.bbCodeToHTML($consentements[$i]['texte']).'</div></td></tr>';
				
				
				
				if($consentements[$i]['obligatoire']){
					$tabConsentementsObligJS.= 'tabConsentementsObligJS.push('.$consentements[$i]['id'].');';
				}
				
				
				if($consentements[$i]['defaut']){
					$defautConsentementsJS.= 'document.getElementById("caseConsent-'.$consentements[$i]['id'].'").checked=true;';
				}else{
					$defautConsentementsJS.= 'document.getElementById("caseConsent-'.$consentements[$i]['id'].'").checked=false;';
				}
				
				$tabConsentementsJS.= 'tabConsentementsJS.push('.$consentements[$i]['id'].');';
				
			}
		}
		
		$listeConsentements.='</table></div>';
		$listeConsentementsEdit.='</table></div>';
	}

	
	
	
	

}//FIN VERIF ID ACTIVITE

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<?php if($acces){ ?>

<table class="activity"><tbody>
	<?php if($act['spots']==0){ ?>
		<tr><th rowspan=2 style="font-size:1.25em;"><?php echo $dateAct; ?></th>
		<th style="width:150px"><b>Places</b></th>
		<th style="width:300px"><b>Inscrits</b></th>
		<th style="width:150px"><b>Prix</b></th></tr>
		
		<tr><td>Illimitées</td>
		<td><?php echo $spotsSold[0]; ?></td>
		<td><?php echo ($act['prix']==0)?"Gratuit":$act['prix']."€"; ?></td></tr>
	<?php }else{ ?>
		<tr><th rowspan=2 style="font-size:1.25em;"><?php echo $dateAct; ?></th>
		<th style="width:150px"><b>Places</b></th>
		<th style="width:150px"><b>Places restantes</b></th>
		<th style="width:150px"><b>Inscrits</b></th>
		<th style="width:150px"><b>Prix</b></th></tr>
		
		<tr><td><?php echo $act['spots']; ?></td>
		<td><?php echo (($isPlacesDispo)?(intval($act['spots'])-intval($spotsSold[0])):'<font color="yellow">Complet</font>'); ?></td>
		<td><?php echo (($spotsSold[1]==0)?$spotsSold[0]:$spotsSold[0].' + '.$spotsSold[1].' en attente'); ?></td>
		<td><?php echo ($act['prix']==0)?"Gratuit":$act['prix']."€"; ?></td></tr>
	<?php } ?>
</tbody></table>

<?php echo $inscrLibre; ?>

<h3>Informations</h3>
<div class="blocText">
<?php echo bbCodeToHTML($act['infos']); ?>
</div>

<?php if(!empty($listeOptions)){
	echo '<br/><div class="blocText">Liste des options :
		<table class="invisible" style="margin:0; width:100%;"><tbody>';
	echo $listeOptions;
	echo '</tbody></table></div>';
}?>
<?php if(!empty($listeRecapConsent)){
	echo '<br/><div class="blocText">Consentements :
		<table class="invisible" style="margin:0; width:100%;"><tbody>';
	echo $listeRecapConsent;
	echo '</tbody></table></div>';
}?>

<?php if(!$actFullFree){ ?>
	<br /><div class="blocText">
	Somme des paiements : <?php echo $sommeEvent; ?>€<?php echo $paieStat; ?>
	</div>
<?php } ?>

<h3 id="h3Inscr">Inscrire quelqu'un<?php echo ($act['spots']!=0&&(intval($act['spots'])-intval($spotsSold[0]))<=0)?" sur la liste d'attente":""?></h3>
<table id="champsFilter" class="invisible"><tbody><tr><td>
<label for="type" >type de membre</label>
	<input id="typeA" type="radio" name="type" value="Adh" onclick="selectType()" checked>  
	<label class="radio" for="typeA" onclick="selectType()">Adhérent</label>  
	<input id="typeB" type="radio" name="type" value="Ben" onclick="selectType()">  
	<label class="radio" for="typeB" onclick="selectType()">Bénévole</label> 
</td><td>
<label for="nom">prénom ou nom</label>
<input type="text" id="nom" name="nom" onkeyup="filtering()" value="" autocomplete="off"/>
</td><td>
<label for="carteesn" id="labelCarteesn">numero carte esn</label>
<input type="text" id="carteesn" name="carteesn" onkeyup="filtering()" style="width:120px" value="" autocomplete="off"/>
</td></tr></tbody></table>

<table id="listeESN" style="display:none"><tbody><th>Nom</th><th id="lastTHESN" style="width:70px" >Inscrire</th>
<?php echo $lstESN; ?>
</tbody></table>

<table id="listeAdh" style="display:none"><tbody><th style="width:255px">Nom</th><th>Pays</th><th style="width:100px">Carte ESN</th><th id="lastTHAdh" style="width:70px" >Inscrire</th>
<?php echo $lstAdh; ?>
</tbody></table>

<div id="diversAdh" style="display:none; width:88%">
<h3>Informations sur l'adhérent</h3>
<div class="blocText" id="textDiversAdh"></div>
<h3>Paiement</h3>
</div>

<form method=post action="http://<?php echo $_SERVER['HTTP_HOST']; ?>/activity-<?php echo $act['id']; ?>" id="formInscr" style="display:none">
<input type="hidden" id="typeInscr" name="typeInscr" />
<input type="hidden" id="idInscr" name="idInscr" />
<input type="hidden" id="nameInscr" name="nameInscr" />
<input type="hidden" id="iInscr" name="iInscr" />
<input type="hidden" id="options" name="options" />

<?php if(!empty($tableOptions)){ ?>
	<table style="margin-top:5px; width:88%">
	<th>Options</th><th>Choix</th>
	<tbody id="tbodyOptions"><?php echo $tableOptions;  ?></tbody>
	</table>
<?php }?>


<?php if(!empty($listeConsentements)){ echo $listeConsentements;}?>



<table style="margin-top:5px; width:88%;"><tr><td>

	<table class="invisible" style="margin:0; width:100%; height:66px;"><tbody><tr>

	<td style="text-align:center; width:28%">Somme due :<br/><span id="sommeDue" style="font-weight:bold">0€</span></td>

	<td id="tdFullPaid" style="vertical-align:bottom; text-align:center" >

	<input type="checkbox" id="fullPaid" name="fullPaid" onchange="putPrix(false)"> 
	<label class="checkbox" for="fullPaid">Entièrement payé</label>
	</td>
	<td id="tdPaid">
	<label for="paid">somme payée</label>
	<input type="text" id="paid" name="paid" class="euro" style="width:70px" maxlength=7 autocomplete="off"> 
	</td>
	<td id="tdRecu" >
	<label for="recu" >numero reçu</label>
	<input type="text" id="recu" name="recu" style="width:70px" maxlength=3 autocomplete="off"> 
	</td>

	<td style="min-width:120px"><center><input type="button" onclick="submInscr(false)" id="submitInscr" value="valider" style="margin-top:0;"/><center></td>
	</tr>
	<tr id="trInfosListeAttente"><td colspan="100%" style="padding-top:5px; padding-bottom:3px; font-size:0.8em; line-height:1em" >En liste d'attente, la personne peut tout de même payer l'inscription afin qu'elle ne soit pas obligée de repasser au local si son inscription est validée.<br/>Si son inscription n'est pas validée, la personne sera automatiquement placée dans une liste spéciale en attendant son remboursement.</td></tr>
	</tbody></table>
</td></tr></table>

</form>

<?php if(!empty($tabRemboursement)){?>
<h3>Membres à rembourser</h3>
<table><tbody>
<tr><th style="width:222px">Nom</th><th style="width:160px">Contact</th><th>Montant</th><th style="width:209px">Entièrement remboursé</th></tr>
<?php echo $tabRemboursement; ?>
</table></tbody>
<form method=post action="http://<?php echo $_SERVER['HTTP_HOST']; ?>/activity-<?php echo $act['id']; ?>" id="formRembours">
<input type="hidden" id="idRembours" name="idRembours" /><input type="hidden" id="nameRembours" name="nameRembours" />
</form>
<?php } ?>


<div id="divInscr">
<h3>Liste des inscrits</h3>
<?php
if(!empty($tabInscrESN)||!empty($tabInscrAdh)){
	echo '<div class="blocText" style="'.((count($tabOptions)>0)?'':'width:315px;').'"><a href="http://'.$_SERVER['HTTP_HOST'].'/activity/actPrintable.php?idAct='.$act['id'].'" target="_blank">
			<img src="../template/images/printer.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste imprimable</a>
			<a href="http://'.$_SERVER['HTTP_HOST'].'/activity/actMails.php?idAct='.$act['id'].'" target="_blank" style="margin-left:30px">
			<img src="../template/images/emails.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste des e-mails</a>'.
			
			((count($tabOptions)>0)?'<a href="http://'.$_SERVER['HTTP_HOST'].'/activity/actOptionsPrintable.php?idAct='.$act['id'].'" target="_blank" style="margin-left:30px"><img src="../template/images/checkboxes.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste imprimable par options</a>'.
			'<a href="http://'.$_SERVER['HTTP_HOST'].'/activity/actOptionsMails.php?idAct='.$act['id'].'" target="_blank" style="margin-left:30px"><img src="../template/images/emails.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste des e-mails par options</a>':'').
			
			'</div><br />';
			
	if(!empty($tabInscrESN)){
		echo '<table id="tabInscrESN"><tbody>';
		if($comptInscritsESN>0){
			echo '<tr><th>Nom ('.$comptInscritsESN.$comptResteResaESN.')</th>
					<th style="width:160px">Contact</th>';
			echo (!$actFullFree)?'<th>Paiement</th>':"";	
			echo	'<th style="width:165px">Inscription</th>			
					</tr>';
		}else{
			echo '<tr><th>Nom</th></tr>';
		}
		echo $tabInscrESN;
		echo '</tbody></table>';
	}
	if(!empty($tabInscrAdh)){	
		echo '<table id="tabInscrAdh"><tbody><tr><th><span style="margin-right:50px">Nom ('.$comptInscritsAdh.')</span>Pays<img class="sortA" onclick="sort(\'pays\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'pays\',\'dsc\')" src="../template/images/sortDesc.png"></th>
				<th style="width:160px">Contact</th>';
		echo (!$actFullFree)?'<th>Paiement<img class="sortA" onclick="sort(\'paie\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'paie\',\'dsc\')" src="../template/images/sortDesc.png"></th>':"";	
		echo	'<th style="width:165px">Inscription<img class="sortA" onclick="sort(\'inscr\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'inscr\',\'dsc\')" src="../template/images/sortDesc.png"></th>			
				</tr>';
		echo $tabInscrAdh;
		echo '</tbody></table>';
	}
	echo '</div>'; //Fin divInscr
			
	//Edition
		
	echo '<div id="divEditPaid" style="display:none"><h3>Edition</h3><table id="tabEditPaid"><tbody>
		<tr><th>Nom</th><th style="width:70px">Annuler</th></tr>
		<tr class="orange"><td id="tabEditPaidNom" class="gras"></td><td class="remove" onclick="annulEditPaid()"></td></tr>
		</tbody></table>';
	echo '<form method=post action="http://'.$_SERVER['HTTP_HOST'].'/activity-'.$act['id'].'" id="formInscrEdit">
			<input type="hidden" id="isLA" name="isLA" />
			<input type="hidden" id="idEditPaid" name="idEditPaid" />
			<input type="hidden" id="typeInscrEdit" name="typeInscrEdit" />
			<input type="hidden" id="nameEditPaid" name="nameEditPaid" />
			<input type="hidden" id="optionsEdit" name="optionsEdit" />';
			
			
			
	if(!empty($tableOptions)){
		echo'<table style="margin-top:5px; width:88%">
			<th>Options</th><th>Choix</th>
			<tbody id="tbodyOptionsEdit">'.$tableOptionsEdit.'</tbody>
			</table>';
	}

	if(!empty($listeConsentementsEdit)){ 
		echo $listeConsentementsEdit;
	}
		
	echo'<table style="margin-top:5px; width:88%;"><tr><td>

			<table class="invisible" style="margin:0; width:100%; height:66px;"><tbody><tr>

				<td style="text-align:center; width:28%">
				Somme due : <span id="sommeDueEdit" style="font-weight:bold">0€</span><br/>
				Somme déjà payée : <span id="sommeDejaPayee" style="font-weight:bold"></span>
				</td>

				<td id="tdFullPaidEdit" style="vertical-align:bottom; text-align:center" >

				<input type="checkbox" id="fullPaidEdit" name="fullPaidEdit" onchange="putPrix(true);affPaiement(true)"> 
				<label class="checkbox" for="fullPaidEdit">Entièrement payé</label>
				</td>
				<td id="tdPaidEdit">
				<label for="paidEdit">somme payée</label>
				<input type="text" id="paidEdit" name="paidEdit" class="euro" style="width:70px" onkeyup="affPaiement(true)" maxlength=7 autocomplete="off"> 
				</td>
				<td id="tdRecuEdit" >
				<label for="recuEdit" >numero reçu</label>
				<input type="text" id="recuEdit" name="recuEdit" style="width:70px" maxlength=3 autocomplete="off"> 
				</td>

				<td style="min-width:120px; vertical-align:center"><center>
				<span id="sommeDiffCaisse" style="font-weight:bold; text-decoration: underline; color: brown;">Caisse : + 150.50€</span>
				<input type="button" onclick="submInscr(true)" id="submitInscrEdit" value="valider" style="margin-top:0;"/>
				<center></td>
				</tr>';
				

	echo '<tr id="trInfosListeAttenteEdit"><td colspan="100%" style="padding-top:5px; padding-bottom:3px; font-size:0.8em; line-height:1em" >En liste d\'attente, la personne peut tout de même payer l\'inscription afin qu\'elle ne soit pas obligée de repasser au local si son inscription est validée.<br/>Si son inscription n\'est pas validée, la personne sera automatiquement placée dans une liste spéciale en attendant son remboursement.</td></tr>';
		
			
	echo '</tbody></table>
		</td></tr></table>	
		</form></div>';
	
		
		//Form desistement
	echo '<form method=post action="http://'.$_SERVER['HTTP_HOST'].'/activity-'.$act['id'].'" id="formDesist">
			<input type="hidden" id="idDesist" name="idDesist" /><input type="hidden" id="nameDesist" name="nameDesist" />
			</form>';
		
}else{
	echo '<div>Pas d\'inscrits.</div></div>';
}

if(!empty($lstAttente)){
	
	asort($lstAttente);
	echo '<div id="divAttente">';
	echo '<h3>Liste d\'attente</h3>';
echo '<table id="tabattente"><tbody><tr><th><span style="margin-right:50px">Nom ('.$spotsSold[1].')</span>Pays<img class="sortA" onclick="sort(\'pays\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'pays\',\'dsc\')" src="../template/images/sortDesc.png"></th>
				<th style="width:160px">Contact</th>';
		echo (!$actFullFree)?'<th>Paiement<img class="sortA" onclick="sort(\'paie\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'paie\',\'dsc\')" src="../template/images/sortDesc.png"></th>':"";	
		echo	'<th style="width:'.(($isPlacesDispo||($isPlacesDispoESN&&$isESNenAttente))?140:165).'px">Inscription</th>'.(($isPlacesDispo||($isPlacesDispoESN&&$isESNenAttente))?"<th>Inscrire</th>":"").'
				</tr>';
	foreach($lstAttente as $tdAttente){
		echo $tdAttente[1];
	}
	echo '</tbody></table></div>';
	
		//Form inscription liste principale
	echo (($isPlacesDispo||$isPlacesDispoESN)?'<form method=post action="http://'.$_SERVER['HTTP_HOST'].'/activity-'.$act['id'].'" id="formInscrAttente">
	<input type="hidden" id="idInscrAttente" name="idInscrAttente" /><input type="hidden" id="nameInscrAttente" name="nameInscrAttente" /></form>':'');
}
?>


<script type="text/javascript">


var lstAdhJS=new Array();
<?php echo $lstAdhJS; ?>
var lstESNJS=new Array();
<?php echo $lstESNJS; ?>
var lstInscrAdhJS=new Array();
<?php echo $lstInscrAdhJS; ?>
var lstInscrESNJS=new Array();
<?php echo $lstInscrESNJS; ?>


var tabConsentementsJS=new Array();
<?php echo $tabConsentementsJS;?>
var tabConsentementsObligJS=new Array();
<?php echo $tabConsentementsObligJS;?>


<?php echo $reselect;?>

function selectType(){

	document.getElementById('listeESN').style.display="none";
	document.getElementById('listeAdh').style.display="none";
	document.getElementById('carteesn').value="";
	document.getElementById('nom').value="";
	document.getElementById('nom').focus();
	
	if(document.getElementById('typeA').checked==true){
		document.getElementById('labelCarteesn').style.display="";
		document.getElementById('carteesn').style.display="";
		for(var i=0; i<lstAdhJS.length; i++){
			document.getElementById('lineAdh'+i).style.display = "none";
		}
		if(<?php echo ($act['spots']!=0&&(intval($act['spots'])-intval($spotsSold[0])<=0))?1:0 ?>){
			document.getElementById('h3Inscr').innerHTML="Inscrire quelqu'un sur la liste d'attente";
		}
		
	}else if(document.getElementById('typeB').checked==true){
		document.getElementById('labelCarteesn').style.display="none";
		document.getElementById('carteesn').style.display="none";
		for(var i=0; i<lstESNJS.length; i++){
			document.getElementById('lineESN'+i).style.display = "none";
		}
		if(<?php echo ($isPlacesDispoESN)?1:0 ?>){
			document.getElementById('h3Inscr').innerHTML="Inscrire quelqu'un";
			
		}
	}
	
	isAffInfosLA(false);
}

function isAffInfosLA(edit){
	
	
	var la = <?php echo ($act['spots']!=0&&(intval($act['spots'])-intval($spotsSold[0])<=0))?1:0 ?>;
	var spotsESN = <?php echo ($isPlacesDispoESN)?1:0 ?>;
	
	
	if(!edit){
		
		
		if(document.getElementById('typeA').checked==true){
			
			if(la && document.getElementById('sommeDue').innerHTML != "0€"){
				document.getElementById('trInfosListeAttente').style.display = "";
			}else{
				document.getElementById('trInfosListeAttente').style.display = "none";
			}
			
			
			
		}else if(document.getElementById('typeB').checked==true){
			
			if(!spotsESN && la && document.getElementById('sommeDue').innerHTML != "0€"){
				document.getElementById('trInfosListeAttente').style.display = "";
			}else{
				document.getElementById('trInfosListeAttente').style.display = "none";
			}
			
		}
		
		
	}else{
		
		var isLA = document.getElementById('isLA').value;
		
		if(isLA==1){
			
			if(document.getElementById('sommeDueEdit').innerHTML != "0€"){
				document.getElementById('trInfosListeAttenteEdit').style.display = "";
			}else{
				document.getElementById('trInfosListeAttenteEdit').style.display = "none";
			}
			
			
		}else{
			
			document.getElementById('trInfosListeAttenteEdit').style.display = "none";
			
		}
	}
}

function filtering(){

	if(document.getElementById('typeA').checked==true){
	
		if(document.getElementById('carteesn').value.length>2 || document.getElementById('nom').value.length>1){
			document.getElementById('listeESN').style.display="none";
			document.getElementById('listeAdh').style.display="";
		
			for(var i=0; i<lstAdhJS.length; i++){
				var nom = lstAdhJS[i][0]+" "+lstAdhJS[i][1];
				if(lstAdhJS[i][2].indexOf(document.getElementById('carteesn').value.toLowerCase())==-1 || nom.indexOf(document.getElementById('nom').value.toLowerCase())==-1){
					document.getElementById('lineAdh'+i).style.display = "none";

				}else{
					document.getElementById('lineAdh'+i).style.display = "";
				}
			}
		
		}else{
		
			for(var i=0; i<lstAdhJS.length; i++){
				document.getElementById('lineAdh'+i).style.display = "none";
			}
			
			document.getElementById('listeAdh').style.display="none";
			document.getElementById('listeESN').style.display="none";

		}
	
	}else if(document.getElementById('typeB').checked==true){
		
		if(document.getElementById('nom').value.length>1){
			document.getElementById('listeESN').style.display="";
			document.getElementById('listeAdh').style.display="none";
		
			for(var i=0; i<lstESNJS.length; i++){
				var nom = lstESNJS[i][0]+" "+lstESNJS[i][1];
				if(nom.indexOf(document.getElementById('nom').value.toLowerCase())==-1){
					document.getElementById('lineESN'+i).style.display = "none";

				}else{
					document.getElementById('lineESN'+i).style.display = "";
				}
			}
		
		}else{
		
			for(var i=0; i<lstESNJS.length; i++){
				document.getElementById('lineESN'+i).style.display = "none";
			}
			document.getElementById('listeAdh').style.display="none";
			document.getElementById('listeESN').style.display="none";
		}
			
	}
}

function select(type,id,i,name){
	
	document.getElementById('options').value = "";
	document.getElementById('sommeDue').innerHTML = <?php echo $act['prix']; ?> + "€";
	
	if(!!document.getElementById('tbodyOptions')){ //Y'a des options?
		var tbodyOptions = document.getElementById('tbodyOptions').childNodes;
		for(opt=0; opt<(tbodyOptions.length); opt++){
			tbodyOptions[opt].className="";
			document.getElementById('tdSelectOpt'+opt).className="checkN";
		}
	}
	
	affPaiement(false);
	

	if(document.getElementById('line'+type+i).className == "selected"){
		document.getElementById('line'+type+i).className = "";
		document.getElementById('cell'+type+i).className = "add";
		document.getElementById('lastTHAdh').innerHTML="Inscrire";
		document.getElementById('lastTHESN').innerHTML="Inscrire";
		
		document.getElementById('typeInscr').value="";
		document.getElementById('idInscr').value="";
		document.getElementById('nameInscr').value="";
		document.getElementById('iInscr').value="";
				
		document.getElementById('champsFilter').style.display="";
		document.getElementById('formInscr').style.display="none";
		
		document.getElementById('diversAdh').style.display="none";
		document.getElementById('textDiversAdh').innerHTML="";
		
		<?php if(!empty($listeConsentements)) { ?>
		document.getElementById('divConsent').style.display="none";
		<?php } ?>
		
		
		document.getElementById('fullPaid').checked=false;
		document.getElementById('paid').value="";
		document.getElementById('recu').value="";
		
		filtering();
	
	}else{
		document.getElementById('line'+type+i).className = "selected";
		document.getElementById('cell'+type+i).className = "remove";
		document.getElementById('lastTHAdh').innerHTML="Annuler";
		document.getElementById('lastTHESN').innerHTML="Annuler";
		
		document.getElementById('typeInscr').value=type;
		document.getElementById('idInscr').value=id;
		document.getElementById('nameInscr').value=name;
		document.getElementById('iInscr').value=i;
		
		document.getElementById('champsFilter').style.display="none";
		document.getElementById('formInscr').style.display="";

		if(type=='Adh'){
			document.getElementById('listeAdh').style.display="";
			for(var a=0; a<lstAdhJS.length; a++){
				if(a!=i){
					document.getElementById('line'+type+a).style.display = "none";
				}else{
					document.getElementById('line'+type+a).style.display = "";				
				}
			}
			
			if(lstAdhJS[i][3] != ""){
				document.getElementById('diversAdh').style.display="";
				document.getElementById('textDiversAdh').innerHTML=lstAdhJS[i][3];
			}
			
			<?php if(!empty($listeConsentements)) { ?>
				<?php echo $defautConsentementsJS ;?>
				document.getElementById('divConsent').style.display="";
			<?php } ?>
			
			
		}else if(type=='ESN'){
			document.getElementById('listeESN').style.display="";
			for(var a=0; a<lstESNJS.length; a++){
				if(a!=i){
					document.getElementById('line'+type+a).style.display = "none";
				}else{
					document.getElementById('line'+type+a).style.display = "";
				}
			}
		}
	}
}

function affPaiement(edit){
	
	if(edit){
		suffixe="Edit";
	}else{
		suffixe="";
	}
	
	
	
	//Si edit : MAJ diffCaisse
	
	if(edit){
		
		var diff = Number(document.getElementById('paidEdit').value) - Number(document.getElementById('sommeDejaPayee').innerHTML.replace("€",""));
		
		if(diff==0 || isNaN(diff) || Number(document.getElementById('paidEdit').value) <0 ){
			
			document.getElementById('sommeDiffCaisse').style.display="none";
			
			
		}else if(diff>0){
			
			document.getElementById('sommeDiffCaisse').style.display="";
			document.getElementById('sommeDiffCaisse').innerHTML = 'Caisse : +'+diff+'€';
			
		}else{
			
			document.getElementById('sommeDiffCaisse').style.display="";
			document.getElementById('sommeDiffCaisse').innerHTML = 'Caisse : '+diff+'€';
			
		}
		
		if(document.getElementById('sommeDue'+suffixe).innerHTML == "0€"){
			
			if(parseFloat(document.getElementById('sommeDejaPayee').innerHTML.replace("€","")) > 0){
				
				document.getElementById('sommeDiffCaisse').innerHTML = 'Caisse : -'+parseFloat(document.getElementById('sommeDejaPayee').innerHTML.replace("€",""))+'€';
				document.getElementById('sommeDiffCaisse').style.display="";
				
			}else{
				
				document.getElementById('sommeDiffCaisse').style.display="none";
				
			}
			
		}
	}
	
	
	if(document.getElementById('sommeDue'+suffixe).innerHTML == "0€"){
		document.getElementById('tdFullPaid'+suffixe).style.display="none";
		document.getElementById('tdPaid'+suffixe).style.display="none";
		document.getElementById('tdRecu'+suffixe).style.display="none";
		document.getElementById('submitInscr'+suffixe).style.width="70%";
		document.getElementById('submitInscr'+suffixe).style.marginTop="0";
		
	
	}else{
		document.getElementById('tdFullPaid'+suffixe).style.display="";
		document.getElementById('tdPaid'+suffixe).style.display="";
		document.getElementById('tdRecu'+suffixe).style.display="";
		document.getElementById('submitInscr'+suffixe).style.width="";
		

	}
	
	
	if(edit){
		if(document.getElementById('sommeDiffCaisse').style.display==""){
			document.getElementById('submitInscr'+suffixe).style.marginTop="2px";
		}else{
			document.getElementById('submitInscr'+suffixe).style.marginTop="14px";
			
		}
		
	}else{
		document.getElementById('submitInscr'+suffixe).style.marginTop="14px";
	}
	
	isAffInfosLA(edit);
	
}

function selectOpt(opt, edit){
	
	if(edit){
		suffixe="Edit";
	}else{
		suffixe="";
	}
	
	var tbodyOptions = document.getElementById('tbodyOptions'+suffixe).childNodes;
	options = "";
	
	
	if(tbodyOptions[opt].className != "selected"){ //AJOUT D'UNE OPTION
	
	
		prixTotal = <?php echo $act['prix']; ?> + parseFloat(document.getElementById('prixOpt'+suffixe+opt).value);
		

		for(i=0; i<(tbodyOptions.length); i++){
			if(tbodyOptions[i].className=="selected"){
				prixTotal += parseFloat(document.getElementById('prixOpt'+suffixe+i).value);
				options += document.getElementById('idOpt'+suffixe+i).value +"//";
			}
		}
		
		if(prixTotal >= 0){
			tbodyOptions[opt].className="selected";
			document.getElementById('tdSelectOpt'+suffixe+opt).className="checkO";
			document.getElementById('sommeDue'+suffixe).innerHTML = prixTotal + "€";
			document.getElementById('options'+suffixe).value = options + document.getElementById('idOpt'+suffixe+opt).value +"//";
			
			if(!edit){
				if(document.getElementById('fullPaid'+suffixe).checked==true){
					document.getElementById('paid'+suffixe).value=document.getElementById('sommeDue'+suffixe).innerHTML.replace("€","");
				}
			}else{
				//Si init, on ne fait rien. Sinon on edit automatiquement somme payée
				if(document.getElementById('sommeDejaPayee').innerHTML != "" && document.getElementById('fullPaid'+suffixe).checked==true){
					
					
					if(parseFloat(document.getElementById('prixOpt'+suffixe+opt).value) > 0){ //Si option payante : on augmente automatiquement paid
						
						document.getElementById('paid'+suffixe).value = Number(document.getElementById('paid'+suffixe).value) + Number(document.getElementById('prixOpt'+suffixe+opt).value);

					}else if(parseFloat(document.getElementById('prixOpt'+suffixe+opt).value) < 0){ //Si option reduction : on diminue automatiquement paid si non negatif
					
						if(parseFloat(document.getElementById('paid'+suffixe).value) >= -1*parseFloat(document.getElementById('prixOpt'+suffixe+opt).value)){
							
							document.getElementById('paid'+suffixe).value = Number(document.getElementById('paid'+suffixe).value) + Number(document.getElementById('prixOpt'+suffixe+opt).value);
						}
					}
				}
			}
			
			if(edit){ //On décoche fullpaid si paiement=gratuit, sait-on jamais si on rajoute une option payante
				if(document.getElementById('fullPaid'+suffixe).checked==true && document.getElementById('paid'+suffixe).value == 0 && document.getElementById('sommeDue'+suffixe).innerHTML == "0€"){
					document.getElementById('fullPaid'+suffixe).checked=false;
				}
	
			}
			
			
			affPaiement(edit);
			
			
		}else{
			
			alert("Le prix de l'activité ne peut pas être négatif.");
		}
	
	}else{ //SUPPRESSION D'UNE OPTION
		 
		tbodyOptions[opt].className="";
		document.getElementById('tdSelectOpt'+suffixe+opt).className="checkN";
		prixTotal = <?php echo $act['prix']; ?>;


		for(i=0; i<(tbodyOptions.length); i++){
			if(tbodyOptions[i].className=="selected"){
				prixTotal += parseFloat(document.getElementById('prixOpt'+suffixe+i).value);
				options += document.getElementById('idOpt'+suffixe+i).value +"//";
			}
		}
		
		if(prixTotal >= 0){

			document.getElementById('sommeDue'+suffixe).innerHTML = prixTotal + "€";
			document.getElementById('options'+suffixe).value = options;
			
			if(!edit){
				if(document.getElementById('fullPaid'+suffixe).checked==true){
					document.getElementById('paid'+suffixe).value=document.getElementById('sommeDue'+suffixe).innerHTML.replace("€","");
				}
			}else{
				//Si init, on ne fait rien. Sinon on change qqs trucs
				if(document.getElementById('sommeDejaPayee').innerHTML != "" && document.getElementById('fullPaid'+suffixe).checked==true){
					
					if(parseFloat(document.getElementById('prixOpt'+suffixe+opt).value) < 0){ //Si option reduction : on augmente automatiquement paid
					
						document.getElementById('paid'+suffixe).value = Number(document.getElementById('paid'+suffixe).value) + -1*Number(document.getElementById('prixOpt'+suffixe+opt).value);

					}else if(parseFloat(document.getElementById('prixOpt'+suffixe+opt).value) > 0){ //Si option payante : on diminue automatiquement paid si non negatif
					
						if(parseFloat(document.getElementById('paid'+suffixe).value) >= parseFloat(document.getElementById('prixOpt'+suffixe+opt).value)){
							
							document.getElementById('paid'+suffixe).value = Number(document.getElementById('paid'+suffixe).value) - Number(document.getElementById('prixOpt'+suffixe+opt).value);
						}
					}
				}
			}
			
			affPaiement(edit);
			
			
			
		}else{
			
			alert("Le prix de l'activité ne peut pas être négatif.");
		}
	}
}



function putPrix(edit){ 
	
	if(edit){
		suffixe="Edit";
	}else{
		suffixe="";
	}

	if(document.getElementById('fullPaid'+suffixe).checked==true){
		document.getElementById('paid'+suffixe).value=document.getElementById('sommeDue'+suffixe).innerHTML.replace("€","");
		document.getElementById('recu'+suffixe).focus();
	}else{
		document.getElementById('paid'+suffixe).value="";
		document.getElementById('paid'+suffixe).focus();
	}
}


function sort(colonne, order){
	window.location.href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/activity-<?php echo $act['id']; ?>-sort="+colonne+"-order="+order;
}

function affConsent(id,edit){
	
	
	if(edit){
		suffixe="Edit";
	}else{
		suffixe="";
	}
	
	//Annulation effet onclick sur le TD
	checkCaseConsent(id,edit)
	
	
	
	if(document.getElementById('divTextConsent'+suffixe+'-'+id).style.display == "none"){
		document.getElementById('divTextConsent'+suffixe+'-'+id).style.display = "";
		document.getElementById('aConsent'+suffixe+'-'+id).innerHTML = "masquer";

	}else{
		document.getElementById('divTextConsent'+suffixe+'-'+id).style.display = "none";
		document.getElementById('aConsent'+suffixe+'-'+id).innerHTML = "afficher";
	}
	
	
}

function checkCaseConsent(id,edit){
	
	if(edit){
		suffixe="Edit";
	}else{
		suffixe="";
	}
	
	if(document.getElementById('caseConsent'+suffixe+'-'+id).disabled == false){
	
		if(document.getElementById('caseConsent'+suffixe+'-'+id).checked == true){
			document.getElementById('caseConsent'+suffixe+'-'+id).checked = false;

		}else{
			document.getElementById('caseConsent'+suffixe+'-'+id).checked = true;
		}
	}
}



function submInscr(edit){
	
	if(edit){
		suffixe="Edit";
	}else{
		suffixe="";
	}
	
	var prix = Number(document.getElementById('sommeDue'+suffixe).innerHTML.replace("€",""));

	
	if(prix != 0){
		

		if(document.getElementById('paid'+suffixe).value > 0 && document.getElementById('recu'+suffixe).value==""){
			alert('Veuillez remplir le champ "Numéro reçu".');
			return;
		}

		if(document.getElementById('typeInscr'+suffixe).value=="Adh" && document.getElementById('fullPaid'+suffixe).checked==true){
			if(!isNaN(document.getElementById('paid'+suffixe).value) && Number(document.getElementById('paid'+suffixe).value >= 0)){
				if(Number(document.getElementById('paid'+suffixe).value) > prix){
					if(!confirm("Le prix payé est supérieur au prix de l'activité. Est-ce normal ?")){
						return;
					}
				}else if(Number(document.getElementById('paid'+suffixe).value) < prix){
					if(!confirm("Le prix payé est inférieur à celui de l'activité. Est-ce normal ?")){
						return;
					}
				}
			}else{
				alert('La valeur donnée dans le champ "Somme payée" n\'est pas valide.');
				return;
			}
		}else{
			if(!isNaN(document.getElementById('paid'+suffixe).value) && Number(document.getElementById('paid'+suffixe).value >= 0)){
				
				
				if(Number(document.getElementById('paid'+suffixe).value) > prix){
					if(!confirm("Le prix payé est supérieur au prix de l'activité. Est-ce normal ?")){
						return;
					}
				}
				if(document.getElementById('fullPaid'+suffixe).checked==false && Number(document.getElementById('paid'+suffixe).value) >= prix){
					if(!confirm("La case \"Entièrement payé\" n'est pas cochée. Est-ce normal ?")){
						return;
					}
				
				}
			}else{
				alert('La valeur donnée dans le champ "Somme payée" n\'est pas valide.');
				return;
			}
		}
	}
	
	//Verif consentements obligatoires
	if(!edit && document.getElementById('typeInscr'+suffixe).value=="Adh"){
		for(var c=0;c<tabConsentementsObligJS.length;c++){
			if(document.getElementById('caseConsent'+suffixe+'-'+tabConsentementsObligJS[c]).checked==false){
				alert("L'adhérent doit accepter les clauses obligatoires.");
				return;
			}
		}
	}
	
	
	//Si on a pas échappé la fonction : go pour inscr
	document.getElementById('submitInscr'+suffixe).disabled=true;
	document.getElementById('submitInscr'+suffixe).value = "Patientez...";
	document.getElementById('submitInscr'+suffixe).onclick="";
	document.getElementById('formInscr'+suffixe).submit();		
}



 function editPaid(type,i,id, nom, isLA){
 
	document.getElementById('divInscr').style.display="none";
	<?php echo(!empty($lstAttente))?'document.getElementById(\'divAttente\').style.display="none";':""; ?>
	
	document.getElementById('sommeDueEdit').innerHTML = <?php echo $act['prix']; ?> + "€";
	
	
	var listOptionsChecked = new Array();
	var listConsentChecked = new Array();
	
	
	document.getElementById('divEditPaid').style.display="";
	
	document.getElementById('idEditPaid').value = id;
	document.getElementById('typeInscrEdit').value = type;
	document.getElementById('nameEditPaid').value = nom;
	document.getElementById('isLA').value = isLA;
	
	if(type=="Adh"){
		document.getElementById('tabEditPaidNom').innerHTML = lstInscrAdhJS[i][0];
		
		document.getElementById('fullPaidEdit').checked = lstInscrAdhJS[i][1]==1?true:false;
		document.getElementById('paidEdit').value = lstInscrAdhJS[i][2];
		document.getElementById('recuEdit').value = lstInscrAdhJS[i][2]==0 ? "" :lstInscrAdhJS[i][3];
		
		listOptionsChecked = lstInscrAdhJS[i][4].split("//");
		
		
		//Consentements
		<?php if(!empty($listeConsentements)) { ?>
		
		listConsentChecked = lstInscrAdhJS[i][5].split("//");
		
		
		for(var cons=0; cons<(tabConsentementsJS.length); cons++){

			document.getElementById('caseConsentEdit-'+tabConsentementsJS[cons]).checked = false;
			document.getElementById('caseConsentEdit-'+tabConsentementsJS[cons]).disabled = false;
			document.getElementById('tdCaseConsentEdit-'+tabConsentementsJS[cons]).style.cursor = "pointer";

			if(listConsentChecked.indexOf(tabConsentementsJS[cons].toString()) > -1){
				
				document.getElementById('caseConsentEdit-'+tabConsentementsJS[cons]).checked = true;
				
				if(tabConsentementsObligJS.indexOf(tabConsentementsJS[cons]) > -1){
					
					document.getElementById('caseConsentEdit-'+tabConsentementsJS[cons]).disabled = true;
					document.getElementById('tdCaseConsentEdit-'+tabConsentementsJS[cons]).style.cursor = "default";
				}
			}
		}
		
		document.getElementById('divConsentEdit').style.display="";
		
		<?php } ?>
		
		
	}else if(type=="ESN"){
		document.getElementById('tabEditPaidNom').innerHTML = lstInscrESNJS[i][0];
		document.getElementById('fullPaidEdit').checked = lstInscrESNJS[i][1]==1?true:false;
		document.getElementById('paidEdit').value = lstInscrESNJS[i][2];
		document.getElementById('recuEdit').value = lstInscrESNJS[i][2]==0 ? "" :lstInscrESNJS[i][3];
		
		listOptionsChecked = lstInscrESNJS[i][4].split("//");
	}
	
	if(!!document.getElementById('tbodyOptionsEdit')){ //Y'a des options?
		var tbodyOptions = document.getElementById('tbodyOptionsEdit').childNodes;
		
		for(var opt=0; opt<(listOptionsChecked.length-1); opt++){
			selectOpt(parseInt(listOptionsChecked[opt]), true);
		}
	}
	
	document.getElementById('sommeDejaPayee').innerHTML =  document.getElementById('paidEdit').value + "€";
	
	affPaiement(true);
}



function annulEditPaid(){

	document.getElementById('divInscr').style.display="";
	<?php echo(!empty($lstAttente))?'document.getElementById(\'divAttente\').style.display="";':""; ?>
	
	document.getElementById('divEditPaid').style.display="none";
	
	document.getElementById('idEditPaid').value = "";
	document.getElementById('typeInscrEdit').value = "";
	document.getElementById('nameEditPaid').value = "";
	document.getElementById('tabEditPaidNom').innerHTML = "";
	document.getElementById('fullPaidEdit').checked = false;
	document.getElementById('paidEdit').value = "";
	document.getElementById('recuEdit').value = "";
	document.getElementById('isLA').value = "";
	
	//Consentements
	<?php if(!empty($listeConsentementsEdit)) { ?>
	document.getElementById('divConsentEdit').style.display="none";
	<?php } ?>
	
	
	if(!!document.getElementById('tbodyOptionsEdit')){ //Y'a des options?
		var tbodyOptions = document.getElementById('tbodyOptionsEdit').childNodes;
		for(var opt=0; opt<(tbodyOptions.length); opt++){
			tbodyOptions[opt].className="";
			document.getElementById('tdSelectOptEdit'+opt).className="checkN";
		}
	}
	
	document.getElementById('sommeDejaPayee').innerHTML = "";
	
	affPaiement(true);
}


function submInscrAttente(id,name){
	document.getElementById('cellInscrAttente'+id).onclick="";
	document.getElementById('idInscrAttente').value = id;
	document.getElementById('nameInscrAttente').value = name;
	document.getElementById('formInscrAttente').submit();
}

function submRembours(id,name){
	document.getElementById('cellRembours'+id).onclick="";
	document.getElementById('idRembours').value = id;
	document.getElementById('nameRembours').value = name;
	document.getElementById('formRembours').submit();
}

function submDesist(id, name){
	if(confirm("Confirmez-vous le désistement de "+name+" ?")){
		document.getElementById('cellDesist'+id).onclick="";
		document.getElementById('idDesist').value = id;
		document.getElementById('nameDesist').value = name;
		document.getElementById('formDesist').submit();
	}
}
</script>
<?php } ?>
<?php
echo $footer;
?>