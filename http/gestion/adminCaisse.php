<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Gestion de la caisse");


$sort="dte ";
$order="DESC";

if (isset($_GET['sort']) && isset($_GET['order'])){

	switch($_GET['order']){
		case "asc" : $order="ASC"; break;	
		case "dsc" : $order="DESC"; break;	
	}
	if(!(empty($order))){
	
		switch($_GET['sort']){
			case "dte" : $sort="dte "; break;
			case "descr" : $sort="log.descr "; break;
			case "somme" : $sort="log.somme "; break;
			case "addby" : $sort="log.addBy "; break;
			default : $order = "DESC"; $sort = "dte ";
		}
	}
}




//Dépot + nouvelle période
if(isset($_POST['montant'])){

	if(empty($_POST['montant'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Montant du dépôt</em>.'));
	}elseif(!is_numeric($_POST['montant'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Montant du dépôt</em> n\'est pas valide.'));
	}elseif($_POST['montant']<=0){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Montant du dépôt</em> n\'est pas valide.'));
	}elseif (mb_strlen($_POST['montant'])>7){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Montant du dépôt</em> ne doit pas dépasser 7 caractères.'));
	}

	if(!isset($_POST['isMontantReel'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez indiquer le montant réel restant dans la caisse.'));
	}elseif($_POST['isMontantReel']=="thIsNOk"){
	
		if(empty($_POST['montantReel'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Montant réél</em>.'));
		}elseif(!is_numeric($_POST['montantReel'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Montant réél</em> n\'est pas valide.'));
		}elseif($_POST['montantReel']<=0){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Montant réél</em> n\'est pas valide.'));
		}elseif (mb_strlen($_POST['montantReel'])>7){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Montant réél</em> ne doit pas dépasser 7 caractères.'));
		}
	}
	
	if(!isset($_POST['montantTheorique'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Montant théorique indisponible.'));
	}elseif(!is_numeric($_POST['montantTheorique'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Montant théorique non valide.'));
	}
	
	if(!isset($_POST['lastReliquat'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Dernier reliquat indisponible.'));
	}elseif(!is_numeric($_POST['lastReliquat'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Dernier reliquat non valide.'));
	}
	
	
	if(empty($pageMessages)){
		$ecart = ($_POST['isMontantReel']=="thIsOk")?0:(($_POST['montant']+$_POST['montantReel'])-($_POST['montantTheorique']));
	
		if ($ecart !=0){
			$descr = "CORRECTION DE L'ECART AVEC LE MONTANT REEL DANS LA CAISSE";
			addCaisse($descr, $ecart, 0, "none", -1);
		}
		addCaisse("DEPOT A LA BANQUE", $_POST['montant'], 0, "none", "NULL");
	
		//Nouvelle période
		
		$bd = db_connect();
		
		$finPeriode = db_exec($bd, "
					UPDATE gestion_caisse_periodes
					SET dteEnd=NOW(), bilan='".($_POST['montantTheorique']+$ecart-($_POST['lastReliquat']))."', ecartCaisse='".$ecart."', depot='".$_POST['montant']."'
					WHERE dteEnd IS NULL");
		
		if($finPeriode!==false){
		
			$addPeriode = db_exec($bd, "
						INSERT INTO gestion_caisse_periodes(dteStart, reliquatPrec)
						VALUES(NOW(),'".($_POST['montantTheorique']+$ecart-($_POST['montant']))."')");
			
			if($addPeriode!==false){
				array_push($pageMessages, array('type'=>'ok', 'content'=>'Le dépôt a bien été validé. Reliquat de la période : '.($_POST['montantTheorique']+$ecart-($_POST['montant'])).'€'));
			}			
		}		
		db_close($bd);
	}


}//fin nouvelle période


if(isset($_POST['idSupLog'])){

	$bd = db_connect();
	$supLog = db_exec($bd, "
						DELETE FROM gestion_caisse_log
						WHERE id='".$_POST['idSupLog']."'
						LIMIT 1");
	
	if($supLog){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'L\'opération a bien été supprimée.'));
	}
	db_close($bd);
}//fin if suppression log



if(isset($_POST['idSupPeriode'])){

	$bd = db_connect();
	$supLog = db_exec($bd, "
						DELETE FROM gestion_caisse_periodes
						WHERE id='".$_POST['idSupPeriode']."'
						LIMIT 1");
	
	if($supLog){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'La période a bien été supprimée.'));
	}
	db_close($bd);
}//fin if suppression periode


//Ajour ref

if(isset($_POST['nameAddRef'])){

	$bd = db_connect();
	
	$_POST['nameAddRef'] = mysqli_real_escape_string($bd, $_POST['nameAddRef']);
					
	$addRef = db_exec($bd, "
				INSERT INTO gestion_caisse_ref(reference, general)
				VALUES('".$_POST['nameAddRef']."',0)");
	
	db_close($bd);
	
	if($addRef){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'La référence a bien été ajoutée.'));
	}

}


//Suppression ref
if(isset($_POST['idSupRef'])){

	$bd = db_connect();
	
	$verifGeneral = db_valeur($bd, "		
				SELECT general
				FROM gestion_caisse_ref
				WHERE id='".$_POST['idSupRef']."'");
	
	if($verifGeneral == 0){
	
		$supRef = db_exec($bd, "
							DELETE FROM gestion_caisse_ref
							WHERE id='".$_POST['idSupRef']."'
							LIMIT 1");
		
		if($supRef){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La référence a bien été supprimée.'));
		}
		
	}else{
		array_push($pageMessages, array('type'=>'err', 'content'=>'Cette référence ne peut pas être supprimée.'));
	
	}
	db_close($bd);
}





//Récupération des données
$bd = db_connect();

	//Liste des réfernces
	
$ref = db_tableau($bd, "		
				SELECT id, reference, general
				FROM gestion_caisse_ref
				ORDER BY id ASC");
				
$tabRef = "";


if($ref !== false){
	for($i=1; $i<count($ref); $i++){		
		
		if($ref[$i]['general']){
			$tabRef.='<tr><td>'.$ref[$i]['reference'].'</td>
				<td style="width:13px"></td></tr>';
		
		}else{
			$tabRef.='<tr><td>'.$ref[$i]['reference'].'</td>
				<td id="cellSupRef'.$ref[$i]['id'].'" class="suppr" onclick="submSupRef('.$ref[$i]['id'].')"></td></tr>';
		}
	}

	$tabRef .= '<tr><td>'.$ref[0]['reference'].'</td><td style="width:13px"></td></tr>
					<tr><td>Nouvelle référence : 
					<input type="text" id="newRef" style="margin:0; box-sizing:border-box; height:inherit; width:82%"/>
				</td>
				<td id="cellAddRef" class="add" onclick="submAddRef()"></td></tr>';
}			
				
				

	//log période

$infosPeriode = db_ligne($bd, "		
				SELECT id, dteStart, reliquatPrec
				FROM gestion_caisse_periodes
				WHERE dteEnd IS NULL
				ORDER BY id DESC");
			
if(empty($infosPeriode)){		
	$addCaissePeriode = db_exec($bdd, "INSERT INTO gestion_caisse_periodes(dteStart) VALUES(NOW())");
	$infosPeriode = db_ligne($bd, "		
									SELECT id, dteStart, reliquatPrec
									FROM gestion_caisse_periodes
									WHERE dteEnd IS NULL
									ORDER BY id DESC");
}
				
$archivesPeriode = db_tableau($bd, "		
				SELECT id, dteStart, dteEnd, bilan, ecartCaisse, reliquatPrec, depot
				FROM gestion_caisse_periodes
				WHERE dteEnd IS NOT NULL
				ORDER BY id DESC");	
			

if($infosPeriode!==false && !empty($infosPeriode) && $archivesPeriode!==false){	

	$dteTmePeriode = explode(' ',$infosPeriode['dteStart'],2);
	$dtePeriode = explode('-',$dteTmePeriode[0],3);
		
	//archives
	$tabArchivesPeriode = "";
	
	for($i=0; $i<count($archivesPeriode); $i++){
	
		$dteTmeStart = explode(' ',$archivesPeriode[$i]['dteStart'],2);
		$dteStart = explode('-',$dteTmeStart[0],3);
		
		$dteTmeEnd = explode(' ',$archivesPeriode[$i]['dteEnd'],2);
		$dteEnd = explode('-',$dteTmeEnd[0],3);
		
		$periode="Du ".$dteStart[2].'/'.$dteStart[1].'/'.$dteStart[0]." au ".$dteEnd[2].'/'.$dteEnd[1].'/'.$dteEnd[0];
		
		 $tabArchivesPeriode.='<tr><td>'.$periode.'
		<a href="periodePrintableGroup.php?id='.$archivesPeriode[$i]['id'].'" target="_blank">
		<img src="../template/images/list_group.png" title="Liste imprimable par groupe" style="vertical-align:sub; height:24px; margin-left:10px"/></a>
		<a href="periodePrintableList.php?id='.$archivesPeriode[$i]['id'].'" target="_blank">
		<img src="../template/images/list.png" title="Liste imprimable par opération" style="vertical-align:sub; height:24px; margin-left:2px"/></a></td>
				<td>'.$archivesPeriode[$i]['ecartCaisse'].'€</td>
				<td>'.$archivesPeriode[$i]['reliquatPrec'].'€</td>
				<td>'.$archivesPeriode[$i]['bilan'].'€</td>
				<td>'.$archivesPeriode[$i]['depot'].'€</td>
				<td class="suppr" onclick="supprPeriode('.$archivesPeriode[$i]['id'].')"></td></tr>';
	}
	
	
	$logPeriode = db_tableau($bd, "		
				SELECT log.id, log.idRef, log.dte, log.descr, log.somme, log.recu, log.addBy, refAct.nom AS nomAct, ref.reference AS nomRef
				FROM gestion_caisse_log AS log
				LEFT JOIN activity_activities AS refAct ON log.idRef = refAct.id
				LEFT JOIN gestion_caisse_ref AS ref ON log.idRef = ref.id*-1
				WHERE log.idPeriode=".$infosPeriode['id']."
				ORDER BY ".$sort.$order);

				
	$tabLogPeriode = "";
	$tabLogPeriodeByRef = "";
	$tempRef = "";
	$sommePeriode = 0;
	$countRef = 0;
	$countTempRef = 0;
	$iRef = 0;
	$tabOrderRef= array();
	$infosRef= array();
	
	if($logPeriode!==false && !(empty($logPeriode))){	
		
		for($i=0; $i<count($logPeriode); $i++){
			$dteTme = explode(' ',$logPeriode[$i]['dte'],2);
			$dte = explode('-',$dteTme[0],3);
			
			$tabLogPeriode.='<tr><td style="font-size:0.7em">'.$dte[2].'/'.$dte[1].'/'.$dte[0].'  '.$dteTme[1].'</td>
					<td style="font-size:0.8em">'.$logPeriode[$i]['descr'].'</td>
					<td style="font-size:0.9em">'.$logPeriode[$i]['somme'].'€'.((!empty($logPeriode[$i]['recu'])&&$logPeriode[$i]['recu']!=0)?'<span style="float:right;font-size:0.7em;width:55px;text-align:right">Reçu n°'.$logPeriode[$i]['recu'].'</span>':'').'</td>
					<td style="font-size:0.8em">'.$logPeriode[$i]['addBy'].'</td>
					<td class="suppr" onclick="supprLog('.$logPeriode[$i]['id'].')"></td></tr>';
					
			$sommePeriode+=$logPeriode[$i]['somme'];
			
			array_push($tabOrderRef, array($logPeriode[$i]['idRef'], $i));
		}
		usort($tabOrderRef, "sortGroup");
		
		$tempRef = $tabOrderRef[0][0];
		
		if(!empty($logPeriode[$tabOrderRef[0][1]]['nomAct'])){
			$nomRef=$logPeriode[$tabOrderRef[0][1]]['nomAct'];
		
		}elseif(!empty($logPeriode[$tabOrderRef[0][1]]['nomRef'])){
			$nomRef=$logPeriode[$tabOrderRef[0][1]]['nomRef'];
		
		}else{
			$nomRef = "<em>Référence supprimée</em>";
		
		}

			
		array_push($infosRef, array('nom'=> $nomRef, 'nb'=>1, 'somme' => $logPeriode[$tabOrderRef[0][1]]['somme']));
					
		
		for($i=1; $i<count($tabOrderRef); $i++){
		
			if($tabOrderRef[$i][0] == $tempRef){
				$infosRef[$countRef]['nb']++;
				$infosRef[$countRef]['somme'] += $logPeriode[$tabOrderRef[$i][1]]['somme'];
			}else{
				$tempRef = $tabOrderRef[$i][0];
				
				
				if(!empty($logPeriode[$tabOrderRef[$i][1]]['nomAct'])){
					$nomRef=$logPeriode[$tabOrderRef[$i][1]]['nomAct'];
				
				}elseif(!empty($logPeriode[$tabOrderRef[$i][1]]['nomRef'])){
					$nomRef=$logPeriode[$tabOrderRef[$i][1]]['nomRef'];
				
				}else{
					$nomRef = "<em>Référence supprimée</em>";
				
				}
					
					array_push($infosRef, array('nom'=> $nomRef, 'nb'=>1, 'somme' => $logPeriode[$tabOrderRef[$i][1]]['somme']));
					$countRef++;
			}
		}


		$tabLogPeriodeByRef.='<tr><th colspan=2>'.$infosRef[0]['nom'].'</th><th colspan=2>Somme totale : '.$infosRef[0]['somme'].'€</th></tr>';
		
		for($i=0; $i<count($tabOrderRef); $i++){
		
			if($countTempRef >= $infosRef[$iRef]['nb']){
				$countTempRef = 0;
				$iRef++;
				$tabLogPeriodeByRef.='<tr><th colspan=2>'.$infosRef[$iRef]['nom'].'</th><th colspan=2>Somme totale : '.$infosRef[$iRef]['somme'].'€</th></tr>';
			}
			
			
			$dteTme = explode(' ',$logPeriode[$tabOrderRef[$i][1]]['dte'],2);
			$dte = explode('-',$dteTme[0],3);
			
			$tabLogPeriodeByRef.='<tr><td style="font-size:0.7em">'.$dte[2].'/'.$dte[1].'/'.$dte[0].'  '.$dteTme[1].'</td>
					<td style="font-size:0.8em">'.$logPeriode[$tabOrderRef[$i][1]]['descr'].'</td>
					<td style="font-size:0.9em">'.$logPeriode[$tabOrderRef[$i][1]]['somme'].'€'.((!empty($logPeriode[$tabOrderRef[$i][1]]['recu'])&&$logPeriode[$tabOrderRef[$i][1]]['recu']!=0)?'<span style="float:right;font-size:0.7em;width:55px;text-align:right">Reçu n°'.$logPeriode[$tabOrderRef[$i][1]]['recu'].'</span>':'').'</td>
					<td style="font-size:0.8em">'.$logPeriode[$tabOrderRef[$i][1]]['addBy'].'</td>
					<td class="suppr" onclick="supprLog('.$logPeriode[$tabOrderRef[$i][1]]['id'].')"></td></tr>';
					

			$countTempRef++;	
		}
	}
	
	//Récupération paiements en attente

$actNoPaid = db_tableau($bd, "SELECT act.id, act.nom AS nomAct, part.paid, ben.nom AS nomBen, ben.prenom AS prenomBen, ben.mail, adh.nom AS nomAdh, adh.prenom AS prenomAdh, adh.email
					FROM activity_participants AS part
					JOIN activity_activities AS act ON part.idAct = act.id
					LEFT JOIN membres_benevoles AS ben ON part.idESN = ben.id
					LEFT JOIN membres_adherents AS adh ON part.idAdh = adh.id
					WHERE part.fullPaid=0 AND part.listeAttente=0 AND DATEDIFF(act.dte,CURDATE())<0
					ORDER BY act.dte ASC, ben.prenom, adh.prenom");	

$listeActNoPaid = "";					
					
if($actNoPaid !== false && !empty($actNoPaid)){
	
	$tempAct = "init";
	
	for($i=0; $i<count($actNoPaid); $i++){
	
		$nom = (empty($actNoPaid[$i]['nomBen'])) ? $actNoPaid[$i]['prenomAdh']." ".$actNoPaid[$i]['nomAdh'] : $actNoPaid[$i]['prenomBen']." ".$actNoPaid[$i]['nomBen'];
		$email = (empty($actNoPaid[$i]['mail'])) ? '<a href="mailto:'.$actNoPaid[$i]['email'].'">'.$actNoPaid[$i]['email'].'</a>' : '<a href="mailto:'.$actNoPaid[$i]['mail'].'">'.$actNoPaid[$i]['mail'].'</a>';

		
		if($tempAct != $actNoPaid[$i]['id']){
			$tempAct = $actNoPaid[$i]['id'];
		
			$listeActNoPaid .= '<tr><th colspan=3>'.$actNoPaid[$i]['nomAct'].'</th></tr>';
			$listeActNoPaid .= '<tr><td style="width:300px">'.$nom.'</td><td style="width:150px">Déjà payé : '.$actNoPaid[$i]['paid'].'€</td><td>'.$email.'</td></tr>';
			
		
		}else{
		
			$listeActNoPaid .= '<tr><td style="width:300px">'.$nom.'</td><td style="width:150px">Déjà payé : '.$actNoPaid[$i]['paid'].'€</td><td>'.$email.'</td></tr>';
		
		}
	}
}

	
	
}				
db_close($bd);	



//Fonction sort group

function sortGroup($a, $b){

	if ($a[0] == $b[0]) {
        return ($a[1] > $b[1]) ? 1 : -1;
	}
	
	if($a[0] < 0 && $b[0] < 0){
		return ($a[0] > $b[0]) ? 1 : -1;
	
	}else{
		return ($a[0] < $b[0]) ? 1 : -1;
	
	}
}


	
			
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<h3>Réaliser un dépot</h3>

<form method=post action="adminCaisse.php" id="formDepot">
<label for="montant">montant du dépot</label>
<input onkeyup="editMontant()" type="text" class="euro" id="montant" name="montant" style="width:130px" maxlength=7 autocomplete="off"/>

<div id="divDepot" style="display:none">
<div id="montantTheorique" class="smallcaps" style="margin-bottom:12px"></div>

<label for="isMontantReel">montant réel dans la caisse après le dépôt</label>
	<input id="isMontantReelO" type="radio" name="isMontantReel" value="thIsOk" onclick="selectMontantReel()">  
	<label id="montantReelO" class="radio" for="isMontantReelO" onclick="selectMontantReel()"></label>
	<input id="isMontantReelN" type="radio" name="isMontantReel" value="thIsNOk" onclick="selectMontantReel()">
	<label class="radio" for="isMontantReelN" onclick="selectMontantReel()" style="margin-right:3px">Autre : </label>
	<input type="text" id="montantReel" name="montantReel" class="euro" onkeyup="editMontantReel()" style="width:75px" maxlength=7 disabled autocomplete="off"/>
	<span id="ecart" class="smallcaps" style="display:none"></span>
	
	
<input type="hidden" name="montantTheorique" value="<?php echo ($sommePeriode+$infosPeriode['reliquatPrec']); ?>" />
<input type="hidden" name="lastReliquat" value="<?php echo ($infosPeriode['reliquatPrec']); ?>" />

	
<input type="button" onclick="submDepot()" id="submitDepot" value="valider" />
<br/>
</div>
</form>


<div id="calcTxt" class="blocText" style="width:215px">
	<a onclick="affCalc()"><img src="../template/images/calculator.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Afficher la table de calculs</a>
</div>


<div id="calc" style="display:none">

<table>
<tbody>
<tr>
	<th></th><th style="width:110px">0.01€</th><th style="width:110px">0.02€</th><th style="width:110px">0.05€</th><th style="width:110px">0.10€</th><th style="width:110px">0.20€</th>
</tr>
<tr>
	<td>Quantité</td>
	<td><input type="text" id="calcNb001" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb002" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb005" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb010" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb020" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
</tr>
<tr>
	<td>Somme</td>
	<td id="calcTot001">-€</td>
	<td id="calcTot002">-€</td>
	<td id="calcTot005">-€</td>
	<td id="calcTot010">-€</td>
	<td id="calcTot020">-€</td>
</tr>


<tr><th></th><th>0.50€</th><th>1€</th><th>2€</th><th>5€</th><th>10€</th></tr>
<tr>
	<td>Quantité</td>
	<td><input type="text" id="calcNb050" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb1" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb2" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb5" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb10" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
</tr>
<tr>
	<td>Somme</td>
	<td id="calcTot050">-€</td>
	<td id="calcTot1">-€</td>
	<td id="calcTot2">-€</td>
	<td id="calcTot5">-€</td>
	<td id="calcTot10">-€</td>
</tr>


<tr><th></th><th>20€</th><th>50€</th><th>100€</th><th>200€</th><th>500€</th></tr>
<tr>
	<td>Quantité</td>
	<td><input type="text" id="calcNb20" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb50" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb100" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb200" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
	<td><input type="text" id="calcNb500" style="margin:0; width:108px" onkeyup="majCalc()"></input></td>
</tr>

<tr>
	<td>Somme</td>
	<td id="calcTot20">-€</td>
	<td id="calcTot50">-€</td>
	<td id="calcTot100">-€</td>
	<td id="calcTot200">-€</td>
	<td id="calcTot500">-€</td>
</tr>


<tbody>
</table>

</div>

<h3>Récapitulatif</h3>
<div class="blocText">
<ul>
	<li>Période débutée le <?php echo $dtePeriode[2].'/'.$dtePeriode[1].'/'.$dtePeriode[0].' à '.$dteTmePeriode[1]; ?></li>
	<li>Bilan sur la période : <?php echo $sommePeriode; ?>€</li>
	<li>Reliquat de la période précédente : <?php echo $infosPeriode['reliquatPrec']; ?>€</li>
	<li>Montant théorique dans la caisse : <?php echo ($sommePeriode+$infosPeriode['reliquatPrec']); ?>€</li>
</ul>
</div>
<?php
if($logPeriode !== false && !(empty($logPeriode))){

	echo '<br />
			<div id="linkView" class="blocText" style="width:200px">
			<a onclick="changeView()"><img src="../template/images/list_group.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Grouper par références</a>
			</div><br/>';

	echo '<table><tbody>';
	echo '<tr><th style="width:100px">Date<img class="sortA" onclick="sort(\'dte\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'dte\',\'dsc\')" src="../template/images/sortDesc.png"></th>
	<th>Description<img class="sortA" onclick="sort(\'descr\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'descr\',\'dsc\')" src="../template/images/sortDesc.png"></th>
	<th style="width:90px">Somme<img class="sortA" onclick="sort(\'somme\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'somme\',\'dsc\')" src="../template/images/sortDesc.png"></th>
	<th style="width:130px">Effectué par<img class="sortA" onclick="sort(\'addby\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'addby\',\'dsc\')" src="../template/images/sortDesc.png"></th></tr>';
	echo '<tbody id="viewList" class="anim">'.$tabLogPeriode.'</tbody>';
	echo '<tbody id="viewGroup" class="anim" style="display:none; opacity:0">'.$tabLogPeriodeByRef.'</tbody>';
	echo '</tbody></table>';
	echo '<form method=post action="adminCaisse.php" id="formSupLog"><input type="hidden" id="idSupLog" name="idSupLog" /></form>';
}



//Activités non payées

if(!empty($listeActNoPaid)){
	echo '<h3>Paiements en attente</h3>';
	echo '<table>'.$listeActNoPaid.'</table>';
}

?>


<h3>Archives</h3>
<?php
if($archivesPeriode !== false && !(empty($archivesPeriode))){

	echo '<table><tbody>';
	echo '<tr><th>Periode<img class="sortA"</th>
	<th style="width:80px">Ecart</th>
	<th style="width:100px">Reliquat prec.</th>
	<th style="width:100px">Bilan période</th>
	<th style="width:100px">Dépot banque</th></tr>';
	echo $tabArchivesPeriode;
	echo '</tbody></table>';
	echo '<form method=post action="adminCaisse.php" id="formSupPeriode"><input type="hidden" id="idSupPeriode" name="idSupPeriode" /></form>';
}else{
	echo '<div>Pas de données.</div>';
}

?>

<h3>Liste des références des opérations</h3>
<?php
if(!empty($tabRef)){

	echo '<table><tbody>';
	echo '<tr><th>Références</th></tr>';
	echo $tabRef;
	echo '</tbody></table>';
	echo '<form method=post action="adminCaisse.php" id="formSupRef"><input type="hidden" id="idSupRef" name="idSupRef" /></form>';
	echo '<form method=post action="adminCaisse.php" id="formAddRef"><input type="hidden" id="nameAddRef" name="nameAddRef" /></form>';

}
?>

<script type="text/javascript">

function editMontant(){
	if(document.getElementById('montant').value!="" && !isNaN(document.getElementById('montant').value)){
		document.getElementById('montantTheorique').innerHTML="montant théorique dans la caisse : "+(Number(<?php echo ($sommePeriode+$infosPeriode['reliquatPrec']); ?>-document.getElementById('montant').value)).toFixed(2) + "€";
		document.getElementById('montantReelO').innerHTML=(Number(<?php echo ($sommePeriode+$infosPeriode['reliquatPrec']); ?>-document.getElementById('montant').value).toFixed(2)) + "€";
		document.getElementById('divDepot').style.display="";
		editMontantReel()
	}else{
		document.getElementById('divDepot').style.display="none";
	}
}

function selectMontantReel(){
	if(document.getElementById('isMontantReelO').checked==true){
		document.getElementById('montantReel').value="";
		document.getElementById('montantReel').disabled=true;	
	}else{
		document.getElementById('montantReel').disabled=false;
		document.getElementById('montantReel').focus();
	}
}

function editMontantReel(){
	if(document.getElementById('montantReel').value!="" && !isNaN(document.getElementById('montantReel').value)){
		document.getElementById('ecart').innerHTML="écart : "+(Number(document.getElementById('montantReel').value-(<?php echo ($sommePeriode+$infosPeriode['reliquatPrec']); ?>-document.getElementById('montant').value))).toFixed(2) + "€";
		document.getElementById('ecart').style.display="";
	}else{
		document.getElementById('ecart').style.display="none";
	}
}


function affCalc(){
	
	if(document.getElementById('calc').style.display=="none"){
		
		document.getElementById('calc').style.display="";
		document.getElementById('calcTxt').style.width = "400px";
		majCalc();

		
	}else{
		
		document.getElementById('calc').style.display="none";
		document.getElementById('calcTxt').style.width = "215px";
		document.getElementById('calcTxt').innerHTML =
			'<a onclick="affCalc()"><img src="../template/images/calculator.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Afficher la table de calculs</a>';
		
	}	
}


function majCalc(){
	
	var tabID = new Array("001","002","005","010","020","050","1","2","5","10","20","50","100","200","500");
	var tabValeur = new Array(0.01,0.02,0.05,0.10,0.20,0.50,1,2,5,10,20,50,100,200,500);
	var total = 0;
	
	
	for(var i=0; i<15; i++){
		
		if(document.getElementById('calcNb'+tabID[i]).value!="" && !isNaN(document.getElementById('calcNb'+tabID[i]).value)){
			
			document.getElementById('calcTot'+tabID[i]).innerHTML = (Math.round(tabValeur[i] * document.getElementById('calcNb'+tabID[i]).value * 100)/100) + "€";
			total += tabValeur[i] * document.getElementById('calcNb'+tabID[i]).value;
			
			
		}else{
			
			document.getElementById('calcTot'+tabID[i]).innerHTML = "-€";
			
		}
	}
	
	total = (Math.round(total * 100)/100);
	
	document.getElementById('calcTxt').innerHTML =
		'<a onclick="affCalc()"><img src="../template/images/calculator.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Masquer la table de calculs</a> - '+
		'<b>Somme totale : '+total+'€</b>';
}



function changeView(){
	if(document.getElementById('viewGroup').style.opacity==0){
	
		document.getElementById('viewList').style.opacity = 0;
		setTimeout(function(){document.getElementById('viewList').style.display = "none";},150)
		setTimeout(function(){document.getElementById('viewGroup').style.display = "";},150)
		setTimeout(function(){document.getElementById('viewGroup').style.opacity = 1;},250)
		document.getElementById('linkView').innerHTML = '<a onclick="changeView()"><img src="../template/images/list.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Lister les opérations</a>';

	}else{
		document.getElementById('viewGroup').style.opacity = 0;
		setTimeout(function(){document.getElementById('viewGroup').style.display = "none";},150)
		setTimeout(function(){document.getElementById('viewList').style.display = "";},150)
		setTimeout(function(){document.getElementById('viewList').style.opacity = 1;},250)
		document.getElementById('linkView').innerHTML = '<a onclick="changeView()"><img src="../template/images/list_group.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Grouper par références</a>';
	}
}


function sort(colonne, order){
window.location.href="adminCaisse.php?sort="+colonne+"&order="+order;
}

function supprLog(id){
	if(confirm("Voulez-vous vraiment supprimer cette entrée ?")){
		document.getElementById('idSupLog').value = id;
		document.getElementById('formSupLog').submit();
	}
}
function supprPeriode(id){
	if(confirm("Voulez-vous vraiment supprimer la totalité de cette période ?")){
		document.getElementById('idSupPeriode').value = id;
		document.getElementById('formSupPeriode').submit();
	}
}

function submDepot(){

	if(document.getElementById('isMontantReelO').checked==true || document.getElementById('isMontantReelN').checked==true){
		if(Number(document.getElementById('montant').value)>0){
			document.getElementById('submitDepot').disabled=true;
			document.getElementById('submitDepot').value = "Patientez...";
			document.getElementById('submitDepot').onclick="";
			document.getElementById('formDepot').submit();
		}else{
			alert("Le montant du dépôt doit être positif.");
		}
	}else{
		alert("Veuillez indiquer le montant réel restant dans la caisse.");
	}
}

function submAddRef(){

	if(document.getElementById('newRef').value != ""){
		document.getElementById('cellAddRef').onclick="";
		document.getElementById('nameAddRef').value = document.getElementById('newRef').value;
		document.getElementById('formAddRef').submit();
	}else{
		alert("Veuillez donner un nom à la référence.");
	}
}


function submSupRef(id){
	if(confirm("Voulez-vous vraiment supprimer cette référence ?")){
		document.getElementById('cellSupRef'+id).onclick="";
		document.getElementById('idSupRef').value = id;
		document.getElementById('formSupRef').submit();
	}
}
</script> 
<?php
echo $footer;
?>