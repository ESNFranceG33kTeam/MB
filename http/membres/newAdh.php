<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

$defaultFinCotis = date("Y-m-d", strtotime("+1 year"));


$valChamps = array(((isset($_GET['numCarte']))?$_GET['numCarte']:""),"","","H","","jj-mm-aaaa","","+33","","","","","","","","aaaa-mm","",$defaultFinCotis,"","");
define('TITRE_PAGE',"Nouvel adhérent");


//Récupération types cotisation et consentements

$bd = db_connect();

$typesCotis = db_tableau($bd, "		
			SELECT id, descr, prix, type
			FROM gestion_cotisations_types
			WHERE type='Adh_Normal' OR type='Adh_Special'
			ORDER BY type ASC, prix DESC");	
			
$consentements = db_tableau($bd, "		
			SELECT id, obligatoire, defaut, texte, texteCase
			FROM gestion_consentements
			WHERE cible=1
			ORDER BY id ASC");
			
db_close($bd);
	
$selectCotis="";
$selectCotisJS="";
if($typesCotis!==false && !empty($typesCotis)){

	for($i=0; $i<count($typesCotis); $i++){	
		$selectCotis.='<option value="'.$typesCotis[$i]['id'].'">'.$typesCotis[$i]['descr'].' - '.$typesCotis[$i]['prix'].'€</option>';
		$selectCotisJS.= 'selectCotisJS['.$i.']=new Array("'.$typesCotis[$i]['prix'].'");';
	}
}

$listeConsentements="";
$tabConsentementsObligJS="";
if($consentements!==false && !empty($consentements)){
	
	
	$listeConsentements='<table class="invisible">';

	for($i=0; $i<count($consentements); $i++){	
		$listeConsentements.='<tr><td><input type="checkbox" id="caseConsent-'.$consentements[$i]['id'].'" name="caseConsent-'.$consentements[$i]['id'].'" '.(($consentements[$i]['defaut'])?'checked':'').'>
		<label class="checkbox '.(($consentements[$i]['obligatoire'])?'required':'').'" for="caseConsent-'.$consentements[$i]['id'].'" style="margin-bottom:10px">'.$consentements[$i]['texteCase'].'.</label>(<a onclick="affConsent('.$consentements[$i]['id'].')" id="aConsent-'.$consentements[$i]['id'].'">afficher</a>)
		<div id="divTextConsent-'.$consentements[$i]['id'].'"class="blocText" style="display:none;margin-bottom:10px">'.bbCodeToHTML($consentements[$i]['texte']).'</div></td></tr>';
		
		if($consentements[$i]['obligatoire']){
			$tabConsentementsObligJS.= 'tabConsentementsObligJS.push('.$consentements[$i]['id'].');';
		}
	}
	$listeConsentements.='</table>';
	
}

$selectCGUJS = "";

if(count($_POST)>20){
	
	
	
	//Définition des messages d'erreurs
	$nomChamps=array("Numéro de carte ESN","Prénom","Nom","Sexe", "Pays d'origine","Date de naissance","E-mail","Indicatif téléphone","Téléphone","Adresse - Résidence","Adresse - Première ligne","Adresse - Deuxième ligne","Code postal","Ville","Etudes", "Mois de retour", "Informations diverses", "Date d'expiration de la cotisation","Type de cotisation");
	$maxChamps = array(15,30,30,1,50,10,80,5,12,999,150,150,5,130,60,7,999,10,999);
	$requiredChamps = array(true,true,true,true,true,true,true,false,false,false,true,false,true,true,true,false,false,true,true);
	$valChamps = array_values($_POST);
	for($i=0; $i<count($nomChamps); $i++){
		if($requiredChamps[$i] && empty($valChamps[$i]) || ($i==0 && isset($_POST['number']) && empty($_POST['number']))){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>'.$nomChamps[$i].'</em>.'));
		}
		if(mb_strlen($valChamps[$i])>$maxChamps[$i]){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>'.$nomChamps[$i].'</em> ne doit pas dépasser '.$maxChamps[$i].' caractères.'));
		}	
	}
	
	if (!empty($_POST['sexe']) && $_POST['sexe'] != "H" && $_POST['sexe'] != "F") {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[3].'</em> n\'est pas valide.'));
	}
	
	if(!empty($_POST['dob'])){
		$dob = date_parse($_POST['dob']);
		if (!checkdate($dob['month'], $dob['day'], $dob['year'])) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[5].'</em> n\'est pas valide.'));
		}else{
			$dateOB = $dob['year'].'-'.$dob['month'].'-'.$dob['day'];
		}	
	}

	if (!empty($_POST['mail']) && !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[6].'</em> n\'est pas valide.'));
	}

	
	if ((!empty($_POST['indtel']) && !is_numeric($_POST['indtel']))||mb_strlen($_POST['indtel'])==1) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[7].'</em> n\'est pas valide.'));
	}
	
	if (!empty($_POST['tel']) && !is_numeric($_POST['tel'])) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[8].'</em> n\'est pas valide.'));
	}
	
	if (!empty($_POST['codpos']) && !is_numeric($_POST['codpos'])) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[12].'</em> n\'est pas valide.'));
	}
	
	
	if(!empty($_POST['retour'])){
		if($_POST['retour'] == "aaaa-mm"){
			$dateRetour = "";
		}else{
			$retour = date_parse($_POST['retour']);
			if (!checkdate($retour['month'], $retour['day'], $retour['year'])) {
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[15].'</em> n\'est pas valide.'));
			}else{
				$dateRetour = $retour['year'].'-'.$retour['month'];
			}
		}		
	}
	
	if(!empty($_POST['finCotis'])){
		$finCotis = date_parse($_POST['finCotis']);

		if (!checkdate($finCotis['month'], $finCotis['day'], $finCotis['year'])) {
		
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[17].'</em> n\'est pas valide.'));
		
		}else{
			
			if(date_create($_POST['finCotis']) > date_create($defaultFinCotis)){
				
				array_push($pageMessages, array('type'=>'err', 'content'=>'La validité de la cotisation ne peut pas excéder un an.'));
				
			}else{
				
				if(date_create($_POST['finCotis']) < date_create(date("Y-m-d"))){
				
					array_push($pageMessages, array('type'=>'err', 'content'=>'La date d\'expiration de la cotisation n\'est pas valide.'));
				
				}else{
			
					$dateFinCotis = $finCotis['year'].'-'.$finCotis['month'].'-'.$finCotis['day'];
			
				}			
			}
		}	
	}
	
	//Cotisation payante ?
	if(!empty($_POST['cotisation'])){
		for($i=0; $i<count($typesCotis); $i++){	
			if($typesCotis[$i]['id']==$_POST['cotisation']){
				$prixCotis = $typesCotis[$i]['prix'];
				$nomCotis = $typesCotis[$i]['descr'];
			}
		}
		
		if($prixCotis!=0){
			if(mb_strlen($_POST['recu'])>3){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Numéro reçu</em> ne doit pas dépasser 3 caractères.'));
			}
			if (empty($_POST['recu'])){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Numéro reçu</em>.'));
			}
			elseif (!is_numeric($_POST['recu'])){
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Numéro reçu</em> n\'est pas valide.'));
			}elseif($_POST['recu']<0){
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Numéro reçu</em> n\'est pas valide.'));
			}
		}
	}
	
	//Verif consentements obligatoires
	
	
	for($i=0; $i<count($consentements); $i++){
		if($consentements[$i]['obligatoire'] && !isset($_POST['caseConsent-'.$consentements[$i]['id']])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'L\'adhérent doit accepter les clauses obligatoires.'));
			break;
		}
	}
	
	//Init consent apres POST pour conserver les choix
		$listeConsentements="";

	if($consentements!==false && !empty($consentements)){

		$listeConsentements='<table class="invisible">';
	
		for($i=0; $i<count($consentements); $i++){	
		
			
			$listeConsentements.='<tr><td><input type="checkbox" id="caseConsent-'.$consentements[$i]['id'].'" name="caseConsent-'.$consentements[$i]['id'].'" '.((isset($_POST['caseConsent-'.$consentements[$i]['id']]))?'checked ':' ').'>
			<label class="checkbox '.(($consentements[$i]['obligatoire'])?'required':'').'" for="caseConsent-'.$consentements[$i]['id'].'" style="margin-bottom:10px">'.$consentements[$i]['texteCase'].'.</label>(<a onclick="affConsent('.$consentements[$i]['id'].')" id="aConsent-'.$consentements[$i]['id'].'">afficher</a>)
			<div id="divTextConsent-'.$consentements[$i]['id'].'"class="blocText" style="display:none;margin-bottom:10px">'.bbCodeToHTML($consentements[$i]['texte']).'</td></tr></div>';

		}
		
		$listeConsentements.='</table>';
	}
	
	if(!empty($tabChamps['cgu']['valeur'])){
		$selectCGUJS="document.getElementById('cgu').checked=true";
	}else{
		$selectCGUJS="";
	}
	
	if(empty($pageMessages)){ //si pas d'erreur : go pour inscription

		$bd = db_connect();
		
		if(isset($_POST['nocard'])){
			
			$_POST['number'] = "NULL";
			
		}else{
			$_POST['number'] = "'".strtoupper(mysqli_real_escape_string($bd, $_POST['number']))."'";
		}
		
		$_POST['prenom'] = mysqli_real_escape_string($bd, ucwords(strtolower($_POST['prenom'])));
		$_POST['nom'] = mysqli_real_escape_string($bd, ucwords(strtolower($_POST['nom'])));
		$_POST['pays'] = mysqli_real_escape_string($bd, $_POST['pays']);
		$_POST['mail'] = mysqli_real_escape_string($bd, $_POST['mail']);
		$_POST['divers'] = mysqli_real_escape_string($bd, $_POST['divers']);
		
		if(!empty($_POST['tel'])){
			$tel = $_POST['indtel']." ";
			$chiffreTel = str_split($_POST['tel']);
			for($i=-1; $i<count($chiffreTel); $i+=2){
				if($i==-1){
					$tel.=$chiffreTel[0];
				}else{
					$tel.=" ".$chiffreTel[$i].$chiffreTel[$i+1];
				}
			}
		}else{$tel = null;}
		
		$adresse = mysqli_real_escape_string($bd, $_POST['adr1'].'&#10;'.((!empty($_POST['adr2']))?$_POST['adr2'].'&#10;':"").$_POST['codpos'].' '.$_POST['ville']);
		$_POST['etudes'] = mysqli_real_escape_string($bd, $_POST['etudes']);		
		
		
		//Verif EI déjà ajouté avec adresse mail ou numéro carte
				
		$verifMail = db_tableau($bd, "		
			SELECT email
			FROM membres_adherents
			WHERE email='".$_POST['mail']."'");
			
		$verifCarte = db_tableau($bd, "		
			SELECT idesn
			FROM membres_adherents
			WHERE idesn=".$_POST['number']."");
	
		
		if((count($verifMail) == 0 && count($verifCarte) == 0) || (count($verifMail) == 0 && isset($_POST['nocard']))){
		
		
			$addAdh = db_exec($bd, "
								INSERT INTO membres_adherents(idesn, prenom, nom, sexe, pays, dob, tel, email, adresse, etudes, dateRetour, divers, cotisation, dateInscr, dateFinInscr)
								VALUES(".$_POST['number'].",'".$_POST['prenom']."','".$_POST['nom']."','".$_POST['sexe']."','".$_POST['pays']."','".$dateOB."',
								'".$tel."','".$_POST['mail']."','".$adresse."','".$_POST['etudes']."','".$dateRetour."','".$_POST['divers']."',
								'".$nomCotis." - ".$prixCotis."€',NOW(),'".$dateFinCotis."')");

			if($addAdh!==false){
				
				$idNewAdh = db_lastId($bd);
				
				//Add consentements acceptés
				for($i=0; $i<count($consentements); $i++){
					if(isset($_POST['caseConsent-'.$consentements[$i]['id']])){
						
						$addConsent = db_exec($bd, "
								INSERT INTO gestion_consentements_accepted(idAdh, idConsent)
								VALUES(".$idNewAdh.",".$consentements[$i]['id'].")");

						if($addConsent===false){die("Erreur ajout consentement.");}
					}
				}
				
				
				array_push($_SESSION['postMessages'],array("ok", $_POST['prenom']." ".$_POST['nom']." a bien été ajouté à la liste des adhérents."));

				if($prixCotis!=0){
					addCaisse("Cotisation de ".$_POST['prenom']." ".$_POST['nom']." - ".$nomCotis, $prixCotis, $_POST['recu'], 'ext', -2);
				}
				//Verif go pour inscription activité ou pas
				if($_POST['goInscrAct']==1){
					header('Location: http://'.$_SERVER['HTTP_HOST'].'/activity/inscription.php?idAdh='.$idNewAdh);
					die();
				}else{
					header('Location: http://'.$_SERVER['HTTP_HOST'].'/index.php');
					die();
				}
			}
		}else{
			
			if(count($verifMail) > 0){
				array_push($pageMessages, array('type'=>'err', 'content'=>'L\'adresse e-mail est déjà utilisée par un autre adhérent.'));
			}
			
			if(count($verifCarte) > 0 && !isset($_POST['nocard'])){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le numéro de carte ESN est déjà utilisé par un autre adhérent.'));
			}
			
		}
		db_close($bd);
	}
}//fin verif post


//Construction Liste Pays
$lstPays = '<option value="">Choisissez...</option>';

$filePays = fopen(($GLOBALS['SITE']->getFolderData()).'/../liste_pays.html', 'r');

while (!feof($filePays)){
	$lignePays = trim(fgets($filePays));
	$lstPays .= '<option value="'.$lignePays.'">'.$lignePays.'</option>';
}
fclose($filePays);


//Construction Liste Résidences
$lstResidences = '<option value="">Liste des résidences</option>';

$fileRes = fopen(($GLOBALS['SITE']->getFolderData()).'/liste_residences.html', 'r');
$isGroup = false;
while (!feof($fileRes)){
	$ligneRes = explode('//',trim(fgets($fileRes)),4);	

	if(count($ligneRes)==4){
		$lstResidences .= '<option value="'.$ligneRes[0].'//'.$ligneRes[1].'//'.$ligneRes[2].'//'.$ligneRes[3].'">'.$ligneRes[0].'</option>';
	}elseif(strpos($ligneRes[0],'>')===0){
		if($isGroup)
			$lstResidences .= '</optgroup>';
		
		$lstResidences .= '<optgroup label="'.substr($ligneRes[0],1).'">';
		$isGroup = true;
	}
}
if($isGroup)
	$lstResidences .= '</optgroup>';
fclose($fileRes);

//Construction Liste Etudes
$lstEtudes = '<option value="">Choisissez...</option>';

$fileEtudes = fopen(($GLOBALS['SITE']->getFolderData()).'/liste_etudes.html', 'r');
$isGroup = false;
while (!feof($fileEtudes)){
	$ligneEtudes = trim(fgets($fileEtudes));

	if(strpos($ligneEtudes[0],'>')===0){
		if($isGroup)
			$lstEtudes .= '</optgroup>';
		
		$lstEtudes .= '<optgroup label="'.substr($ligneEtudes,1).'">';
		$isGroup = true;
	}else{
		$lstEtudes .= '<option value="'.$ligneEtudes.'">'.$ligneEtudes.'</option>';
	}
}
if($isGroup)
	$lstEtudes .= '</optgroup>';
fclose($fileEtudes);
	

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<form method=post id="formAdh" action="http://<?php echo $_SERVER['HTTP_HOST']; ?>/membres/newAdh.php">

<table class="invisible"><tbody><tr><td>
	<label for="number" class="required">numéro de carte esn</label>
	<input type="text" id="number" name="number" maxlength=15 value="<?php echo $valChamps[0]; ?>" autocomplete="off"/>
</td><td style="padding-top:12px">
	<input type="checkbox" id="nocard" name="nocard" onchange="selectNoCard()" >
	<label class="checkbox" for="nocard" style="margin-bottom:10px">Pas de carte ESN</label> 
</td></tr></tbody></table>


<table class="invisible"><tbody><tr><td>
<label for="prenom" class="required">prénom</label>
<input type="text" id="prenom" name="prenom" maxlength=30 value="<?php echo $valChamps[1]; ?>" autocomplete="off"/>
</td><td>
<label for="nom" class="required">nom</label>
<input type="text" id="nom" name="nom" maxlength=30 value="<?php echo $valChamps[2]; ?>" autocomplete="off"/>
</td></tr></tbody></table>

<label for="sexe" class="required">sexe</label>
	<input id="sexeH" type="radio" name="sexe" value="H" <?php echo (($valChamps[3]=="H")?"checked":""); ?>>  
	<label class="radio" for="sexeH">Homme</label>  
	<input id="sexeF" type="radio" name="sexe" value="F"  <?php echo (($valChamps[3]=="F")?"checked":""); ?>>  
	<label class="radio" for="sexeF">Femme</label> 

<label for="pays" class="required">pays d'origine</label>
<select id="pays" name="pays">
<?php echo $lstPays ?>
</select>

<label for="dob" class="required">date de naissance</label>
<input type="date" id="dob" name="dob" maxlength=10 value="<?php echo $valChamps[5]; ?>" autocomplete="off"/>

<label for="mail" class="required">e-mail</label>
<input type="text" id="mail" name="mail" maxlength=80 value="<?php echo $valChamps[6]; ?>" autocomplete="off"/>

<label for="indtel" >téléphone</label>
<table class="invisible"><tbody><tr><td>
<input type="text" id="indtel" name="indtel" style="width:40px" onkeyup="addPlus()" maxlength=5 value="<?php echo $valChamps[7]; ?>" autocomplete="off"/>
</td><td>
<input type="text" id="tel" name="tel" style="width:226px" onkeyup="supZero()" maxlength=12 value="<?php echo $valChamps[8]; ?>" autocomplete="off"/>
</td></tr></tbody></table>

<table class="invisible"><tbody><tr><td>
<label for="residence" >adresse - résidence</label>
<select id="residence" name="residence" onchange="fillAdresse()">
<?php echo $lstResidences ?>
</select>
</td></tr><tr><td>
<label for="adr1" class="required"class="required">adresse - première ligne</label>
<input type="text" id="adr1" name="adr1" maxlength=150 value="<?php echo $valChamps[10]; ?>" autocomplete="off"/>
</td><td>
<label for="adr2" >adresse - deuxième ligne</label>
<input type="text" id="adr2" name="adr2" maxlength=150 value="<?php echo $valChamps[11]; ?>" autocomplete="off"/>
</td></tr><tr></tbody></table>
<table class="invisible"><tbody><tr><td>
<label for="codpos" class="required">code postal</label>
<input type="text" id="codpos" name="codpos" style="width:80px" maxlength=5 value="<?php echo $valChamps[12]; ?>" autocomplete="off"/>
</td><td>
<label for="ville" class="required">ville</label>
<input type="text" id="ville" name="ville" style="width:186px" maxlength=130 value="<?php echo $valChamps[13]; ?>" autocomplete="off"/>
</td></tr></tbody></table>

<label for="etudes" class="required">études</label>
<select id="etudes" name="etudes">
<?php echo $lstEtudes?>
</select>

<label for="retour" >mois de retour envisagé</label>
<input type="month" id="retour" name="retour" maxlength=7 value="<?php echo $valChamps[15]; ?>" autocomplete="off"/>


<label for="divers" >informations diverses : régime alimentaire, allergies, handicap, ...</label>
<textarea type="month" id="divers" name="divers" style="height:36px" autocomplete="off"><?php echo $valChamps[16]; ?></textarea>

<label for="finCotis" class="required">date d'expiration de la cotisation</label>
<input type="date" id="finCotis" name="finCotis" maxlength=10 value="<?php echo $valChamps[17]; ?>" autocomplete="off"/>


<table class="invisible"><tbody><tr><td>
<label for="cotisation" class="required">type de cotisation</label>
<select id="cotisation" name="cotisation" onchange="changeCotis()">
<?php echo $selectCotis?>
</select>
</td><td id="numRecu">
<label for="recu" class="required">numero reçu</label>
<input type="text" id="recu" name="recu" style="width:90px" maxlength=3 value="<?php echo $valChamps[19]; ?>" autocomplete="off"> 
</td></tr></tbody></table>

<?php echo $listeConsentements?>

<input type="hidden" id="goInscrAct" name="goInscrAct">
</form>

<?php if(!empty($tabChamps['cgu']['valeur'])){ ?>
	<input type="checkbox" id="cgu" name="cgu">
	<label class="checkbox required" for="cgu" style="margin-bottom:10px">Le nouvel adhérent a pris conscience et accepte les <a href="../cgu.php" target="_blank"> conditions générales de vente</a>.</label>
<?php } ?>

<table class="invisible"><tbody><tr>
<td style="width:100px"><input type="button" onclick="submAdh('no')" id="submitAdhN" value="valider" /></td><td><input type="button" onclick="submAdh('yes')" id="submitAdhY" value="valider et inscrire à une activité" /></td>
</tr></tbody></table>


<script type="text/javascript">

var selectCotisJS=new Array();
<?php echo $selectCotisJS; ?>

<?php echo $selectCGUJS; ?>

var tabConsentementsObligJS=new Array();
<?php echo $tabConsentementsObligJS; ?>

var valCard = "<?php echo $valChamps[0];?>";
var val1 = "<?php echo $valChamps[4];?>";
var val2 = "<?php echo $valChamps[9];?>";
var val3 = "<?php echo $valChamps[14];?>";
var val4 = "<?php echo $valChamps[18];?>";

var opt = document.getElementsByTagName('option');
	for(i=0;i<opt.length;i++){
		if(opt[i].value != "" && (opt[i].value==val1 || opt[i].value==val2 || opt[i].value==val3 || opt[i].value==val4)){
			opt[i].selected="selected";
		}
	}
	
if(valCard == "on"){
	document.getElementById('nocard').checked = true;
	document.getElementById('number').value = "";
	document.getElementById('number').disabled = true;
	
}

	
if(selectCotisJS[cotisation.selectedIndex][0]==0){
	document.getElementById('numRecu').style.display = "none";
}


function selectNoCard(){
	
	if(document.getElementById('nocard').checked){
		
		document.getElementById('number').value = "";
		document.getElementById('number').disabled = true;
		
	}else{
		
		document.getElementById('number').disabled = false;
		
	}	
}



function addPlus(){
	if(document.getElementById('indtel').value.charAt(0) != "+"){
		var ind=document.getElementById('indtel').value;
		document.getElementById('indtel').value = "+"+ind;
	}
}

function supZero(){
	if(document.getElementById('tel').value.charAt(0) == "0"){
		document.getElementById('tel').value="";
	}
}

function fillAdresse(){

	var residence = document.getElementById('residence');
	var adresse = residence.options[residence.selectedIndex].value;
	if(adresse != ""){
		var adr = adresse.split("//");
		document.getElementById('adr1').value = adr[0];
		document.getElementById('adr2').value = adr[1];
		document.getElementById('codpos').value = adr[2];
		document.getElementById('ville').value = adr[3];
	}else{
		document.getElementById('adr1').value = "";
		document.getElementById('adr2').value = "";
		document.getElementById('codpos').value = "";
		document.getElementById('ville').value = "";
	}
}

function changeCotis(){

	if(selectCotisJS[cotisation.selectedIndex][0]==0){
		document.getElementById('numRecu').style.display = "none";
		document.getElementById('recu').value = "";

	}else{
		document.getElementById('numRecu').style.display = "";
		document.getElementById('recu').focus();
	}

}

function affConsent(id){
	
	if(document.getElementById('divTextConsent-'+id).style.display == "none"){
		document.getElementById('divTextConsent-'+id).style.display = "";
		document.getElementById('aConsent-'+id).innerHTML = "masquer";

	}else{
		document.getElementById('divTextConsent-'+id).style.display = "none";
		document.getElementById('aConsent-'+id).innerHTML = "afficher";
	}
	
}
	
function submAdh(inscrAct){
	//Consentements obligatoires
	
	for(var i=0;i<tabConsentementsObligJS.length;i++){
		if(document.getElementById('caseConsent-'+tabConsentementsObligJS[i]).checked==false){
			alert("L'adhérent doit accepter les clauses obligatoires.");
			return;
		}
	}
	
	
<?php if(!empty($tabChamps['cgu']['valeur'])){ ?>
	if(document.getElementById('cgu').checked == false){
		alert("L'adhérent doit accepter les clauses obligatoires.");
		return;
	}
<?php } ?>

	if(inscrAct=="yes"){
		document.getElementById('goInscrAct').value = "1";
	
		document.getElementById('submitAdhY').disabled=true;
		document.getElementById('submitAdhY').value = "Patientez...";
		document.getElementById('submitAdhY').onclick="";	
		document.getElementById('submitAdhN').disabled=true;
		document.getElementById('submitAdhN').style.display = "none";
		document.getElementById('submitAdhN').onclick="";
		
		document.getElementById('formAdh').submit();
	}else if (inscrAct=="no"){
		document.getElementById('goInscrAct').value = "0";
	
		document.getElementById('submitAdhN').disabled=true;
		document.getElementById('submitAdhN').value = "Patientez...";
		document.getElementById('submitAdhN').onclick="";	
		document.getElementById('submitAdhY').disabled=true;
		document.getElementById('submitAdhY').style.display = "none";
		document.getElementById('submitAdhY').onclick="";
		
		document.getElementById('formAdh').submit();
	}	
}
</script> 

<?php
echo $footer;
?>