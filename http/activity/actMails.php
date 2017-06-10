<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//VERIF ID ACT
$acces=false;
if(isset($_GET['idAct'])){

	$bd = db_connect();
	
	$_GET['idAct'] = mysqli_real_escape_string($bd, $_GET['idAct']);
	
	$act = db_ligne($bd, "SELECT id, nom, dte, tme, infos, spots, spotsSold, spotsResESN, prix, paiementStatut, code
						FROM activity_activities
						WHERE id='".$_GET['idAct']."'");
	db_close($bd);

	if(empty($act) && $act!==false){
		echo "Cette activit&eacute; n'existe pas";
	}elseif($act!==false){
		$acces=true;
	}
}else{ // Pas de code fourni
		echo "Cette activit&eacute; n'existe pas";
}

if($acces){
		
	
	//Récupération Liste Inscrits
	$bd = db_connect();
	
	$lstInscrESN = db_tableau($bd, "		
								SELECT ben.prenom, ben.nom, ben.mail, part.paid, part.fullPaid, part.listeAttente
								FROM activity_participants AS part
								JOIN membres_benevoles AS ben ON part.idESN = ben.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY ben.prenom ASC, ben.nom ASC");
	$lstInscrAdh = db_tableau($bd, "								
								SELECT adh.prenom, adh.nom, adh.email, part.paid, part.fullPaid, part.listeAttente
								FROM activity_participants AS part
								JOIN membres_adherents AS adh ON part.idAdh = adh.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY adh.prenom ASC, adh.nom ASC");
	db_close($bd);

	$lstMailsInscr="";
	$lstMailsAttente="";
	$lstMailsRemb="";
	
	$tabMailsInscr="";
	$tabMailsAttente="";
	$tabMailsRemb="";

	if($lstInscrESN!==false && $lstInscrAdh!==false){
		for($i=0; $i<count($lstInscrESN); $i++){

			
			if($lstInscrESN[$i]['fullPaid']==-1||($lstInscrESN[$i]['listeAttente']==1&&(date_add(date_create($act['dte']), date_interval_create_from_date_string('1 day'))<date_create('now'))&&$lstInscrESN[$i]['paid']>0)){
				$lstMailsRemb.=$lstInscrESN[$i]['mail'].", ";
				$tabMailsRemb.= '<tr><td>'.$tabInscrESN[$i]['mail'].'</td><td>'.$tabInscrESN[$i]['prenom'].'</td><td>'.$tabInscrESN[$i]['nom'].'</td></tr>';
			
			}
			if($lstInscrESN[$i]['listeAttente']==1){
				$lstMailsAttente.=$lstInscrESN[$i]['mail'].", ";
				$tabMailsAttente.= '<tr><td>'.$tabInscrESN[$i]['mail'].'</td><td>'.$tabInscrESN[$i]['prenom'].'</td><td>'.$tabInscrESN[$i]['nom'].'</td></tr>';
			
			}elseif($lstInscrESN[$i]['fullPaid']!=-1){
				$lstMailsInscr.=$lstInscrESN[$i]['mail'].", ";
				$tabMailsInscr.= '<tr><td>'.$tabInscrESN[$i]['mail'].'</td><td>'.$tabInscrESN[$i]['prenom'].'</td><td>'.$tabInscrESN[$i]['nom'].'</td></tr>';
			
			}
		}					
		
		for($i=0; $i<count($lstInscrAdh); $i++){
	
			if($lstInscrAdh[$i]['fullPaid']==-1||($lstInscrAdh[$i]['listeAttente']==1&&(date_add(date_create($act['dte']), date_interval_create_from_date_string('1 day'))<date_create('now'))&&$lstInscrAdh[$i]['paid']>0)){
				$lstMailsRemb.=$lstInscrAdh[$i]['email'].", ";
				$tabMailsRemb.= '<tr><td>'.$lstInscrAdh[$i]['email'].'</td><td>'.$lstInscrAdh[$i]['prenom'].'</td><td>'.$lstInscrAdh[$i]['nom'].'</td></tr>';
			
			}
			if($lstInscrAdh[$i]['listeAttente']==1){
				$lstMailsAttente.=$lstInscrAdh[$i]['email'].", ";
				$tabMailsAttente.= '<tr><td>'.$lstInscrAdh[$i]['email'].'</td><td>'.$lstInscrAdh[$i]['prenom'].'</td><td>'.$lstInscrAdh[$i]['nom'].'</td></tr>';
			
			}elseif($lstInscrAdh[$i]['fullPaid']!=-1){
				$lstMailsInscr.=$lstInscrAdh[$i]['email'].", ";
				$tabMailsInscr.= '<tr><td>'.$lstInscrAdh[$i]['email'].'</td><td>'.$lstInscrAdh[$i]['prenom'].'</td><td>'.$lstInscrAdh[$i]['nom'].'</td></tr>';
			
			}
		}					
	}
	
}//FIN VERIF ID ACTIVITE

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
	<tr><td style="width:70%"><h1>Liste des e-mails</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2><?php echo $act['nom']?></h2></td><td class="italic" style="width:30% ; text-align:right">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></td></tr>
	</tbody></table>
	
	<h3>Séparer les e-mails par : <a onclick="changeSeparator(',')">une virgule</a>, <a onclick="changeSeparator(';')">un point-virgule</a>, <a onclick="changeSeparator(' ')">un espace</a>, <a onclick="changeSeparator('<br/>')">un retour à la ligne</a>, <a onclick="changeSeparator('table')">un tableau</a></h3>

	<?php if(!empty($lstMailsRemb)){ ?>
		<h3>A rembourser</h3>
		<div class="lstMails"><?php echo $lstMailsRemb; ?></div>
		<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
			<?php echo $tabMailsRemb; ?>
		</table>
	<?php } ?>
	<?php if(!empty($lstMailsInscr)){ ?>
		<h3>Inscrits</h3>
		<div class="lstMails"><?php echo $lstMailsInscr; ?></div>
		<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
			<?php echo $tabMailsInscr; ?>
		</table>
	<?php } ?>
	<?php if(!empty($lstMailsAttente)){ ?>
		<h3>Liste d'attente</h3>
		<div class="lstMails"><?php echo $lstMailsAttente; ?></div>
		<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
			<?php echo $tabMailsAttente; ?>
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