<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Gestion des bénévoles");


//Edit date de fin de probation
if(isset($_POST['idEditProba'])){

	if(!empty($_POST['dateEditProba'])){
		
		$dte = date_parse($_POST['dateEditProba']);
	
		if (!checkdate($dte['month'], $dte['day'], $dte['year'])) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Date de fin de probation</em> n\'est pas valide.'));
		}else{
			$dateEditProba = $dte['year'].'-'.$dte['month'].'-'.$dte['day'];
		}

	}else{
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Date de fin de probation</em>.'));
	}


	if(empty($pageMessages)){
		$bd = db_connect();
		$addUsr = db_exec($bd, "
							UPDATE membres_droits
							SET finProbatoire='".$dateEditProba."'
							WHERE id='".$_POST['idEditProba']."'");
		
		if($addUsr){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La modification a bien été effectuée.'));
		}
		db_close($bd);	
	}
}

//Validation période probatoire
if(isset($_POST['idValProba'])){

	$bd = db_connect();
	$addUsr = db_exec($bd, "
						UPDATE membres_droits
						SET general='membre'
						WHERE id='".$_POST['idValProba']."'");
	
	if($addUsr){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'La validation de la période probatoire a bien été effectuée.'));
	}
	db_close($bd);	
}

//Ajout au bureau
if(isset($_POST['idAdd'])){

	$bd = db_connect();
	$addUsr = db_exec($bd, "
						UPDATE membres_droits
						SET general='bureau'
						WHERE id='".$_POST['idAdd']."'");
	
	if($addUsr){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'La modification a bien été effectuée.'));
	}
	db_close($bd);	
}

//Suppression du bureau
if(isset($_POST['idRem'])){

	$bd = db_connect();
	
	$checkBureau = db_colonne($bd, "
					SELECT id
					FROM membres_droits
					WHERE general='bureau'");
					
	if(count($checkBureau)>1){
	
		$remUsr = db_exec($bd, "
							UPDATE membres_droits
							SET general='membre'
							WHERE id='".$_POST['idRem']."'");
	
		if($remUsr){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La modification a bien été effectuée.'));
		}
	
	}else{
		array_push($pageMessages, array('type'=>'err', 'content'=>'Il doit toujours y avoir au moins un membre du bureau.'));
	}
	db_close($bd);	
}


//Ajout d'un role

if(isset($_POST['idAddRole'])){

	if (empty($_POST['selectAddRole']) || !is_numeric($_POST['selectAddRole']) || mb_strlen($_POST['selectAddRole'])>4){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Nouveau rôle</em> n\'est pas valide.'));
	}
	

	if(empty($pageMessages)){
		$bd = db_connect();
		$rolesInit = db_valeur($bd, "
							SELECT roles
							FROM membres_droits
							WHERE id='".$_POST['idAddRole']."'");
							
		$roles = explode('//',$rolesInit);	
		
		if(!in_array($_POST['selectAddRole'], $roles)){
		
			array_push($roles,$_POST['selectAddRole']);
			
			sort($roles);
			
			$rolesNew = mysqli_real_escape_string($bd, implode('//',$roles));
			
			$updateRoles = db_exec($bd, "
								UPDATE membres_droits
								SET roles='".$rolesNew."'
								WHERE id='".$_POST['idAddRole']."'");
			
			
			if($rolesInit && $updateRoles){
				
				array_push($pageMessages, array('type'=>'ok', 'content'=>'Le rôle à bien été ajouté.'));
			}
		
		}else{
			
			array_push($pageMessages, array('type'=>'err', 'content'=>'Ce rôle a déjà été attribué à ce membre.'));
		}
		db_close($bd);	
	}
}


//Suppression d'un role

if(isset($_POST['idSupprRole'])){

	$bd = db_connect();
		
	$roleSuppr = '//' . mysqli_real_escape_string($bd, $_POST['roleSuppr']);
	
	$updateRoles = db_exec($bd, "
						UPDATE membres_droits
						SET roles=REPLACE(roles, '".$roleSuppr."' , '')
						WHERE id='".$_POST['idSupprRole']."'");
	
	
	if($updateRoles){
		
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Le rôle à bien été supprimé.'));
	}
	
	
	db_close($bd);	
	
}


//Edit date d'arrivée
if(isset($_POST['idEditArrived'])){

		if($_POST['dateEditArrived'] == "aaaa-mm" || empty($_POST['dateEditArrived'])){
			$dateArrivee = "";
		
		}else{
			$dte = date_parse($_POST['dateEditArrived']);
		
			if (!checkdate($dte['month'], $dte['day'], $dte['year'])) {
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Mois d\'arrivée</em> n\'est pas valide.'));
			}else{
				$dateArrivee = $dte['year'].'-'.$dte['month'];
			}
		}

	

	if(empty($pageMessages)){
		$bd = db_connect();
		$addUsr = db_exec($bd, "
							UPDATE membres_benevoles
							SET arrived='".$dateArrivee."'
							WHERE id='".$_POST['idEditArrived']."'");
		
		if($addUsr){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La modification a bien été effectuée.'));
		}
		db_close($bd);	
	}
}






//Suppression du membre
if(isset($_POST['idSup'])){

	$bd = db_connect();
	
	// verif inscrit à une future activité.
	
	$activities = db_ligne($bd, "SELECT COUNT(*)
						FROM activity_participants AS part
						JOIN activity_activities AS act ON act.id=part.idAct
						WHERE part.idESN='".$_POST['idSup']."' AND DATEDIFF(act.dte,CURDATE())>=0");
	
	$rembours = db_ligne($bd, "SELECT COUNT(*)
					FROM activity_participants
					WHERE idESN='".$_POST['idSup']."' AND (fullPaid=-1 OR (listeAttente=1 AND paid>0 ))");
					
	$mustPay = db_ligne($bd, "SELECT COUNT(*)
					FROM activity_participants
					WHERE idESN='".$_POST['idSup']."' AND fullPaid=0 AND listeAttente=0");
	
	if($activities !== false && $rembours !== false && $mustPay !== false && $activities[0]==0 && $rembours[0]==0 && $mustPay[0]==0){
						
		$supUsr = db_exec($bd, "
							DELETE FROM membres_benevoles
							WHERE id='".$_POST['idSup']."' AND EXISTS(SELECT id, general
																			FROM membres_droits
																			WHERE id='".$_POST['idSup']."' AND general != 'bureau')
							LIMIT 1");
						
		db_close($bd);	
		
		if($supUsr){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Le membre a bien été supprimé.'));
		}
	}elseif($activities[0]>0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le membre ne peut pas être supprimé car il est inscrit à une activité future.'));
	
	}elseif($rembours[0]>0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le membre ne peut pas être supprimé car il doit se faire rembourser une ou plusieurs activités.'));
	
	}elseif($mustPay[0]>0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le membre ne peut pas être supprimé car il doit encore payer une ou plusieurs activités.'));
	}
}


//Construction Liste Roles
$tabRoles = array();
$lstRoles = '<option value="">Choisissez un rôle</option>';

$fileRoles = fopen(($GLOBALS['SITE']->getFolderData()).'/../liste_roles.txt', 'r');

while (!feof($fileRoles)){

	$ligneRole = explode('//',trim(fgets($fileRoles)),3);	
	$lstRoles .= '<option value="'.$ligneRole[0].'">'.$ligneRole[1].'</option>';
	array_push($tabRoles, array($ligneRole[0], $ligneRole[1]));
	
}

fclose($fileRoles);



//récup liste bénévoles
$bd = db_connect();
$tabMembres = db_tableau($bd, "SELECT ben.id, ben.prenom, ben.nom, drt.general, drt.finProbatoire, ben.arrived, drt.roles
								FROM membres_benevoles AS ben
								LEFT JOIN membres_droits AS drt ON ben.id = drt.id
								ORDER BY ben.prenom ASC, ben.nom ASC");		
db_close($bd);

$mois = array("Jan", "Fev", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sept", "Oct", "Nov", "Dec");

$bureau="";
$membres="";
$membresProba="";


if($tabMembres !== false && !(empty($tabMembres))){
	for($i=0; $i<count($tabMembres); $i++){
		
		//Roles
		$rolesMembre = "";
		$rolesMem = explode('//', $tabMembres[$i]['roles']);
	
		for($rol=0; $rol<count($rolesMem); $rol++){
			
			$keyRole = "";

			for($liTabRole=0; $liTabRole<count($tabRoles); $liTabRole++){
				
				if($rolesMem[$rol] ==  $tabRoles[$liTabRole][0]){
					$keyRole = $liTabRole;
					break;
				}
			}
			
			if(!empty($keyRole) || $keyRole===0){
				$rolesMembre .= '<span style="display: inline-block; white-space: nowrap;">'.$tabRoles[$keyRole][1].' <a id="aSupprRole-'.$tabMembres[$i][0].'-'.$tabRoles[$keyRole][0].'"" onclick="submSupprRole('.$tabMembres[$i][0].','.$tabRoles[$keyRole][0].')"><img src="/../template/images/suppr.png" style="vertical-align:middle;height:7px; margin-right:6px"></a></span>';
			}
		}

	
	
		
		//Date d'arrivée
		
		if(!empty($tabMembres[$i]['arrived'])){
			$dteArrivee = $tabMembres[$i]['arrived'];
			
			$arrivee = explode('-',$tabMembres[$i]['arrived'],2);
			$tabMembres[$i]['arrived'] = $mois[intval($arrivee[1]-1)].' '.$arrivee[0];
			
			if(intval($arrivee[1] < 10)){
				
				$dteArrivee = $arrivee[0]. '-0' . $arrivee[1];
				
			}else{
				$dteArrivee = $arrivee[0]. '-' . $arrivee[1];	
			}
			

		}else{
			$dteArrivee = "aaaa-mm";
			
		}
		
		
	
		if($tabMembres[$i][3]=="bureau"){
			
			if(empty($rolesMembre)){
				$rolesMembre = "Membre du bureau";
			}
			
			$bureau.= '<tr><td>'
							.$tabMembres[$i][1].' '.$tabMembres[$i][2].'
							<br/><div style="max-width:380px;line-height:11px; font-size:11px; float:left">'.$rolesMembre.'</div>
							<span style="line-height:11px; font-size:11px; float:right">
								<a onclick="affAddRole('.$tabMembres[$i][0].')"><img src="/../template/images/add.png" style="vertical-align:middle;height:11px; margin-right:3px">Ajouter un rôle</a>
							</span>
							
							<div id="affAddRole-'.$tabMembres[$i][0].'" style="display:none">
								<br/>
								<form method=post id="formAddRole-'.$tabMembres[$i][0].'" action="setBenevoles.php">
								<table class="invisible"><tbody><tr><td>
									<select name="selectAddRole" style="margin-bottom:2px">'.$lstRoles.'</select>
									<input type="hidden" id="idAddRole" name="idAddRole" value="'.$tabMembres[$i][0].'" />
								</td><td>
									<input type="button" onclick="submAddRole('.$tabMembres[$i][0].')" id="submAddRole-'.$tabMembres[$i][0].'" value="valider" style="margin-top:0"/>
								</td></tr></tbody></table>
								</form>
							</div>
							</td>

						<td id="tdDateArrived'.$tabMembres[$i][0].'" style="width:95px; text-align:center">'.$tabMembres[$i]['arrived'].'</td>
						<td id="tdDateArrivedTxt'.$tabMembres[$i][0].'" style="width:100px; display:none">
							<input type="month" id="newDateArrived'.$tabMembres[$i][0].'" value="'.$dteArrivee.'" style="margin:0; box-sizing:border-box; height:inherit; width:185px"/>
						</td>
						<td id="tdEditArrived'.$tabMembres[$i][0].'" class="edit" onclick="editArrived('.$tabMembres[$i][0].')"></td>

						<td class="remove" onclick="del('.$tabMembres[$i][0].')"></td></tr>';	
				
				
		}elseif($tabMembres[$i][3]=="membre"){
			
			if(empty($rolesMembre)){
				$rolesMembre = "Membre actif";
			}
			
			
			$membres.= '<tr><td>'.$tabMembres[$i][1].' '.$tabMembres[$i][2].'
			
							<br/><div style="max-width:220px;line-height:11px; font-size:11px; float:left">'.$rolesMembre.'</div>
							<span style="line-height:11px; font-size:11px; float:right">
								<a onclick="affAddRole('.$tabMembres[$i][0].')"><img src="/../template/images/add.png" style="vertical-align:middle;height:11px; margin-right:3px">Ajouter un rôle</a>
							</span>
							
							<div id="affAddRole-'.$tabMembres[$i][0].'" style="display:none">
								<br/>
								<form method=post id="formAddRole-'.$tabMembres[$i][0].'" action="setBenevoles.php">
								<table class="invisible"><tbody><tr><td>
									<select name="selectAddRole" style="margin-bottom:2px; width:200px">'.$lstRoles.'</select>
									<input type="hidden" id="idAddRole" name="idAddRole" value="'.$tabMembres[$i][0].'" />
								</td><td>
									<input type="button" onclick="submAddRole('.$tabMembres[$i][0].')" id="submAddRole-'.$tabMembres[$i][0].'" value="valider" style="margin-top:0"/>
								</td></tr></tbody></table>
								</form>
							</div>
							</td>
			
						<td id="tdDateArrived'.$tabMembres[$i][0].'" style="width:95px; text-align:center">'.$tabMembres[$i]['arrived'].'</td>
						<td id="tdDateArrivedTxt'.$tabMembres[$i][0].'" style="width:100px; display:none">
							<input type="month" id="newDateArrived'.$tabMembres[$i][0].'" value="'.$dteArrivee.'" style="margin:0; box-sizing:border-box; height:inherit; width:185px"/>
						</td>
						
						<td id="tdEditArrived'.$tabMembres[$i][0].'" class="edit" onclick="editArrived('.$tabMembres[$i][0].')"></td>
							<td class="add" onclick="addBureau('.$tabMembres[$i][0].')"></td>
							<td class="suppr" onclick="suppr('.$tabMembres[$i][0].', \''.str_replace("'","\'", $tabMembres[$i][1]).' '.str_replace("'","\'", $tabMembres[$i][2]).'\')"></td></tr>';
							
							
							
		}elseif($tabMembres[$i][3]=="probatoire"){
			$dte = explode('-',$tabMembres[$i][4],3);	
			$dateProba = $dte[2].'/'.$dte[1].'/'.$dte[0];
			
			$styleDate="";
			if(date_create($tabMembres[$i][4])<=date_create('now')){
				$styleDate="color:red; font-weight:bold";
			}
			
			$membresProba.= '<tr><td>'.$tabMembres[$i][1].' '.$tabMembres[$i][2].'</td>
			
				<td id="tdDateProba'.$tabMembres[$i][0].'" style="width:160px; '.$styleDate.'">'.$dateProba.'</td>
				<td id="tdDateProbaTxt'.$tabMembres[$i][0].'" style="width:160px; display:none">
					<input type="date" id="newDateProba'.$tabMembres[$i][0].'" value="'.$tabMembres[$i][4].'" style="margin:0; box-sizing:border-box; height:inherit; width:160px"/></td>
				<td id="tdEditDateProba'.$tabMembres[$i][0].'" class="edit" onclick="editProba('.$tabMembres[$i][0].')"></td>
			
				<td class="tick" onclick="submValProba('.$tabMembres[$i][0].', \''.str_replace("'","\'", $tabMembres[$i][1]).' '.str_replace("'","\'", $tabMembres[$i][2]).'\')"></td>
				<td class="suppr" onclick="suppr('.$tabMembres[$i][0].', \''.str_replace("'","\'", $tabMembres[$i][1]).' '.str_replace("'","\'", $tabMembres[$i][2]).'\')"></td></tr>';
		
		}
	}
}

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Membres en période probatoire</h3>
<?php
if(!empty($membresProba)){
	echo '<table><tbody>';
	echo '<tr><th>Nom</th><th colspan=2>Fin de probation</th><th style="width:150px">Valider la probation</th><th style="width:150px">Supprimer le membre</th></tr>';
	echo $membresProba;
	echo '</tbody></table>';
}else{
	echo '<div>Personne</div>';
}
?>

<h3>Membres du bureau</h3>
<?php
if(!empty($bureau)){

	echo '<table><tbody>';
	echo '<tr><th>Nom</th><th colspan=2>Mois d\'arrivée</th><th style="width:150px">Enlever du bureau</th></tr>';
	echo $bureau;
	echo '</tbody></table>';
}else{
	echo '<div>Personne</div>';
}
?>
<h3>Membres actifs</h3>
<?php
if(!empty($membres)){
	echo '<table><tbody>';
	echo '<tr><th>Nom</th><th colspan=2>Mois d\'arrivée</th><th style="width:150px">Ajouter au bureau</th><th style="width:150px">Supprimer le membre</th></tr>';
	echo $membres;
	echo '</tbody></table>';
}else{
	echo '<div>Personne</div>';
}
?>



<form method=post action="setBenevoles.php" id="formAdd" style="display:none"><input type="hidden" id="idAdd" name="idAdd" /></form>
<form method=post action="setBenevoles.php" id="formSup" style="display:none"><input type="hidden" id="idSup" name="idSup" /></form>
<form method=post action="setBenevoles.php" id="formRem" style="display:none"><input type="hidden" id="idRem" name="idRem" /></form>

<form method=post action="setBenevoles.php" id="formSupprRole" style="display:none">
	<input type="hidden" id="idSupprRole" name="idSupprRole" />
	<input type="hidden" id="roleSuppr" name="roleSuppr" />
</form>

<form method=post action="setBenevoles.php" id="formEditArrived" style="display:none">
	<input type="hidden" id="idEditArrived" name="idEditArrived" />
	<input type="hidden" id="dateEditArrived" name="dateEditArrived" />
</form>


<form method=post action="setBenevoles.php" id="formEditProba" style="display:none">
	<input type="hidden" id="idEditProba" name="idEditProba" />
	<input type="hidden" id="dateEditProba" name="dateEditProba" />
</form>

<form method=post action="setBenevoles.php" id="formValProba" style="display:none"><input type="hidden" id="idValProba" name="idValProba" /></form>



<script type="text/javascript">
function del(id){
	document.getElementById('idRem').value = id;
	document.getElementById('formRem').submit();
}

function addBureau(id){
	document.getElementById('idAdd').value = id;
	document.getElementById('formAdd').submit();
}
function suppr(id, nom){
	if(confirm("Voulez-vous vraiment supprimer "+nom+" du site ?")){
		document.getElementById('idSup').value = id;
		document.getElementById('formSup').submit();
	}
}

function affAddRole(id){
	
	document.getElementById('affAddRole-'+id).style.display = "";
	
}


function submAddRole(id){
	document.getElementById('submAddRole-'+id).disabled=true;
	document.getElementById('submAddRole-'+id).value = "Patientez...";
	document.getElementById('submAddRole-'+id).onclick="";
	document.getElementById('formAddRole-'+id).submit();
}

function submSupprRole(id, role){
	document.getElementById('aSupprRole-'+id+'-'+role).onclick="";
	document.getElementById('idSupprRole').value = id;
	document.getElementById('roleSuppr').value = role;
	document.getElementById('formSupprRole').submit();
}


function editArrived(id){
	document.getElementById('tdDateArrived'+id).style.display = "none";
	document.getElementById('tdDateArrivedTxt'+id).style.display = "";
	document.getElementById('tdEditArrived'+id).className = "tick";
	document.getElementById('tdEditArrived'+id).onclick=function(){submEditArrived(id);};

}
function submEditArrived(id){
	document.getElementById('tdEditArrived'+id).onclick="";
	document.getElementById('idEditArrived').value = id;
	document.getElementById('dateEditArrived').value = document.getElementById('newDateArrived'+id).value;
	document.getElementById('formEditArrived').submit();
}



function editProba(id){
	document.getElementById('tdDateProba'+id).style.display = "none";
	document.getElementById('tdDateProbaTxt'+id).style.display = "";
	document.getElementById('tdEditDateProba'+id).className = "tick";
	document.getElementById('tdEditDateProba'+id).onclick=function(){submEditProba(id);};

}
function submEditProba(id){
	document.getElementById('tdEditDateProba'+id).onclick="";
	document.getElementById('idEditProba').value = id;
	document.getElementById('dateEditProba').value = document.getElementById('newDateProba'+id).value;
	document.getElementById('formEditProba').submit();
}
function submValProba(id, nom){
	if(confirm("Voulez-vous valider la période probatoire de "+nom+" ?")){
		document.getElementById('idValProba').value = id;
		document.getElementById('formValProba').submit();
	}
}

</script> 
<?php
echo $footer;
?>