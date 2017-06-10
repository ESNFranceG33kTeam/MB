<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

define('TITRE_PAGE',"Planning des permanences");


//Verif affichage du samedi
if($tabChamps['permSaturday']['valeur']=='Oui'){
	$addSaturday = '1';
}else{
	$addSaturday = '0';
}


//MAJ des présences
if(isset($_POST['editPerm'])){

	$bd = db_connect();
	//Suppression des entrées

	$supPerm = db_exec($bd, "
						DELETE FROM membres_permanences
						WHERE nom = '".PRENOM." ".NOM."'");

	if($supPerm === false){
		db_close($bd);
		die();
	}


	//formatage des données + ajout BDD
	$tabPerm = explode('//',$_POST['editPerm'],-1);
	
	for($i=0; $i< count($tabPerm); $i++){
	
		$datePerm = explode('-',$tabPerm[$i],2);
	
		$addPerm = db_exec($bd, "
						INSERT INTO membres_permanences(idJour, heure, nom)
						VALUES(".$datePerm[0].", ".$datePerm[1].", '".PRENOM." ".NOM."')");
	
		if($addPerm === false){
			db_close($bd);
		die();
		}
	}
	
	db_close($bd);

}

//Récupération liste des jours

$thisWeekMonday = ((date("w", time())==1)?date("Y-m-d", strtotime("this monday")):date("Y-m-d", strtotime("last monday")));
$dateMondayLastWeek = date("Y-m-d", strtotime($thisWeekMonday. "-1 week"));

$dateDay = $dateMondayLastWeek;
$idDay = date("z", strtotime($dateMondayLastWeek));

$tabDays = array();
$jours =array("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
$querySupprBDD = "";

for ($i=0; $i<21; $i++){

	array_push($tabDays, array('idDay' => $idDay, 'idWeekDay' => date("N", strtotime($dateDay)), 'dateDay' => $jours[intval(date("w", strtotime($dateDay)))]." ".date("j", strtotime($dateDay))));
	
	$querySupprBDD .= " idJour != ".$idDay;
	if($i != 20){
		$querySupprBDD .= " AND";
	}
	
	$dateDay = date("Y-m-d", strtotime($dateDay. "+1 day"));
	$idDay = date("z", strtotime($dateDay));

}


//BDD
$bd = db_connect();
	//Suppression journées périmées

	$supDays = db_exec($bd, "
						DELETE FROM membres_permanences
						WHERE ".$querySupprBDD);

	if($supDays === false){
		db_close($bd);
		die();
	}
	
	
	//Récupération des données
	$perm = db_tableau ($bd, "SELECT * FROM membres_permanences ORDER BY idJour ASC, heure ASC, nom ASC");
	db_close($bd);
		if($perm === false){
			die();
		}


//mise en forme tableau permanences
$permanences = array();
$tempIDJour = -1;
$tempIDHeure = -1;
$presencesMe = "";	


for($i=0; $i<count($perm); $i++){

	
	$isMe = ($perm[$i]['nom'] == PRENOM.' '.NOM);
	$nom = ($isMe)?'<span><b>'.$perm[$i]['nom'].'</b></span>':$perm[$i]['nom'];
	
	$presencesMe .= ($isMe)?$perm[$i]['idJour']."-".$perm[$i]['heure']."//":"";
	
	
	if($perm[$i]['idJour'] == $tempIDJour){
	
		if($perm[$i]['heure'] == $tempIDHeure){
			
			$permanences[intval($perm[$i]['idJour'])][intval($perm[$i]['heure'])]['nb'] ++;
			$permanences[intval($perm[$i]['idJour'])][intval($perm[$i]['heure'])]['noms'] .= '<br/>'.$nom;
	
		}else{
			
			$permanences[intval($perm[$i]['idJour'])][intval($perm[$i]['heure'])] = array('nb' => 1, 'noms' => $nom);

			$tempIDHeure = $perm[$i]['heure'];
		}
		
	}else{
	
		$permanences[intval($perm[$i]['idJour'])] = array();
		$permanences[intval($perm[$i]['idJour'])][intval($perm[$i]['heure'])] = array('nb' => 1, 'noms' => $nom);
	
		$tempIDJour = $perm[$i]['idJour'];
		$tempIDHeure = $perm[$i]['heure'];
	}

}
//Ouverture du local
$divOuverture='<div style="float:left; width:50%"><u>Ouverture cette semaine :</u><br />';
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
$divOuverture .= $ouverture . '</div><div style="clear: both;"></div>';

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>


<h3>Horraires d'ouverture</h3>
<div class="blocText">
<?php echo $divOuverture; ?>
</div>

<h3>Planning</h3>
<div id="divSetSemaine" class="blocText">
<a onclick="changeSem('pre')" >Afficher la semaine précédente</a><a onclick="changeSem('suiv')" style="margin-left:30px" >Afficher la semaine prochaine</a>
</div>

<table>
	<?php
	
		for($tab=0; $tab<3; $tab++){
			
			if($tab==0){
				echo '<tbody id="tabPrevious" style="display:none; opacity:0">';
			
			}elseif($tab==1){
				echo '</tbody><tbody id="tabNow"';
			
			}elseif($tab==2){
				echo '</tbody><tbody id="tabNext" style="display:none; opacity:0">';			
			
			}
			
			for($li=0; $li<12; $li++){
			
				if($li==0){
					echo '<tr><th></th>';
				}else{
					echo '<tr><td style="height:50px">'.($li+7).'h à '.($li+8).'h</td>';
				}
				
				
				for($col=0; $col<(5+$addSaturday); $col++){
					
					
					if($li==0){
						echo '<th style="width:110px">'.$tabDays[$col+($tab*7)]['dateDay'].'</th>';
					
					}else{
					
						$attributs = 'id="td'.$tabDays[$col+($tab*7)]['idDay'].'-'.($li+7).'" onclick="editPerm('.$tabDays[$col+($tab*7)]['idDay'].','.($li+7).')" style="font-size:10pt; line-height:11pt; cursor:pointer"';
					
					
						if(isset($permanences[$tabDays[$col+($tab*7)]['idDay']][$li+7]['noms'])){
						

							if($permanences[$tabDays[$col+($tab*7)]['idDay']][$li+7]['nb'] == 1){
								
								echo '<td '.$attributs.' class="orange">';
							
							}else{
							
								echo '<td '.$attributs.' class="green">';
							
							}
	
							echo $permanences[$tabDays[$col+($tab*7)]['idDay']][$li+7]['noms'].'</td>';
						
						}else{
							echo '<td '.$attributs.' class="red"></td>';
						}

					}
				}
				
				echo '</tr>';
			}
			
			echo '</tbody>';
		}
	?>

</table>
<form method=post action="permanences.php" id="formEditPerm">
	<input type="button" onclick="submEditPerm()" id="submitPerm" value="valider les modifications" />
	<input type="hidden" id="editPerm" name="editPerm" value="<?php echo $presencesMe ?>" />
</form>


<script type="text/javascript">


function changeSem(sem){

	
	if(sem=="pre"){
		toBeShown = "tabPrevious";
		textDiv = '<a onclick="changeSem(\'now\')">Afficher la semaine suivante</a>';
	
	}else if(sem=="now"){
		toBeShown = "tabNow";
		textDiv = '<a onclick="changeSem(\'pre\')">Afficher la semaine précédente</a><a onclick="changeSem(\'suiv\')" style="margin-left:30px">Afficher la semaine suivante</a>';
	
	}else if(sem=="suiv"){
		toBeShown = "tabNext";
		textDiv = '<a onclick="changeSem(\'now\')">Afficher la semaine précédente</a>';
	}
	
	
	if (document.getElementById('tabPrevious').style.display != "none"){
		toBeHidden = "tabPrevious";
		
	}else if (document.getElementById('tabNow').style.display != "none"){
		toBeHidden = "tabNow";
	
	}else if (document.getElementById('tabNext').style.display != "none"){
		toBeHidden = "tabNext";
	
	}
	
		document.getElementById(toBeHidden).style.opacity = 0;
		setTimeout(function(){document.getElementById(toBeHidden).style.display = "none";},150)
		setTimeout(function(){document.getElementById(toBeShown).style.display = "";},150)
		setTimeout(function(){document.getElementById(toBeShown).style.opacity = 1;},250)
		document.getElementById('divSetSemaine').innerHTML = textDiv;

}




function editPerm(idJour, idHeure){
	
	tagsTD = document.getElementById('td'+idJour+"-"+idHeure).childNodes;
	
	if(document.getElementById('td'+idJour+"-"+idHeure).innerHTML.indexOf("<?php echo (PRENOM." ".NOM) ?>") == -1){
	
		if(tagsTD.length == 0){
			document.getElementById('td'+idJour+"-"+idHeure).innerHTML += "<span><b><?php echo (PRENOM." ".NOM) ?></b></span>";
			document.getElementById('td'+idJour+"-"+idHeure).className = "orange";
			
		
		}else if(tagsTD.length > 0){
			document.getElementById('td'+idJour+"-"+idHeure).innerHTML += "<br/><span><b><?php echo (PRENOM." ".NOM) ?></b></span>";
			document.getElementById('td'+idJour+"-"+idHeure).className = "green";
		}
	
		document.getElementById('editPerm').value += idJour+"-"+idHeure+"//";

	}else{
	

		for(i=0; i<tagsTD.length; i++){
		
			if(tagsTD[i].nodeName == "SPAN"){

				if(i==0){
					tagsTD[i].parentNode.removeChild(tagsTD[i]);
					if(tagsTD.length>0){
						tagsTD[i].parentNode.removeChild(tagsTD[i]);
					}
				
				}else{
					tagsTD[i-1].parentNode.removeChild(tagsTD[i-1]);
					if(tagsTD.length>0){
						tagsTD[i-1].parentNode.removeChild(tagsTD[i-1]);
					}
				}
			}
		}
		
		if(tagsTD.length == 0){
			document.getElementById('td'+idJour+"-"+idHeure).className = "red";
		
		}else if(tagsTD.length == 1){
			document.getElementById('td'+idJour+"-"+idHeure).className = "orange";
		}
		
		document.getElementById('editPerm').value = document.getElementById('editPerm').value.replace(idJour+"-"+idHeure+"//","");
	}
}


function submEditPerm(){
	document.getElementById('submitPerm').disabled=true;
	document.getElementById('submitPerm').value = "Patientez...";
	document.getElementById('submitPerm').onclick="";
	document.getElementById('formEditPerm').submit();
}


</script> 
<?php
echo $footer;
?>