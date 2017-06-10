<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/textEditor/textEditor.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/PHPExcel/Classes/PHPExcel.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/fonctions/PHPExcel/Classes/PHPExcel/Writer/Excel2007.php');



define('TITRE_PAGE',"Inscriptions via Excel");


//Récupération types cotisation et consentements

$bd = db_connect();

$typesCotis = db_tableau($bd, "		
			SELECT id, descr, prix, type
			FROM gestion_cotisations_types
			WHERE type='Adh_Normal' OR type='Adh_Special'
			ORDER BY type ASC, prix DESC");	
			
$consentements = db_tableau($bd, "		
			SELECT id, obligatoire, defaut, texte, texteCase
			FROM gestion_consentements
			WHERE cible=1
			ORDER BY id ASC");
			
db_close($bd);


	
//GENERATION EXCEL
	
$XLSXDocument = new PHPExcel_Reader_Excel2007();
$fichierInscriptions = $XLSXDocument->load($GLOBALS['SITE']->getFolderData().'/../Inscriptions.xlsx');
$sheetInscr = $fichierInscriptions->getSheet(0);
$sheetData = $fichierInscriptions->getSheetByName('data');	



	//Liste des pays

$filePays = fopen(($GLOBALS['SITE']->getFolderData()).'/../liste_pays.html', 'r');
$liPays=2;

while (!feof($filePays)){
	$lignePays = trim(fgets($filePays));
	$sheetData->setCellValueByColumnAndRow(0,$liPays, $lignePays);
	$liPays++;
}
fclose($filePays);
	
	
	//Liste des résidences

$fileRes = fopen(($GLOBALS['SITE']->getFolderData()).'/liste_residences.html', 'r');
$liResidences=2;

while (!feof($fileRes)){
	$ligneRes = explode('//',trim(fgets($fileRes)),4);	
	if(count($ligneRes)==4){
		$sheetData->setCellValueByColumnAndRow(1,$liResidences, $ligneRes[0]);
		$sheetData->setCellValueByColumnAndRow(2,$liResidences, $ligneRes[1]);
		$sheetData->setCellValueByColumnAndRow(3,$liResidences, $ligneRes[2]);
		$sheetData->setCellValueByColumnAndRow(4,$liResidences, $ligneRes[3]);
		$liResidences++;
	}
}
fclose($fileRes);

	
	
	//Liste des études

$fileEtudes = fopen(($GLOBALS['SITE']->getFolderData()).'/liste_etudes.html', 'r');
$liEtudes=2;
while (!feof($fileEtudes)){
	$ligneEtudes = trim(fgets($fileEtudes));

	if(strpos($ligneEtudes[0],'>')!==0){
		$sheetData->setCellValueByColumnAndRow(5,$liEtudes, $ligneEtudes);
		$liEtudes++;
	}
}
fclose($fileEtudes);

	
	//Cotisations
$liCotis=2;
$tabCotisGratuites = array();

if($typesCotis!==false && !empty($typesCotis)){

	for($i=0; $i<count($typesCotis); $i++){	
		$sheetData->setCellValueByColumnAndRow(6,$liCotis, str_replace('"','\"', $typesCotis[$i]['descr']).' - '.$typesCotis[$i]['prix'].'€');
		$sheetData->setCellValueByColumnAndRow(7,$liCotis, $typesCotis[$i]['prix']);
		
		if($typesCotis[$i]['prix'] == 0){
			array_push($tabCotisGratuites, str_replace('"','\"', $typesCotis[$i]['descr']).' - '.$typesCotis[$i]['prix'].'€');
		}
		
		$liCotis++;
	}
	
}


	//Consentements

$liConsentData=2;
$liConsentInscr=22;
$arrayIDConsent = array();
$arrayConsentOblig = array();


if($consentements!==false && !empty($consentements)){
	

	for($i=0; $i<count($consentements); $i++){
		
		$sheetData->setCellValueByColumnAndRow(8,$liConsentData, $consentements[$i]['texteCase']);
		$sheetData->setCellValueByColumnAndRow(9,$liConsentData, $consentements[$i]['id']);
		array_push($arrayIDConsent,$consentements[$i]['id']);
		
		if($consentements[$i]['obligatoire']){
			
			$textRequired = new PHPExcel_RichText();
			$textRequired->createText($consentements[$i]['texteCase']);
			$objAsterisque = $textRequired->createTextRun(' *');
			$objAsterisque->getFont()->getColor()->setARGB('00C00000');

			$sheetInscr->setCellValueByColumnAndRow(0,$liConsentInscr, $textRequired);
			
			array_push($arrayConsentOblig,$liConsentInscr);
			
		}else{
			$sheetInscr->setCellValueByColumnAndRow(0,$liConsentInscr, $consentements[$i]['texteCase']);
		}
		
		$liConsentData++;
		$liConsentInscr++;
		
	}
	
}
	
	//CGU
	if(!empty($tabChamps['cgu']['valeur'])){
		
		$sheetData->setCellValueByColumnAndRow(10,2, 'Oui');
		
		$textCGU = new PHPExcel_RichText();
		$textCGU->createText("Le nouvel adhérent a pris conscience et accepte les conditions générales de vente.");
		$objAsterisque = $textCGU->createTextRun(' *');
		$objAsterisque->getFont()->getColor()->setARGB('00C00000');

		$sheetInscr->setCellValueByColumnAndRow(0,$liConsentInscr, $textCGU);
		
		array_push($arrayConsentOblig,$liConsentInscr);
	
	}else{
		$sheetData->setCellValueByColumnAndRow(10,2, 'Non');
	}
	
	
	//Remplissage des colonnes
	
	for($col=1 ; $col<255 ; $col++){
		
		//Dates
		$validationDate = $sheetInscr->getCellByColumnAndRow($col,2)->getDataValidation();
		$validationDate->setType( PHPExcel_Cell_DataValidation::TYPE_DATE );
		$validationDate->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
		$validationDate->setAllowBlank(false);
		$validationDate->setShowInputMessage(true);
		$validationDate->setShowErrorMessage(true);
		$validationDate->setShowDropDown(true);
		$validationDate->setErrorTitle('Erreur');
		$validationDate->setError("Veuillez entrer une date valide.");
		$validationDate->setFormula1('=DATE(YEAR(TODAY())-100,MONTH(TODAY()),DAY(TODAY()))');
		$validationDate->setFormula2('=DATE(YEAR(TODAY())+100,MONTH(TODAY()),DAY(TODAY()))');
		
		$sheetInscr->getCellByColumnAndRow($col,8)->setDataValidation(clone $validationDate);
		$sheetInscr->getCellByColumnAndRow($col,17)->setDataValidation(clone $validationDate);
		$sheetInscr->getCellByColumnAndRow($col,19)->setDataValidation(clone $validationDate);
		
		
		//Sexe
		$validationSexe = $sheetInscr->getCellByColumnAndRow($col,6)->getDataValidation();
		$validationSexe->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$validationSexe->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
		$validationSexe->setAllowBlank(false);
		$validationSexe->setShowInputMessage(true);
		$validationSexe->setShowErrorMessage(true);
		$validationSexe->setShowDropDown(true);
		$validationSexe->setErrorTitle('Erreur');
		$validationSexe->setError("La valeur choisie n'est pas valide.");
		$validationSexe->setFormula1('"F,H"');

		//Pays
		$validationPays = $sheetInscr->getCellByColumnAndRow($col,7)->getDataValidation();
		$validationPays->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$validationPays->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
		$validationPays->setAllowBlank(false);
		$validationPays->setShowInputMessage(true);
		$validationPays->setShowErrorMessage(true);
		$validationPays->setShowDropDown(true);
		$validationPays->setErrorTitle('Erreur');
		$validationPays->setError("La valeur choisie n'est pas valide.");
		$validationPays->setFormula1('=data!$A$2:$A$'.($liPays-1));


		
		//Résidences
		$validationResidence = $sheetInscr->getCellByColumnAndRow($col,11)->getDataValidation();
		$validationResidence->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$validationResidence->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
		$validationResidence->setAllowBlank(true);
		$validationResidence->setShowInputMessage(true);
		$validationResidence->setShowErrorMessage(true);
		$validationResidence->setShowDropDown(true);
		$validationResidence->setErrorTitle('Erreur');
		$validationResidence->setError("La valeur choisie n'est pas valide.");
		$validationResidence->setFormula1('=data!$B$2:$B$'.($liResidences-1));

		//Code Postal
		$validationDate = $sheetInscr->getCellByColumnAndRow($col,14)->getDataValidation();
		$validationDate->setType( PHPExcel_Cell_DataValidation::TYPE_WHOLE );
		$validationDate->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
		$validationDate->setAllowBlank(false);
		$validationDate->setShowInputMessage(true);
		$validationDate->setShowErrorMessage(true);
		$validationDate->setShowDropDown(true);
		$validationDate->setErrorTitle('Erreur');
		$validationDate->setError("Veuillez entrer un code postal valide.");
		$validationDate->setFormula1('1000');
		$validationDate->setFormula2('99999');
		
		
		//Etudes
		$validationEtudes = $sheetInscr->getCellByColumnAndRow($col,16)->getDataValidation();
		$validationEtudes->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$validationEtudes->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
		$validationEtudes->setAllowBlank(false);
		$validationEtudes->setShowInputMessage(true);
		$validationEtudes->setShowErrorMessage(true);
		$validationEtudes->setShowDropDown(true);
		$validationEtudes->setErrorTitle('Erreur');
		$validationEtudes->setError("La valeur choisie n'est pas valide.");
		$validationEtudes->setFormula1('=data!$F$2:$F$'.($liEtudes-1));
		
		//Cotisation
		$validationCotis = $sheetInscr->getCellByColumnAndRow($col,20)->getDataValidation();
		$validationCotis->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		$validationCotis->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
		$validationCotis->setAllowBlank(false);
		$validationCotis->setShowInputMessage(true);
		$validationCotis->setShowErrorMessage(true);
		$validationCotis->setShowDropDown(true);
		$validationCotis->setErrorTitle('Erreur');
		$validationCotis->setError("La valeur choisie n'est pas dans la liste.");
		$validationCotis->setFormula1('=data!$G$2:$G$'.($liCotis-1));
		
		
		//Recu
		$validationDate = $sheetInscr->getCellByColumnAndRow($col,21)->getDataValidation();
		$validationDate->setType( PHPExcel_Cell_DataValidation::TYPE_WHOLE );
		$validationDate->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
		$validationDate->setAllowBlank(true);
		$validationDate->setShowInputMessage(true);
		$validationDate->setShowErrorMessage(true);
		$validationDate->setShowDropDown(true);
		$validationDate->setErrorTitle('Erreur');
		$validationDate->setError("Veuillez entrer un numéro de reçu valide entre 1 et 999.");
		$validationDate->setFormula1('1');
		$validationDate->setFormula2('999');
		
		
		//Consentements
		
		$liConsentInscr=22;
		
		if($consentements!==false && !empty($consentements)){
			
			
			for($i=0; $i<count($consentements); $i++){
				
				//Par défaut
				$sheetInscr->setCellValueByColumnAndRow($col,$liConsentInscr, '=IF('.PHPExcel_Cell::stringFromColumnIndex($col).'2<>"","'.(($consentements[$i]['defaut'])?"Oui":"Non").'","")');
				

				//Validation
				$validationConsent = $sheetInscr->getCellByColumnAndRow($col,$liConsentInscr)->getDataValidation();
				$validationConsent->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
				$validationConsent->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
				$validationConsent->setAllowBlank(false);
				$validationConsent->setShowInputMessage(true);
				$validationConsent->setShowErrorMessage(true);
				$validationConsent->setShowDropDown(true);
				$validationConsent->setErrorTitle('Erreur');
				$validationConsent->setError("La valeur choisie n'est pas valide.");
				$validationConsent->setFormula1('"Oui,Non"');
				

				$liConsentInscr++;
			}
	
		}
		
		//CGU
		
		if(!empty($tabChamps['cgu']['valeur'])){
			
			//Par défaut
			$sheetInscr->setCellValueByColumnAndRow($col,$liConsentInscr, '=IF('.PHPExcel_Cell::stringFromColumnIndex($col).'2<>"","Non","")');
		
			
			//Validation
			$validationCGU = $sheetInscr->getCellByColumnAndRow($col,$liConsentInscr)->getDataValidation();
			$validationCGU->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
			$validationCGU->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
			$validationCGU->setAllowBlank(false);
			$validationCGU->setShowInputMessage(true);
			$validationCGU->setShowErrorMessage(true);
			$validationCGU->setShowDropDown(true);
			$validationCGU->setErrorTitle('Erreur');
			$validationCGU->setError("La valeur choisie n'est pas valide.");
			$validationCGU->setFormula1('"Oui,Non"');
			
			$liConsentInscr++;
		}
		
		$liConsentInscr--;
		
		//Numéro de reçu
		
		$textConditionRecu = "";
		
		if(!empty($tabCotisGratuites)){
		
			
			for ($cotis=0; $cotis < count($tabCotisGratuites); $cotis++){
				$textConditionRecu .= PHPExcel_Cell::stringFromColumnIndex($col).'20="'.$tabCotisGratuites[$cotis].'",';
			}
			
			$textConditionRecu = substr($textConditionRecu, 0, -1);
			
			
			$stylePasRecu = new PHPExcel_Style_Conditional();
			$stylePasRecu->setConditionType(PHPExcel_Style_Conditional::CONDITION_EXPRESSION)
							->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_EQUAL)
							->addCondition('OR('.$textConditionRecu.')');		
			$stylePasRecu->getStyle()->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getEndColor()->setARGB('00444444'); ;

			$sheetInscr->getStyleByColumnAndRow($col,21)->setConditionalStyles($stylePasRecu);
			$conditionalStyles = $sheetInscr->getStyleByColumnAndRow($col,21)->getConditionalStyles();
			array_push($conditionalStyles, $stylePasRecu);
			$sheetInscr->getStyleByColumnAndRow($col,21)->setConditionalStyles($conditionalStyles);
		
		}
		
		//Validation de l'inscription
		
		$tousChamps = "";
		$champsObligatoires = "";
		$arrayChampsObligatoires = array(2,4,5,6,7,8,9,12,14,15,16,19,20);
		
		for ($champ=2; $champ < $liConsentInscr+1 ; $champ++){
			
			if($champ < 21){
				
				if(in_array($champ,$arrayChampsObligatoires)){
					$champsObligatoires .= PHPExcel_Cell::stringFromColumnIndex($col).$champ.'<>"",';
				}
				
			}elseif($champ == 21){
				
				if(!empty($textConditionRecu)){
					$champsObligatoires .= 'OR('.PHPExcel_Cell::stringFromColumnIndex($col).$champ.'<>"",'.$textConditionRecu.'),';
				}else{
					$champsObligatoires .= PHPExcel_Cell::stringFromColumnIndex($col).$champ.'<>"",';
				}
				
			}else{
				
				$champsObligatoires .= PHPExcel_Cell::stringFromColumnIndex($col).$champ.'<>"",';
			}
			
			$tousChamps .= PHPExcel_Cell::stringFromColumnIndex($col).$champ.'="",';
			
		}
		$champsObligatoires = substr($champsObligatoires, 0, -1);
		$tousChamps = substr($tousChamps, 0, -1);
		
		
		if(!empty($arrayConsentOblig)){
			
			$textConsentOblig = "";
			
			for ($liConsentObli = 0 ; $liConsentObli < count($arrayConsentOblig) ; $liConsentObli++){
				
				$textConsentOblig .= PHPExcel_Cell::stringFromColumnIndex($col).$arrayConsentOblig[$liConsentObli].'="Oui",';
				
			}
			
			$textConsentOblig = substr($textConsentOblig, 0, -1);
		
			$sheetInscr->setCellValueByColumnAndRow($col,1, '=IF(AND('.$tousChamps.'),"",IF(NOT(AND('.$champsObligatoires.')),"Inscription incomplète",IF(AND('.$textConsentOblig.'),"Inscription complète","Consentements à modifier")))');
		
		}else{
			
			$sheetInscr->setCellValueByColumnAndRow($col,1, '=IF(AND('.$tousChamps.'),"",IF(NOT(AND('.$champsObligatoires.')),"Inscription incomplète","Inscription complète"))');
		
		}
	}
	
$writer = new PHPExcel_Writer_Excel2007($fichierInscriptions, 'Excel2007');
$writer->save($GLOBALS['SITE']->getFolderData().'/Inscriptions.xlsx');



//Download

if(isset($_POST['downloadFichier'])){
	
	$filename = $GLOBALS['SITE']->getFolderData().'/Inscriptions.xlsx';
	
	   // GET THE CONTENTS OF THE FILE
		$filedata = file_get_contents($filename);

    if ($filedata)
    {
        // GET A NAME FOR THE FILE
        $basename = basename($filename);

        // THESE HEADERS ARE USED ON ALL BROWSERS
        header("Content-Type: application-x/force-download");
        header("Content-Disposition: attachment; filename=$basename");
        header("Content-length: ".(string)(strlen($filedata)));
        header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")+2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
        header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");

        // THIS HEADER MUST BE OMITTED FOR IE 6+
        if (FALSE === strpos($_SERVER["HTTP_USER_AGENT"], 'MSIE '))
        {
            header("Cache-Control: no-cache, must-revalidate");
        }

        // THIS IS THE LAST HEADER
        header("Pragma: no-cache");

        // FLUSH THE HEADERS TO THE BROWSER
        flush();

        // CAPTURE THE FILE IN THE OUTPUT BUFFERS - WILL BE FLUSHED AT SCRIPT END
        ob_start();
        echo $filedata;
    }

    // ERROR
    else
    {
        die("Erreur");
    }
}



$lstMessInscrits = array();
$lstMessErreurs = array();
$sommeCotisations = 0;

$validFchier = false;


//Verif fichier
if(isset($_FILES['fichierInscr'])){
	
	
	if(!is_uploaded_file ($_FILES['fichierInscr']['tmp_name'])){
		array_push($pageMessages,array('type'=>'err', 'content' => "Erreur lors du transfert du fichier."));
	
	}else{
	
		if($_FILES['fichierInscr']['error'] > 0){
			array_push($pageMessages,array('type'=>'err', 'content' => "Erreur lors du transfert du fichier."));
		} 
		
		
		if($_FILES['fichierInscr']['size'] > 2000000){
			array_push($pageMessages,array('type'=>'err', 'content' => "Fichier trop volumineux."));
		} 
		
		$extension_upload = strtolower(  substr(  strrchr($_FILES['fichierInscr']['name'], '.')  ,1)  );
		if($extension_upload != 'xlsx'){
			array_push($pageMessages,array('type'=>'err', 'content' => "Type du fichier incorrect."));
		}
		
	}

	if(empty($pageMessages)){
		$validFchier = true;
	}
}

if($validFchier){ // Ajout fichier

	$ExcelDocument = new PHPExcel_Reader_Excel2007();
	$loadInscriptions = $ExcelDocument->load($_FILES['fichierInscr']['tmp_name']);
	
	
	//Verif structure
	
	if ($loadInscriptions->getSheetCount() < 2){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Fichier invalide.'));
		goto endLoadInscriptions;
	}
	
	
	$sheetInscr = $loadInscriptions->getSheet(0);
	$sheetData = $loadInscriptions->getSheetByName('data');	
	$nbLignes = (21 + count($consentements) + (!empty($tabChamps['cgu']['valeur'])?1:0));
	
	
	if ($sheetData === NULL){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Fichier invalide : données introuvables.'));
		goto endLoadInscriptions;
	}
	
	if($sheetInscr->getHighestRow() < 21){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Fichier invalide : liste des champs incomplète.'));
		goto endLoadInscriptions;
	}
	
	if($sheetInscr->getHighestColumn() > 256){
		array_push($pageMessages, array('type'=>'err', 'content'=>'Fichier invalide : trop de colonnes.'));
		goto endLoadInscriptions;
	}
	
	if($sheetInscr->getHighestRow() < $nbLignes){
		array_push($pageMessages, array('type'=>'err', 'content'=>"Le fichier n'est pas à jour. Veuillez télécharger la nouvelle verison."));
		goto endLoadInscriptions;
	}
	
	$listeIDConsentLoaded = array();
	
	foreach ($sheetData->getRowIterator() as $row) {
		
		if($row->getRowIndex() > 1){
			if(!is_null($sheetData->getCellByColumnAndRow(9,$row->getRowIndex())->getValue())){
				array_push($listeIDConsentLoaded, $sheetData->getCellByColumnAndRow(9,$row->getRowIndex())->getValue());
			}else{
				break;
			}
		}
	}
	
	
	if($listeIDConsentLoaded != $arrayIDConsent){
		array_push($pageMessages, array('type'=>'err', 'content'=>"Le fichier n'est pas à jour. Veuillez télécharger la nouvelle version."));
		goto endLoadInscriptions;
	}
	
	
	if($sheetData->getCellByColumnAndRow(10,2)->getValue() != 'Oui' && !empty($tabChamps['cgu']['valeur'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>"Le fichier n'est pas à jour. Veuillez télécharger la nouvelle version."));
		goto endLoadInscriptions;
	}
	
	if($sheetData->getCellByColumnAndRow(10,2)->getValue() != 'Non' && empty($tabChamps['cgu']['valeur'])){
		array_push($pageMessages, array('type'=>'err', 'content'=>"Le fichier n'est pas à jour. Veuillez télécharger la nouvelle version."));
		goto endLoadInscriptions;
	}
	

	//Début ajouts des inscrits

	
	foreach ($sheetInscr->getColumnIterator() as $colInscr) {

		//Verif colonne avec au moins 1 élément

		$numColInscr = PHPExcel_Cell::columnIndexFromString($colInscr->getColumnIndex()) -1;
		$InscrEmpty = true;
		$infosInscr = array();

		
		if($numColInscr > 0 ){
			
			for($li=2; $li <= $nbLignes; $li++){
				
				//On ne vérifie pas le résultat des formules pour gain de temps d'execution
				if(!empty($sheetInscr->getCellByColumnAndRow($numColInscr,$li)->getValue()) && $sheetInscr->getCellByColumnAndRow($numColInscr,$li)->getValue()[0] != "="){
					
					goto verifInscription;
				}
				
			}

			goto finColonne; //Si pas de données dans la colonne
			
			
verifInscription:
			$okInscr = true;
			
				
			//Date d'inscription (ex : 01/01/2010) *
			
 			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,2)->getFormattedValue())){
				
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date d'inscription non définie.");
				
			}else{

				$dateInscr = date_parse(date('Y-m-d',PHPExcel_Shared_Date::ExcelToPHP($sheetInscr->getCellByColumnAndRow($numColInscr,2)->getCalculatedValue())));
				
				if (!checkdate($dateInscr['month'], $dateInscr['day'], $dateInscr['year'])) {
					
					$okInscr = false;
					array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date d'inscription invalide.");
				
				
				}elseif(date_create($dateInscr['year'].'-'.$dateInscr['month'].'-'.$dateInscr['day']) > date_create(date("Y-m-d"))){
					
					$okInscr = false;
					array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date d'inscription invalide.");
				
				}else{
					$dteInscr = $dateInscr['year'].'-'.$dateInscr['month'].'-'.$dateInscr['day'];
					$infosInscr['dateInscr'] = $dteInscr;
				}	
			}
			
			
			//Numéro de carte ESN
			
			if(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,3)->getFormattedValue()) > 15){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le numéro de carte ESN ne peut pas dépasser 15 caractères.");
			
			}else{
				$infosInscr['number'] = $sheetInscr->getCellByColumnAndRow($numColInscr,3)->getFormattedValue();
			}
			
			//Prénom *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,4)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Prénom non défini.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,4)->getFormattedValue()) > 30){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le prénom ne peut pas dépasser 30 caractères.");
			
			}else{
				$infosInscr['prenom'] = $sheetInscr->getCellByColumnAndRow($numColInscr,4)->getFormattedValue();
			}
			
			
			//Nom *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,5)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Nom non défini.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,5)->getFormattedValue()) > 30){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le nom ne peut pas dépasser 30 caractères.");
			
			}else{
				$infosInscr['nom'] = $sheetInscr->getCellByColumnAndRow($numColInscr,5)->getFormattedValue();
			}
			
			
			
			//Sexe *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,6)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Sexe non défini.");
			
			}elseif($sheetInscr->getCellByColumnAndRow($numColInscr,6)->getFormattedValue() != "H" && $sheetInscr->getCellByColumnAndRow($numColInscr,6)->getFormattedValue() != "F"){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Sexe invalide.");
			
			}else{
				$infosInscr['sexe'] = $sheetInscr->getCellByColumnAndRow($numColInscr,6)->getFormattedValue();
			}

			
			
			
			//Pays d'origine *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,7)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Pays non défini.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,7)->getFormattedValue()) > 50){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le pays ne peut pas dépasser 50 caractères.");
			
			}else{
				$infosInscr['pays'] = $sheetInscr->getCellByColumnAndRow($numColInscr,7)->getFormattedValue();
			}
			
			
			
			//Date de naissance *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,8)->getFormattedValue())){
				
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date de naissance non définie.");
				
			}else{

				$dateN = date_parse(date('Y-m-d',PHPExcel_Shared_Date::ExcelToPHP($sheetInscr->getCellByColumnAndRow($numColInscr,8)->getCalculatedValue())));
				
				if (!checkdate($dateN['month'], $dateN['day'], $dateN['year'])) {
					
					$okInscr = false;
					array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date de naissance invalide.");
				
				}else{
					$dteN = $dateN['year'].'-'.$dateN['month'].'-'.$dateN['day'];
					$infosInscr['dob'] = $dteN;
				}	
			}
			
			
			//E-mail *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,9)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : E-mail non défini.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,9)->getFormattedValue()) > 80){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : L'e-mail ne peut pas dépasser 80 caractères.");
			
			
			}elseif(!filter_var($sheetInscr->getCellByColumnAndRow($numColInscr,9)->getFormattedValue(), FILTER_VALIDATE_EMAIL)){
				
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : E-mail invalide.");
			
			
			}else{
				$infosInscr['mail'] = $sheetInscr->getCellByColumnAndRow($numColInscr,9)->getFormattedValue();
			}
			
			
			//Téléphone (forme : 0033-123456789)
			
			if(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,10)->getFormattedValue()) > 17){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le numéro de téléphone ne peut pas dépasser 17 caractères.");
			
			}elseif(!empty($sheetInscr->getCellByColumnAndRow($numColInscr,10)->getFormattedValue())){
				
				$tabTel = explode('-',$sheetInscr->getCellByColumnAndRow($numColInscr,10)->getFormattedValue(),2);
				
				if(count($tabTel) != 2){
					$okInscr = false;
					array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Format du numéro de téléphone invalide.");
				
				}else{
					
					if(!is_numeric($tabTel[0]) || !is_numeric($tabTel[1])){
						
						$okInscr = false;
						array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Numéro de téléphone invalide.");
						
						
					}else{
						
						$infosInscr['tel'] = '+'.(int)$tabTel[0].' ';
					
						$chiffreTel = str_split((int)$tabTel[1]);
						for($i=-1; $i<count($chiffreTel); $i+=2){
							if($i==-1){
								$infosInscr['tel'].=$chiffreTel[0];
							}else{
								$infosInscr['tel'].=" ".$chiffreTel[$i].$chiffreTel[$i+1];
							}
						}						
					}
				}
				
				
			}else{
				$infosInscr['tel'] = null;
			}
			
			//Adresse - Première ligne *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,12)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Adresse non définie.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,12)->getFormattedValue()) > 150){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : L'adresse (première ligne) ne peut pas dépasser 150 caractères.");
			
			}else{
				$infosInscr['adr1'] = $sheetInscr->getCellByColumnAndRow($numColInscr,12)->getFormattedValue();
			}
			
			//Adresse - Deuxieme ligne
			
			if(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,13)->getFormattedValue()) > 150){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : L'adresse (deuxième ligne) ne peut pas dépasser 150 caractères.");
			
			}else{
				$infosInscr['adr2'] = $sheetInscr->getCellByColumnAndRow($numColInscr,13)->getFormattedValue();
			}
			
			//Adresse - Code postal *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,14)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Code postal non défini.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,14)->getFormattedValue()) > 5){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le code postal ne peut pas dépasser 5 caractères.");
			
			
			}elseif(!is_numeric($sheetInscr->getCellByColumnAndRow($numColInscr,14)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Code postal invalide.");
			
			}else{
				$infosInscr['codpos'] = $sheetInscr->getCellByColumnAndRow($numColInscr,14)->getFormattedValue();
			}
			
			//Adresse - Ville *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,15)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Ville non définie.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,15)->getFormattedValue()) > 130){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : L'adresse (première ligne) ne peut pas dépasser 130 caractères.");
			
			}else{
				$infosInscr['ville'] = $sheetInscr->getCellByColumnAndRow($numColInscr,15)->getFormattedValue();
			}
			
	
			//Etudes *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,16)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Type d'études non défini.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,16)->getFormattedValue()) > 60){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le type d'études ne peut pas dépasser 60 caractères.");
			
			}else{
				$infosInscr['etudes'] = $sheetInscr->getCellByColumnAndRow($numColInscr,16)->getFormattedValue();
			}
			
			
			
			//Mois de retour envisagé (ex : mars 2012) 
			
			if(!empty($sheetInscr->getCellByColumnAndRow($numColInscr,17)->getFormattedValue())){
				
				$dateRetour = date_parse(date('Y-m-d',PHPExcel_Shared_Date::ExcelToPHP($sheetInscr->getCellByColumnAndRow($numColInscr,17)->getCalculatedValue())));
				
				if (!checkdate($dateRetour['month'], $dateRetour['day'], $dateRetour['year'])) {
					
					$okInscr = false;
					array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date de retour invalide.");
				
				}elseif(date_create($dateRetour['year'].'-'.$dateRetour['month'].'-'.$dateRetour['day']) < date_create(date("Y-m-d"))){
					
					$okInscr = false;
					array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date de retour invalide.");
				
				}else{
					$dteInscr = $dateRetour['year'].'-'.$dateRetour['month'];
					$infosInscr['retour'] = $dteInscr;
				}
				
			}else{
				$infosInscr['retour'] = "";
	
			}
			
			
			
			//Informations diverses
			
			
			if(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,18)->getFormattedValue()) > 999){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Les informations diverses ne peuvent pas dépasser 999 caractères.");
			
			}else{
				$infosInscr['divers'] = $sheetInscr->getCellByColumnAndRow($numColInscr,18)->getFormattedValue();
			}
			

			//Date de fin de la cotisation *
			
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,19)->getFormattedValue())){
				
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date de fin de cotisation non définie.");
				
			}else{

				$dateFin = date_parse(date('Y-m-d',PHPExcel_Shared_Date::ExcelToPHP($sheetInscr->getCellByColumnAndRow($numColInscr,19)->getCalculatedValue())));
				
				if (!checkdate($dateFin['month'], $dateFin['day'], $dateFin['year'])) {
					
					$okInscr = false;
					array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date de fin de cotisation invalide.");
					
					
				}elseif(date_create($dateFin['year'].'-'.$dateFin['month'].'-'.$dateFin['day']) < date_create(date("Y-m-d"))){
							
							$okInscr = false;
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Date de fin de cotisation invalide.");
						
				}else{
					
					if(isset($infosInscr['dateInscr'])){
					
						
						if(date_create($dateFin['year'].'-'.$dateFin['month'].'-'.$dateFin['day']) > date_create(date("Y-m-d", strtotime($infosInscr['dateInscr']." +1 year")))){
					
							$okInscr = false;
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : La durée de la cotisation ne peut pas excéder un an.");
						

						}else{
							$dteFinInscr = $dateFin['year'].'-'.$dateFin['month'].'-'.$dateFin['day'];
							$infosInscr['dateFinCotis'] = $dteFinInscr;
						}	
					}	
				}
			}
			
			
			//Type de cotisation *
			
			if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,20)->getFormattedValue())){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Adresse non définie.");
			
			}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,20)->getFormattedValue()) > 999){
			
				$okInscr = false;
				array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le type de cotisation ne peut pas dépasser 999 caractères.");
				
			
			}else{
				$infosInscr['cotisation'] = $sheetInscr->getCellByColumnAndRow($numColInscr,20)->getFormattedValue();
			}
			
			if(isset($infosInscr['cotisation'])){
				
				$trouveCotis = false;
			
				foreach ($sheetData->getRowIterator() as $rowCotis) {
			
					if($rowCotis->getRowIndex() > 1){
						
						if(!is_null($sheetData->getCellByColumnAndRow(6,$rowCotis->getRowIndex())->getValue())){
							
							if($sheetData->getCellByColumnAndRow(6,$rowCotis->getRowIndex())->getValue() == $infosInscr['cotisation']){
								
								$trouveCotis = true;
								$infosInscr['prixCotisation'] = $sheetData->getCellByColumnAndRow(7,$rowCotis->getRowIndex())->getValue();
								break;
							}
						}else{
							break;
						}
					}
				}
				
				if(!$trouveCotis){
					$okInscr = false;
					array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Cotisation invalide.");
				
				}else{
					if(!empty($infosInscr['prixCotisation']) && !is_numeric($infosInscr['prixCotisation'])){
						$okInscr = false;
						array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Prix de cotisation invalide.");						
					}
				}			
			}

			
			//Numéro de reçu *
			
			$infosInscr['recu']= "";
			
			if($trouveCotis){
				
				if(is_numeric($infosInscr['prixCotisation'])){
				
					if($infosInscr['prixCotisation'] > 0){
					
						if(empty($sheetInscr->getCellByColumnAndRow($numColInscr,21)->getFormattedValue())){
				
							$okInscr = false;
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Numéro de reçu non défini.");
						
						
						}elseif(strlen($sheetInscr->getCellByColumnAndRow($numColInscr,21)->getFormattedValue()) > 3){
						
							$okInscr = false;
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le numéro de reçu doit être compris entre 1 et 999.");
						
						
						}elseif(!is_numeric($sheetInscr->getCellByColumnAndRow($numColInscr,21)->getFormattedValue())){
						
							$okInscr = false;
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le numéro de reçu doit être compris entre 1 et 999.");
						
						
						}elseif($sheetInscr->getCellByColumnAndRow($numColInscr,21)->getFormattedValue() > 999){
						
							$okInscr = false;
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le numéro de reçu doit être compris entre 1 et 999.");
						
						}elseif($sheetInscr->getCellByColumnAndRow($numColInscr,21)->getFormattedValue() < 1){
						
							$okInscr = false;
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le numéro de reçu doit être compris entre 1 et 999.");
						
						}else{
							$infosInscr['recu'] = $sheetInscr->getCellByColumnAndRow($numColInscr,21)->getFormattedValue();
						}
					}
				}
			}
			
			//Consentements et CGU

			$messErrConsent = false;
		
			for($ligneConsent = 22 ; $ligneConsent <= $nbLignes ; $ligneConsent++){
				
				$numConsent = $ligneConsent - 20;
				
				if($ligneConsent != $nbLignes || $sheetData->getCellByColumnAndRow(10,2)->getValue() != 'Oui'){
				
					if($sheetInscr->getCellByColumnAndRow($numColInscr,$ligneConsent)->getFormattedValue() != 'Oui' && $sheetInscr->getCellByColumnAndRow($numColInscr,$ligneConsent)->getFormattedValue() != 'Non'){
					
						$okInscr = false;
						
						if(!$messErrConsent){
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Veuillez répondre aux demandes de consentements.");
							$messErrConsent = true;
						}
					
					}else{ //Consentements
						
						$trouveConsent = false;
						
							if(!is_null($sheetData->getCellByColumnAndRow(8,$numConsent)->getValue())){
								
								$trouveConsent = true;
								$infosInscr['consent-'.$sheetData->getCellByColumnAndRow(9,$numConsent)->getValue()] = $sheetInscr->getCellByColumnAndRow($numColInscr,$ligneConsent)->getFormattedValue();
	
								//Verif oblig
								if(in_array($ligneConsent,$arrayConsentOblig) && $infosInscr['consent-'.$sheetData->getCellByColumnAndRow(9,$numConsent)->getValue()] != 'Oui'){
									
									if(!$messErrConsent){
										$okInscr = false;
										array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Veuillez répondre aux demandes de consentements.");
										$messErrConsent = true;
									}
								}
							}
					
						if(!$trouveConsent){
							$okInscr = false;
							array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Consentement invalide.");
						
						}

					}
				
				}else{
					
					if($sheetInscr->getCellByColumnAndRow($numColInscr,$ligneConsent)->getFormattedValue() != 'Oui'){ //cgu
				
						$okInscr = false;
						array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : L'adhérent doit accepter les conditions générales.");
				
					}
				}
			}	
			
			
			//Ajout BDD
			if($okInscr){
				
				$bd = db_connect();
				
				if(empty($infosInscr['number'])){
					
					$infosInscr['number'] = "NULL";
					
				}else{
					$infosInscr['number'] = "'".strtoupper(mysqli_real_escape_string($bd, $infosInscr['number']))."'";
				}
				
				$infosInscr['prenom'] = mysqli_real_escape_string($bd, ucwords(strtolower($infosInscr['prenom'])));
				$infosInscr['nom'] = mysqli_real_escape_string($bd, ucwords(strtolower($infosInscr['nom'])));
				$infosInscr['pays'] = mysqli_real_escape_string($bd, $infosInscr['pays']);
				$infosInscr['mail'] = mysqli_real_escape_string($bd, $infosInscr['mail']);
				$infosInscr['divers'] = mysqli_real_escape_string($bd, $infosInscr['divers']);		
				$adresse = mysqli_real_escape_string($bd, $infosInscr['adr1'].'&#10;'.((!empty($infosInscr['adr2']))?$infosInscr['adr2'].'&#10;':"").$infosInscr['codpos'].' '.$infosInscr['ville']);
				$infosInscr['etudes'] = mysqli_real_escape_string($bd, $infosInscr['etudes']);		
				$infosInscr['cotisation'] = mysqli_real_escape_string($bd, $infosInscr['cotisation']);		
				
				
				//Verif EI déjà ajouté avec adresse mail
				
				$verifMail = db_tableau($bd, "		
					SELECT email
					FROM membres_adherents
					WHERE email='".$infosInscr['mail']."'");
				
				$verifCarte = db_tableau($bd, "		
					SELECT idesn
					FROM membres_adherents
					WHERE idesn=".$infosInscr['number']."");
			
		
				if((count($verifMail) == 0 && count($verifCarte) == 0) || (count($verifMail) == 0 && $infosInscr['number']=="NULL")){
		
		
					$addAdh = db_exec($bd, "
										INSERT INTO membres_adherents(idesn, prenom, nom, sexe, pays, dob, tel, email, adresse, etudes, dateRetour, divers, cotisation, dateInscr, dateFinInscr)
										VALUES(".$infosInscr['number'].",'".$infosInscr['prenom']."','".$infosInscr['nom']."','".$infosInscr['sexe']."','".$infosInscr['pays']."','".$infosInscr['dob']."',
										'".$infosInscr['tel']."','".$infosInscr['mail']."','".$adresse."','".$infosInscr['etudes']."','".$infosInscr['retour']."','".$infosInscr['divers']."',
										'".$infosInscr['cotisation']."','".$infosInscr['dateInscr']."','".$infosInscr['dateFinCotis']."')");
 
					if($addAdh!==false){
						
						$idNewAdh = db_lastId($bd);
						
						//Add consentements acceptés
 						for($i=0; $i<count($consentements); $i++){
							if(isset($infosInscr['consent-'.$consentements[$i]['id']])){
								
								if($infosInscr['consent-'.$consentements[$i]['id']] == 'Oui'){
									
									$addConsent = db_exec($bd, "
										INSERT INTO gestion_consentements_accepted(idAdh, idConsent)
										VALUES(".$idNewAdh.",".$consentements[$i]['id'].")");

									if($addConsent===false){die("Erreur ajout consentement.");}
								}
							}
						}
						
						$sommeCotisations += $infosInscr['prixCotisation'];
						
						$nomCotis = implode(' - ', explode(' - ',$infosInscr['cotisation'],-1));
						
						array_push($lstMessInscrits, "Colonne ".$colInscr->getColumnIndex()." : ".$infosInscr['prenom']." ".$infosInscr['nom']);

						if($infosInscr['prixCotisation']!=0){
							addCaisse("Inscriptions hors-ligne de ".$infosInscr['prenom']." ".$infosInscr['nom']." - ".$nomCotis, $infosInscr['prixCotisation'], $infosInscr['recu'], 'none', -2);
						}
					}
				}else{
					
					
					if(count($verifMail) > 0){
						array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : L'adresse e-mail est déjà utilisée par un autre adhérent.");
					}
			
					if(count($verifCarte) > 0 && $infosInscr['number']!="NULL"){
						array_push($lstMessErreurs, "Colonne ".$colInscr->getColumnIndex()." : Le numéro de carte ESN est déjà utilisé par un autre adhérent.");
					}

				}
				db_close($bd);
				
			}
		}
finColonne:
	}
		
endLoadInscriptions:

	unlink($_FILES['fichierInscr']['tmp_name']);

	//Affichage des messages

	if(count($lstMessInscrits) > 0){
		
		if(count($lstMessInscrits) > 1){
			
			array_push($pageMessages,array('type'=>'ok', 'content' => count($lstMessInscrits)." personnes ont été ajoutées à la liste des adhérents."));
			
		}else{
			
			array_push($pageMessages,array('type'=>'ok', 'content' => "Une personne a été ajoutée à la liste des adhérents."));
		}
		
		
	}

	if($sommeCotisations != 0){
		$pluriel = (abs($sommeCotisations)>=2)?true:false;
		array_push($pageMessages,array('type'=>'cash', 'content' => abs($sommeCotisations)."€ ".(($pluriel)?"ont":"a")." été ".(($sommeCotisations>0)?"ajouté":"retiré").(($pluriel)?"s ":" ").(($sommeCotisations>0)?"à":" de ")." la caisse."));
	}

	if(count($lstMessErreurs) > 0){
		
		if(count($lstMessErreurs) > 1){
			
			array_push($pageMessages,array('type'=>'err', 'content' => count($lstMessErreurs)." erreurs ont été rencontrées."));
			
		}else{
			
			array_push($pageMessages,array('type'=>'err', 'content' => "Une erreur a été rencontrée."));
		}
	}
}
	
include_once($_SERVER['DOCUMENT_ROOT'].'/template/container.php');


if(!empty ($lstMessInscrits) || !empty($lstMessErreurs)){
	echo "<h3>Bilan de l'importation</h3>";
	
	if(!empty ($lstMessInscrits)){
		
		if(count($lstMessInscrits) >1){
		
			echo '<div class="blocText"><a onclick="affMessInscr()">Afficher la liste des inscrits ('.count($lstMessInscrits).')</a><div id="lstMessInscrits" style="display:none"><ul>';
			
			for($messInscrit = 0; $messInscrit<count($lstMessInscrits) ; $messInscrit++){
				
				echo '<li>'.$lstMessInscrits[$messInscrit].'</li>';
				
			}
			
			echo '</ul></div>';
		
		}else{
			
			echo '<div class="blocText">Une personne a été inscrite : <br /><ul><li>'.$lstMessInscrits[0].'</li></ul>';
		}
		
		echo '</div>';
		
	}else{
		
		echo '<div class="blocText">Aucune personne n\'a été inscrite.</div>';
	}
	
	echo '<br />';
	
	if(!empty ($lstMessErreurs)){
		
		if(count($lstMessErreurs) > 1){
		
			echo '<div class="blocText"><a onclick="affMessErreurs()">Afficher la liste des erreurs ('.count($lstMessErreurs).')</a><div id="lstMessErreurs" style="display:none"><ul>';
			
			for($messErreur = 0; $messErreur<count($lstMessErreurs) ; $messErreur++){
				
				echo '<li>'.$lstMessErreurs[$messErreur].'</li>';
				
			}
			
			echo '</ul></div>';
		
		}else{
			
			echo '<div class="blocText">Une erreur a été rencontrée : <br /><ul><li>'.$lstMessErreurs[0].'</li></ul>';
		}
		
		echo '</div>';
		
		
	}else{
		
		echo '<div class="blocText">Aucune erreur n\'a été rencontrée.</div>';
		
	}
	
	
}

?>
<h3>Instructions</h3>
<div class="blocText">
Il est possible d'inscrire des adhérents sans être connecté à Internet via un fichier Excel.
<br />1- Téléchargez le fichier Excel. Il est nécessaire d'avoir Office version 2007 ou ultérieur.
<br />2- Complétez le fichier, une colonne par adhérent.
<br />3- Importez le fichier pour inscrire vos adhérents sur le module bénévoles.
<br /><br />A savoir : Le fichier est généré automatiquement en fonction des données de votre module bénévoles (liste des résidences, des études, etc.), il est alors préférable de le retélécharger régulièrement afin qu'il soit à jour.
</div>

<h3>Télécharger le fichier d'inscriptions</h3>
<div class="blocText"><a onclick="downloadFichier()">
<img src="../template/images/excel_exports.png" style="vertical-align:sub; height:18px; margin-right:8px"/>Cliquez ici pour télécharger le fichier Excel d'inscriptions</a></div>

<h3>Importer un fichier d'inscriptions complété</h3>
<div class="blocText">
<form method="post" id="formFichier" action="inscrOffline.php" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />

<img src="../template/images/excel_imports.png" style="vertical-align:sub; height:18px; margin-right:8px"/>
<input type="file" name="fichierInscr" />
<br/><input type="button" onclick="subFichier()" id="submitFichier" name="submitFichier" value="Envoyer" />

</form>
</div>
<form method="post" id="formDownloadFichier" action="inscrOffline.php">
<input type="hidden"  id="downloadFichier" name="downloadFichier" value="ok"/>
</form>


<script type="text/javascript">

function affMessInscr(){
	
	if(document.getElementById("lstMessInscrits").style.display == "none"){
		document.getElementById("lstMessInscrits").style.display = "";
		
	}else{
		document.getElementById("lstMessInscrits").style.display = "none";
		
	}
	
}

function affMessErreurs(){
	
	if(document.getElementById("lstMessErreurs").style.display == "none"){
		document.getElementById("lstMessErreurs").style.display = "";
		
	}else{
		document.getElementById("lstMessErreurs").style.display = "none";
	}
	
}

function downloadFichier(){

	document.getElementById('formDownloadFichier').submit();
}

function subFichier(){
	
	document.getElementById('submitFichier').disabled=true;
	document.getElementById('submitFichier').value = "Téléchargement en cours...";
	document.getElementById('submitFichier').onclick="";
	document.getElementById('formFichier').submit();
}

</script>
<?php
echo $footer;
?>