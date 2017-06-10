<?php
if(isset($_GET['code'])&&!empty($_GET['code'])){
	define('NEED_CONNECT',false);
	$affMenu=true;
	$votePublic=true;
	$postCarteESN="";
}else{
	$votePublic=false;
}

include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
define('TITRE_PAGE',"Vote");

//VERIF ID Vote
$acces=false;

if(isset($_GET['idVote']) || $votePublic){

	$bd = db_connect();
	
	if($votePublic){

		$_GET['code'] = mysqli_real_escape_string($bd, $_GET['code']);
		
		$vote = db_ligne($bd, "SELECT question.id, question.question, question.type, question.dteFin, question.nbChoix, question.anonyme, question.visibility, question.votants, question.nbVotants, question.askBy, question.code
					FROM membres_votes_questions AS question
					WHERE question.code='".$_GET['code']."' AND question.code IS NOT NULL");
					
		$vote['voted'] = "";

	}else{
	
		$_GET['idVote'] = mysqli_real_escape_string($bd, $_GET['idVote']);

		$vote = db_ligne($bd, "SELECT question.id, question.question, question.type, question.dteFin, question.nbChoix, question.anonyme, question.visibility, question.votants, question.nbVotants, question.askBy, question.code, votes.idQuestion AS voted
					FROM membres_votes_questions AS question
					LEFT JOIN membres_votes_votes AS votes ON question.id = votes.idQuestion AND votes.typeVotant='ESN' AND votes.idVotant='".ID."'
					WHERE question.id='".$_GET['idVote']."'");
	}	

	db_close($bd);

	if(empty($vote) && $vote!==false){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Ce vote n\'existe pas.'));
	
	}elseif($vote!==false){
	
		if(checkDroits($vote['visibility']) || $votePublic){
			$acces=true;
		}else{
			array_push($pageMessages, array('type'=>'err', 'content'=>'Vous n\'avez pas les droits nécessaires.'));
		}
	}
}else{ // Pas de code fourni
	array_push($pageMessages, array('type'=>'err', 'content'=>'Ce vote n\'existe pas.'));
}

if($acces){

	//Nouveau Vote
	if(isset($_POST['choix'])){
	
	
		if(date_create($vote['dteFin'])<date_create('now')){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Ce vote est clos.'));
		}
	
		elseif(!($votePublic || checkDroits($vote['votants']))){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Vous n\'avez pas les droits pour voter.'));
		}
		
		elseif($votePublic && empty($_POST['carteESN'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Numéro de carte ESN</em>..'));
		}
		
		
		if($votePublic){
		
			$bd = db_connect();
		
			$postCarteESN =  mysqli_real_escape_string($bd, $_POST['carteESN']);
			
			$infosVotantPublic = db_ligne($bd, "SELECT adh.id, adh.prenom, adh.nom, votes.idQuestion AS voted
												FROM membres_adherents AS adh
												LEFT JOIN membres_votes_votes AS votes ON votes.idQuestion='".$vote['id']."' AND votes.typeVotant='Adh' AND votes.idVotant = adh.id
												WHERE adh.idesn = '".$postCarteESN."'");
			
			db_close($bd);			

			if(empty($infosVotantPublic)){
				array_push($pageMessages, array('type'=>'err', 'content'=>"La carte ESN n'est pas reconnue."));
			
			}elseif(!empty($infosVotantPublic['voted'])){
				array_push($pageMessages, array('type'=>'err', 'content'=> $infosVotantPublic['prenom']." ".$infosVotantPublic['nom']." a déjà voté."));
				$vote['voted'] = $vote['id'];
			}
		
		}elseif(!empty($vote['voted'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>"Vous avez déjà voté."));
		}
		
		
		if(empty($pageMessages)){

			$tabChoix = explode('//',$_POST['choix'],-1);
			
			
			//Récuperation des id des choix pour ce vote
			
			$bd = db_connect();
			$idChoix = db_colonne($bd, "SELECT id
								FROM membres_votes_choix
								WHERE idQuestion='".$vote['id']."'");
			db_close($bd);
			if($idChoix === false){
				die();
			}
			
			$selectedChoix = array();
	
			
			//verif id de choix corrects et pas de doublons
			
			for($i=0; $i<count($tabChoix); $i++){
					
				if(!in_array($tabChoix[$i], $idChoix)){
					
					array_push($pageMessages, array('type'=>'err', 'content'=>'Vote invalide.'));
					break;
				}
				
				if(in_array($tabChoix[$i], $selectedChoix)){
					
					array_push($pageMessages, array('type'=>'err', 'content'=>'Vote invalide.'));
					break;
				}
				
				array_push($selectedChoix, $tabChoix[$i]);
			}
			
			
			if($vote['type'] == "classement"){
				
				if(count($tabChoix)!=count($idChoix)){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Vote invalide.'));
				}
	
				
			}else{
				
				if(count($tabChoix)!=intval($vote['nbChoix'])){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez sélectionner '.intval($vote['nbChoix']).' choix de vote.'));
				}
				
			}

		}
		

		if(empty($pageMessages)){
			
			$bd = db_connect();
			
			if($votePublic){
				$typeVotant = "Adh";
				$idVotant = $infosVotantPublic['id'];
			
			}else{
				$typeVotant = "ESN";
				$idVotant = ID;
			}
			
				
			for($i=0; $i<count($tabChoix); $i++){

				
				
				if($vote['type'] == "classement"){
					
					$addChoix = db_exec($bd, "
									INSERT INTO membres_votes_votes(idQuestion, typeVotant, idVotant, idChoix, nbPoints)
									VALUES(".$vote['id'].", '".$typeVotant."', ".$idVotant.", ".$tabChoix[$i].", ".(count($idChoix) - $i).")");
					
					
				}else{
				
					$addChoix = db_exec($bd, "
									INSERT INTO membres_votes_votes(idQuestion, typeVotant, idVotant, idChoix)
									VALUES(".$vote['id'].", '".$typeVotant."', ".$idVotant.", ".$tabChoix[$i].")");

				}
				
				
				if($addChoix === false){
					db_close($bd);
					die();
				}
			}
			
			$addVote = db_exec($bd, "
							UPDATE membres_votes_questions
							SET nbVotants = nbVotants+1
							WHERE id=".$vote['id']);
			
			
			if($addVote !== false){

				//Réactualisation
				
				$vote['nbVotants']++;
				$vote['voted'] = $vote['id'];	

				array_push($pageMessages, array('type'=>'ok', 'content'=>'Votre vote a bien été enregistré.'));
		
			}
			db_close($bd);
		}
	
	}//fin vote


	//Récuperation des choix
	$bd = db_connect();
	$choix = db_tableau($bd, "SELECT id, choix
						FROM membres_votes_choix
						WHERE idQuestion='".$vote['id']."'");
	db_close($bd);
	if($choix === false){
		die();
	}
	

	//mise en forme
	$dateFin = date("d/m/Y à H:i", strtotime($vote['dteFin']));
	
	if($vote['type']=="normal"){
		$type = "Vote normal";
		
	}elseif($vote['type']=="classement"){
		$type = "Vote par classement";
	}

	
	if(!empty($vote['code'])){
		$visibility = "Adhérents et tous les membres";
	}elseif($vote['visibility']=="bureau"){
		$visibility = "Membres du bureau";
	}elseif($vote['visibility']=="membre"){
		$visibility = "Membres actifs et du bureau";
	}else{
		$visibility = "Tous les membres";
	}

	if(!empty($vote['code']) && $vote['votants']=="adh"){
		$votants = "Adhérents seulement";
	}elseif(!empty($vote['code']) && $vote['votants']=="probatoire"){
		$votants = "Adhérents et tous les membres";
	}elseif($vote['votants']=="bureau"){
		$votants = "Membres du bureau";
	}elseif($vote['votants']=="membre"){
		$votants = "Membres actifs et du bureau";
	}else{
		$votants = "Tous les membres";
	}

	if($vote['anonyme']){
		$anonyme = "Oui";
	}else{
		$anonyme = "Non";
	}

	$tabChooseChoix = "";
	$tabResults = "";
	
	
	$canVote = false;
	if($votePublic || checkDroits($vote['votants'])){
			
		if(empty($vote['voted'])){
		
			if(date_create($vote['dteFin'])>date_create('now')){
				$statutVote = "<b>Vous n'avez pas encore répondu à ce vote.</b>";
				$canVote = true;
				
				//Création contenu de select si vote par classement
				
				if($vote['type']=="classement"){
					
					$optionsClassement = '<option value="">...</option>';
					
					for($i=0; $i < count($choix); $i++){
						
						
						$optionsClassement .= '<option value="'.($i+1).'">'.($i+1).'</option>';						
					}
				}
				
				
				for($i=0; $i < count($choix); $i++){
					
					if($vote['type']=="normal"){
					
						$tabChooseChoix .= '<tr id="trChoix'.$choix[$i]['id'].'" name="'.$choix[$i]['id'].'"><td>'.$choix[$i]['choix'].'</td><td id="cellSelectChoix'.$choix[$i]['id'].'" class="checkN" onclick="selectChoix('.$choix[$i]['id'].')"></td></tr>';
				
					
					}elseif($vote['type']=="classement"){
						
						$selectClassement = '<select id="selectClassement-'.$choix[$i]['id'].'" name="selectClassement-'.$choix[$i]['id'].'"
												style="width:80px;margin-bottom:0" onchange="changeClassement('.$choix[$i]['id'].')">'
											.$optionsClassement.
											'</select>';
						
						$tabChooseChoix .= '<tr id="trChoix'.$choix[$i]['id'].'" name="'.$choix[$i]['id'].'"><td>'.$choix[$i]['choix'].'</td>
											<td>'.$selectClassement.'<span id="nbPoints-'.$choix[$i]['id'].'"></span></td></tr>';
					}
				}
				
				
			}else{
				$statutVote = "Vous n'avez pas répondu à ce vote.";
			}
		
		}else{
			$statutVote = "Vous avez répondu à ce vote.";
		}
	
	}else{
		$statutVote = "Vous ne pouvez pas répondre à ce vote.";
	}
	
	
	
	
	if(!$canVote){
	
		//Récupération des résultats
		$bd = db_connect();
		$resultatsVote = db_tableau($bd, "SELECT votes.idChoix, votes.nbPoints, IF(ben.nom IS NULL, adh.nom, ben.nom) AS nom, IF(ben.prenom IS NULL, adh.prenom, ben.prenom) AS prenom
										FROM membres_votes_votes AS votes
										LEFT JOIN membres_benevoles AS ben ON votes.idVotant = ben.id AND votes.typeVotant = 'ESN'
										LEFT JOIN membres_adherents AS adh ON votes.idVotant = adh.id AND votes.typeVotant = 'Adh'
										WHERE idQuestion='".$vote['id']."'
										ORDER by votes.idChoix ASC, votes.nbPoints DESC, votes.typeVotant DESC, prenom ASC, nom ASC");
										
		db_close($bd);
		
		
		if($resultatsVote === false){
			die();
		}

		
		global $tabResultatsVote;
		$tabResultatsVote = array();
		
		$tempIdChoix = "";
		$totalPoints = 0;
		
		for($i=0; $i < count($resultatsVote); $i++){
			
			
			if($vote['type']=="classement"){
				
				$nbVote = $resultatsVote[$i]['nbPoints'];
				$totalPoints += $resultatsVote[$i]['nbPoints'];
				
				if($resultatsVote[$i]['nbPoints'] > 1){
					$nbPoints = " : ".$resultatsVote[$i]['nbPoints']."pts";
				}else{
					$nbPoints = " : ".$resultatsVote[$i]['nbPoints']."pt";
				}
				
			}else{
				
				$nbVote = 1;
				$nbPoints = "";
			}
			
			
			if($tempIdChoix == $resultatsVote[$i]['idChoix']){
			
				$tabResultatsVote[$resultatsVote[$i]['idChoix']]['nbVotes'] = $tabResultatsVote[$resultatsVote[$i]['idChoix']]['nbVotes'] + $nbVote;
				
				if(empty($resultatsVote[$i]['prenom'])){
					$tabResultatsVote[$resultatsVote[$i]['idChoix']]['noms'] .= ", Membre supprimé".$nbPoints;
				
				}else{
					$tabResultatsVote[$resultatsVote[$i]['idChoix']]['noms'] .= ", " . $resultatsVote[$i]['prenom']." ".$resultatsVote[$i]['nom'].$nbPoints;
				}
				

			}else{
			
				if(empty($resultatsVote[$i]['prenom'])){
					$nom = "Membre supprimé".$nbPoints;
				
				}else{
					$nom = $resultatsVote[$i]['prenom']." ".$resultatsVote[$i]['nom'].$nbPoints;
				}
			
				$tabResultatsVote[$resultatsVote[$i]['idChoix']] = array('nbVotes' => $nbVote, 'noms' => $nom);
				$tempIdChoix = $resultatsVote[$i]['idChoix'];
			}
		}
		

		$divResultats = "";
		

		usort($choix,"sortResults");
		
		if($vote['type']=="classement"){
			$labelVote = "Points";
			$nbTotalVotes = $totalPoints;
			
		}else{
			$labelVote = "Voix";
			$nbTotalVotes = count($resultatsVote);
		}

		for($i=0; $i < count($choix); $i++){

			$nbVotesChoix = (isset($tabResultatsVote[$choix[$i]['id']]['nbVotes'])?$tabResultatsVote[$choix[$i]['id']]['nbVotes']:0);
			
			if(!$vote['anonyme'] && $nbVotesChoix>0){
				
				$lstVotants = '<span id="lstVotants'.$choix[$i]['id'].'" style="display:none;">'.$tabResultatsVote[$choix[$i]['id']]['noms'].'</span>';
				$nbVoix = '<a onclick="affLstVotants('.$choix[$i]['id'].')">'.$labelVote.' : '.$nbVotesChoix.'</a>';
		
			}else{
				
				$lstVotants = "";
				$nbVoix = $labelVote.' : '.$nbVotesChoix;
			}

			$divResultats .= '<div class="blocResultatVote"><span style="font-weight:bold">'.$choix[$i]['choix'].'</span><br/>
								<table class="invisible" style="text-align:right"><tr>
									<td style="width:410px;">'.$nbVoix.'</td>
									<td><div class="barreAvancementOut" style="width:300px"><div class="barreAvancementIn" style="width:'.(($nbTotalVotes!=0)?(($nbVotesChoix/$nbTotalVotes)*100):0).'%'.(($nbVotesChoix==0)?"; border:none":"").'"></td>
									<td>'.(($nbTotalVotes!=0)?round(($nbVotesChoix/$nbTotalVotes)*100,1):0).'%</td></tr></table>'
									.$lstVotants.
								'</div>';
		}
	}

}//FIN VERIF ACCES

include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<?php if($acces){ ?>

<table class="vote"><tbody>
<tr><th>Date de fin du vote</th><th style="width:33%">Nombre de votants</th><th style="width:33%">Vote proposé par</th></tr>
<tr><td><?php echo $dateFin; ?></td><td><?php echo $vote['nbVotants']; ?></td><td><?php echo $vote['askBy']; ?></td></tr>
</tbody></table>

<h3>Paramètres</h3>
<div class="blocText">
<ul>
	<li>Type : <?php echo $type; ?></li>
	<li>Peuvent voir le vote : <?php echo $visibility; ?></li>
	<li>Peuvent voter : <?php echo $votants; ?></li>
	<li>Vote anonyme : <?php echo $anonyme; ?></li>
	<?php if($vote['type']=="normal"){ ?>
		<li><?php echo intval($vote['nbChoix']); ?> choix de réponse par vote</li>
	<?php } ?>
	
</ul>
</div>

<h3><?php echo $vote['question']; ?></h3>

<?php 
	if(!$votePublic){
		echo '<div class="blocText">';
		echo $statutVote;
		echo '</div>';
		echo '<br/>';
	}
	
	//Affichage des choix de vote
	if($canVote){
?>

	<?php if($vote['type']=="normal"){ ?>
		<div class="blocText">
		Vous devez sélectionner <?php echo intval($vote['nbChoix']); ?> choix.
		</div>
	<?php } ?>
	
	
	<?php
		echo '<table>';
		echo '<tr><th>Choix</th><th style="width:150px">'.(($vote['type']=="normal")?"Sélectionner":"Classement").'</th></tr>';
		echo '<tbody id="tbodyChoix">';
		echo $tabChooseChoix;
		echo '</tbody></table>';
	?>
	
	
	<br/>
	<form method=post action="http://<?php echo $_SERVER['HTTP_HOST']; ?>/vote-<?php echo (($votePublic)?$vote['code']:$vote['id']); ?>" id="formVote">
	<input type="hidden" id="choix" name="choix" value="" />
	
	<?php if($votePublic){ ?>
	<label for="carteESN">numéro de carte esn</label>
	<input type="text" id="carteESN" name="carteESN" maxlength=15 value="<?php echo $postCarteESN; ?>" />
	<br/>
	<?php } ?>
	
	<b>Attention : une fois le vote enregistré, vous ne pourrez plus le modifier.</b>
	<input type="button" onclick="submVote()" id="submitVote" value="valider le vote" />
	</form>

<?php }else{
echo $divResultats;
}?> 
<script type="text/javascript">

<?php if($canVote){ ?>

	<?php if($vote['type']=="normal"){ ?>

		function selectChoix(id){

			if(document.getElementById('trChoix'+id).className == "selected"){
				document.getElementById('trChoix'+id).className = "";
				document.getElementById('cellSelectChoix'+id).className = "checkN";
			
			}else{
				
				var trChoix = document.getElementById('tbodyChoix').childNodes;
				var countSelected = 0;
				
					for(var i=0; i<trChoix.length; i++){
					
						if(trChoix[i].className == "selected"){
							countSelected ++;
						}
					}
				
				if(countSelected >= <?php echo intval($vote['nbChoix']); ?>){
					alert("Vous ne pouvez pas sélectionner plus de <?php echo intval($vote['nbChoix']); ?> choix.");
				
				}else{

					document.getElementById('trChoix'+id).className = "selected";
					document.getElementById('cellSelectChoix'+id).className = "checkO";
				}
			}
		}

		function submVote(){ //submit vote normal

			var trChoix = document.getElementById('tbodyChoix').childNodes;
			var countSelected = 0;
			var ok=true;
			
			document.getElementById('choix').value="";
			
				for(var i=0; i<trChoix.length; i++){
				
					if(trChoix[i].className == "selected"){
						countSelected ++;
						document.getElementById('choix').value += trChoix[i].id.substr(7) + "//";				
					}
				}

			if(countSelected != <?php echo intval($vote['nbChoix']); ?>){
				alert("Vous devez sélectionner <?php echo intval($vote['nbChoix']); ?> choix.");
				ok=false;
				
			}else{
			
				<?php if($votePublic){ ?>
					if(document.getElementById('carteESN').value == ""){
						alert("Veuillez indiquer votre numéro de carte ESN.");
						ok=false;
					}
				<?php } ?>
			
			}	
			
			if(ok){
				document.getElementById('submitVote').disabled=true;
				document.getElementById('submitVote').value = "Patientez...";
				document.getElementById('submitVote').onclick="";
				document.getElementById('formVote').submit();
			}
		}
		
		<?php }else{ //type classement ?> 
		
		
			function changeClassement(id){
				
				var tabClassement = document.getElementById('tbodyChoix');
				var selectClassement = document.getElementById('selectClassement-'+id);
				var items = tabClassement.childNodes;
				var itemsArr = [];
				
				//MAJ points
					
				if(selectClassement.value != ""){
				
					document.getElementById('nbPoints-'+id).innerHTML = "Points : " + (items.length - selectClassement.value + 1)
				
				}else{
				
					document.getElementById('nbPoints-'+id).innerHTML = "";
				}
				
				
				//Classement
				
				for (var i in items) {
					if (items[i].nodeType == 1) {
						itemsArr.push(items[i]);
					}
				}

				itemsArr.sort(function(a, b) {
					
					var idA = a.id.match(/[0-9]+/);
					var idB = b.id.match(/[0-9]+/);
					
					var classementA = document.getElementById('selectClassement-'+idA).value;
					var classementB = document.getElementById('selectClassement-'+idB).value;
					
					
					if(classementA == "" && classementB == ""){
						return 0 ;
						
					}else if(classementA == ""){
						return -1 ;
						
					}else if(classementB == ""){
						return 1 ;
					
					}else{
						return parseInt(classementA) == parseInt(classementB) ? 0 : (parseInt(classementA) > parseInt(classementB) ? 1 : -1);
					}
				});

				for (i = 0; i < itemsArr.length; ++i) {
				  tabClassement.appendChild(itemsArr[i]);
				}
		
			}
			
			function submVote(){ //Submit classement

				var trChoix = document.getElementById('tbodyChoix').childNodes;
				var id = 0;
				var ok=true;
				
				
				document.getElementById('choix').value="";
				
					for(var i=0; i<trChoix.length; i++){

						id = trChoix[i].id.match(/[0-9]+/);
						
						if(document.getElementById('selectClassement-'+id).value != (i+1)){
							ok=false;
							break;
						}
					
						document.getElementById('choix').value += id + "//";				
					}

				if(!ok){
					alert("Votre classement n'est pas valide.");
					
				}else{
				
					<?php if($votePublic){ ?>
						if(document.getElementById('carteESN').value == ""){
							alert("Veuillez indiquer votre numéro de carte ESN.");
							ok=false;
						}
					<?php } ?>
				
				}	
				
				if(ok){
					document.getElementById('submitVote').disabled=true;
					document.getElementById('submitVote').value = "Patientez...";
					document.getElementById('submitVote').onclick="";
					document.getElementById('formVote').submit();
				}
			}
			
		
		
		<?php } // fin verif type de vote ?>

			
		

<?php }else{ //mode affichage resultats ?> 

	<?php if(!$vote['anonyme']){ ?>

		function affLstVotants(id){
			
			if(document.getElementById('lstVotants'+id).style.display == "none"){
				document.getElementById('lstVotants'+id).style.display = "";
			
			}else{
				document.getElementById('lstVotants'+id).style.display = "none";
			}
		}


	<?php } // fin fonctions vote non anonyme ?>


<?php } ?>
</script>
<?php } // fin verif acces ?> 
<?php

function sortResults($a, $b){

	$nbVotesChoixA = (isset($GLOBALS['tabResultatsVote'][$a['id']]['nbVotes'])?$GLOBALS['tabResultatsVote'][$a['id']]['nbVotes']:0);
	$nbVotesChoixB = (isset($GLOBALS['tabResultatsVote'][$b['id']]['nbVotes'])?$GLOBALS['tabResultatsVote'][$b['id']]['nbVotes']:0);

	if ($nbVotesChoixA == $nbVotesChoixB) {
		return 0;
	}
	return ($nbVotesChoixA > $nbVotesChoixB) ? -1 : 1;
}


echo $footer;
?>