<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');

//Verif droits
requireDroits("membre");

$idOk=false;
$valChamps = array("","jj-mm-aaaa","--:--","normale","","","unlimited","","","gratuit","","",0);

define('TITRE_PAGE',"Edition");

//Verif possibilité inscr libres payantes

if($tabChamps['actLibrePayant']['valeur'] == "Oui"){

	$infosInscrLibres = "Une inscription via Internet permet aux adhérents de s'inscrire grâce à un lien à leur communiquer.&#10;Attention ! Pour les activités payantes, l'adhérent devra vous payer après son inscription.";

}else{

	$infosInscrLibres = "Une inscription via Internet permet aux adhérents de s'inscrire grâce à un lien à leur communiquer.&#10;Le site est actuellement configuré pour ne pas accepter les inscriptions via Internet pour une activité payante.";
}




if(isset($_POST['idEdit'])||isset($_POST['idE'])){

	$idEdit=(isset($_POST['idEdit']))?$_POST['idEdit']:$_POST['idE'];
	$bd = db_connect();
		
	$infosAct = db_ligne($bd, "
						SELECT nom, dte, tme, spots, spotsResESN, spotsSold, prix, paiementStatut, infos, code, consent
						FROM activity_activities
						WHERE id='".$idEdit."'");	
	//Des inscrits ?
	$nbInscr = db_valeur($bd, "		
						SELECT COUNT(idAct)
						FROM activity_participants
						WHERE idAct='".$idEdit."'");	
						
	
	$nbInscrESN = db_valeur($bd, "		
						SELECT COUNT(idESN)
						FROM activity_participants
						WHERE idAct='".$idEdit."' AND idESN!='' AND fullPaid!='-1' AND listeAttente='0'");
						
	$tabOptions = db_tableau($bd, "
						SELECT opt, prixOpt
						FROM activity_options
						WHERE idAct='".$idEdit."'");	
						
						
				
	$consentements = db_tableau($bd, "		
					SELECT id, titre, cible, obligatoire
					FROM gestion_consentements
					WHERE cible=2 OR cible=3
					ORDER BY id ASC");
			
db_close($bd);






	if($infosAct!==false && !empty($infosAct) && $nbInscr!==false && $nbInscrESN!==false){
		$idOk=true;
		
		$spotsSold = explode('//',$infosAct['spotsSold'],2);	
		
		$isInscr=(intval($nbInscr)>0)?true:false; //Servira pour bloquer la modif du prix si il y a des inscrits
		$isFree=(intval($infosAct['prix'])>0)?false:true;
		
		$valChamps = array($infosAct['nom'],$infosAct['dte'],$infosAct['tme'], $infosAct['infos'], (empty($infosAct['code']))?"normale":"libre",
			$infosAct['spots'],(intval($infosAct['spots'])>0)?"limited":"unlimited", $infosAct['spotsResESN'],
			$infosAct['prix'],(intval($infosAct['prix'])>0)?"payant":"gratuit",$idEdit);
			
			
		//Passage à Gratuit d'une activité payante si inscriptions via Internet désactivée entre temps pour les activités payantes
		
		if($valChamps[4] == "libre" && $tabChamps['actLibrePayant']['valeur'] != "Oui"){
			$valChamps[9] = "gratuit";
		}
		
		
		//Mise en forme options
		for($i=0; $i<count($tabOptions); $i++){

			
			if($tabOptions[$i]['prixOpt'] > 0 ){
				$textPrix = "Supplément : " .$tabOptions[$i]['prixOpt']. "€";
				
			}else if($tabOptions[$i]['prixOpt'] < 0){
				$textPrix = "Réduction : " .$tabOptions[$i]['prixOpt']. "€";
				
			}else{
				$textPrix = "Option gratuite";
			}
			
			$valChamps[11] .= '<tr id="option'.$i.'">'.
								'<td>'.$tabOptions[$i]['opt'].'</td>'.
								'<td class="prix:'.$tabOptions[$i]['prixOpt'].'">' .$textPrix. '</td>'.
								((!$isInscr)?'</td><td id="editOption'.$i.'" class="edit" onclick="editOption('.$i.')"></td></td><td class="remove" onclick="supOption('.$i.')"></td>':'').
								'</tr>';

			$valChamps[12] = $i+1;

		}
		
		$isEditOption = ((!$isInscr && count($tabOptions) > 0)?true:false);


		//Consentements
		$listeConsentements="";
		$selectConsentJS="";
		

		if($consentements!==false && !empty($consentements)){

			for($i=0; $i<count($consentements); $i++){	
				$listeConsentements.='<tr id="trAssocConsent-'.$consentements[$i]['id'].'"><td style="width:450px" ><span class="'.(($consentements[$i]['obligatoire'])?'required':'').'">'.$consentements[$i]['titre'].'</span></td>
				<td id="tdAssocConsent-'.$consentements[$i]['id'].'" class="checkN" onclick="selectConsent('.$consentements[$i]['id'].')"></td>
				</tr>';
			}
			
			if(!empty($infosAct['consent'])){
	
				$tabConsent = explode('///',$infosAct['consent'],-1);

				for($i=0; $i<count($consentements); $i++){	
					if(in_array($consentements[$i]['id'],$tabConsent)){
						$selectConsentJS.="selectConsent(".$consentements[$i]['id'].");";
					
					}
				}
			}		
			
		}

	}

	
	if($idOk && count($_POST)>3){
		//Définition des messages d'erreurs
		$nomChamps=array("Nom de l'activité","Date de l'activité","Heure de rendez-vous", "Informations");
		$maxChamps = array(100,10,5,9999);
		$requiredChamps = array(true,true,false,true);
		
		$valChamps = array($_POST['nameAct'],$_POST['dateAct'],$_POST['timeAct'],$_POST['infos'], ((isset($_POST['typeAct']))?$_POST['typeAct']:((empty($infosAct['code']))?"normale":"libre")),$_POST['spots'],$_POST['spotsAct'],
							(isset($_POST['spotsESN']))?$_POST['spotsESN']:$infosAct['spotsResESN'],
							(isset($_POST['prix']))?$_POST['prix']:$infosAct['prix'],((isset($_POST['prixAct']))?$_POST['prixAct']:((intval($infosAct['prix'])>0)?"payant":"gratuit")),$idEdit);
		
		for($i=0; $i<count($nomChamps); $i++){
			if($requiredChamps[$i] && empty($valChamps[$i])){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>'.$nomChamps[$i].'</em>.'));
			}
			if(mb_strlen($valChamps[$i])>$maxChamps[$i]){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>'.$nomChamps[$i].'</em> ne doit pas dépasser '.$maxChamps[$i].' caractères.'));
			}	
		}
		
		if(!empty($_POST['dateAct'])){
			$dte = date_parse($_POST['dateAct']);
			if (!checkdate($dte['month'], $dte['day'], $dte['year'])) {
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[1].'</em> n\'est pas valide.'));
			}else{
				$dateAct = $dte['year'].'-'.$dte['month'].'-'.$dte['day'];
			}	
		}

		if(!empty($_POST['timeAct'])&&$_POST['timeAct']!="--:--"){
			$tme = $_POST['timeAct'];
			if (!preg_match("/(2[0-3]|[01][0-9]):[0-5][0-9]/", $tme)){
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>'.$nomChamps[2].'</em> n\'est pas valide.'));
			}	
		}else{
			$tme=null;
		}
		
		if($_POST['spotsAct']=="limited"){
			if(empty($_POST['spots'])){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Nombre total de places</em>.'));
			}
			if(mb_strlen($_POST['spots'])>3){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Nombre total de places</em> ne doit pas dépasser 3 caractères.'));
			}
			if(mb_strlen($_POST['spotsESN'])>3){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Places réservées</em> ne doit pas dépasser 3 caractères.'));
			}
			
			if (!empty($_POST['spots']) && (!is_numeric($_POST['spots'])||$_POST['spots']<1)){
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Nombre total de places</em> n\'est pas valide.'));
			}
			
			if (!empty($_POST['spotsESN']) && (!is_numeric($_POST['spotsESN'])||$_POST['spotsESN']<1)){
				array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Places réservées</em> n\'est pas valide.'));
			}
			
			if(!empty($_POST['spots']) && !empty($_POST['spotsESN'])&&is_numeric($_POST['spots'])&&is_numeric($_POST['spotsESN'])&&$_POST['spotsESN']>$_POST['spots']){
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Places réservées</em> ne doit pas dépasser le nombre total de places.'));
			}

			$spots=$_POST['spots'];
			$spotsESN=$_POST['spotsESN'];
			$updateSpotsESN = 0;
			//Verifs places restantes
			
			if(empty($pageMessages)){//verif is numeric,..OK
			
				if($spotsSold[0] > $spots){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Impossible de réduire le nombre de places. '.$spotsSold[0].' personnes sont déjà inscrites.'));
				}
				
				if($spotsESN<$infosAct['spotsResESN'] && $infosAct['spotsResESN'] > $nbInscrESN){ //On libère des places ESN
					
					$updateSpotsESN = max(($spotsESN-$infosAct['spotsResESN']), ($nbInscrESN-$infosAct['spotsResESN']));
				
				
				}elseif($spotsESN>$infosAct['spotsResESN'] && $spotsESN > $nbInscrESN){ //on reserve davantage de places ESN
				
					$updateSpotsESN = min(($spotsESN-$nbInscrESN), ($spotsESN-$infosAct['spotsResESN']));
					
					//verif assez de places dispo
					if($spotsSold[0] + $updateSpotsESN > $spots){
						array_push($pageMessages, array('type'=>'err', 'content'=>'Impossible d\'augmenter le nombre de places reservées. '.($spotsSold[0]-(max($infosAct['spotsResESN'], $nbInscrESN))).' adhérents sont déjà inscrits.'));
					}
				}
			}
		
		}else{ //Si places illimités
			$spots=0;
			$spotsESN=0;
			//Verif s'il y avait des places ESN réservées avant
			if($nbInscrESN < $infosAct['spotsResESN']){
				$updateSpotsESN = ($nbInscrESN-$infosAct['spotsResESN']);
			}else{
				$updateSpotsESN = 0;
			}
			
		}
		
		if(!$isInscr && isset($_POST['prixAct'])&& isset($_POST['typeAct'])){
			if($_POST['prixAct']=="payant" && ($_POST['typeAct']=="normale" || ($_POST['typeAct']=="libre" && $tabChamps['actLibrePayant']['valeur'] == "Oui"))){
				if(empty($_POST['prix'])){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Veuillez remplir le champ <em>Prix</em>.'));
				}
				if(mb_strlen($_POST['prix'])>7){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Le champ <em>Prix</em> ne doit pas dépasser 7 caractères.'));
				}
			
				if (!empty($_POST['prix']) && (!is_numeric($_POST['prix'])||$_POST['prix']<1)){
					array_push($pageMessages, array('type'=>'err', 'content'=>'La valeur donnée dans le champ <em>Prix</em> n\'est pas valide.'));
				}
				
				$prix=$_POST['prix'];
			}else{
				$prix=0;
			}
		}else{
			$prix=$infosAct['prix'];
		}
		
		
		
		//Verif options
				
		$nbOptExistantes = count($tabOptions);
				
		
		$tabOptions = explode('///',$_POST['lstOptions'],-1);
		
		
		for($i=0; $i<count($tabOptions); $i++){

			$option = explode('@@',$tabOptions[$i],2);
		
			
			if(empty($option[0])&&!is_numeric($option[0])){ //Verif nom option
				array_push($pageMessages, array('type'=>'err', 'content'=>'Le nom d\'une option est vide.'));
				
			}elseif(mb_strlen($option[0])>200){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Le nom d\'une option ne doit pas dépasser 200 caractères.'));
					
			
			}else{//Verif prix option
			
				if(mb_strlen($option[1])>7){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Le prix de l\'option <em>'.$option[0].'</em> ne doit pas dépasser 7 caractères.'));
				}
				
				if ($option[1] <> 0 && (empty($option[1]) || !is_numeric($option[1]))){
					
					array_push($pageMessages, array('type'=>'err', 'content'=>'Le prix de l\'option <em>'.$option[0].'</em> est invalide.'));
				
				}elseif(($option[1] + $prix) < 0){
					
					array_push($pageMessages, array('type'=>'err', 'content'=>'La réduction de l\'option <em>'.$option[0].'</em> est supérieure au prix de l\'activité.'));
				
				}elseif($option[1] > 0 && $_POST['typeAct']=="libre" && $tabChamps['actLibrePayant']['valeur'] != "Oui"){
					
					array_push($pageMessages, array('type'=>'err', 'content'=>'L\'option <em>'.$option[0].'</em> ne peut pas être payante pour une activité obligatoirement gratuite.'));
					
				}
				
			}
		
			//Construction tableaux
			
			$tabOptions[$i] = array($option[0], $option[1]);

			
			if($option[1] > 0 ){
				$textPrix = "Supplément : " .$option[1]. "€";
				
			}else if($option[1] < 0){
				$textPrix = "Réduction : " .$option[1]. "€";
				
			}else{
				$textPrix = "Option gratuite";
			}
			
			$valChamps[11] .= '<tr id="option'.$i.'">'.
								'<td>'.$option[0].'</td>'.
								'<td class="prix:'.$option[1].'">' .$textPrix. '</td>'.
								((!$isInscr||$i >= $nbOptExistantes)?'</td><td class="remove" onclick="supOption('.$i.')"></td>':'').'</tr>';
								
			$valChamps[12] = $i+1;

		}
		
		
		//Consentements
		$selectConsentJS="";
		
		if(!empty($_POST['lstConsent'])){
			
			$tabConsent = explode('///',$_POST['lstConsent'],-1);

			if($consentements!==false && !empty($consentements)){

				for($i=0; $i<count($consentements); $i++){	
					if(in_array($consentements[$i]['id'],$tabConsent)){
						$selectConsentJS.="selectConsent(".$consentements[$i]['id'].");";
					
					}
				}
			}
			

			for($i=0; $i<count($tabConsent); $i++){	
				if(!is_numeric($tabConsent[$i])){
					array_push($pageMessages, array('type'=>'err', 'content'=>'Consentement invalide'));
				}
			}
		}
		
		
		
		
		
		
		if(empty($pageMessages)){ //si pas d'erreur : go pour ajout

			$bd = db_connect();

			$_POST['nameAct'] = mysqli_real_escape_string($bd, $_POST['nameAct']);
			$_POST['infos'] = mysqli_real_escape_string($bd, $_POST['infos']);
			$_POST['lstConsent'] = mysqli_real_escape_string($bd, $_POST['lstConsent']);
			
			$spotsSold[0] += $updateSpotsESN;
			
			if(($isFree||!$isInscr||$tabChamps['actLibrePayant']['valeur'] == "Oui") && isset($_POST['typeAct'])){
				if($_POST['typeAct']=="normale"){
					$code = null;
				}elseif(empty($infosAct['code'])){
					$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					$code = '';
					srand();
					for ($i = 0; $i < 10; $i++) {
						$code .= $characters[rand(0, strlen($characters) - 1)];
					}
				}else{
					$code=$infosAct['code'];
				}
			}else{
				$code=$infosAct['code'];
			}
			
			$addAct = db_exec($bd, "
							UPDATE activity_activities
							SET nom='".$_POST['nameAct']."', dte='".$dateAct."', tme='".$tme."', spots='".$spots."', 
							spotsResESN='".$spotsESN."', spotsSold='".$spotsSold[0]."//".$spotsSold[1]."', prix='".$prix."', 
							infos='".$_POST['infos']."', code='".$code."', consent='".$_POST['lstConsent']."'
							WHERE id='".$idEdit."'");
				
			//Si pas d'inscrit on reactualise entierement la liste d'options en supprimant l'ancienne.
			
			if(!$isInscr){
				$supOptions = db_exec($bd, "
						DELETE FROM activity_options
						WHERE idAct='".$idEdit."'");
						
									
				if($supOptions === false){
					die("Erreur SQL.");
				}
			}
				
			for($i=0; $i<count($tabOptions); $i++){
		
				$tabOptions[$i][0] = mysqli_real_escape_string($bd, $tabOptions[$i][0]);
				
				if(!$isInscr||$i >= $nbOptExistantes){ //Verif ajout uniquement des nouvelles options
					$addOpt	= db_exec($bd, "
										INSERT INTO activity_options(idAct, opt, prixOpt) 
										VALUES(".$idEdit.",'".$tabOptions[$i][0]."','".$tabOptions[$i][1]."')");
					
					if($addOpt === false){
						die("Erreur SQL.");
					}
				}
			}	

			db_close($bd);
			if($addAct!==false){
				array_push($_SESSION['postMessages'],array("ok", "Les modifications ont bien été effectuées."));
				header('Location: http://'.$_SERVER['HTTP_HOST'].'/activity/index.php');
				die();
			}
		}
	}
}else{
	array_push($pageMessages, array('type'=>'err', 'content'=>'Impossible d\'accéder aux informations.'));
}//fin verif post



include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');
initBBCode();
?>

<?php if($idOk){ ?>
<form method=post id="formAct" action="editAct.php">

<label for="nameAct" class="required">nom de l'activité</label>
<input type="text" id="nameAct" name="nameAct" maxlength=100 value="<?php echo $valChamps[0]; ?>" autocomplete="off"/>

<table class="invisible"><tbody><tr><td>
<label for="dateAct" class="required">date de l'activité</label>
<input type="date" id="dateAct" name="dateAct" maxlength=10 value="<?php echo $valChamps[1]; ?>" autocomplete="off"/>
</td><td>
<label for="timeAct">heure de rendez-vous</label>
<input type="time" id="timeAct" name="timeAct" style="width:130px" maxlength=5 value="<?php echo $valChamps[2]; ?>" autocomplete="off"/>
</td></tr></tbody></table>

<label for="typeAct" <?php echo($isFree||!$isInscr)?'class=required':''?>>inscriptions possibles via internet</label>
<?php if($isFree||!$isInscr||$tabChamps['actLibrePayant']['valeur'] == "Oui"){ ?>
	<input id="typeActN" type="radio" name="typeAct" value="normale" onclick="canBeFree()">  
	<label class="radio" for="typeActN" onclick="canBeFree()">Non</label>  
	<input id="typeActO" type="radio" name="typeAct" value="libre" onclick="mustBeFree()">  
	<label class="radio" for="typeActO" onclick="mustBeFree()">Oui</label><img class="info" src="../template/images/information.png" title="<?php echo $infosInscrLibres;?>"/>
<?php }elseif(!$isFree){ ?>
	<div style="font-size:11px">Modification impossible tant que l'activité est payante.</div>
	<input id="typeActN" type="radio" name="typeAct" value="normale" disabled style="cursor:default">  
	<label class="radio" for="typeActN" style="cursor:default">Non</label>  
	<input id="typeActO" type="radio" name="typeAct" value="libre" disabled style="cursor:default">  
	<label class="radio" for="typeActO" style="cursor:default">Oui</label>
<?php } ?>

<table class="invisible"><tbody><tr><td>
<label for="spots" class="required">nombre total de places</label>
	<input type="text" id="spots" name="spots" onclick="limit()" style="width:50px" maxlength=3 value="<?php echo $valChamps[5]; ?>" autocomplete="off"/>
	<input id="spotsL" type="radio" name="spotsAct" value="limited">
	<input id="spotsU" type="radio" name="spotsAct" value="unlimited" onclick="illimit()">  
	<label class="radio" for="spotsU" onclick="illimit()">Illimité</label>
</td><td>
<label for="spots">places reservées esn</label>
<input type="text" id="spotsESN" name="spotsESN" style="width:50px" maxlength=3 value="<?php echo $valChamps[7]; ?>" autocomplete="off"/><img class="info" src="../template/images/information.png" title="Les places réservées seront décomptées du nombre total de places renseigné dans la case précédente.&#10;Il est utile de réserver des places pour les ESNer afin qu'elles ne soient pas vendues aux adhérents."/>
</td></tr></tbody></table>

<label for="prix" <?php echo(!$isInscr)?'class=required':''?>>prix</label>
<?php if(!$isInscr){ ?>
	<input type="text" id="prix" name="prix" class="euro" onclick="payant()" style="width:50px" maxlength=7 value="<?php echo $valChamps[8]; ?>" autocomplete="off"/>
	<input id="prixP" type="radio" name="prixAct" value="payant" onclick="payant()">
	<input id="prixG" type="radio" name="prixAct" value="gratuit" onclick="gratuit()">  
	<label class="radio" for="prixG" onclick="gratuit()">Gratuit</label>
<?php }else{ echo'<div style="font-size:11px">Modification impossible tant qu\'il y a des personnes inscrites.</div>'.
'<input type="text" id="prix" name="prix" class="euro" disabled style="width:50px" maxlength=7 value="'.$valChamps[8].'" autocomplete="off"/>';} ?>


<label for="inputNewOption">options : repas, activités supplémentaires, locations, lieux de rendez-vous, ...</label>
<?php echo (($isInscr)?'<div style="font-size:11px">Modification des options existantes impossible tant qu\'il y a des personnes inscrites.</div>':''); ?>
	<table style="width:660px">
	<thead><th>Options</th><th <?php echo (($isEditOption)?'colspan=2':''); ?>>Différence de prix</th></thead>
	<tbody id="tbodyOptions"><?php echo $valChamps[11]; ?></tbody>
	<tr>
	<td style="width:450px">
		Nouvelle option : <input type="text" id="inputNewOption" maxlength=200 style="margin:0; box-sizing:border-box; height:inherit; width:320px"/>
	</td>
	<td <?php echo (($isEditOption)?'colspan=2':''); ?>>
		( + ou - ) &nbsp;<input type="text" id="inputNewOptionPrix" maxlength=7 onclick="resetNewOptionPrix()" class="euro" value ="0" style="margin:0; box-sizing:border-box; height:inherit; width:80px"/>
	</td>

	<td id="tdAddOption" class="add" onclick="addOption(<?php echo $valChamps[12];?>)"></td>
	</tr>
	</table>
<br/>


<label for="infos" class="required">informations : programme, inclus dans le prix, lieu de rendez-vous, prix esn, ...</label>
<?php addTextAreaBBCode("infos", "infos", "", $valChamps[3]) ?>



<?php if(!empty($listeConsentements)){ ?>

<label for="consentAct">demandes de consentements liées à l'activité</label>
	<table style="width:650px">
	<thead><th>Textes</th><th>Associer</th></thead>
	<tbody id="tbodyConsent"><?php echo $listeConsentements ?></tbody>
	</table>
<br/>

<?php } ?>



<input type="hidden" id="idE" name="idE" value="<?php echo $valChamps[10]; ?>" />
<input type="hidden" id="lstOptions" name="lstOptions" value=""/>
<input type="hidden" id="lstConsent" name="lstConsent" value=""/>
<input type="button" onclick="submAct()" id="submitAct" value="valider" />
</form>


<script type="text/javascript">

//Select consent
<?php echo $selectConsentJS ?>

<?php if($isFree||!$isInscr||$tabChamps['actLibrePayant']['valeur'] == "Oui"){ ?>
	if("<?php echo $valChamps[4];?>"=="normale"){
		canBeFree();

	}else{
		mustBeFree();
	}
<?php }else{ ?>

	if("<?php echo $valChamps[4];?>"=="normale"){
		document.getElementById('typeActN').checked=true;
		document.getElementById('typeActO').checked=false;

	}else{
		document.getElementById('typeActN').checked=false;
		document.getElementById('typeActO').checked=true;
	}

<?php } ?>

if("<?php echo $valChamps[6];?>"=="unlimited"){
	illimit();
}else{
	limit();
	document.getElementById('spots').value="<?php echo $valChamps[5];?>";
	document.getElementById('spotsESN').value="<?php echo $valChamps[7];?>";
}

<?php if(!$isInscr){ ?>
if("<?php echo $valChamps[9];?>"=="payant"){
	payant();
	document.getElementById('prix').value="<?php echo $valChamps[8];?>";
}else{
	gratuit();
}
<?php } ?>

<?php if($isFree||!$isInscr||$tabChamps['actLibrePayant']['valeur'] == "Oui"){ ?>
function mustBeFree(){
	<?php if(!$isInscr && $tabChamps['actLibrePayant']['valeur'] != "Oui"){ ?>
	document.getElementById('prix').disabled=true;
	document.getElementById('prix').value=0;
	document.getElementById('prixP').checked=false;
	document.getElementById('prixG').checked=true;
	<?php } ?>
	document.getElementById('typeActO').checked=true;
	document.getElementById('typeActN').checked=false;
}
function canBeFree(){
	<?php if(!$isInscr && $tabChamps['actLibrePayant']['valeur'] != "Oui"){ ?>
	document.getElementById('prix').disabled=false;
	if(document.getElementById('prixG').checked==true){
		document.getElementById('prix').value=0;
	}else{
		document.getElementById('prix').value="";
	}
	<?php } ?>
	document.getElementById('typeActO').checked=false;
	document.getElementById('typeActN').checked=true;
}	
<?php } ?>

function limit(){
	document.getElementById('spotsL').checked=true;
	document.getElementById('spotsU').checked=false;
	document.getElementById('spotsESN').disabled=false;
	document.getElementById('spots').value="";
	document.getElementById('spotsESN').value=0;
}
function illimit(){
	document.getElementById('spotsL').checked=false;
	document.getElementById('spotsU').checked=true;
	document.getElementById('spots').value="";
	document.getElementById('spotsESN').value="";
	document.getElementById('spotsESN').disabled=true;
}
<?php if(!$isInscr){ ?>
function payant(){
	document.getElementById('prixP').checked=true;
	document.getElementById('prixG').checked=false;
	document.getElementById('prix').value="<?php echo $valChamps[8];?>";
}
function gratuit(){
	document.getElementById('prixP').checked=false;
	document.getElementById('prixG').checked=true;
	document.getElementById('prix').value=0;
}
<?php } ?>

function resetNewOptionPrix(){
	document.getElementById('inputNewOptionPrix').value= "";
}

function addOption(i){

	if(document.getElementById('inputNewOption').value!= "" && !isNaN(document.getElementById('inputNewOptionPrix').value)){
		
		if((parseFloat(document.getElementById('inputNewOptionPrix').value) + parseFloat(document.getElementById('prix').value) >= 0) && !(parseFloat(document.getElementById('inputNewOptionPrix').value) > 0 && document.getElementById('typeActO').checked==true && <?php echo(($tabChamps['actLibrePayant']['valeur'] != "Oui")?"true":"false") ?>)){
				
				
			if(parseFloat(document.getElementById('inputNewOptionPrix').value) > 0 ){
				textPrix = "Supplément : " + parseFloat(document.getElementById('inputNewOptionPrix').value)+"€";
				
			}else if(parseFloat(document.getElementById('inputNewOptionPrix').value) < 0){
				textPrix = "Réduction : " + parseFloat(document.getElementById('inputNewOptionPrix').value)+"€";
				
			}else{
				textPrix = "Option gratuite";
			}
			
			document.getElementById('tbodyOptions').innerHTML += '<tr id="option'+i+'">' +
													'<td>'+document.getElementById('inputNewOption').value+'</td>'+
													'<td class="prix:'+parseFloat(document.getElementById('inputNewOptionPrix').value)+'">' + textPrix + '</td>'+
													'<td class="remove" onclick="supOption('+i+')"></td></tr>';
													
			document.getElementById('inputNewOption').value = "";
			document.getElementById('inputNewOptionPrix').value = "0";
			document.getElementById('tdAddOption').onclick = function(){addOption(i+1)};
			
			document.getElementById('inputNewOption').focus();
			
		}else if(parseFloat(document.getElementById('inputNewOptionPrix').value) + parseFloat(document.getElementById('prix').value) < 0){
			
			alert("La réduction ne peut pas être plus élevée que le prix de base de l'activité.");
			document.getElementById('inputNewOptionPrix').focus();
			
		}else if(parseFloat(document.getElementById('inputNewOptionPrix').value) > 0 && document.getElementById('typeActO').checked==true && <?php echo(($tabChamps['actLibrePayant']['valeur'] != "Oui")?"true":"false") ?>){
			
			alert("Il n'est pas possible d'ajouter des options payantes pour une activité obligatoirement gratuite.");
			document.getElementById('inputNewOptionPrix').focus();
			
		}else{
			alert("Prix incorrect. Info : utilisez le point pour ajouter des décimales.");
			document.getElementById('inputNewOptionPrix').focus();
		}


	}else if(document.getElementById('inputNewOption').value== ""){
		document.getElementById('inputNewOption').focus();
		
	}else if(isNaN(document.getElementById('inputNewOptionPrix').value)){
		
		alert("Prix incorrect. Info : utilisez le point pour ajouter des décimales.");
		document.getElementById('inputNewOptionPrix').focus();
		
	}

}

<?php if(!$isInscr){ ?>
function editOption(i){
	document.getElementById('option'+i).childNodes[1].innerHTML =
	'( + ou - ) &nbsp;<input type="text" id="inputOptionPrix'+i+'" maxlength=7 onclick="resetNewOptionPrix()" class="euro" value ="'+document.getElementById('option'+i).childNodes[1].className.replace("prix:","")+'" style="margin:0; box-sizing:border-box; height:inherit; width:80px"/>';
	
	document.getElementById('editOption'+i).className = "tick";
	document.getElementById('editOption'+i).onclick=function(){valEditOption(i)};

	document.getElementById('inputOptionPrix'+i).focus();
	document.getElementById('inputOptionPrix'+i).select();
}



function valEditOption(i){

	if(!isNaN(document.getElementById('inputOptionPrix'+i).value)){
		
		if((parseFloat(document.getElementById('inputOptionPrix'+i).value) + parseFloat(document.getElementById('prix').value) >= 0) && !(parseFloat(document.getElementById('inputOptionPrix'+i).value) > 0 && document.getElementById('typeActO').checked==true && <?php echo(($tabChamps['actLibrePayant']['valeur'] != "Oui")?"true":"false") ?>)){
				
				
			if(parseFloat(document.getElementById('inputOptionPrix'+i).value) > 0 ){
				textPrix = "Supplément : " + parseFloat(document.getElementById('inputOptionPrix'+i).value)+"€";
				
			}else if(parseFloat(document.getElementById('inputOptionPrix'+i).value) < 0){
				textPrix = "Réduction : " + parseFloat(document.getElementById('inputOptionPrix'+i).value)+"€";
				
			}else{
				textPrix = "Option gratuite";
			}
			
			document.getElementById('option'+i).childNodes[1].className = "prix:"+parseFloat(document.getElementById('inputOptionPrix'+i).value);										
			document.getElementById('option'+i).childNodes[1].innerHTML = textPrix;
										
										
			document.getElementById('editOption'+i).className = "edit";
			document.getElementById('editOption'+i).onclick = function(){editOption(i)};
			
			
		}else if(parseFloat(document.getElementById('inputOptionPrix'+i).value) + parseFloat(document.getElementById('prix').value) < 0){
			
			alert("La réduction ne peut pas être plus élevée que le prix de base de l'activité.");
			document.getElementById('inputOptionPrix'+i).focus();
			
		}else if(parseFloat(document.getElementById('inputOptionPrix'+i).value) > 0 && document.getElementById('typeActO').checked==true && <?php echo(($tabChamps['actLibrePayant']['valeur'] != "Oui")?"true":"false") ?>){
			
			alert("Il n'est pas possible d'ajouter des options payantes pour une activité obligatoirement gratuite.");
			document.getElementById('inputOptionPrix'+i).focus();
			
		}else{
			alert("Prix incorrect. Info : utilisez le point pour ajouter des décimales.");
			document.getElementById('inputOptionPrix'+i).focus();
		}


	}else if(isNaN(document.getElementById('inputOptionPrix'+i).value)){
		
		alert("Prix incorrect. Info : utilisez le point pour ajouter des décimales.");
		document.getElementById('inputOptionPrix'+i).focus();
		
	}

}

<?php } ?>

	
function supOption(i){
	document.getElementById('option'+i).parentNode.removeChild(document.getElementById('option'+i));
	document.getElementById('inputNewOption').focus();
}


function selectConsent(id){
	
	if(document.getElementById('tdAssocConsent-'+id).className=="checkN"){
		document.getElementById('trAssocConsent-'+id).className="selected";
		document.getElementById('tdAssocConsent-'+id).className="checkO";
		
	}else{
		
		document.getElementById('trAssocConsent-'+id).className="";
		document.getElementById('tdAssocConsent-'+id).className="checkN";
		
	}	
}


function submAct(){
	
	//construction liste des choix
	var tbodyOptions = document.getElementById('tbodyOptions').childNodes;

	for(i=0; i<(tbodyOptions.length); i++){
		option = tbodyOptions[i].childNodes[0].innerHTML.replace("///"," ");
		prix = tbodyOptions[i].childNodes[1].className.replace("prix:","");
		document.getElementById('lstOptions').value += option.replace("@@"," ")+"@@"+prix+"///";
	}
	
	
	//construction liste des consentemnts
	<?php if(!empty($listeConsentements)){ ?>
	
	var tbodyConsent = document.getElementById('tbodyConsent').childNodes;

	for(a=0; a<(tbodyConsent.length); a++){
		if(tbodyConsent[a].className=="selected"){
		consent = tbodyConsent[a].id.replace("trAssocConsent-","");
		document.getElementById('lstConsent').value += consent + "///";
		}
	}
	<?php } ?>
	
	
	
	document.getElementById('submitAct').disabled=true;
	document.getElementById('submitAct').value = "Patientez...";
	document.getElementById('submitAct').onclick="";
	document.getElementById('formAct').submit();
}
</script> 
<?php } ?>
<?php
echo $footer;
?>