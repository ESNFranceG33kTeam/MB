<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Nouveau Bénévole");

//New bénévole
if(isset($_POST['prenom']) && isset($_POST['nom'])){

	
	if (empty($_POST['prenom'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Prénom</em>.'));
	}
	if (empty($_POST['nom'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom</em>.'));
	}
	if (mb_strlen($_POST['prenom'])>30){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Prénom</em> ne doit pas dépasser 30 caractères.'));
	}
	if (mb_strlen($_POST['nom'])>30){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom</em> ne doit pas dépasser 30 caractères.'));
	}
	
	
	if(empty($_POST['arrived'])){
	
		$dateArrivee = "";
		
	}else{
		
		$dteArrivee = date_parse($_POST['arrived']);
	
		if (!checkdate($dteArrivee['month'], $dteArrivee['day'], $dteArrivee['year'])) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Mois d\'arrivée</em> n\'est pas valide.'));
		}else{
			$dateArrivee = "'".$dteArrivee['year'].'-'.$dteArrivee['month']."'";
		}
	}
	
	
	if(isset($_POST['isProba']) && $_POST['isProba']==true){
		if(!empty($_POST['dateProba'])){
		
			$dte = date_parse($_POST['dateProba']);
		
			if (!checkdate($dte['month'], $dte['day'], $dte['year'])) {
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Date de fin de probation</em> n\'est pas valide.'));
			}else{
				$dateProba = "'".$dte['year'].'-'.$dte['month'].'-'.$dte['day']."'";
			}

		}else{
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Date de fin de probation</em>.'));
		}
	}
	
	if(empty($pageMessages)){
	
		$bd = db_connect();
		
		$_POST['prenom'] = mysqli_real_escape_string($bd, $_POST['prenom']);
		$_POST['nom'] = mysqli_real_escape_string($bd, $_POST['nom']);
		$usrname = strtolower($_POST['prenom']."_".$_POST['nom']);
		$usrname = str_replace(" ",".",$usrname);
		if(!isset($dateProba))
			$dateProba = "NULL";
	
	
		$rep = db_ligne($bd, "
								SELECT ben.login
								FROM membres_benevoles ben
								WHERE ben.login='".$usrname."' 
								UNION
								SELECT pre.login
								FROM membres_prebenevoles pre
								WHERE pre.login='".$usrname."'");

		
		if(!empty($rep)){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La personne existe déjà.'));
		}elseif($rep!==false){
	
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$code = '';
			srand();
			for ($i = 0; $i < 10; $i++) {
				$code .= $characters[rand(0, strlen($characters) - 1)];
			}
 
			$addUsr = db_exec($bd, "
								INSERT INTO membres_prebenevoles(nom, prenom, login, code, arrived, finProbatoire)
								VALUES('".$_POST['nom']."','".$_POST['prenom']."','".$usrname."','".$code."',".$dateArrivee.",".$dateProba.")");
	
			if($addUsr){
				array_push($pageMessages, array('type'=>'ok', 'content'=>$_POST['prenom']." ".$_POST['nom'].' a bien été ajouté.<br /> Lien à donner pour finaliser l\'inscription : <a href="http://'.$_SERVER['HTTP_HOST'].'/inscr-'.$code.'">http://'.$_SERVER['HTTP_HOST'].'/inscr-'.$code.'</a>'));
			}
	
		}
	db_close($bd);
	
	}
}// fin if ajout bénévole

if(isset($_POST['idSup'])){

	$bd = db_connect();
	$supUsr = db_exec($bd, "
						DELETE FROM membres_prebenevoles
						WHERE id='".$_POST['idSup']."'
						LIMIT 1");
	
	if($supUsr){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'La pré-inscription a bien été supprimée.'));
	}
	db_close($bd);
	
}//fin if suppression

//récup liste préinscripton
$bd = db_connect();
$tabPreInscrits = db_tableau($bd, "SELECT id, prenom, nom, code FROM membres_prebenevoles");			
db_close($bd);


include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Pré-inscriptions</h3>
<?php
if($tabPreInscrits !== false && !(empty($tabPreInscrits))){

	echo '<table><tbody>';
	echo '<tr><th style="width:200px">Nom</th><th>Lien à communiquer à la personne</th></tr>';
	for($i=0; $i<count($tabPreInscrits); $i++){
		echo '<tr><td>'.$tabPreInscrits[$i][1].' '.$tabPreInscrits[$i][2].'</td>
				<td><a href="http://'.$_SERVER['HTTP_HOST'].'/inscr-'.$tabPreInscrits[$i][3].'">http://'.$_SERVER['HTTP_HOST'].'/inscr-'.$tabPreInscrits[$i][3].'</a></td>
				<td class="suppr" onclick="suppr('.$tabPreInscrits[$i][0].')"></td></tr>';
	}
	echo '</tbody></table>';
	echo '<form method=post action="newMembre.php" id="formSup"><input type="hidden" id="idSup" name="idSup" /></form>';
}else{
	echo '<div>Pas de pré-inscription en cours.</div>';
}

?>
<h3>Ajouter un bénévole</h3>

<form method=post action="newMembre.php" id="formAdd">
<label for="prenom">prénom</label>
<input onkeyup="editUsrName()" type="text" id="prenom" name="prenom" maxlength=30 autocomplete="off"/>

<label for="nom">nom</label>
<input onkeyup="editUsrName()" type="text" id="nom" name="nom" maxlength=30 autocomplete="off"/>


<label for="usrname">identifiant</label>
<input type="text" disabled="disabled" id="usrname" name="usrname" />


<label for="arrived">mois d'arrivée dans l'association</label>
<input type="month" id="arrived" name="arrived" autocomplete="off" value="<?php echo date("Y-m") ?>"/>


<label for="dateProba">période probatoire</label>
<table class="invisible" style="margin-top:5px"><tbody><tr>
<td style="vertical-align:bottom"><input type="checkbox" id="isProba" name="isProba" onchange="changeProba()" <?php echo (($tabChamps['dureeProb']['valeur']>0)?"checked":"")?>/> 
<label class="checkbox" for="isProba" style="margin-right:5px">Jusqu'au</label></td>
<td style="vertical-align:bottom"><input type="date" id="dateProba" name="dateProba" style="margin-left:0; width:212px" maxlength=10 <?php echo (($tabChamps['dureeProb']['valeur']>0)?"":"disabled")?> value="<?php echo date_format(date_add(date_create('now'),date_interval_create_from_date_string($tabChamps['dureeProb']['valeur']." week")),'Y-m-d');?>" autocomplete="off"/></td>
</tr></tbody></table>

<input type="button" onclick="submAdd()" id="submitAdd" value="valider" />

</form>


<script type="text/javascript">
function suppr(id){
document.getElementById('idSup').value = id;
document.getElementById('formSup').submit();
}
function editUsrName(){

	var prenom = document.getElementById('prenom').value;
	var nom = document.getElementById('nom').value;

	if (nom==""){
		document.getElementById('usrname').value = prenom;
		document.getElementById('usrname').value = document.getElementById('usrname').value.replace(/\s+/g,'.');
	}
	if (prenom==""){
		document.getElementById('usrname').value = nom;
		document.getElementById('usrname').value = document.getElementById('usrname').value.replace(/\s+/g,'.');
	}
	if (nom!="" && prenom!=""){
		document.getElementById('usrname').value = prenom.toLowerCase()+"_"+nom.toLowerCase();
		document.getElementById('usrname').value = document.getElementById('usrname').value.replace(/\s+/g,'.');
	}

}

function changeProba(){
	if(document.getElementById('isProba').checked==true){
		document.getElementById('dateProba').disabled=false;
	}else{
		document.getElementById('dateProba').disabled=true;
	}
}

function submAdd(){
	document.getElementById('submitAdd').disabled=true;
	document.getElementById('submitAdd').value = "Patientez...";
	document.getElementById('submitAdd').onclick="";
	document.getElementById('formAdd').submit();
}
</script> 
<?php
echo $footer;
?>