<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//VERIF ID
$acces=false;
if(isset($_GET['id']) && isset($_GET['rep'])){

	$bd = db_connect();
	
	$_GET['id'] = mysqli_real_escape_string($bd, $_GET['id']);
	
	//Récupération Liste plannings

	$feuille = db_ligne ($bd, "SELECT * FROM membres_presence_feuilles WHERE id='".$_GET['id']."'");
	
	db_close($bd);
		if($feuille === false){
			die();
		}
	
	
	//Verif droits
	requireDroits($feuille['visibility']);
	
	$rep = $_GET['rep'];
	$where = "";
	$verifRep = true;
	
	if($rep != "stats"){
		
		$where = "(";
		
		for($char=0; $char < iconv_strlen($rep) ; $char++){
			
			$lettre = substr($rep, $char, 1);
			
			if($lettre != 'O' && $lettre != 'N' && $lettre !='P' && $lettre != 'R' && $lettre != 'U'){
				$verifRep = false ;
			}else{
				
				if($lettre != 'U'){
				
					$where .= " inscr.reponse='".$lettre."' OR";
				
				}else{
					
					$where .= " inscr.reponse IS NULL OR";
				}
			}
		}
		
		$where = substr($where, 0, -3) . ") AND ";
		
	}else{
		
		$verifRep = ($feuille['idGroupe'] == -1);
	}
	
	if(empty($rep)){
		$verifRep = false;
	}
}

	

	
if((empty($feuille) && $feuille!==false)){
	echo "Cette feuille n'existe pas";
	
}elseif($feuille!==false){
	
	if($verifRep){
		$acces=true;
	}else{
		echo "Choix de réponse invalides";
	}
	
}else{ // Pas de code fourni
	echo "Cette feuille n'existe pas";
}

if($acces){
	
	$bits = $feuille['affiche'];
	$types = array();

	if($bits & 1) $types[] = 'probatoire';
	if($bits & 2) $types[] = 'membre';
	if($bits & 4) $types[] = 'bureau';

	$types = array_map(function($t) { return 'drt.general="'.$t.'"'; }, $types);

	$where .= '(' . implode($types, ' OR ') . ')';
	
	
	if($rep != "stats"){
	
		//Recup membres		
		$bd = db_connect();
		$membres = db_tableau($bd, "
								SELECT ben.id, ben.prenom, ben.nom, ben.tel, drt.general , inscr.reponse
								FROM membres_benevoles AS ben
								LEFT JOIN membres_droits AS drt ON ben.id = drt.id
								LEFT JOIN membres_presence_inscrits AS inscr ON (inscr.idMembre = ben.id AND inscr.idFeuille = '".$feuille['id']."')
								WHERE ".$where."
								ORDER BY ben.prenom ASC, ben.nom ASC");
								
		db_close($bd);
		if($membres === false || empty($membres)){
			die("Personne à afficher");
		}
		
		
		$tabFeuille = '<div><table><thead><th colspan=4 >Nom</th><th style="width:75pt">Tel</th><th style="width:130pt">Présent</th></thead>
						<tbody>';

						
		for($m=0; $m<count($membres); $m++){

			if($membres[$m]['reponse'] == 'O'){
				$txtTD = "Oui";
				
			}elseif($membres[$m]['reponse'] == 'N'){
				$txtTD = "Non";

			}elseif($membres[$m]['reponse'] == 'P'){
				$txtTD = "Peut-être";
				
			}elseif($membres[$m]['reponse'] == 'R'){
				$txtTD = "En retard";
			}else{
				$txtTD = "Indéfini";
			}	

			$tabFeuille .= '<tr><td style="width:10pt"></td><td style="width:10pt"></td><td style="width:10pt"></td>
							<td class="gras">'.$membres[$m]['prenom'].' '.$membres[$m]['nom'].'</td>
							<td>'.chunk_split($membres[$m]['tel'], 2, " ").'</td>
							<td style="text-align:center">'.$txtTD.'</td></tr>';
		}

	
	
	}else{ //Stats feuille
	
		$arrayStats = array();
		
		//Recup membres		
		$bd = db_connect();
		$membres = db_tableau($bd, "
								SELECT ben.id, ben.prenom, ben.nom, ben.tel, drt.general , inscr.reponse
								FROM membres_benevoles AS ben
								LEFT JOIN membres_droits AS drt ON ben.id = drt.id
								LEFT JOIN membres_presence_feuilles AS feuille ON feuille.idGroupe = '".$feuille['id']."'
								LEFT JOIN membres_presence_inscrits AS inscr ON (inscr.idMembre = ben.id AND inscr.idFeuille = feuille.id)
								WHERE ".$where."
								ORDER BY ben.prenom ASC, ben.nom ASC");

								
		db_close($bd);
		if($membres === false || empty($membres)){
			die("Personne à afficher");
		}
		
		
		for($m=0; $m<count($membres); $m++){
			
			if(!isset($arrayStats[$membres[$m]['id']]['nom'])){
				$arrayStats[$membres[$m]['id']]['nom'] = $membres[$m]['prenom'].' '.$membres[$m]['nom'];
				$arrayStats[$membres[$m]['id']]['tel'] = chunk_split($membres[$m]['tel'], 2, " ");
			}
			
			if(isset($arrayStats[$membres[$m]['id']][$membres[$m]['reponse']])){
				
				$arrayStats[$membres[$m]['id']][$membres[$m]['reponse']] ++;
				
			}else{
				
				$arrayStats[$membres[$m]['id']][$membres[$m]['reponse']] = 1;
				
			}
		}

		$typeFeuille = $feuille['choixRep'];
		$nomRep = array('O' => 'Oui', 'N' => 'Non', 'P' => 'Peut-être', 'R' => 'En retard');
	
	
		//Création feuille stats
		
		$tabFeuille = '<div><table><thead><th colspan=4>Nom</th><th style="width:15%">Tel</th>';
			
		for($char=0; $char < iconv_strlen($typeFeuille) ; $char++){
			
			$lettre = substr($typeFeuille, $char, 1 );
			$tabFeuille .= '<th style="width:'.(45/iconv_strlen($typeFeuille)).'%">'.$nomRep[$lettre].'</th>';
		}
		
		$tabFeuille .= '</thead><tbody>';
		
		usort($arrayStats,'triStats');
		
		foreach ($arrayStats as $mem){
			
			$tabFeuille .= '<tr><td style="width:10pt"><td style="width:10pt"><td style="width:10pt"><td>'.$mem['nom'].'</td><td>'.$mem['tel'].'</td>';
			
			for($char=0; $char < iconv_strlen($typeFeuille) ; $char++){
			
				$lettre = substr($typeFeuille, $char, 1);
				$tabFeuille .= '<td class="center">'.((isset($mem[$lettre]))?$mem[$lettre]:0).'</td>';
			}
			
			$tabFeuille .= '</tr>';
		}
	}
	
	$tabFeuille .= '</tbody></table></div>';
}//FIN VERIF ID

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
	<tr><td style="width:70%"><h1>Feuille de présence</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2><?php echo $feuille['nom']?></h2></td><td class="italic" style="width:30%; text-align:right"><span style="font-size:8pt">En date du <?php print_r(date("d/m/Y à H:i:s")) ?></span></td></tr></tr>
	</tbody></table>

	<?php
		if(!empty($tabFeuille)){
			echo($tabFeuille);
		} 
		
	?>

</body>
</html>

<?php
}// fin verif acces

function triStats($a, $b){

	$ordre = 'ORPN';
	
	for($char=0; $char < iconv_strlen($ordre) ; $char++){

		$lettre = substr($ordre, $char, 1);

		if(isset($a[$lettre]) && isset($b[$lettre])){
		
			if($a[$lettre] != $b[$lettre]){
		
				if($lettre == 'N'){
					return ($a[$lettre] < $b[$lettre]) ? -1 : 1;
				}else{
					return ($a[$lettre] > $b[$lettre]) ? -1 : 1;
				}
			}
			
		}elseif(isset($a[$lettre])){
			return ($lettre == 'N')?1:-1;
			
		}elseif(isset($b[$lettre])){
			return ($lettre == 'N')?-1:1;
			
		}
	}
	
	if(strnatcmp ($a['nom'] , $b['nom'] ) > 0){
		return 1;
	}else{
		return -1;
	}
}
?>
