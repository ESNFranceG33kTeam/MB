<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

$affMenu=false;
define('TITRE_PAGE',"Erreur 403");


include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<div>Vous n'avez pas la permission d'accéder à cette page.<br/><br/><a href="<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/index.php' ?>">Retour à la page d'accueil</a></div>
<?php
echo $footer;
?>