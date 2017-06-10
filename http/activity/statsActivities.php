<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/charts/chartFunctions.php');


//Recuperation du 1er jour du dernier mois d'aout
if(date('n')<8){
	$annee = date('Y') - 1;
}else{
	$annee = date('Y');
}
$firstOfAugust = date($annee.'-'.'08-01');
$anneeScolaire = $annee."/".($annee+1);
define('TITRE_PAGE',"Statistiques année ".$anneeScolaire);



$order="nbAct DESC, nbPaid DESC";

if (isset($_GET['sort']) && isset($_GET['order'])){

	if($_GET['sort']=="act"){
	
		if($_GET['order']=="dsc"){
		
			$order="nbAct DESC, nbPaid DESC";
		
		}elseif($_GET['order']=="asc"){
		
			$order="nbAct ASC, nbPaid ASC";
		}
	
	}elseif($_GET['sort']=="paid"){
	
		if($_GET['order']=="dsc"){
		
			$order="nbPaid DESC, nbAct DESC";
		
		}elseif($_GET['order']=="asc"){
		
			$order="nbPaid ASC, nbAct ASC";
		}
	}
}





//recup données
$bd = db_connect();

$nbAdh = db_valeur($bd, "SELECT COUNT(*)
						FROM membres_adherents
						WHERE DATEDIFF(dateInscr,'".$firstOfAugust."')>=0");
						
$nbAct = db_valeur($bd, "SELECT COUNT(*)
						FROM activity_activities
						WHERE DATEDIFF(dte,'".$firstOfAugust."')>=0");
						
$nbPart = db_ligne($bd, "SELECT SUM(CASE WHEN idAdh IS NOT NULL THEN 1 ELSE 0 END) AS adh, SUM(CASE WHEN idESN IS NOT NULL THEN 1 ELSE 0 END) as ESN
						FROM activity_participants AS part
						JOIN activity_activities AS act ON act.id = part.idAct
						WHERE DATEDIFF(act.dte,'".$firstOfAugust."')>=0");
						

$statsBestPartAdh = db_tableau($bd, "		
						SELECT adh.nom, adh.prenom, adh.email, adh.pays, COUNT(*) AS nbAct, SUM(part.paid) as nbPaid
						FROM activity_participants AS part
						JOIN activity_activities AS act ON act.id = part.idAct
						JOIN membres_adherents as adh ON part.idAdh = adh.id
						WHERE DATEDIFF(act.dte,'".$firstOfAugust."')>=0
						GROUP BY part.idAdh
						ORDER BY ".$order.", adh.prenom, adh.nom");
						
$statsBestPartESN = db_tableau($bd, "		
						SELECT ben.nom, ben.prenom, ben.mail, COUNT(*) AS nbAct, SUM(part.paid) as nbPaid
						FROM activity_participants AS part
						JOIN activity_activities AS act ON act.id = part.idAct
						JOIN membres_benevoles as ben ON part.idESN = ben.id
						WHERE DATEDIFF(act.dte,'".$firstOfAugust."')>=0
						GROUP BY part.idESN
						ORDER BY ".$order.", ben.prenom, ben.nom");
						
						
$statsPartPays = db_tableau($bd, "		
						SELECT adh.pays, COUNT(*) AS nb
						FROM activity_participants AS part
						JOIN activity_activities AS act ON act.id = part.idAct
						JOIN membres_adherents as adh ON part.idAdh = adh.id
						WHERE DATEDIFF(act.dte,'".$firstOfAugust."')>=0
						GROUP BY adh.pays
						ORDER BY nb DESC, adh.pays ASC");
						
						
$statsPartAdh = db_tableau($bd, "		
						SELECT liste.nbAct, COUNT(*) as nb
						FROM(
							SELECT COUNT(*) AS nbAct
							FROM activity_participants AS part
							JOIN activity_activities AS act ON act.id = part.idAct
							JOIN membres_adherents as adh ON part.idAdh = adh.id
							WHERE DATEDIFF(act.dte,'".$firstOfAugust."')>=0
							GROUP BY part.idAdh
                        ) AS liste
                        GROUP BY liste.nbAct
						ORDER BY liste.nbAct ASC");

db_close($bd);


$tableBestPartAdh = "";

if($statsBestPartAdh !== false){
	for($i=0 ; $i < count($statsBestPartAdh) ; $i++){


		$tableBestPartAdh .= '<tr id="bestAdh'.$i.'" style="display:none">
								<td>'.($i+1).'</td>
								<td>
									<div style="float:left">'.$statsBestPartAdh[$i]['prenom'].' '.$statsBestPartAdh[$i]['nom'].'</div>
									
									<div style="float:right"><a href="mailto:'.$statsBestPartAdh[$i]['email'].'">
									<img class="iconeListe" src="../template/images/email.png" title="'.$statsBestPartAdh[$i]['email'].'">
									</div>
								</td>
								<td>'.$statsBestPartAdh[$i]['pays'].'</td>
								<td class="center">'.$statsBestPartAdh[$i]['nbAct'].'</td>
								<td class="center">'.$statsBestPartAdh[$i]['nbPaid'].'€</td>
							</tr>';
	}
}


$tableBestPartESN = "";

if($statsBestPartESN !== false){
	for($i=0 ; $i < count($statsBestPartESN) ; $i++){


		$tableBestPartESN .= '<tr id="bestESN'.$i.'" style="display:none">
								<td>'.($i+1).'</td>
								<td>
									<div style="float:left">'.$statsBestPartESN[$i]['prenom'].' '.$statsBestPartESN[$i]['nom'].'</div>
									
									<div style="float:right"><a href="mailto:'.$statsBestPartESN[$i]['mail'].'">
									<img class="iconeListe" src="../template/images/email.png" title="'.$statsBestPartESN[$i]['mail'].'">
									</div>
								</td>
								<td class="center">'.$statsBestPartESN[$i]['nbAct'].'</td>
								<td class="center">'.$statsBestPartESN[$i]['nbPaid'].'€</td>
							</tr>';
	}
}

//Ajout adherents n'ayant participé à aucune activité
array_unshift($statsPartAdh, array('nbAct' => 0, 'nb' => ($nbAdh-count($statsBestPartAdh))));


//Construction données pour highCharts


$dataPays = SQLtoChart($statsPartPays, 'pays', 'nb');
$dataPart = SQLtoChart($statsPartAdh, 'nbAct', 'nb');


include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Statistiques générales</h3>
<div class="blocText">
<ul>
	<li>Nombre d'activités depuis août : <?php echo $nbAct ?></li>
	<li>Nombre d'inscriptions toutes activités cofondues : <?php echo $nbPart['adh'] ?> par les adhérents + <?php echo $nbPart['ESN'] ?> par les membres</li>
	<li>Nombre d'adhérents ayant participé à au moins une activité : <?php echo count($statsBestPartAdh) ?> (<?php echo ($nbAdh>0)?round(100*(count($statsBestPartAdh)/$nbAdh),1):0; ?>% des adhérents)</li>
</ul>
</div>

<h3>Adhérents les plus participatifs</h3>
<?php 
if(!empty($tableBestPartAdh)){
	echo '<div>Afficher le top <input type="text" style="width:40px" value=5 onkeyup="nbBestAdh(this.value)" maxlength=4/></div>';
	echo '<table id="tableBestAdh"><tbody>';
	echo '<tr>
			<th style="width:20px">#</th>
			<th>Nom</th>
			<th style="width:130px">Pays</th>
			<th style="width:115px">Nb d\'activités<img class="sortA" onclick="sort(\'act\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'act\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th style="width:145px">Somme dépensée<img class="sortA" onclick="sort(\'paid\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'paid\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			</tr>';
	echo $tableBestPartAdh;
	echo '</tbody></table>';

}else{
	echo '<div>Pas d\'inscrits.</div>';
}
?>

<h3>Membres les plus participatifs</h3>
<?php 
if(!empty($tableBestPartESN)){
	echo '<div>Afficher le top <input type="text" style="width:40px" value=5 onkeyup="nbBestESN(this.value)" maxlength=4/></div>';
	echo '<table id="tableBestESN"><tbody>';
	echo '<tr>
			<th style="width:20px">#</th>
			<th>Nom</th>
			<th style="width:115px">Nb d\'activités<img class="sortA" onclick="sort(\'act\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'act\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th style="width:145px">Somme dépensée<img class="sortA" onclick="sort(\'paid\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'paid\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			</tr>';
	echo $tableBestPartESN;
	echo '</tbody></table>';

}else{
	echo '<div>Pas d\'inscrits.</div>';
}
?>


<h3>Inscriptions par pays</h3>
<div id="graphPays" style="width:100%; height:370px;"></div>


<h3>Nombre d'activités auxquelles les adhérents ont participé</h3>
<div id="graphPart" style="width:100%; height:370px;"></div>

<script src="/fonctions/charts/standalone-framework.js"></script>
<script src="/fonctions/charts/highcharts.js"></script>



<script>
<?php echo ((!empty($tableBestPartAdh)) ?  "nbBestAdh(5);" :  ""); ?>
<?php echo ((!empty($tableBestPartESN)) ?  "nbBestESN(5);" :  ""); ?>



function nbBestAdh(nb){

	for(var i=0; i < document.getElementById('tableBestAdh').rows.length-1 ; i++){
		
		if(i<nb){
			document.getElementById('bestAdh'+i).style.display="";
		
		}else{
			document.getElementById('bestAdh'+i).style.display="none";
		}
	}
}

function nbBestESN(nb){

	for(var i=0; i < document.getElementById('tableBestESN').rows.length-1 ; i++){
		
		if(i<nb){
			document.getElementById('bestESN'+i).style.display="";
		
		}else{
			document.getElementById('bestESN'+i).style.display="none";
		}
	}
}


function sort(colonne, order){
	window.location.href="statsActivities.php?sort="+colonne+"&order="+order;
}


	window.onload = function(){


		// Radialize the colors
		new Highcharts.getOptions().colors = Highcharts.map(Highcharts.getOptions().colors, function (color) {
			return {
				radialGradient: { cx: 0.5, cy: 0.3, r: 0.7 },
				stops: [
					[0, color],
					[1, Highcharts.Color(color).brighten(-0.3).get('rgb')] // darken
				]
			};
		});

		<?php
			addPie('graphPays', "Nombre d\'inscriptions", $dataPays); 
			addPie('graphPart', "Adhérents", $dataPart);
		?>
		
	};

    


</script>
<?php
echo $footer;
?>