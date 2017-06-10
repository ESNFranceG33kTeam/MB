<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

define('TITRE_PAGE',"Ajouter une feuille de présence");



//nouvelle feuille
if(isset($_POST['nom'])){



	if(empty($_POST['nom'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nom</em>.'));
	}
	if(mb_strlen($_POST['nom'])>150){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nom</em> ne doit pas dépasser 150 caractères.'));
	}
	
	
	
	if(!($_POST['typeFeuille']=="unique" || $_POST['typeFeuille']=="groupe")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Type</em> n\'est pas valide.'));
	}


	if(!($_POST['droits']=="self" || $_POST['droits']=="bureau" || $_POST['droits']=="all" || $_POST['droits']==ID)){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Fonctionnement de la feuille</em> n\'est pas valide.'));
	
	}

	if(!($_POST['droitsView']=="probatoire" || $_POST['droitsView']=="membre" || $_POST['droitsView']=="bureau")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir la feuille</em> n\'est pas valide.'));
	
	}
	
	if(DROITS=="probatoire" && ($_POST['droitsView']=="membre" || $_POST['droitsView']=="bureau")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir la feuille</em> n\'est pas valide.'));
	
	}elseif(DROITS=="membre" && $_POST['droitsView']=="bureau"){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres pouvant voir la feuille</em> n\'est pas valide.'));
	
	}
	
	
	if(!is_numeric($_POST['membresAffiche']) || $_POST['membresAffiche'] <1 || $_POST['membresAffiche'] >7){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Membres affichés sur la feuille</em> n\'est pas valide.'));
	}
	
	if(!($_POST['choix']=="ON" || $_POST['choix']=="ONP" || $_POST['choix']=="ONR" || $_POST['choix']=="ONPR")){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur entrée dans le champ <em>Réponses possibles</em> n\'est pas valide.'));
	
	}
	
	
	if(empty($pageMessages)){
	
		if($_POST['typeFeuille']=="groupe"){
			$idGroupe = -1;
			$res = "Le groupe a bien été ajouté.";
		}else{
			$idGroupe = "NULL";
			$res = "La feuille a bien été ajoutée.";
		}
		
		
		$bd = db_connect();
		
		$_POST['nom'] = mysqli_real_escape_string($bd, $_POST['nom']);
		
		

		$addPresence = db_exec($bd, "
					INSERT INTO membres_presence_feuilles(idGroupe, nom, droits, visibility, affiche, choixRep)
					VALUES(".$idGroupe.",'".$_POST['nom']."','".$_POST['droits']."','".$_POST['droitsView']."','".$_POST['membresAffiche']."','".$_POST['choix']."')");
		
		$idPres = db_lastId($bd);
		db_close($bd);
			
		if($addPresence !== false){
			array_push($_SESSION['postMessages'], array('type'=>'ok', 'content'=>$res));
			
			if($idGroupe != -1){
				header('Location: http://'.$_SERVER['HTTP_HOST'].'/presence-'.$idPres);
			}else{
				header('Location: http://'.$_SERVER['HTTP_HOST'].'/membres/presence.php');
			}
			die();
		}		
	}
}//fin nouvele feuille


include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
?>


<br/>
<form method=post action="addPresence.php" id="formNewPresence">

<label for="typeFeuille">type de feuille de présence</label>
	<input id="typeFeuilleU" type="radio" name="typeFeuille" value="unique" onchange="selectType()" checked>  
	<label class="radio" for="typeFeuilleU">Feuille unique</label>  
	<input id="typeFeuilleG" type="radio" name="typeFeuille" value="groupe" onchange="selectType()" >  
	<label class="radio" for="typeFeuilleG">Groupe de feuilles</label> 


<label id="labelNom" for="nom">nom de la feuille</label>
<input type="text" id="nom" name="nom" style="width:520px" maxlength=150 autocomplete="off" /><br/><br/>


<label id="labelFonctionnement" for="droits">fonctionnement de la feuille</label>
	<input id="droitsS" type="radio" name="droits" value="self"  checked>  
	<label class="radio" for="droitsS">Une personne ne peut inscrire qu'elle même</label><br/> 
	<input id="droitsM" type="radio" name="droits" value="<?php echo ID; ?>" >  
	<label class="radio" for="droitsM">Vous êtes la seule personne à pouvoir inscrire les autres</label><br/> 
	<input id="droitsB" type="radio" name="droits" value="bureau" >  
	<label class="radio" for="droitsB">Seuls les membres du bureau peuvent inscrire les personnes</label><br/> 
	<input id="droitsA" type="radio" name="droits" value="all" >  
	<label class="radio" for="droitsA">Toutes les personnes ayant accès à la feuille peuvent inscrire les autres</label> 

	
<br/><br/>
	<label id="labelVisible" for="visible">membres pouvant voir la feuille</label>
	<input type="checkbox" id="visibleP" name="visibleP" onchange="selectVisible()" <?php echo ((DROITS=="probatoire")?"checked":"")?>>
	<label class="checkbox" for="visibleP" style="margin-bottom:10px">Membres en période probatoire</label>
	
	<input type="checkbox" id="visibleM" name="visibleM" onchange="selectVisible()" <?php echo ((DROITS=="probatoire"||DROITS=="membre")?"checked":"")?>>
	<label class="checkbox" for="visibleM" style="margin-bottom:10px">Membres actifs</label>
	
	<input type="checkbox" id="visibleB" name="visibleB" onchange="selectVisible()" checked>
	<label class="checkbox" for="visibleB" style="margin-bottom:10px">Membres du bureau</label>

<br/><br/>

	<label id="labelAffiche" for="affiche">membres affichés sur la feuille</label>
	<input type="checkbox" id="afficheP" name="afficheP" checked>
	<label class="checkbox" for="afficheP" style="margin-bottom:10px">Membres en période probatoire</label>
	
	<input type="checkbox" id="afficheM" name="afficheM" checked>
	<label class="checkbox" for="afficheM" style="margin-bottom:10px">Membres actifs</label>
	
	<input type="checkbox" id="afficheB" name="afficheB" checked>
	<label class="checkbox" for="afficheB" style="margin-bottom:10px">Membres du bureau</label>

<br/><br/>	
	
<label id="labelChoixRep" for="choix">réponses possibles</label>
	<input id="choixON" type="radio" name="choix" value="ON" checked>  
	<label class="radio" for="choixON">Oui / Non</label>
	<input id="choixONP" type="radio" name="choix" value="ONP" >  
	<label class="radio" for="choixONP">Oui / Non / Peut-être</label>
	<input id="choixONR" type="radio" name="choix" value="ONR" >  
	<label class="radio" for="choixONR">Oui / Non / En retard</label> 
	<input id="choixONPR" type="radio" name="choix" value="ONPR" >  
	<label class="radio" for="choixONPR">Oui / Non / Peut-être / En retard</label> 

	
<br/><br/>
<input type="button" onclick="submNewPresence()" id="submitNewPresence" value="valider" />
<input type="hidden" id="droitsView" name="droitsView" value=""/>
<input type="hidden" id="membresAffiche" name="membresAffiche" value=""/>
</form>

<script type="text/javascript">

function selectType(){
	
	if(document.getElementById('typeFeuilleU').checked==true){
		
		
		document.getElementById('labelNom').innerHTML = "nom de la feuille";
		document.getElementById('labelFonctionnement').innerHTML = "fonctionnement de la feuille";
		document.getElementById('labelVisible').innerHTML = "membres pouvant voir la feuille";
		document.getElementById('labelAffiche').innerHTML = "membres affichés sur la feuille";
		document.getElementById('labelChoixRep').innerHTML = "réponses possibles";
		

	}else{

		document.getElementById('labelNom').innerHTML = "nom du groupe";
		document.getElementById('labelFonctionnement').innerHTML = "fonctionnement des feuilles contenues dans le groupe";
		document.getElementById('labelVisible').innerHTML = "membres pouvant voir le groupe et les feuilles contenues dans le groupe";
		document.getElementById('labelAffiche').innerHTML = "membres affichés sur les feuilles contenues dans le groupe";
		document.getElementById('labelChoixRep').innerHTML = "réponses possibles";
		
	}
}



function selectVisible(){
	document.getElementById('visibleB').checked=true;
	<?php echo ((DROITS=="probatoire")?"document.getElementById('visibleP').checked=true;document.getElementById('visibleM').checked=true;":"")?>
	<?php echo ((DROITS=="membre")?"document.getElementById('visibleM').checked=true;":"")?>
	
	if(document.getElementById('visibleP').checked==true && document.getElementById('visibleM').checked==false){
		document.getElementById('visibleM').checked=true;
	}

	
	if(document.getElementById('visibleM').checked==false){
		document.getElementById('visibleP').checked=false;
	}
	
}

function submNewPresence(){


	if(document.getElementById('nom').value == ""){
		alert("Veuillez choisir un nom.");
	
	}else if(document.getElementById('afficheP').checked == false && document.getElementById('afficheM').checked == false && document.getElementById('afficheB').checked == false){
		
		alert("Veuillez ajouter des membres à afficher sur la feuille.");
	}else{
	
	
		if(document.getElementById('visibleP').checked==true){
			document.getElementById('droitsView').value = "probatoire";
		
		}else if(document.getElementById('visibleM').checked==true){
			document.getElementById('droitsView').value = "membre";
		
		}else{
			document.getElementById('droitsView').value = "bureau";
		}
		
		
		affiche = 0;
		
		if(document.getElementById('afficheP').checked){
			affiche += 1;
		}
		if(document.getElementById('afficheM').checked){
			affiche += 2;
		}
		if(document.getElementById('afficheB').checked){
			affiche += 4;
		}
		
		
		document.getElementById('membresAffiche').value = affiche;
	
		document.getElementById('submitNewPresence').disabled=true;
		document.getElementById('submitNewPresence').value = "Patientez...";
		document.getElementById('submitNewPresence').onclick="";
		document.getElementById('formNewPresence').submit();
	}
}


</script> 
<?php
echo $footer;
?>