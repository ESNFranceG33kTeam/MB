<?php
define('NEED_CONNECT',false);
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

$affMenu=false;
define('TITRE_PAGE',"Conditions générales");

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Vie privée</h3>
<div class="blocText">
L'association <?php echo $tabChamps['nomAsso']['valeur']; ?> traite les données concernant ses adhérents conformément à son réglement intérieur et aux dispositions de
la loi Informatique et Libertés n° 78-17 du 6 janvier 1978 modifiée par la loi du 6 août 2004 et de la délibération n°2010-229 du 10 juin 2010
décidant de la dispense de déclaration des traitements constitués à des fins d'information ou de communication externe.
<br /><br />
Vos données personelles sont modifiables sur simple demande adressée par mail à
<a href="mailto:<?php echo $tabChamps['mailAdmin']['valeur']; ?>"><?php echo $tabChamps['mailAdmin']['valeur']; ?></a>.
<br /><br />
Conformément à la législation en vigueur, les données personnelles sont supprimées en cas de radiation, démission, départ ou en cas d'expiration de la cotisation.
</div>

<?php if(!empty($tabChamps['cgu']['valeur'])){ ?>
<h3>Conditions générales de vente</h3>
<div class="blocText">
<?php echo bbCodeToHTML($tabChamps['cgu']['valeur']); ?>
</div>

<?php } ?>
<?php
echo $footer;
?>