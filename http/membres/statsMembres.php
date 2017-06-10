<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
define('TITRE_PAGE',"Statistiques");

include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/charts/chartFunctions.php');


//recup données
$bd = db_connect();

$statsSexe = db_tableau($bd, "		
						SELECT sexe, count(*) AS nb
						FROM membres_adherents
						GROUP BY sexe
						ORDER BY nb DESC, sexe ASC");
						

$statsDateInscr = db_tableau($bd, "		
						SELECT DATE_FORMAT(dateInscr, '%Y-%m') AS mois, count(*) AS nb
						FROM membres_adherents
						GROUP BY mois
						ORDER BY dateInscr ASC");

$statsPays = db_tableau($bd, "		
						SELECT pays, count(*) AS nb
						FROM membres_adherents
						GROUP BY pays
						ORDER BY nb DESC, pays ASC");
						
$statsResidence = db_tableau($bd, "		
						SELECT SUBSTRING(adresse,1, LOCATE('&#10', adresse)-1) AS adresse, count(*) AS nb
						FROM membres_adherents
						GROUP BY adresse
						ORDER BY nb DESC, adresse ASC");

$statsEtudes = db_tableau($bd, "		
						SELECT etudes, count(*) AS nb
						FROM membres_adherents
						GROUP BY etudes
						ORDER BY nb DESC, etudes ASC");	

$statsRetour = db_tableau($bd, "		
						SELECT dateRetour, count(*) AS nb
						FROM membres_adherents
						GROUP BY dateRetour
						ORDER BY nb DESC, dateRetour ASC");	

$statsNaissance = db_tableau($bd, "		
						SELECT YEAR(dob) AS annee, count(*) AS nb
						FROM membres_adherents
						GROUP BY annee
						ORDER BY nb DESC, annee ASC");

$statsCotis = db_tableau($bd, "		
						SELECT cotisation, count(*) AS nb
						FROM membres_adherents
						GROUP BY cotisation
						ORDER BY nb DESC, cotisation ASC");							
						

db_close($bd);

$mois = array("Jan", "Fev", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sept", "Oct", "Nov", "Dec");

//Recuperation du 1er jour du dernier mois d'aout
if(date('n')<8){
	$annee = date('Y') - 1;
}else{
	$annee = date('Y');
}



$start = (date_create(date($annee.'-'.'08')) < date_create($statsDateInscr[0]['mois'])) ? $annee.'-'.'08' : $statsDateInscr[0]['mois'];



//Construction données date d'inscription
$arrayInscr = array(array('< Août '.$annee, 0));
$tempMois = $start;
$nbInscrits = 0;
$i=0;

while(date_create($tempMois) <= date_create(date("Y-m"))){

	$txtMois = $mois[intval(date("m", strtotime($tempMois)))-1].' '.date("Y", strtotime($tempMois));

	if(!empty($statsDateInscr) && $i < count($statsDateInscr)){


		if(date_create($statsDateInscr[$i]['mois']) < date_create(date($annee.'-'.'08'))){
			$arrayInscr[0][1] += $statsDateInscr[$i]['nb'];
			$nbInscrits += $statsDateInscr[$i]['nb'];
			$i++;
		
		}else{
		
		
			if(date_create($statsDateInscr[$i]['mois']) == date_create($tempMois)){

				array_push($arrayInscr, array($txtMois, $statsDateInscr[$i]['nb']));
				$nbInscrits += $statsDateInscr[$i]['nb'];
				$i++;
			
			}else{
			
				if(date_create($tempMois) > date_create(date($annee.'-'.'08'))){
					array_push($arrayInscr, array($txtMois, 0));
				}
			
			}

		}
		
	
	}else{
		if(date_create($tempMois) > date_create(date($annee.'-'.'08'))){
			array_push($arrayInscr, array($txtMois, 0));
		}
		
	}
	
	$tempMois = date("Y-m", strtotime($tempMois. " +1 month"));
}

//Complétion des mois vides jusqu'à aujourd'hui
/* while(date_create($tempMois) < date_create(date("Y-m"))){

	$txtMois = $mois[intval(date("m", strtotime($tempMois)))-1].' '.date("Y", strtotime($tempMois));
	
	array_push($arrayInscr, array($txtMois, 0));
	
	$tempMois = date("Y-m", strtotime($tempMois. " +1 month"));
} */

//Verification Résidence ou non

	//Récuperation liste des residences
$tabResidences = array();

$fileRes = fopen(($GLOBALS['SITE']->getFolderData()).'/liste_residences.html', 'r');
while (!feof($fileRes)){
	$ligneRes = explode('//',trim(fgets($fileRes)),4);	

	if(count($ligneRes)==4){
		array_push($tabResidences, $ligneRes[0]);
	}
}
fclose($fileRes);

	//Comptage adherents non en résidence
$nbNonResidence = 0;

for($i=0; $i<count($statsResidence); $i++){
	
	if(!in_array($statsResidence[$i]['adresse'], $tabResidences)){
		$nbNonResidence += $statsResidence[$i]['nb'];
		$statsResidence[$i] = array();
	}
}

if($nbNonResidence > 0){
	array_unshift($statsResidence, array('adresse' => 'Autre', 'nb' => $nbNonResidence));
}

//Mise en forme date de retour

for($i=0; $i<count($statsRetour); $i++){
	
	if(!empty($statsRetour[$i]['dateRetour'])){
	
		$statsRetour[$i]['dateRetour'] = $mois[intval(date("m", strtotime($statsRetour[$i]['dateRetour'])))-1].' '.date("Y", strtotime($statsRetour[$i]['dateRetour']));
	
	}else{
		
		$statsRetour[$i]['dateRetour'] = "Inconnue";
	}
}	



//Construction données pour highCharts

$dataInscr = ArraytoChart($arrayInscr);
$dataPays = SQLtoChart($statsPays, 'pays', 'nb');
$dataPaysMap = paysSQLtoDataIso($statsPays, 'pays', 'nb');
$dataSexe = SQLtoChart($statsSexe, 'sexe', 'nb');
$dataResidence = SQLtoChart($statsResidence, 'adresse', 'nb');
$dataEtudes = SQLtoChart($statsEtudes, 'etudes', 'nb');
$dataRetour = SQLtoChart($statsRetour, 'dateRetour', 'nb');
$dataNaissance = SQLtoChart($statsNaissance, 'annee', 'nb');
$dataCotis = SQLtoChart($statsCotis, 'cotisation', 'nb');





include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Statistiques générales</h3>
<div class="blocText">
<ul>
	<li>Nombre d'adhérents : <?php echo $nbInscrits ?></li>
	<li>Nombre de pays représentés : <?php echo count($statsPays) ?></li>
	<li>Nombre d'établissements universitaires représentés : <?php echo count($statsEtudes) ?></li>
</ul>
</div>

<h3>Nombre d'inscriptions</h3>
<div id="graphDateInscr" style="width:100%; height:370px;"></div>

<h3>Pays d'origine</h3>
<div id="graphPays" style="width:100%; height:370px;"></div>

<h3>Répartition des adhérents</h3>
<div id="mapMonde" style="width:auto; height:500px;"></div>

<h3>Sexe</h3>
<div id="graphSexe" style="width:100%; height:370px;"></div>

<h3>Lieu de résidence</h3>
<div id="graphResidence" style="width:100%; height:370px;"></div>

<h3>Etudes suivies</h3>
<div id="graphEtudes" style="width:100%; height:370px;"></div>

<h3>Date de retour envisagée</h3>
<div id="graphRetour" style="width:100%; height:370px;"></div>

<h3>Année de naissance</h3>
<div id="graphNaissance" style="width:100%; height:370px;"></div>

<h3>Types de cotisations</h3>
<div id="graphCotis" style="width:100%; height:370px;"></div>


<script src="/fonctions/charts/standalone-framework.js"></script>


<script src="/fonctions/charts/highcharts.js"></script>
<script src="/fonctions/charts/map.js"></script>
<script src="/fonctions/charts/mapWorld.js"></script>

<link rel="stylesheet" type="text/css" href="/template/style/flags32.css" />


<script>


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
			
			addColumn('graphDateInscr', 'Inscrits', $dataInscr); 
			addPie('graphPays', 'Adhérents', $dataPays); 
			addMap('mapMonde', 'Adhérents', $dataPaysMap);
			addPie('graphSexe', 'Adhérents', $dataSexe);
			addPie('graphResidence', 'Adhérents', $dataResidence);
			addPie('graphEtudes', 'Adhérents', $dataEtudes);
			addPie('graphRetour', 'Adhérents', $dataRetour);
			addPie('graphNaissance', 'Adhérents', $dataNaissance);
			addPie('graphCotis', 'Adhérents', $dataCotis);
		?>
	};

    


</script>
<?php
echo $footer;
?>