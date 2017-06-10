<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
	
$listeSuppr = "";
//recup données
$bd = db_connect();

$tabAdh = db_tableau($bd, "
						SELECT id, dateInscr, dateFinInscr, prenom, nom
						FROM membres_adherents");

if($tabAdh !== false && !(empty($tabAdh))){

	for($i=0; $i<count($tabAdh); $i++){
		//Test si carte ESN expirée
		if(date_create($tabAdh[$i]['dateFinInscr']) < date_create('now')){
			
			
			// verif inscrit à une future activité ou doit se faire rembourse
			$activities = db_ligne($bd, "SELECT COUNT(*)
								FROM activity_participants AS part
								JOIN activity_activities AS act ON act.id=part.idAct
								WHERE part.idAdh='".$tabAdh[$i]['id']."' AND DATEDIFF(act.dte,CURDATE())>=0");
			
			$rembours = db_ligne($bd, "SELECT COUNT(*)
							FROM activity_participants
							WHERE idAdh='".$tabAdh[$i]['id']."' AND (fullPaid=-1 OR (listeAttente=1 AND paid>0 ))");
							
			$mustPay = db_ligne($bd, "SELECT COUNT(*)
							FROM activity_participants
							WHERE idAdh='".$_POST['idSup']."' AND fullPaid=0 AND listeAttente=0");
			
			
			if($activities !== false && $rembours !== false && $mustPay !== false && $activities[0]==0 && $rembours[0]==0 && $mustPay[0]==0){
				
				$supUsr = db_exec($bd, "
									DELETE FROM membres_adherents
									WHERE id='".$tabAdh[$i]['id']."'
									LIMIT 1");
									
				$listeSuppr .= "Suppression de ".$tabAdh[$i]['prenom']." ". $tabAdh[$i]['nom']. " inscrit le ".$tabAdh[$i]['dateInscr'].". Cotisation éxiprée le ".$tabAdh[$i]['dateFinInscr']."<br/>";
			
			}elseif($activities[0]>0){
				$listeSuppr .= "Suppression impossible de ".$tabAdh[$i]['prenom']." ". $tabAdh[$i]['nom']. " inscrit le ".$tabAdh[$i]['dateInscr']." car il est inscrit à une activité future.<br/>";
			
			}elseif($rembours[0]>0){
				$listeSuppr .= "Suppression impossible de ".$tabAdh[$i]['prenom']." ". $tabAdh[$i]['nom']. " inscrit le ".$tabAdh[$i]['dateInscr']." car il doit se faire rembourser une ou plusieurs activités.<br/>";
			
			}elseif($mustPay[0]>0){
				$listeSuppr .= "Suppression impossible de ".$tabAdh[$i]['prenom']." ". $tabAdh[$i]['nom']. " inscrit le ".$tabAdh[$i]['dateInscr']." car il doit encore payer une ou plusieurs activités.<br/>";

			}
		}
	}
}

if(empty($listeSuppr)){
	$listeSuppr = "Aucune suppression nécessaire.";
}

db_close($bd);	
?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
</head>
<body>
<?php echo $listeSuppr ?>
</body>
</html>