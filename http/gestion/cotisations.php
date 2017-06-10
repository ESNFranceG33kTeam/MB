<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("membre");

define('TITRE_PAGE',"Cotisations");

//Récupération types cotisation

$bd = db_connect();

$typesCotis = db_tableau($bd, "		
			SELECT id, descr, prix, type
			FROM gestion_cotisations_types
			WHERE type='ESN_Normal' OR type='ESN_Special'
			ORDER BY type ASC, prix DESC");	
			
db_close($bd);
	
$selectCotis="";
if($typesCotis!==false && !empty($typesCotis)){

	for($i=0; $i<count($typesCotis); $i++){	
		$selectCotis.='<option value="'.$typesCotis[$i]['id'].'">'.$typesCotis[$i]['descr'].' - '.$typesCotis[$i]['prix'].'€</option>';
	}
}


//nouvelle cotisation
if(isset($_POST['idNom'])){

	
	if(empty($_POST['idNom'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez sélectionner une personne.'));
	}
		if(empty($_POST['cotisation'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez sélectionner un type de cotisation.'));
	}
	
	if(!empty($_POST['dteCotis'])){
		$dte = date_parse($_POST['dteCotis']);
		if (!checkdate($dte['month'], $dte['day'], $dte['year'])) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Date de la cotisation</em> n\'est pas valide.'));
		}else{
			$dateCotis = $dte['year'].'-'.$dte['month'].'-'.$dte['day'];

			if(date_create($dateCotis)>date_create(date('Y-m-d'))){
				array_push($pageMessages, array('type'=>'err', 'content'=>'La date de la cotisation ne peut pas être une date future.'));
				
			}
		}	
	}
	
	if(empty($pageMessages)){
	
		for($i=0; $i<count($typesCotis); $i++){	
			if($typesCotis[$i]['id']==$_POST['cotisation']){
				$prixCotis = $typesCotis[$i]['prix'];
				$nomCotis = $typesCotis[$i]['descr'];
			}
		}
		echo $prixCotis;
		
		
		$bd = db_connect();
		$nom = db_ligne($bd, "		
			SELECT prenom, nom
			FROM membres_benevoles
			WHERE id='".$_POST['idNom']."'");	
		db_close($bd);
		
		if($nom!==false && !empty($nom)){
			$bd = db_connect();
			
			
			$nomCotis = mysqli_real_escape_string($bd, $nomCotis);
			
			
			$addCotis = db_exec($bd, "
				INSERT INTO gestion_cotisations_benevoles(idBen, dteCotis, typeCotis)
				VALUES('".$_POST['idNom']."','".$dateCotis."','".$nomCotis." - ".$prixCotis."€')
				ON DUPLICATE KEY UPDATE dteCotis='".$dateCotis."', typeCotis='".$nomCotis." - ".$prixCotis."€'");
				

			
			db_close($bd);
			
			if($addCotis!==false){
				array_push($pageMessages, array('type'=>'ok', 'content'=>'La cotisation a bien été validée.'));
				if($prixCotis!=0){
					addCaisse("Cotisation de ".$nom['prenom']." ".$nom['nom']." - ".$nomCotis, $prixCotis, 0, 'local', -2);
				}
			}
		}
	}
}//fin nouvelle opération


//Récupération des données
$bd = db_connect();

$cotis = db_tableau($bd, "		
			SELECT ben.id, ben.prenom, ben.nom, cotis.dteCotis, cotis.typeCotis
				FROM membres_benevoles AS ben
				LEFT JOIN gestion_cotisations_benevoles AS cotis ON ben.id = cotis.idBen
				ORDER BY prenom ASC, nom ASC");	
			
db_close($bd);	
	
	
if($cotis!==false && !empty($cotis)){

	$tabCotisOk="";
	$tabCotisNoOk="";
	$lstNonCotis='<option value="">Choisir quelqu\'un</option>';

	for($i=0; $i<count($cotis); $i++){


		if(!empty($cotis[$i]['dteCotis'])){
		
			$finCotis = date_add(date_create($cotis[$i]['dteCotis']), date_interval_create_from_date_string('1 year'));
			
			if($finCotis<date_create(date('m/d/Y'))){
				$expDays = date_diff($finCotis, date_create(date('m/d/Y')));
				$tabCotisNoOk.='<tr><td>'.$cotis[$i]['prenom'].' '.$cotis[$i]['nom'].'</td><td style="font-weight:bold; color:orangered">Expirée depuis le '.date_format($finCotis, 'd/m/Y').' ('.$expDays->format('%a').' jours)</td></tr>';
				$lstNonCotis.='<option value="'.$cotis[$i]['id'].'">'.$cotis[$i]['prenom'].' '.$cotis[$i]['nom'].'</option>';
			
			}else{
				$tabCotisOk.='<tr><td>'.$cotis[$i]['prenom'].' '.$cotis[$i]['nom'].'</td><td>A cotisé le '.date_format(date_create($cotis[$i]['dteCotis']), 'd/m/Y').' - '.$cotis[$i]['typeCotis'].'</td></tr>';
			}
		}else{
			$tabCotisNoOk.='<tr><td>'.$cotis[$i]['prenom'].' '.$cotis[$i]['nom'].'</td><td style="font-weight:bold; color:orangered">N\'a encore jamais cotisé</td></tr>';
			$lstNonCotis.='<option value="'.$cotis[$i]['id'].'">'.$cotis[$i]['prenom'].' '.$cotis[$i]['nom'].'</option>';
		}
		
	}
}
			
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<?php
if(!empty($tabCotisNoOk)){
?>
<h3>Payer une cotisation</h3>
<form method=post action="cotisations.php" id="formCotis">
<label for="idNom">nom</label>
<select id="idNom" name="idNom" onchange="changeNom()">
<?php echo $lstNonCotis;?>
</select>
<div id="blocCotis" style="display:none">
<label for="dteCotis" >date de la cotisation</label>
<input type="date" id="dteCotis" name="dteCotis" maxlength=10 value="<?php echo date('Y-m-d'); ?>"/>
<label for="cotisation">type de cotisation</label>
<select id="cotisation" name="cotisation">
<?php echo $selectCotis?>
</select>
<input type="button" onclick="submCotis()" id="submitCotis" value="valider" />
</div>
</form>
<?php } ?>

<h3>Etat des cotisations</h3>
<?php
if(!(empty($tabCotisNoOk) && empty($tabCotisOk))){

	echo '<table><tbody>';
	echo '<tr><th>Nom</th><th style="width:450px">Etat</th>';
	echo $tabCotisNoOk;
	echo $tabCotisOk;
	echo '</tbody></table>';
}else{
	echo "<br/>Pas de données.<br/>";
}
?>

<script type="text/javascript">

function changeNom(){

	var lstNoms = document.getElementById('idNom');
	var nom = lstNoms.options[lstNoms.selectedIndex].value;
	if(nom != ""){
		document.getElementById('blocCotis').style.display = "";
	}else{
		document.getElementById('blocCotis').style.display = "none";
	}
}

	
function submCotis(){

	document.getElementById('submitCotis').disabled=true;
	document.getElementById('submitCotis').value = "Patientez...";
	document.getElementById('submitCotis').onclick="";
	document.getElementById('formCotis').submit();

}
</script> 
<?php
echo $footer;
?>