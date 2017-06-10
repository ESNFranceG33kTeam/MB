<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

define('TITRE_PAGE',"Liste des votes");



//nouveau vote
if(isset($_POST['question'])){


	$nomChamps=array("Question","Date de fin du vote","Heure de fin du vote");
	$maxChamps = array(150,10,5);

	$valChamps = array($_POST['question'],$_POST['dateFin'],$_POST['timeFin']);
	
 	for($i=0; $i<count($nomChamps); $i++){
		if(empty($valChamps[$i])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>'.$nomChamps[$i].'</em>.'));
		}
		if(mb_strlen($valChamps[$i])>$maxChamps[$i]){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>'.$nomChamps[$i].'</em> ne doit pas dépasser '.$maxChamps[$i].' caractères.'));
		}	
	}
	
	if(!empty($_POST['dateFin'])){
		$dte = date_parse($_POST['dateFin']);
		if (!checkdate($dte['month'], $dte['day'], $dte['year'])) {
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Date de fin du vote</em> n\'est pas valide.'));
		}else{
			$dateFin = $dte['year'].'-'.$dte['month'].'-'.$dte['day'];
		}	
	}

	if(!empty($_POST['timeFin'])&&$_POST['timeFin']!="--:--"){
		$tme = $_POST['timeFin'];
		if (!preg_match("/(2[0-3]|[01][0-9]):[0-5][0-9]/", $tme)){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Heure de fin du vote</em> n\'est pas valide.'));
		}	
	}

	if(date_create($dateFin." ".$tme)<date_create('now') && empty($pageMessages)){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La date de fin du vote ne doit pas être passée.'));
	}

	$tabChoix = explode('//',$_POST['lstChoix'],-1);
	
	if(empty($tabChoix)){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez ajouter des choix de vote.'));
	}
	

	
	if(!($_POST['typeVote']=="normal" || $_POST['typeVote']=="classement")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Type de vote</em> n\'est pas valide.'));
	}


	if($_POST['typeVote']=="normal"){
		
		if(empty($_POST['selectNbChoix'])&&!is_numeric($_POST['selectNbChoix'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nombre de choix sélectionnables</em>.'));
		}elseif(!is_numeric($_POST['selectNbChoix'])){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Nombre de choix sélectionnables</em> n\'est pas valide.'));
		}elseif($_POST['selectNbChoix']<1){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Nombre de choix sélectionnables</em> n\'est pas valide.'));
		}elseif ($_POST['selectNbChoix'] > count($tabChoix)){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Nombre de choix sélectionnables</em> n\'est pas valide.'));
		}
		
	}elseif($_POST['typeVote']=="classement"){
		$_POST['selectNbChoix'] = count($tabChoix);
	}

	
	if(!($_POST['anonyme']=="Non" || $_POST['anonyme']=="Oui")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Vote anonyme</em> n\'est pas valide.'));
	}
	
	
	if(!($_POST['droitsVote']=="probatoire" || $_POST['droitsVote']=="membre" || $_POST['droitsVote']=="bureau" || $_POST['droitsVote']=="adh")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voter</em> n\'est pas valide.'));
	}

	if(!($_POST['droitsView']=="probatoire" || $_POST['droitsView']=="membre" || $_POST['droitsView']=="bureau")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le vote</em> n\'est pas valide.'));
	
	}elseif($_POST['droitsVote']=="probatoire" && $_POST['droitsView']!="probatoire"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le vote</em> n\'est pas valide.'));
	
	}elseif($_POST['droitsVote']=="membre" && $_POST['droitsView']=="bureau"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le vote</em> n\'est pas valide.'));
	
	}elseif($_POST['droitsVote']=="adh" && $_POST['droitsView']!="probatoire"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le vote</em> n\'est pas valide.'));
	}
	
	if($_POST['public']=="Oui" && $_POST['publicBen']=="Oui" && $_POST['droitsVote']!="probatoire"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voter</em> n\'est pas valide.'));
	}
	
	if(DROITS=="probatoire" && ($_POST['droitsView']=="membre" || $_POST['droitsView']=="bureau")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le vote</em> n\'est pas valide.'));
	
	}elseif(DROITS=="membre" && $_POST['droitsView']=="bureau"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir le vote</em> n\'est pas valide.'));
	
	}
	
	if(empty($pageMessages)){
	
		
		$bd = db_connect();
		
		$_POST['question'] = mysqli_real_escape_string($bd, $_POST['question']);
		
		if($_POST['anonyme']=="Non"){
			$anonyme = 0;
		}else{
			$anonyme = 1;
		}
		
		if($_POST['public']=="Non"){
			$code = null;
		}else{
			$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$code = '';
			srand();
			for ($i = 0; $i < 10; $i++) {
				$code .= $characters[rand(0, strlen($characters) - 1)];
			}
		}
		
						
		$addVote = db_exec($bd, "
					INSERT INTO membres_votes_questions(question, type, dteFin, nbChoix, anonyme, visibility, votants, askBy, code)
					VALUES('".$_POST['question']."','".$_POST['typeVote']."','".$dateFin." ".$tme."','".$_POST['selectNbChoix']."','".$anonyme."','".$_POST['droitsView']."','".$_POST['droitsVote']."','".PRENOM." ".NOM."','".$code."')");
		
		
		if($addVote!==false){
			
			$idVote = db_lastId($bd);
			
			for($i=0; $i<count($tabChoix); $i++){
	
				$tabChoix[$i] = mysqli_real_escape_string($bd, $tabChoix[$i]);
	
				$addChoix = db_exec($bd, "
								INSERT INTO membres_votes_choix(idQuestion, choix)
								VALUES(".$idVote.", '".$tabChoix[$i]."')");
			
				if($addChoix === false){
					db_close($bd);
				die();
				}
			}
			
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Le vote a bien été ajouté.'));
			db_close($bd);
		}		
	}
}//fin nouveau vote


//Suppr vote
if(isset($_POST['idSup'])){

	//Verif droits
	requireDroits("bureau");
		
	$bd = db_connect();
					
	$sup = db_exec($bd, "
				DELETE FROM membres_votes_questions
				WHERE id='".$_POST['idSup']."'
				LIMIT 1");
	
	db_close($bd);
	
	if($sup!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Le vote à bien été supprimé.'));
	}		
}//fin suppr vote



//Récupération des données
$bd = db_connect();

$votes = db_tableau($bd, "		
			SELECT question.id, question.question, question.dteFin, question.visibility, question.votants, question.nbVotants, question.code, votes.idQuestion AS voted
			FROM membres_votes_questions AS question
			LEFT JOIN membres_votes_votes AS votes ON question.id = votes.idQuestion AND votes.typeVotant='ESN' AND votes.idVotant='".ID."'
			GROUP BY question.id
			ORDER BY dteFin ASC");	
			
db_close($bd);		

$votesEnCours="";
$votesPast="";


if($votes!==false && !empty($votes)){
		
	for($i=0; $i<count($votes); $i++){	

		$ligneVote = "";
	
		//verif Droits
		if(checkDroits($votes[$i]['visibility'])){
		
			if(!empty($votes[$i]['code'])){
				$votePublic = '<div style="float:right"><a href="http://'.$_SERVER['HTTP_HOST'].'/vote-'.$votes[$i]['code'].'"><img class="iconeListe" src="../template/images/world_link.png" title="Lien du vote à partager"></a></div>';
			}else{
				$votePublic = "";
			}
		
			if(checkDroits($votes[$i]['votants'])){
			
				if(empty($votes[$i]['voted'])){
				
					if(date_create($votes[$i]['dteFin'])>date_create('now')){
						$statutVote = "<b>Vous n'avez pas encore répondu à ce vote.</b>";
					}else{
						$statutVote = "Vous n'avez pas répondu à ce vote.";
					}
				
				}else{
					$statutVote = "Vous avez répondu à ce vote.";
				}
			
			}else{
				$statutVote = "Vous ne pouvez pas répondre à ce vote.";
			}
			
			$nomVote = '<div style="overflow:hidden; width:530px; font-weight:bold"><a href="http://'.$_SERVER['HTTP_HOST'].'/vote-'.$votes[$i]['id'].'">'.$votes[$i]['question'].'</a></div>';
			
			$dateFin = date("d/m/Y à H:i", strtotime($votes[$i]['dteFin']));
			
			$ligneVote.='<tr><td>'.$nomVote.$statutVote.$votePublic.'</td>
						<td>'.$dateFin.'</td>
						<td style="text-align:center">'.$votes[$i]['nbVotants'].'</td>
						'.(checkDroits("bureau")?'<td class="suppr" id="cellRemoveVote'.$votes[$i]['id'].'" onclick="supprVote('.$votes[$i]['id'].')"></td>':'').
						'</tr>';

			
			if(date_create($votes[$i]['dteFin'])>date_create('now')){
				$votesEnCours.=$ligneVote;
			}else{
				$votesPast = $ligneVote.$votesPast;
			}		
		}
	}
}
			
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>

<h3>Liste des votes en cours</h3>
<?php
if(!empty($votesEnCours)){

	echo '<table><tbody>';
	echo '<tr><th>Vote</th><th style="width:120px">Date de fin</th><th style="width:60px">Votants</th></tr>';
	echo $votesEnCours;
	echo '</tbody></table>';
}else{
	echo "<br/>Aucun vote en cours.<br/>";
}
?>

<h3>Liste des votes passés</h3>
<?php
if(!empty($votesPast)){

	echo '<table><tbody>';
	echo '<tr><th>Vote</th><th style="width:120px">Date de fin</th><th style="width:60px">Votants</th></tr>';
	echo $votesPast;
	echo '</tbody></table>';
}else{
	echo "<br/>Aucun vote passé.<br/>";
}
?>
<h3>Nouveau vote</h3>

<form method=post action="lstVotes.php" id="formNewVote">

<label for="question">question</label>
<input type="text" id="question" name="question" onclick="affAdd()" style="width:520px" maxlength=150 autocomplete="off" />

<div id="divAddVote" style="display:none">

<table class="invisible"><tbody><tr><td>
<label for="dateFin">date de fin du vote</label>
<input type="date" id="dateFin" name="dateFin" maxlength=10 autocomplete="off" value="jj-mm-aaaa"/>
</td><td>
<label for="timeFin">heure</label>
<input type="time" id="timeFin" name="timeFin" style="width:130px" maxlength=5 autocomplete="off" value="--:--"/>
</td></tr></tbody></table>


<label for="inputNewChoix">choix de vote</label>
	<table style="width:542px">
	<thead><th>Choix</th></thead>
	<tbody id="tbodyChoix">
	</tbody>
	<tr>
	<td>
		Nouveau choix : <input type="text" id="inputNewChoix" style="margin:0; box-sizing:border-box; height:inherit; width:398px"/>
	</td>
	<td id="tdAddChoix" class="add" onclick="addChoix(0)"></td>
	</tr>
	</table>
<br/>



<label for="typeVote">type de vote</label>
	<input id="typeVoteN" type="radio" name="typeVote" value="normal" onchange="selectType()" checked>  
	<label class="radio" for="typeVoteN">Normal</label>  
	<input id="typeVoteC" type="radio" name="typeVote" value="classement" onchange="selectType()">  
	<label class="radio" for="typeVoteC">Classement</label> 
	
	

<div id="divNbChoix">
<label for="selectNbChoix">nombre de choix sélectionnables</label>
	<select id="selectNbChoix" name="selectNbChoix" style="width:60px">
	<option value="1">1</option>
	</select>
</div>



<label for="anonyme">vote anonyme</label>
	<input id="anonymeN" type="radio" name="anonyme" value="Non" checked>  
	<label class="radio" for="anonymeN">Non</label>  
	<input id="anonymeO" type="radio" name="anonyme" value="Oui">  
	<label class="radio" for="anonymeO">Oui</label> 


<label for="public">vote ouvert aux adhérents</label>
	<input id="publicN" type="radio" name="public" value="Non" onchange="selectPublic()" checked>  
	<label class="radio" for="publicN">Non</label>  
	<input id="publicO" type="radio" name="public" value="Oui" onchange="selectPublic()">  
	<label class="radio" for="publicO">Oui</label> 
	

<div id="divPublicBen" style="display:none">
<label for="public">vote ouvert aux bénévoles</label>
	<input id="publicBenN" type="radio" name="publicBen" value="Non" checked>  
	<label class="radio" for="publicBenN">Non</label>  
	<input id="publicBenO" type="radio" name="publicBen" value="Oui">  
	<label class="radio" for="publicBenO">Oui</label>
</div>

<div id="divDroits">
<label for="votants">membres pouvant voter</label>
	<input type="checkbox" id="votantsP" name="votantsP" onchange="selectVotants()">
	<label class="checkbox" for="votantsP" style="margin-bottom:10px">Membres en période probatoire</label>
	
	<input type="checkbox" id="votantsM" name="votantsM" onchange="selectVotants()">
	<label class="checkbox" for="votantsM" style="margin-bottom:10px">Membres actifs</label>
	
	<input type="checkbox" id="votantsB" name="votantsB" onchange="selectVotants()" checked>
	<label class="checkbox" for="votantsB" style="margin-bottom:10px">Membres du bureau</label>
	
<br/><br/>
	<label for="visible">membres pouvant voir le vote</label>
	<input type="checkbox" id="visibleP" name="visibleP" onchange="selectVisible()" <?php echo ((DROITS=="probatoire")?"checked":"")?>>
	<label class="checkbox" for="visibleP" style="margin-bottom:10px">Membres en période probatoire</label>
	
	<input type="checkbox" id="visibleM" name="visibleM" onchange="selectVisible()" <?php echo ((DROITS=="probatoire"||DROITS=="membre")?"checked":"")?>>
	<label class="checkbox" for="visibleM" style="margin-bottom:10px">Membres actifs</label>
	
	<input type="checkbox" id="visibleB" name="visibleB" onchange="selectVisible()" checked>
	<label class="checkbox" for="visibleB" style="margin-bottom:10px">Membres du bureau</label>

	<br/><br/>
</div>

	<input type="button" onclick="submNewVote()" id="submitNewVote" value="valider" />
</div>


<input type="hidden" id="lstChoix" name="lstChoix" value=""/>
<input type="hidden" id="droitsVote" name="droitsVote" value=""/>
<input type="hidden" id="droitsView" name="droitsView" value=""/>
</form>

<form method=post action="lstVotes.php" id="formRemoveVote" style="display:none">
<input type="hidden" id="idSup" name="idSup" value=""/>
</form>


<script type="text/javascript">

function affAdd(){
	document.getElementById('divAddVote').style.display="";
}	

function addChoix(i){

	if(document.getElementById('inputNewChoix').value!= ""){

		document.getElementById('tbodyChoix').innerHTML += '<tr id="choix'+i+'"><td>'+document.getElementById('inputNewChoix').value+'</td><td class="remove" onclick="supChoix('+i+')"></td></tr>';
		document.getElementById('inputNewChoix').value = "";
		document.getElementById('tdAddChoix').onclick = function(){addChoix(i+1)};
		setNbChoix();
	}
	
	document.getElementById('inputNewChoix').focus();
}

function supChoix(i){
	document.getElementById('choix'+i).parentNode.removeChild(document.getElementById('choix'+i));
	setNbChoix();
	document.getElementById('inputNewChoix').focus();
}


function selectType(){
	
	if(document.getElementById('typeVoteN').checked==true){
		document.getElementById('divNbChoix').style.display="";
		
	}else{
		document.getElementById('divNbChoix').style.display="none";
	}
}



function setNbChoix(){

	document.getElementById('selectNbChoix').innerHTML = "";
	var tbodyChoix = document.getElementById('tbodyChoix').childNodes;

	for(i=1; i<(tbodyChoix.length); i++){
		document.getElementById('selectNbChoix').innerHTML += '<option value="'+i+'">'+i+'</option>';
	}
}

function selectPublic(){

	if(document.getElementById('publicO').checked==true){
		document.getElementById('divPublicBen').style.display="";
		document.getElementById('divDroits').style.display="none";
	
	}else{
		document.getElementById('divPublicBen').style.display="none";
		document.getElementById('divDroits').style.display="";
	}
}



function selectVotants(){
	document.getElementById('votantsB').checked=true;

	if(document.getElementById('votantsP').checked==true && document.getElementById('votantsM').checked==false){
		document.getElementById('votantsM').checked=true;
	}
	
	if(document.getElementById('votantsM').checked==true){
		
		document.getElementById('visibleB').checked=true;
		document.getElementById('visibleM').checked=true;
	
	}
	
	if(document.getElementById('votantsP').checked==true){
		document.getElementById('votantsM').checked=true;
		
		document.getElementById('visibleB').checked=true;
		document.getElementById('visibleM').checked=true;
		document.getElementById('visibleP').checked=true;
	}	
}

function selectVisible(){
	document.getElementById('visibleB').checked=true;
	<?php echo ((DROITS=="probatoire")?"document.getElementById('visibleP').checked=true;document.getElementById('visibleM').checked=true;":"")?>
	<?php echo ((DROITS=="membre")?"document.getElementById('visibleM').checked=true;":"")?>
	
	if(document.getElementById('visibleP').checked==true && document.getElementById('visibleM').checked==false){
		document.getElementById('visibleM').checked=true;
	}

	if(document.getElementById('votantsM').checked==true){
		document.getElementById('visibleM').checked=true;
	
	}
	
	if(document.getElementById('visibleM').checked==false){
		document.getElementById('visibleP').checked=false;
	}
	
	if(document.getElementById('votantsP').checked==true){
		document.getElementById('visibleM').checked=true;
		document.getElementById('visibleP').checked=true;
	}
}

function submNewVote(){

	document.getElementById('lstChoix').value = "";

	if(document.getElementById('question').value == ""){
	
		alert("Veuillez remplir l'intitulé de la question.");
		
	}else{
	
		if(document.getElementById('dateFin').value == "jj-mm-aaaa" || document.getElementById('timeFin').value == "--:--" || document.getElementById('dateFin').value == "" || document.getElementById('timeFin').value == ""){
		
			alert("Veuillez choisir la date et l'heure de la fin du vote.");
			
		}else{
		
			//construction liste des choix
			var tbodyChoix = document.getElementById('tbodyChoix').childNodes;

			for(i=1; i<(tbodyChoix.length); i++){
				choix = tbodyChoix[i].childNodes[0].innerHTML.replace("//"," ");
				document.getElementById('lstChoix').value += choix+"//";
			}
			
			if(document.getElementById('lstChoix').value == ""){
			
				alert("Veuillez ajouter des choix de vote.");
			
			}else{
			
			
				//definition des droits
				
				
				if(document.getElementById('publicO').checked==true && document.getElementById('publicBenN').checked==true){
					document.getElementById('droitsVote').value = "adh";
				
				}else if(document.getElementById('votantsP').checked==true || (document.getElementById('publicO').checked==true && document.getElementById('publicBenO').checked==true)){
					document.getElementById('droitsVote').value = "probatoire";
				
				}else if(document.getElementById('votantsM').checked==true){
					document.getElementById('droitsVote').value = "membre";
				
				}else{
					document.getElementById('droitsVote').value = "bureau";
				}
				
				
			
				if(document.getElementById('visibleP').checked==true || document.getElementById('publicO').checked==true){
					document.getElementById('droitsView').value = "probatoire";
				
				}else if(document.getElementById('visibleM').checked==true){
					document.getElementById('droitsView').value = "membre";
				
				}else{
					document.getElementById('droitsView').value = "bureau";
				}
				
				document.getElementById('submitNewVote').disabled=true;
				document.getElementById('submitNewVote').value = "Patientez...";
				document.getElementById('submitNewVote').onclick="";
				document.getElementById('formNewVote').submit();
			
			}
		}
	}
}

function supprVote(id){
	if(confirm("Voulez-vous vraiment supprimer ce vote ?")){
		document.getElementById('cellRemoveVote'+id).onclick="";
		document.getElementById('idSup').value = id;
		document.getElementById('formRemoveVote').submit();
	}
}

</script> 
<?php
echo $footer;
?>