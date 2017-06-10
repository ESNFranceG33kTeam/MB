<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Prix des cotisations");



//nouvelle cotisation
if(isset($_POST['descr'])&&isset($_POST['prix'])){

	if(!($_POST['typeCotis']=="cotisAdh" || $_POST['typeCotis']=="cotisESN")){
		array_push($pageMessages, array('type'=>'err', 'content'=>"Type de cotisation non valide."));
	}
	
	if(empty($_POST['descr'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom de la cotisation</em>.'));
	}elseif (mb_strlen($_POST['descr'])>999){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom de la cotisation</em> ne doit pas dépasser 999 caractères.'));
	}
	
	if(empty($_POST['prix'])&&!is_numeric($_POST['prix'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Prix</em>.'));
	}elseif(!is_numeric($_POST['prix'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Prix</em> n\'est pas valide.'));
	}elseif($_POST['prix']<0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Prix</em> n\'est pas valide.'));
	}elseif (mb_strlen($_POST['prix'])>7){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Prix</em> ne doit pas dépasser 7 caractères.'));
	}
	
	if(empty($pageMessages)){
		
		if($_POST['typeCotis']=="cotisAdh"){
			$typeCotis="Adh_Special";
		}elseif($_POST['typeCotis']=="cotisESN"){
			$typeCotis="ESN_Special";
		}
		
		$bd = db_connect();
		
		$_POST['descr'] = mysqli_real_escape_string($bd, $_POST['descr']);
						
		$addCotis = db_exec($bd, "
					INSERT INTO gestion_cotisations_types(descr, prix, type)
					VALUES('".$_POST['descr']."','".$_POST['prix']."','".$typeCotis."')");
		
		db_close($bd);
		
		if($addCotis!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La nouvelle cotisation a bien été ajoutée.'));
		}		
	}
}//fin nouvelle cotisation


//editPrix
if(isset($_POST['descrEdit']) && isset($_POST['prixEdit'])&& isset($_POST['idEdit'])){


	if(empty($_POST['descrEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom de la cotisation</em>.'));
	}elseif (mb_strlen($_POST['descrEdit'])>999){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom de la cotisation</em> ne doit pas dépasser 999 caractères.'));
	}
	
	if(empty($_POST['prixEdit'])&&!is_numeric($_POST['prixEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Prix</em>.'));
	}elseif(!is_numeric($_POST['prixEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Prix</em> n\'est pas valide.'));
	}elseif($_POST['prixEdit']<0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Prix</em> n\'est pas valide.'));
	}elseif (mb_strlen($_POST['prixEdit'])>7){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Prix</em> ne doit pas dépasser 7 caractères.'));
	}
	
	if(empty($pageMessages)){
		
		$bd = db_connect();
		
		$_POST['descrEdit'] = mysqli_real_escape_string($bd, $_POST['descrEdit']);
						
		$editCotis = db_exec($bd, "
					UPDATE gestion_cotisations_types
					SET prix='".$_POST['prixEdit']."', descr='".$_POST['descrEdit']."'
					WHERE id='".$_POST['idEdit']."'");
		
		db_close($bd);
		
		if($editCotis!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La cotisation à bien été modifiée.'));
		}		
	}
}//fin editPrix

//Suppr Cotis
if(isset($_POST['idSup'])){
		
	$bd = db_connect();
					
	$supCotis = db_exec($bd, "
				DELETE FROM gestion_cotisations_types
				WHERE id='".$_POST['idSup']."'
				LIMIT 1");
	
	db_close($bd);
	
	if($supCotis!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'La cotisation à bien été supprimée.'));
	}		
}//fin suppr Cottis



//Récupération des données
$bd = db_connect();

$typesCotis = db_tableau($bd, "		
			SELECT id, descr, prix, type
			FROM gestion_cotisations_types
			ORDER BY prix DESC");	
			
db_close($bd);		
	
if($typesCotis!==false && !empty($typesCotis)){
	$tabCotisAdhNormal="";
	$tabCotisAdhSpecial="";
	$tabCotisESNNormal="";
	$tabCotisESNSpecial="";
	
	for($i=0; $i<count($typesCotis); $i++){		
		
		if($typesCotis[$i]['type']=='Adh_Normal'){
			$tabCotisAdhNormal.='<tr><td id="cellCotisDescr'.$typesCotis[$i]['id'].'">'.$typesCotis[$i]['descr'].'</td>
				<td id="cellCotisPrix'.$typesCotis[$i]['id'].'" style="width:143px">'.$typesCotis[$i]['prix'].'€</td>
				<td id="cellCotisEdit'.$typesCotis[$i]['id'].'" class="edit" onclick="editCotis('.$typesCotis[$i]['id'].',\''.str_replace("'","\'", $typesCotis[$i]['descr']).'\','.$typesCotis[$i]['prix'].')"></td>
				<td style="width:13px"></td></tr>';
		
		}elseif($typesCotis[$i]['type']=='Adh_Special'){
			$tabCotisAdhSpecial.='<tr><td id="cellCotisDescr'.$typesCotis[$i]['id'].'">'.$typesCotis[$i]['descr'].'</td>
				<td id="cellCotisPrix'.$typesCotis[$i]['id'].'" style="width:143px">'.$typesCotis[$i]['prix'].'€</td>
				<td id="cellCotisEdit'.$typesCotis[$i]['id'].'" class="edit" onclick="editCotis('.$typesCotis[$i]['id'].',\''.str_replace("'","\'", $typesCotis[$i]['descr']).'\','.$typesCotis[$i]['prix'].')"></td>
				<td id="cellCotisRemove'.$typesCotis[$i]['id'].'" class="suppr" onclick="submRemoveCotis('.$typesCotis[$i]['id'].')"></td></tr>';
		
		}elseif($typesCotis[$i]['type']=='ESN_Normal'){
			$tabCotisESNNormal.='<tr><td id="cellCotisDescr'.$typesCotis[$i]['id'].'">'.$typesCotis[$i]['descr'].'</td>
				<td id="cellCotisPrix'.$typesCotis[$i]['id'].'" style="width:143px">'.$typesCotis[$i]['prix'].'€</td>
				<td id="cellCotisEdit'.$typesCotis[$i]['id'].'" class="edit" onclick="editCotis('.$typesCotis[$i]['id'].',\''.str_replace("'","\'", $typesCotis[$i]['descr']).'\','.$typesCotis[$i]['prix'].')"></td>
				<td style="width:13px"></td></tr>';
	
		}elseif($typesCotis[$i]['type']=='ESN_Special'){
			$tabCotisESNSpecial.='<tr><td id="cellCotisDescr'.$typesCotis[$i]['id'].'">'.$typesCotis[$i]['descr'].'</td>
				<td id="cellCotisPrix'.$typesCotis[$i]['id'].'" style="width:143px">'.$typesCotis[$i]['prix'].'€</td>
				<td id="cellCotisEdit'.$typesCotis[$i]['id'].'" class="edit" onclick="editCotis('.$typesCotis[$i]['id'].',\''.str_replace("'","\'", $typesCotis[$i]['descr']).'\','.$typesCotis[$i]['prix'].')"></td>
				<td id="cellCotisRemove'.$typesCotis[$i]['id'].'" class="suppr" onclick="submRemoveCotis('.$typesCotis[$i]['id'].')"></td></tr>';
		}
	}
}
			
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<h3>Cotisations Adhérents</h3>
<?php
if(!empty($tabCotisAdhNormal)){

	echo '<table><tbody>';
	echo '<tr><th>Type de cotisation</th>
		<th id="thPrixAdh">Prix<tr></th>';
	echo $tabCotisAdhNormal;
	echo '</tbody></table>';
}else{
	echo "<br/>Pas de données.<br/>";
}

if(!empty($tabCotisAdhSpecial)){

	echo '<table><tbody>';
	echo $tabCotisAdhSpecial;
	echo '</tbody></table>';
}

?>

<h3>Cotisations Bénévoles ESN</h3>
<?php
if(!empty($tabCotisESNNormal)){

	echo '<table><tbody>';
	echo '<tr><th>Type de cotisation</th>
		<th id="thPrixESN">Prix</th></tr>';
	echo $tabCotisESNNormal;
	echo '</tbody></table>';
}else{
	echo "<br/>Pas de données.<br/>";
}
if(!empty($tabCotisESNSpecial)){

	echo '<table><tbody>';
	echo $tabCotisESNSpecial;
	echo '</tbody></table>';
}
?>

<form method=post action="prixCotisations.php" id="formEditCotis" style="display:none">
<input type="hidden" id="idEdit" name="idEdit" value=""/>
<input type="hidden" id="descrEdit" name="descrEdit" value=""/>
<input type="hidden" id="prixEdit" name="prixEdit" value=""/>
</form>

<form method=post action="prixCotisations.php" id="formRemoveCotis" style="display:none">
<input type="hidden" id="idSup" name="idSup" value=""/>
</form>

<h3>Nouveau type de cotisation</h3>

<form method=post action="prixCotisations.php" id="formNewCotis">

<label for="typeCotis">type</label>
	<input id="typeCotisA" type="radio" name="typeCotis" value="cotisAdh" onclick="selectType()">  
	<label id="labelCotisA" class="radio" for="typeCotisA" onclick="selectType()">Cotisation Adhérents</label>
	<input id="typeCotisE" type="radio" name="typeCotis" value="cotisESN" onclick="selectType()">
	<label id="labelCotisE" class="radio" for="typeCotisE" onclick="selectType()">Cotisation Bénévoles</label>	
	
	
<div id="divNewCotis" style="display:none">
<label for="descr">nom de la cotisation</label>
<input type="text" id="descr" name="descr" autocomplete="off"/>
	
<label id="labelPrix" for="prix">prix</label>
<input type="text" class="euro" id="prix" name="prix" style="width:140px" maxlength=7 autocomplete="off"/>
	
<input type="button" onclick="submNewCotis()" id="submitNewCotis" value="valider" />
</div>
</form>


<script type="text/javascript">


function selectType(){
	if(document.getElementById('typeCotisA').checked==true || document.getElementById('typeCotisE').checked==true){
		document.getElementById('divNewCotis').style.display="";
	}else{
		document.getElementById('divNewCotis').style.display="none";
	}
}


function editCotis(id, descr, prix){

	document.getElementById('cellCotisDescr'+id).innerHTML='<input type="text" id="descrEdit'+id+'" name="descrEdit" value="'+descr+'" style="width:577px; margin:0" maxlength=999 autocomplete="off"/>';
	document.getElementById('cellCotisPrix'+id).innerHTML='<input type="text" class="euro" id="prixEdit'+id+'" name="prixEdit" value='+prix+' style="width:121px; margin:0" maxlength=7 autocomplete="off"/>';
	document.getElementById('cellCotisEdit'+id).className="tick";
	document.getElementById('cellCotisEdit'+id).onclick=function(){submEditCotis(id);};

}


function submEditCotis(id){
	if(!isNaN(document.getElementById('prixEdit'+id).value) && Number(document.getElementById('prixEdit'+id).value)>=0){
		document.getElementById('cellCotisEdit'+id).onclick="";
		document.getElementById('idEdit').value = id;
		document.getElementById('descrEdit').value = document.getElementById('descrEdit'+id).value;
		document.getElementById('prixEdit').value = document.getElementById('prixEdit'+id).value;
		document.getElementById('formEditCotis').submit();
	}else{
		alert("Prix invalide.");
	}
}	

	
function submNewCotis(){

	if(!isNaN(document.getElementById('prix').value) && Number(document.getElementById('prix').value)>=0){
		document.getElementById('submitNewCotis').disabled=true;
		document.getElementById('submitNewCotis').value = "Patientez...";
		document.getElementById('submitNewCotis').onclick="";
		document.getElementById('formNewCotis').submit();
	}else{
		alert("Prix invalide.");
	}
}

function submRemoveCotis(id){
	if(confirm("Voulez-vous vraiment supprimer cette cotisation ?")){
		document.getElementById('cellCotisRemove'+id).onclick="";
		document.getElementById('idSup').value = id;
		document.getElementById('formRemoveCotis').submit();
	}
}


</script> 
<?php
echo $footer;
?>