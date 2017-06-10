<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

//VERIF ID ACT
$acces=false;
if(isset($_GET['idAct']) && isset($_GET['idConsent'])){

	$bd = db_connect();
	
	$_GET['idAct'] = mysqli_real_escape_string($bd, $_GET['idAct']);
	$_GET['idConsent'] = mysqli_real_escape_string($bd, $_GET['idConsent']);
	
	$act = db_ligne($bd, "SELECT id, nom, consent
						FROM activity_activities
						WHERE id='".$_GET['idAct']."'");
						
	$consentements = db_ligne($bd, "		
						SELECT id, obligatoire, texte, titre
						FROM gestion_consentements
						WHERE id='".$_GET['idConsent']."'
						ORDER BY id ASC");						
		
	$consentementsAccepted = db_tableau($bd, "		
						SELECT idAdh, idConsent
						FROM gestion_consentements_accepted
						WHERE idAct='".$_GET['idAct']."' AND idConsent='".$_GET['idConsent']."'
						ORDER BY idConsent ASC");		
						
						
	db_close($bd);

	if(empty($act) && $act!==false){
		echo "Cette activit&eacute; n'existe pas";
	}elseif($act!==false){
		
		
		//Consentements
		if($consentements!==false && !empty($consentements) && !empty($act['consent'])){
			$tabConsentAct = explode('///',$act['consent'],-1);
		
			if(in_array($_GET['idConsent'],$tabConsentAct)){
				$acces=true;
			}else{
				echo "Consentement invalide";
			}
		}
	}
}else{ // Pas de code fourni
	echo "Cette activit&eacute; n'existe pas";
}

if($acces){
	
	//Récupération Liste Inscrits
	$bd = db_connect();
	

	$lstInscrAdh = db_tableau($bd, "								
								SELECT adh.prenom, adh.nom, adh.tel, adh.email, part.fullPaid, part.listeAttente, part.idAdh
								FROM activity_participants AS part
								JOIN membres_adherents AS adh ON part.idAdh = adh.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY adh.prenom ASC, adh.nom ASC");
	db_close($bd);

	$tabInscrAdhO = "";
	$tabInscrAdhN = "";
	$comptAccept = 0;
	$comptNonAccept = 0;
	
	if($lstInscrAdh!==false){
		
		for($i=0; $i<count($lstInscrAdh); $i++){
			
			$accept = false;
			
			
			for($consent=0; $consent<count($consentementsAccepted); $consent++){
				
				if($consentementsAccepted[$consent]['idAdh'] == $lstInscrAdh[$i]['idAdh']){
					$accept = true;
					break;
				}
			}
		
			if($lstInscrAdh[$i]['listeAttente']==0 && $lstInscrAdh[$i]['fullPaid']!=-1 ){

				if($accept){
					$tabInscrAdhO.='<tr><td style="width:10pt"></td><td style="width:10pt"></td><td style="width:10pt"></td>
								<td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].'</td>
								<td>'.$lstInscrAdh[$i]['tel'].'</td>
								<td style="font-size:8pt">'.$lstInscrAdh[$i]['email'].'</td>
								<td class="center">Oui</td>
								</tr>';
					$comptAccept++;
					
				}else{
					$tabInscrAdhN.='<tr><td style="width:10pt"></td><td style="width:10pt"></td><td style="width:10pt"></td>
								<td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].'</td>
								<td>'.$lstInscrAdh[$i]['tel'].'</td>
								<td style="font-size:8pt">'.$lstInscrAdh[$i]['email'].'</td>
								<td class="center">Non</td>
								</tr>';
					$comptNonAccept++;
				}
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
	<tr><td style="width:70%"><h1><?php echo $act['nom']?></h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2><?php echo $consentements['titre']?></h2></td><td class="italic" style="width:30%; text-align:right">Accepté par <?php echo $comptAccept?> adhérent<?php echo (($comptAccept>1)?'s':'') ?> sur <?php echo($comptAccept+$comptNonAccept)?><br /><span style="font-size:8pt">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></span></td></tr>
	</tbody></table>
<div style="width:100%">
<br />
<div style="text-align:left"><?php echo bbCodeToHTML($consentements['texte']); ?></div>



<?php if(!(empty($tabInscrAdhO) && empty($tabInscrAdhN))){ ?>
	<br />
	<h3>Adhérents</h3>
	<table><thead>
	<tr><th colspan=4>Nom</th><th style="width:19%">Téléphone</th><th style="width:28%">E-mail</th><th style="width:10%">Accepté</th></tr>
	</thead><tbody>
	<?php echo($tabInscrAdhO.$tabInscrAdhN); ?>
	</tbody></table>
<?php } ?>
	

	
	
</div>
<?php } ?>
</body>
</html>
