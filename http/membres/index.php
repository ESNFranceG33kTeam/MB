<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
define('TITRE_PAGE',"Liste des adhérents");
$affMenu = false;

$sort="";
$order="";


if (isset($_GET['sort']) && isset($_GET['order'])){

	switch($_GET['order']){
		case "asc" : $order="ASC,"; break;	
		case "dsc" : $order="DESC,"; break;	
	}
	if(!(empty($order))){
	
		switch($_GET['sort']){
			case "dob" : $sort="dob "; break;
			case "etudes" : $sort="etudes "; break;
			case "pays" : $sort="pays "; break;
			case "retour" : $sort="dateRetour "; break;
			case "inscr" : $sort="dateInscr "; break;
			default : $order = ""; $sort = "";
		}
	}
}

if(isset($_POST['idSup'])){

	//Verif droits
	requireDroits("membre");

	$bd = db_connect();
	// verif inscrit à une future activité ou doit se faire rembourser
	
	$activities = db_ligne($bd, "SELECT COUNT(*)
						FROM activity_participants AS part
						JOIN activity_activities AS act ON act.id=part.idAct
						WHERE part.idAdh='".$_POST['idSup']."' AND DATEDIFF(act.dte,CURDATE())>=0");
	
	$rembours = db_ligne($bd, "SELECT COUNT(*)
					FROM activity_participants
					WHERE idAdh='".$_POST['idSup']."' AND (fullPaid=-1 OR (listeAttente=1 AND paid>0 ))");
	
	$mustPay = db_ligne($bd, "SELECT COUNT(*)
					FROM activity_participants
					WHERE idAdh='".$_POST['idSup']."' AND fullPaid=0 AND listeAttente=0");
					
	
	if($activities !== false && $rembours !== false && $rembours !== false && $activities[0]==0 && $rembours[0]==0 && $mustPay[0]==0){
						
	
		$supUsr = db_exec($bd, "
							DELETE FROM membres_adherents
							WHERE id='".$_POST['idSup']."'
							LIMIT 1");
		db_close($bd);	
		if($supUsr){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'L\'adhérent a bien été supprimé.'));
		}
	}elseif($activities[0]>0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'L\'adhérent ne peut pas être supprimé car il est inscrit à une activité future.'));
	
	}elseif($rembours[0]>0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'L\'adhérent ne peut pas être supprimé car il doit se faire rembourser une ou plusieurs activités.'));
	
	}elseif($mustPay[0]>0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'L\'adhérent ne peut pas être supprimé car il doit encore payer une ou plusieurs activités.'));
	}
}



//recup données
$bd = db_connect();
$tabAdh = db_tableau($bd, "
						SELECT id, idesn, prenom, nom, pays, dob, tel, email, adresse, etudes, dateRetour, dateInscr, cotisation, sexe
						FROM membres_adherents
						ORDER BY ".$sort.$order." prenom ASC, nom ASC");

$lstCotis = db_tableau($bd, "		
			SELECT cotisation
			FROM membres_adherents
			GROUP by cotisation");							

db_close($bd);

$selectCotis="";

if($lstCotis!==false){

	$selectCotis.='<option value=""></option>';

	for($i=0; $i<count($lstCotis); $i++){	
		$selectCotis.='<option value="'.$lstCotis[$i]['cotisation'].'">'.$lstCotis[$i]['cotisation'].'</option>';
	}
}

$mois = array("Jan", "Fev", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sept", "Oct", "Nov", "Dec");
$moisLong = array("Janvier", "Fevrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");

$selectDteInscr = '<option value=""></option>';
$nextMonth = date("Y-m");

	for($i=0; $i<12; $i++){
		$selectDteInscr .= '<option value='.$i.'>'.$moisLong[intval(date("m", strtotime($nextMonth)))-1].' '.date("Y", strtotime($nextMonth)).'</option>';
		$nextMonth = date("Y-m", strtotime($nextMonth. " -1 month"));
	}


$adh="";
$countAdhSinceAugust = 0;
$tabJS="";

//Recuperation du 1er jour du dernier mois d'aout
if(date('n')<8){
	$annee = date('y') - 1;
}else{
	$annee = date('y');
}
$firstOfAugust = date($annee.'-'.'08-01');


if($tabAdh !== false && !(empty($tabAdh))){

	for($i=0; $i<count($tabAdh); $i++){
	
			$dob = explode('-',$tabAdh[$i][5],3);
			$dateOB = $dob[2].'/'.$dob[1].'/'.$dob[0];	
			
			$tabAdh[$i][6]=(empty($tabAdh[$i][6]))?"Non renseigné":$tabAdh[$i][6];
			
			if(empty($tabAdh[$i][10])){
				$retour="Inconnu";
			}else{
				$dretour = explode('-',$tabAdh[$i][10],2);
				$retour = $mois[intval($dretour[1]-1)].' '.$dretour[0];
			}
			
			if(date_create($tabAdh[$i][11]) >= date_create($firstOfAugust)){
				$countAdhSinceAugust++;
			}
			
			
			$dIns = explode('-',$tabAdh[$i][11],3);
			$dateInscr = $dIns[2].'/'.$dIns[1].'/'.$dIns[0];
			
			
			$nbMoisDteInscr = date_diff(date_create(date("Y-m-02")), date_create($dIns[0].'-'.$dIns[1].'-02'));

			if($tabAdh[$i]['sexe'] == "H"){
				$sexe = '<img style="float:right; height:14px" src="../template/images/male.png" title="'.$tabAdh[$i]['sexe'].'">';
			}elseif($tabAdh[$i]['sexe'] == "F"){
				$sexe = '<img style="float:right; height:14px" src="../template/images/female.png" title="'.$tabAdh[$i]['sexe'].'">';
			}else{
				$sexe ='';
			}
			
			if(empty($tabAdh[$i][1])){
				$tabAdh[$i][1] = 'Pas de carte';
			}
			
			
			
			$adh.= '<tr id="line'.$i.'"><td class="gras"><div style="float:left; font-size:0.95em; line-height:1.15em; width:126px;">'.$tabAdh[$i][2].' '.$tabAdh[$i][3].'</div>'.$sexe.'</td>
				<td><div style="float:left; font-size:0.9em;">Tel : '.$tabAdh[$i][6].'</div><div style="float:right"><img class="iconeListe" src="../template/images/home.png" title="'.$tabAdh[$i][8].'"></div><br /><div class="hidden-inline" style="width:185px; line-height:1.15em; font-size:0.8em"><a href="mailto:'.$tabAdh[$i][7].'">'.$tabAdh[$i][7].'</a></div></td>
				<td><div class="hidden-inline" style="float:left; width:165px; font-size:0.95em; line-height:20px">Pays : '.$tabAdh[$i][4].'</div><div style="float:right; font-size:0.8em; line-height:22px">Né le : '.$dateOB.'</div><br />
				<div class="hidden-inline" style="width:275px; line-height:1.15em; font-size:0.8em">Etudes : '.$tabAdh[$i][9].'</div></td>
				<td class="center"><div style="font-size:0.9em">'.$tabAdh[$i][1].'</div></td>
				<td class="center">'.$retour.'</td><td class="center">'.$dateInscr.'</td>
				<td class="edit" onclick="edit('.$tabAdh[$i][0].')"></td>
				<td class="suppr" onclick="suppr('.$tabAdh[$i][0].',\''.str_replace("'","\'", $tabAdh[$i][2]).' '.str_replace("'","\'", $tabAdh[$i][3]).'\')"></td>
				</tr>';
				
			$tabJS.= 'tabJS['.$i.']=new Array("'.strtolower($tabAdh[$i]['prenom']).'","'.strtolower($tabAdh[$i]['nom']).'","'.strtolower($tabAdh[$i]['pays']).'",'.($nbMoisDteInscr->m + 12*$nbMoisDteInscr->y).',"'.$tabAdh[$i]['cotisation'].'");';
	}
}

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<div class="gras">Nombre d'adhérents : <?php echo count($tabAdh); ?> - Depuis août : <?php echo $countAdhSinceAugust; ?></div>
<h3>Filtrer</h3>
<table class="invisible"><tbody><tr>
<td>
<label for="nom" >prénom ou nom</label>
<input type="text" id="nom" name="nom" onkeyup="filtering()" value="" style="width:220px" autocomplete="off"/>
</td><td>
<label for="pays" >pays</label>
<input type="text" id="pays" name="pays" onkeyup="filtering()" value="" style="width:220px" autocomplete="off"/>
</td>
<td>
<label for="dteInscr">inscrit à partir de</label>
<select id="dteInscr" name="dteInscr" onchange="filtering()" style="width:200px">
<?php echo $selectDteInscr?>
</select>
</td>
<td>
<label for="cotisation">type de cotisation</label>
<select id="cotisation" name="cotisation" onchange="filtering()" style="width:280px">
<?php echo $selectCotis?>
</select>
</td>
</tr></tbody></table>
<div id="divNbFiltered" style="padding-top:5px; font-weight:bold;"></div>
<h3>Adhérents</h3>
<div class="blocText">
	<div id="divMails">
		<a onclick="lstMails('all','no')">
		<img src="../template/images/emails.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste des e-mails de tous les adhérents</a>
		<a onclick="lstMails('aout','no')" style="margin-left:30px">
		<img src="../template/images/emails.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste des e-mails des adhérents inscrits depuis août</a>
	</div><div id="divMailsFiltre" style="display:none">
		<a onclick="lstMails('all','yes')">
		<img src="../template/images/emails.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste des e-mails de tous les adhérents filtrés</a>
		<a onclick="lstMails('aout','yes')" style="margin-left:30px">
		<img src="../template/images/emails.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste des e-mails des adhérents filtrés et inscrits depuis août</a>
	</div>
</div><br />
<?php

if(!empty($adh)){
	echo '<table><tbody>';
	echo '<tr><th style="width:125px">Nom</th><th>Contact</th>
			<th> 
				Pays<img class="sortA" onclick="sort(\'pays\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'pays\',\'dsc\')" src="../template/images/sortDesc.png">
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Etudes<img class="sortA" onclick="sort(\'etudes\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'etudes\',\'dsc\')" src="../template/images/sortDesc.png">
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Né le<img class="sortA" onclick="sort(\'dob\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'dob\',\'dsc\')" src="../template/images/sortDesc.png">	
			</th>
			<th style="width:68px">Carte ESN</th>
			<th style="width:72px">Retour<img class="sortA" onclick="sort(\'retour\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'retour\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th style="width:85px">Inscrit le<img class="sortA" onclick="sort(\'inscr\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'inscr\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			</tr>';
	echo $adh;
	echo '</tbody></table>';
	echo '<form method=post action="editAdh.php" id="formEdit"><input type="hidden" id="idEdit" name="idEdit" /></form>';
	echo '<form method=post action="index.php" id="formSup"><input type="hidden" id="idSup" name="idSup" /></form>';
}else{
	echo '<div>Pas de données.</div>';
}
?>
<script type="text/javascript">

var tabJS=new Array();
<?php echo $tabJS; ?>


function filtering(){
	if(document.getElementById('nom').value.length>1 || document.getElementById('pays').value.length>1 || document.getElementById('dteInscr').value!="" || document.getElementById('cotisation').value!=""){
	
		var countFiltered = 0;
		
		for(var i=0; i<tabJS.length; i++){
			var nom = tabJS[i][0]+" "+tabJS[i][1];
			
			if(tabJS[i][2].indexOf(document.getElementById('pays').value.toLowerCase())==-1 || 
						nom.indexOf(document.getElementById('nom').value.toLowerCase())==-1 || 
						(document.getElementById('dteInscr').value!="" && tabJS[i][3] > document.getElementById('dteInscr').value) ||
						(document.getElementById('cotisation').value!="" && tabJS[i][4] != document.getElementById('cotisation').value)){
				
				document.getElementById('line'+i).style.display = "none";
			}else{
				document.getElementById('line'+i).style.display = "";
				countFiltered++;
			}
		}
		document.getElementById('divMails').style.display = "none";
		document.getElementById('divMailsFiltre').style.display = "";
		
		if(countFiltered==0){
			document.getElementById('divNbFiltered').innerHTML = "Résultat : Pas d'adhérents";
		
		}else if((countFiltered==1)){
			document.getElementById('divNbFiltered').innerHTML = "Résultat : 1 adhérent";
		
		}else{
			document.getElementById('divNbFiltered').innerHTML = "Résultat : " + countFiltered + " adhérents";
		}
		
	}else{
		for(var i=0; i<tabJS.length; i++){
			document.getElementById('line'+i).style.display = "";
		}
		document.getElementById('divNbFiltered').innerHTML = "";
		document.getElementById('divMails').style.display = "";
		document.getElementById('divMailsFiltre').style.display = "none";
	}
}

function lstMails(type, filtered){
	if(filtered=="yes"){
		filtre="";
		if(document.getElementById('nom').value.length>0){
			filtre+="&nom="+document.getElementById('nom').value.toLowerCase();
		}
		if(document.getElementById('pays').value.length>0){
			filtre+="&pays="+document.getElementById('pays').value.toLowerCase();
		}
		if(document.getElementById('dteInscr').value != ""){
			filtre+="&date="+document.getElementById('dteInscr').value;
		}
		if(document.getElementById('cotisation').value != ""){
			filtre+="&cotis="+document.getElementById('cotisation').value;
		}
		
		window.open("lstMailsAdh.php?type="+type+filtre);
	}else{
		window.open("lstMailsAdh.php?type="+type);
	}
}

function sort(colonne, order){
	window.location.href="index.php?sort="+colonne+"&order="+order;
}

function edit(id){
	document.getElementById('idEdit').value = id;
	document.getElementById('formEdit').submit();
}

function suppr(id, nom){
if(confirm("Voulez-vous vraiment supprimer "+nom+" des adhérents de l'association ?")){
	document.getElementById('idSup').value = id;
	document.getElementById('formSup').submit();
	}
}


</script>
<?php
echo $footer;
?>