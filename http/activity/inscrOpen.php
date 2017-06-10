<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');
$affMenu=true;

$postCarteESN="";

//VERIF CODE D'ACCES
$acces=false;
if(isset($_GET['code'])&&!empty($_GET['code'])){

	$bd = db_connect();
	
	$_GET['code'] = mysqli_real_escape_string($bd, $_GET['code']);
	
	$infosAct = db_ligne($bd, "
								SELECT id, nom, dte, tme, infos, spots, spotsSold, prix, paiementStatut, code, consent
								FROM activity_activities
								WHERE code='".$_GET['code']."'");
								
	$tabOptions = db_tableau($bd, "
						SELECT id, opt, prixOpt
						FROM activity_options
						WHERE idAct='".$infosAct['id']."'
						ORDER BY id ASC");
						
	$consentements = db_tableau($bd, "		
					SELECT id, obligatoire, defaut, texte, texteCase
					FROM gestion_consentements
					WHERE cible=2 OR cible=3
					ORDER BY id ASC");	
							
							
	db_close($bd);

	if(empty($infosAct) && $infosAct!==false){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Code de l\'activité invalide.'));
		define('TITRE_PAGE',"Inscription");
	}elseif($infosAct!==false){
		$acces=true;
		define('TITRE_PAGE',$infosAct['nom']);
		
		//mise en forme
		$mois = Array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
		
		$dte = explode('-',$infosAct['dte'],3);
		$infosAct['dateText']=(($dte[2]{0}=="0")?$dte[2]{1}:$dte[2]).' '.$mois[intval($dte[1])-1].' '.$dte[0];
		if(!empty($infosAct['tme'])){
			$infosAct['dateText'] .= '<br /><span style="font-size:0.85em">'.$infosAct['tme'].'</span>';
		}

		$spotsSold = explode('//',$infosAct['spotsSold'],2);
		$infosAct['spotsSold']=array($spotsSold[0],$spotsSold[1]);	
		
	}
}else{ // Pas de code fourni
	array_push($pageMessages, array('type'=>'err', 'content'=>'Code de l\'activité invalide.'));
	define('TITRE_PAGE',"Inscription");
}

if($acces){
	
	$reselect="";

	if(isset($_POST['carteESN'])){
		
		
		//Calcul prix total avec options
			$prixTotal = $infosAct['prix'];

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
			
			//Init consentements apres POST pour conserver les choix + Verif consentements obligatoires

			if($consentements!==false && !empty($consentements) && !empty($infosAct['consent'])){
			
				
				$tabConsentAct = explode('///',$infosAct['consent'],-1);
				

				for($i=0; $i<count($consentements); $i++){
					
					if(in_array($consentements[$i]['id'],$tabConsentAct)){
					
					
						if(isset($_POST['caseConsent-'.$consentements[$i]['id']])){
							
							
							$reselect.='document.getElementById("caseConsent-'.$consentements[$i]['id'].'").checked=true;';
							
						}else{
							
							
							$reselect.='document.getElementById("caseConsent-'.$consentements[$i]['id'].'").checked=false;';

						}

						if($consentements[$i]['obligatoire'] && !isset($_POST['caseConsent-'.$consentements[$i]['id']])){
							array_push($pageMessages, array('type'=>'err', 'content'=>'Vous devez accepter les clauses obligatoires.'));
						}
					}
				}
			}
		
			
		//verifs
		if (empty($_POST['carteESN'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Numéro de carte ESN</em>.'));
			
			
		}else{

			if($prixTotal < 0){
				array_push($pageMessages, array('type'=>'err', 'content'=>"Le prix d'une activité ne peut pas être négatif."));
				
			}else{
		
				$bd = db_connect();
				
				$postCarteESN =  mysqli_real_escape_string($bd, $_POST['carteESN']);
				
				$membre = db_ligne($bd, "		
							SELECT prenom, nom, id, dateFinInscr
							FROM membres_adherents
							WHERE idESN='".$postCarteESN."'");
				db_close($bd);

				if($membre===false){die();}
				
				if(empty($membre)){
					array_push($pageMessages, array('type'=>'err', 'content'=>"La carte ESN n'est pas reconnue."));
				
				}elseif(date_create($membre['dateFinInscr']) < date_create($infosAct['dte'])){
						array_push($pageMessages,array('type'=>'err', 'content' => "La carte ESN de ".$membre['prenom']." ".$membre['nom']." ne sera plus valide au moment de l'activité."));
				}else{
				
					$bd = db_connect();
					$isInscr = db_ligne($bd, "		
										SELECT idAct
										FROM activity_participants
										WHERE idAct='".$infosAct['id']."' AND idAdh='".$membre['id']."'");
					db_close($bd);
					
					if($isInscr===false){die();}
					if(!empty($isInscr)){
						array_push($pageMessages, array('type'=>'err', 'content'=>$membre['prenom']." ".$membre['nom']." est déjà inscrit à cette activité."));
					}
					
					
					
					if(empty($pageMessages)){ //si pas d'erreur : go pour ajout
					
						if($infosAct['spots']!=0 && (intval($infosAct['spots'])-intval($spotsSold[0]))<= 0){
							$attente=1;
							$spotsSold[1] = intval($spotsSold[1])+1;
						}else{
							$attente=0;
							$spotsSold[0] = intval($spotsSold[0])+1;
						}
					
						$fullPaid=($prixTotal==0)?1:0;
						
						$tabPaiements = explode('//',$infosAct['paiementStatut'],2);

						
						if($fullPaid==0 && $attente==0){
							$tabPaiements[0]=intval($tabPaiements[0])+1;
						}
						//Ajout d'un remboursement probable (Supprimé car impossible de payer en inscription libre, meme si inscriptions libres ouvertes aux inscriptions payantes)
						
						//elseif($attente==1 && $actInscr['paid']>0){
							//$tabRemboursement = explode('/',$tabPaiements[1],2);	
							//$tabPaiements[1]=$tabRemboursement[0].'/'.(intval($tabRemboursement[1])+1);
						//}
						
						
						$bd = db_connect();
						$addParticipant = db_exec($bd, "
							INSERT INTO activity_participants(idAct, idAdh, paid, fullPaid, recu, listeAttente, dateInscr, inscrBy)
							VALUES('".$infosAct['id']."','".$membre['id']."','0','".$fullPaid."','0',
							'".$attente."',NOW(),'Inscription libre')");
							
							
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
										VALUES(".$membre['id'].",".$consentements[$i]['id'].",".$infosAct['id'].")
										ON DUPLICATE KEY UPDATE idAdh=idAdh");

								if($addConsent===false){die("Erreur ajout consentement.");}
							}
						}
							
							
						if($addParticipant!==false){

							$updateActivity = db_exec($bd, "
								UPDATE activity_activities
								SET spotsSold='".$spotsSold[0]."//".$spotsSold[1]."', paiementStatut='".$tabPaiements[0]."//".$tabPaiements[1]."'
								WHERE id='".$infosAct['id']."'");

							if($updateActivity!==false){
								

								if($infosAct['prix']!=0){
									
									array_push($pageMessages,array('type'=>'ok', 'content' => $membre['prenom']." ".$membre['nom']." a bien été inscrit. N'oubliez pas que vous devrez aller payer l'activité."));
									
								}else{
									
									array_push($pageMessages,array('type'=>'ok', 'content' => $membre['prenom']." ".$membre['nom']." a bien été inscrit."));
								}
								
								$reselect="";
								
								
								//Actualisation
								$infosAct['spotsSold']=array($spotsSold[0],$spotsSold[1]);	
								$postCarteESN="";
							}
						}	
						db_close($bd);					
					}
				}	
			}
		}
	}//fin verif post
	
	//Traitement tableau options
	
	$tableOptions = "";

	for($opt=0; $opt<count($tabOptions); $opt++){
		
		if($tabOptions[$opt]['prixOpt'] > 0 ){
			$textPrix = " (Supplément : " .$tabOptions[$opt]['prixOpt']. "€)";
			
		}else if($tabOptions[$opt]['prixOpt'] < 0){
			$textPrix = " (Réduction : " .$tabOptions[$opt]['prixOpt']. "€)";
			
		}else{
			$textPrix ="";
		}
		
		$tableOptions .= '<tr><td>'.$tabOptions[$opt]['opt'].'<div style="float:right">'.$textPrix.'</div>'.
							'<input type="hidden" id="idOpt'.$opt.'"  value="'.$tabOptions[$opt]['id'].'"/>'.
							'<input type="hidden" id="prixOpt'.$opt.'" value="'.$tabOptions[$opt]['prixOpt'].'"/>'.
							'</td>'.
							'<td id="tdSelectOpt'.$opt.'" onclick="selectOpt('.$opt.')" class="checkN" style="width:90px"></td></tr>';

	}
	
	
		//Mise en forme case consentements
	
	$tabConsentementsObligJS="";
	$listeConsentements="";

	if($consentements!==false && !empty($consentements) && !empty($infosAct['consent'])){
	
		$tabConsentAct = explode('///',$infosAct['consent'],-1);
		$listeConsentements='<div id="divConsent" style="padding:0; margin-top:5px; width:88%"><table style="width:100%; margin:0">';


		for($i=0; $i<count($consentements); $i++){
			
			if(in_array($consentements[$i]['id'],$tabConsentAct)){
			
				$listeConsentements.='<tr><td style="padding-left8px; cursor:pointer" onclick="checkCaseConsent('.$consentements[$i]['id'].')"><input type="checkbox" id="caseConsent-'.$consentements[$i]['id'].'" name="caseConsent-'.$consentements[$i]['id'].'" '.(($consentements[$i]['defaut'])?'checked':'').'>
				<label class="checkbox '.(($consentements[$i]['obligatoire'])?'required':'').'" for="caseConsent-'.$consentements[$i]['id'].'" style="margin-bottom:6px; display:inline">'.$consentements[$i]['texteCase'].'.</label>(<a onclick="affConsent('.$consentements[$i]['id'].')" id="aConsent-'.$consentements[$i]['id'].'">afficher</a>)</div>
				<div id="divTextConsent-'.$consentements[$i]['id'].'" style="display:none;padding-left:4px; margin-bottom:10px; width:95%;">'.bbCodeToHTML($consentements[$i]['texte']).'</div></td></tr>';
				
				
				if($consentements[$i]['obligatoire']){
					$tabConsentementsObligJS.= 'tabConsentementsObligJS.push('.$consentements[$i]['id'].');';
				}
			}
		}
		
		$listeConsentements.='</table></div>';
	}
	
	
	
	
}//fin VERIF CODE

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<?php if($acces){ ?>

<table class="activity"><tbody>
	<?php if($infosAct['spots']==0){ ?>
		<tr><th rowspan=2 style="font-size:1.25em;"><?php echo $infosAct['dateText']; ?></th>
		<th style="width:150px"><b>Places</b></th>
		<th style="width:300px"><b>Inscrits</b></th>
		<th style="width:150px"><b>Prix</b></th></tr>
		
		<tr><td>Illimitées</td>
		<td><?php echo $infosAct['spotsSold'][0]; ?></td>
		<td><?php echo ($infosAct['prix']==0)?"Gratuit":$infosAct['prix']."€"; ?></td></tr>
	<?php }else{ ?>
		<tr><th rowspan=2 style="font-size:1.25em;"><?php echo $infosAct['dateText']; ?></th>
		<th style="width:150px"><b>Places</b></th>
		<th style="width:150px"><b>Places restantes</b></th>
		<th style="width:150px"><b>Inscrits</b></th>
		<th style="width:150px"><b>Prix</b></th></tr>
		
		<tr><td><?php echo $infosAct['spots']; ?></td>
		<td><?php echo ((intval($infosAct['spots'])-intval($infosAct['spotsSold'][0])>0)?(intval($infosAct['spots'])-intval($infosAct['spotsSold'][0])):'<font color="yellow">Complet</font>'); ?></td>
		<td><?php echo (($infosAct['spotsSold'][1]==0)?$infosAct['spotsSold'][0]:$infosAct['spotsSold'][0].' + '.$infosAct['spotsSold'][1].' en attente'); ?></td>
		<td><?php echo ($infosAct['prix']==0)?"Gratuit":$infosAct['prix']."€"; ?></td></tr>
	<?php } ?>
</tbody></table>

<h3>Informations</h3>
<div class="blocText">
<?php echo bbCodeToHTML($infosAct['infos']); ?>
</div>


<h3>Inscription</h3>
<form method=post id="formInscr" action="inscrAct-<?php echo $_GET['code']; ?>">

<input type="hidden" id="options" name="options" />

<?php if(!empty($tableOptions)){ ?>
	<table style="margin-top:5px; width:88%">
	<th>Options</th><th>Choix</th>
	<tbody id="tbodyOptions"><?php echo $tableOptions;  ?></tbody>
	</table>
<?php }?>

<?php if(!empty($listeConsentements)){ echo $listeConsentements;}?>

<table style="margin-top:5px; width:88%;"><tr><td>

	<table class="invisible" style="margin:0; width:100%; height:66px;"><tbody>
	
	<?php echo ($infosAct['spots']!=0&&(intval($infosAct['spots'])-intval($spotsSold[0]))<=0)?
		'<tr><td colspan=3 class="gras center"><img class="iconeAlert" src="../template/images/alert.png"/>Attention : vous serez inscrit en liste d\'attente. Vous serez contacté si une place se libère.<br/><br/></td></tr>':""
	?>
	
	<tr>
		<td style="text-align:center; width:28%">Somme à payer :<br/><span id="sommeDue" style="font-weight:bold"><?php echo $infosAct['prix']; ?>€</span></td>

		<td>
			<label for="carteESN">numéro de carte esn</label>
			<input type="text" id="carteESN" name="carteESN" maxlength=15 value="<?php echo $postCarteESN; ?>" />
		</td>

		<td style="min-width:120px"><center><input type="button" onclick="submInscr()" id="submitInscr" value="valider" style="margin-top:0;"/><center></td>
	</tr>
	</tbody></table>
	
</td></tr></table>

</form>

<script type="text/javascript">

<?php echo $reselect; ?>

var tabConsentementsObligJS=new Array();
<?php echo $tabConsentementsObligJS;?>

function selectOpt(opt){
	
	var tbodyOptions = document.getElementById('tbodyOptions').childNodes;
	options = "";
	
	
	if(tbodyOptions[opt].className != "selected"){
	
	
		prixTotal = <?php echo $infosAct['prix']; ?> + parseFloat(document.getElementById('prixOpt'+opt).value);
		
		
		

		for(i=0; i<(tbodyOptions.length); i++){
			if(tbodyOptions[i].className=="selected"){
				prixTotal += parseFloat(document.getElementById('prixOpt'+i).value);
				options += document.getElementById('idOpt'+i).value +"//";
			}
		}
		
		if(prixTotal >= 0){
			tbodyOptions[opt].className="selected";
			document.getElementById('tdSelectOpt'+opt).className="checkO";
			document.getElementById('sommeDue').innerHTML = prixTotal + "€";
			document.getElementById('options').value = options + document.getElementById('idOpt'+opt).value +"//";
			
			
		}else{
			
			alert("Le prix de l'activité ne peut pas être négatif.");
		}
	
	}else{
		
		tbodyOptions[opt].className="";
		document.getElementById('tdSelectOpt'+opt).className="checkN";
		prixTotal = <?php echo $infosAct['prix']; ?>;


		for(i=0; i<(tbodyOptions.length); i++){
			if(tbodyOptions[i].className=="selected"){
				prixTotal += parseFloat(document.getElementById('prixOpt'+i).value);
				options += document.getElementById('idOpt'+i).value +"//";
			}
		}
		
		if(prixTotal >= 0){

			document.getElementById('sommeDue').innerHTML = prixTotal + "€";
			document.getElementById('options').value = options;
			
			
		}else{
			
			alert("Le prix de l'activité ne peut pas être négatif.");
		}
	}
}



function affConsent(id){
	
	//Annulation click sur le td
	checkCaseConsent(id);
	
	if(document.getElementById('divTextConsent-'+id).style.display == "none"){
		document.getElementById('divTextConsent-'+id).style.display = "";
		document.getElementById('aConsent-'+id).innerHTML = "masquer";

	}else{
		document.getElementById('divTextConsent-'+id).style.display = "none";
		document.getElementById('aConsent-'+id).innerHTML = "afficher";
	}
	
	
}

function checkCaseConsent(id){
	
	
	if(document.getElementById('caseConsent-'+id).disabled == false){
	
		if(document.getElementById('caseConsent-'+id).checked == true){
			document.getElementById('caseConsent-'+id).checked = false;

		}else{
			document.getElementById('caseConsent-'+id).checked = true;
		}
	}
}




function submInscr(){
	
	
	if(parseFloat(document.getElementById('sommeDue').innerHTML.replace("€","")) > 0){
		if(!confirm("N'oubliez pas que vous devrez aller payer l'activité.")){
			return;
		}
	}
	
	//Verif consentements obligatoires
	for(var c=0;c<tabConsentementsObligJS.length;c++){
		if(document.getElementById('caseConsent-'+tabConsentementsObligJS[c]).checked==false){
			alert("Vous devez accepter les clauses obligatoires.");
			return;
		}
	}
	
	
	document.getElementById('submitInscr').disabled=true;
	document.getElementById('submitInscr').value = "Patientez...";
	document.getElementById('submitInscr').onclick="";
	document.getElementById('formInscr').submit();
	
	
}
</script> 

<?php } ?>
<?php
echo $footer;
?>