<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Invitations OneDrive");

//edit
if(isset($_POST['idEdit'])){

	
	if(empty($_POST['idEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'ID inexistant.'));
	}
	if(!($_POST['typeEdit']=="Mem" || $_POST['typeEdit']=="Bur")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Type invalide.'));
	}
	if(!($_POST['valueEdit']=="none" || $_POST['valueEdit']=="invit" || $_POST['valueEdit']=="ok")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Valeur invalide.'));
	}
	
	if(empty($pageMessages)){
	
		$colonne = "";
		
		if($_POST['typeEdit']=="Mem"){
			$colonne = "gr_membres";
		}elseif($_POST['typeEdit']=="Bur"){
			$colonne = "gr_bureau";
		}
		
		$bd = db_connect();
						
		$edit = db_exec($bd, "
					UPDATE membres_onedrive_invits
					SET ".$colonne."='".$_POST['valueEdit']."'
					WHERE id='".$_POST['idEdit']."'");
		
		db_close($bd);
		
		if($edit!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Le statut a bien été modifié.'));
		}		
	}
}//fin edit



//récup liste
$bd = db_connect();
$tabMembres = db_tableau($bd, "SELECT ben.id, ben.prenom, ben.nom, mail_microsoft, drt.general, one.gr_membres, one.gr_bureau
								FROM membres_benevoles AS ben
								JOIN membres_droits AS drt ON ben.id = drt.id
								JOIN membres_onedrive_invits AS one ON ben.id = one.id
								ORDER BY drt.general DESC, ben.prenom ASC, ben.nom ASC");		
db_close($bd);

$liste="";


if($tabMembres !== false && !(empty($tabMembres))){
	for($i=0; $i<count($tabMembres); $i++){

		switch($tabMembres[$i][5]){
			case "invit" : $styleMem = "orange"; $textMem = "Invité"; break;
			case "ok" : $styleMem = "green"; $textMem = "OK"; break;
			default : $styleMem = "red"; $textMem = "Pas d'acccès";
		}
			
		if($tabMembres[$i][4]=="bureau"){
			switch($tabMembres[$i][6]){
				case "invit" : $styleBur = "orange"; $textBur = "Invité"; break;
				case "ok" : $styleBur = "green"; $textBur = "OK"; break;
				default : $styleBur = "red"; $textBur = "Pas d'acccès";
			}
		}else{
			switch($tabMembres[$i][6]){
				case "invit" : $styleBur = "red"; $textBur = "Invité"; break;
				case "ok" : $styleBur = "red"; $textBur = "OK"; break;
				default : $styleBur = "grisé"; $textBur = "Pas d'accès";
			}
		}
		
		$optionsOneDriveMem = '<option value="none" '.(($tabMembres[$i][5]=="none")?"selected":"").'>Pas d\'accès</option>
								<option value="invit"'.(($tabMembres[$i][5]=="invit")?"selected":"").'>Invité</option>
								<option value="ok" '.(($tabMembres[$i][5]=="ok")?"selected":"").'>OK</option>';
								
		$optionsOneDriveBur = '<option value="none" '.(($tabMembres[$i][6]=="none")?"selected":"").'>Pas d\'accès</option>
								<option value="invit"'.(($tabMembres[$i][6]=="invit")?"selected":"").'>Invité</option>
								<option value="ok" '.(($tabMembres[$i][6]=="ok")?"selected":"").'>OK</option>';
	
		$liste.= '<tr><td>'.$tabMembres[$i][1].' '.$tabMembres[$i][2].'</td>
			<td>'.$tabMembres[$i][3].'</td>
			<td id="tdMem'.$tabMembres[$i][0].'" class="'.$styleMem.'" style="width:130px">'.$textMem.'</td>
			<td id="tdLstMem'.$tabMembres[$i][0].'" class="'.$styleMem.'" style="display:none; width:130px">
				<select id="lstMem'.$tabMembres[$i][0].'" style="margin:0; width:100%">'.$optionsOneDriveMem.'</select></td>
			<td id="tdEditMem'.$tabMembres[$i][0].'" class="edit" onclick="edit(\'Mem\','.$tabMembres[$i][0].')"></td>
			
			<td id="tdBur'.$tabMembres[$i][0].'" class="'.$styleBur.'" style="width:130px">'.$textBur.'</td>
			<td id="tdLstBur'.$tabMembres[$i][0].'" class="'.$styleBur.'" style="display:none; width:130px">
				<select id="lstBur'.$tabMembres[$i][0].'" style="margin:0; width:100%">'.$optionsOneDriveBur.'</select></td>
			<td id="tdEditBur'.$tabMembres[$i][0].'" class="edit" onclick="edit(\'Bur\','.$tabMembres[$i][0].')"></td></tr>';
	
	}
}

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Suivi des invitations</h3>
<?php
if(!empty($liste)){

	echo '<table><tbody>';
	echo '<tr><th style="width:180px">Nom</th><th>Mail Microsoft</th><th colspan=2>Membres</th><th colspan=2>Bureau</th></tr>';
	echo $liste;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de données à afficher</div>';
}
?>
<form method=post action="invitsOnedrive.php" id="formEditOneDrive" style="display:none">
<input type="hidden" id="idEdit" name="idEdit" value=""/>
<input type="hidden" id="typeEdit" name="typeEdit" value=""/>
<input type="hidden" id="valueEdit" name="valueEdit" value=""/>
</form>

<script type="text/javascript">

	
function edit(type, id){

	document.getElementById('td'+type+id).style.display = "none";
	document.getElementById('tdLst'+type+id).style.display = "";
	document.getElementById('tdEdit'+type+id).className = "tick";
	document.getElementById('tdEdit'+type+id).onclick=function(){submEditOneDrive(type, id);};
}

function submEditOneDrive(type, id){

	document.getElementById('tdEdit'+type+id).onclick="";
	document.getElementById('idEdit').value = id;
	document.getElementById('typeEdit').value = type;
	document.getElementById('valueEdit').value = document.getElementById('lst'+type+id).value;
	document.getElementById('formEditOneDrive').submit();
}
</script> 
<?php
echo $footer;
?>