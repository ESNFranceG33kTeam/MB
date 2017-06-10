<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

if((isset($_SESSION['connect']) && $_SESSION['connect'] === true)) {
	header('Location: http://'.$_SERVER['HTTP_HOST']);
	die();
}

$affMenu=true;
define('TITRE_PAGE',"Connexion");

//Définition des messages d'erreurs
if(isset($_POST['usrname']) && empty($_POST['usrname'])){
	array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Identifiant</em>.'));
}
if(isset($_POST['pass']) && empty($_POST['pass'])){
	array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Mot de passe</em>.'));
}

if(empty($pageMessages) && isset($_POST['usrname']) && isset($_POST['pass'])){ //si pas d'erreur : verif pseudo/pass

	$bd = db_connect();
	
	$_POST['usrname'] = mysqli_real_escape_string($bd, $_POST['usrname']);

	$infosUsr = db_ligne($bd, "
								SELECT ben.id, ben.prenom, ben.nom, drt.general 
								FROM membres_benevoles AS ben
								LEFT JOIN membres_droits AS drt ON ben.id = drt.id
								WHERE login='".$_POST['usrname']."' 
								AND pass='".crypt($_POST['pass'], '$2a$07$esnnancy4everthebest$')."'");
	db_close($bd);

	if(empty($infosUsr) && $infosUsr!==false){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Les informations fournies ne permettent pas de vous identifier.'));
	}elseif(!empty($infosUsr) && $infosUsr!==false){
	
		$bd = db_connect();
	
		$UpLastCo = db_exec($bd, "
								UPDATE membres_benevoles
								SET last_connect=NOW()
								WHERE login='".$_POST['usrname']."'");						
		db_close($bd);
		
		
		if($UpLastCo){
			
			if(!empty($_SESSION['redirect'])){
				$redirect = $_SESSION['redirect'];
			}else{
				$redirect = '';
			}
			
			session_destroy();
			session_start();
			
			$_SESSION = array();
			$_SESSION['connect'] = true;
			$_SESSION['connectGalaxy'] = false;
			$_SESSION['nomBDD'] = $_SERVER['SERVER_NAME'];
			$_SESSION['id'] = $infosUsr[0];
			$_SESSION['prenom'] = $infosUsr[1];
			$_SESSION['nom'] = $infosUsr[2];
			$_SESSION['droits'] = $infosUsr[3];
			$_SESSION['postMessages'] = array();
			header('Location: http://'.$_SERVER['HTTP_HOST'].$redirect);
			die();
		}
	}
}

if(isset($_GET['src']) && $_GET['src']=="deco"){
	array_push($pageMessages, array('type'=>'ok', 'content'=>'Vous avez bien été déconnecté.'));
}

if(isset($_GET['src']) && $_GET['src']=="decoGalaxy"){
	array_push($pageMessages, array('type'=>'ok', 'content'=>'Vous avez bien été déconnecté. N\'oubliez pas de vous déconnecter aussi de Galaxy.'));
}

if(isset($_GET['logGalaxy']) && $_GET['logGalaxy']=="nobody"){
	array_push($pageMessages, array('type'=>'err', 'content'=>'Votre identifiant Galaxy n\'est lié à aucun compte.'));
}

if(isset($_GET['logGalaxy']) && $_GET['logGalaxy']=="wrongID"){
	array_push($pageMessages, array('type'=>'err', 'content'=>'Votre identité Galaxy ne correspond pas avec celle du site.'));
}

if(isset($_GET['logGalaxy']) && $_GET['logGalaxy']=="wrongRole"){
	array_push($pageMessages, array('type'=>'err', 'content'=>'Votre rôle Galaxy ne correspond pas avec celui du site.'));
}

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Connexion via votre compte Galaxy</h3>
<input type="button" value="se connecter" onclick="self.location.href='connectGalaxy.php'"/>


<br />

<h3>Connexion sans Galaxy</h3>
<form method=post action="connect.php">
<label for="usrname" form="idutilisateur">identifiant</label>
<input type="text" id="usrname" name="usrname" autofocus />

<label for="pass" form="idutilisateur">mot de passe</label>
<input type="password" id="pass" name="pass" />

<input type="submit" value="valider" />
</form>
<?php
echo $footer;
?>