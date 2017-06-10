<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

define('TITRE_PAGE',"Plannings");



//Suppr planning
if(isset($_POST['idSup'])){

	//Verif droits
	requireDroits("bureau");
		
	$bd = db_connect();
	
	$_POST['idSup'] = mysqli_real_escape_string($bd, $_POST['idSup']);
	
	$sup1 = db_exec($bd, "
				DELETE FROM membres_plannings_liste
				WHERE id='".$_POST['idSup']."'
				LIMIT 1");
				
	$sup2 = db_exec($bd, "
				DELETE FROM membres_plannings_intervalles
				WHERE idPlanning='".$_POST['idSup']."'");
	
	db_close($bd);
	
	if($sup1!==false && $sup2!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Le planning à bien été supprimé.'));
	}		
}//fin suppr planning



//MAJ des présences
if(isset($_POST['editPlanning'])){

	$bd = db_connect();
	//Suppression des entrées

	$supPlan = db_exec($bd, "
						DELETE FROM membres_plannings_inscrits
						WHERE nom = '".PRENOM." ".NOM."'");

	if($supPlan === false){
		db_close($bd);
		die();
	}

	//formatage des données + ajout BDD
	$tabPlan = explode('//',$_POST['editPlanning'],-1);

	for($i=0; $i< count($tabPlan); $i++){
	
		$valeurs = explode('-',$tabPlan[$i],3);

		if(empty($valeurs[1])){
			$valeurs[1] = "NULL";
		}

		if(count($valeurs) == 3){
			
			
			//Verif droits edit
			
			$droitPlanning = db_valeur ($bd, "SELECT planning.edit 
											FROM membres_plannings_intervalles AS intervalle
											LEFT JOIN membres_plannings_liste AS planning ON intervalle.idPlanning = planning.id
											WHERE intervalle.id =".$valeurs[0]."");

			if($droitPlanning === false || empty($droitPlanning)){
				db_close($bd);
				die();
			}

			requireDroits($droitPlanning);
			
	
			$addPlan = db_exec($bd, "
						INSERT INTO membres_plannings_inscrits(idIntervalle, idJour, creneau, nom)
						VALUES(".$valeurs[0].", ".$valeurs[1].", ".$valeurs[2].", '".PRENOM." ".NOM."')");
	
			if($addPlan === false){
				db_close($bd);
			die();
			}
			
			
		}
	}
	array_push($pageMessages, array('type'=>'ok', 'content'=>'Les modifications ont bien été enregistrées.'));
	db_close($bd);

}


//Récupération Liste plannings
	$bd = db_connect();
	$plannings = db_tableau ($bd, "SELECT * FROM membres_plannings_liste ORDER BY type ASC, id DESC");
	db_close($bd);
		if($plannings === false){
			die();
		}

//Récupération liste des jours

$thisWeekMonday = ((date("w", time())==1)?date("Y-m-d", strtotime("this monday")):date("Y-m-d", strtotime("last monday")));
$dateMondayLastWeek = date("Y-m-d", strtotime($thisWeekMonday. "-1 week"));

$dateDay = $dateMondayLastWeek;
$idDay = date("z", strtotime($dateMondayLastWeek));

$tabDays = array();
$jours =array("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
$querySupprBDD = "";

for ($i=0; $i<28; $i++){

	array_push($tabDays, array('idDay' => $idDay, 'idWeekDay' => date("N", strtotime($dateDay)), 'dateDay' => $jours[intval(date("w", strtotime($dateDay)))]." ".date("j", strtotime($dateDay))));
	
	$querySupprBDD .= " idJour != ".$idDay;
	if($i != 27){
		$querySupprBDD .= " AND";
	}
	
	$dateDay = date("Y-m-d", strtotime($dateDay. "+1 day"));
	$idDay = date("z", strtotime($dateDay));

}


//BDD
$bd = db_connect();
	//Suppression journées périmées

	$supDays = db_exec($bd, "
						DELETE FROM membres_plannings_inscrits
						WHERE idJour IS NOT NULL AND idJour != '' AND".$querySupprBDD);

	if($supDays === false){
		db_close($bd);
		die();
	}
	db_close($bd);
	

	
	
//Recup Inscrits
$bd = db_connect();
$inscrits = db_tableau ($bd, "SELECT * FROM membres_plannings_inscrits 
							ORDER BY idIntervalle ASC, idJour ASC, creneau ASC, nom ASC");
db_close($bd);
if($inscrits === false){
	die();
}

$tabInscrits = createTabInscrits($inscrits);


//Mise en forme liste + tableaux plannings

$lstPlannings = "";
$tabPlannings = "";
$initAffPlanning = "";
		
	for($i=0; $i<count($plannings); $i++){
		
		if(checkDroits($plannings[$i]['visibility'])){
			
			
			if(empty($initAffPlanning)){
				$initAffPlanning = 'changePlanning('.$plannings[$i]['id'].');sizeSpan();';
			}
			
			if(isset($_GET['idPlanning'])){
				if($_GET['idPlanning'] == $plannings[$i]['id']){
					$initAffPlanning = 'changePlanning('.$plannings[$i]['id'].');sizeSpan();';
				}
			}
			
			
			//LISTE
			if($plannings[$i]['visibility']=="probatoire"){
				$visible = "Tous les membres";
			}elseif($plannings[$i]['visibility']=="membre"){
				$visible = "Membres actifs";
			}elseif($plannings[$i]['visibility']=="bureau"){
				$visible = "Membres du bureau";
			}
			
			if($plannings[$i]['edit']=="probatoire"){
				$edit = "Tous les membres";
			}elseif($plannings[$i]['edit']=="membre"){
				$edit = "Membres actifs";
			}elseif($plannings[$i]['edit']=="bureau"){
				$edit = "Membres du bureau";
			}
			
			$lstPlannings .= '<tr><td id="tdLst-'.$plannings[$i]['id'].'"><div style="overflow:hidden; width:475px; font-weight:bold"><a onclick="changePlanning('.$plannings[$i]['id'].')">'.$plannings[$i]['nom'].'</a></div></td>'.
							'<td>'.$visible.'</td><td>'.$edit.'</td>'.
							(checkDroits("bureau")?'<td class="suppr" id="cellRemovePlanning'.$plannings[$i]['id'].'" onclick="supprPlanning('.$plannings[$i]['id'].')"></td>':'').'</tr>';
			
			
			
			//TABLEAU
			
				//Recup Intervalles
			
			$bd = db_connect();
			$intervalles = db_tableau ($bd, "SELECT * FROM membres_plannings_intervalles 
											WHERE idPlanning =".$plannings[$i]['id']."
											ORDER BY id ASC, jour ASC, debut ASC");

			if($intervalles === false || empty($intervalles)){
				db_close($bd);
				die();
			}
			

			//Construction tabIntervalles et tabInscrits
			
			$tabIntervalles = array();
			
			for($colInt=0; $colInt<count($intervalles); $colInt++){

				array_push($tabIntervalles, createColIntervalles($intervalles[$colInt]['debut'], $intervalles[$colInt]['fin'], $intervalles[$colInt]['intervalle']));				
			}
			
			
			//Création tableau
			
			
			$tabPlannings .= '<div id="planning-'.$plannings[$i]['id'].'" style="clear:both; display:none;opacity:0" class="anim">';
			
				if($plannings[$i]['type']=="infini"){

					
					for($div=0; $div<4; $div++){
						
						if($div==0){
							$tabPlannings .= '<div id="'.$plannings[$i]['id'].'-'.$div.'" style="clear:both; height:100%; display:none; opacity:0" class="anim">';
						
						}elseif($div==1){
							$tabPlannings .= '<div id="'.$plannings[$i]['id'].'-'.$div.'" style="clear:both" class="anim">';
						
						}elseif($div>1){
							$tabPlannings .= '<div id="'.$plannings[$i]['id'].'-'.$div.'" style="clear:both; display:none;opacity:0" class="anim">';			
						
						}

						
						for($jour=0; $jour < count($intervalles); $jour++){
						
							$tabPlannings .= '<div style="float:left; width:'.(100/count($intervalles)).'%;">';
							$tabPlannings .= '<table id="table-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$jour]['id'].'">
											<tbody>
											<th class="center" style="padding-left:3px; padding-right:3px">'.$tabDays[$intervalles[$jour]['jour']+($div*7)-1]['dateDay'].'</th>';

							
							for($li=0; $li<count($tabIntervalles[$jour]); $li++){
								
								
								if(checkDroits($plannings[$i]['edit'])){
									
									$idSpan = 'span-'.$intervalles[$jour]['id'].'-'.$tabDays[$intervalles[$jour]['jour']+($div*7)]['idDay'].'-'.$li;
								
									$attributs = '	id="td-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$jour]['id'].'-'.($li).'" 
													onclick="editInscrits('.$plannings[$i]['id'].','.$div.','.$intervalles[$jour]['id'].','.($li).',\''.$idSpan.'\')" 
													style="font-size:10pt; line-height:11pt; cursor:pointer"';

								}else{
									
									$idSpan = '';
									
									$attributs = '	id="td-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$jour]['id'].'-'.($li).'"  
													style="font-size:10pt; line-height:11pt;';

								}


								if(isset($tabInscrits[$intervalles[$jour]['id'].'-'.$tabDays[$intervalles[$jour]['jour']+($div*7)]['idDay']][$li])){
									$tabNoms = $tabInscrits[$intervalles[$jour]['id'].'-'.$tabDays[$intervalles[$jour]['jour']+($div*7)]['idDay']][$li];
									
								}else{
									$tabNoms = array();
								}
							
								$noms = formatInscrits ($tabNoms, $idSpan);
								
								
								$tabPlannings .= '<tr><td '.$attributs.' class="'.$noms['class'].'">
													<div class="center" style="border-bottom:1px dotted #333; margin-bottom:4px;">'.$tabIntervalles[$jour][$li].'</div>
													<div id="div-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$jour]['id'].'-'.($li).'" 
														class="center" style="height:45px">'.$noms['noms'].'</div>
												</td></tr>';

							}
							
							$tabPlannings .= '</tbody></table>';
							$tabPlannings .= '<div id="divBoucheTrou-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$jour]['id'].'" style="height:0px; background-color:rgba(0,174,239,0.08); border-radius:6px">&nbsp;</div></div>';
						}
						$tabPlannings .= '</div>';
					}	

	
				}elseif($plannings[$i]['type']=="ponctuel"){
					
					//Calcul Nb Divs
					
					$nbDiv = ceil(count($intervalles)/2);

					if(count($intervalles) == 1){
						$taille = 100;
						$nbPeriodesDiv = 1;
						
					}else{
						$taille = 100/2;
						$nbPeriodesDiv = 2;
					}
					

					for($div=0; $div<$nbDiv; $div++){
						
						
				
						if($div==0){
							$tabPlannings .= '<div id="'.$plannings[$i]['id'].'-'.$div.'" style="clear:both" class="anim">';
						
						}else{
							$tabPlannings .= '<div id="'.$plannings[$i]['id'].'-'.$div.'" style="clear:both; height:100%; display:none; opacity:0" class="anim">';
						}
						
						
						for($periode=$div*2; $periode < $nbPeriodesDiv*($div+1); $periode++){
							
							if(isset($intervalles[$periode]['id'])){
						
						
								$deb = date_create($intervalles[$periode]['debut']);
								$fin = date_create($intervalles[$periode]['fin']);
								
								if(date_format($fin, 'H:i') == "00:00"){
								
									$fin = date_create();
									date_timestamp_set($fin, strtotime($intervalles[$periode]['fin'])-1440*60);
			
								}
			
								if(date_format($deb, 'd/m/Y') == date_format($fin, 'd/m/Y')){
									$entete = "Le " . $jours[intval(date_format($deb, 'w'))] . " ". date_format($deb, 'd/m/Y');
								}else{
									$entete = "Du " . $jours[intval(date_format($deb, 'w'))] . " ". date_format($deb, 'd/m/Y'). " au " . $jours[intval(date_format($fin, 'w'))] . " ". date_format($fin, 'd/m/Y');
								}
			
			
			
			
								$tabPlannings .= '<div style="float:left; width:'.$taille.'%">';
								$tabPlannings .= '<table id="table-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$periode]['id'].'">
												<tbody>
							<th class="center" style="padding-left:3px; padding-right:3px">'.$entete.'</th>';

								
								for($li=0; $li<count($tabIntervalles[$periode]); $li++){
									
									
									if(checkDroits($plannings[$i]['edit'])){
										
										$idSpan = 'span-'.$intervalles[$periode]['id'].'--'.$li;
									
										$attributs = '	id="td-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$periode]['id'].'-'.($li).'" 
														onclick="editInscrits('.$plannings[$i]['id'].','.$div.','.$intervalles[$periode]['id'].','.($li).',\''.$idSpan.'\')" 
														style="font-size:10pt; line-height:11pt; cursor:pointer"';

									}else{
										
										$idSpan = '';
										
										$attributs = '	id="td-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$periode]['id'].'-'.($li).'"  
														style="font-size:10pt; line-height:11pt;';

									}


									if(isset($tabInscrits[$intervalles[$periode]['id']][$li])){
										$tabNoms = $tabInscrits[$intervalles[$periode]['id']][$li];
										
									}else{
										$tabNoms = array();
									}
								
									$noms = formatInscrits ($tabNoms, $idSpan);
									
									
									$tabPlannings .= '<tr><td '.$attributs.' class="'.$noms['class'].'">
														<div class="center" style="border-bottom:1px dotted #333; margin-bottom:4px;">'.$tabIntervalles[$periode][$li].'</div>
														<div id="div-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$periode]['id'].'-'.($li).'" 
															class="center" style="height:45px">'.$noms['noms'].'</div>
													</td></tr>';
								}
								
							
								$tabPlannings .= '</tbody></table>';
								$tabPlannings .= '<div id="divBoucheTrou-'.$plannings[$i]['id'].'-'.$div.'-'.$intervalles[$periode]['id'].'" style="height:0px; background-color:rgba(0,174,239,0.08); border-radius:6px">&nbsp;</div></div>';
							
							}else{
								$tabPlannings .= '<div style="float:left; width:'.$taille.'%">';
								$tabPlannings .= '<table id="table-'.$plannings[$i]['id'].'-'.$div.'-'.$nbPeriodesDiv*($div+1).'"></table>';
								$tabPlannings .= '<div id="divBoucheTrou-'.$plannings[$i]['id'].'-'.$div.'-'.$nbPeriodesDiv*($div+1).'" style="height:0px; background-color:rgba(0,174,239,0.08); border-radius:6px">&nbsp;</div></div>';
							}
							
						}
						$tabPlannings .= '</div>';
					}

				}
			
			$tabPlannings .= '</div>';
		}
	}	



//Ouverture du local
/* $divOuverture='<div style="float:left; width:50%"><u>Ouverture cette semaine :</u><br />';
$ouverture = "";
$tempJour = "";
$tempHeure = "";

for($i=7; $i<(19+$addSaturday); $i++){

	if($i<(12+$addSaturday) || $i>13){ // on exclu les week ends
	
		if($i==14){ //Début semaine prochaine
			$divOuverture .= $ouverture;
			$divOuverture .= '</div><div style="float:left; width:50%"><u>Ouverture la semaine prochaine :</u><br />';
			$ouverture = "";
		}

		if(isset($permanences[$tabDays[$i]['idDay']])){ //S'il y a du monde à la perm ce jour, on regarde les horraires
			
			$tempJour = "";
			$tempHeure = "";
			
			for($h=8; $h<20; $h++){
			
			
				if(isset($permanences[$tabDays[$i]['idDay']][$h])){
				
					if(empty($tempHeure)){
						$tempHeure = "&nbsp;&nbsp;" . $h . "h"; //Initialisation de la plage horraire
					}
				
				}else{
				
					if(!empty($tempHeure)){
						$tempHeure .= "-" . $h . "h"; //fermeture de la plage horraire
						$tempJour .= $tempHeure;
						$tempHeure = "";
					}
				}
			}
			
			if(!empty($ouverture)){
				$ouverture .= "<br />";
			}
			
			$ouverture .= $tabDays[$i]['dateDay'] . "&nbsp;&nbsp;:" . $tempJour; // Affichage de la plage horraire
		
		
		}else{ // personne : local fermé
		
			if(!empty($ouverture)){
				$ouverture .= "<br />";
			}
			
			$ouverture .= $tabDays[$i]['dateDay'] . "&nbsp;&nbsp;:&nbsp;&nbsp;Fermé";
		
		}
	}
}
$divOuverture .= $ouverture . '</div><div style="clear: both;"></div>'; */

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<h3>Liste des plannings</h3>
<?php
if(!empty($tabPlannings)){

	echo '<table><tbody>';
	echo '<tr><th>Planning</th><th style="width:120px">Visible par</th><th style="width:120px">Peuvent s\'inscire</th></tr>';
	echo $lstPlannings;
	echo '</tbody></table>';
	
}else{
	echo "<br/>Aucun planning créé.<br/>";
}
	echo '<table><tbody><tr><td colspan=3><a href="http://'.$_SERVER['HTTP_HOST'].'/membres/addPlanning.php"><b>Créer un planning</b></a></td></tr></tbody></table>';
?>
<span id="tab"></span>
<form  method=post action="plannings.php" id="formRemovePlanning" style="display:none">
<input type="hidden" id="idSup" name="idSup" value=""/>
</form>

<?php
if(!empty($tabPlannings)){
?>

	<h3 id="h3Planning">Planning</h3>
	<div id="divPage" class="blocText">
	<a id="aPagePre" onclick="changeDiv('pre')" style="margin-right:30px">Afficher la page précédente</a>
	<span id="spanNoPre" style="margin-right:30px; color:#aaa">Afficher la page précédente</span>
	<span id="spanNumPages" >Page 1/4</span>
	<span id="spanNoSuiv" style="margin-left:30px; color:#aaa">Afficher la page suivante</span>
	<a id="aPageSuiv" onclick="changeDiv('suiv')" style="margin-left:30px">Afficher la page suivante</a>
	
	<a id ="aPrint" onclick="print()" target="_blank" style="float:right">
		<img src="../template/images/printer.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Version imprimable de la page
	</a>
	</div>

	<div id="Plannings" style="clear:both">
	

<?php
	echo $tabPlannings;
	echo '</div>';
	echo '<form id="formEditPlanning" method=post action="plannings.php" id="formEditPlanning" style="clear:both">
			<br/>
			<input type="button" onclick="submEditPlanning()" id="submitPlanning" value="valider les modifications" />
			<input type="hidden" id="editPlanning" name="editPlanning" value="" />
			</form>';
}
?>



<script type="text/javascript">


<?php echo $initAffPlanning ?>


function supprPlanning(id){
	if(confirm("Voulez-vous vraiment supprimer ce planning ?")){
		document.getElementById('cellRemovePlanning'+id).onclick="";
		document.getElementById('idSup').value = id;
		document.getElementById('formRemovePlanning').submit();
	}
}

function changePlanning(id){
	
	//Recup planning affiché
	
	idAffich= -1;
	
	tagsDivs = document.getElementsByTagName("DIV");

	for(i=0; i<tagsDivs.length; i++){
	
		if(tagsDivs[i].id.indexOf("planning-") != -1){
			
			if(tagsDivs[i].style.display == ""){
				idAffich= tagsDivs[i].id.replace("planning-","");
				break;
			}
		}
	}
	
	
	//Set Pages
	divsPlanning = document.getElementById("planning-"+id).childNodes;
	divAffiche = -1;
	
	for(i=0; i<divsPlanning.length; i++){
		
		if(divsPlanning[i].style.display == ""){
			divAffiche = i+1;
		}	
	}
	document.getElementById("spanNumPages").innerHTML = "Page " + divAffiche + "/" + divsPlanning.length;
	
	if(divAffiche == 1){
		document.getElementById("aPagePre").style.display = "none";
		document.getElementById("spanNoPre").style.display = "";
	}else{
		document.getElementById("aPagePre").style.display = "";
		document.getElementById("spanNoPre").style.display = "none";
	}
	if(divAffiche == divsPlanning.length){
		document.getElementById("aPageSuiv").style.display = "none";
		document.getElementById("spanNoSuiv").style.display = "";
	}else{
		document.getElementById("aPageSuiv").style.display = "";
		document.getElementById("spanNoSuiv").style.display = "none";
	}
	
	//Selection dans liste + chagement titre
	if(idAffich != -1){
		document.getElementById("tdLst-"+idAffich).className = "";
	}
	document.getElementById("tdLst-"+id).className = "green";
	document.getElementById("h3Planning").innerHTML = "Planning - " + document.getElementById("tdLst-"+id).childNodes[0].childNodes[0].innerHTML;

	
	//On masque le planning affiché et on affiche celui demandé
	
	if(idAffich != -1){
		document.getElementById("planning-"+idAffich).style.opacity = 0;
		setTimeout(function(){document.getElementById("planning-"+idAffich).style.display = "none";},150)
	}
	
	setTimeout(function(){document.getElementById("planning-"+id).style.display = "";},150)
	setTimeout(function(){sizeSpan();},150)
	setTimeout(function(){sizeBoucheTrou(id);},150)
	setTimeout(function(){document.getElementById("planning-"+id).style.opacity = 1;},250)

	//setTimeout(function(){self.location.hash="#tab";},400)
	
	
	//Changement URL
	if(window.history.replaceState){
		window.history.replaceState('Object', 'Title', '/planning-'+id);
	}
	document.getElementById("formEditPlanning").action = "http://<?php echo $_SERVER['HTTP_HOST'] ?>/planning-"+id;
	document.getElementById("formRemovePlanning").action = "http://<?php echo $_SERVER['HTTP_HOST'] ?>/planning-"+id;
}



function changeDiv(sens){

	
	//Recup planning + div affiché
	
	var idPlanning = -1;
	var idDiv = -1;
	var nbDivs = -1;
	
	tagsDivs = document.getElementsByTagName("DIV");

	for(i=0; i<tagsDivs.length; i++){
		
		if(tagsDivs[i].id.indexOf("planning-") != -1 && tagsDivs[i].style.display == ""){
			
			idPlanning = tagsDivs[i].id.replace("planning-", "");
			divsPlanning = tagsDivs[i].childNodes;
			nbDivs = divsPlanning.length;
			
			for(j=0; j<divsPlanning.length; j++){
				if(divsPlanning[j].style.display == ""){
					idDiv = j;
					break;
				}
			}		
		}
	}
	

	if(sens == "pre"){
		delta = -1;
	}else if(sens=="suiv"){
		delta = 1;
	}else{
		delta = 0;
	}
	
	document.getElementById("spanNumPages").innerHTML = "Page " + (idDiv+1+delta) + "/" + nbDivs;
	
	if((idDiv+delta) == 0){
		document.getElementById("aPagePre").style.display = "none";
		document.getElementById("spanNoPre").style.display = "";
	}else{
		document.getElementById("aPagePre").style.display = "";
		document.getElementById("spanNoPre").style.display = "none";
	}
	if((idDiv+delta) == (nbDivs-1)){
		document.getElementById("aPageSuiv").style.display = "none";
		document.getElementById("spanNoSuiv").style.display = "";
	}else{
		document.getElementById("aPageSuiv").style.display = "";
		document.getElementById("spanNoSuiv").style.display = "none";
	}
	

	
	//On masque le div affiché et on affiche celui demandé
	
	if(idDiv != -1){
		document.getElementById(idPlanning+"-"+idDiv).style.opacity = 0;
		setTimeout(function(){document.getElementById(idPlanning+"-"+idDiv).style.display = "none";},150)
	}
	setTimeout(function(){document.getElementById(idPlanning+"-"+(idDiv+delta)).style.display = "";},150)
	setTimeout(function(){sizeSpan();},150)
	setTimeout(function(){sizeBoucheTrou(idPlanning);},150)
	setTimeout(function(){document.getElementById(idPlanning+"-"+(idDiv+delta)).style.opacity = 1;},250)

}




function editInscrits(idPlanning, idDiv, idIntervalle, idHeure, idSpan){
	
	tagsDIV = document.getElementById("div-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).childNodes;
	
	
	if(document.getElementById("div-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).innerHTML.indexOf("<?php echo (PRENOM." ".NOM) ?>") == -1){
		
		taille = document.getElementById("div-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).offsetWidth;
	
	
		if(tagsDIV.length == 0){
			document.getElementById("div-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).innerHTML += '<span id="'+idSpan+'" class="spanNom" style="float:left; width:'+taille+'px; overflow:hidden; white-space:nowrap"><b><?php echo (PRENOM.' '.NOM) ?></b></span>';
			document.getElementById("td-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).className = "orange";
			
		
		}else if(tagsDIV.length > 0){
			document.getElementById("div-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).innerHTML += '<span id="'+idSpan+'" class="spanNom" style="float:left; width:'+taille+'px; overflow:hidden; white-space:nowrap"><b><?php echo (PRENOM.' '.NOM) ?></b></span>';
			document.getElementById("td-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).className = "green";
		}
	

	}else{
	

		for(i=0; i<tagsDIV.length; i++){

			if(tagsDIV[i].nodeName == "SPAN" && tagsDIV[i].id.indexOf("span-") != -1){

				tagsDIV[i].parentNode.removeChild(tagsDIV[i]);
			}
		}
		
		if(tagsDIV.length == 0){
			document.getElementById("td-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).className = "red";
		
		}else if(tagsDIV.length == 1){
			document.getElementById("td-"+idPlanning+"-"+idDiv+"-"+idIntervalle+"-"+idHeure).className = "orange";
		}
		

	}
	
	sizeBoucheTrou(idPlanning)
}


function sizeSpan(){
	// Maj de la longueur des span plannings 

	tagsSPAN = document.getElementsByClassName("spanNom");
	for(i=0; i<tagsSPAN.length; i++){
		tagsSPAN[i].style.width = tagsSPAN[i].parentNode.offsetWidth + "px";
	}

}


function sizeBoucheTrou(idPlanning){
	
	
	divsPlanning = document.getElementById("planning-"+idPlanning).childNodes;
	
	for(div=0; div<divsPlanning.length; div++){
		

		var maxHeight = 0;
		var divChild = document.getElementById(idPlanning+"-"+div).childNodes;

		for(var i = 0; i < divChild.length; i++){
			
			var innerDiv = divChild[i].childNodes;

			for(var j = 0; j < innerDiv.length; j++){

				if(innerDiv[j].id.indexOf("table") != -1){
					
					if(innerDiv[j].offsetHeight > maxHeight){
						maxHeight = innerDiv[j].offsetHeight;
					}
				} 
			}
		}


		for(var i = 0; i < divChild.length; i++){
			
			var innerDiv = divChild[i].childNodes;
			
			for(var j = 0; j < innerDiv.length; j++){
				
				if(innerDiv[j].id.indexOf("divBoucheTrou") != -1){
					
					var identifiant = innerDiv[j].id.replace("divBoucheTrou-", "");

					if(maxHeight == document.getElementById("table-"+identifiant).offsetHeight){
						innerDiv[j].style.display = "none";
						
					}else{
						
						innerDiv[j].style.height = (maxHeight - document.getElementById("table-"+identifiant).offsetHeight) +"px";
						innerDiv[j].style.display = "";
						
					}
					
					
				}
			}
		}
	}
}

function print(){
	
	//Recup planning + div affiché
	
	var idPlanning = -1;
	var idDiv = -1;

	
	tagsDivs = document.getElementsByTagName("DIV");

	for(i=0; i<tagsDivs.length; i++){
		
		if(tagsDivs[i].id.indexOf("planning-") != -1 && tagsDivs[i].style.display == ""){
			
			idPlanning = tagsDivs[i].id.replace("planning-", "");
			divsPlanning = tagsDivs[i].childNodes;
			nbDivs = divsPlanning.length;
			
			for(j=0; j<divsPlanning.length; j++){
				if(divsPlanning[j].style.display == ""){
					idDiv = j;
					break;
				}
			}		
		}
	}
	
	window.open("http://<?php echo $_SERVER['HTTP_HOST'] ?>/membres/printPlanning.php?idPlanning="+idPlanning+"&idDiv="+idDiv, '_blank');

	
}


function submEditPlanning(){
	
	tagsSPAN = document.getElementsByTagName("SPAN");
	
	for(i=0; i<tagsSPAN.length; i++){
		
		if(tagsSPAN[i].id.indexOf("span-") != -1){
			
			if(tagsSPAN[i].innerHTML.indexOf("<?php echo (PRENOM." ".NOM) ?>") != -1){

				document.getElementById("editPlanning").value += (tagsSPAN[i].id.replace("span-","") + "//");
			
			}
		}
	}

	document.getElementById('submitPlanning').disabled=true;
	document.getElementById('submitPlanning').value = "Patientez...";
	document.getElementById('submitPlanning').onclick="";
	document.getElementById('formEditPlanning').submit();
}


</script> 
<?php

function createColIntervalles($debut, $fin, $intervalle){
	
	$tabIntervalles = array();

	
	if(strlen($debut)==5 && strlen($fin)==5){
		
		$hDeb = explode(':',$debut,2);
		$hFin = explode(':',$fin,2);

		$debut = intval($hDeb[0]*60 + intval($hDeb[1]));	
		$fin = intval($hFin[0]*60 + intval($hFin[1]));
		if($fin == 0) {$fin == intval(1440);}
		
		
		for($i = $debut; $i<$fin; $i += $intervalle){
			
			array_push($tabIntervalles, intToTime($i)." à ". intToTime($i+$intervalle));
			
		}
		
		
		
	}elseif(strlen($debut)==16 && strlen($fin)==16){
		
		
		$debut = strtotime($debut);
		$fin = strtotime($fin);
		
		
		for($i = $debut; $i<$fin; $i += $intervalle*60){
			
			$date1 = date_create();
			date_timestamp_set($date1, $i);
			$date2 = date_create();
			date_timestamp_set($date2, $i+$intervalle*60);
			
						
			if(date_format($date1, 'd/m/Y') == date_format($date2, 'd/m/Y') || ($intervalle < 1440 && date_format($date2, 'H:i') == "00:00")){
				
				array_push($tabIntervalles, "Le " . date_format($date1, 'd/m/Y \d\e H:i')." à ". date_format($date2, 'H:i'));
				
			}elseif($intervalle == 1440 && date_format($date1, 'H:i') == "00:00"){	
				
				array_push($tabIntervalles, "Le " . date_format($date1, 'd/m/Y')." Toute la journée");
				
			}else{
				
				array_push($tabIntervalles, "Du " . date_format($date1, 'd/m/Y \à H:i')." au ". date_format($date2, 'd/m/Y \à H:i'));
			}
		}
		
		
		
		
	}else{
		die();
	}
	
	return $tabIntervalles;
	
}


function intToTime($nombre){
	
	if ($nombre==1440){
		return "0h00";
	
	}else{
		
		$h = floor($nombre / 60);
		$min = round(($nombre % 60)*60);
		if ($min < 10){
			return $h."h0".$min;
			
		}else{
			return $h."h".$min;
		}
		
	}

}

function createTabInscrits($inscrits){
	
	$tabInscrits = array();
	$tempIntervalle = -1;
	$tempCreneau = -1;

	for($i=0; $i<count($inscrits); $i++){

		$intervalle = (empty($inscrits[$i]['idJour']))?$inscrits[$i]['idIntervalle']:$inscrits[$i]['idIntervalle'].'-'.$inscrits[$i]['idJour'];
		
		$nom = $inscrits[$i]['nom'];

		
		if($intervalle == $tempIntervalle){
		
			if($inscrits[$i]['creneau'] == $tempCreneau){
				
				array_push($tabInscrits[$intervalle][intval($inscrits[$i]['creneau'])], $nom);
		
			}else{
				
				$tabInscrits[$intervalle][intval($inscrits[$i]['creneau'])] = array($nom);
				$tempCreneau = $inscrits[$i]['creneau'];
			}
			
		}else{
		
			$tabInscrits[$intervalle] = array();
			$tabInscrits[$intervalle][intval($inscrits[$i]['creneau'])] = array($nom);
		
			$tempIntervalle = $intervalle;
			$tempCreneau = $inscrits[$i]['creneau'];
		}
	}
	
	return $tabInscrits;
}

function formatInscrits ($tabNoms, $idSpan){
	
	$noms = array('noms' => "", 'class' => "");
	
	for($i=0; $i<count($tabNoms); $i++){
		
		if($tabNoms[$i] == PRENOM." ".NOM){
			$noms['noms'] .= '<span id="'.$idSpan.'" class="spanNom" style="float:left; overflow:hidden; white-space:nowrap"><b>'.$tabNoms[$i].'</b></span>';
			
		}else{
			$noms['noms'] .= '<span class="spanNom" style="float:left; overflow:hidden; white-space:nowrap">'.$tabNoms[$i].'</span>';
		}
	}
	
	if(empty($tabNoms)){
		$noms['class'] = "red";
		
	}elseif(count($tabNoms) == 1){
		$noms['class'] = "orange";
		
	}else{
		$noms['class'] = "green";
	}
	
	return $noms;
}


echo $footer;
?>