<?php
//Code par Maxime Scher - maxime.scher@live.fr - ESN Nancy

include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

define('TITRE_PAGE',"Accueil");
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');

$bd = db_connect();

//Verif périodes probatoires
$alertFinProbatoire = "";
if(checkDroits("bureau")){

	$nbFinProba = db_valeur($bd, "SELECT COUNT(general)
									FROM membres_droits
									WHERE general='probatoire' AND DATEDIFF(finProbatoire,CURDATE())<=0");		

	if($nbFinProba !== false){
		if($nbFinProba == 1){
		
			$alertFinProbatoire = '<div class="blocText gras center">1 membre vient de finir sa période probatoire. 
									<a href="membres/setBenevoles.php">Cliquez ici</a> pour accéder à la page <em>Gestion bénévoles</em>.
								</div>';
		
		}elseif($nbFinProba > 1){
		
			$alertFinProbatoire = '<div class="blocText gras center">'.$nbFinProba.' membres viennent de finir leur période probatoire. 
									<a href="membres/setBenevoles.php">Cliquez ici</a> pour accéder à la page <em>Gestion bénévoles</em>.
								</div>';
		}
	}
}


//Verifs activités passées non enore payées
$alertActNoPaid = "";

$actNoPaid = db_tableau($bd, "SELECT act.id, act.nom
					FROM activity_participants AS part
					JOIN activity_activities AS act ON part.idAct = act.id
					WHERE part.idESN='".ID."' AND part.fullPaid=0 AND part.listeAttente=0 AND DATEDIFF(act.dte,CURDATE())<0
					ORDER BY act.dte ASC");		

if($actNoPaid !== false){
	if(count($actNoPaid) == 1){
	
		$alertActNoPaid = '<div class="blocText gras center">
								Rappel : Vous n\'avez pas encore payé l\'activité 
								<a href="/activity-'.$actNoPaid[0]['id'].'">'.$actNoPaid[0]['nom'].'</a>.
							</div>';
	
	}elseif(count($actNoPaid) > 1){
	
		$alertActNoPaid = '<div class="blocText gras">
								Rappel : Vous n\'avez pas encore payé les activités suivantes :
								<ul style="margin:0 0 0 50px">';
		
		
		for($i=0;$i<count($actNoPaid);$i++){
		
			$alertActNoPaid .= '<li><a href="/activity-'.$actNoPaid[$i]['id'].'">'.$actNoPaid[$i]['nom'].'</a></li>';
		
		}		
								
		$alertActNoPaid .= '</ul></div>';
	}
}



//Recup liste activités

$tabAct = db_tableau($bd, "
						SELECT id, nom, dte, spots, spotsSold, prix
						FROM activity_activities
						ORDER BY dte ASC");


if($tabAct !== false){
	$lstActivity = array();
	$mois = array("Jan", "Fev", "Mar", "Avr", "Mai", "Juin", "Juil", "Aou", "Sep", "Oct", "Nov", "Dec");
	
	for($i=0;$i<count($tabAct);$i++){
	
		if(date_add(date_create($tabAct[$i][2]), date_interval_create_from_date_string('1 day'))>date_create('now')){

			$dte = explode('-',$tabAct[$i][2],3);	
			$dateAct = $dte[2].'<br />'.$mois[intval($dte[1])-1];
			
			$spotsSold = explode('//',$tabAct[$i][4],2);	
			
			if($tabAct[$i][3]==0){
				$places="Illimité - Inscrits : ".$spotsSold[0];
			}elseif($tabAct[$i][3]-intval($spotsSold[0])<=0){
				$places='<font color="yellow">Complet</font> - En attente : '.$spotsSold[1];
			}else{
				$places="Places restantes : ".($tabAct[$i][3]-intval($spotsSold[0]));
			}
			
			if($tabAct[$i][5]==0){
				$prix="Gratuit";
			}else{
				$prix=$tabAct[$i][5]."€";
			}
			
			array_push($lstActivity, array($tabAct[$i]['id'],$dateAct,$tabAct[$i]['nom'],$places,$prix));
		}
	}
}

//Récuperation Anniversaires
$anniversaires = "";


$tabAnnivBen = db_tableau($bd, "
						SELECT prenom, nom, dob
						FROM membres_benevoles
						WHERE MONTH(dob) = MONTH(NOW()) AND DAY(dob) = DAY(NOW())
						ORDER BY dob DESC");

$tabAnnivAdh = db_tableau($bd, "
						SELECT prenom, nom, dob, pays
						FROM membres_adherents
						WHERE MONTH(dob) = MONTH(NOW()) AND DAY(dob) = DAY(NOW())
						ORDER BY dob DESC");					


if($tabAnnivBen !== false && $tabAnnivAdh !== false){
	
	$today = date_create('now');

	for($i=0;$i<count($tabAnnivBen);$i++){
		if(!empty($anniversaires)){
			$anniversaires.= ', ';
		}
		
		$anniversaires.= '<span style="font-weight:bold; white-space:nowrap">'.
						$tabAnnivBen[$i]['prenom'].' '.$tabAnnivBen[$i]['nom'].date_diff(date_create($tabAnnivBen[$i]['dob']),$today)->format(' (%Y ans)').
						'</span>';
	}
	
	for($i=0;$i<count($tabAnnivAdh);$i++){
		if(!empty($anniversaires)){
			$anniversaires.= ', ';
		}
		
		$anniversaires.= '<span style="white-space:nowrap">'.
						$tabAnnivAdh[$i]['prenom'].' '.$tabAnnivAdh[$i]['nom'].' ('.$tabAnnivAdh[$i]['pays'].date_diff(date_create($tabAnnivAdh[$i]['dob']),$today)->format(' - %Y ans)').
						'</span>';
	}


}

//Récupération Produits en vente

$produits = "";


$tabProduits = db_tableau($bd, "
						SELECT id, nom, qte, vendu, prix
						FROM gestion_achats_produits
						ORDER BY nom ASC");


if($tabProduits !== false){

	for($i=0;$i<count($tabProduits);$i++){

		if($tabProduits[$i]['qte']==0 || (intval($tabProduits[$i]['qte']-$tabProduits[$i]['vendu'])>0)){
			
		
			$quantite = (($tabProduits[$i]['qte']==0)?'Quantité illimitée':
				((intval($tabProduits[$i]['qte']-$tabProduits[$i]['vendu'])>1)?'Restants : '.(intval($tabProduits[$i]['qte']-$tabProduits[$i]['vendu'])):'Restant : 1'));

			$prixProduit =($tabProduits[$i]['prix']==0)?'Gratuit':$tabProduits[$i]['prix'].'€';
			
			
			$produits .= '<a class="nonSouligne" href="http://'.$_SERVER['HTTP_HOST'].'/gestion/achats.php?prod='.$tabProduits[$i]['id'].'">
							<div class="blocProduit">
								<div class="gras hidden" style="width:100%; height:18px; text-align:center">'.$tabProduits[$i]['nom'].'</div>
								<div class="italic" style="float:left">'.$quantite.'</div>
								<div class="italic" style="float:right">'.$prixProduit.'</div>
							</div>
						</a>';
		}	
	}
}

//Récupération sondages en cours

$tabVotes = db_tableau($bd, "		
			SELECT question.id, question.question, question.dteFin, question.visibility, question.votants, votes.idQuestion AS voted
			FROM membres_votes_questions AS question
			LEFT JOIN membres_votes_votes AS votes ON question.id = votes.idQuestion AND votes.typeVotant='ESN' AND votes.idVotant='".ID."'
			GROUP BY question.id
			ORDER BY dteFin ASC");		

$votes = "";

if($tabVotes!==false){
		
	for($i=0; $i<count($tabVotes); $i++){	

		if(checkDroits($tabVotes[$i]['visibility'])){
		
			if(date_add(date_create($tabVotes[$i]['dteFin']), date_interval_create_from_date_string('2 days'))>date_create('now')){
			
				if(checkDroits($tabVotes[$i]['votants'])){
				
					if(empty($tabVotes[$i]['voted'])){
					
						if(date_create($tabVotes[$i]['dteFin'])>date_create('now')){
							$statutVote = '<font color="yellow">Vous n\'avez pas encore voté</font>';
							
						}else{
							$statutVote = "Vous n'avez pas voté";
						}
					
					}else{
						$statutVote = "Vous avez voté";
					}
				
				}else{
					$statutVote = "Vous ne pouvez pas voter";
				}
				
				if(date_create($tabVotes[$i]['dteFin'])>date_create('now')){
					$dateFin = "Fin : ".date("d/m/Y à H:i", strtotime($tabVotes[$i]['dteFin']));
				}else{
					$dateFin = "Vote clos";
				}
				
				$votes.='<a class="nonSouligne" href="http://'.$_SERVER['HTTP_HOST'].'/vote-'.$tabVotes[$i]['id'].'">
							<div class="blocVote">
							<table class="invisible" style="width:100%;"><tbody>
								<tr>
									<td style="width:60%; font-weight:bold;"><div class="hidden" style="height:18px">'.$tabVotes[$i]['question'].'</div></td>
									<td style="width:22%; padding-left:2px"><div class="hidden italic" style="text-align:center">'.$statutVote.'</div></td>
									<td style="width:18%; padding-left:2px"><div class="hidden italic" style="text-align:right">'.$dateFin.'</div></td>
								</tr>
							</tbody></table>
							</div>
						</a>';
			}
		}
	}
}


//Récupération Espace OneDrive
if($tabChamps['moduleOneDrive']['valeur']=='Oui'){
	$fileStorage = fopen(($GLOBALS['SITE']->getFolderData()).'/storageSpaceOneDrive.txt', 'r');
	$storageAvailable = (!feof($fileStorage))?(fgets($fileStorage)):""; 
	$storageTotal = (!feof($fileStorage))?(fgets($fileStorage)):"";  
	fclose($fileStorage);
}


db_close($bd);


//Affichage Message d'accueil + informations

$messAccueil = bbCodeToHTML($tabChamps['messAccueil']['valeur']);

if(!empty($messAccueil)){
	echo '<div class="blocText">'.$messAccueil.'</div>';
	
	echo ((!empty($alertFinProbatoire) || !empty($alertActNoPaid))?"<br/>":"");
}

echo $alertFinProbatoire;

echo ((!empty($alertFinProbatoire) && !empty($alertActNoPaid))?"<br/>":"");

echo $alertActNoPaid;
?>


<h3>Inscriptions ouvertes</h3>
<?php 
if(!empty($lstActivity)){
	echo '<div style="padding:0 0 10px 0">';
	foreach ($lstActivity as $act){
		echo '<a class="nonSouligne" href="http://'.$_SERVER['HTTP_HOST'].'/activity-'.$act[0].'">
					<div class="blockNextActivity">
						<div class="dateNextActivity">'.$act[1].'</div>
						<div class="contentNextActivity">
							<div class="gras inline">'.$act[2].'</div><br />
							<span class="italic">'.$act[3].'</span><br />
							<span class="italic">'.$act[4].'</span>
						</div>
					</div>
				</a>';
	}
	echo'</div>';
}else{
	echo "<div>Aucune activité à venir. Le calme avant la tempête...</div>";
}

if(!empty($produits)){

	echo '<h3>Produits en vente</h3>
			<div style="padding:0 0 10px 0">';
	echo $produits;
	echo '</div>';
}

if(!empty($votes)){

	echo '<h3>Votes en cours</h3>
			<div style="padding:0 0 10px 0">';
	echo $votes;
	echo '</div>';
}
?>


<?php if(!empty($anniversaires)){ ?>
<h3>Anniversaires</h3>
<div class="blocText"><?php echo $anniversaires; ?></div>
<br/>
<?php }?>

<?php if($tabChamps['moduleOneDrive']['valeur']=='Oui'){?>
	<h3>OneDrive</h3>
	
	<?php if(!empty($storageAvailable) && !empty($storageTotal)){ ?>
	<div class="blocText" style="margin-bottom:8px">Espace disponible : <?php echo round($storageAvailable/(bcpow(1024,3)),2) ?> Go

	<div class="barreAvancementOut" style="width:230px" >
	<div class="barreAvancementIn" style="width:<?php echo(($storageTotal-$storageAvailable)/$storageTotal)*100 ?>%">
	</div>
	</div>
	<?php echo round(($storageTotal-$storageAvailable)/(bcpow(1024,3)),2) ?>/<?php echo round($storageTotal/(bcpow(1024,3)),2) ?> Go
	</div>
	<?php } ?>
	
	<div class="actuOneDrive"><?php ((DROITS=='bureau')?include(($GLOBALS['SITE']->getFolderData()).'/NewFilesBoard.html'):include(($GLOBALS['SITE']->getFolderData()).'/NewFiles.html')); ?></div>
	
<?php } ?>

<?php
echo $footer;
?>