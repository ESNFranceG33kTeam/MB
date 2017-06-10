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
								SELECT ben.prenom, ben.nom, ben.tel, ben.voiture, part.id, part.paid, part.fullPaid, part.listeAttente, o.idOpt
								FROM activity_participants AS part
								JOIN membres_benevoles AS ben ON part.idESN = ben.id
								LEFT JOIN activity_options_participants AS o ON o.idPart = part.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY ben.prenom ASC, ben.nom ASC");
	$lstInscrAdh = db_tableau($bd, "								
								SELECT adh.prenom, adh.nom, adh.tel, adh.pays, adh.idESN, adh.divers, part.id, part.paid, part.fullPaid, part.listeAttente, o.idOpt
								FROM activity_participants AS part
								JOIN membres_adherents AS adh ON part.idAdh = adh.id
								LEFT JOIN activity_options_participants AS o ON o.idPart = part.id
								WHERE part.idAct='".$_GET['idAct']."'
								ORDER BY adh.prenom ASC, adh.nom ASC");
	db_close($bd);

	$tabInscrOptions = "";
	$tempESN = "";
	$tempAdh = "";
	$arrayInfosAdh = array();
	$tabInfosAdh = "";
	$comptOption = 0;

	$spotsSold = explode('//',$act['spotsSold'],2);			

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

				$tempSommeOptions = 0;
				
				for($opti=0; $opti<count($tabOptionsChoisies); $opti++){
					
					if($tabOptionsChoisies[$opti]['idPart'] == $lstInscrESN[$i]['id']){
						
						//on cherche le numero de l'option
						for($o=0; $o<count($tabOptions); $o++){
							if($tabOptions[$o]['id'] == $tabOptionsChoisies[$opti]['idOpt']){
								$tempSommeOptions += $tabOptions[$o]['prixOpt'];
								break;
							}
						}
					}
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
				
				if($lstInscrESN[$i]['listeAttente']==0 && $lstInscrESN[$i]['fullPaid']!=-1 && $tabOptions[$opt]['id'] == $lstInscrESN[$i]['idOpt']){
				
					$tempESN.='<tr><td style="width:10pt"></td><td style="width:10pt"></td><td style="width:10pt"></td>
								<td class="gras">'.$lstInscrESN[$i]['prenom'].' '.$lstInscrESN[$i]['nom'].'</td>
								<td>'.chunk_split($lstInscrESN[$i]['tel'], 2, " ").'</td>
								'.$tdPaye.'
								<td class="center">'.(($lstInscrESN[$i]['voiture'])?"Oui":"Non").'</td>
								</tr>';	
					
					$comptOption++;
				}
			}					
			
			if(!empty($tempESN)){
				$tabInscrOptions .= '<table><thead>';
				$tabInscrOptions .= '<tr><th colspan=4>Bénévoles</th><th style="width:19%">Téléphone</th>'.((!$actFullFree)?'<th style="width:10%">Paiement</th>':'').'<th style="width:13%">Voiture</th></tr>';
				$tabInscrOptions .= '</thead><tbody>';
				$tabInscrOptions .= $tempESN;
				$tabInscrOptions .= '</tbody></table>';
			}
			
			
			for($i=0; $i<count($lstInscrAdh); $i++){
				
				$tempSommeOptions = 0;
				
				for($opti=0; $opti<count($tabOptionsChoisies); $opti++){
					
					if($tabOptionsChoisies[$opti]['idPart'] == $lstInscrAdh[$i]['id']){
						
						//on cherche le numero de l'option
						for($o=0; $o<count($tabOptions); $o++){
							if($tabOptions[$o]['id'] == $tabOptionsChoisies[$opti]['idOpt']){
								$tempSommeOptions += $tabOptions[$o]['prixOpt'];
								break;
							}
						}
					}
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
				
				if($lstInscrAdh[$i]['listeAttente']==0 && $lstInscrAdh[$i]['fullPaid']!=-1 && $tabOptions[$opt]['id'] == $lstInscrAdh[$i]['idOpt']){	
			
					$noteInfos = "";
					
					if(!empty($lstInscrAdh[$i]['divers'])){
						
						$noteInfos = "";

						//Si y'a des infos, on recherche dans le tableau si elle n'ont pas déjà été mises précédemment
						
							for($info=0; $info<count($arrayInfosAdh); $info++){
								
								if($lstInscrAdh[$i]['id'] == $arrayInfosAdh[$info]['id']){
									
									$noteInfos = "<sup> (".($info+1).")</sup>";
									break;
									
								}
							}
						
						if(empty($noteInfos)){ //Si pas trouvé dans le tableau, on l'ajoute
							
							array_push($arrayInfosAdh, array('id'=> $lstInscrAdh[$i]['id'], 'nom' => ($lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom']), 'infos'=>$lstInscrAdh[$i]['divers']));
							$noteInfos = "<sup> (".count($arrayInfosAdh).")</sup>";
						}		
					}

					$tempAdh.='<tr><td style="width:10pt"></td><td style="width:10pt"></td><td style="width:10pt"></td>
								<td class="gras">'.$lstInscrAdh[$i]['prenom'].' '.$lstInscrAdh[$i]['nom'].$noteInfos.'</td>
								<td>'.$lstInscrAdh[$i]['tel'].'</td>
								<td style="font-size:8pt">'.$lstInscrAdh[$i]['pays'].'</td>
								'.$tdPaye.'
								<td class="center" style="font-size:8pt"><div >'.$lstInscrAdh[$i]['idESN'].'</td>
								</tr>';
								
					$comptOption++;
								
				}
			}
			
			if(!empty($tempAdh)){
				$tabInscrOptions .= (!empty($tempESN))?'<br/>':'';
				$tabInscrOptions .= '<table><thead>';
				$tabInscrOptions .= '<tr><th colspan=4>Adhérents</th><th style="width:19%">Téléphone</th><th style="width:15%">Pays</th>'.((!$actFullFree)?'<th style="width:10%">Paiement</th>':'').'<th style="width:13%">Carte ESN</th></tr>';
				$tabInscrOptions .= '</thead><tbody>';
				$tabInscrOptions .= $tempAdh;
				$tabInscrOptions .= '</tbody></table>';
			}
			

			if(empty($tempESN) && empty($tempAdh)){
				$tabInscrOptions.= '<br/><div style="text-align:left">Personne n\'a choisi cette option.</div><br/>';
			}else{
				if($comptOption == 1){
					$tabInscrOptions.= '<br/><div style="text-align:left">Total: 1 personne</div><br/>';
				}else{
					$tabInscrOptions.= '<br/><div style="text-align:left">Total : '.$comptOption.' personnes</div><br/>';
				}
			}
			
			$tempAdh = "";
			$tempESN = "";
			$comptOption = 0;
			
		}//fin boucle options
		
		//Construction tableau infos adh
		
		for($info=0; $info<count($arrayInfosAdh); $info++){
		
			$tabInfosAdh .= '<tr><td class="center gras">'.($info+1).'</td>
					<td class="gras">'.$arrayInfosAdh[$info]['nom'].'</td>
					<td>'.$arrayInfosAdh[$info]['infos'].'</td>
					</tr>';
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
	<tr><td style="width:70%"><h1>Liste des options choisies</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2><?php echo $act['nom']?></h2></td><td class="italic" style="width:30%; text-align:right">Inscrits : <?php echo $spotsSold[0]?><br /><span style="font-size:8pt">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></span></td></tr>
	</tbody></table>
	<br/>
<div style="width:100%">

	<?php echo($tabInscrOptions); ?>

	<br />
	
	<?php if(!empty($tabInfosAdh)){ ?>
		<h3>Informations diverses</h3>
		<table><thead>
		<tr><th style="width:5%"></th><th style="width:30%">Nom</th><th>Informations</th></tr>
		</thead><tbody>
		<?php echo($tabInfosAdh); ?>
		</tbody></table>
	<?php } ?>
	
	
</div>
<?php } ?>
</body>
</html>
