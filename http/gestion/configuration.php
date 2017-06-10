<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Configuration");


//editMessAccueil
if(isset($_POST['mess'])){
		
	$bd = db_connect();
	
	$_POST['mess'] = mysqli_real_escape_string($bd, $_POST['mess']);
					
	$edit = db_exec($bd, "
				UPDATE gestion_config_general
				SET valeur='".$_POST['mess']."'
				WHERE champ='messAccueil'");
	
	db_close($bd);
	
	if($edit!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Le message d\'accueil a bien été modifié.'));
	}		

}//fin edit


//editMessAccueil
if(isset($_POST['cgu'])){
		
	$bd = db_connect();
	
	$_POST['cgu'] = mysqli_real_escape_string($bd, $_POST['cgu']);
					
	$edit = db_exec($bd, "
				UPDATE gestion_config_general
				SET valeur='".$_POST['cgu']."'
				WHERE champ='cgu'");
	
	db_close($bd);
	
	if($edit!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Les conditions générales de vente ont bien été modifiées.'));
	}		

}//fin edit



//editChampsGen
if(isset($_POST['idEditGen'])){
	
	if(empty($_POST['idEditGen'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'ID inexistant.'));
	}

	
	if(empty($pageMessages)){
	
		$bd = db_connect();
		
		$_POST['valueEditGen'] = mysqli_real_escape_string($bd, $_POST['valueEditGen']);
						
		$edit = db_exec($bd, "
					UPDATE gestion_config_general
					SET valeur='".$_POST['valueEditGen']."'
					WHERE id='".$_POST['idEditGen']."'");
		
		db_close($bd);
		
		if($edit!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La valeur a bien été modifiée.'));
		}		
	}
}//fin edit

//nouveau bouton
if(isset($_POST['nomNewBB'])){
	
	if(mb_strlen($_POST['posNewBB'])>3){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Position</em> ne doit pas dépasser 3 caractères.'));
	}	
	if (!is_numeric($_POST['posNewBB'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Position</em> n\'est pas valide.'));
	}elseif($_POST['posNewBB']<0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Position</em> n\'est pas valide.'));
	}
		
	if(empty($pageMessages)){
		$bd = db_connect();
		
		$_POST['nomNewBB'] = mysqli_real_escape_string($bd, $_POST['nomNewBB']);
						
		$addCotis = db_exec($bd, "
					INSERT INTO gestion_config_boutonsbar(nom, position)
					VALUES('".$_POST['nomNewBB']."', '".$_POST['posNewBB']."')");
		
		db_close($bd);
		
		if($addCotis!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La nouveau bouton a bien été ajouté.'));
		}	
	}
}//fin nouveau bouton


//editBoutonsBar
if(isset($_POST['idEditBB'])){

	
	if(empty($_POST['idEditBB'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'ID inexistant.'));
	}
	if(!($_POST['typeEditBB']=="Pro" || $_POST['typeEditBB']=="Mem" || $_POST['typeEditBB']=="Bur" || $_POST['typeEditBB']=="Nom" )){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Type invalide.'));
	}
	
	if($_POST['typeEditBB']=="Nom"){
		if(mb_strlen($_POST['posEditBB'])>3){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Position</em> ne doit pas dépasser 3 caractères.'));
		}	
		if (!is_numeric($_POST['posEditBB'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Position</em> n\'est pas valide.'));
		}elseif($_POST['posEditBB']<0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Position</em> n\'est pas valide.'));
		}
	}
	if(empty($pageMessages)){
	
		$colonne = "";
		$position = "";
		
		if($_POST['typeEditBB']=="Pro"){
			$colonne = "link_probatoire";
		}elseif($_POST['typeEditBB']=="Mem"){
			$colonne = "link_membres";
		}elseif($_POST['typeEditBB']=="Bur"){
			$colonne = "link_bureau";
		}elseif($_POST['typeEditBB']=="Nom"){
			$colonne = "nom";
			$position = " , position='".$_POST['posEditBB']."'";
		}
		
		$bd = db_connect();
		$_POST['valueEditBB'] = mysqli_real_escape_string($bd, $_POST['valueEditBB']);
		
		$edit = db_exec($bd, "
					UPDATE gestion_config_boutonsbar
					SET ".$colonne."='".$_POST['valueEditBB']."'".$position."
					WHERE id='".$_POST['idEditBB']."'");
		
		db_close($bd);
		
		if($edit!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Le bouton a bien été modifié.'));
		}		
	}
}//fin edit

//Suppr Bouton
if(isset($_POST['idSupBB'])){
		
	$bd = db_connect();
					
	$supBouton = db_exec($bd, "
				DELETE FROM gestion_config_boutonsbar
				WHERE id='".$_POST['idSupBB']."'
				LIMIT 1");
	
	db_close($bd);
	
	if($supBouton!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Le bouton a bien été supprimé.'));
	}		
}//fin suppr Bouton


//Edit Résidences
if(isset($_POST['valueEditResidences'])){
	
	$fileResidences = fopen(($GLOBALS['SITE']->getFolderData()).'/liste_residences.html','r+');
	fseek($fileResidences, 0);
	ftruncate($fileResidences, 0);
	fputs($fileResidences, $_POST['valueEditResidences']);
	fclose($fileResidences);
	
	array_push($pageMessages, array('type'=>'ok', 'content'=>'La liste des résidences a bien été modifiée.'));	
}

//Edit Etudes
if(isset($_POST['valueEditEtudes'])){
	
	$fileEtudes = fopen(($GLOBALS['SITE']->getFolderData()).'/liste_etudes.html','r+');
	fseek($fileEtudes, 0);
	ftruncate($fileEtudes, 0);
	fputs($fileEtudes, $_POST['valueEditEtudes']);
	fclose($fileEtudes);
	
	array_push($pageMessages, array('type'=>'ok', 'content'=>'La liste des études a bien été modifiée.'));	
}


//récup liste
$bd = db_connect();
$tabChampsGen = db_tableau($bd, "SELECT id, champ, descr, valeur FROM gestion_config_general");
$tabChamps = db_tableau($bd, "SELECT champ, valeur FROM gestion_config_general","champ"); //Reactualisation apres modifs POST
$tabBoutonsBar = db_tableau($bd, "SELECT id, nom, position, link_probatoire, link_membres, link_bureau FROM gestion_config_boutonsbar ORDER BY position ASC");
db_close($bd);





//Champs généraux
$listeGen="";

if($tabChampsGen !== false && !(empty($tabChampsGen))){
	for($i=1; $i<count($tabChampsGen); $i++){

		if($i != 8){ //On vire le mess accueil et les CGU de cette liste
			$listeGen.= '<tr><td>'.$tabChampsGen[$i]['descr'].'</td>
				<td id="tdChampGen'.$tabChampsGen[$i][0].'"><div class="hidden-inline" style="width:250px">'.$tabChampsGen[$i]['valeur'].'</div></td>
				<td id="tdChampGenTxt'.$tabChampsGen[$i][0].'" style="display:none">
					<input type="text" id="txtChampGen'.$tabChampsGen[$i][0].'" value="'.$tabChampsGen[$i]['valeur'].'" style="margin:0; box-sizing:border-box; height:inherit; width:100%"/></td>
				<td id="tdChampGenEdit'.$tabChampsGen[$i][0].'" class="edit" onclick="editGen('.$tabChampsGen[$i][0].')"></td></tr>';
		}
	}
}
$listeBoutons="";

//Liste boutons
if($tabBoutonsBar !== false && !(empty($tabBoutonsBar))){
	for($i=0; $i<count($tabBoutonsBar); $i++){

		$listeBoutons.= '<tbody><tr><td id="tdRemoveBouton'.$tabBoutonsBar[$i][0].'" rowspan=3 class="suppr" onclick="submRemoveBouton('.$tabBoutonsBar[$i][0].')">
		
			<td id="tdLinkNomEdit'.$tabBoutonsBar[$i][0].'" rowspan=3 class="edit" onclick="editBouton(\'Nom\','.$tabBoutonsBar[$i][0].')"></td>
			<td id="tdLinkNom'.$tabBoutonsBar[$i][0].'" rowspan=3 style="width:140px">
				<div class="hidden-inline" style="width:160px">'.$tabBoutonsBar[$i]['nom'].'</div>
				<span style="line-height:1.15em; font-size:0.8em">Position : '.$tabBoutonsBar[$i]['position'].'</span></td>
			<td id="tdLinkNomTxt'.$tabBoutonsBar[$i][0].'" rowspan=3 style="display:none">
				<input type="text" id="txtLinkNom'.$tabBoutonsBar[$i][0].'" value="'.$tabBoutonsBar[$i]['nom'].'" style="margin:0; box-sizing:border-box; height:inherit; width:100%"/>
				Pos : <input type="text" id="posLinkNom'.$tabBoutonsBar[$i][0].'" value="'.$tabBoutonsBar[$i]['position'].'" style="margin:0; box-sizing:border-box; height:inherit; width:50px" maxlength=3/></td>
		
		
			<td id="tdLinkPro'.$tabBoutonsBar[$i][0].'"><div class="hidden-inline" style="width:540px">En probation : '.((empty($tabBoutonsBar[$i]['link_probatoire']))?'Invisible':'<a href="'.$tabBoutonsBar[$i]['link_probatoire'].'" target="_blank">'.$tabBoutonsBar[$i]['link_probatoire']).'</a></div></td>
			<td id="tdLinkProTxt'.$tabBoutonsBar[$i][0].'" style="display:none">
				<input type="text" id="txtLinkPro'.$tabBoutonsBar[$i][0].'" value="'.$tabBoutonsBar[$i]['link_probatoire'].'" style="margin:0; box-sizing:border-box; height:inherit; width:100%"/></td>
			<td id="tdLinkProEdit'.$tabBoutonsBar[$i][0].'" class="edit" onclick="editBouton(\'Pro\','.$tabBoutonsBar[$i][0].')"></td></tr>
		
		
			<tr><td id="tdLinkMem'.$tabBoutonsBar[$i][0].'"><div class="hidden-inline" style="width:540px">Membres : '.((empty($tabBoutonsBar[$i]['link_membres']))?'Invisible':'<a href="'.$tabBoutonsBar[$i]['link_membres'].'" target="_blank">'.$tabBoutonsBar[$i]['link_membres']).'</a></div></td>
			<td id="tdLinkMemTxt'.$tabBoutonsBar[$i][0].'" style="display:none">
				<input type="text" id="txtLinkMem'.$tabBoutonsBar[$i][0].'" value="'.$tabBoutonsBar[$i]['link_membres'].'" style="margin:0; box-sizing:border-box; height:inherit; width:100%"/></td>
			<td id="tdLinkMemEdit'.$tabBoutonsBar[$i][0].'" class="edit" onclick="editBouton(\'Mem\','.$tabBoutonsBar[$i][0].')"></td></tr>
			
			
			<tr><td id="tdLinkBur'.$tabBoutonsBar[$i][0].'"><div class="hidden-inline" style="width:540px">Bureau : '.((empty($tabBoutonsBar[$i]['link_bureau']))?'Invisible':'<a href="'.$tabBoutonsBar[$i]['link_bureau'].'" target="_blank">'.$tabBoutonsBar[$i]['link_bureau']).'</a></div></td>
			<td id="tdLinkBurTxt'.$tabBoutonsBar[$i][0].'" style="display:none">
				<input type="text" id="txtLinkBur'.$tabBoutonsBar[$i][0].'" value="'.$tabBoutonsBar[$i]['link_bureau'].'" style="margin:0; box-sizing:border-box; height:inherit; width:100%"/></td>
			<td id="tdLinkBurEdit'.$tabBoutonsBar[$i][0].'" class="edit" onclick="editBouton(\'Bur\','.$tabBoutonsBar[$i][0].')"></td></tr></tbody>';
	
	}
}
$listeBoutons.='<tr><td colspan=3 style="text-align:right">Nouveau bouton : </td>
				<td>
					<input type="text" id="newBouton" style="margin:0; box-sizing:border-box; height:inherit; width:78%"/>
					Position : <input type="text" id="posNewBouton" style="margin:0; box-sizing:border-box; height:inherit; width:50px" maxlength=3/>
				</td>
				
				<td id="tdAddBouton" class="add" onclick="submAddBouton()"></td></tr>';


include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
initBBCode();
?>
<h3>Message d'accueil</h3>
<form method=post action="configuration.php" id="formEditMessAccueil">
<?php addTextAreaBBCode("mess", "mess", "", $tabChampsGen[0]['valeur']); ?>
<input type="button" onclick="submMessAccueil()" id="submitMessAccueil" value="valider" />
</form>


<h3>Nettoyer la liste d'adhérents</h3>
<div class="blocText">
La loi ordonne aux associations de supprimer les données des adhérents dont la cotisation a expirée. <br/>
<a href="../membres/purgeAdh.php" target="_blank">Cliquez ici</a> pour supprimer les adhérents dont l'inscription remonte à il y a plus d'un an.
</div>

<h3>Champs généraux</h3>
<?php
if(!empty($listeGen)){

	echo '<table><tbody>';
	echo '<tr><th>Champ</th><th colspan=2>Valeur</th></tr>';
	echo $listeGen;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de données à afficher</div>';
}
?>
<form method=post action="configuration.php" id="formEditGen" style="display:none">
<input type="hidden" id="idEditGen" name="idEditGen" value=""/>
<input type="hidden" id="valueEditGen" name="valueEditGen" value=""/>
</form>

<h3>Barre de boutons</h3>
<?php
if(!empty($listeBoutons)){

	echo '<table class="tabBoutonsBar"><tbody>';
	echo '<tr><th colspan=3>Bouton</th><th colspan=2>Lien</th></tr>';
	echo $listeBoutons;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de données à afficher</div>';
}
?>


<form method=post action="configuration.php" id="formEditBoutonsBar" style="display:none">
<input type="hidden" id="idEditBB" name="idEditBB" value=""/>
<input type="hidden" id="typeEditBB" name="typeEditBB" value=""/>
<input type="hidden" id="posEditBB" name="posEditBB" value=""/>
<input type="hidden" id="valueEditBB" name="valueEditBB" value=""/>
</form>
<form method=post action="configuration.php" id="formRemoveBoutonsBar" style="display:none">
<input type="hidden" id="idSupBB" name="idSupBB" value=""/>
</form>
<form method=post action="configuration.php" id="formAddBoutonsBar" style="display:none">
<input type="hidden" id="nomNewBB" name="nomNewBB" value=""/>
<input type="hidden" id="posNewBB" name="posNewBB" value=""/>
</form>


<h3>Liste des résidences</h3>
<div class="blocText">Sous la forme : Nom//Adresse//Code postal//Ville<br/>Pour créer une catégorie insérez un chevron ">". Exemple : >Résidences privées</div>
<form method=post action="configuration.php" id="formEditResidences">
<textarea id="valueEditResidences" name="valueEditResidences" style="box-sizing:border-box; height:120px; width:100%; resize:vertical"><?php include_once(($GLOBALS['SITE']->getFolderData()).'/liste_residences.html')?></textarea>
<input type="button" onclick="submitResidences()" id="submitEditResidences" value="valider" />
</form>

<h3>Liste des études</h3>
<div class="blocText">Indiquez simplement une fac/école par ligne<br/>Pour créer une catégorie, insérez un chevron ">". Exemple : >Ecoles d'ingénieurs</div>
<form method=post action="configuration.php" id="formEditEtudes">
<textarea id="valueEditEtudes" name="valueEditEtudes" style="box-sizing:border-box; height:120px; width:100%; resize:vertical"><?php include_once(($GLOBALS['SITE']->getFolderData()).'/liste_etudes.html')?></textarea>
<input type="button" onclick="submitEtudes()" id="submitEditEtudes" value="valider" />
</form>


<h3>Conditions générales de vente</h3>
<div class="blocText">Si vous le désirez, vous pouvez rédiger les conditions générales de vente relatives aux activités de votre association.
<br/>L'adhérent devra alors cocher une case lors de son inscription pour les accepter.</div>
<form method=post action="configuration.php" id="formEditCGU">
<?php addTextAreaBBCode("cgu", "cgu", "", $tabChampsGen[8]['valeur']); ?>
<input type="button" onclick="submMessCGU()" id="submitMessCGU" value="valider" />
</form>


<script type="text/javascript">


function submMessAccueil(){

	document.getElementById('submitMessAccueil').disabled=true;
	document.getElementById('submitMessAccueil').value = "Patientez...";
	document.getElementById('submitMessAccueil').onclick="";
	document.getElementById('formEditMessAccueil').submit();
}

function submMessCGU(){

	document.getElementById('submitMessCGU').disabled=true;
	document.getElementById('submitMessCGU').value = "Patientez...";
	document.getElementById('submitMessCGU').onclick="";
	document.getElementById('formEditCGU').submit();
}



function editGen(id){

	document.getElementById('tdChampGen'+id).style.display = "none";
	document.getElementById('tdChampGenTxt'+id).style.display = "";
	document.getElementById('tdChampGenEdit'+id).className = "tick";
	document.getElementById('tdChampGenEdit'+id).onclick=function(){submEditGen(id);};
}


function submEditGen(id){

	document.getElementById('tdChampGenEdit'+id).onclick="";
	document.getElementById('idEditGen').value = id;
	document.getElementById('valueEditGen').value = document.getElementById('txtChampGen'+id).value;
	document.getElementById('formEditGen').submit();
}


function editBouton(type,id){

	document.getElementById('tdLink'+type+id).style.display = "none";
	document.getElementById('tdLink'+type+'Txt'+id).style.display = "";
	document.getElementById('tdLink'+type+'Edit'+id).className = "tick";
	document.getElementById('tdLink'+type+'Edit'+id).onclick=function(){submEditBouton(type, id);};
}
function submEditBouton(type, id){

	document.getElementById('tdLink'+type+'Edit'+id).onclick="";
	document.getElementById('idEditBB').value = id;
	document.getElementById('typeEditBB').value = type;
	document.getElementById('valueEditBB').value = document.getElementById('txtLink'+type+id).value;
	document.getElementById('posEditBB').value = document.getElementById('posLinkNom'+id).value;
	document.getElementById('formEditBoutonsBar').submit();
}
function submRemoveBouton(id){
	if(confirm("Voulez-vous vraiment supprimer ce bouton ?")){
		document.getElementById('tdRemoveBouton'+id).onclick="";
		document.getElementById('idSupBB').value = id;
		document.getElementById('formRemoveBoutonsBar').submit();
	}
}
function submAddBouton(){
	document.getElementById('tdAddBouton').onclick="";
	document.getElementById('nomNewBB').value = document.getElementById('newBouton').value;
	document.getElementById('posNewBB').value = document.getElementById('posNewBouton').value;
	document.getElementById('formAddBoutonsBar').submit();
}

function submitResidences(){

	document.getElementById('submitEditResidences').disabled=true;
	document.getElementById('submitEditResidences').value = "Patientez...";
	document.getElementById('submitEditResidences').onclick="";
	document.getElementById('formEditResidences').submit();
}

function submitEtudes(){

	document.getElementById('submitEditEtudes').disabled=true;
	document.getElementById('submitEditEtudes').value = "Patientez...";
	document.getElementById('submitEditEtudes').onclick="";
	document.getElementById('formEditEtudes').submit();
}
</script> 
<?php
echo $footer;
?>