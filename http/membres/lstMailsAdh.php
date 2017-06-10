<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//VERIF ID ACT
$acces=false;
if(isset($_GET['type'])&&($_GET['type']=="all"||$_GET['type']=="aout")){

	$bd = db_connect();
	$tabAdh = db_tableau($bd, "
						SELECT prenom, nom, pays, email, dateInscr, cotisation
						FROM membres_adherents
						ORDER BY prenom ASC, nom ASC");					
	db_close($bd);

	if(empty($tabAdh) && $tabAdh!==false){
		echo "Pas d'adh&eacute;rents";
	}elseif($tabAdh!==false){
		$acces=true;
	}
}else{ // Pas de code fourni
		echo "Type invalide.";
}

if($acces){
	//Recuperation du 1er jour du dernier mois d'aout
	if(date('n')<8){
		$annee = date('y') - 1;
	}else{
		$annee = date('y');
	}
	$firstOfAugust = date($annee.'-'.'08-01');

	$lstMails="";
	$tabMails="";
	$countMails=0;
	
	if($_GET['type']=="aout"){
		$titreLst = "Adhérents depuis août";
	}else{
		$titreLst = "Tous les adhérents";
	}
	
	if(isset($_GET['nom'])){
		$titreLst .= '<br/>Prénom ou Nom contenant : "'.$_GET['nom'].'"';
	}
	if(isset($_GET['pays'])){
		$titreLst .= '<br/>Pays contenant : "'.$_GET['pays'].'"';
	}
	if(isset($_GET['cotis'])){
		$titreLst .= '<br/>Type de cotisation : "'.$_GET['cotis'].'"';
	}
	
	
	for($i=0; $i<count($tabAdh); $i++){
	
		if($_GET['type']=="aout" && date_create($tabAdh[$i]['dateInscr']) < date_create($firstOfAugust)){
			continue;
		}

		if(isset($_GET['nom'])){
			if(stripos($tabAdh[$i]['prenom'],$_GET['nom'])===false && stripos($tabAdh[$i]['nom'],$_GET['nom'])===false){
				continue;
			}
		}
		
		if(isset($_GET['pays'])){
			if(stripos($tabAdh[$i]['pays'],$_GET['pays'])===false){
				continue;
			}
		}
		
		
		if(isset($_GET['date']) && is_numeric($_GET['date'])){
		
			$dIns = explode('-',$tabAdh[$i]['dateInscr'],3);			
			$nbMoisDteInscr = date_diff(date_create(date("Y-m-02")), date_create($dIns[0].'-'.$dIns[1].'-02'));
		
			if( ($nbMoisDteInscr->m + 12*$nbMoisDteInscr->y) > $_GET['date']){
				continue;
			}
		}
		
		if(isset($_GET['cotis'])){
			if($tabAdh[$i]['cotisation'] != $_GET['cotis']){
				continue;
			}
		}
	
		$lstMails.=$tabAdh[$i]['email'].", ";
		$tabMails.= '<tr><td>'.$tabAdh[$i]['email'].'</td><td>'.$tabAdh[$i]['prenom'].'</td><td>'.$tabAdh[$i]['nom'].'</td></tr>';
		$countMails++;
	}

	
	
}//FIN VERIF Acces

?>

<?php if($acces){ ?>
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
	<tr><td style="width:70%"><h1>Liste des e-mails des adhérents</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2><?php echo $titreLst; ?></h2></td><td class="italic" style="text-align:right; width:30%">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></td></tr>
	</tbody></table>
	<h3>Séparer les e-mails par : <a onclick="changeSeparator(',')">une virgule</a>, <a onclick="changeSeparator(';')">un point-virgule</a>, <a onclick="changeSeparator(' ')">un espace</a>, <a onclick="changeSeparator('<br/>')">un retour à la ligne</a>, <a onclick="changeSeparator('table')">un tableau</a></h3>

	
	<h3><?php echo $countMails; ?> mails :</h3>
	<?php if(!empty($lstMails)){ ?>
		<div class="lstMails"><?php echo $lstMails; ?></div>
		<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
			<?php echo $tabMails; ?>
		</table>
	<?php } ?>

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
