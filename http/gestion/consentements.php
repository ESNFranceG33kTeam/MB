<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

//Verif droits
requireDroits("bureau");

define('TITRE_PAGE',"Consentements");


//addConsentement

if(isset($_POST['titre'])){
	
	
	
	if(mb_strlen($_POST['titre'])>300){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Titre</em> ne doit pas dépasser 300 caractères.'));
	}
	if(empty($_POST['titre'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Titre</em>.'));
	}
	
	
	if($_POST['cible'] != 1 && $_POST['cible'] != 2  && $_POST['cible'] != 3){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Cible</em> n\'est pas valide.'));
	}
	
	if($_POST['type'] != 0 && $_POST['type'] != 1){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Type</em> n\'est pas valide.'));
	}
	
	if($_POST['defaut'] != 0 && $_POST['defaut'] != 1){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Défaut</em> n\'est pas valide.'));
	}
	
	
	if(empty($_POST['texte'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Texte</em>.'));
	}

	
	if(mb_strlen($_POST['texteCase'])>300){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Texte de la case à cocher</em> ne doit pas dépasser 300 caractères.'));
	}
	if(empty($_POST['texteCase'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Texte de la case à cocher</em>.'));
	}
	
	
	if(empty($pageMessages)){
		$bd = db_connect();
		
		$_POST['titre'] = mysqli_real_escape_string($bd, $_POST['titre']);
		$_POST['texte'] = mysqli_real_escape_string($bd, $_POST['texte']);
		$_POST['texteCase'] = mysqli_real_escape_string($bd, $_POST['texteCase']);
						
		$addCotis = db_exec($bd, "
					INSERT INTO gestion_consentements(titre, cible, obligatoire, defaut, texte, texteCase)
					VALUES('".$_POST['titre']."', '".$_POST['cible']."', '".$_POST['type']."', '".$_POST['defaut']."', '".$_POST['texte']."', '".$_POST['texteCase']."')");
		
		db_close($bd);
		
		if($addCotis!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'La nouveau texte a bien été ajouté.'));
		}	
	}
}//fin nouveau texte


//editConsentement

if(isset($_POST['idEditTexte'])){
	
	
	
	if(mb_strlen($_POST['titreEdit'])>300){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Titre</em> ne doit pas dépasser 300 caractères.'));
	}
	if(empty($_POST['titreEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Titre</em>.'));
	}
	
	if($_POST['idEditTexte']!=0){
		
		if($_POST['cibleEdit'] != 1 && $_POST['cibleEdit'] != 2 && $_POST['cibleEdit'] != 3){
			array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Cible</em> n\'est pas valide.'));
		}
		
	}
	
	if($_POST['typeEdit'] != 0 && $_POST['typeEdit'] != 1 && $_POST['typeEdit'] != 3){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Type</em> n\'est pas valide.'));
	}
	
	if($_POST['defautEdit'] != 0 && $_POST['defautEdit'] != 1){
		array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Défaut</em> n\'est pas valide.'));
	}
	
	
	if(mb_strlen($_POST['texteCaseEdit'])>300){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Texte de la case à cocher</em> ne doit pas dépasser 300 caractères.'));
	}
	if(empty($_POST['texteCaseEdit'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Texte de la case à cocher</em>.'));
	}
	
	
	if(empty($pageMessages)){
		$bd = db_connect();
		
		$_POST['titreEdit'] = mysqli_real_escape_string($bd, $_POST['titreEdit']);
		$_POST['texteCaseEdit'] = mysqli_real_escape_string($bd, $_POST['texteCaseEdit']);
						
						
		if($_POST['idEditTexte']!=0){
			$editTexte = db_exec($bd, "
					UPDATE gestion_consentements
					SET titre='".$_POST['titreEdit']."', cible='".$_POST['cibleEdit']."', obligatoire='".$_POST['typeEdit']."', defaut='".$_POST['defautEdit']."', texteCase='".$_POST['texteCaseEdit']."'
					WHERE id='".$_POST['idEditTexte']."'");
		}else{
			$editTexte = db_exec($bd, "
					UPDATE gestion_consentements
					SET titre='".$_POST['titreEdit']."', obligatoire='".$_POST['typeEdit']."', defaut='".$_POST['defautEdit']."', texteCase='".$_POST['texteCaseEdit']."'
					WHERE id='".$_POST['idEditTexte']."'");
		}
		
		db_close($bd);
		
		if($editTexte!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Les modifications ont bien été effectuées.'));
		}	
	}
}//fin nouveau texte


//Suppr Consentement
if(isset($_POST['idSupprTexte'])){
		
	if($_POST['idSupprTexte']!=0){
		
		$bd = db_connect();
						
		$suptexte = db_exec($bd, "
					DELETE FROM gestion_consentements
					WHERE id='".$_POST['idSupprTexte']."'
					LIMIT 1");
		
		db_close($bd);
		
		if($suptexte!==false){
			array_push($pageMessages, array('type'=>'ok', 'content'=>'Le texte a bien été supprimé.'));
		}
	}	
}//fin suppr


//editcgu
if(isset($_POST['cgu'])){
		
	$bd = db_connect();
	
	$_POST['cgu'] = mysqli_real_escape_string($bd, $_POST['cgu']);
					
	$edit = db_exec($bd, "
				UPDATE gestion_config_general
				SET valeur='".$_POST['cgu']."'
				WHERE champ='cgu'");
	
	db_close($bd);
	
	if($edit!==false){
		array_push($pageMessages, array('type'=>'ok', 'content'=>'Les conditions générales de vente ont bien été modifiées.'));
	}		

}//fin edit







//récup liste textes
$bd = db_connect();
$tabChampsGen = db_tableau($bd, "SELECT id, champ, descr, valeur FROM gestion_config_general");
$tabTextes = db_tableau($bd, "SELECT id, titre, cible, obligatoire, defaut, texte, texteCase FROM gestion_consentements");
db_close($bd);





//Textes
$listeTextes="";
$listeTextesConsent="";

if($tabTextes !== false && !(empty($tabTextes))){
	for($i=0; $i<count($tabTextes); $i++){
		
		$lienAffConsent='';
		
		if($tabTextes[$i]['cible']==1){
			$cible = "Pour chaque nouvelle adhésion";
			$lienAffConsent=' / <a href="http://'.$_SERVER['HTTP_HOST'].'/gestion/affConsentements.php?idConsent='.$tabTextes[$i]['id'].'" target="_blank"">Voir la liste</a>';
			
		}elseif($tabTextes[$i]['cible']==2){
			$cible = "Pour les activités";
			
		}elseif($tabTextes[$i]['cible']==3){
			$cible = "Par défaut pour les activités";
			
		}

		if($tabTextes[$i]['obligatoire']==0){
			$obligatoire = "Facultatif";
			
		}elseif($tabTextes[$i]['obligatoire']==1){
			$obligatoire = "Obligatoire";
			
		}
		
		if($tabTextes[$i]['defaut']==0){
			$defaut = "Case non cochée par défaut";
			
		}elseif($tabTextes[$i]['defaut']==1){
			$defaut = "Case cochée par défaut";
			
		}
			
		$listeTextes.= '<tr id="trTexte-'.$tabTextes[$i]['id'].'"><td id="tdTexte-'.$tabTextes[$i]['id'].'"><a style="font-weight:bold" onclick="affTexte('.$tabTextes[$i]['id'].')">'.$tabTextes[$i]['titre'].'</a><br/>
						<div id="affTexte-'.$tabTextes[$i]['id'].'" style="display:none; border-top:dotted 1px black; border-bottom:dotted 1px black">'.bbCodeToHTML($tabTextes[$i]['texte']).'<br/></div>
						<span>'.$cible.' / '.$obligatoire.' / '.$defaut.$lienAffConsent.'</span></td>

						<td id="tdTexteEdit-'.$tabTextes[$i]['id'].'" class="edit" onclick="editTexte('.$tabTextes[$i]['id'].')"></td>'.
						
						($tabTextes[$i]['id']!=0?'<td id="tdTexteSuppr-'.$tabTextes[$i]['id'].'" class="suppr" onclick="supprTexte('.$tabTextes[$i]['id'].')"></td>':'')
						
						.'</tr>';
						
		$listeTextesConsent .= '<div id="divTextEdit-'.$tabTextes[$i]['id'].'"><label for="texteEdit-'.$tabTextes[$i]['id'].'">texte (non modifiable)</label>'
								.'<textarea id="texteEdit-'.$tabTextes[$i]['id'].'" name="texteEdit" style="box-sizing:border-box; height:120px; width:80%; resize:vertical" disabled>'.$tabTextes[$i]['texte'].'</textarea></div>';
						
		$listesTextesJS.= 'listesTextesJS['.$i.']=new Array("'.str_replace('"','\"',$tabTextes[$i]['titre']).'","'.$tabTextes[$i]['cible'].'","'.$tabTextes[$i]['obligatoire'].'","'.$tabTextes[$i]['defaut'].'","'.str_replace('"','\"',$tabTextes[$i]['texteCase']).'",'.$tabTextes[$i]['id'].');';
	}
}
$listeBoutons="";




include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
initBBCode();
?>
<div class="blocText">
Sur cette page, vous pouvez ajouter des textes de demandes de consentements, des clauses, des chartes, des décharges de responsabilités etc. pour vos adhérents.<br/>
Ces textes peuvent être associé à une activité ou pour chaque nouvelle adhésion, ils peuvent être soit obligatoires soit facultatifs pour valider l'inscription.<br/>
Les différents textes créés seront visibles sur les pages d'inscriptions via une case à cocher.
</div>


<h3>Consentements créés</h3>
<?php
if(!empty($listeTextes)){

	echo '<table>';
	echo '<tr><th>Textes</th></tr><tbody id="tbodyTextes">';
	echo $listeTextes;
	echo '</tbody></table>';
}else{
	echo '<div>Pas de données à afficher</div>';
}
?>
<form method=post action="consentement.php" id="formEditGen" style="display:none">
<input type="hidden" id="idEditGen" name="idEditGen" value=""/>
<input type="hidden" id="valueEditGen" name="valueEditGen" value=""/>
</form>

<div id="affFormEditConsentement" style="display:none">
<h3 id="h3Edit">Edition</h3>
<form method=post action="consentements.php" id="formEditConsentement">

<label for="titreEdit">titre</label>
<input type="text" id="titreEdit" name="titreEdit" style="width:78%" maxlength=300 value="" autocomplete="off"  onclick="affFormNewConsentement()"/>

<div id="divCibleEdit">
<label for="cibleEdit">cible</label>
	<input id="cibleIEdit" type="radio" name="cibleEdit" value="1" >  
	<label class="radio" for="cibleIEdit">Pour chaque nouvelle adhésion</label>  <br/>
	<input id="cibleAEdit" type="radio" name="cibleEdit" value="2">  
	<label class="radio" for="cibleAEdit">Pour certaines activités (non ajouté par défaut)</label>
	<input id="cibleADEdit" type="radio" name="cibleEdit" value="3">  
	<label class="radio" for="cibleADEdit">Pour la majorité des activités (ajouté par défaut)</label> 
</div>
	
	
<label for="typeEdit">type</label>
	<input id="typeOEdit" type="radio" name="typeEdit" value="1" >  
	<label class="radio" for="typeOEdit">Consentement obligatoire</label>  
	<input id="typeFEdit" type="radio" name="typeEdit" value="0">  
	<label class="radio" for="typeFEdit">Consentement facultatif</label>


<label for="defautEdit">défaut</label>
	<input id="defautOEdit" type="radio" name="defautEdit" value="1" >  
	<label class="radio" for="defautOEdit">Accepté par défaut (case cochée)</label>  
	<input id="defautNEdit" type="radio" name="defautEdit" value="0">  
	<label class="radio" for="defautNEdit">Non accepté par défaut (case non cochée)</label>
	
<?php echo $listeTextesConsent; ?>

<label for="texteCaseEdit">texte de la case à cocher</label>
<input type="text" id="texteCaseEdit" name="texteCaseEdit" style="width:78%" maxlength=300 value="J'accepte de/les ..." autocomplete="off"/>

<input type="hidden" id="idEditTexte" name="idEditTexte" value=""/>
<input type="button" onclick="submEditConsentement()" id="submitEditConsentement" value="valider" />

</form>
</div>

<h3>Nouvelle demande de consentement</h3>
<form method=post action="consentements.php" id="formAddConsentement">

<label for="titre">titre</label>
<input type="text" id="titre" name="titre" style="width:78%" maxlength=300 value="" autocomplete="off"  onclick="affFormNewConsentement()"/>

<div id="affFormNewConsentement" style="display:none">
<label for="cible">cible</label>
	<input id="cibleI" type="radio" name="cible" value="1" checked>  
	<label class="radio" for="cibleI">Pour chaque nouvelle adhésion</label>  <br/>
	<input id="cibleA" type="radio" name="cible" value="2">  
	<label class="radio" for="cibleA">Pour certaines activités (non ajouté par défaut)</label>
	<input id="cibleAD" type="radio" name="cible" value="3">  
	<label class="radio" for="cibleAD">Pour la majorité des activités (ajouté par défaut)</label> 

<label for="type">type</label>
	<input id="typeO" type="radio" name="type" value="1" checked>  
	<label class="radio" for="typeO">Consentement obligatoire</label>  
	<input id="typeF" type="radio" name="type" value="0">  
	<label class="radio" for="typeF">Consentement facultatif</label>


<label for="defaut">défaut</label>
	<input id="defautO" type="radio" name="defaut" value="1" checked>  
	<label class="radio" for="defautO">Accepté par défaut (case cochée)</label>  
	<input id="defautN" type="radio" name="defaut" value="0">  
	<label class="radio" for="defautN">Non accepté par défaut (case non cochée)</label>
	

<label for="texte">texte</label>
<?php addTextAreaBBCode("texte", "texte", "", ""); ?>


<label for="texteCase">texte de la case à cocher</label>
<input type="text" id="texteCase" name="texteCase" style="width:78%" maxlength=300 value="L'adhérent accepte de/les ..." autocomplete="off"/>


<input type="button" onclick="submAddConsentement()" id="submitAddConsentement" value="valider" />
</div>
</form>



<form method=post action="consentements.php" id="formSupprTexte" style="display:none">
<input type="hidden" id="idSupprTexte" name="idSupprTexte" value=""/>
</form>



<h3>Conditions générales de vente</h3>
<div class="blocText">Si vous le désirez, vous pouvez rédiger les conditions générales de vente relatives aux activités de votre association.
<br/>L'adhérent devra alors cocher une case lors de son inscription pour les accepter.</div>
<form method=post action="consentements.php" id="formEditCGU">
<?php addTextAreaBBCode("cgu", "cgu", "", $tabChampsGen[8]['valeur']); ?>
<input type="button" onclick="submMessCGU()" id="submitMessCGU" value="valider" />
</form>


<script type="text/javascript">

var listesTextesJS=new Array();
<?php echo $listesTextesJS; ?>

function affTexte(id){
	
	if(document.getElementById('affTexte-'+id).style.display == "none"){
		
		document.getElementById('affTexte-'+id).style.display = "";
		
	}else{
		document.getElementById('affTexte-'+id).style.display = "none";
	}
	
}

function editTexte(id){
	
	if(document.getElementById('tdTexteEdit-'+id).className == "edit"){
		
		var ligneTexte = 0;
		var tbodyTextes = document.getElementById('tbodyTextes').childNodes;
		for(ligne=0; ligne<(tbodyTextes.length); ligne++){
			
			if(tbodyTextes[ligne].id != "trTexte-"+id){
				tbodyTextes[ligne].style.display="none";
			
			}else{
				ligneTexte = ligne;
				
			}
		}
		
		//Verif possibilité edit 
		if(id==0){
			document.getElementById('divCibleEdit').style.display = "none"
		}
		
		
		//MAJ champs edit
		
		
		document.getElementById('idEditTexte').value = id;
		
		document.getElementById('h3Edit').innerHTML = "Edition : " + listesTextesJS[ligneTexte][0];
		document.getElementById('titreEdit').value = listesTextesJS[ligneTexte][0];
		
		if(listesTextesJS[ligneTexte][1]==1){
			
			document.getElementById('cibleIEdit').checked = true;
			
		}else if(listesTextesJS[ligneTexte][1]==2){
			
			document.getElementById('cibleAEdit').checked = true;
			
		}else if(listesTextesJS[ligneTexte][1]==3){
			
			document.getElementById('cibleADEdit').checked = true;
			
		}
		
		
		if(listesTextesJS[ligneTexte][2]==0){
			
			document.getElementById('typeFEdit').checked = true;
			
		}else if(listesTextesJS[ligneTexte][2]==1){
			
			document.getElementById('typeOEdit').checked = true;
			
		}
		
		
		if(listesTextesJS[ligneTexte][3]==0){
			
			document.getElementById('defautNEdit').checked = true;
			
		}else if(listesTextesJS[ligneTexte][3]==1){
			
			document.getElementById('defautOEdit').checked = true;
			
		}
	
		
		for(c=0; c<(listesTextesJS.length); c++){
			
			document.getElementById('divTextEdit-'+listesTextesJS[c][5]).style.display="none";
	
		}
		document.getElementById('divTextEdit-'+id).style.display="";
		
	
		document.getElementById('texteCaseEdit').value = listesTextesJS[ligneTexte][4];
		
		
		document.getElementById('affFormEditConsentement').style.display = "";
		document.getElementById('tdTexteEdit-'+id).className = "edit orange";
		document.getElementById('tdTexte-'+id).className = "orange";
		
	}else{
		
		
		var tbodyTextes = document.getElementById('tbodyTextes').childNodes;
		for(ligne=0; ligne<(tbodyTextes.length); ligne++){
			
			tbodyTextes[ligne].style.display="";
	
		}
		
		document.getElementById('affFormEditConsentement').style.display = "none";
		document.getElementById('tdTexteEdit-'+id).className = "edit";
		document.getElementById('tdTexte-'+id).className = "";
		
	}
}

function submEditConsentement(){
	
	ok = true;

	if(document.getElementById('titreEdit').value == ""){
		alert("Veuillez remplir le champ Titre");
		ok = false;
	}

	if(document.getElementById('texteCaseEdit').value == ""){
		alert("Veuillez remplir le champ Texte de la case à cocher");
		ok = false;
	}
	
	
	if (ok){
		document.getElementById('submitEditConsentement').disabled=true;
		document.getElementById('submitEditConsentement').value = "Patientez...";
		document.getElementById('submitEditConsentement').onclick="";
		document.getElementById('formEditConsentement').submit();
	}
}

function affFormNewConsentement(){
	document.getElementById('affFormNewConsentement').style.display = "";
}

function submAddConsentement(){
	
	ok = true;

	if(document.getElementById('titre').value == ""){
		alert("Veuillez remplir le champ Titre");
		ok = false;
	}
	if(document.getElementById('texte').value == ""){
		alert("Veuillez remplir le champ Texte");
		ok = false;
	}
	if(document.getElementById('texteCase').value == ""){
		alert("Veuillez remplir le champ Texte de la case à cocher");
		ok = false;
	}
	
	
	if (ok){
		document.getElementById('submitAddConsentement').disabled=true;
		document.getElementById('submitAddConsentement').value = "Patientez...";
		document.getElementById('submitAddConsentement').onclick="";
		document.getElementById('formAddConsentement').submit();
	}
}

function supprTexte(id){
	if(confirm("Voulez-vous vraiment supprimer ce texte ? \n La liste des personnes ayant accepté les clauses du texte sera aussi supprimée.")){
		document.getElementById('tdTexteSuppr-'+id).onclick="";
		document.getElementById('idSupprTexte').value = id;
		document.getElementById('formSupprTexte').submit();
	}
}


function submMessCGU(){

	document.getElementById('submitMessCGU').disabled=true;
	document.getElementById('submitMessCGU').value = "Patientez...";
	document.getElementById('submitMessCGU').onclick="";
	document.getElementById('formEditCGU').submit();
}

</script> 
<?php
echo $footer;
?>