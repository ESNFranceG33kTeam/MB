<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
define('TITRE_PAGE',"Liste des activités");


$sort="";
$order="";


if (isset($_GET['sort']) && isset($_GET['order'])){

	switch($_GET['order']){
		case "asc" : $order="ASC,"; break;	
		case "dsc" : $order="DESC,"; break;	
	}
	if(!(empty($order))){
	
		switch($_GET['sort']){
			case "paie" : $sort="paiementStatut "; break;

			default : $order = ""; $sort = "";
		}
	}
}

if(isset($_POST['idSup'])){
	//Verif droits
	requireDroits("membre");
	
	$canBeSuppr = false;
	$bd = db_connect();
	$nbInscr = db_valeur($bd, "		
						SELECT COUNT(idAct)
						FROM activity_participants
						WHERE idAct='".$_POST['idSup']."'");
	if($nbInscr!==false){
		if($nbInscr>0){
			if(DROITS=='bureau'){
				$canBeSuppr = true;
			}else{
				$canBeSuppr = false;
				array_push($pageMessages, array('type'=>'err', 'content'=>'Seuls les membres du bureau peuvent supprimer une activité comprenant des inscrits.'));
			}
		}else{
			$canBeSuppr = true;
		}
	}

	if($canBeSuppr){
		$supAct = db_exec($bd, "
						DELETE FROM activity_activities
						WHERE id='".$_POST['idSup']."'
						LIMIT 1");
						
		$supPart = db_exec($bd, "
				DELETE FROM activity_participants
				WHERE idAct='".$_POST['idSup']."'");
				
		
		$supOptions = db_exec($bd, "
				DELETE FROM activity_options
				WHERE idAct='".$_POST['idSup']."'");
						
		
		if($supAct!==false && $supPart!==false && $supOptions!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'L\'activité a bien été supprimée.'));
		}
	}
	db_close($bd);	
}


//recup données
$bd = db_connect();
$tabAct = db_tableau($bd, "
						SELECT id, nom, dte, tme, spots, spotsSold, prix, paiementStatut, code
						FROM activity_activities
						ORDER BY ".$sort.$order." dte DESC");
db_close($bd);


$actFutur="";
$actPast="";

if($tabAct !== false && !(empty($tabAct))){

	for($i=0; $i<count($tabAct); $i++){
	
			if(empty($tabAct[$i][8])){
				$nomAct='<div style="overflow:hidden; width:250px"><a href="http://'.$_SERVER['HTTP_HOST'].'/activity-'.$tabAct[$i][0].'">'.$tabAct[$i][1].'</a></div>';
			}else{
				$nomAct='<div style="overflow:hidden; float:left; width:235px"><a href="http://'.$_SERVER['HTTP_HOST'].'/activity-'.$tabAct[$i][0].'">'.$tabAct[$i][1].'</a></div><div style="float:right"><a href="http://'.$_SERVER['HTTP_HOST'].'/inscrAct-'.$tabAct[$i][8].'"><img class="iconeListe" src="../template/images/world_link.png" title="Lien d\'inscription à partager"></a></div>';
			}
	
			$dte = explode('-',$tabAct[$i][2],3);	
			$dateAct = $dte[2].'/'.$dte[1].'/'.$dte[0];
			if(!empty($tabAct[$i][3])){
				$dateAct .= " à ".$tabAct[$i][3];
			}
			
			
			$spots = explode('//',$tabAct[$i][5],2); //[0]:inscrit pour activité [1]:en liste d'attente
			$spots[0]=intval($spots[0]);
			$spots[1]=intval($spots[1]);
			
			if($tabAct[$i][4]==0){
				$places="Places illimitées<br />Inscrits : ".$spots[0];
			}elseif(($tabAct[$i][4]-$spots[0])<=0 && $spots[1]<=0){
				$places="Places : ".$tabAct[$i][4]."<br />Complet";
			}elseif(($tabAct[$i][4]-$spots[0])<=0 && $spots[1]>0){
				$places="Complet<br />En attente : ".$spots[1];
			}elseif(($tabAct[$i][4]-$spots[0])>0 && $spots[1]>0){
				$places="Restantes : ".($tabAct[$i][4]-$spots[0])."<br />En attente : ".$spots[1];
			}else{
				$places="Places : ".$tabAct[$i][4]."<br />Restantes : ".($tabAct[$i][4]-$spots[0]);
			}
			
			if($tabAct[$i][6]==0){
				$prix="Gratuit";
			}else{
				$prix=$tabAct[$i][6]."€";
			}

			$paie = explode('//',$tabAct[$i][7],2);		
			$remb = explode('/',$paie[1],2);
			$nbRemb = (date_add(date_create($tabAct[$i][2]), date_interval_create_from_date_string('1 day'))>date_create('now'))?intval($remb[0]):(intval($remb[0])+intval($remb[1]));
			
			if($paie[0]==0 && $nbRemb==0){
				$paieStat="A jour";
				$colorPaieStat = "";
			}else{
				$colorPaieStat = 'style="font-weight:bold;color: orangered;"';
				if($paie[0]!=0 && $nbRemb==0){
					$paieStat="Non payé : ".$paie[0];
				}elseif($paie[0]==0 && $nbRemb!=0){
					$paieStat="A rembourser : ".$nbRemb;
				}elseif($paie[0]!=0 && $nbRemb!=0){
					$paieStat="Non payé : ".$paie[0]."<br />A rembourser : ".$nbRemb;
				}	
			}
	
			$act= '<tr><td  class="gras">'.$nomAct.'</td>
				<td>'.$dateAct.'</td>
				<td>'.$places.'</td>
				<td>'.$prix.'</td>
				<td '.$colorPaieStat.'>'.$paieStat.'</td>
				<td class="edit" onclick="edit('.$tabAct[$i][0].')"></td>
				<td class="suppr" onclick="suppr('.$tabAct[$i][0].',\''.str_replace("'","\'", $tabAct[$i][1]).'\')"></td>
				</tr>';

			if(date_add(date_create($tabAct[$i][2]), date_interval_create_from_date_string('1 day'))>date_create('now')){
				$actFutur=$act.$actFutur;
			}else{
				$actPast.=$act;
			}
	}
}

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<h3>Prochaines activités</h3>
<?php
if(!empty($actFutur)){
	echo '<table><tbody>';
	echo '<tr><th style="width:250px">Activité</th>
			<th>Date</th>
			<th style="width:95px">Places</th>
			<th style="width:40px">Prix</th>
			<th style="width:115px">Paiements<img class="sortA" onclick="sort(\'paie\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'paie\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			</tr>';
	echo $actFutur;
	echo '</tbody></table>';
	echo '<form method=post action="editAct.php" id="formEdit"><input type="hidden" id="idEdit" name="idEdit" /></form>';
	echo '<form method=post action="index.php" id="formSup"><input type="hidden" id="idSup" name="idSup" /></form>';
}else{
	echo '<div>Pas d\'activités.</div>';
}
?>
<h3>Activités passées</h3>
<?php
if(!empty($actPast)){
	echo '<table><tbody>';
	echo '<tr><th style="width:250px">Activité</th>
			<th>Date</th>
			<th style="width:95px">Places</th>
			<th style="width:40px">Prix</th>
			<th style="width:115px">Paiements<img class="sortA" onclick="sort(\'paie\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'paie\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			</tr>';
	echo $actPast;
	echo '</tbody></table>';
	echo '<form method=post action="editAct.php" id="formEdit"><input type="hidden" id="idEdit" name="idEdit" /></form>';
	echo '<form method=post action="index.php" id="formSup"><input type="hidden" id="idSup" name="idSup" /></form>';
}else{
	echo '<div>Pas d\'activités.</div>';
}
?>
<script type="text/javascript">

function sort(colonne, order){
window.location.href="index.php?sort="+colonne+"&order="+order;
}

function edit(id){
	document.getElementById('idEdit').value = id;
	document.getElementById('formEdit').submit();
}

function suppr(id, nom){
if(confirm("Voulez-vous vraiment supprimer l'activité "+nom+" ?")){
	document.getElementById('idSup').value = id;
	document.getElementById('formSup').submit();
	}
}
</script>
<?php
echo $footer;
?>