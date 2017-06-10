<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Gestion OneDrive");

//editConfig
if(isset($_POST['idEditConfig'])){
	
	if(empty($_POST['idEditConfig'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'ID inexistant.'));
	}
	
	if($_POST['idEditConfig']==3){
		if(empty($_POST['valueEditConfig'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nombre d\'entrées</em>.'));
		}elseif(!is_numeric($_POST['valueEditConfig'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Nombre d\'entrées</em> n\'est pas valide.'));
		}elseif($_POST['valueEditConfig']<=0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Nombre d\'entrées</em> n\'est pas valide.'));
		}elseif ($_POST['valueEditConfig']>500){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nombre d\'entrées</em> ne doit pas dépasser 500.'));
		}
	}

	
	if(empty($pageMessages)){
	
		$bd = db_connect();
		
		$_POST['valueEditConfig'] = mysqli_real_escape_string($bd, $_POST['valueEditConfig']);
						
		$edit = db_exec($bd, "
					UPDATE gestion_onedrive_config
					SET valeur='".$_POST['valueEditConfig']."'
					WHERE id='".$_POST['idEditConfig']."'");
		
		db_close($bd);
		
		if($edit!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La valeur a bien été modifiée.'));
		}		
	}
}//fin edit

//addFolder
if(isset($_POST['idFolder'])){

	if(empty($_POST['idFolder'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'ID incorrect.'));
	}
	if(!($_POST['typeFolder']=="racine" || $_POST['typeFolder']=="exclus" || $_POST['typeFolder']=="board")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Type invalide.'));
	}

	
	if(empty($pageMessages)){
	
		
		$bd = db_connect();
		
		$_POST['idFolder'] = mysqli_real_escape_string($bd, $_POST['idFolder']);
		$_POST['nameFolder'] = mysqli_real_escape_string($bd, $_POST['nameFolder']);
		
		if($_POST['typeFolder']=="racine"){
			$supRacine = db_exec($bd, "
						DELETE FROM gestion_onedrive_folders
						WHERE type='racine'
						LIMIT 1");
		}
		
		$addFolder = db_exec($bd, "
					INSERT INTO gestion_onedrive_folders(type, name, idFolder)
					VALUES('".$_POST['typeFolder']."','".$_POST['nameFolder']."','".$_POST['idFolder']."')");
		
		db_close($bd);
		
		$file = fopen(($GLOBALS['SITE']->getFolderData())."/exFiles.txt",'r+');
		fseek($file, 0);
		ftruncate($file, 0);
		fclose($file);
		
		if($addFolder!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Le dossier a bien été ajouté.'));
		}		
	}
}//fin ajout folder

//Suppr Folder
if(isset($_POST['idSup'])){
		
	$bd = db_connect();
					
	$supBouton = db_exec($bd, "
				DELETE FROM gestion_onedrive_folders
				WHERE id='".$_POST['idSup']."'
				LIMIT 1");
	
	db_close($bd);
	
	$file = fopen(($GLOBALS['SITE']->getFolderData())."/exFiles.txt",'r+');
	fseek($file, 0);
	ftruncate($file, 0);
	fclose($file);
	
	if($supBouton!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Le dossier a bien été supprimé de la liste.'));
	}		
}//fin suppr Bouton


//Reset Activity
if(isset($_POST['typeReset'])){

	if($_POST['typeReset']=="membre"){
		$chemin = ($GLOBALS['SITE']->getFolderData())."/NewFiles.html";
	
	}elseif($_POST['typeReset']=="bureau"){
		$chemin = ($GLOBALS['SITE']->getFolderData())."/NewFilesBoard.html";
	}
	
	$file = fopen($chemin,'r+');
	fseek($file, 0);
	ftruncate($file, 0);
	fclose($file);
	
	array_push($pageMessages, array('type'=>'ok', 'content'=>'La liste a bien été remise à zéro.'));

}


//Recup infos onedrive

$bd = db_connect();
$clientID = db_valeur($bd, "SELECT valeur FROM gestion_onedrive_config WHERE champ='client_id'");
$tabConfig = db_tableau($bd, "SELECT id, descr, valeur FROM gestion_onedrive_config");
db_close($bd);

define('REDIRECT', "http://".$_SERVER['SERVER_NAME']."/gestion/gestionOnedrive.php");
define('CLIENT_ID', $clientID);


$listeChampsConfig="";

if($tabConfig !== false && !(empty($tabConfig))){
	for($i=0; $i<count($tabConfig); $i++){

		$listeChampsConfig.= '<tr><td>'.$tabConfig[$i]['descr'].'</td>
				<td id="tdConfig'.$tabConfig[$i][0].'"><div class="hidden-inline" style="width:350px">'.$tabConfig[$i]['valeur'].'</div></td>
				<td id="tdConfigTxt'.$tabConfig[$i][0].'" style="display:none">
					<input type="text" id="txtConfig'.$tabConfig[$i][0].'" value="'.$tabConfig[$i]['valeur'].'" style="margin:0; box-sizing:border-box; height:inherit; width:100%"/></td>
				<td id="tdConfigEdit'.$tabConfig[$i][0].'" class="edit" onclick="editConfig('.$tabConfig[$i][0].')"></td></tr>';
	}
}

//récup liste
$bd = db_connect();
$tabFolders = db_tableau($bd, "SELECT id, type, name
							FROM gestion_onedrive_folders
							ORDER BY type");		
db_close($bd);

$racine = "Non configuré.";
$exclus = "";
$board = "";

if($tabFolders !== false){
	for($i=0; $i<count($tabFolders); $i++){

		if($tabFolders[$i]['type']=="racine"){
			$racine = $tabFolders[$i]['name'];
		
		}elseif($tabFolders[$i]['type']=="exclus"){
			$exclus .= '<tr><td>'.$tabFolders[$i]['name'].'</td><td class="suppr" id="tdSupFolder'.$tabFolders[$i]['id'].'" onclick="supprFolder('.$tabFolders[$i]['id'].')"></td></tr>';
		
		}elseif($tabFolders[$i]['type']=="board"){
			$board .= '<tr><td>'.$tabFolders[$i]['name'].'</td><td class="suppr" id="tdSupFolder'.$tabFolders[$i]['id'].'" onclick="supprFolder('.$tabFolders[$i]['id'].')"></td></tr>';
		}
	}
}




include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Compte OneDrive</h3>
<div class="blocText">
<a href="../fonctions/onedrive/login.php" target="_blank">Cliquez ici</a> pour vous logger.<br/>
L'application vous demandera votre consentement afin que le script puisse vérifier vos fichiers.
</div>
<br/>

<?php
if(!empty($listeChampsConfig)){

	echo '<table><tbody>';
	echo '<tr><th>Champ</th><th colspan=2>Valeur</th></tr>';
	echo $listeChampsConfig;
	echo '</tbody></table><br/>';
}
?>

<form method=post action="gestionOnedrive.php" id="formEditConfig" style="display:none">
<input type="hidden" id="idEditConfig" name="idEditConfig" value=""/>
<input type="hidden" id="valueEditConfig" name="valueEditConfig" value=""/>
</form>


<div class="blocText">
<a href="../fonctions/onedrive/checkNewFile.php" target="_blank">Cliquez ici</a> pour mettre à jour l'historique des modifications.<br/>
Pour un meilleur fonctionnement, il est préférable d'exécuter ce script régulierement à l'aide d'un cron job.
</div>

<h3>Dossier racine</h3>
<table><tbody>
	<tr><th>Nom du dossier racine</th></tr>
	<tr>
		<td><?php echo $racine; ?></td>
		<td id="tdRacineEdit" class="edit" onclick="chooseFolder('racine')"></td>
	</tr>
</tbody></table>

<h3>Dossiers exclus</h3>
<div class="blocText"><a onclick="chooseFolder('exclus')">Exclure un dossier (et ses sous-dossiers) de la recherche</a><br/>
Personne ne pourra voir les modifications concernant les fichiers de ces dossiers.
</div>
<?php
if(!empty($exclus)){

	echo '<table><tbody>';
	echo '<tr><th>Nom des dossier exclus</th></tr>';
	echo $exclus;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de dossiers exclus.</div>';
}
?>

<h3>Dossiers réservés au bureau</h3>
<div class="blocText"><a onclick="chooseFolder('board')">Réserver un dossier (et ses sous-dossiers) pour les membres du bureau</a><br/>
Seuls les membres du bureau pourront voir les modifications concernant les fichiers de ces dossiers.
</div>
<?php
if(!empty($board)){

	echo '<table><tbody>';
	echo '<tr><th>Nom des dossier réservés au bureau</th></tr>';
	echo $board;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de dossiers réservés au bureau.</div>';
}
?>

<h3>Historique OneDrive - Visible par les membres</h3>
<div class="blocText"><a onclick="raz('membre')">Remettre à zéro la liste</a></div>
<div class="actuOneDrive"><?php include(($GLOBALS['SITE']->getFolderData()).'/NewFiles.html'); ?></div>

<h3>Historique OneDrive - Visible par les membres du bureau</h3>
<div class="blocText"><a onclick="raz('bureau')">Remettre à zéro la liste</a></div>
<div class="actuOneDrive"><?php include(($GLOBALS['SITE']->getFolderData()).'/NewFilesBoard.html'); ?></div>


<form method=post action="gestionOnedrive.php" id="formAddFolder" style="display:none">
<input type="hidden" id="idFolder" name="idFolder" value=""/>
<input type="hidden" id="typeFolder" name="typeFolder" value=""/>
<input type="hidden" id="nameFolder" name="nameFolder" value=""/>
</form>

<form method=post action="gestionOnedrive.php" id="formSupFolder" style="display:none">
<input type="hidden" id="idSup" name="idSup" value=""/>
</form>

<form method=post action="gestionOnedrive.php" id="formResetActivity" style="display:none">
<input type="hidden" id="typeReset" name="typeReset" value=""/>
</form>

<script type="text/javascript">

function editConfig(id){

	document.getElementById('tdConfig'+id).style.display = "none";
	document.getElementById('tdConfigTxt'+id).style.display = "";
	document.getElementById('tdConfigEdit'+id).className = "tick";
	document.getElementById('tdConfigEdit'+id).onclick=function(){submEditConfig(id);};
}
function submEditConfig(id){

	document.getElementById('tdConfigEdit'+id).onclick="";
	document.getElementById('idEditConfig').value = id;
	document.getElementById('valueEditConfig').value = document.getElementById('txtConfig'+id).value;
	document.getElementById('formEditConfig').submit();
}

function supprFolder(id){
	if(confirm("Voulez-vous vraiment supprimer ce dossier de la liste ?")){
		document.getElementById('tdSupFolder'+id).onclick="";
		document.getElementById('idSup').value = id;
		document.getElementById('formSupFolder').submit();
	}
}

function raz(type){
	if(confirm("Voulez-vous vraiment remettre à zéro la liste ?")){
		document.getElementById('typeReset').value = type;
		document.getElementById('formResetActivity').submit();
	}
}

</script> 

<!-- FONCTIONS API ONEDRIVE !-->
<script src="//js.live.net/v5.0/wl.js"></script>
<script type="text/javascript">


var client_id = "<?php echo CLIENT_ID ?>" ;
var redirect_uri = "<?php echo REDIRECT ?>";
WL.init({ client_id: client_id, redirect_uri: redirect_uri});

function chooseFolder(type) {
	WL.login({ scope: "wl.skydrive wl.signin" }).then(
	WL.fileDialog({
			mode: "open",
			select: "multi"
	}).then(
			function (response) {
				if (response.data.folders.length > 0) {
					document.getElementById('idFolder').value = response.data.folders[0].id;
					document.getElementById('nameFolder').value = response.data.folders[0].name;
					document.getElementById('typeFolder').value = type;
					document.getElementById('formAddFolder').submit();
				}
			}
		)
	);
}

</script>

<?php
echo $footer;
?>