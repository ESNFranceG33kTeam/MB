<?php

class SelectSite {
	
	private static $BDD_NAME;
    private static $BDD_HOST;
    private static $BDD_USER;
	private static $BDD_PASS; 

	private static $FOLDER_DATA;

	private static $LOGO_ASSO;
	private static $LOGO_ASSO_GREY;
	
	
	function SelectSite(){
	
		$domaine = (defined('IS_CONNECT') && IS_CONNECT==true) ? NOM_BDD : $_SERVER['SERVER_NAME'];
	
		switch ($domaine){

				
			case "localhost": // A remplacer par votre nom de domaine
				
				self::$BDD_NAME = "";	//A COMPLETER
				self::$BDD_HOST = "localhost";
				self::$BDD_USER = ""; //A COMPLETER
				self::$BDD_PASS = ""; //A COMPLETER
				
				self::$FOLDER_DATA = $_SERVER['DOCUMENT_ROOT'].'/../data/nancy';
				
				self::$LOGO_ASSO = '/template/images/esnnancy.png';
				self::$LOGO_ASSO_GREY = '/template/images/ESN Nancy grey.jpg';
				
				break;

			
			default: 
				die('Base de données non définie.');

		}
	}
	

	public static function getIdBDD(){
		
		$tabId = array('name' => self::$BDD_NAME, 'host' => self::$BDD_HOST, 'user' => self::$BDD_USER, 'pass' => self::$BDD_PASS);
		return $tabId;
	}
	
	
	public static function getFolderData(){
		
		return self::$FOLDER_DATA;
	}
	
	
	public static function getLogoAsso(){
		
		return self::$LOGO_ASSO;
	}
	
	
	public static function getLogoAssoGrey(){
		
		return self::$LOGO_ASSO_GREY;
	}
	
	
}
?>