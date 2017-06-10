<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//VERIF ID
$acces=false;
if(isset($_GET['idPlanning']) && isset($_GET['idDiv'])){

	$bd = db_connect();
	
	$_GET['idPlanning'] = mysqli_real_escape_string($bd, $_GET['idPlanning']);
	
//Récupération Liste plannings

	$planning = db_ligne ($bd, "SELECT * FROM membres_plannings_liste WHERE id='".$_GET['idPlanning']."'");
	
	db_close($bd);
		if($planning === false){
			die();
		}
	
	
	//Verif droits
	requireDroits($planning['visibility']);
	
	$div = $_GET['idDiv'];
	

//Récupération liste des jours

$thisWeekMonday = ((date("w", time())==1)?date("Y-m-d", strtotime("this monday")):date("Y-m-d", strtotime("last monday")));
$dateMondayLastWeek = date("Y-m-d", strtotime($thisWeekMonday. "-1 week"));

$dateDay = $dateMondayLastWeek;
$idDay = date("z", strtotime($dateMondayLastWeek));

$tabDays = array();
$jours =array("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
$querySupprBDD = "";

for ($i=0; $i<28; $i++){

	array_push($tabDays, array('idDay' => $idDay, 'idWeekDay' => date("N", strtotime($dateDay)), 'dateDay' => $jours[intval(date("w", strtotime($dateDay)))]." ".date("j", strtotime($dateDay))));
	
	$querySupprBDD .= " idJour != ".$idDay;
	if($i != 27){
		$querySupprBDD .= " AND";
	}
	
	$dateDay = date("Y-m-d", strtotime($dateDay. "+1 day"));
	$idDay = date("z", strtotime($dateDay));

}

$divOk = false;


if(!empty($planning)){
	
	$bd = db_connect();
	$intervalles = db_tableau ($bd, "SELECT * FROM membres_plannings_intervalles 
									WHERE idPlanning =".$planning['id']."
									ORDER BY id ASC, jour ASC, debut ASC");

	if($intervalles === false || empty($intervalles)){
		db_close($bd);
		die();
	}
	
	
	//Recup Inscrits

	$inscrits = db_tableau ($bd, "SELECT * FROM membres_plannings_inscrits 
								ORDER BY idIntervalle ASC, idJour ASC, creneau ASC, nom ASC");
	db_close($bd);

	if($inscrits === false){
		die();
	}

	$tabInscrits = createTabInscrits($inscrits);
		
		
	//Verif num div


	if ($planning['type']=="infini"){
		
		if($div > -1 && $div <4){
			$divOK = true;
		}
		
	}else{
		
		if($div > -1 && $div < ceil(count($intervalles)/2)){
			$divOK = true;
		}
	}
	
}	

	

	
if((empty($planning) && $planning!==false) || !$divOK){
		echo "Ce planning n'existe pas";
	}elseif($planning!==false){
		$acces=true;
	}
}else{ // Pas de code fourni
		echo "Ce planning n'existe pas";
}

if($acces){
	
	

$tabPlannings = "";


//Construction tabIntervalles et tabInscrits

$tabIntervalles = array();

for($colInt=0; $colInt<count($intervalles); $colInt++){

	array_push($tabIntervalles, createColIntervalles($intervalles[$colInt]['debut'], $intervalles[$colInt]['fin'], $intervalles[$colInt]['intervalle']));				
}


//Création tableau


$tabPlannings .= '<div id="'.$planning['id'].'-'.$div.'" >';

	if($planning['type']=="infini"){
		

		
		for($jour=0; $jour < count($intervalles); $jour++){
			
			$border = "";
			
			if($jour==0){
				$border = "border-left: 1pt solid black; ";
			}
			if($jour == count($intervalles)-1){
				$border .= "border-right: 1pt solid black; ";
			}
			


			$tabPlannings .= '<div class="divTable" style="margin:0; box-sizing:border-box; float:left; border-top: 1pt solid black; border-bottom: 1pt solid black; '.$border.'">';
			$tabPlannings .= '<table id="table-'.$jour.'" style="margin:0; box-sizing:border-box; border:none ; padding:0 ;"><tbody>
							<th class="center" style="padding-left:1pt; padding-right:1pt;">'.$tabDays[$intervalles[$jour]['jour']+($div*7)-1]['dateDay'].'</th>';

			
			for($li=0; $li<count($tabIntervalles[$jour]); $li++){
				
							
				$attributs = 'style="font-size:10pt; line-height:11pt;"';


				if(isset($tabInscrits[$intervalles[$jour]['id'].'-'.$tabDays[$intervalles[$jour]['jour']+($div*7)]['idDay']][$li])){
					$tabNoms = $tabInscrits[$intervalles[$jour]['id'].'-'.$tabDays[$intervalles[$jour]['jour']+($div*7)]['idDay']][$li];
					
				}else{
					$tabNoms = array();
				}
			
				$noms = formatInscrits ($tabNoms, '');
				
				
				$tabPlannings .= '<tr><td '.$attributs.'>
									<div class="center" style="border-bottom:1pt dotted #333;">'.$tabIntervalles[$jour][$li].'</div>
									<div class="center gras" style="height:45px">'.$noms['noms'].'</div>
								</td></tr>';
			}
			
			$tabPlannings .= '</tbody></table>';
			$tabPlannings .= '<div id="divBoucheTrou-'.$jour.'" style="height:0px; border-style: none solid solid solid; border-width:1pt; border-color:black;" >&nbsp;</div></div>';
			
		}
	

	}elseif($planning['type']=="ponctuel"){
		
		
		$nbDiv = ceil(count($intervalles)/2);

		if(count($intervalles) == ($div*2)+1){
			$taille = 100;
			$nbPeriodesDiv = 1;
			
		}else{
			$taille = 50;
			$nbPeriodesDiv = 2;
		}


			
		for($periode=$div*2; $periode < $nbPeriodesDiv+($div*2); $periode++){
			
			
			$border = "";
			
			if($periode % 2==0 ||  $nbPeriodesDiv == 1){
				$border = "border-left: 1pt solid black; ";
			}
			if($periode % 2 > 0 || $nbPeriodesDiv == 1){
				$border .= "border-right: 1pt solid black; ";
			}			
		
		
			$deb = date_create($intervalles[$periode]['debut']);
			$fin = date_create($intervalles[$periode]['fin']);
			
			if(date_format($fin, 'H:i') == "00:00"){
			
				$fin = date_create();
				date_timestamp_set($fin, strtotime($intervalles[$periode]['fin'])-1440*60);

			}

			if(date_format($deb, 'd/m/Y') == date_format($fin, 'd/m/Y')){
				$entete = "Le " . $jours[intval(date_format($deb, 'w'))] . " ". date_format($deb, 'd/m/Y');
			}else{
				$entete = "Du " . $jours[intval(date_format($deb, 'w'))] . " ". date_format($deb, 'd/m/Y'). " au " . $jours[intval(date_format($fin, 'w'))] . " ". date_format($fin, 'd/m/Y');
			}

			$tabPlannings .= '<div style="width:'.$taille.'%; box-sizing:border-box; float:left; border-top: 1pt solid black; border-bottom: 1pt solid black; '.$border.'">';
			$tabPlannings .= '<table id="table-'.$periode.'"; style="border:none; padding:0"><tbody><th class="center" style="padding-left:1pt; padding-right:1pt">'.$entete.'</th>';

			
			for($li=0; $li<count($tabIntervalles[$periode]); $li++){
				
				

				$attributs = 'style="font-size:10pt; line-height:11pt;"';


				if(isset($tabInscrits[$intervalles[$periode]['id']][$li])){
					$tabNoms = $tabInscrits[$intervalles[$periode]['id']][$li];
					
				}else{
					$tabNoms = array();
				}
			
				$noms = formatInscrits ($tabNoms, '');
				
				
				$tabPlannings .= '<tr><td '.$attributs.'>
									<div class="center" style="border-bottom:1pt dotted #333; margin-bottom:4px;">'.$tabIntervalles[$periode][$li].'</div>
									<div class="center" style="height:45px">'.$noms['noms'].'</div>
								</td></tr>';
			}
			
		
			$tabPlannings .= '</tbody></table>';
			$tabPlannings .= '<div id="divBoucheTrou-'.$periode.'" style="height:0px; border-style: none solid solid solid; border-width:1pt; border-color:black;" >&nbsp;</div></div>';
		
		}
		

	}

$tabPlannings .= '</div>';
	
	
	
}//FIN VERIF ID Planning

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
	<tr><td style="width:70%"><h1>Planning</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2><?php echo $planning['nom']?></h2></td><td class="italic" style="width:30%; text-align:right"><span style="font-size:8pt">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></span></td></tr>
	</tbody></table>

	<?php
		if(!empty($tabPlannings)){
			echo($tabPlannings);
		} 
	?>



<?php } ?>
<script type="text/javascript">

idPlanning = <?php echo $_GET['idPlanning']; ?>;
idDiv = <?php echo $div; ?>;
//MAJ width div table


tagsDIV = document.getElementsByClassName("divTable");
for(i=0; i<tagsDIV.length; i++){
	tagsDIV[i].style.width = Math.floor(tagsDIV[i].parentNode.offsetWidth/<?php echo count($intervalles); ?>) + "px";
}



// Maj de la longueur des span plannings 

tagsSPAN = document.getElementsByClassName("spanNom");
for(i=0; i<tagsSPAN.length; i++){
	tagsSPAN[i].style.width = tagsSPAN[i].parentNode.offsetWidth + "px";
}


var maxHeight = 0;
var divChild = document.getElementById(idPlanning+"-"+idDiv).childNodes;

for(var i = 0; i < divChild.length; i++){
	
	var innerDiv = divChild[i].childNodes;

	for(var j = 0; j < innerDiv.length; j++){

		if(innerDiv[j].id.indexOf("table") != -1){
			
			if(innerDiv[j].offsetHeight > maxHeight){
				maxHeight = innerDiv[j].offsetHeight;
			}
		} 
	}
}


for(var i = 0; i < divChild.length; i++){
	
	var innerDiv = divChild[i].childNodes;
	
	for(var j = 0; j < innerDiv.length; j++){
		
		if(innerDiv[j].id.indexOf("divBoucheTrou-") != -1){
			
			identifiant = innerDiv[j].id.replace("divBoucheTrou-", "");
			
			if(maxHeight == document.getElementById("table-"+identifiant).offsetHeight){
				innerDiv[j].style.display = "none";
						
			}else{
						
				innerDiv[j].style.height = (maxHeight - document.getElementById("table-"+identifiant).offsetHeight) -1 +"px";
				innerDiv[j].style.display = "";	
			}
		}
	}
}

</script>
</body>
</html>

<?php
function createColIntervalles($debut, $fin, $intervalle){
	
	$tabIntervalles = array();

	
	if(strlen($debut)==5 && strlen($fin)==5){
		
		$hDeb = explode(':',$debut,2);
		$hFin = explode(':',$fin,2);

		$debut = intval($hDeb[0]*60 + intval($hDeb[1]));	
		$fin = intval($hFin[0]*60 + intval($hFin[1]));
		if($fin == 0) {$fin == intval(1440);}
		
		
		for($i = $debut; $i<$fin; $i += $intervalle){
			
			array_push($tabIntervalles, intToTime($i)." à ". intToTime($i+$intervalle));
			
		}
		
		
		
	}elseif(strlen($debut)==16 && strlen($fin)==16){
		
		
		$debut = strtotime($debut);
		$fin = strtotime($fin);
		
		
		for($i = $debut; $i<$fin; $i += $intervalle*60){
			
			$date1 = date_create();
			date_timestamp_set($date1, $i);
			$date2 = date_create();
			date_timestamp_set($date2, $i+$intervalle*60);
			
						
			if(date_format($date1, 'd/m/Y') == date_format($date2, 'd/m/Y') || ($intervalle < 1440 && date_format($date2, 'H:i') == "00:00")){
				
				array_push($tabIntervalles, "Le " . date_format($date1, 'd/m/Y \d\e H:i')." à ". date_format($date2, 'H:i'));
				
			}elseif($intervalle == 1440 && date_format($date1, 'H:i') == "00:00"){	
				
				array_push($tabIntervalles, "Le " . date_format($date1, 'd/m/Y')." Toute la journée");
				
			}else{
				
				array_push($tabIntervalles, "Du " . date_format($date1, 'd/m/Y \à H:i')." au ". date_format($date2, 'd/m/Y \à H:i'));
			}
		}
		
		
		
		
	}else{
		die();
	}
	
	return $tabIntervalles;
	
}


function intToTime($nombre){
	
	if ($nombre==1440){
		return "0h00";
	
	}else{
		
		$h = floor($nombre / 60);
		$min = round(($nombre % 60)*60);
		if ($min < 10){
			return $h."h0".$min;
			
		}else{
			return $h."h".$min;
		}
		
	}

}

function createTabInscrits($inscrits){
	
	$tabInscrits = array();
	$tempIntervalle = -1;
	$tempCreneau = -1;
	
	

	for($i=0; $i<count($inscrits); $i++){

		$intervalle = (empty($inscrits[$i]['idJour']))?$inscrits[$i]['idIntervalle']:$inscrits[$i]['idIntervalle'].'-'.$inscrits[$i]['idJour'];
		
		$nom = $inscrits[$i]['nom'];

		
		if($intervalle == $tempIntervalle){
		
			if($inscrits[$i]['creneau'] == $tempCreneau){
				
				array_push($tabInscrits[$intervalle][intval($inscrits[$i]['creneau'])], $nom);
		
			}else{
				
				$tabInscrits[$intervalle][intval($inscrits[$i]['creneau'])] = array($nom);
				$tempCreneau = $inscrits[$i]['creneau'];
			}
			
		}else{
		
			$tabInscrits[$intervalle] = array();
			$tabInscrits[$intervalle][intval($inscrits[$i]['creneau'])] = array($nom);
		
			$tempIntervalle = $intervalle;
			$tempCreneau = $inscrits[$i]['creneau'];
		}
	}
	
	return $tabInscrits;
}

function formatInscrits ($tabNoms, $idSpan){
	
	$noms = array('noms' => "");
	
	for($i=0; $i<count($tabNoms); $i++){
		
		$noms['noms'] .= '<span id="'.$idSpan.'" class="spanNom" style="float:left; overflow:hidden; white-space:nowrap"><b>'.$tabNoms[$i].'</b></span>';	

	}
	
	
	return $noms;
}

?>
