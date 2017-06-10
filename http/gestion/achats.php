<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("membre");

define('TITRE_PAGE',"Achats");


//achat produit
if(isset($_POST['idAchat'])){

	if(!($_POST['typeMembreAchat'] == "Adh" || $_POST['typeMembreAchat'] == "ESN" || $_POST['typeMembreAchat'] == "Ext" )){
		
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le type de membre n\'est pas valide.'));
	}

	if($_POST['typeMembreAchat'] != "Ext" && $_POST['nomMembreAchat'] == ""){
		
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez sélectionner un membre.'));
	}
	
	
	if($_POST['prixAchat'] != 0){
	
		if(empty($_POST['prixAchat'])&&!is_numeric($_POST['prixAchat'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Somme payée</em>.'));
		}elseif(!is_numeric($_POST['prixAchat'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Somme payée</em> n\'est pas valide.'));
		}elseif($_POST['prixAchat']<0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Somme payée</em> n\'est pas valide.'));
		}elseif (mb_strlen($_POST['prixAchat'])>7){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Somme payée</em> ne doit pas dépasser 7 caractères.'));
		}
		
		
		if(empty($_POST['recu'])&&!is_numeric($_POST['recu'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Reçu</em>.'));
		}elseif(!is_numeric($_POST['recu'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Reçu</em> n\'est pas valide.'));
		}elseif($_POST['recu']<0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Reçu</em> n\'est pas valide.'));
		}elseif (mb_strlen($_POST['recu'])>3){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Reçu</em> ne doit pas dépasser 3 caractères.'));
		}
		
	}
	
	//verif quantité restante
	$bd = db_connect();
	$nbRestant = db_ligne($bd, "
				SELECT qte, vendu
				FROM gestion_achats_produits
				WHERE id='".$_POST['idAchat']."'");
	db_close($bd);
	
	
	if($nbRestant === false || ($nbRestant['qte'] != 0 && intval($nbRestant['qte'] - $nbRestant['vendu'])<=0)){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Ce produit n\'est plus disponible.'));
	}
	
	
	
	if(empty($pageMessages)){
		
		$bd = db_connect();
			
		$_POST['nomAchat'] = mysqli_real_escape_string($bd, $_POST['nomAchat']);
		$_POST['nomMembreAchat'] = mysqli_real_escape_string($bd, $_POST['nomMembreAchat']);

		
		$achat = db_exec($bd, "
					UPDATE gestion_achats_produits
					SET vendu=vendu+1
					WHERE id='".$_POST['idAchat']."'");
					
		$addAcheteur = db_exec($bd, "
					INSERT INTO gestion_achats_acheteurs(idAchat, type, nom, dteAchat, soldBy)
					VALUES('".$_POST['idAchat']."','".$_POST['typeMembreAchat']."','".$_POST['nomMembreAchat']."',NOW(), '".PRENOM." ".NOM."')");
		
		
		db_close($bd);

		if($achat!==false && $addAcheteur!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'L\'achat a bien été validé.'));
			
			unset($_GET['prod']);
			
			if($_POST['prixAchat'] != 0){
				$descr = "Vente de ".$_POST['nomAchat']. " à ". ((empty($_POST['nomMembreAchat']))?"un extérieur":$_POST['nomMembreAchat']);
				addCaisse($descr, $_POST['prixAchat'], $_POST['recu'], 'local', -4);
			}
		}		
	}
}//fin achat


//Récupération des données
$bd = db_connect();

$produits = db_tableau($bd, "		
			SELECT id, nom, qte, vendu, prix
			FROM gestion_achats_produits
			ORDER BY nom ASC");	
			
$acheteurs = db_tableau($bd, "		
			SELECT id, idAchat, type, nom, dteAchat, soldBy
			FROM gestion_achats_acheteurs
			ORDER BY idAchat ASC, FIELD(type, 'ESN', 'Adh', 'Ext') , nom ASC");				
			
//Récupération liste membres


$tabAdh = db_tableau($bd, "
					SELECT adh.id, adh.idesn, adh.prenom, adh.nom, adh.pays
					FROM membres_adherents AS adh
					ORDER BY adh.prenom ASC, adh.nom ASC");
					
$tabESN = db_tableau($bd, "
					SELECT ben.id, ben.prenom, ben.nom
					FROM membres_benevoles AS ben
					ORDER BY ben.prenom ASC, ben.nom ASC");
					
db_close($bd);

//Mise en forme produits
$lstProduits="";
$lstSelectProduits='<option value="">Choisissez un produit</option>';

if($produits!==false && !empty($produits)){
		
	for($i=0; $i<count($produits); $i++){
		
		$lstProduits.='<tr id="lineProd'.$produits[$i]['id'].'">
			<td id="cellNomProd'.$produits[$i]['id'].'">'.$produits[$i]['nom'].'</td>
			<td id="cellRestProd'.$produits[$i]['id'].'">'.(($produits[$i]['qte']==0)? "Illimité" : intval($produits[$i]['qte']-$produits[$i]['vendu'])).'</td>
			<td id="cellPrixProd'.$produits[$i]['id'].'">'.(($produits[$i]['prix']==0)? "Gratuit" : $produits[$i]['prix'].'€').'</td>'.
			(($produits[$i]['qte']!=0 && intval($produits[$i]['qte']-$produits[$i]['vendu'])<=0)?'<td></td>':'
				<td id="cellAchatProd'.$produits[$i]['id'].'" class="tick" onclick="selectProd('.$produits[$i]['id'].','.$produits[$i]['prix'].',\''.str_replace("'","\'", $produits[$i]['nom']).'\')"></td>').
			'</tr>';
			
		$lstSelectProduits .= '<option value="'.$produits[$i]['id'].'">'.str_replace("'","\'", $produits[$i]['nom']).'</option>';
	}
}

//Vérif préselection produit
$preSelectProduit = "";

if(isset($_GET['prod'])){

	for($i=0; $i<count($produits); $i++){
		
		if($_GET['prod'] == $produits[$i]['id']){
		
			if($produits[$i]['qte']==0 || intval($produits[$i]['qte']-$produits[$i]['vendu'])>0){
				$preSelectProduit = "selectProd(".$produits[$i]['id'].",".$produits[$i]['prix'].",'".str_replace("'","\'", $produits[$i]['nom'])."')";
			}
		}
	}
}

//mise en forme membres
$lstAdh="";
$lstAdhJS="";
$lstESN="";
$lstESNJS="";

if($tabAdh!==false&&$tabESN!==false){

	for($i=0; $i<count($tabAdh); $i++){
		
		$lstAdh.='<tr id="lineAdh'.$i.'" style="display:none"><td class="gras">'.$tabAdh[$i]['prenom'].' '.$tabAdh[$i]['nom'].'</td>
					<td>'.$tabAdh[$i]['pays'].'</td><td>'.$tabAdh[$i]['idesn'].'</td>
					<td id="cellAdh'.$i.'" class="tick" onclick="selectMembre(\'Adh\','.$i.',\''.str_replace("'","\'", $tabAdh[$i]['prenom']).' '.str_replace("'","\'", $tabAdh[$i]['nom']).'\')"></td></tr>';
					
		$lstAdhJS.= 'lstAdhJS['.$i.']=new Array("'.strtolower($tabAdh[$i][2]).'","'.strtolower($tabAdh[$i][3]).'","'.strtolower($tabAdh[$i][1]).'");';
		
	}
	for($i=0; $i<count($tabESN); $i++){
		
		$lstESN.='<tr id="lineESN'.$i.'" style="display:none"><td class="gras">'.$tabESN[$i]['prenom'].' '.$tabESN[$i]['nom'].'</td>
					<td id="cellESN'.$i.'" class="tick" onclick="selectMembre(\'ESN\','.$i.',\''.str_replace("'","\'", $tabESN[$i]['prenom']).' '.str_replace("'","\'", $tabESN[$i]['nom']).'\')"></td></tr>';
					
		$lstESNJS.= 'lstESNJS['.$i.']=new Array("'.strtolower($tabESN[$i][1]).'","'.strtolower($tabESN[$i][2]).'");';
		
	}
}


//mise en forme acheteurs

$tempIdAchat = "";
$tempAcheteur ="";
$tbody = "";
$firstTable = true;
$lstAcheteursJS = "";

if($acheteurs!==false && !empty($acheteurs)){
		
	for($i=0; $i<count($acheteurs); $i++){
	
		
		if($tempIdAchat != $acheteurs[$i]['idAchat']){
					
			
			//fermeture tbody
			if(!empty($tbody)){
				$tbody .= '</tbody>';
			}
			
			//nouveau tableau
			
			$tbody .= '<tbody id="tbodyAcheteurs-'.$acheteurs[$i]['idAchat'].'" style="display:none">';
	
			$tempIdAchat = $acheteurs[$i]['idAchat'];
			$firstTable = true;
		
		}
		
		//Remplissage
	
		$dtetmeAchat = explode(' ',$acheteurs[$i]['dteAchat'],2);
		$dteAchat = explode('-',$dtetmeAchat[0],3);
		$dateAchat = $dteAchat[2].'/'.$dteAchat[1].'/'.$dteAchat[0].' '.$dtetmeAchat[1];
		
		
		if($acheteurs[$i]['type'] == 'ESN'){
			$nom = $acheteurs[$i]['nom']." (ESN)";
		
		}elseif($acheteurs[$i]['type'] == 'Adh'){
			$nom = $acheteurs[$i]['nom']." (Adhérent)";
		
		}elseif($acheteurs[$i]['type'] == 'Ext'){
			$nom = "Personne extérieure";
		
		}
		
		$tbody .= '<tr><td>'.$nom.'</td><td>'.$dateAchat.'</td><td>'.$acheteurs[$i]['soldBy'].'</td></tr>';
		
		
		//Construction tableau JS
		$lstAcheteursJS.= 'lstAcheteursJS['.$i.']=new Array("'.$acheteurs[$i]['idAchat'].'","'.$acheteurs[$i]['type'].'","'.$acheteurs[$i]['nom'].'");';
		
	}
	
	//fermeture tbody
	if(!empty($tbody)){
		$tbody .= '</tbody>';
	}
}
			
			
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<h3>Enregistrer un achat</h3>
<?php
if(!empty($lstProduits)){

	echo '<table id="tableProd"><tbody>';
	echo '<tr><th>Produit</th><th style="width:70px">Restants</th><th style="width:70px">Prix</th><th id="thAchat" style="width:150px">Enregistrer un achat</th></tr>';
	echo $lstProduits;
	echo '</tbody></table>';
}else{
	echo "<br/>Pas de données.<br/>";
}

?>
<br />
<table id="champsFilter" class="invisible" style="display:none"><tbody><tr><td>
<label for="type" >type de membre</label>
	<input id="typeA" type="radio" name="type" value="Adh" onclick="selectType()" checked>  
	<label class="radio" for="typeA" onclick="selectType()">Adhérent</label>  
	<input id="typeB" type="radio" name="type" value="Ben" onclick="selectType()">  
	<label class="radio" for="typeB" onclick="selectType()">Bénévole</label> 
	<input id="typeE" type="radio" name="type" value="Ext" onclick="selectType()">  
	<label class="radio" for="typeE" onclick="selectType()">Extérieur</label> 
</td><td>
<label for="nom" id="labelNom">prénom ou nom</label>
<input type="text" id="nom" name="nom" onkeyup="filtering()" value="" autocomplete="off"/>
</td><td>
<label for="carteesn" id="labelCarteesn">numero carte esn</label>
<input type="text" id="carteesn" name="carteesn" onkeyup="filtering()" style="width:120px" value="" autocomplete="off"/>
</td></tr></tbody></table>

<table id="listeESN" style="display:none"><tbody><th>Nom</th><th id="lastTHESN" style="width:150px" >Valider</th>
<?php echo $lstESN; ?>
</tbody></table>

<table id="listeAdh" style="display:none"><tbody><th style="width:255px">Nom</th><th>Pays</th><th style="width:100px">Carte ESN</th><th id="lastTHAdh" style="width:150px" >Valider</th>
<?php echo $lstAdh; ?>
</tbody></table>


<form method=post action="achats.php" id="formAchatProd" >
<input type="hidden" id="idAchat" name="idAchat" value=""/>
<input type="hidden" id="nomAchat" name="nomAchat" value=""/>
<input type="hidden" id="prixAchat" name="prixAchat" value=""/>
<input type="hidden" id="typeMembreAchat" name="typeMembreAchat" value=""/>
<input type="hidden" id="nomMembreAchat" name="nomMembreAchat" value=""/>

<table id="tabPaiement" class="invisible" style="margin-top:5px; display:none"><tbody><tr>
<td>
<label for="paid">somme payée</label>
<input type="text" id="paid" name="paid" class="euro" style="width:70px" maxlength=7 autocomplete="off" disabled> 
</td>
<td>
<label for="recu">numero reçu</label>
<input type="text" id="recu" name="recu" style="width:70px" maxlength=3 autocomplete="off"> 
</td>
<div id="divVerifDejaAcheteur" class="gras"></div>
<td><input type="button" onclick="submAchatProdPayant()" id="submitAchatPayant" value="valider" /></td>
</tr></tbody></table></form>

<h3>Liste des acheteurs</h3>

<label for="selectProduit">produit</label>
<select id="selectProduit" onchange="changeProduit()">
<?php echo $lstSelectProduits;?>
</select>
<br/>


<table id="tableAcheteurs" style="display:none">
<thead>
<th>Acheté par</th><th style="width:140px">Le</th><th style="width:170px">Effectué par</th>
</thead>
<?php echo $tbody;?>
</table>


<div id="divPasAcheteurs" style="display:none">Aucun exemplaire vendu.</div>


<script type="text/javascript">

var lstAdhJS=new Array();
<?php echo $lstAdhJS; ?>
var lstESNJS=new Array();
<?php echo $lstESNJS; ?>

var lstAcheteursJS=new Array();
<?php echo $lstAcheteursJS; ?>



<?php echo $preSelectProduit; ?>

function selectProd(id,prix,nom){

	var tableProd = document.getElementById("tableProd");

	if(document.getElementById('lineProd'+id).className == "selected"){
		document.getElementById('lineProd'+id).className = "";
		document.getElementById('cellAchatProd'+id).className = "tick";
		document.getElementById('thAchat').innerHTML="Enregistrer un achat";


		for(var i=1; i < tableProd.rows.length; i++){
			tableProd.rows[i].style.display = "";
		}
		
		document.getElementById('idAchat').value="";
		document.getElementById('paid').value="";
		document.getElementById('prixAchat').value="";
		document.getElementById('nomAchat').value="";
		document.getElementById('recu').value="";
		document.getElementById('typeMembreAchat').value="";
		document.getElementById('nomMembreAchat').value="";
		
		document.getElementById('champsFilter').style.display="none";
		document.getElementById('nom').value="";
		
		document.getElementById('listeAdh').style.display="none";
		document.getElementById('listeESN').style.display="none";
		document.getElementById('lastTHAdh').innerHTML="Valider";
		document.getElementById('lastTHESN').innerHTML="Valider";

		for(var i=0; i<lstAdhJS.length; i++){
			document.getElementById('lineAdh'+i).className = "";
			document.getElementById('cellAdh'+i).className = "tick";
		}
		for(var i=0; i<lstESNJS.length; i++){
			document.getElementById('lineESN'+i).className = "";
			document.getElementById('cellESN'+i).className = "tick";
		}
		
		document.getElementById('tabPaiement').style.display="none";

	
	}else{
		document.getElementById('lineProd'+id).className = "selected";
		document.getElementById('cellAchatProd'+id).className = "remove";
		document.getElementById('thAchat').innerHTML="Annuler";


		for(var i=1; i < tableProd.rows.length; i++){
		
			if(tableProd.rows[i].id != "lineProd"+id){
				tableProd.rows[i].style.display = "none";
			}
		}
		
		document.getElementById('idAchat').value=id;
		document.getElementById('paid').value=prix;
		document.getElementById('prixAchat').value=prix;
		document.getElementById('nomAchat').value=nom;
		
		document.getElementById('champsFilter').style.display="";
		document.getElementById('nom').focus();

	}

}


function selectType(){

	document.getElementById('listeESN').style.display="none";
	document.getElementById('listeAdh').style.display="none";
	document.getElementById('carteesn').value="";
	document.getElementById('nom').value="";
	document.getElementById('nom').focus();
	
	if(document.getElementById('typeA').checked==true){
		
		document.getElementById('labelNom').style.display="";
		document.getElementById('nom').style.display="";
		
		document.getElementById('labelCarteesn').style.display="";
		document.getElementById('carteesn').style.display="";
		
		document.getElementById('tabPaiement').style.display="none";
		
		for(var i=0; i<lstAdhJS.length; i++){
			document.getElementById('lineAdh'+i).style.display = "none";
		}

		
	}else if(document.getElementById('typeB').checked==true){
		
		document.getElementById('labelNom').style.display="";
		document.getElementById('nom').style.display="";
		
		document.getElementById('labelCarteesn').style.display="none";
		document.getElementById('carteesn').style.display="none";
		
		document.getElementById('tabPaiement').style.display="none";
		
		for(var i=0; i<lstESNJS.length; i++){
			document.getElementById('lineESN'+i).style.display = "none";
		}
		
	}else if(document.getElementById('typeE').checked==true){
	
		document.getElementById('labelNom').style.display="none";
		document.getElementById('nom').style.display="none";
	
		document.getElementById('labelCarteesn').style.display="none";
		document.getElementById('carteesn').style.display="none";
	
		document.getElementById('typeMembreAchat').value="Ext";
		document.getElementById('nomMembreAchat').value="";
	
	
	
		if(document.getElementById('prixAchat').value==0){
			submAchatProdGratuit('Ext', 0, "");
	
		}else{
			document.getElementById('recu').value="";
			document.getElementById('tabPaiement').style.display="";
			document.getElementById('recu').focus();
		}
	
	}
}

function selectMembre(type, i, nomMembre){

	if(document.getElementById('prixAchat').value==0){
	
		submAchatProdGratuit(type, i, nomMembre);
	
	}else{
	
	
		if(document.getElementById('line'+type+i).className == "selected"){
			document.getElementById('line'+type+i).className = "";
			document.getElementById('cell'+type+i).className = "tick";
			document.getElementById('lastTHAdh').innerHTML="Valider";
			document.getElementById('lastTHESN').innerHTML="Valider";
			
			document.getElementById('typeMembreAchat').value="";
			document.getElementById('nomMembreAchat').value="";
					
			document.getElementById('champsFilter').style.display="";
			
			document.getElementById('recu').value="";
			document.getElementById('tabPaiement').style.display="none";
			
			filtering();
	
		}else{
	
			document.getElementById('line'+type+i).className = "selected";
			document.getElementById('cell'+type+i).className = "remove";
			document.getElementById('lastTHAdh').innerHTML="Annuler";
			document.getElementById('lastTHESN').innerHTML="Annuler";
			
			document.getElementById('typeMembreAchat').value=type;
			document.getElementById('nomMembreAchat').value=nomMembre;

			
			document.getElementById('champsFilter').style.display="none";

			if(type=='Adh'){
				document.getElementById('listeAdh').style.display="";
				for(var a=0; a<lstAdhJS.length; a++){
					if(a!=i){
						document.getElementById('line'+type+a).style.display = "none";
					}else{
						document.getElementById('line'+type+a).style.display = "";				
					}
				}
			}else if(type=='ESN'){
				document.getElementById('listeESN').style.display="";
				for(var a=0; a<lstESNJS.length; a++){
					if(a!=i){
						document.getElementById('line'+type+a).style.display = "none";
					}else{
						document.getElementById('line'+type+a).style.display = "";
					}
				}				
			}
			
			
			if(verifDejaAcheteur()){
				document.getElementById('divVerifDejaAcheteur').innerHTML = '<br/><div style="vertical-align:bottom"><img class="info" style="margin-right:5px" src="../template/images/information.png"/>Cette personne a déjà acheté ce produit.<div>';

			}else{
				document.getElementById('divVerifDejaAcheteur').innerHTML = "";
			}
			
			document.getElementById('recu').value="";
			document.getElementById('tabPaiement').style.display="";
			document.getElementById('recu').focus();	
		}
	}
}

function filtering(){

	if(document.getElementById('typeA').checked==true){
	
		if(document.getElementById('carteesn').value.length>2 || document.getElementById('nom').value.length>1){
			document.getElementById('listeESN').style.display="none";
			document.getElementById('listeAdh').style.display="";
		
			for(var i=0; i<lstAdhJS.length; i++){
				var nom = lstAdhJS[i][0]+" "+lstAdhJS[i][1];
				if(lstAdhJS[i][2].indexOf(document.getElementById('carteesn').value.toLowerCase())==-1 || nom.indexOf(document.getElementById('nom').value.toLowerCase())==-1){
					document.getElementById('lineAdh'+i).style.display = "none";

				}else{
					document.getElementById('lineAdh'+i).style.display = "";
				}
			}
		
		}else{
		
			for(var i=0; i<lstAdhJS.length; i++){
				document.getElementById('lineAdh'+i).style.display = "none";
			}
			
			document.getElementById('listeAdh').style.display="none";
			document.getElementById('listeESN').style.display="none";

		}
	
	}else if(document.getElementById('typeB').checked==true){
		
		if(document.getElementById('nom').value.length>1){
			document.getElementById('listeESN').style.display="";
			document.getElementById('listeAdh').style.display="none";
		
			for(var i=0; i<lstESNJS.length; i++){
				var nom = lstESNJS[i][0]+" "+lstESNJS[i][1];
				if(nom.indexOf(document.getElementById('nom').value.toLowerCase())==-1){
					document.getElementById('lineESN'+i).style.display = "none";

				}else{
					document.getElementById('lineESN'+i).style.display = "";
				}
			}
		
		}else{
		
			for(var i=0; i<lstESNJS.length; i++){
				document.getElementById('lineESN'+i).style.display = "none";
			}
			document.getElementById('listeAdh').style.display="none";
			document.getElementById('listeESN').style.display="none";
		}
			
	}
}


function submAchatProdGratuit(type, i, nomMembre){
	
	document.getElementById('typeMembreAchat').value=type;
	document.getElementById('nomMembreAchat').value=nomMembre;
		
	var valid = false;

		if(verifDejaAcheteur()){
			if(confirm("Cette personne a déjà acheté ce produit. Continuer ?")){
				valid = true;
			
			}
		}else{
			valid = true;
		}

		
	if(valid){
		if(type != "Ext"){
			document.getElementById('cell'+type+i).onclick="";
		}
		
		document.getElementById('formAchatProd').action="achats.php?prod="+document.getElementById('idAchat').value;
		document.getElementById('formAchatProd').submit();
	}
}

	
function submAchatProdPayant(){

	document.getElementById('submitAchatPayant').disabled=true;
	document.getElementById('submitAchatPayant').value = "Patientez...";
	document.getElementById('submitAchatPayant').onclick="";
	document.getElementById('formAchatProd').action="achats.php?prod="+document.getElementById('idAchat').value;
	document.getElementById('formAchatProd').submit();
}


function changeProduit(){

	var selectProduit = document.getElementById('selectProduit');
	var IdProduit = selectProduit.options[selectProduit.selectedIndex].value;
	
	if(IdProduit == ""){
		document.getElementById('tableAcheteurs').style.display = "none";
		document.getElementById('divPasAcheteurs').style.display = "none";
	}else{
		
		if(document.getElementById('tbodyAcheteurs-'+IdProduit) == null){
		
			document.getElementById('tableAcheteurs').style.display = "none";
			document.getElementById('divPasAcheteurs').style.display = "";
		
		}else{
		
		
			var tableAcheteurs = document.getElementById("tableAcheteurs");


			for(var i=0; i < tableAcheteurs.tBodies.length; i++){
				tableAcheteurs.tBodies[i].style.display = "none";
			}

		
			document.getElementById('tbodyAcheteurs-'+IdProduit).style.display = "";
		
			document.getElementById('tableAcheteurs').style.display = "";
			document.getElementById('divPasAcheteurs').style.display = "none";
		
		}
	}
}

function verifDejaAcheteur(){
	
	var idProduit = document.getElementById('idAchat').value;
	var type = document.getElementById('typeMembreAchat').value;	
	var nom = document.getElementById('nomMembreAchat').value;
	
	var verif = false;

	for(var i=0; i < lstAcheteursJS.length; i++){
			 
		if(type != 'Ext' && lstAcheteursJS[i][0] == idProduit && lstAcheteursJS[i][1] == type && lstAcheteursJS[i][2] == nom){
			var verif = true;
			break;
		}
	}
	return verif;
}


</script> 
<?php
echo $footer;
?>