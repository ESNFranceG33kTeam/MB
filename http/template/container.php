<?php
if (!defined('IS_CONNECT')){
	header('Location: http://'.$_SERVER['HTTP_HOST'].'/index.php');
	die();
}

if(IS_CONNECT){

	switch (DROITS) {
		case "probatoire":
			$colLinkBouton = 'link_probatoire';
			break;
		case "membre":
			$colLinkBouton = 'link_membres';
			break;
		case "bureau":
			$colLinkBouton = 'link_bureau';
			break;
	}
	$bd = db_connect();
	$tabBoutonsBar = db_tableau($bd, "SELECT nom, ".$colLinkBouton." AS link FROM gestion_config_boutonsbar ORDER BY position ASC");
	db_close($bd);
}

?>
<!DOCTYPE html>
<html>
<head>

<title><?php echo $tabChamps['title']['valeur']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
<link rel="icon" type="image/png" href="../ESN_star.png" />
<link rel="stylesheet" type="text/css" href="/template/style/stylev2.css" />

</head>
<body>
<div id="conteneur">
<header>
	<div class="inner">
			<div id="headerMain">

				<h1><img src="../template/images/ESN_star.png" alt="Logo ESN Nancy" style="margin-right:10px; width:24px;"/><?php echo $tabChamps['titre']['valeur']?></h1>
<?php
if(IS_CONNECT){
?>
				<div id="headerBoutons">
					<a class="nonSouligne" href="../"><span class="boutonHeader">Home</span></a>
					
					<?php
						foreach($tabBoutonsBar as $bouton){
							if(!empty($bouton['link'])){
							 echo '<a class="nonSouligne" href="'.$bouton['link'].'" target="_blank">
										<span class="boutonHeader">'.$bouton['nom'].'</span>
									</a>';
							}
						}
					?>
				</div>
<?php } ?>
			</div>
			
<?php
if(IS_CONNECT){
?>
		<div id="headerId">
			<span><b><?php echo PRENOM." ".NOM?></b></span><br />
			<span class="italic"><?php
					if(DROITS=='probatoire'){echo "membre en probation";}
					elseif(DROITS=='membre'){echo "membre actif";}
					elseif(DROITS=='bureau'){echo "membre du bureau";}
					?></span><br />
			<a class="gris" href="../membres/profil.php"><span>Profil</span></a>
			<span class="gris"> - </span>
			<a class="gris" href="../disconnect.php"><span>Déconnexion</span></a>
		</div>
<?php } ?>

	</div>

</header>
<div class="inner">
<?php
if(IS_CONNECT && $affMenu){
?>
	<div id="menu">
		<div class="entete">Activités</div>
		<div class="arrow"></div>
		<ul class="menu">
			<li><a href="../activity/inscription.php">Inscription</a></li>
			<li><a href="../activity/index.php">Liste</a></li>
		<?php if(DROITS=='membre' || DROITS=='bureau'){ ?>
			<li><a href="../activity/newAct.php">Créer</a></li>
		<?php } ?>
			<li><a href="../activity/statsActivities.php">Statistiques</a></li>
		</ul>
		<div class="entete">Adhérents</div>
		<div class="arrow"></div>
		<ul class="menu">
			<li><a href="../membres/newAdh.php">Nouveau</a></li>
			<li><a href="../membres/inscrOffline.php">Inscriptions Excel</a></li>
			<li><a href="../membres/index.php">Liste</a></li>
			<li><a href="../membres/statsMembres.php">Statistiques</a></li>
		</ul>
		<div class="entete"><?php echo (strlen(utf8_decode($tabChamps['nomAsso']['valeur']))<15?$tabChamps['nomAsso']['valeur']:"Fonctionnement") ?></div>
		<div class="arrow"></div>
		<ul class="menu">
			<li><a href="../membres/benevoles.php">Bénévoles</a></li>
			<li><a href="../membres/plannings.php">Plannings</a></li>
			<li><a href="../membres/presence.php">Présence</a></li>
		<?php if(DROITS=='membre' || DROITS=='bureau'){ ?>
			<li><a href="../gestion/caisse.php">Caisse</a></li>
			<li><a href="../gestion/achats.php">Achats</a></li>
			<li><a href="../gestion/cotisations.php">Cotisations</a></li>
		<?php } ?>
			<li><a href="../membres/lstVotes.php">Votes</a></li>
		</ul>
<?php
if(DROITS=='bureau'){
?>
		<div class="entete">Gestion</div>
		<div class="arrow"></div>
		<ul class="menu">
			<li><a href="../membres/newMembre.php">Nouveau bénévole</a></li>
			<li><a href="../membres/setBenevoles.php">Gestion bénévoles</a></li>
			<li><a href="../gestion/adminCaisse.php">Gestion caisse</a></li>
			<li><a href="../gestion/gestionAchats.php">Gestion achats</a></li>
			<li><a href="../gestion/prixCotisations.php">Prix cotisations</a></li>
			<li><a href="../gestion/consentements.php">Consentements</a></li>
			<?php if($tabChamps['moduleOneDrive']['valeur']=='Oui'){?>
				<li><a href="../gestion/invitsOnedrive.php">Invits OneDrive</a></li>
				<li><a href="../gestion/gestionOnedrive.php">Gestion OneDrive</a></li>
			<?php } ?>
			<li><a href="../gestion/configuration.php">Configuration</a></li>
		</ul>
<?php } ?>
	</div>
<?php } ?>
	<div id="page" <?php if(!$affMenu){echo 'class="big"';} ?>>
		<h2><?php echo TITRE_PAGE;?></h2>
		<?php 
		foreach($pageMessages as $mess){
			if($mess['type']=='err'){
			echo '<div class="messErr">'.$mess['content'].'</div>';
			}elseif($mess['type']=='ok'){
			echo '<div class="messOk">'.$mess['content'].'</div>';
			}elseif($mess['type']=='cash'){
			echo '<div class="messCash">'.$mess['content'].'</div>';
			}
		}
		unset($mess);
		?>

<?php
$footer='

	</div>
</div>
<footer>
	<div class="inner">
		<img src="'.$GLOBALS['SITE']->getLogoAsso().'" alt="Logo" style="max-height:49px; float:left;"/>
		<div style="float:left; margin:8px 0 0 15px">Site inspiré par un design de l\'IT Commitee, mis en oeuvre par Maxime Scher.<br />
		<a class="gris" href="mailto:'.$tabChamps['mailAdmin']['valeur'].'">Assistance technique - Faire remonter un bug</a> - <a class="gris" href="../cgu.php">Conditions générales</a>
		</div>
	</div>
</footer>
</div>
</body>
</html>'
?>