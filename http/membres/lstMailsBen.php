<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

$bd = db_connect();
$tabESN = db_tableau($bd, "
					SELECT ben.prenom, ben.nom, ben.mail, drt.general AS droit
					FROM membres_benevoles AS ben
					LEFT JOIN membres_droits AS drt ON ben.id = drt.id
					ORDER BY ben.prenom ASC, ben.nom ASC");					
db_close($bd);


$lstMails="";
$lstMailsBureau="";
$lstMailsMembres="";
$lstMailsProbatoire="";

$tabMails="";
$tabMailsBureau="";
$tabMailsMembres="";
$tabMailsProbatoire="";

$countMails=0;


for($i=0; $i<count($tabESN); $i++){
	$lstMails.=$tabESN[$i]['mail'].", ";
	$tabMails.= '<tr><td>'.$tabESN[$i]['mail'].'</td><td>'.$tabESN[$i]['prenom'].'</td><td>'.$tabESN[$i]['nom'].'</td></tr>';

	$countMails++;
	
	if($tabESN[$i]['droit']=="bureau"){
		$lstMailsBureau.=$tabESN[$i]['mail'].", ";
		$tabMailsBureau.= '<tr><td>'.$tabESN[$i]['mail'].'</td><td>'.$tabESN[$i]['prenom'].'</td><td>'.$tabESN[$i]['nom'].'</td></tr>';
	
	
	}elseif($tabESN[$i]['droit']=="membre"){
		$lstMailsMembres.=$tabESN[$i]['mail'].", ";
		$tabMailsMembres.= '<tr><td>'.$tabESN[$i]['mail'].'</td><td>'.$tabESN[$i]['prenom'].'</td><td>'.$tabESN[$i]['nom'].'</td></tr>';
		
		
	}elseif($tabESN[$i]['droit']=="probatoire"){
		$lstMailsProbatoire.=$tabESN[$i]['mail'].", ";
		$tabMailsProbatoire.= '<tr><td>'.$tabESN[$i]['mail'].'</td><td>'.$tabESN[$i]['prenom'].'</td><td>'.$tabESN[$i]['nom'].'</td></tr>';
		
	}
}

?>

<!DOCTYPE html>
<html>
<head>
<title><?php echo $tabChamps['title']['valeur']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
<link rel="icon" type="image/png" href="/template/images/ESN_star.png" />
<link rel="stylesheet" type="text/css" href="/template/style/printable.css">
</head>
<body>
<table class="invisible" style="width:100%"><tbody>
<tr><td style="width:70%"><h1>Liste des e-mails des bénévoles</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
<tr><td style="width:70%"><h2><?php echo $countMails; ?> mails</h2></td><td class="italic" style="text-align:right; width:30%">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></td></tr>
</tbody></table>
	<h3>Séparer les e-mails par : <a onclick="changeSeparator(',')">une virgule</a>, <a onclick="changeSeparator(';')">un point-virgule</a>, <a onclick="changeSeparator(' ')">un espace</a>, <a onclick="changeSeparator('<br/>')">un retour à la ligne</a>, <a onclick="changeSeparator('table')">un tableau</a></h3>

	
<?php if(!empty($lstMails)){ ?>
	<div class="lstMails"><?php echo $lstMails; ?></div>
	<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
		<?php echo $tabMails; ?>
	</table>
<?php } ?>


<?php if(!empty($lstMailsBureau)){ ?>
	<br/>
	<h2>Membres du bureau</h2>
	<div class="lstMails"><?php echo $lstMailsBureau; ?></div>
	<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
		<?php echo $tabMailsBureau; ?>
	</table>
<?php } ?>

<?php if(!empty($lstMailsMembres)){ ?>
	<br/>
	<h2>Membres actifs</h2>
	<div class="lstMails"><?php echo $lstMailsMembres; ?></div>
	<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
		<?php echo $tabMailsMembres; ?>
	</table>
<?php } ?>


<?php if(!empty($lstMailsProbatoire)){ ?>
	<br/>
	<h2>Membres en probation</h2>
	<div class="lstMails"><?php echo $lstMailsProbatoire; ?></div>
	<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
		<?php echo $tabMailsProbatoire; ?>
	</table>
<?php } ?>

</body>
<script type="text/javascript">

function changeSeparator(sep){
	
	if(sep=='table'){
		
		for(var li=0; li<document.getElementsByClassName('lstMails').length; li++){ 
		
			document.getElementsByClassName('tabMails')[li].style.display = "";
			document.getElementsByClassName('lstMails')[li].style.display = "none";
		}
		
		
	}else{
	
		for(var li=0; li<document.getElementsByClassName('lstMails').length; li++){ 
		
			document.getElementsByClassName('tabMails')[li].style.display = "none";
			document.getElementsByClassName('lstMails')[li].style.display = "";
		
			lstMails = document.getElementsByClassName('lstMails')[li].innerHTML;
			lstMails = lstMails.replace(/, /g,sep);
			lstMails = lstMails.replace(/; /g,sep);
			lstMails = lstMails.replace(/<br\/>/g,sep);
			lstMails = lstMails.replace(/<br>/g,sep);
			lstMails = lstMails.replace(/ /g,sep);
			lstMails = lstMails.replace(/,/g,(sep+" "));
			lstMails = lstMails.replace(/;/g,(sep+" "));
			
			document.getElementsByClassName('lstMails')[li].innerHTML = lstMails;
		
		}
	}
}

</script>
</html>
