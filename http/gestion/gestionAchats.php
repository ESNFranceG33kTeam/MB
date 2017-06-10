<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Gestion des achats");



//nouveau produit
if(isset($_POST['nom'])){
	
	if(empty($_POST['nom'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom</em>.'));
	
	}elseif (mb_strlen($_POST['nom'])>200){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom</em> ne doit pas dépasser 200 caractères.'));
	}
	
	if($_POST['radioQte']=="limited"){
		if(empty($_POST['qte'])&&!is_numeric($_POST['qte'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Quantité</em>.'));
		}elseif(!is_numeric($_POST['qte'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Quantité</em> n\'est pas valide.'));
		}elseif($_POST['qte']<0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Quantité</em> n\'est pas valide.'));
		}elseif (mb_strlen($_POST['qte'])>7){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Quantité</em> ne doit pas dépasser 7 caractères.'));
		}
		$qte = $_POST['qte'];
		
	}else{
		$qte = 0;
	}
	
	if($_POST['radioPrix']=="payant"){
		if(empty($_POST['prix'])&&!is_numeric($_POST['prix'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Prix</em>.'));
		}elseif(!is_numeric($_POST['prix'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Prix</em> n\'est pas valide.'));
		}elseif($_POST['prix']<0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Prix</em> n\'est pas valide.'));
		}elseif (mb_strlen($_POST['prix'])>7){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Prix</em> ne doit pas dépasser 7 caractères.'));
		}
		$prix = $_POST['prix'];
		
	}else{
		$prix = 0;
	}
	
	
	if(empty($pageMessages)){
		
		$bd = db_connect();
		
		$_POST['nom'] = mysqli_real_escape_string($bd, $_POST['nom']);
						
		$addCotis = db_exec($bd, "
					INSERT INTO gestion_achats_produits(nom, qte, prix)
					VALUES('".$_POST['nom']."','".$qte."','".$prix."')");
		
		db_close($bd);
		
		if($addCotis!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La produit a bien été ajouté.'));
		}		
	}
}//fin nouveau produit


//edit produit
if(isset($_POST['idEdit'])){

	
	if(empty($_POST['nomEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom</em>.'));
	
	}elseif (mb_strlen($_POST['nomEdit'])>200){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom</em> ne doit pas dépasser 200 caractères.'));
	}
	
	
	if($_POST['restEdit'] !== 0 && $_POST['restEdit'] != "Illimité"){
		if(empty($_POST['restEdit'])&&!is_numeric($_POST['restEdit'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Restants</em>.'));
		}elseif(!is_numeric($_POST['restEdit'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Restants</em> n\'est pas valide.'));
		}elseif($_POST['restEdit']<0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Restants</em> n\'est pas valide.'));
		}elseif (mb_strlen($_POST['restEdit'])>4){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Restants</em> ne doit pas dépasser 4 caractères.'));
		}
	}
	
	
	if($_POST['soldEdit'] !== 0){
		if(empty($_POST['soldEdit'])&&!is_numeric($_POST['soldEdit'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Vendus</em>.'));
		}elseif(!is_numeric($_POST['soldEdit'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Vendus</em> n\'est pas valide.'));
		}elseif($_POST['soldEdit']<0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Vendus</em> n\'est pas valide.'));
		}elseif (mb_strlen($_POST['soldEdit'])>4){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Vendus</em> ne doit pas dépasser 4 caractères.'));
		}
		
	}
	$sold = $_POST['soldEdit'];
	
	if($_POST['prixEdit'] !== 0){
		if(empty($_POST['prixEdit'])&&!is_numeric($_POST['prixEdit'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Prix</em>.'));
		}elseif(!is_numeric($_POST['prixEdit'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Prix</em> n\'est pas valide.'));
		}elseif($_POST['prixEdit']<0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Prix</em> n\'est pas valide.'));
		}elseif (mb_strlen($_POST['prixEdit'])>7){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Prix</em> ne doit pas dépasser 7 caractères.'));
		}
	}
	$prix = $_POST['prixEdit'];
	
	if(empty($pageMessages)){
	
		$qte = ($_POST['restEdit']=="Illimité")?0:intval($_POST['restEdit']+$sold);
		
		$bd = db_connect();
		
		$_POST['nomEdit'] = mysqli_real_escape_string($bd, $_POST['nomEdit']);
						
		$edit = db_exec($bd, "
					UPDATE gestion_achats_produits
					SET nom='".$_POST['nomEdit']."',
					qte='".$qte."',
					vendu='".$sold."',
					prix='".$prix."'
					WHERE id='".$_POST['idEdit']."'");
		
		db_close($bd);

		if($edit!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Les modifications ont bien été effectuées.'));
		}		
	}
}//fin edit produit

//Suppr Produit
if(isset($_POST['idSup'])){
		
	$bd = db_connect();
					
	$sup = db_exec($bd, "
				DELETE FROM gestion_achats_produits
				WHERE id='".$_POST['idSup']."'
				LIMIT 1");
	
	db_close($bd);
	
	if($sup!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Le produit à bien été supprimé.'));
	}		
}//fin suppr produit



//Récupération des données
$bd = db_connect();

$produits = db_tableau($bd, "		
			SELECT id, nom, qte, vendu, prix
			FROM gestion_achats_produits
			ORDER BY nom ASC");	
			
db_close($bd);		

$lstProduits="";

if($produits!==false && !empty($produits)){
		
	for($i=0; $i<count($produits); $i++){		
		
		$lstProduits.='<tr>
		
			<td id="cellNomProd'.$produits[$i]['id'].'" >'.$produits[$i]['nom'].'</td>
			<td id="cellEditNomProd'.$produits[$i]['id'].'" style="display:none">
				<input type="text" id="editNom'.$produits[$i]['id'].'" style="width:350px; margin:0" maxlength=200 autocomplete="off" value="'.$produits[$i]['nom'].'"/>
			</td>
			
			
			<td id="cellRestProd'.$produits[$i]['id'].'" >'.(($produits[$i]['qte']==0)? "Illimité" : intval($produits[$i]['qte']-$produits[$i]['vendu'])).'</td>
			<td id="cellEditRestProd'.$produits[$i]['id'].'" style="display:none">
				<input type="text" id="editRest'.$produits[$i]['id'].'" style="width:60px; margin:0" autocomplete="off" value="'.(($produits[$i]['qte']==0)? "Illimité" : intval($produits[$i]['qte']-$produits[$i]['vendu'])).'"/>
			</td>
			
			
			<td id="cellSoldProd'.$produits[$i]['id'].'">'.intval($produits[$i]['vendu']).'</td>
			<td id="cellEditSoldProd'.$produits[$i]['id'].'" style="display:none">
				<input type="text" id="editSold'.$produits[$i]['id'].'" style="width:60px; margin:0" maxlength=4 autocomplete="off" value="'.intval($produits[$i]['vendu']).'"/>
			</td>
			
			
			<td id="cellPrixProd'.$produits[$i]['id'].'">'.(($produits[$i]['prix']==0)? "Gratuit" : $produits[$i]['prix'].'€').'</td>
			<td id="cellEditPrixProd'.$produits[$i]['id'].'" style="display:none">
				<input type="text" id="editPrix'.$produits[$i]['id'].'" class="euro" style="width:60px; margin:0" maxlength=7 autocomplete="off" value="'.intval($produits[$i]['prix']).'"/>
			</td>
			
			
			<td id="cellEditProd'.$produits[$i]['id'].'" class="edit" onclick="editProd('.$produits[$i]['id'].')"></td>
			<td class="suppr" id="cellRemoveProd'.$produits[$i]['id'].'" onclick="supprProd('.$produits[$i]['id'].')"></td></tr>';

	}
}
			
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<h3>Liste des produits</h3>
<?php
if(!empty($lstProduits)){

	echo '<table><tbody>';
	echo '<tr><th>Produit</th><th style="width:70px">Restants</th><th style="width:70px">Vendus</th><th style="width:70px">Prix</th></tr>';
	echo $lstProduits;
	echo '</tbody></table>';
}else{
	echo "<br/>Pas de données.<br/>";
}

?>

<form method=post action="gestionAchats.php" id="formEditProd" style="display:none">
<input type="hidden" id="idEdit" name="idEdit" value=""/>
<input type="hidden" id="nomEdit" name="nomEdit" value=""/>
<input type="hidden" id="restEdit" name="restEdit" value=""/>
<input type="hidden" id="soldEdit" name="soldEdit" value=""/>
<input type="hidden" id="prixEdit" name="prixEdit" value=""/>
</form>

<form method=post action="gestionAchats.php" id="formRemoveProd" style="display:none">
<input type="hidden" id="idSup" name="idSup" value=""/>
</form>

<h3>Nouveau produit</h3>

<form method=post action="gestionAchats.php" id="formNewProduit">

<label for="nom">nom du produit - avec taille et/ou couleur</label>
<input type="text" id="nom" name="nom" onclick="affAdd()" maxlength=200 autocomplete="off" />

<div id="divAddProd" style="display:none">
	<label for="qte">quantité disponible</label>
		<input type="text" id="qte" name="qte" onclick="limit()" style="width:60px" maxlength=4 autocomplete="off"/>
		<input id="qteL" type="radio" name="radioQte" value="limited">
		<input id="qteU" type="radio" name="radioQte" value="unlimited" onclick="illimit()">  
		<label class="radio" for="qteU" onclick="illimit()">Illimité</label>

	<label for="prix">prix</label>
		<input type="text" id="prix" name="prix" class="euro" onclick="payant()" style="width:60px" maxlength=7" autocomplete="off"/>
		<input id="prixP" type="radio" name="radioPrix" value="payant">
		<input id="prixG" type="radio" name="radioPrix" value="gratuit" onclick="gratuit()">  
		<label class="radio" for="prixG" onclick="gratuit()">Gratuit</label>
		
	<input type="button" onclick="submNewProduit()" id="submitNewProduit" value="valider" />
</div>
</form>


<script type="text/javascript">

illimit();
gratuit();

function editProd(id){

	document.getElementById('cellNomProd'+id).style.display="none";
	document.getElementById('cellEditNomProd'+id).style.display="";
	
	document.getElementById('cellRestProd'+id).style.display="none";
	document.getElementById('cellEditRestProd'+id).style.display="";
	
	document.getElementById('cellSoldProd'+id).style.display="none";
	document.getElementById('cellEditSoldProd'+id).style.display="";
	
	document.getElementById('cellPrixProd'+id).style.display="none";
	document.getElementById('cellEditPrixProd'+id).style.display="";
	
	document.getElementById('cellEditProd'+id).className="tick";
	document.getElementById('cellEditProd'+id).onclick=function(){submEditProd(id);};

}


function submEditProd(id){

		document.getElementById('cellEditProd'+id).onclick="";
		
		document.getElementById('idEdit').value = id;
		document.getElementById('nomEdit').value = document.getElementById('editNom'+id).value;
		document.getElementById('restEdit').value = document.getElementById('editRest'+id).value;
		document.getElementById('soldEdit').value = document.getElementById('editSold'+id).value;
		document.getElementById('prixEdit').value = document.getElementById('editPrix'+id).value;

		document.getElementById('formEditProd').submit();

}	


function supprProd(id){
	if(confirm("Voulez-vous vraiment supprimer ce produit ?")){
		document.getElementById('cellRemoveProd'+id).onclick="";
		document.getElementById('idSup').value = id;
		document.getElementById('formRemoveProd').submit();
	}
}
	
function affAdd(){
	document.getElementById('divAddProd').style.display="";
}	

function limit(){
	document.getElementById('qteL').checked=true;
	document.getElementById('qteU').checked=false;
	document.getElementById('qte').value="";
}
function illimit(){
	document.getElementById('qteL').checked=false;
	document.getElementById('qteU').checked=true;
	document.getElementById('qte').value="";
}

function payant(){
	document.getElementById('prixP').checked=true;
	document.getElementById('prixG').checked=false;
	document.getElementById('prix').value="";
}

function gratuit(){
	document.getElementById('prixP').checked=false;
	document.getElementById('prixG').checked=true;
	document.getElementById('prix').value=0;
}	

	
function submNewProduit(){
	document.getElementById('submitNewProduit').disabled=true;
	document.getElementById('submitNewProduit').value = "Patientez...";
	document.getElementById('submitNewProduit').onclick="";
	document.getElementById('formNewProduit').submit();
}


</script> 
<?php
echo $footer;
?>