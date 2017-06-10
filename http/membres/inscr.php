<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

$affMenu=true;

$valChamps = array("","","jj-mm-aaaa","","","","","","","","","");
$infosUsr=array();

define('TITRE_PAGE',"Inscription");
//VERIF CODE D'ACCES
$acces=false;
$firstInscr=false;

if(isset($_GET['code'])){

	$bd = db_connect();
	
	$_GET['code'] = mysqli_real_escape_string($bd, $_GET['code']);
	
	$infosUsr = db_ligne($bd, "
								SELECT id, prenom, nom, login, finProbatoire, arrived
								FROM membres_prebenevoles
								WHERE code='".$_GET['code']."'");
								
	if($_GET['code']=="admin"){
		$countUsr = db_valeur($bd, "
								SELECT COUNT(*)
								FROM membres_benevoles");
	}	
	
	db_close($bd);

	if(empty($infosUsr) && $infosUsr!==false){
	
		if(isset($countUsr) && $countUsr!==false && $countUsr==0){
			$acces=true;
			$firstInscr=true;
		}
		
	}elseif($infosUsr!==false ){
		$acces=true;
	}
}

if(!$acces){
	array_push($pageMessages, array('type'=>'err', 'content'=>'Code d\'accès invalide.'));

}else{
	
	if($firstInscr){
		$infosUsr[0] = 0;
		$infosUsr[1] = "";
		$infosUsr[2] = "";
		$infosUsr[3] = "";
		$infosUsr['finProbatoire'] = "";
	}

	if(count($_POST) > 10){
		//Définition des messages d'erreurs
		$nomChamps=array("Mot de passe", "Confirmation du mot de passe", "Date de naissance","E-mail courant", "E-mail Microsoft", "Profil Facebook", "Téléphone", "Adresse", "Code postal", "Ville", "Etudes");
		$maxChamps = array(30,30,10,80,80,100,10,150,5,130,60);
		
		if($tabChamps['moduleOneDrive']['valeur']!='Oui'){
			$_POST['mail_microsoft']="Unknown";
		}
		
		$valChamps = array_values($_POST);
		
		if($firstInscr){
			$valChamps = array_splice($valChamps,2);
		}
		
		for($i=0; $i<count($nomChamps); $i++){
			if(empty($valChamps[$i])){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>'.$nomChamps[$i].'</em>.'));
			}
			if(mb_strlen($valChamps[$i])>$maxChamps[$i]){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>'.$nomChamps[$i].'</em> ne doit pas dépasser '.$maxChamps[$i].' caractères.'));
			}	
		}
		
		
		if($firstInscr){
			if (empty($_POST['prenom'])){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Prénom</em>.'));
			}
			if (empty($_POST['nom'])){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom</em>.'));
			}
			if (mb_strlen($_POST['prenom'])>30){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Prénom</em> ne doit pas dépasser 30 caractères.'));
			}
			if (mb_strlen($_POST['nom'])>30){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom</em> ne doit pas dépasser 30 caractères.'));
			}
		}
			
			
		if(!empty($_POST['pass']) && !empty($_POST['pass_conf']) && ($_POST['pass'] != $_POST['pass_conf'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La confirmation ne correspond pas au mot de passe.'));
			$valChamps[0]="";
			$valChamps[1]="";
		}
		
		if(!ctype_alnum($_POST['pass'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le mot de passe ne doit comporter que des caractères alphanumériques.'));
		}
		
		
		if(!empty($_POST['dob'])){
			$dob = date_parse($_POST['dob']);
			if (!checkdate($dob['month'], $dob['day'], $dob['year'])) {
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[2].'</em> n\'est pas valide.'));
			}else{
				$dateOB = $dob['year'].'-'.$dob['month'].'-'.$dob['day'];
			}	
		}

		if (!empty($_POST['mail']) && !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[3].'</em> n\'est pas valide.'));
		}
		if ($tabChamps['moduleOneDrive']['valeur']=='Oui' && !empty($_POST['mail_microsoft']) && !filter_var($_POST['mail_microsoft'], FILTER_VALIDATE_EMAIL)) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[4].'</em> n\'est pas valide.'));
		}
		
		if (!empty($_POST['fb']) && !filter_var($_POST['fb'], FILTER_VALIDATE_URL)) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[5].'</em> n\'est pas valide.'));
		}
		
		if (!empty($_POST['tel']) && !is_numeric($_POST['tel'])) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[6].'</em> n\'est pas valide.'));
		}
		
		if (!empty($_POST['codpos']) && !is_numeric($_POST['codpos'])) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[8].'</em> n\'est pas valide.'));
		}
		
		$bd = db_connect();
		
		$_POST['mail'] = mysqli_real_escape_string($bd, $_POST['mail']);
	
		$rep = db_ligne($bd, "
							SELECT mail
							FROM membres_benevoles
							WHERE mail='".$_POST['mail']."'");

	
		if(!empty($rep) || $rep===false){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Cette adresse mail est déjà utilisée.'));
		}
		
		if(empty($pageMessages)){ //si pas d'erreur : go pour inscription

			
			$_POST['mail_microsoft'] = mysqli_real_escape_string($bd, $_POST['mail_microsoft']);
			$_POST['fb'] = mysqli_real_escape_string($bd, $_POST['fb']);
			$_POST['logGalaxy'] = mysqli_real_escape_string($bd, $_POST['logGalaxy']);
			$adresse = mysqli_real_escape_string($bd, $_POST['adr'].'//'.$_POST['codpos'].'//'.$_POST['ville']);
			$_POST['etudes'] = mysqli_real_escape_string($bd, $_POST['etudes']);
			$voit=($_POST['voiture']=="oui")?1:0;
			$affAnnuaire=(isset($_POST['affAnnuaire']))?1:0;
			
			if($firstInscr){
				$infosUsr[1] = mysqli_real_escape_string($bd, $_POST['prenom']);
				$infosUsr[2] = mysqli_real_escape_string($bd, $_POST['nom']);
				$infosUsr[3] = strtolower($infosUsr[1]."_".$infosUsr[2]);
				$infosUsr[3] = str_replace(" ",".",$infosUsr[3]);
				$arrived = "";
			}else{
				$arrived = $infosUsr['arrived'];
				
			}
			
			$addUsr = db_exec($bd, "
								INSERT INTO membres_benevoles(nom, prenom, login, pass, dob, mail, mail_microsoft, fb, tel, adresse, etudes, voiture, affAnnuaire, arrived, last_connect)
								VALUES('".$infosUsr[2]."','".$infosUsr[1]."','".$infosUsr[3]."','".crypt($_POST['pass'], '$2a$07$esnnancy4everthebest$')."','".$dateOB."',
								'".$_POST['mail']."','".$_POST['mail_microsoft']."','".$_POST['fb']."','".$_POST['tel']."','".$adresse."','".$_POST['etudes']."',
								'".$voit."','".$affAnnuaire."','".$arrived."',NOW())");
								
			$idUsr = db_valeur($bd, "
								SELECT id
								FROM membres_benevoles
								WHERE login='".$infosUsr[3]."'");
			

			if($addUsr!==false && $idUsr!==false){
			
				
				
				if(empty($infosUsr['finProbatoire'])){
				
					if($firstInscr){
						$addDroits = db_exec($bd, "INSERT INTO membres_droits(id, general) VALUES('".$idUsr."','bureau')");
					}else{
						$addDroits = db_exec($bd, "INSERT INTO membres_droits(id, general) VALUES('".$idUsr."','membre')");
					}
				
				}else{
					$addDroits = db_exec($bd, "INSERT INTO membres_droits(id, general, finProbatoire) VALUES('".$idUsr."','probatoire','".$infosUsr['finProbatoire']."')");
				}
				
				$addOne = db_exec($bd, "INSERT INTO membres_onedrive_invits(id) VALUES('".$idUsr."')");
				
				
				if($addDroits!==false && $addOne!==false){
					
					if(!$firstInscr){
					
						
							$supPreUsr = db_exec($bd, "
								DELETE FROM membres_prebenevoles
								WHERE id='".$infosUsr[0]."'
								LIMIT 1");
						
					
					}else{
						//Init BDD
						
						$addCaissePeriode = db_exec($bd, "INSERT INTO gestion_caisse_periodes(dteStart) VALUES(NOW())");
						
					}
					
					if((!$firstInscr && $supPreUsr!==false)||($firstInscr && $addCaissePeriode!==false)){
					
						$_SESSION = array();
						$_SESSION['connect'] = true;
						$_SESSION['nomBDD'] = $_SERVER['SERVER_NAME'];
						$_SESSION['id'] = $idUsr;
						$_SESSION['prenom'] = $infosUsr[1];
						$_SESSION['nom'] = $infosUsr[2];
						
						if(empty($infosUsr['finProbatoire'])){
						
							if($firstInscr){
								$_SESSION['droits'] = "bureau";
							}else{
								$_SESSION['droits'] = "membre";
							}
							
						}else{
							$_SESSION['droits'] = "probatoire";
						}
						$_SESSION['postMessages'] = array();
				
						array_push($_SESSION['postMessages'],array("ok", "Votre inscription a bien été validée. Bienvenue !"));
						header('Location: http://'.$_SERVER['HTTP_HOST'].'/index.php');
						die();
				
					}
				}
			}

		}
		db_close($bd);

	}//fin verif post
}//fin VERIF CODE

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

<?php if($acces){ ?>
<form method=post id="formInscr" action="inscr-<?php echo $_GET['code']; ?>">
<table class="invisible"><tbody><tr><td>
<label for="prenom" class="required">prénom</label>
<input type="text" id="prenom" name="prenom" <?php echo (($firstInscr)?'onkeyup="editUsrName()"':'disabled'); ?> maxlength=30 value="<?php echo $infosUsr[1]; ?>" />
</td><td>
<label for="nom" class="required">nom</label>
<input type="text" id="nom" name="nom" <?php echo (($firstInscr)?'onkeyup="editUsrName()"':'disabled'); ?> maxlength=30 value="<?php echo $infosUsr[2]; ?>" />
</td></tr></tbody></table>

<label for="usrname">identifiant</label>
<input type="text" id="usrname" name="usrname" disabled value="<?php echo $infosUsr[3]; ?>" /><img class="info" src="../template/images/information.png" title="Retenez bien votre identifiant, il vous permettra de vous connecter au site."/>

<table class="invisible"><tbody><tr><td>
<label for="pass" class="required">mot de passe</label>
<input type="password" id="pass" name="pass" maxlength=30 value="<?php echo $valChamps[0]; ?>"/>
</td><td>
<label for="pass_conf" class="required">confirmation du mot de passe</label>
<input type="password" id="pass_conf" name="pass_conf" maxlength=30 value="<?php echo $valChamps[1]; ?>"/>
</td></tr></tbody></table>

<label for="dob" class="required">date de naissance</label>
<input type="date" id="dob" name="dob" maxlength=10 value="<?php echo $valChamps[2]; ?>"/>

<table class="invisible"><tbody><tr><td>
<label for="mail" class="required">e-mail courant</label>
<input type="text" id="mail" name="mail" maxlength=80 value="<?php echo $valChamps[3]; ?>"/>
</td><td <?php echo (($tabChamps['moduleOneDrive']['valeur']!='Oui')?'style="display:none"':'');?> >
<label for="mail_microsoft" class="required">e-mail de votre compte microsoft</label>
<input type="text" id="mail_microsoft" name="mail_microsoft" maxlength=80 value="<?php echo $valChamps[4]; ?>"/><a href="https://signup.live.com/signup.aspx?wa=wsignin1.0&rpsnv=12&ct=1393958500&rver=6.4.6456.0&wp=MBI_SSL_SHARED&wreply=https%3a%2f%2fonedrive.live.com%2f%3finvref%3dd66ec2ee1999a9bc%26invsrc%3d90%26mkt%3dfr-FR&id=250206&cbcxt=sky&cbcxt=sky&bk=1393958502&uiflavor=web&uaid=c2b46ed6be2c402882e3c6d00fc6b0d4&mkt=FR-FR&lc=1036&lic=1" target="_blank"><img class="info" src="../template/images/information.png" title="Un compte Microsoft est obligatoire pour accéder aux fichiers partagés de l'association sur OneDrive. Si vous n'en possédez pas, cliquez ici."/></a>
</td></tr></tbody></table>

<label for="fb" class="required">url de votre profil facebook</label>
<input type="text" id="fb" name="fb" maxlength=100 value="<?php echo $valChamps[5]; ?>"/><img class="info" src="../template/images/information.png" title="Veuillez indiquer l'URL complète de votre profil Facebook avec 'http://'. Exemple: https://www.facebook.com/mirabellix.esnnancy"/>


<label for="tel" class="required">téléphone</label>
<input type="text" id="tel" name="tel" maxlength=15 value="<?php echo $valChamps[6]; ?>"/>

<table class="invisible"><tbody><tr><td>
<label for="adr" class="required">adresse</label>
<input type="text" id="adr" name="adr" maxlength=150 value="<?php echo $valChamps[7]; ?>"/>
</td><td>
<label for="codpos" class="required">code postal</label>
<input type="text" id="codpos" name="codpos" style="width:80px" maxlength=5 value="<?php echo $valChamps[8]; ?>"/>
</td><td>
<label for="ville" class="required">ville</label>
<input type="text" id="ville" name="ville" style="width:186px" maxlength=130 value="<?php echo $valChamps[9]; ?>"/>
</td></tr></tbody></table>

<label for="etudes" class="required">études</label>
<select id="etudes" name="etudes">
<?php echo $lstEtudes?>
</select>

<label for="voiture" class="required">voiture disponible régulièrement</label>
	<input id="voitureN" type="radio" name="voiture" value="non">  
	<label class="radio" for="voitureN">Non</label>  
	<input id="voitureO" type="radio" name="voiture" value="oui">  
	<label class="radio" for="voitureO">Oui</label> 

<input type="checkbox" id="affAnnuaire" name="affAnnuaire" checked>
<label class="checkbox" id="labelAffAnnuaire" for="affAnnuaire" style="margin-bottom:10px">Je souhaite que mes coordonnées (mail et téléphone) apparaissent sur l'annuaire privé d'ESN France</label> 
	
<input type="button" onclick="submAdd()" id="submitAdd" value="je retiens bien mon identifiant et je valide !" />
</form>

<script type="text/javascript">
var val = "<?php echo $valChamps[10];?>";
var opt = document.getElementsByTagName('option');
	for(i=0;i<opt.length;i++){
		if(opt[i].value==val){
			opt[i].selected="selected";
		}
	}

if("<?php echo $valChamps[11];?>" == "oui"){
	document.getElementById('voitureO').checked="checked";
}else{
	document.getElementById('voitureN').checked="checked";
}

<?php if($firstInscr){ ?>
function editUsrName(){

	var prenom = document.getElementById('prenom').value;
	var nom = document.getElementById('nom').value;

	if (nom==""){
		document.getElementById('usrname').value = prenom;
		document.getElementById('usrname').value = document.getElementById('usrname').value.replace(/\s+/g,'.');
	}
	if (prenom==""){
		document.getElementById('usrname').value = nom;
		document.getElementById('usrname').value = document.getElementById('usrname').value.replace(/\s+/g,'.');
	}
	if (nom!="" && prenom!=""){
		document.getElementById('usrname').value = prenom.toLowerCase()+"_"+nom.toLowerCase();
		document.getElementById('usrname').value = document.getElementById('usrname').value.replace(/\s+/g,'.');
	}

}
<?php } ?>


function submAdd(){
	document.getElementById('submitAdd').disabled=true;
	document.getElementById('submitAdd').value = "Patientez...";
	document.getElementById('submitAdd').onclick="";
	document.getElementById('formInscr').submit();
}

</script> 

<?php } ?>
<?php
echo $footer;
?>