<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
requireDroits("bureau");

//VERIF ID
$acces=false;
if(isset($_GET['id'])){

	$bd = db_connect();
	
	$_GET['id'] = mysqli_real_escape_string($bd, $_GET['id']);
	
	$infosPeriode = db_ligne($bd, "		
				SELECT dteStart, dteEnd, bilan, ecartCaisse, reliquatPrec, depot
				FROM gestion_caisse_periodes
				WHERE id=".$_GET['id']."");	
	
	$logPeriode = db_tableau($bd, "		
				SELECT dte, descr, somme, recu, addBy
				FROM gestion_caisse_log
				WHERE idPeriode=".$_GET['id']."
				ORDER BY dte ASC");
				
	db_close($bd);

	if(empty($infosPeriode) && $infosPeriode!==false && $logPeriode!==false){
		echo "Cette p&eacute;riode n'existe pas.";
	}elseif(empty($infosPeriode['dteEnd'])){
		echo "Cette p&eacute;riode n'est pas achev&eacute;e.";
	}else{
		$acces=true;
	}
}else{ // Pas de code fourni
		echo "Cette p&eacute;riode n'existe pas.";
}

if($acces){
		
	$dteTmeStart = explode(' ',$infosPeriode['dteStart'],2);
	$dteStart = explode('-',$dteTmeStart[0],3);
	
	$dteTmeEnd = explode(' ',$infosPeriode['dteEnd'],2);
	$dteEnd = explode('-',$dteTmeEnd[0],3);
	
	$periode="du ".$dteStart[2].'/'.$dteStart[1].'/'.$dteStart[0]." au ".$dteEnd[2].'/'.$dteEnd[1].'/'.$dteEnd[0];
	
		
	$tabLogPeriode = "";

	if(!empty($logPeriode)){
		for($i=0; $i<count($logPeriode); $i++){
			$dteTme = explode(' ',$logPeriode[$i]['dte'],2);
			$dte = explode('-',$dteTme[0],3);
			
			$tabLogPeriode.='<tr><td style="font-size:0.7em">'.$dte[2].'/'.$dte[1].'/'.$dte[0].'  '.$dteTme[1].'</td>
					<td style="font-size:0.8em">'.$logPeriode[$i]['descr'].'</td>
					<td style="font-size:0.9em">'.$logPeriode[$i]['somme'].'€'.((!empty($logPeriode[$i]['recu'])&&$logPeriode[$i]['recu']!=0)?'<span style="float:right;font-size:0.7em;width:55px;text-align:right">Reçu n°'.$logPeriode[$i]['recu'].'</span>':'').'</td>
					<td style="font-size:0.8em">'.$logPeriode[$i]['addBy'].'</td>';
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
	<tr><td style="width:70%"><h1>Relevé caisse</h1></td><td style="width:30%"><img style="max-width:200px; max-height:100px; float:right" src="<?php echo $GLOBALS['SITE']->getLogoAsso() ?>"/></td></tr>
	<tr><td style="width:70%"><h2>Période <?php echo $periode; ?></h2></td>
	<td class="italic" style="text-align:right; width:30%">
	Reliquat période précédente : <?php echo $infosPeriode['reliquatPrec']; ?>€<br />
	Bilan sur la période : <?php echo $infosPeriode['bilan'];?>€<br />
	Dépôt en banque : <?php echo $infosPeriode['depot'];?>€<br />
	</td></tr>
	</tbody></table><br/>
<div style="width:100%">
	<?php if(!empty($tabLogPeriode)){ ?>
		<table><thead>
		<tr><th style="width:100px">Date</th><th>Description</th><th style="width:79px">Somme</th><th style="width:125px">Effectué par</th></tr>
		</thead><tbody>
		<?php echo($tabLogPeriode); ?>
		</tbody></table>
	<?php } ?>
	</div>
<?php } ?>
</body>
</html>