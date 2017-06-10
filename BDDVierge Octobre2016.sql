-- MySQL dump 10.13  Distrib 5.6.24, for Win64 (x86_64)
--
-- Host: localhost    Database: modulebenevoles_vierge
-- ------------------------------------------------------
-- Server version	5.7.8-rc-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_activities`
--

DROP TABLE IF EXISTS `activity_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `dte` date NOT NULL,
  `tme` text,
  `spots` int(3) DEFAULT NULL,
  `spotsResESN` int(3) DEFAULT NULL,
  `spotsSold` varchar(8) DEFAULT NULL COMMENT 'inscrits activité//inscrits liste atente',
  `prix` float DEFAULT NULL,
  `paiementStatut` varchar(8) DEFAULT NULL COMMENT 'nb d''impayé//(nb à rembourser/nb a rembourser inscrit sur liste attente)',
  `infos` text NOT NULL,
  `code` varchar(10) CHARACTER SET latin1 DEFAULT NULL,
  `consent` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_activities`
--

LOCK TABLES `activity_activities` WRITE;
/*!40000 ALTER TABLE `activity_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_options`
--

DROP TABLE IF EXISTS `activity_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idAct` int(11) NOT NULL,
  `opt` varchar(200) DEFAULT NULL,
  `prixOpt` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_options`
--

LOCK TABLES `activity_options` WRITE;
/*!40000 ALTER TABLE `activity_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_options_participants`
--

DROP TABLE IF EXISTS `activity_options_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_options_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idPart` int(11) NOT NULL,
  `idOpt` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_options_participants`
--

LOCK TABLES `activity_options_participants` WRITE;
/*!40000 ALTER TABLE `activity_options_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_options_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_participants`
--

DROP TABLE IF EXISTS `activity_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idAct` int(11) NOT NULL,
  `idAdh` int(11) DEFAULT NULL,
  `idESN` int(11) DEFAULT NULL,
  `paid` float NOT NULL DEFAULT '0',
  `fullPaid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '-1 : A rembourser',
  `recu` int(11) NOT NULL DEFAULT '0',
  `listeAttente` tinyint(1) NOT NULL DEFAULT '0',
  `dateInscr` datetime NOT NULL,
  `inscrBy` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `supprPart_idx` (`idAct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_participants`
--

LOCK TABLES `activity_participants` WRITE;
/*!40000 ALTER TABLE `activity_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_achats_acheteurs`
--

DROP TABLE IF EXISTS `gestion_achats_acheteurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_achats_acheteurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idAchat` int(11) DEFAULT NULL,
  `type` enum('Adh','ESN','Ext') DEFAULT NULL,
  `nom` varchar(61) DEFAULT NULL,
  `dteAchat` datetime DEFAULT NULL,
  `soldBy` varchar(61) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suppAcheteurs_idx` (`idAchat`),
  CONSTRAINT `suppAcheteurs` FOREIGN KEY (`idAchat`) REFERENCES `gestion_achats_produits` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_achats_acheteurs`
--

LOCK TABLES `gestion_achats_acheteurs` WRITE;
/*!40000 ALTER TABLE `gestion_achats_acheteurs` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_achats_acheteurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_achats_produits`
--

DROP TABLE IF EXISTS `gestion_achats_produits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_achats_produits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(200) DEFAULT NULL,
  `qte` int(11) DEFAULT NULL,
  `vendu` int(11) DEFAULT '0',
  `prix` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_achats_produits`
--

LOCK TABLES `gestion_achats_produits` WRITE;
/*!40000 ALTER TABLE `gestion_achats_produits` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_achats_produits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_caisse_fonds`
--

DROP TABLE IF EXISTS `gestion_caisse_fonds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_caisse_fonds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idRef` int(11) DEFAULT NULL,
  `dte` datetime NOT NULL,
  `descr` text NOT NULL,
  `montant` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_caisse_fonds`
--

LOCK TABLES `gestion_caisse_fonds` WRITE;
/*!40000 ALTER TABLE `gestion_caisse_fonds` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_caisse_fonds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_caisse_log`
--

DROP TABLE IF EXISTS `gestion_caisse_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_caisse_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idPeriode` int(11) NOT NULL,
  `idRef` int(11) DEFAULT NULL COMMENT '-1:achats - 0:cotiz - x:IdActivité - Null : divers',
  `dte` datetime NOT NULL,
  `descr` text NOT NULL,
  `somme` float NOT NULL,
  `recu` int(11) DEFAULT NULL,
  `addBy` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idPeriode` (`idPeriode`),
  CONSTRAINT `supprLog` FOREIGN KEY (`idPeriode`) REFERENCES `gestion_caisse_periodes` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_caisse_log`
--

LOCK TABLES `gestion_caisse_log` WRITE;
/*!40000 ALTER TABLE `gestion_caisse_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_caisse_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_caisse_periodes`
--

DROP TABLE IF EXISTS `gestion_caisse_periodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_caisse_periodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dteStart` datetime NOT NULL,
  `dteEnd` datetime DEFAULT NULL,
  `reliquatPrec` float NOT NULL DEFAULT '0',
  `bilan` float DEFAULT NULL,
  `ecartCaisse` float DEFAULT NULL,
  `depot` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_caisse_periodes`
--

LOCK TABLES `gestion_caisse_periodes` WRITE;
/*!40000 ALTER TABLE `gestion_caisse_periodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_caisse_periodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_caisse_ref`
--

DROP TABLE IF EXISTS `gestion_caisse_ref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_caisse_ref` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` text,
  `general` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_caisse_ref`
--

LOCK TABLES `gestion_caisse_ref` WRITE;
/*!40000 ALTER TABLE `gestion_caisse_ref` DISABLE KEYS */;
INSERT INTO `gestion_caisse_ref` VALUES (1,'Divers',1),(2,'Cotisation',1),(3,'Fonctionnement',1),(4,'Vente',1);
/*!40000 ALTER TABLE `gestion_caisse_ref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_config_boutonsbar`
--

DROP TABLE IF EXISTS `gestion_config_boutonsbar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_config_boutonsbar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text,
  `position` tinyint(3) unsigned DEFAULT NULL,
  `link_probatoire` text,
  `link_membres` text,
  `link_bureau` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_config_boutonsbar`
--

LOCK TABLES `gestion_config_boutonsbar` WRITE;
/*!40000 ALTER TABLE `gestion_config_boutonsbar` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_config_boutonsbar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_config_general`
--

DROP TABLE IF EXISTS `gestion_config_general`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_config_general` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `champ` varchar(50) NOT NULL,
  `descr` text,
  `valeur` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `champ_UNIQUE` (`champ`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_config_general`
--

LOCK TABLES `gestion_config_general` WRITE;
/*!40000 ALTER TABLE `gestion_config_general` DISABLE KEYS */;
INSERT INTO `gestion_config_general` VALUES (1,'messAccueil','Message d\'accueil',NULL),(2,'nomAsso','Nom de l\'association',''),(3,'titre','Nom du site','Module bénévoles'),(4,'title','Nom de l\'onglet','ESN - Module bénévoles'),(5,'mailAdmin','Adresse mail de l\'administrateur','webmaster@----.fr'),(7,'dureeProb','Durée de la période probatoire (semaines)','6'),(8,'moduleOneDrive','Activer l\'intégration de OneDrive (Oui/Non)','Non'),(9,'actLibrePayant','Autoriser les inscriptions sur Internet pour les activités payantes (Oui/Non)','Non'),(10,'cgu','Conditions générales de vente',NULL);
/*!40000 ALTER TABLE `gestion_config_general` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_consentements`
--

DROP TABLE IF EXISTS `gestion_consentements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_consentements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(300) DEFAULT NULL,
  `cible` tinyint(1) NOT NULL COMMENT '1: Adhesion\n2: Activités\n3: Activités par defaut',
  `obligatoire` tinyint(1) NOT NULL,
  `defaut` tinyint(1) NOT NULL,
  `texte` text,
  `texteCase` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_consentements`
--

LOCK TABLES `gestion_consentements` WRITE;
/*!40000 ALTER TABLE `gestion_consentements` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_consentements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_consentements_accepted`
--

DROP TABLE IF EXISTS `gestion_consentements_accepted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_consentements_accepted` (
  `idAdh` int(11) NOT NULL,
  `idConsent` int(11) NOT NULL,
  `idAct` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`idAdh`,`idConsent`,`idAct`),
  KEY `supConsent_idx` (`idConsent`),
  CONSTRAINT `supAdhConsent` FOREIGN KEY (`idAdh`) REFERENCES `membres_adherents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `supConsent` FOREIGN KEY (`idConsent`) REFERENCES `gestion_consentements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_consentements_accepted`
--

LOCK TABLES `gestion_consentements_accepted` WRITE;
/*!40000 ALTER TABLE `gestion_consentements_accepted` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_consentements_accepted` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_cotisations_benevoles`
--

DROP TABLE IF EXISTS `gestion_cotisations_benevoles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_cotisations_benevoles` (
  `idBen` int(11) NOT NULL,
  `dteCotis` date NOT NULL,
  `typeCotis` text NOT NULL,
  PRIMARY KEY (`idBen`),
  CONSTRAINT `supprCotisBen` FOREIGN KEY (`idBen`) REFERENCES `membres_benevoles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_cotisations_benevoles`
--

LOCK TABLES `gestion_cotisations_benevoles` WRITE;
/*!40000 ALTER TABLE `gestion_cotisations_benevoles` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_cotisations_benevoles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_cotisations_types`
--

DROP TABLE IF EXISTS `gestion_cotisations_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_cotisations_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descr` text,
  `prix` float NOT NULL,
  `type` enum('Adh_Normal','Adh_Special','ESN_Normal','ESN_Special') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_cotisations_types`
--

LOCK TABLES `gestion_cotisations_types` WRITE;
/*!40000 ALTER TABLE `gestion_cotisations_types` DISABLE KEYS */;
INSERT INTO `gestion_cotisations_types` VALUES (1,'Tarif Normal',10,'Adh_Normal'),(2,'Tarif Normal Bénévoles',5,'ESN_Normal');
/*!40000 ALTER TABLE `gestion_cotisations_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_onedrive_config`
--

DROP TABLE IF EXISTS `gestion_onedrive_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_onedrive_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `champ` varchar(50) DEFAULT NULL,
  `descr` text,
  `valeur` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_onedrive_config`
--

LOCK TABLES `gestion_onedrive_config` WRITE;
/*!40000 ALTER TABLE `gestion_onedrive_config` DISABLE KEYS */;
INSERT INTO `gestion_onedrive_config` VALUES (1,'client_id','Votre ID Client',''),(2,'client_secret','Votre Clé secrète client',''),(3,'nb_newfiles','Nombre d\'entrées dans l\'historique','50'),(4,'aff_noms','Afficher le nom des personnes (Oui/Non)','Oui');
/*!40000 ALTER TABLE `gestion_onedrive_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion_onedrive_folders`
--

DROP TABLE IF EXISTS `gestion_onedrive_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_onedrive_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('racine','exclus','board') CHARACTER SET latin1 DEFAULT NULL,
  `name` text CHARACTER SET latin1,
  `idFolder` varchar(60) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion_onedrive_folders`
--

LOCK TABLES `gestion_onedrive_folders` WRITE;
/*!40000 ALTER TABLE `gestion_onedrive_folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `gestion_onedrive_folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_adherents`
--

DROP TABLE IF EXISTS `membres_adherents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_adherents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idesn` varchar(15) DEFAULT NULL,
  `prenom` varchar(30) DEFAULT NULL,
  `nom` varchar(30) DEFAULT NULL,
  `sexe` enum('H','F') DEFAULT NULL,
  `pays` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT '0000-00-00',
  `tel` varchar(25) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `adresse` varchar(450) DEFAULT NULL,
  `etudes` varchar(60) DEFAULT NULL,
  `dateRetour` varchar(7) DEFAULT 'unknown',
  `cotisation` text,
  `divers` text,
  `dateInscr` date NOT NULL,
  `dateFinInscr` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_adherents`
--

LOCK TABLES `membres_adherents` WRITE;
/*!40000 ALTER TABLE `membres_adherents` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_adherents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_benevoles`
--

DROP TABLE IF EXISTS `membres_benevoles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_benevoles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(30) NOT NULL,
  `prenom` varchar(30) NOT NULL,
  `login` varchar(61) NOT NULL,
  `pass` varchar(100) CHARACTER SET latin1 NOT NULL,
  `dob` date NOT NULL,
  `mail` varchar(80) NOT NULL,
  `mail_microsoft` varchar(80) NOT NULL,
  `fb` varchar(100) NOT NULL,
  `tel` varchar(10) NOT NULL,
  `adresse` varchar(300) NOT NULL,
  `etudes` varchar(60) NOT NULL,
  `voiture` tinyint(1) NOT NULL,
  `affAnnuaire` tinyint(1) DEFAULT '1',
  `arrived` varchar(7) DEFAULT NULL,
  `last_connect` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `id_2` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_benevoles`
--

LOCK TABLES `membres_benevoles` WRITE;
/*!40000 ALTER TABLE `membres_benevoles` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_benevoles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_droits`
--

DROP TABLE IF EXISTS `membres_droits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_droits` (
  `id` int(11) NOT NULL,
  `general` set('none','probatoire','membre','bureau') DEFAULT 'none',
  `finProbatoire` date DEFAULT NULL,
  `roles` varchar(150) DEFAULT NULL COMMENT 'Id des roles séparés par //',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  CONSTRAINT `suppr_id` FOREIGN KEY (`id`) REFERENCES `membres_benevoles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_droits`
--

LOCK TABLES `membres_droits` WRITE;
/*!40000 ALTER TABLE `membres_droits` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_droits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_onedrive_invits`
--

DROP TABLE IF EXISTS `membres_onedrive_invits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_onedrive_invits` (
  `id` int(11) NOT NULL,
  `gr_membres` enum('none','invit','ok') NOT NULL DEFAULT 'none',
  `gr_bureau` enum('none','invit','ok') NOT NULL DEFAULT 'none',
  PRIMARY KEY (`id`),
  CONSTRAINT `supprById` FOREIGN KEY (`id`) REFERENCES `membres_benevoles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_onedrive_invits`
--

LOCK TABLES `membres_onedrive_invits` WRITE;
/*!40000 ALTER TABLE `membres_onedrive_invits` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_onedrive_invits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_plannings_inscrits`
--

DROP TABLE IF EXISTS `membres_plannings_inscrits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_plannings_inscrits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idIntervalle` int(11) DEFAULT NULL,
  `idJour` int(11) DEFAULT NULL,
  `creneau` int(11) DEFAULT NULL,
  `nom` varchar(61) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_plannings_inscrits`
--

LOCK TABLES `membres_plannings_inscrits` WRITE;
/*!40000 ALTER TABLE `membres_plannings_inscrits` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_plannings_inscrits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_plannings_intervalles`
--

DROP TABLE IF EXISTS `membres_plannings_intervalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_plannings_intervalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idPlanning` int(11) NOT NULL,
  `jour` tinyint(1) DEFAULT NULL,
  `debut` varchar(20) DEFAULT NULL,
  `fin` varchar(20) DEFAULT NULL,
  `intervalle` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_plannings_intervalles`
--

LOCK TABLES `membres_plannings_intervalles` WRITE;
/*!40000 ALTER TABLE `membres_plannings_intervalles` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_plannings_intervalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_plannings_liste`
--

DROP TABLE IF EXISTS `membres_plannings_liste`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_plannings_liste` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(80) DEFAULT NULL,
  `visibility` enum('probatoire','membre','bureau') DEFAULT 'probatoire',
  `edit` enum('probatoire','membre','bureau') DEFAULT 'probatoire',
  `type` enum('infini','ponctuel') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_plannings_liste`
--

LOCK TABLES `membres_plannings_liste` WRITE;
/*!40000 ALTER TABLE `membres_plannings_liste` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_plannings_liste` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_prebenevoles`
--

DROP TABLE IF EXISTS `membres_prebenevoles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_prebenevoles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(30) NOT NULL,
  `prenom` varchar(30) NOT NULL,
  `login` varchar(60) NOT NULL,
  `code` varchar(10) CHARACTER SET latin1 NOT NULL,
  `arrived` varchar(7) DEFAULT NULL,
  `finProbatoire` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_prebenevoles`
--

LOCK TABLES `membres_prebenevoles` WRITE;
/*!40000 ALTER TABLE `membres_prebenevoles` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_prebenevoles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_presence_feuilles`
--

DROP TABLE IF EXISTS `membres_presence_feuilles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_presence_feuilles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idGroupe` int(11) DEFAULT NULL COMMENT '-1 = Est un groupe',
  `nom` varchar(150) DEFAULT NULL,
  `droits` varchar(10) DEFAULT NULL,
  `visibility` enum('probatoire','membre','bureau') DEFAULT NULL,
  `affiche` int(1) DEFAULT NULL COMMENT 'Est égale à la somme :\nProbatoire = 1\nMembres = 2\nBureau = 4',
  `choixRep` enum('ON','ONP','ONR','ONPR') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_presence_feuilles`
--

LOCK TABLES `membres_presence_feuilles` WRITE;
/*!40000 ALTER TABLE `membres_presence_feuilles` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_presence_feuilles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_presence_inscrits`
--

DROP TABLE IF EXISTS `membres_presence_inscrits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_presence_inscrits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFeuille` int(11) DEFAULT NULL,
  `idMembre` int(11) DEFAULT NULL,
  `reponse` enum('O','N','P','R') DEFAULT 'N',
  PRIMARY KEY (`id`),
  UNIQUE KEY `MEMBRE-FEUILLE` (`idFeuille`,`idMembre`),
  KEY `SupprMem_idx` (`idMembre`),
  CONSTRAINT `SupprMem` FOREIGN KEY (`idMembre`) REFERENCES `membres_benevoles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_presence_inscrits`
--

LOCK TABLES `membres_presence_inscrits` WRITE;
/*!40000 ALTER TABLE `membres_presence_inscrits` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_presence_inscrits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_votes_choix`
--

DROP TABLE IF EXISTS `membres_votes_choix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_votes_choix` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idQuestion` int(11) DEFAULT NULL,
  `choix` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supChoix_idx` (`idQuestion`),
  CONSTRAINT `supChoix` FOREIGN KEY (`idQuestion`) REFERENCES `membres_votes_questions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_votes_choix`
--

LOCK TABLES `membres_votes_choix` WRITE;
/*!40000 ALTER TABLE `membres_votes_choix` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_votes_choix` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_votes_questions`
--

DROP TABLE IF EXISTS `membres_votes_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_votes_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(150) DEFAULT NULL,
  `type` enum('normal','classement') DEFAULT 'normal',
  `dteFin` datetime DEFAULT NULL,
  `nbChoix` int(11) DEFAULT NULL,
  `anonyme` tinyint(1) DEFAULT NULL,
  `visibility` enum('probatoire','membre','bureau') DEFAULT NULL,
  `votants` enum('adh','probatoire','membre','bureau') DEFAULT NULL,
  `nbVotants` int(11) NOT NULL DEFAULT '0',
  `askBy` varchar(61) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_votes_questions`
--

LOCK TABLES `membres_votes_questions` WRITE;
/*!40000 ALTER TABLE `membres_votes_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_votes_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres_votes_votes`
--

DROP TABLE IF EXISTS `membres_votes_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres_votes_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idQuestion` int(11) DEFAULT NULL,
  `typeVotant` enum('Adh','ESN') DEFAULT NULL,
  `idVotant` int(11) DEFAULT NULL,
  `idChoix` int(11) DEFAULT NULL,
  `nbPoints` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supVotes_idx` (`idQuestion`),
  CONSTRAINT `supVotes` FOREIGN KEY (`idQuestion`) REFERENCES `membres_votes_questions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres_votes_votes`
--

LOCK TABLES `membres_votes_votes` WRITE;
/*!40000 ALTER TABLE `membres_votes_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres_votes_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'modulebenevoles_vierge'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-11-01 17:38:42
