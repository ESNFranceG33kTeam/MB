<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

define('TITRE_PAGE',"Profil");

//Récupération des données

$bd = db_connect();
	
$infosUsr = db_ligne($bd, "
						SELECT login, dob, mail, mail_microsoft, fb, tel, adresse, etudes, voiture, affAnnuaire
						FROM membres_benevoles
						WHERE id='".ID."'");					
db_close($bd);
$valChamps = array();

if($infosUsr!==false){

	for($i=0; $i < 10; $i++){
		if($i != 6){
			array_push($valChamps, $infosUsr[$i]);
			
		}else{
			
			$adr = explode('//', $infosUsr[$i],3);
			array_push($valChamps, $adr[0]);
			array_push($valChamps, $adr[1]);
			array_push($valChamps, $adr[2]);
		}
	}
}


if(isset($_POST['pass']) && isset($_POST['pass_conf'])){ //Modifs pass
	if(empty($_POST['pass'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Mot de passe</em>.'));
	}
	if(empty($_POST['pass_conf'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Confirmation du mot de passe</em>.'));
	}
	if(mb_strlen($_POST['pass'])>30){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Mot de passe</em> ne doit pas dépasser 30 caractères.'));
	}
	if(mb_strlen($_POST['pass_conf'])>30){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Confirmation du mot de passe</em> ne doit pas dépasser 30 caractères.'));
	}
	
	
	if(!ctype_alnum($_POST['pass'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le mot de passe ne doit comporter que des caractères alphanumériques.'));
	}
	
	
	if(!empty($_POST['pass']) && !empty($_POST['pass_conf']) && ($_POST['pass'] != $_POST['pass_conf'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La confirmation ne correspond pas au mot de passe.'));
	}
	
	if(empty($pageMessages)){
		$bd = db_connect();
		$modifPass = db_exec($bd, "
			UPDATE membres_benevoles
			SET pass='".crypt($_POST['pass'], '$2a$07$esnnancy4everthebest$')."'
			WHERE id='".ID."'");	
		db_close($bd);
		
		if ($modifPass !== false){
			array_push($_SESSION['postMessages'],array("ok", "Votre mot de passe a bien été modifié."));
			header('Location: http://'.$_SERVER['HTTP_HOST'].'/index.php');
			die();
		}	
	}
}


if(count($_POST)>9){
	//Définition des messages d'erreurs
	$nomChamps=array("Date de naissance","E-mail courant", "E-mail Microsoft", "Profil Facebook", "Téléphone", "Adresse", "Code postal", "Ville");
	$maxChamps = array(10,80,80,100,10,150,5,130);
	$valChamps = array_values($_POST);
	for($i=0; $i<count($nomChamps); $i++){
		if(empty($valChamps[$i])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>'.$nomChamps[$i].'</em>.'));
		}
		if(mb_strlen($valChamps[$i])>$maxChamps[$i]){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>'.$nomChamps[$i].'</em> ne doit pas dépasser '.$maxChamps[$i].' caractères.'));
		}	
	}
	
	if(mb_strlen($valChamps[8])>60){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Etudes</em> ne doit pas dépasser 60 caractères.'));
	}

	
	if(!empty($_POST['dob'])){
		$dob = date_parse($_POST['dob']);
		if (!checkdate($dob['month'], $dob['day'], $dob['year'])) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[0].'</em> n\'est pas valide.'));
		}else{
			$dateOB = $dob['year'].'-'.$dob['month'].'-'.$dob['day'];
		}	
	}

	if (!empty($_POST['mail']) && !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[1].'</em> n\'est pas valide.'));
	}
	if ($tabChamps['moduleOneDrive']['valeur']=='Oui' && !empty($_POST['mail_microsoft']) && !filter_var($_POST['mail_microsoft'], FILTER_VALIDATE_EMAIL)) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[2].'</em> n\'est pas valide.'));
	}
	
	if (!empty($_POST['fb']) && !filter_var($_POST['fb'], FILTER_VALIDATE_URL)) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[3].'</em> n\'est pas valide.'));
	}
	
	if (!empty($_POST['tel']) && !is_numeric($_POST['tel'])) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[4].'</em> n\'est pas valide.'));
	}
	
	if (!empty($_POST['codpos']) && !is_numeric($_POST['codpos'])) {
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[6].'</em> n\'est pas valide.'));
	}
	
	
	$bd = db_connect();
	
	$_POST['mail'] = mysqli_real_escape_string($bd, $_POST['mail']);
	
	$rep = db_ligne($bd, "
							SELECT mail
							FROM membres_benevoles
							WHERE mail='".$_POST['mail']."' AND id!=".ID);

	
	if(!empty($rep) || $rep===false){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Cette adresse mail est déjà utilisée.'));
	}
	
	if(empty($pageMessages)){ //si pas d'erreur : go pour modifications

		
		
		$_POST['mail_microsoft'] = mysqli_real_escape_string($bd, $_POST['mail_microsoft']);
		$_POST['fb'] = mysqli_real_escape_string($bd, $_POST['fb']);
		$adresse = mysqli_real_escape_string($bd, $_POST['adr'].'//'.$_POST['codpos'].'//'.$_POST['ville']);
		$_POST['etudes'] = mysqli_real_escape_string($bd, $_POST['etudes']);
		$voit=($_POST['voiture']=="oui")?1:0;
		$affAnnuaire=(isset($_POST['affAnnuaire']))?1:0;
		
		
		$modifUsr = db_exec($bd, "
							UPDATE membres_benevoles
							SET dob='".$dateOB."', mail='".$_POST['mail']."', mail_microsoft='".$_POST['mail_microsoft']."',
							fb='".$_POST['fb']."', tel='".$_POST['tel']."', adresse='".$adresse."', etudes='".$_POST['etudes']."', voiture='".$voit."', affAnnuaire='".$affAnnuaire."' 
							WHERE id='".ID."'");
							

		if($modifUsr!==false){	
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Vos informations ont bien été modifiées'));
		}
	}
	db_close($bd);
	array_unshift($valChamps,$infosUsr[0]); 
	
}//fin verif post

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
<h3>Modification du mot de passe</h3>
<form method=post id="formMdp" action="profil.php">
<table class="invisible"><tbody><tr><td>
<label for="pass" class="required">nouveau mot de passe</label>
<input type="password" id="pass" name="pass" maxlength=30 value=""/>
</td><td>
<label for="pass_conf" class="required">confirmation du mot de passe</label>
<input type="password" id="pass_conf" name="pass_conf" maxlength=30 value=""/>
</td></tr></tbody></table>
</form>
<input type="button" onclick="submAdd('formMdp','submitMdp')" id="submitMdp" value="valider" />
<h3>Modification des informations personnelles</h3>

<form method=post id="formInfos" action="profil.php">
<table class="invisible"><tbody><tr><td>
<label for="prenom">prénom</label>
<input type="text" id="prenom" name="prenom" disabled="disabled" value="<?php echo PRENOM; ?>" />
</td><td>
<label for="nom">nom</label>
<input type="text" id="nom" name="nom" disabled="disabled" value="<?php echo NOM; ?>" />
</td></tr></tbody></table>

<label for="usrname">identifiant</label>
<input type="text" id="usrname" name="usrname" disabled="disabled" value="<?php echo $valChamps[0]; ?>" />

<label for="dob" class="required">date de naissance</label>
<input type="date" id="dob" name="dob" maxlength=10 value="<?php echo $valChamps[1]; ?>"/>

<table class="invisible"><tbody><tr><td>
<label for="mail" class="required">e-mail courant</label>
<input type="text" id="mail" name="mail" maxlength=80 value="<?php echo $valChamps[2]; ?>"/>
</td><td <?php echo (($tabChamps['moduleOneDrive']['valeur']!='Oui')?'style="display:none"':'');?> >
<label for="mail_microsoft" class="required">e-mail de votre compte microsoft</label>
<input type="text" id="mail_microsoft" name="mail_microsoft" maxlength=80 value="<?php echo $valChamps[3]; ?>"/><a href="https://signup.live.com/signup.aspx?wa=wsignin1.0&rpsnv=12&ct=1393958500&rver=6.4.6456.0&wp=MBI_SSL_SHARED&wreply=https%3a%2f%2fonedrive.live.com%2f%3finvref%3dd66ec2ee1999a9bc%26invsrc%3d90%26mkt%3dfr-FR&id=250206&cbcxt=sky&cbcxt=sky&bk=1393958502&uiflavor=web&uaid=c2b46ed6be2c402882e3c6d00fc6b0d4&mkt=FR-FR&lc=1036&lic=1" target="_blank"><img class="info" src="../template/images/information.png" title="Un compte Microsoft est obligatoire pour accéder aux fichiers partagés de l'association sur OneDrive. Si vous n'en possédez pas, cliquez ici."/></a>
</td></tr></tbody></table>

<label for="fb" class="required">url de votre profil facebook</label>
<input type="text" id="fb" name="fb" maxlength=100 value="<?php echo $valChamps[4]; ?>"/><img class="info" src="../template/images/information.png" title="Veuillez indiquer l'URL complète de votre profil Facebook avec 'http://'. Exemple: https://www.facebook.com/mirabellix.esnnancy"/>


<label for="tel" class="required">téléphone</label>
<input type="text" id="tel" name="tel" maxlength=15 value="<?php echo $valChamps[5]; ?>"/>

<table class="invisible"><tbody><tr><td>
<label for="adr" class="required">adresse</label>
<input type="text" id="adr" name="adr" maxlength=150 value="<?php echo $valChamps[6]; ?>"/>
</td><td>
<label for="codpos" class="required">code postal</label>
<input type="text" id="codpos" name="codpos" style="width:80px" maxlength=5 value="<?php echo $valChamps[7]; ?>"/>
</td><td>
<label for="ville" class="required">ville</label>
<input type="text" id="ville" name="ville" style="width:186px" maxlength=130 value="<?php echo $valChamps[8]; ?>"/>
</td></tr></tbody></table>

<label for="etudes" class="required">études</label>
<select id="etudes" name="etudes">
<?php echo $lstEtudes ?>
</select>

<label for="voiture" class="required">voiture disponible régulièrement</label>
	<input id="voitureN" type="radio" name="voiture" value="non">  
	<label class="radio" for="voitureN">Non</label>  
	<input id="voitureO" type="radio" name="voiture" value="oui">  
	<label class="radio" for="voitureO">Oui</label> 

<input type="checkbox" id="affAnnuaire" name="affAnnuaire" <?php echo (($valChamps[11])?'checked':''); ?>>
<label class="checkbox" id="labelAffAnnuaire" for="affAnnuaire" style="margin-bottom:10px">Je souhaite que mes coordonnées (mail et téléphone) apparaissent sur l'annuaire privé d'ESN France</label> 
	
	
	
<input type="button" onclick="submAdd('formInfos','submitInfos')" id="submitInfos" value="valider" />
</form>

<script type="text/javascript">
var val = "<?php echo $valChamps[9];?>";
var opt = document.getElementsByTagName('option');
	for(i=0;i<opt.length;i++){
		if(opt[i].value==val){
			opt[i].selected="selected";
		}
	}

if("<?php echo $valChamps[10];?>" == 1 || "<?php echo $valChamps[10];?>" == "oui"){
	document.getElementById('voitureO').checked="checked";
}else{
	document.getElementById('voitureN').checked="checked";
}

function submAdd(formId,submitId){
	document.getElementById(submitId).disabled=true;
	document.getElementById(submitId).value = "Patientez...";
	document.getElementById(submitId).onclick="";
	document.getElementById(formId).submit();
}
</script> 

<?php
echo $footer;
?>