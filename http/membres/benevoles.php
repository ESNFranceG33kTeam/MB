<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

define('TITRE_PAGE',"Bénévoles ".$tabChamps['nomAsso']['valeur']);
$affMenu = false;

$sort="";
$order="";


if (isset($_GET['sort']) && isset($_GET['order'])){

	switch($_GET['order']){
		case "asc" : $order="ASC,"; break;	
		case "dsc" : $order="DESC,"; break;	
	}
	if(!(empty($order))){
	
		switch($_GET['sort']){
			case "dob" : $sort="ben.dob "; break;
			case "etudes" : $sort="ben.etudes "; break;
			case "voit" : $sort="ben.voiture "; break;
			default : $order = ""; $sort = "";
		}
	}
}

//Construction tableau Roles
$tabRoles = array();


$fileRoles = fopen(($GLOBALS['SITE']->getFolderData()).'/../liste_roles.txt', 'r');

while (!feof($fileRoles)){

	$ligneRole = explode('//',trim(fgets($fileRoles)),3);	
	array_push($tabRoles, array($ligneRole[0], $ligneRole[1]));
	
}

fclose($fileRoles);






//recup données
$bd = db_connect();
$tabUsr = db_tableau($bd, "
						SELECT ben.prenom, ben.nom, ben.fb, ben.tel, ben.mail, ben.dob, ben.adresse, ben.etudes, ben.voiture, drt.general, drt.roles
						FROM membres_benevoles AS ben
						LEFT JOIN membres_droits AS drt ON ben.id = drt.id
						ORDER BY ".$sort.$order." ben.prenom ASC, ben.nom ASC");
db_close($bd);


$bureau="";
$membres="";
$membresProba="";
$countBureau=0;
$countMembres=0;
$countProba=0;

if($tabUsr !== false && !(empty($tabUsr))){

	for($i=0; $i<count($tabUsr); $i++){
		
		//Roles
		$rolesMembre = "";
		$rolesMem = explode('//', $tabUsr[$i]['roles']);
	
		for($rol=0; $rol<count($rolesMem); $rol++){
			
			$keyRole = "";

			for($liTabRole=0; $liTabRole<count($tabRoles); $liTabRole++){
				
				if($rolesMem[$rol] ==  $tabRoles[$liTabRole][0]){
					$keyRole = $liTabRole;
					break;
				}
			}
			
			if(!empty($keyRole) || $keyRole===0){
				$rolesMembre .= '<span style="font-size:11px; white-space:nowrap; display:inline-block">'.$tabRoles[$keyRole][1].(($rol+1 < count($rolesMem))?', ':'').'</span>';
			}
		}
		
		
		
	
		$dob = explode('-',$tabUsr[$i][5],3);
		$dateOB = $dob[2].'/'.$dob[1].'/'.$dob[0];
		
		$adr = explode('//',$tabUsr[$i][6],3);
		$adresse = $adr[0].'<br/>'.$adr[1].' '.$adr[2];

		$voit=($tabUsr[$i][8]=='1')?'Oui':'Non';
		
		$ligneBenevole = '<tr>
		<td><div class="gras" style="line-height:18px">'.$tabUsr[$i][0].' '.$tabUsr[$i][1].'</div><div style="line-height:11px; max-width:185px">'.$rolesMembre.'</div></td>
			<td>Tel : '.chunk_split($tabUsr[$i][3], 2, " ").'<div style="float:right"><a href="'.$tabUsr[$i][2].'" target="_blank"><img class="iconeListe" src="../template/images/facebook.png"></a></div><br /><div class="hidden-inline" style="width:210px"><a href="mailto:'.$tabUsr[$i][4].'">'.$tabUsr[$i][4].'</a></div></td>
			<td  class="center">'.$dateOB.'</td><td style="line-height:1.15em; font-size:0.8em">'.$adresse.'</td><td>'.$tabUsr[$i][7].'</td>
			<td  class="center">'.$voit.'</td></tr>';
			
		if($tabUsr[$i][9]=="bureau"){
			$bureau.= $ligneBenevole;
			$countBureau++;
		}elseif($tabUsr[$i][9]=="membre"){
			$membres.= $ligneBenevole;
			$countMembres++;
		}elseif($tabUsr[$i][9]=="probatoire"){
			$membresProba.= $ligneBenevole;
			$countProba++;
		}
	}
}

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>
<div class="gras">Nombre de bénévoles : <?php echo count($tabUsr); ?> (<?php echo $countBureau; ?> membres du bureau, <?php echo $countMembres; ?> membres actifs, <?php echo $countProba; ?> en probation)</div><br/>
<div class="blocText" style="width:250px"><a href="lstMailsBen.php" target="_blank">
<img src="../template/images/emails.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Liste des e-mails des bénévoles</a>
</div>
<h3>Membres du bureau</h3>
<?php
if(!empty($bureau)){
	echo '<table><tbody>';
	echo '<tr><th style="width:190px">Nom</th><th style="width:190px">Contact</th>
			<th>Naissance<img class="sortA" onclick="sort(\'dob\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'dob\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th style="width:135px">Adresse</th>
			<th style="width:135px">Etudes<img class="sortA" onclick="sort(\'etudes\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'etudes\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th>Voiture<img class="sortA" onclick="sort(\'voit\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'voit\',\'dsc\')" src="../template/images/sortDesc.png"></th></tr>';
	echo $bureau;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de données.</div>';
}
?>
<h3>Membres actifs</h3>
<?php
if(!empty($membres)){
	echo '<table><tbody>';
	echo '<tr><th style="width:190px">Nom</th><th style="width:190px">Contact</th>
			<th>Naissance<img class="sortA" onclick="sort(\'dob\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'dob\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th style="width:135px">Adresse</th>
			<th style="width:135px">Etudes<img class="sortA" onclick="sort(\'etudes\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'etudes\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th>Voiture<img class="sortA" onclick="sort(\'voit\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'voit\',\'dsc\')" src="../template/images/sortDesc.png"></th></tr>';
	echo $membres;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de données.</div>';
}
?>

<h3>Membres en période probatoire</h3>
<?php
if(!empty($membresProba)){
	echo '<table><tbody>';
	echo '<tr><th style="width:190px">Nom</th><th style="width:190px">Contact</th>
			<th>Naissance<img class="sortA" onclick="sort(\'dob\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'dob\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th style="width:135px">Adresse</th>
			<th style="width:135px">Etudes<img class="sortA" onclick="sort(\'etudes\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'etudes\',\'dsc\')" src="../template/images/sortDesc.png"></th>
			<th>Voiture<img class="sortA" onclick="sort(\'voit\',\'asc\')" src="../template/images/sortAsc.png"><img class="sortD" onclick="sort(\'voit\',\'dsc\')" src="../template/images/sortDesc.png"></th></tr>';
	echo $membresProba;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de données.</div>';
}
?>
<script type="text/javascript">
function suppr(id){
document.getElementById('idSup').value = id;
document.getElementById('formSup').submit();
}
function editUsrName(){

	var prenom = document.getElementById('prenom').value;
	var nom = document.getElementById('nom').value;

	if (nom==""){
		document.getElementById('usrname').value = prenom;
	}
	if (prenom==""){
		document.getElementById('usrname').value = nom;
	}
	if (nom!="" && prenom!=""){
		document.getElementById('usrname').value = prenom.toLowerCase()+"_"+nom.toLowerCase();
	}

}
function sort(colonne, order){
window.location.href="benevoles.php?sort="+colonne+"&order="+order;
}
</script>
<?php
echo $footer;
?>