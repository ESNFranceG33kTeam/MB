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
						
	$tabOptionsChoisies = db_tableau($bd, "
						SELECT o.idPart, o.idOpt
						FROM activity_options_participants AS o
						JOIN activity_participants AS part ON o.idPart = part.id
						WHERE part.idAct='".$_GET['idAct']."'
						ORDER BY part.id ASC");	
						
						
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
								SELECT ben.prenom, ben.nom, ben.tel, ben.voiture, part.id, part.paid, part.fullPaid, part.listeAttente
								FROM activity_participants AS part
								JOIN membres_benevoles AS ben ON part.idESN = ben.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY ben.prenom ASC, ben.nom ASC");
	$lstInscrAdh = db_tableau($bd, "								
								SELECT adh.prenom, adh.nom, adh.tel, adh.pays, adh.idESN, adh.divers, part.id, part.paid, part.fullPaid, part.listeAttente
								FROM activity_participants AS part
								JOIN membres_adherents AS adh ON part.idAdh = adh.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY adh.prenom ASC, adh.nom ASC");
	db_close($bd);

	$tabInscrESN = "";
	$tabInscrAdh = "";
	$tabInfosAdh = "";
	
	$comptInscritsESN = 0;
	$comptInfosAdh = 0;
	
	
	$actFullFree = ($act['prix']!=0)?false:true;

	if($actFullFree){
		//Test si options payantes
		for($o=0; $o<count($tabOptions); $o++){
			
			if($tabOptions[$o]['prixOpt']>0){
				$actFullFree = false;
				break;
			}
		}
	}
	
	
	//Ajout colonne nb pour chaque options
	for($o=0; $o<count($tabOptions); $o++){
		$tabOptions[$o]['nb'] = 0;
	}
	
	
	$spotsSold = explode('//',$act['spotsSold'],2);						
						
	//reste des places ?
	$isPlacesDispo = ($act['spots']!=0 && (intval($act['spots'])-intval($spotsSold[0])>0))?true:false;
	//reste des places reservees?
	for($i=0; $i<count($lstInscrESN); $i++){
		if($lstInscrESN[$i]['fullPaid']!=-1 && $lstInscrESN[$i]['listeAttente']==0){							
			$comptInscritsESN++;
		}
	}
	$isPlacesDispoESN = ($act['spots']!=0 && intval($act['spotsResESN'])>$comptInscritsESN)?true:false;

	
	if($lstInscrESN!==false && $lstInscrAdh!==false){
		for($i=0; $i<count($lstInscrESN); $i++){

			$tempSommeOptions = 0;
		
			if(count($tabOptions) > 0){
				//Recup des options
				$arrayOpt = array();
				
				for($opti=0; $opti<count($tabOptionsChoisies); $opti++){
					
					if($tabOptionsChoisies[$opti]['idPart'] == $lstInscrESN[$i]['id']){
						
						//on cherche le numero de l'option
						for($o=0; $o<count($tabOptions); $o++){
							if($tabOptions[$o]['id'] == $tabOptionsChoisies[$opti]['idOpt']){
								
								$tempSommeOptions += $tabOptions[$o]['prixOpt'];
								
								array_push($arrayOpt, ($o+1));
								$tabOptions[$o]['nb'] ++;
								break;
							}
						}
					}
				}
				//Mise en forme texte options
				sort($arrayOpt);
				if(count($arrayOpt) == 0){
					
					$textOpt = "Sans options";
					
				}elseif(count($arrayOpt) == 1){
					
					$textOpt = "Option : ".$arrayOpt[0];
					
				}else{
					
					$textOpt = "Options : ";
					
					for($txtO = 0; $txtO < count($arrayOpt); $txtO++){
						
						$textOpt .= $arrayOpt[$txtO]. ", ";
					}
					$textOpt = substr($textOpt, 0 , -2);
				}
				
				
			}else{
				$textOpt = "";
			}
			
			
			if(!$actFullFree){
				if($lstInscrESN[$i]['fullPaid']==0){
					
					if($lstInscrESN[$i]['paid']==0){
						$tdPaye='<td style="font-weight:bold">Non payé</td>';
					}else{
						$tdPaye='<td style="font-weight:bold">'.$lstInscrESN[$i]['paid'].'€</td>';
					}
					
				}else{
					if($lstInscrESN[$i]['paid']==0){
						if(($act['prix'] + $tempSommeOptions) == 0){
							$tdPaye='<td style="width:90px">Gratuit</td>';
						}else{
							$tdPaye='<td style="width:90px">OK (0€)</td>';
						}
					}else{
						$tdPaye='<td>OK ('.$lstInscrESN[$i]['paid'].'€)</td>';
					}
				}
			}else{
				$tdPaye="";
			}

			
			if($lstInscrESN[$i]['listeAttente']==0 && $lstInscrESN[$i]['fullPaid']!=-1){
			
				$tabInscrESN.='<tr><td style="width:10pt"></td><td style="width:10pt"></td><td style="width:10pt"></td>
							<td class="gras">'.$lstInscrESN[$i]['prenom'].' '.$lstInscrESN[$i]['nom'].(!empty($textOpt)?'<br/><div style="float:right; font-size:8pt; line-height:8pt; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
							<td>'.chunk_split($lstInscrESN[$i]['tel'], 2, " ").'</td>'
							.$tdPaye.
							'<td class="center">'.(($lstInscrESN[$i]['voiture'])?"Oui":"Non").'</td>
							</tr>';	
			
			}
			
		}
		
		for($i=$comptInscritsESN; $i<$act['spotsResESN']; $i++){
			$tabInscrESN.='<tr><td colspan=4>Place réservée pour un bénévole ESN.</td><tr>';
		}
		
		for($i=0; $i<count($lstInscrAdh); $i++){
	
			$tempSommeOptions = 0;
			
			
			if(count($tabOptions) > 0){
				//Recup des options
				$arrayOpt = array();
				
				for($opti=0; $opti<count($tabOptionsChoisies); $opti++){
					
					if($tabOptionsChoisies[$opti]['idPart'] == $lstInscrAdh[$i]['id']){
						
						//on cherche le numero de l'option
						for($o=0; $o<count($tabOptions); $o++){
							if($tabOptions[$o]['id'] == $tabOptionsChoisies[$opti]['idOpt']){
								
								$tempSommeOptions += $tabOptions[$o]['prixOpt'];
								
								array_push($arrayOpt, ($o+1));
								$tabOptions[$o]['nb'] ++;
								break;
							}
						}
					}
				}
				//Mise en forme texte options
				sort($arrayOpt);
				if(count($arrayOpt) == 0){
					
					$textOpt = "Sans options";
					
				}elseif(count($arrayOpt) == 1){
					
					$textOpt = "Option : ".$arrayOpt[0];
					
				}else{
					
					$textOpt = "Options : ";
					
					for($txtO = 0; $txtO < count($arrayOpt); $txtO++){
						
						$textOpt .= $arrayOpt[$txtO]. ", ";
					}
					$textOpt = substr($textOpt, 0 , -2);
				}
				
				
			}else{
				$textOpt = "";
			}	
	
	
			if(!$actFullFree){
				if($lstInscrAdh[$i]['fullPaid']==0){
					if($lstInscrAdh[$i]['paid']==0){
						$tdPaye='<td style="font-weight:bold">Non payé</td>';
					}else{
						$tdPaye='<td style="font-weight:bold">'.$lstInscrAdh[$i]['paid'].'€</td>';
					}
				}else{	
				
					if($lstInscrAdh[$i]['paid']==0){
						
						if(($act['prix'] + $tempSommeOptions) == 0){
							$tdPaye='<td style="width:90px">Gratuit</td>';
						}else{
							$tdPaye='<td style="width:90px">OK (0€)</td>';
						}
						
					}else{
						$tdPaye='<td>OK ('.$lstInscrAdh[$i]['paid'].'€)</td>';
					}
				}
			}else{
				$tdPaye="";
			}
			
			if($lstInscrAdh[$i]['listeAttente']==0 && $lstInscrAdh[$i]['fullPaid']!=-1){	
		
				$noteInfos = "";
				
				if(!empty($lstInscrAdh[$i]['divers'])){
					
					$comptInfosAdh ++;
					
					$tabInfosAdh .= '<tr><td class="center gras">'.$comptInfosAdh.'</td>
									<td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].'</td>
									<td>'.$lstInscrAdh[$i]['divers'].'</td>
									</tr>';
				
					$noteInfos = "<sup> (".$comptInfosAdh.")</sup>";
				
				}

				$tabInscrAdh.='<tr><td style="width:10pt"></td><td style="width:10pt"></td><td style="width:10pt"></td>
							<td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].$noteInfos.(!empty($textOpt)?'<br/><div style="float:right; font-size:8pt; line-height:8pt; font-weight:normal" >'.$textOpt.'</div>':'').'</td>
							<td>'.$lstInscrAdh[$i]['tel'].'</td>
							<td style="font-size:8pt">'.$lstInscrAdh[$i]['pays'].'</td>
							'.$tdPaye.'
							<td class="center" style="font-size:8pt"><div >'.$lstInscrAdh[$i]['idESN'].'</td>
							</tr>';
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
	<tr><td style="width:70%"><h1>Liste des inscrits</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2><?php echo $act['nom']?></h2></td><td class="italic" style="width:30%; text-align:right">Inscrits : <?php echo $spotsSold[0]?> (<?php echo $comptInscritsESN?> ESN + <?php echo ($spotsSold[0]-$comptInscritsESN)?> Adhérents)<br /><span style="font-size:8pt">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></span></td></tr>
	</tbody></table>
<div style="width:100%">


	<?php if(!empty($tabInscrESN)){ ?>
		<h3>Bénévoles ESN</h3>
		<table><thead>
		<tr><th colspan=4>Nom</th><th style="width:19%">Téléphone</th><?php echo (!$actFullFree)?'<th style="width:10%">Paiement</th>':'' ?><th style="width:13%">Voiture</th></tr>
		</thead><tbody>
		<?php echo($tabInscrESN); ?>
		</tbody></table>
	<?php } ?>
		<?php if(!empty($tabInscrAdh)){ ?>
		<br />
		<h3>Adhérents</h3>
		<table><thead>
		<tr><th colspan=4>Nom</th><th style="width:19%">Téléphone</th><th style="width:15%">Pays</th><?php echo (!$actFullFree)?'<th style="width:10%">Paiement</th>':'' ?><th style="width:13%">Carte ESN</th></tr>
		</thead><tbody>
		<?php echo($tabInscrAdh); ?>
		</tbody></table>
	<?php } ?>
	
	<br />
	
	<?php if(!empty($tabInfosAdh)){ ?>
		<h3>Informations diverses</h3>
		<table><thead>
		<tr><th style="width:5%"></th><th style="width:30%">Nom</th><th>Informations</th></tr>
		</thead><tbody>
		<?php echo($tabInfosAdh); ?>
		</tbody></table><br/>
	<?php } ?>
	
	
	<?php 
	if(count($tabOptions)>0){
		echo '<h3>Liste des options</h3>';
		echo '<table><thead><tr><th style="width:5%"></th><th>Option</th><th style="width:15%">Nombre</th></tr></thead><tbody>';

		for($opt=0; $opt<count($tabOptions); $opt++){
			
			if($tabOptions[$opt]['prixOpt'] > 0 ){
				$textPrix = " (Supplément : " .$tabOptions[$opt]['prixOpt']. "€)";
			
			}else if($tabOptions[$opt]['prixOpt'] < 0){
				$textPrix = " (Réduction : " .$tabOptions[$opt]['prixOpt']. "€)";
				
			}else{
				$textPrix ="";
			}
			
			echo '<tr><td class="center gras">'.($opt+1).'</td><td><b>'.$tabOptions[$opt]['opt'].'</b><div style="float:right">'.$textPrix.'</td><td class="center">'.$tabOptions[$opt]['nb'].'</td></tr>';
		}
		
		echo '</tbody></table><br/>';
	}
	?>
	
</div>
<?php } ?>
</body>
</html>
