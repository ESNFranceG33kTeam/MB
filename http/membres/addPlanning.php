<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

define('TITRE_PAGE',"Ajouter un planning");



//nouveau planning
if(isset($_POST['nom'])){



	if(empty($_POST['nom'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom du planning</em>.'));
	}
	if(mb_strlen($_POST['nom'])>150){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom du planning</em> ne doit pas dépasser 150 caractères.'));
	}
	
	
	
	if(!($_POST['typePlan']=="infini" || $_POST['typePlan']=="ponctuel")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Type de planning</em> n\'est pas valide.'));
	}

	
	
	if(!($_POST['droitscanInscr']=="probatoire" || $_POST['droitscanInscr']=="membre" || $_POST['droitscanInscr']=="bureau")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant s\'inscrire au planning</em> n\'est pas valide.'));
	}

	if(!($_POST['droitsView']=="probatoire" || $_POST['droitsView']=="membre" || $_POST['droitsView']=="bureau")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le planning</em> n\'est pas valide.'));
	
	}elseif($_POST['droitscanInscr']=="probatoire" && $_POST['droitsView']!="probatoire"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le planning</em> n\'est pas valide.'));
	
	}elseif($_POST['droitscanInscr']=="membre" && $_POST['droitsView']=="bureau"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le planning</em> n\'est pas valide.'));
	
	}
	
	
	if(DROITS=="probatoire" && ($_POST['droitsView']=="membre" || $_POST['droitsView']=="bureau")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le planning</em> n\'est pas valide.'));
	
	}elseif(DROITS=="membre" && $_POST['droitsView']=="bureau"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le planning</em> n\'est pas valide.'));
	
	}
	

	$tabPeriodes = explode('//',$_POST['lstPeriodes'],-1);
	
	if(empty($tabPeriodes)){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez ajouter des périodes.'));
	}
	
	
	if($_POST['typePlan']=="infini"){
	
	
		for($i=0; $i<count($tabPeriodes); $i++){
			
			$tabDates = explode('@@',$tabPeriodes[$i],4);
			
			if(count($tabDates) == 4){
			
			
				if(!empty($tabDates[1]) && $tabDates[1]!="--:--"){
					$tme = $tabDates[1];
					if (!preg_match("/(2[0-3]|[01][0-9]):[0-5][0-9]/", $tme)){
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Heure de début</em> n\'est pas valide.'));
					}
					
				}else{
					array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Heure de début</em> n\'est pas valide.'));
				}
			
			
				if(!empty($tabDates[2]) && $tabDates[2]!="--:--"){
					$tme = $tabDates[2];
					if (!preg_match("/(2[0-3]|[01][0-9]):[0-5][0-9]/", $tme)){
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Heure de fin</em> n\'est pas valide.'));
					}
				}else{
					array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Heure de fin</em> n\'est pas valide.'));
				}

				
				if(empty($tabDates[3]) || !is_numeric($tabDates[3])){
					array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Intervalle</em> n\'est pas valide.'));
				}
			
			
				

			
				if(empty($pageMessages)){
					
					$hDeb = explode(':',$tabDates[1],2);
					$hFin = explode(':',$tabDates[2],2);
					
					if(is_numeric($hDeb[0]) && is_numeric($hDeb[1]) && is_numeric($hFin[0]) && is_numeric($hFin[1]) && $tabDates[3]>0 && $tabDates[3] <1441){
							
						$debut = intval($hDeb[0]*60 + intval($hDeb[1]));	
						$fin = intval($hFin[0]*60 + intval($hFin[1]));
						if($fin == 0) {$fin == intval(1440);}
				
						if($fin <= $debut || ($fin-$debut) % $tabDates[3] != 0 || ($fin-$debut) / $tabDates[3] > 200){
							array_push($pageMessages, array('type'=>'err', 'content'=>'Horraires incohérentes.'));
						}
						
					}else{
						array_push($pageMessages, array('type'=>'err', 'content'=>'Horraires incohérentes.'));
					}
				}
			
			}else{
				array_push($pageMessages, array('type'=>'err', 'content'=>'Périodes non valides.'));
			}
		}	
	
	
		
	}elseif($_POST['typePlan']=="ponctuel"){
		
		for($i=0; $i<count($tabPeriodes); $i++){
			
			$tabDates = explode('@@',$tabPeriodes[$i],3);
			
			
			if(count($tabDates) == 3){

			
				if(!empty($tabDates[0])){
					$dte = date_parse($tabDates[0]);
					if (!checkdate($dte['month'], $dte['day'], $dte['year']) && $dte['hour']>=0 && $dte['hour']<24 && $dte['minute']>=0 && $dte['minute']<60) {
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Date de début</em> n\'est pas valide.'));
					}	
				}
			
				if(!empty($tabDates[1])){
					$dte = date_parse($tabDates[1]);
					if (!checkdate($dte['month'], $dte['day'], $dte['year']) && $dte['hour']>=0 && $dte['hour']<24 && $dte['minute']>=0 && $dte['minute']<60) {
						array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Date de fin</em> n\'est pas valide.'));
					}	
				}
			
			
			
				if(empty($tabDates[2]) || !is_numeric($tabDates[2])){
					array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Intervalle</em> n\'est pas valide.'));
				}
			
			
			
				if(empty($pageMessages)){
					
					$debut = strtotime($tabDates[0]);
					$fin = strtotime($tabDates[1]);
					
					$deltaMin = intval($fin - $debut) / 60;

					echo $deltaMin." ".$deltaMin % $tabDates[2]." ".$deltaMin / $tabDates[2]." ";
					
					
					if($tabDates[2] <1 || $tabDates[2] > $deltaMin || $deltaMin < 1 || $deltaMin % $tabDates[2] != 0 || $deltaMin / $tabDates[2] > 200){
						array_push($pageMessages, array('type'=>'err', 'content'=>'Horraires incohérentes.'));
					}
			
				}else{
					array_push($pageMessages, array('type'=>'err', 'content'=>'Périodes non valides.'));
				}
			}
		}
	}
	
	
	if(empty($pageMessages)){
	
		
		$bd = db_connect();
		
		$_POST['nom'] = mysqli_real_escape_string($bd, $_POST['nom']);
		

		$addPlanning = db_exec($bd, "
					INSERT INTO membres_plannings_liste(nom, type, visibility, edit)
					VALUES('".$_POST['nom']."','".$_POST['typePlan']."','".$_POST['droitsView']."','".$_POST['droitscanInscr']."')");
		
		
		if($addPlanning!==false){
			
			$idPlanning = db_lastId($bd);
			
			for($i=0; $i<count($tabPeriodes); $i++){
	
				if($_POST['typePlan']=="infini"){
					
					$tabDates = explode('@@',$tabPeriodes[$i],4);
					
					$addIntervalle = db_exec($bd, "
								INSERT INTO membres_plannings_intervalles(idPlanning, jour, debut, fin, intervalle)
								VALUES(".$idPlanning.",'".$tabDates[0]."','".$tabDates[1]."','".$tabDates[2]."','".$tabDates[3]."')");
					
					
				}elseif($_POST['typePlan']=="ponctuel"){
					
					$tabDates = explode('@@',$tabPeriodes[$i],3);
					

					$addIntervalle = db_exec($bd, "
								INSERT INTO membres_plannings_intervalles(idPlanning, debut, fin, intervalle)
								VALUES(".$idPlanning.",'".$tabDates[0]."','".$tabDates[1]."','".$tabDates[2]."')");

				}
	

			
				if($addIntervalle === false){
					db_close($bd);
					die();
				}
			}
			
			array_push($_SESSION['postMessages'], array('type'=>'ok', 'content'=>'Le planning a bien été ajouté.'));
			db_close($bd);
			header('Location: http://'.$_SERVER['HTTP_HOST'].'/planning-'.$idPlanning);
			die();
		}		
	}
}//fin nouveau planning


include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>


<br/>
<form method=post action="addPlanning.php" id="formNewPlanning">

<label for="nom">nom du planning</label>
<input type="text" id="nom" name="nom" style="width:520px" maxlength=150 autocomplete="off" />



<label for="typePlan">type de planning</label>
	<input id="typePlanN" type="radio" name="typePlan" value="infini" onchange="selectType()" checked>  
	<label class="radio" for="typePlanN">Infini dans le temps</label>  
	<input id="typePlanC" type="radio" name="typePlan" value="ponctuel" onchange="selectType()">  
	<label class="radio" for="typePlanC">Ponctuel</label> 
	


<label for="canInscr">membres pouvant s'inscrire au planning</label>
	<input type="checkbox" id="canInscrP" name="canInscrP" onchange="selectCanInscr()">
	<label class="checkbox" for="canInscrP" style="margin-bottom:10px">Membres en période probatoire</label>
	
	<input type="checkbox" id="canInscrM" name="canInscrM" onchange="selectCanInscr()">
	<label class="checkbox" for="canInscrM" style="margin-bottom:10px">Membres actifs</label>
	
	<input type="checkbox" id="canInscrB" name="canInscrB" onchange="selectCanInscr()" checked>
	<label class="checkbox" for="canInscrB" style="margin-bottom:10px">Membres du bureau</label>
	
<br/><br/>
	<label for="visible">membres pouvant voir le planning</label>
	<input type="checkbox" id="visibleP" name="visibleP" onchange="selectVisible()" <?php echo ((DROITS=="probatoire")?"checked":"")?>>
	<label class="checkbox" for="visibleP" style="margin-bottom:10px">Membres en période probatoire</label>
	
	<input type="checkbox" id="visibleM" name="visibleM" onchange="selectVisible()" <?php echo ((DROITS=="probatoire"||DROITS=="membre")?"checked":"")?>>
	<label class="checkbox" for="visibleM" style="margin-bottom:10px">Membres actifs</label>
	
	<input type="checkbox" id="visibleB" name="visibleB" onchange="selectVisible()" checked>
	<label class="checkbox" for="visibleB" style="margin-bottom:10px">Membres du bureau</label>

	<br/><br/>

	
	
<div id="lstJours">
<label for="visible">jours visibles dans le planning</label>
	
	<table><tbody>
	
	<?php
	
		$jour = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche");
		
		for($i=1; $i<8 ; $i++){
			
			echo '<tr>
					<td style="width:120px" onclick="selectJour('.$i.')">
						<input type="checkbox" id="jour'.$i.'" name="jour'.$i.'" onchange="affJour('.$i.')">
						<label class="checkbox" for="jour'.$i.'" style="margin-bottom:10px">'.$jour[$i-1].'</label>
					</td><td id="tdDeb'.$i.'" style="display:none; width:150px">
						<label for="heureDeb'.$i.'">heure de début</label>
						<input type="time" id="heureDeb'.$i.'" name="heureDeb'.$i.'" style="width:110px; margin-right:0" onkeyup="verifTime('.$i.')" maxlength=5 autocomplete="off" value="--:--"/>
					</td><td id="tdIntervalle'.$i.'" style="display:none ; width:150px">
						<label for="heureFin'.$i.'">intervalle en minutes</label>
						<input type="text" id="intervalle'.$i.'" name="intervalle'.$i.'" style="width:110px; margin-right:0" onkeyup="verifTime('.$i.')" maxlength=4 autocomplete="off" value="60"/>
					</td><td id="tdFin'.$i.'" style="display:none ; width:150px">
						<label for="heureFin'.$i.'">heure de fin</label>
						<input type="time" id="heureFin'.$i.'" name="heureFin'.$i.'" style="width:110px; margin-right:0" onkeyup="verifTime('.$i.')" maxlength=5 autocomplete="off" value="--:--"/>
					</td><td id="tdEtat'.$i.'" style="display:none">
						<span id="etat'.$i.'"></span>
					</td>
				</tr>';
		}
	
	
	
	?>

	</tbody></table>
</div>
	
<div id="addPeriode" style="display:none">

<label for="dateDeb8">ajouter des périodes</label>
	<table style="width:100%">
	<thead id="thNewPeriode"><th colspan=2>Périodes</th></thead>
	<tbody id="tbodyPeriode">
	</tbody>
	<tr>
	<td>
		Nouvelle période : <br/> 
		Du <input type="date" id="dateDeb8" name="dateDeb8" style="width:160px; margin-right:0" onkeyup="verifTime(8)" maxlength=10 autocomplete="off" value="jj-mm-aaaa"/>
		à <input type="time" id="heureDeb8" name="heureDeb8" style="width:130px" onkeyup="verifTime(8)" maxlength=5 autocomplete="off" value="--:--"/>
		<br/>
		Au
		<input type="date" id="dateFin8" name="dateFin8" style="width:160px; margin-right:0" onkeyup="verifTime(8)" maxlength=10 autocomplete="off" value="jj-mm-aaaa"/>
		à <input type="time" id="heureFin8" name="heureFin8" style="width:130px" onkeyup="verifTime(8)" maxlength=5 autocomplete="off" value="--:--"/>
		<br/>
		Intervalle : <input type="text" id="intervalle8" name="intervalle8" style="width:110px; margin-right:0" onkeyup="verifTime(8)" maxlength=5 autocomplete="off" value="60"/> minutes
	
	</td>
	</td><td id="tdEtat8" style="width:200px">
		<span id="etat8" style="display:none"></span>
	</td>
	<td id="tdAddPeriode" style="width:35px; display:none" class="add" onclick="addPeriode(0)"></td>
	</tr>
	</table>
	
	
</div>
	
<br/>
<input type="button" onclick="submNewPlanning()" id="submitNewPlanning" value="valider" />


<input type="hidden" id="lstPeriodes" name="lstPeriodes" value=""/>
<input type="hidden" id="droitscanInscr" name="droitscanInscr" value=""/>
<input type="hidden" id="droitsView" name="droitsView" value=""/>
</form>

<script type="text/javascript">

function selectJour (id){
	if(document.getElementById('jour'+id).checked==true){
		document.getElementById('jour'+id).checked=false;
	}else{
		document.getElementById('jour'+id).checked=true;
	}
	affJour(id);
}

function affJour (id){
	if(document.getElementById('jour'+id).checked==true){
		document.getElementById('tdDeb'+id).style.display = "";
		document.getElementById('tdIntervalle'+id).style.display = "";
		document.getElementById('tdFin'+id).style.display = "";
		document.getElementById('tdEtat'+id).style.display = "";
	}else{
		document.getElementById('tdDeb'+id).style.display = "none";
		document.getElementById('tdIntervalle'+id).style.display = "none";
		document.getElementById('tdFin'+id).style.display = "none";
		document.getElementById('tdEtat'+id).style.display = "none";
	}
}

function verifTime(id){
	
	
	hDeb = document.getElementById('heureDeb'+id).value.substring(0,2);
	minDeb = document.getElementById('heureDeb'+id).value.substring(3,5);
	intervalle = document.getElementById('intervalle'+id).value;
	hFin = document.getElementById('heureFin'+id).value.substring(0,2);
	minFin = document.getElementById('heureFin'+id).value.substring(3,5);

	if(id == 8){
		dateDeb = document.getElementById('dateDeb'+id).value;
		dateFin = document.getElementById('dateFin'+id).value;
		
	}

	if(((id==8 && dateDeb != "" && dateFin != "" && isDate(dateDeb) && isDate(dateFin)) || id != 8) && hDeb != "" && minDeb != "" && intervalle != "" && hFin != "" && minFin != "" && !isNaN(hDeb) && !isNaN(minDeb) && !isNaN(intervalle) && !isNaN(hFin) && !isNaN(minFin)){
		
		document.getElementById('etat'+id).style.display = "";
		
		if (id == 8){
			
			if(document.getElementById('dateDeb8').value.indexOf("-") == 4){
				debut = new Date(dateDeb +"T"+ hDeb+":"+minDeb+":00");
				fin = new Date(dateFin +"T"+ hFin+":"+minFin+":00");
			
			}else{
				
				jourDeb = dateDeb.substring(0,2);
				moisDeb = dateDeb.substring(3,5);
				anneeDeb = dateDeb.substring(6,10);
				
				jourFin = dateFin.substring(0,2);
				moisFin = dateFin.substring(3,5);
				anneeFin = dateFin.substring(6,10);
				
				
				debut = new Date(anneeDeb + "-" + moisDeb +"-"+ jourDeb + "T" + hDeb+":"+minDeb+":00");
				fin = new Date(anneeFin + "-" + moisFin +"-"+ jourFin +"T"+ hFin+":"+minFin+":00");
				
			}
			
			deltaMin = parseInt(fin - debut) / 60000;


			if(intervalle > 0 && intervalle <= deltaMin && deltaMin > 0 && deltaMin % intervalle == 0){
				
				if((deltaMin / intervalle) < 201){
					verif = "ok";
					document.getElementById('tdAddPeriode').style.display = "";
					
				}else{
					verif = "int";
					document.getElementById('tdAddPeriode').style.display = "none";
					
				}
				

			}else{

				verif = "inco";
				document.getElementById('tdAddPeriode').style.display = "none";
			}
			
			
		}else{
			debut = parseInt(hDeb*60 + parseInt(minDeb));
			fin = parseInt(hFin*60 + parseInt(minFin));
			if(fin == 0){fin = parseInt(1440);}
			
			if(intervalle > 0 && intervalle < 1441 && fin > debut && (fin-debut) % intervalle == 0){
				
				if(((fin-debut) / intervalle) < 201){
					verif = "ok";
				}else{
					verif = "int";
				}

			}else{
				verif = "inco";
			}
			
		}
		
		
		if(verif=="ok"){
			document.getElementById('etat'+id).className = "messOk";
			document.getElementById('etat'+id).innerHTML = "Ok";

			
		}else if(verif=="inco"){
			document.getElementById('etat'+id).className = "messErr";
			document.getElementById('etat'+id).innerHTML = "Heures incohérentes";
		
		}else if(verif=="int"){
			document.getElementById('etat'+id).className = "messErr";
			document.getElementById('etat'+id).innerHTML = "Intervalle trop petit";
		}
		
	}else{
		document.getElementById('etat'+id).style.display = "none";
	}
}



function addPeriode(i){

	if(document.getElementById('dateDeb8').value.indexOf("-") == 4){
	

	document.getElementById('tbodyPeriode').innerHTML += '<tr id="periode'+i+'"><td colspan=2>'+
		' Du ' + document.getElementById('dateDeb8').value.substring(8,10) + "/" + document.getElementById('dateDeb8').value.substring(5,7) + "/" + document.getElementById('dateDeb8').value.substring(0,4) +
		' à ' + document.getElementById('heureDeb8').value +
		
		'&nbsp;&nbsp;Au ' + document.getElementById('dateFin8').value.substring(8,10) + "/" + document.getElementById('dateFin8').value.substring(5,7) + "/" + document.getElementById('dateFin8').value.substring(0,4) + 
		' à ' + document.getElementById('heureFin8').value +
		
		'&nbsp;&nbsp;&nbsp;Intervalle : ' + document.getElementById('intervalle8').value+ " minutes" +
		
		'<span style="display:none">'+
			document.getElementById('dateDeb8').value+' '+document.getElementById('heureDeb8').value+'@@'+
			document.getElementById('dateFin8').value+' '+document.getElementById('heureFin8').value+'@@'+
			document.getElementById('intervalle8').value+
		'</span>' +

		'</td><td class="remove" style="width:35px" onclick="supPeriode('+i+')"></td></tr>';
	
	}else{
		
			document.getElementById('tbodyPeriode').innerHTML += '<tr id="periode'+i+'"><td colspan=2>'+
		' Du ' + document.getElementById('dateDeb8').value +
		' à ' + document.getElementById('heureDeb8').value +
		
		'&nbsp;&nbsp;Au ' + document.getElementById('dateFin8').value + 
		' à ' + document.getElementById('heureFin8').value +
		
		'&nbsp;&nbsp;&nbsp;Intervalle : ' + document.getElementById('intervalle8').value+ " minutes" +
		
		'<span style="display:none">'+
			document.getElementById('dateDeb8').value+' '+document.getElementById('heureDeb8').value+'@@'+
			document.getElementById('dateFin8').value+' '+document.getElementById('heureFin8').value+'@@'+
			document.getElementById('intervalle8').value+
		'</span>' +

		'</td><td class="remove" style="width:35px" onclick="supPeriode('+i+')"></td></tr>';
		
	}
	
	
	document.getElementById('dateDeb8').value = "jj/mm/aaaa";
	document.getElementById('dateFin8').value = "jj/mm/aaaa";
	document.getElementById('heureDeb8').value = "--:--";
	document.getElementById('heureFin8').value = "--:--";
	document.getElementById('intervalle8').value = "60";
	document.getElementById('tdAddPeriode').onclick = function(){addPeriode(i+1)};

	document.getElementById('tdAddPeriode').style.display = "none";
	document.getElementById('etat8').style.display = "none";
	
	document.getElementById('dateDeb8').focus();
}




function supPeriode(i){
	document.getElementById('periode'+i).parentNode.removeChild(document.getElementById('periode'+i));
	document.getElementById('dateDeb8').focus();
}


function selectType(){
	
	if(document.getElementById('typePlanN').checked==true){
		document.getElementById('lstJours').style.display="";
		document.getElementById('addPeriode').style.display="none";
		
	}else{
		document.getElementById('lstJours').style.display="none";
		document.getElementById('addPeriode').style.display="";
	}

}



function selectCanInscr(){
	document.getElementById('canInscrB').checked=true;

	if(document.getElementById('canInscrP').checked==true && document.getElementById('canInscrM').checked==false){
		document.getElementById('canInscrM').checked=true;
	}
	
	if(document.getElementById('canInscrM').checked==true){
		
		document.getElementById('visibleB').checked=true;
		document.getElementById('visibleM').checked=true;
	
	}
	
	if(document.getElementById('canInscrP').checked==true){
		document.getElementById('canInscrM').checked=true;
		
		document.getElementById('visibleB').checked=true;
		document.getElementById('visibleM').checked=true;
		document.getElementById('visibleP').checked=true;
	}	
}

function selectVisible(){
	document.getElementById('visibleB').checked=true;
	<?php echo ((DROITS=="probatoire")?"document.getElementById('visibleP').checked=true;document.getElementById('visibleM').checked=true;":"")?>
	<?php echo ((DROITS=="membre")?"document.getElementById('visibleM').checked=true;":"")?>
	
	if(document.getElementById('visibleP').checked==true && document.getElementById('visibleM').checked==false){
		document.getElementById('visibleM').checked=true;
	}

	if(document.getElementById('canInscrM').checked==true){
		document.getElementById('visibleM').checked=true;
	
	}
	
	if(document.getElementById('visibleM').checked==false){
		document.getElementById('visibleP').checked=false;
	}
	
	if(document.getElementById('canInscrP').checked==true){
		document.getElementById('visibleM').checked=true;
		document.getElementById('visibleP').checked=true;
	}
}

function submNewPlanning(){


	ok = true;
	document.getElementById('lstPeriodes').value = "";

	if(document.getElementById('nom').value == ""){
		ok = false;
		alert("Veuillez remplir le nom du planning.");
	}
	
	
	

	if(document.getElementById('typePlanN').checked==true){



		//Verifs + construction liste des choix 
		
		aucunJour = true;
		malRempli = false;

		for(i=1; i<8; i++){
			
			if(document.getElementById('jour'+i).checked==true){
				
				aucunJour = false;
				
				if(document.getElementById('etat'+i).style.display == "" && document.getElementById('etat'+i).className == "messOk" && document.getElementById('etat'+i).innerHTML == "Ok"){
					
					periode = i+ '@@' + document.getElementById('heureDeb'+i).value + '@@' + document.getElementById('heureFin'+i).value + '@@' + document.getElementById('intervalle'+i).value;
					document.getElementById('lstPeriodes').value += periode+"//";
					
				}else{
					
					malRempli = true;
					
				}
			}
		}
		
		if(malRempli){
			ok = false;
			alert("Veuillez corriger les horraires incohérentes.");
		}
		
		if(aucunJour){
			ok = false;
			alert("Veuillez sélectionner au moins un jour.");
			}
		
	
	}else{
		
		//Verifs + construction liste des choix 
		var tbodyPeriode = document.getElementById('tbodyPeriode').childNodes;

		for(i=1; i<(tbodyPeriode.length); i++){
			
			periode = tbodyPeriode[i].childNodes[0].getElementsByTagName("SPAN")[0].innerHTML;
			document.getElementById('lstPeriodes').value += periode+"//";
		}
		
		if(document.getElementById('lstPeriodes').value == ""){
			ok = false;
			alert("Veuillez ajouter des périodes.");
		}
	}
			
			
	if(ok){
		//definition des droits
	
		
		if(document.getElementById('canInscrP').checked==true){
			document.getElementById('droitscanInscr').value = "probatoire";
		
		}else if(document.getElementById('canInscrM').checked==true){
			document.getElementById('droitscanInscr').value = "membre";
		
		}else{
			document.getElementById('droitscanInscr').value = "bureau";
		}
		
		
	
		if(document.getElementById('visibleP').checked==true){
			document.getElementById('droitsView').value = "probatoire";
		
		}else if(document.getElementById('visibleM').checked==true){
			document.getElementById('droitsView').value = "membre";
		
		}else{
			document.getElementById('droitsView').value = "bureau";
		}

		document.getElementById('submitNewPlanning').disabled=true;
		document.getElementById('submitNewPlanning').value = "Patientez...";
		document.getElementById('submitNewPlanning').onclick="";
		document.getElementById('formNewPlanning').submit();
	}
}



function isDate(val) {
    var d = new Date(val);
    return !isNaN(d.valueOf());
}


</script> 
<?php
echo $footer;
?>