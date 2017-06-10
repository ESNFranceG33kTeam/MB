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
						
	$tabOptions = db_tableau($bd, "
						SELECT id, opt, prixOpt
						FROM activity_options
						WHERE idAct='".$_GET['idAct']."'
						ORDER BY id ASC");	
							
							
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
								SELECT ben.prenom, ben.nom, ben.mail, part.fullPaid, part.listeAttente, o.idOpt
								FROM activity_participants AS part
								JOIN membres_benevoles AS ben ON part.idESN = ben.id
								LEFT JOIN activity_options_participants AS o ON o.idPart = part.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY ben.prenom ASC, ben.nom ASC");
	$lstInscrAdh = db_tableau($bd, "								
								SELECT adh.prenom, adh.nom, adh.email, part.fullPaid, part.listeAttente, o.idOpt
								FROM activity_participants AS part
								JOIN membres_adherents AS adh ON part.idAdh = adh.id
								LEFT JOIN activity_options_participants AS o ON o.idPart = part.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY adh.prenom ASC, adh.nom ASC");
	db_close($bd);

	$tabInscrOptions = "";
	$tempMails = "";
	$tempTabMails = "";

	$spotsSold = explode('//',$act['spotsSold'],2);			



	if($lstInscrESN!==false && $lstInscrAdh!==false && $tabOptions!==false){
		
		for($opt=0; $opt<count($tabOptions); $opt++){
			
			if($tabOptions[$opt]['prixOpt'] > 0 ){
				$textPrix = '<span style="float:right">Supplément : ' .$tabOptions[$opt]['prixOpt']. '€</span>';
				
			}else if($tabOptions[$opt]['prixOpt'] < 0){
				$textPrix = '<span style="float:right">Réduction : ' .$tabOptions[$opt]['prixOpt']. '€</span>';
				
			}else{
				$textPrix ="";
			}
	
			$tabInscrOptions .= '<h3>'.$tabOptions[$opt]['opt'].$textPrix.'</h3>';
	
			for($i=0; $i<count($lstInscrESN); $i++){

				
				if($lstInscrESN[$i]['listeAttente']==0 && $lstInscrESN[$i]['fullPaid']!=-1 && $tabOptions[$opt]['id'] == $lstInscrESN[$i]['idOpt']){
				
					$tempMails.=$lstInscrESN[$i]['mail']. ', ';	
					$tempTabMails.= '<tr><td>'.$lstInscrESN[$i]['mail'].'</td><td>'.$lstInscrESN[$i]['prenom'].'</td><td>'.$lstInscrESN[$i]['nom'].'</td></tr>';
					
				}
			}					

			for($i=0; $i<count($lstInscrAdh); $i++){
				
				if($lstInscrAdh[$i]['listeAttente']==0 && $lstInscrAdh[$i]['fullPaid']!=-1 && $tabOptions[$opt]['id'] == $lstInscrAdh[$i]['idOpt']){	
	
					$tempMails.=$lstInscrAdh[$i]['email']. ', ';
					$tempTabMails.= '<tr><td>'.$lstInscrAdh[$i]['email'].'</td><td>'.$lstInscrAdh[$i]['prenom'].'</td><td>'.$lstInscrAdh[$i]['nom'].'</td></tr>';
				}
			
			}
			


			if(empty($tempMails)){
				
				$tabInscrOptions.= '<div style="text-align:left">Personne n\'a choisi cette option.</div><br/>';
			
			}else{
				
				$tabInscrOptions.= '<div class="lstMails">'.$tempMails.'</div>
									<table class="tabMails" style="display:none"><th>E-mail</th><th style="width:25%">Prénom</th><th style="width:25%">Nom</th>
									'.$tempTabMails.'
									</table><br/>';
			}
			
			$tempMails = "";
			$tempTabMails = "";
			
		}//fin boucle options
		

		
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
	<tr><td style="width:70%"><h1>Liste des e-mails par options choisies</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2><?php echo $act['nom']?></h2></td><td class="italic" style="width:30%; text-align:right">Inscrits : <?php echo $spotsSold[0]?><br /><span style="font-size:8pt">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></span></td></tr>
	</tbody></table>
	<h3>Séparer les e-mails par : <a onclick="changeSeparator(',')">une virgule</a>, <a onclick="changeSeparator(';')">un point-virgule</a>, <a onclick="changeSeparator(' ')">un espace</a>, <a onclick="changeSeparator('<br/>')">un retour à la ligne</a>, <a onclick="changeSeparator('table')">un tableau</a></h3>

<div style="width:100%">

	<?php echo($tabInscrOptions); ?>


</div>
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