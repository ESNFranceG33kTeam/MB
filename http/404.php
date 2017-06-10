<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

$affMenu=false;
define('TITRE_PAGE',"Erreur 404");


include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<div>Cette page n'existe pas.<br/><br/><a href="<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/index.php' ?>">Retour à la page d'accueil</a></div>
<?php
echo $footer;
?>