<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
//Verif droits
requireDroits("membre");

define('TITRE_PAGE',"Caisse");


$sort="dte ";
$order="DESC";

if (isset($_GET['sort']) && isset($_GET['order'])){

	switch($_GET['order']){
		case "asc" : $order="ASC"; break;	
		case "dsc" : $order="DESC"; break;	
	}
	if(!(empty($order))){
	
		switch($_GET['sort']){
			case "dte" : $sort="dte "; break;
			case "descr" : $sort="descr "; break;
			case "somme" : $sort="somme "; break;
			case "addby" : $sort="addBy "; break;
			default : $order = "DESC"; $sort = "dte ";
		}
	}
}




//nouvelle opération
if(isset($_POST['typeEntree'])){

	if(!($_POST['typeEntree']=="typePaiement" || $_POST['typeEntree']=="typeRembours" || $_POST['typeEntree']=="typeFond")){
		array_push($pageMessages, array('type'=>'err', 'content'=>"Type d'opération non valide."));
	}
	
	if(empty($_POST['ref']) && !is_numeric($_POST['ref'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez sélectionner une référence.'));
	}
	
	if(empty($_POST['descr'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Description</em>.'));
	}
	
	if(empty($_POST['montant'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Montant</em>.'));
	}elseif(!is_numeric($_POST['montant'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Montant</em> n\'est pas valide.'));
	}elseif($_POST['montant']<=0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Montant</em> n\'est pas valide.'));
	}elseif (mb_strlen($_POST['montant'])>7){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Montant</em> ne doit pas dépasser 7 caractères.'));
	}
	
	if(empty($pageMessages)){
		
		if($_POST['typeEntree']=="typePaiement"){
			addCaisse($_POST['descr'], $_POST['montant'], 0, 'local', $_POST['ref']);
		}elseif($_POST['typeEntree']=="typeRembours"){
			addCaisse($_POST['descr'], -1*$_POST['montant'], 0, 'local', $_POST['ref']);
		}elseif($_POST['typeEntree']=="typeFond"){
			
			$bd = db_connect();
			
			$_POST['descr'] = mysqli_real_escape_string($bd, $_POST['descr']);
							
			$addFond = db_exec($bd, "
						INSERT INTO gestion_caisse_fonds(idRef, dte, descr, montant)
						VALUES(".$_POST['ref'].", NOW(),'".$_POST['descr']."','".$_POST['montant']."')");
			
			db_close($bd);
			if($addFond!==false){
				array_push($pageMessages, array('type'=>'ok', 'content'=>'Le fond de caisse de '.$_POST['montant'].'€ a bien été créé.'));
			}
		}		
	}
}//fin nouvelle opération

//Resultat fond de caisse
if(isset($_POST['idFondCaisse'])){


	if(!isset($_POST['resultatFondCaisse'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Montant final</em>.'));
	}elseif(!is_numeric($_POST['resultatFondCaisse'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Montant final</em> n\'est pas valide.'));
	}elseif($_POST['resultatFondCaisse']<0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Montant final</em> n\'est pas valide.'));
	}elseif (mb_strlen($_POST['resultatFondCaisse'])>7){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Montant final</em> ne doit pas dépasser 7 caractères.'));
	}

	if(empty($pageMessages)){
		
		$bd = db_connect();
		
		$fondCaisse = db_ligne($bd, "		
			SELECT idRef, descr, montant
			FROM gestion_caisse_fonds
			WHERE id='".$_POST['idFondCaisse']."'");	
	
		if($fondCaisse!==false && !empty($fondCaisse)){
			
			$supFond = db_exec($bd, "
				DELETE FROM gestion_caisse_fonds
				WHERE id='".$_POST['idFondCaisse']."'
				LIMIT 1");
			
			if($supFond && ($_POST['resultatFondCaisse']-$fondCaisse['montant'])!=0){
				if(empty($fondCaisse['idRef'])){
					$fondCaisse['idRef']="NULL";
				}
				addCaisse($fondCaisse['descr'], ($_POST['resultatFondCaisse']-$fondCaisse['montant']), 0, 'local', $fondCaisse['idRef']);
			}
		}
		db_close($bd);	
	}
}



//Récupération des données
$bd = db_connect();

	//Liste des réferences
	
$ref = db_tableau($bd, "		
				SELECT id, reference
				FROM gestion_caisse_ref
				ORDER BY id ASC");
				
$lstRef='<option value="">Choisissez une référence</option>';

	if($ref !== false && !(empty($ref))){
		for($i=1; $i<count($ref); $i++){
			$lstRef.='<option value="'.$ref[$i]['id']*(-1).'">'.$ref[$i]['reference'].'</option>';
		}
		$lstRef.='<option value="'.$ref[0]['id']*(-1).'">'.$ref[0]['reference'].'</option>';
	}


//liste activités pour references

$tabAct = db_tableau($bd, "
						SELECT id, nom
						FROM activity_activities
						ORDER BY dte DESC");


$lstRef.='<option value="" disabled>Activités</option>';

if($tabAct !== false && !(empty($tabAct))){
	for($i=0; $i<count($tabAct); $i++){
		$lstRef.='<option value="'.$tabAct[$i]['id'].'">'.$tabAct[$i]['nom'].'</option>';
	}
}


//Fonds de caisse

$fondsCaisse = db_tableau($bd, "		
			SELECT id, dte, descr, montant
			FROM gestion_caisse_fonds
			ORDER BY dte ASC");	
	
if($fondsCaisse!==false && !empty($fondsCaisse)){
	$tabFondsCaisse="";
	
	for($i=0; $i<count($fondsCaisse); $i++){
		$dteTme = explode(' ',$fondsCaisse[$i]['dte'],2);
		$dte = explode('-',$dteTme[0],3);
		
		$tabFondsCaisse.='<tr><td style="font-size:0.7em">'.$dte[2].'/'.$dte[1].'/'.$dte[0].'  '.$dteTme[1].'</td>
				<td style="font-size:0.8em">'.$fondsCaisse[$i]['descr'].'</td>
				<td style="font-size:0.9em">'.$fondsCaisse[$i]['montant'].'€</td>
				<td><input type="text" class="euro" id="inputFondCaisse'.$fondsCaisse[$i]['id'].'" name="inputFondCaisse'.$fondsCaisse[$i]['id'].'" onkeyup="editBilanFondCaisse('.$fondsCaisse[$i]['id'].','.$fondsCaisse[$i]['montant'].')" style="width:100px; margin:0" maxlength=7 autocomplete="off"/></td>
				<td id="bilanFondCaisse'.$fondsCaisse[$i]['id'].'"></td>
				<td id="cellFondCaisse'.$fondsCaisse[$i]['id'].'" class="tick" onclick="submFondCaisse('.$fondsCaisse[$i]['id'].')"></td></tr>';
	}
}
	//log période

$infosPeriode = db_ligne($bd, "		
				SELECT id, dteStart, reliquatPrec
				FROM gestion_caisse_periodes
				WHERE dteEnd IS NULL
				ORDER BY id DESC");	

				
if(empty($infosPeriode)){		
	$addCaissePeriode = db_exec($bdd, "INSERT INTO gestion_caisse_periodes(dteStart) VALUES(NOW())");
	$infosPeriode = db_ligne($bd, "		
									SELECT id, dteStart, reliquatPrec
									FROM gestion_caisse_periodes
									WHERE dteEnd IS NULL
									ORDER BY id DESC");
}	

if($infosPeriode!==false && !empty($infosPeriode)){	


	$logPeriode = db_tableau($bd, "		
			SELECT id, dte, descr, somme, recu, addBy
			FROM gestion_caisse_log
			WHERE idPeriode=".$infosPeriode['id']."
			ORDER BY ".$sort.$order);	

	if($logPeriode!==false){	
		$tabLogPeriode = "";
		$sommePeriode = 0;
		$dteTmePeriode = explode(' ',$infosPeriode['dteStart'],2);
		$dtePeriode = explode('-',$dteTmePeriode[0],3);
		
		
		for($i=0; $i<count($logPeriode); $i++){
			$dteTme = explode(' ',$logPeriode[$i]['dte'],2);
			$dte = explode('-',$dteTme[0],3);
			
			$tabLogPeriode.='<tr><td style="font-size:0.7em">'.$dte[2].'/'.$dte[1].'/'.$dte[0].'  '.$dteTme[1].'</td>
					<td style="font-size:0.8em">'.$logPeriode[$i]['descr'].'</td>
					<td style="font-size:0.9em">'.$logPeriode[$i]['somme'].'€'.((!empty($logPeriode[$i]['recu'])&&$logPeriode[$i]['recu']!=0)?'<span style="float:right;font-size:0.7em;width:55px;text-align:right">Reçu n°'.$logPeriode[$i]['recu'].'</span>':'').'</td>
					<td style="font-size:0.8em">'.$logPeriode[$i]['addBy'].'</td></tr>';
					
			$sommePeriode+=$logPeriode[$i]['somme'];
		}
	}
}				
db_close($bd);		
			
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<?php 
if($fondsCaisse!==false && !empty($fondsCaisse)){
	echo '<h3>Fonds de caisse</h3>';
	echo '<table><tbody>';
	echo '<tr><th style="width:100px">Date</th>
	<th>Description</th>
	<th style="width:105px">Fond de caisse</th>
	<th style="width:105px">Montant final</th>
	<th style="width:70px">Bilan</th></tr>';
	echo $tabFondsCaisse;
	echo '</tbody></table>';
	echo '<form method=post action="caisse.php" id="formFondCaisse">
	<input type="hidden" id="idFondCaisse" name="idFondCaisse" />
	<input type="hidden" id="resultatFondCaisse" name="resultatFondCaisse" /></form>';
}
?>

<h3>Nouvelle opération</h3>

<form method=post action="caisse.php" id="formOperation">

<label for="typeEntree">type</label>
	<input id="typeEntreeP" type="radio" name="typeEntree" value="typePaiement" onclick="selectType()">  
	<label id="istypeEntreeP" class="radio" for="typeEntreeP" onclick="selectType()">Entrée d'argent</label>
	<input id="typeEntreeR" type="radio" name="typeEntree" value="typeRembours" onclick="selectType()">
	<label id="istypeEntreeR" class="radio" for="typeEntreeR" onclick="selectType()">Sortie d'argent</label>	
	<input id="typeEntreeF" type="radio" name="typeEntree" value="typeFond" onclick="selectType()">
	<label id="istypeEntreeF" class="radio" for="typeEntreeF" onclick="selectType()">Créer un fond de caisse</label>	
	
<div id="divOperation" style="display:none">

<label for="ref">référence</label>
<select id="ref" name="ref" onchange="changeRef()">
<?php echo $lstRef?>
</select>

<label for="descr">description - pour quel événement, qui, quoi, etc.</label>
<input type="text" id="descr" name="descr" style="width:58%" autocomplete="off"/>
	
<label id="labelMontant" for="montant">montant</label>
<input type="text" class="euro" id="montant" name="montant" style="width:140px" maxlength=7 autocomplete="off"/>
	
<input type="button" onclick="submOperation()" id="submitOperation" value="valider" />
</div>
</form>

<h3>Récapitulatif</h3>
<div class="blocText">
Période débutée le <?php echo $dtePeriode[2].'/'.$dtePeriode[1].'/'.$dtePeriode[0].' à '.$dteTmePeriode[1]; ?>
</div>

<?php
if($logPeriode !== false && !(empty($logPeriode))){

	echo '<table><tbody>';
	echo '<tr><th style="width:100px">Date<img class="sortA" onclick="sort(\'dte\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'dte\',\'dsc\')" src="../template/images/sortDesc.png"></th>
	<th>Description<img class="sortA" onclick="sort(\'descr\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'descr\',\'dsc\')" src="../template/images/sortDesc.png"></th>
	<th style="width:90px">Somme<img class="sortA" onclick="sort(\'somme\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'somme\',\'dsc\')" src="../template/images/sortDesc.png"></th>
	<th style="width:130px">Effectué par<img class="sortA" onclick="sort(\'addby\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'addby\',\'dsc\')" src="../template/images/sortDesc.png"></th></tr>';
	echo $tabLogPeriode;
	echo '</tbody></table>';
}
?>


<script type="text/javascript">

function editBilanFondCaisse(id, fond){
	if(document.getElementById('inputFondCaisse'+id).value!="" && !isNaN(document.getElementById('inputFondCaisse'+id).value) && Number(document.getElementById('inputFondCaisse'+id).value)>=0){
		document.getElementById('bilanFondCaisse'+id).innerHTML=(Number(document.getElementById('inputFondCaisse'+id).value-fond).toFixed(2))+"€";
	}else{
		document.getElementById('bilanFondCaisse'+id).innerHTML="";
	}
}

function selectType(){
	if(document.getElementById('typeEntreeP').checked==true || document.getElementById('typeEntreeR').checked==true || document.getElementById('typeEntreeF').checked==true){
		document.getElementById('divOperation').style.display="";
		
		if(document.getElementById('typeEntreeP').checked==true){
			document.getElementById('labelMontant').innerHTML="montant ajouté à la caisse";
		
		}else if(document.getElementById('typeEntreeR').checked==true){
			document.getElementById('labelMontant').innerHTML="montant retiré de la caisse";
		
		}else if(document.getElementById('typeEntreeF').checked==true){
			document.getElementById('labelMontant').innerHTML="montant du fond de caisse";
		}

	}else{
		document.getElementById('divOperation').style.display="none";
	}
}

function changeRef(){
	var lstRef = document.getElementById('ref');
	if(lstRef.options[lstRef.selectedIndex].value != -1 && lstRef.options[lstRef.selectedIndex].value != ""){
		document.getElementById('descr').value = lstRef.options[lstRef.selectedIndex].innerHTML + " : ";
	}else{
		document.getElementById('descr').value = "";
	}
}

function sort(colonne, order){
	window.location.href="caisse.php?sort="+colonne+"&order="+order;
}


function submFondCaisse(id){
	if(!isNaN(document.getElementById('inputFondCaisse'+id).value) && Number(document.getElementById('inputFondCaisse'+id).value)>=0){
		document.getElementById('cellFondCaisse'+id).onclick="";
		document.getElementById('idFondCaisse').value = id;
		document.getElementById('resultatFondCaisse').value = document.getElementById('inputFondCaisse'+id).value;
		document.getElementById('formFondCaisse').submit();
	}else{
		alert("Montant invalide.");
	}
}	
	
function submOperation(){

	if(!isNaN(document.getElementById('montant').value) && Number(document.getElementById('montant').value)>0){
		document.getElementById('submitOperation').disabled=true;
		document.getElementById('submitOperation').value = "Patientez...";
		document.getElementById('submitOperation').onclick="";
		document.getElementById('formOperation').submit();
	}else{
		alert("Montant invalide.");
	}
}
</script> 
<?php
echo $footer;
?>