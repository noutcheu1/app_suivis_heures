-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 04 mai 2026 à 15:59
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bdchaudoudoux`
--

-- --------------------------------------------------------

--
-- Structure de la table `1- garde d enfants menage 2018`
--

CREATE TABLE `1- garde d enfants menage 2018` (
  `JANVIER 2018 FACT` varchar(50) DEFAULT NULL,
  `FEVRIER 2018 FACT` varchar(50) DEFAULT NULL,
  `FEVRIER 2018 ENC` varchar(50) DEFAULT NULL,
  `MARS 2018 FACT` varchar(50) DEFAULT NULL,
  `MARS 2018 ENC` varchar(50) DEFAULT NULL,
  `JANVIER 2018 ENC` varchar(50) DEFAULT NULL,
  `AVRIL 2018 FACT` varchar(50) DEFAULT NULL,
  `AVRIL 2018 ENC` varchar(50) DEFAULT NULL,
  `MAI 2018 FACT` varchar(50) DEFAULT NULL,
  `MAI 2018 ENC` varchar(50) DEFAULT NULL,
  `JUIN 2018 FACT` varchar(50) DEFAULT NULL,
  `JUILLET FACT` varchar(50) DEFAULT NULL,
  `JUILLET ENC` varchar(50) DEFAULT NULL,
  `JUIN 2018 ENC` varchar(50) DEFAULT NULL,
  `numero` int(11) DEFAULT NULL,
  `CODE CLIENT` varchar(50) DEFAULT NULL,
  `CODE PRESTATAIRE MENAGE` varchar(50) DEFAULT NULL,
  `CODE EN PRESTATAIRE GE` varchar(50) DEFAULT NULL,
  `MAND REG-MAND OCC-PREST` varchar(50) DEFAULT NULL,
  `MANDREG/PREST M` varchar(50) DEFAULT NULL,
  `PREST OCC` bit(1) DEFAULT NULL,
  `TYPE DE PRESTATION` varchar(50) DEFAULT NULL,
  `OPTIONS` varchar(50) DEFAULT NULL,
  `MODE DE PAIEMENT` varchar(50) DEFAULT NULL,
  `FAMILLE` varchar(50) DEFAULT NULL,
  `DATE D ENTREE` varchar(50) DEFAULT NULL,
  `DATE DE SORTIE` datetime DEFAULT NULL,
  `ARCHIVE` bit(1) DEFAULT NULL,
  `NOM` varchar(50) DEFAULT NULL,
  `PRENOM` varchar(50) DEFAULT NULL,
  `ADRESSE` varchar(160) DEFAULT NULL,
  `CODE POSTAL` int(11) DEFAULT NULL,
  `VILLE` varchar(50) DEFAULT NULL,
  `QUARTIER` varchar(50) DEFAULT NULL,
  `N° ligne de bus` varchar(50) DEFAULT NULL,
  `Arrêt de bus` varchar(50) DEFAULT NULL,
  `TELEPHONE DOMICILE` varchar(50) DEFAULT NULL,
  `TEL TRAV MME` varchar(50) DEFAULT NULL,
  `TEL TRAV MR` varchar(50) DEFAULT NULL,
  `PORTABLE MME` varchar(50) DEFAULT NULL,
  `PORTABLE MR` varchar(50) DEFAULT NULL,
  `E-MAIL` varchar(60) DEFAULT NULL,
  `e-mail n°2` varchar(50) DEFAULT NULL,
  `PROFESSION MME` varchar(50) DEFAULT NULL,
  `PROFESSION MR` varchar(50) DEFAULT NULL,
  `N ° ALLOCATAIRE` varchar(50) DEFAULT NULL,
  `NOM ENFANT1` varchar(50) DEFAULT NULL,
  `ENFANT 1` varchar(50) DEFAULT NULL,
  `DATE DE NAISSANCE 1` datetime DEFAULT NULL,
  `NOM ENFANT2` varchar(50) DEFAULT NULL,
  `ENFANT2` varchar(50) DEFAULT NULL,
  `DATE DE NAISSANCE 2` datetime DEFAULT NULL,
  `NOM ENFANT3` varchar(50) DEFAULT NULL,
  `ENFANT3` varchar(50) DEFAULT NULL,
  `DATE DE NAISSANCE 3` datetime DEFAULT NULL,
  `NOM ENFANT4` varchar(50) DEFAULT NULL,
  `ENFANT4` varchar(50) DEFAULT NULL,
  `DATE DE NAISSANCE 4` datetime DEFAULT NULL,
  `NOM ENFANT5` varchar(50) DEFAULT NULL,
  `ENFANT 5` varchar(50) DEFAULT NULL,
  `DATE DE NAISSANCE5` datetime DEFAULT NULL,
  `INTERVENANT GE 2015-2016` varchar(50) DEFAULT NULL,
  `INTERVENANT MENAGE SEPTEMBRE 2017` varchar(50) DEFAULT NULL,
  `NOMBRE D HEURES SEMAINE` varchar(50) DEFAULT NULL,
  `NOMBRE D HEURES MOIS` varchar(50) DEFAULT NULL,
  `N°URSSAF` varchar(50) DEFAULT NULL,
  `VOITURE` bit(1) DEFAULT NULL,
  `OBSERVATION: demande, personalité des enfants` varchar(255) DEFAULT NULL,
  `OBSERVATION SUITE` varchar(255) DEFAULT NULL,
  `REMARQUES : profil, autres` varchar(255) DEFAULT NULL,
  `INTERVENANT GE 2017-2018` varchar(50) DEFAULT NULL,
  `ENFANT 2018` bit(1) DEFAULT NULL,
  `ENFANT 2017` bit(1) DEFAULT NULL,
  `ENFANT 2016` bit(1) DEFAULT NULL,
  `ENFANT  2015` bit(1) DEFAULT NULL,
  `FAMILLE ENFANT - 3 ANS MAI 2017` bit(1) DEFAULT NULL,
  `FAMILLE - 3 ANS FEVRIER 2018` bit(1) DEFAULT NULL,
  `ADH MENAGES 2018` varchar(50) DEFAULT NULL,
  `ADH MAND 2018` varchar(50) DEFAULT NULL,
  `INTERVENANT MENAGE JUIN 218` varchar(50) DEFAULT NULL,
  `INTERVENANT GE 2018-2019` varchar(50) DEFAULT NULL,
  `RENTREE 2018 POURVU` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `antimatching`
--

CREATE TABLE `antimatching` (
  `id` int(50) NOT NULL,
  `numero_Famille` varchar(255) NOT NULL,
  `numero_Intervenant` varchar(255) NOT NULL,
  `menage` tinyint(1) DEFAULT NULL,
  `GE` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `antimatching`
--

INSERT INTO `antimatching` (`id`, `numero_Famille`, `numero_Intervenant`, `menage`, `GE`) VALUES
(4, 'M7169', '100744', 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `besoinsfamille`
--

CREATE TABLE `besoinsfamille` (
  `id` int(50) NOT NULL,
  `numero_famille` varchar(255) NOT NULL,
  `jour` varchar(50) NOT NULL,
  `jourException` varchar(20) DEFAULT NULL,
  `heureDebut` time NOT NULL,
  `heureFin` time NOT NULL,
  `activite` varchar(15) NOT NULL,
  `frequence` tinyint(1) NOT NULL,
  `heureSemaine` decimal(5,2) DEFAULT NULL,
  `heureIntervention` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `besoinsfamille`
--

INSERT INTO `besoinsfamille` (`id`, `numero_famille`, `jour`, `jourException`, `heureDebut`, `heureFin`, `activite`, `frequence`, `heureSemaine`, `heureIntervention`) VALUES
(1168, 'M7228', 'lundi', NULL, '16:30:00', '18:30:00', 'garde d\'enfants', 1, 0.00, 0.00),
(1169, 'M7228', 'jeudi', NULL, '16:30:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1173, 'M7743', 'lundi', NULL, '09:00:00', '16:00:00', 'menage', 1, 3.00, 3.00),
(1174, 'M7743', 'mardi', NULL, '09:00:00', '16:00:00', 'menage', 1, 3.00, 3.00),
(1175, 'M7743', 'jeudi', NULL, '09:00:00', '16:00:00', 'menage', 1, 3.00, 3.00),
(1176, 'M7743', 'vendredi', NULL, '09:00:00', '16:00:00', 'menage', 1, 3.00, 3.00),
(1236, 'M7783', 'mercredi', NULL, '13:30:00', '16:30:00', 'menage', 1, 2.00, 3.00),
(1237, 'M7744', 'lundi', NULL, '16:30:00', '18:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1238, 'M7744', 'mercredi', NULL, '16:15:00', '17:45:00', 'garde d\'enfants', 1, 0.00, 0.00),
(1239, 'M7708', 'lundi', NULL, '17:00:00', '00:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1240, 'M7708', 'mardi', NULL, '17:00:00', '00:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1241, 'M7708', 'mercredi', NULL, '09:30:00', '12:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1242, 'M7708', 'jeudi', NULL, '17:00:00', '19:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1277, 'M7576', 'lundi', NULL, '07:30:00', '09:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1278, 'M7576', 'mardi', NULL, '07:30:00', '09:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1279, 'M7576', 'mercredi', NULL, '07:30:00', '09:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1280, 'M7576', 'jeudi', NULL, '07:30:00', '09:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1281, 'M7576', 'vendredi', NULL, '07:30:00', '09:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1282, 'M7394', 'jeudi', NULL, '09:00:00', '16:00:00', 'menage', 1, 2.00, 2.00),
(1283, 'M7394', 'vendredi', NULL, '09:00:00', '16:00:00', 'menage', 1, 2.00, 2.00),
(1300, 'M7800', 'mardi', NULL, '17:15:00', '19:45:00', 'garde d\'enfants', 1, NULL, NULL),
(1301, 'M7800', 'mercredi', NULL, '18:15:00', '20:45:00', 'garde d\'enfants', 1, NULL, NULL),
(1304, 'M7755', 'lundi', NULL, '07:45:00', '08:45:00', 'garde d\'enfants', 1, NULL, NULL),
(1305, 'M7755', 'lundi', NULL, '18:45:00', '20:15:00', 'garde d\'enfants', 1, 0.00, 0.00),
(1306, 'M7755', 'mardi', NULL, '07:45:00', '08:45:00', 'garde d\'enfants', 1, NULL, NULL),
(1307, 'M7755', 'mardi', NULL, '18:45:00', '20:15:00', 'garde d\'enfants', 1, 0.00, 0.00),
(1308, 'M7755', 'mercredi', NULL, '18:45:00', '20:15:00', 'garde d\'enfants', 1, 0.00, 0.00),
(1309, 'M7755', 'jeudi', NULL, '07:45:00', '08:45:00', 'garde d\'enfants', 1, NULL, NULL),
(1310, 'M7755', 'jeudi', NULL, '18:45:00', '20:15:00', 'garde d\'enfants', 1, 0.00, 0.00),
(1311, 'M7755', 'vendredi', NULL, '07:45:00', '08:45:00', 'garde d\'enfants', 1, NULL, NULL),
(1312, 'M7430', 'sans importance', 'mercredi', '09:00:00', '16:00:00', 'menage', 1, 2.00, 2.00),
(1313, 'M7754', 'lundi', NULL, '19:15:00', '20:15:00', 'menage', 1, 1.00, 1.00),
(1314, 'M7754', 'jeudi', NULL, '19:15:00', '20:15:00', 'menage', 1, 1.00, 1.00),
(1316, 'M7708', 'sans importance', 'mercredi', '09:00:00', '16:00:00', 'menage', 1, 4.00, 4.00),
(1317, 'M7802', 'jeudi', NULL, '08:00:00', '09:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1318, 'M7802', 'jeudi', NULL, '12:00:00', '13:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1319, 'M7770', 'mercredi', NULL, '15:30:00', '18:30:00', 'garde d\'enfants', 1, 0.00, 0.00),
(1329, 'M7806', 'mercredi', NULL, '09:00:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1330, 'M7806', 'lundi', NULL, '09:00:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1331, 'M7806', 'mardi', NULL, '09:00:00', '20:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1332, 'M7806', 'jeudi', NULL, '09:00:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1333, 'M7806', 'vendredi', NULL, '09:00:00', '17:00:00', 'garde d\'enfants', 1, NULL, NULL),
(1334, 'M7784', 'sans importance', NULL, '00:00:00', '00:00:00', 'menage', 1, 3.00, 3.00),
(1335, 'M7697', 'vendredi', NULL, '17:00:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1336, 'M6438', 'lundi', NULL, '17:00:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1337, 'M6438', 'mardi', NULL, '17:00:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1338, 'M7780', 'lundi', NULL, '16:30:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1339, 'M7780', 'mardi', NULL, '16:30:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1340, 'M7780', 'jeudi', NULL, '16:30:00', '18:30:00', 'garde d\'enfants', 1, NULL, NULL),
(1341, 'M7634', 'lundi', NULL, '09:00:00', '16:00:00', 'menage', 1, 3.00, 3.00),
(1342, 'M7685', 'lundi', NULL, '16:15:00', '18:45:00', 'garde d\'enfants', 1, NULL, NULL),
(1343, 'M7685', 'jeudi', NULL, '16:15:00', '18:45:00', 'garde d\'enfants', 1, NULL, NULL),
(1344, 'M7685', 'vendredi', NULL, '16:00:00', '17:30:00', 'garde d\'enfants', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `candidats`
--

CREATE TABLE `candidats` (
  `numCandidat_Candidats` int(4) NOT NULL,
  `titre_Candidats` varchar(3) DEFAULT NULL,
  `nom_Candidats` varchar(50) DEFAULT NULL,
  `prenom_Candidats` varchar(50) DEFAULT NULL,
  `dateNaiss_Candidats` datetime DEFAULT NULL,
  `lieuNaiss_Candidats` varchar(20) DEFAULT NULL,
  `paysNaiss_Candidats` varchar(20) DEFAULT NULL,
  `nationalite_Candidats` varchar(25) DEFAULT NULL,
  `numTitreSejour` varchar(15) DEFAULT NULL,
  `numSS_Candidats` varchar(21) DEFAULT NULL,
  `Mutuelle_Candidats` varchar(40) CHARACTER SET utf16 COLLATE utf16_general_ci DEFAULT '0',
  `CMU_Candidats` tinyint(1) DEFAULT 0,
  `adresse_Candidats` varchar(50) DEFAULT NULL,
  `cp_Candidats` char(5) DEFAULT NULL,
  `ville_Candidats` varchar(50) DEFAULT NULL,
  `secteur_Candidats` varchar(50) DEFAULT NULL,
  `Quartier_Candidats` varchar(50) DEFAULT NULL,
  `telPortable_Candidats` varchar(14) DEFAULT NULL,
  `telFixe_Candidats` varchar(14) DEFAULT NULL,
  `TelUrg_Candidats` varchar(14) DEFAULT NULL,
  `email_Candidats` varchar(60) DEFAULT NULL,
  `permis_Candidats` tinyint(1) DEFAULT NULL,
  `vehicule_Candidats` tinyint(1) DEFAULT NULL,
  `statutPro_Candidats` varchar(15) DEFAULT NULL,
  `situationFamiliale_Candidats` varchar(15) DEFAULT NULL,
  `diplomes_Candidats` varchar(150) DEFAULT NULL,
  `qualifications_Candidats` varchar(100) DEFAULT NULL,
  `expBBmoins1a_Candidats` tinyint(1) DEFAULT NULL,
  `enfantHand_Candidats` tinyint(1) DEFAULT NULL,
  `disponibilites_Candidats` varchar(1000) DEFAULT NULL,
  `observations_Candidats` varchar(1000) DEFAULT NULL,
  `candidatureRetenue_Candidats` varchar(150) DEFAULT 'En attente',
  `dateEntretien_Candidats` datetime DEFAULT current_timestamp(),
  `travailVoulu_Candidats` varchar(50) DEFAULT NULL,
  `nomJF_Candidats` varchar(50) DEFAULT NULL,
  `statutHandicap_Candidats` tinyint(1) DEFAULT 0,
  `dateTitreSejour` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `candidats`
--


-- --------------------------------------------------------

--
-- Structure de la table `famille`
--

CREATE TABLE `famille` (
  `numero_Famille` varchar(10) NOT NULL,
  `Famille_Famille` varchar(100) DEFAULT NULL,
  `dateEntree_Famille` datetime DEFAULT NULL,
  `dateSortie_Famille` datetime DEFAULT NULL,
  `archive_Famille` tinyint(1) DEFAULT NULL,
  `adresse_Famille` varchar(50) DEFAULT NULL,
  `cp_Famille` char(5) DEFAULT NULL,
  `ville_Famille` varchar(50) DEFAULT NULL,
  `secteur_Famille` varchar(50) DEFAULT NULL,
  `quartier_Famille` varchar(30) DEFAULT NULL,
  `telDom_Famille` char(14) DEFAULT NULL,
  `numAlloc_Famille` varchar(15) DEFAULT NULL,
  `numURSSAF_Famille` varchar(20) DEFAULT NULL,
  `vehicule_Famille` tinyint(1) DEFAULT NULL,
  `observations_Famille` varchar(1000) DEFAULT NULL,
  `Remarques_Famille` varchar(1000) DEFAULT NULL,
  `AG_Famille` tinyint(1) NOT NULL DEFAULT 0,
  `PM_Famille` varchar(10) DEFAULT NULL,
  `REG_Famille` varchar(10) DEFAULT NULL,
  `PGE_Famille` varchar(10) DEFAULT NULL,
  `dateModif_Famille` varchar(100) DEFAULT NULL,
  `suivi_Famille` varchar(250) DEFAULT NULL,
  `enfantHand_Famille` tinyint(1) DEFAULT NULL,
  `option_Famille` varchar(10) DEFAULT NULL,
  `modePaiement_Famille` varchar(50) DEFAULT NULL,
  `mand_Famille` tinyint(1) DEFAULT NULL,
  `prestM_Famille` tinyint(1) DEFAULT NULL,
  `prestGE_Famille` tinyint(1) DEFAULT NULL,
  `aPourvoir_Famille` tinyint(1) DEFAULT NULL,
  `aPourvoir_PM` tinyint(1) DEFAULT NULL,
  `Date_aPourvoir_PM` date DEFAULT NULL,
  `aPourvoir_PGE` tinyint(1) DEFAULT NULL,
  `Date_aPourvoir_PGE` date DEFAULT NULL,
  `arretBus_Famille` varchar(50) DEFAULT NULL,
  `numBus_Famille` varchar(20) DEFAULT NULL,
  `typeLogement_Famille` varchar(30) DEFAULT NULL,
  `superficie_Famille` int(4) DEFAULT NULL,
  `nbEtage_Famille` int(3) DEFAULT NULL,
  `nbChambres_Famille` int(3) DEFAULT NULL,
  `nbSDB_Famille` int(3) DEFAULT NULL,
  `nbSanitaire_Famille` int(3) DEFAULT NULL,
  `gardePart_Famille` tinyint(1) DEFAULT NULL,
  `repassage_Famille` tinyint(1) DEFAULT NULL,
  `sortieMand_Famille` date DEFAULT NULL,
  `sortiePGE_Famille` date DEFAULT NULL,
  `sortiePM_Famille` date DEFAULT NULL,
  `nbSemVacancesM` tinyint(4) NOT NULL DEFAULT 0,
  `nbSemVacancesGE` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `famille`
--
--
-- Structure de la table `publipostagecontrats`
--

CREATE TABLE `publipostagecontrats` (
  `codeClient` varchar(10) NOT NULL,
  `codePM` varchar(10) DEFAULT NULL,
  `codePGE` varchar(10) DEFAULT NULL,
  `noms` varchar(150) DEFAULT NULL,
  `prenoms` varchar(150) DEFAULT NULL,
  `adresse` varchar(500) DEFAULT NULL,
  `cp` char(5) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `telDom` varchar(10) DEFAULT NULL,
  `telPortable1` varchar(10) DEFAULT NULL,
  `telPortable2` varchar(10) DEFAULT NULL,
  `telPro1` varchar(10) DEFAULT NULL,
  `telPro2` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `numCaf` varchar(40) DEFAULT NULL,
  `nomEnfant1` varchar(100) DEFAULT NULL,
  `nomEnfant2` varchar(100) DEFAULT NULL,
  `nomEnfant3` varchar(100) DEFAULT NULL,
  `nomEnfant4` varchar(100) DEFAULT NULL,
  `prenomEnfant1` varchar(100) DEFAULT NULL,
  `prenomEnfant2` varchar(100) DEFAULT NULL,
  `prenomEnfant3` varchar(100) DEFAULT NULL,
  `prenomEnfant4` varchar(100) DEFAULT NULL,
  `dateNaiss1` date DEFAULT NULL,
  `dateNaiss2` date DEFAULT NULL,
  `dateNaiss3` date DEFAULT NULL,
  `dateNaiss4` date DEFAULT NULL,
  `modePaiement` varchar(100) DEFAULT NULL,
  `intervenants` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `suivre`
--

CREATE TABLE `suivre` (
  `idForm_Formations` int(5) NOT NULL,
  `numSalarie_Intervenants` int(5) NOT NULL,
  `numFamille` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `suivre`
--

INSERT INTO `suivre` (`idForm_Formations`, `numSalarie_Intervenants`, `numFamille`) VALUES
(1, 1398, NULL),
(1, 2446, NULL),
(1, 2461, NULL),
(1, 2569, NULL),
(1, 2605, NULL),
(1, 2779, NULL),
(2, 68, NULL),
(2, 117, NULL),
(2, 172, NULL),
(2, 200, NULL),
(2, 201, NULL),
(2, 361, NULL),
(2, 412, NULL),
(2, 730, NULL),
(2, 732, NULL),
(2, 787, NULL),
(2, 1444, NULL),
(2, 1649, NULL),
(2, 2569, 'M000'),
(3, 68, NULL),
(3, 117, NULL),
(3, 201, NULL),
(3, 361, NULL),
(4, 68, NULL),
(4, 81, NULL),
(4, 117, NULL),
(4, 200, NULL),
(4, 201, NULL),
(4, 361, NULL),
(4, 412, NULL),
(4, 1455, NULL),
(4, 100118, 'M000'),
(5, 81, NULL),
(5, 172, NULL),
(5, 201, NULL),
(5, 361, NULL),
(5, 2166, 'M000'),
(6, 68, NULL),
(6, 117, NULL),
(6, 201, NULL),
(6, 412, NULL),
(6, 1166, NULL),
(6, 1365, NULL),
(6, 2247, NULL),
(7, 81, NULL),
(7, 412, NULL),
(7, 730, NULL),
(7, 732, NULL),
(7, 787, NULL),
(7, 1166, NULL),
(7, 1365, NULL),
(7, 1642, NULL),
(7, 1693, NULL),
(7, 1795, NULL),
(7, 1827, NULL),
(7, 1885, NULL),
(7, 2100, NULL),
(7, 2166, 'M000'),
(7, 2494, NULL),
(7, 2525, NULL),
(7, 2569, NULL),
(7, 2599, NULL),
(7, 2632, NULL),
(7, 2644, NULL),
(7, 2683, NULL),
(7, 2690, NULL),
(7, 2750, NULL),
(7, 2757, NULL),
(7, 2779, NULL),
(7, 2792, NULL),
(7, 2814, NULL),
(7, 2822, NULL),
(7, 100039, NULL),
(7, 100118, 'M000'),
(8, 68, NULL),
(8, 81, NULL),
(8, 117, NULL),
(8, 172, NULL),
(8, 201, NULL),
(8, 361, NULL),
(8, 730, NULL),
(8, 1398, NULL),
(8, 1455, NULL),
(8, 1592, NULL),
(8, 2446, NULL),
(8, 2461, NULL),
(8, 100118, 'M000'),
(9, 1711, NULL),
(9, 2113, NULL),
(9, 2253, NULL),
(11, 172, NULL),
(11, 918, NULL),
(11, 1384, NULL),
(11, 1423, NULL),
(11, 1649, NULL),
(12, 81, NULL),
(12, 117, NULL),
(12, 911, NULL),
(12, 918, NULL),
(12, 1365, NULL),
(12, 1384, NULL),
(12, 1423, NULL),
(12, 1455, NULL),
(12, 1649, NULL),
(12, 1711, NULL),
(12, 2113, NULL),
(12, 2166, NULL),
(12, 2282, NULL),
(12, 2415, NULL),
(12, 2569, NULL),
(12, 2701, NULL),
(12, 2818, NULL),
(12, 2825, NULL),
(13, 68, NULL),
(13, 117, NULL),
(13, 201, NULL),
(13, 412, NULL),
(13, 601, NULL),
(13, 730, NULL),
(13, 1642, NULL),
(13, 1649, NULL),
(13, 2446, NULL),
(14, 1893, NULL),
(14, 1954, NULL),
(14, 2113, NULL),
(14, 2409, NULL),
(14, 2494, NULL),
(14, 2525, NULL),
(15, 68, NULL),
(15, 81, NULL),
(15, 117, NULL),
(15, 412, NULL),
(15, 1398, NULL),
(15, 1455, NULL),
(15, 1661, NULL),
(15, 2100, NULL),
(15, 2446, NULL),
(15, 2461, NULL),
(15, 100118, 'M000'),
(16, 81, NULL),
(16, 117, NULL),
(16, 201, NULL),
(16, 361, NULL),
(16, 1579, NULL),
(16, 1642, NULL),
(16, 2446, NULL),
(16, 2461, NULL),
(16, 2494, NULL),
(16, 2587, NULL),
(16, 2605, NULL),
(17, 81, NULL),
(17, 117, NULL),
(17, 361, NULL),
(18, 68, NULL),
(18, 117, NULL),
(18, 732, NULL),
(18, 1649, NULL),
(19, 68, NULL),
(19, 172, NULL),
(19, 201, NULL),
(19, 361, NULL),
(19, 412, NULL),
(19, 562, NULL),
(19, 601, NULL),
(19, 730, NULL),
(19, 787, NULL),
(19, 1827, NULL),
(19, 2166, 'M000'),
(20, 172, NULL),
(20, 201, NULL),
(20, 1649, NULL),
(20, 1711, NULL),
(21, 918, NULL),
(21, 1384, NULL),
(21, 1423, NULL),
(21, 1455, NULL),
(21, 1711, NULL),
(22, 1455, NULL),
(22, 1837, NULL),
(22, 2755, NULL),
(23, 68, NULL),
(23, 81, NULL),
(23, 117, NULL),
(23, 200, NULL),
(23, 201, NULL),
(23, 361, NULL),
(23, 1398, NULL),
(23, 1649, NULL),
(24, 117, NULL),
(24, 200, NULL),
(24, 201, NULL),
(24, 361, NULL),
(24, 412, NULL),
(24, 562, NULL),
(24, 730, NULL),
(24, 1661, NULL),
(24, 1885, NULL),
(24, 1893, NULL),
(24, 2792, NULL),
(25, 68, NULL),
(25, 172, NULL),
(25, 787, NULL),
(25, 1200, NULL),
(25, 1365, NULL),
(25, 1455, NULL),
(25, 2750, NULL),
(26, 1711, NULL),
(26, 1837, NULL),
(26, 1954, NULL),
(26, 2113, NULL),
(27, 117, NULL),
(27, 201, NULL),
(27, 1365, NULL),
(28, 1954, NULL),
(28, 2113, NULL),
(28, 2253, NULL),
(29, 1954, NULL),
(29, 2113, NULL),
(29, 2253, NULL),
(29, 2409, NULL),
(30, 81, NULL),
(30, 117, NULL),
(30, 201, NULL),
(30, 361, NULL),
(30, 412, NULL),
(30, 730, NULL),
(30, 787, NULL),
(30, 1166, NULL),
(30, 1365, NULL),
(30, 1455, NULL),
(30, 1693, NULL),
(30, 2247, NULL),
(30, 2975, NULL),
(31, 117, NULL),
(31, 201, NULL),
(31, 412, NULL),
(31, 730, NULL),
(31, 1661, NULL),
(31, 2247, NULL),
(32, 1837, NULL),
(32, 2113, NULL),
(32, 2409, NULL),
(33, 1893, NULL),
(33, 2113, NULL),
(33, 2253, NULL),
(33, 2409, NULL),
(33, 2425, NULL),
(34, 1398, NULL),
(34, 2444, NULL),
(34, 2461, NULL),
(34, 2494, NULL),
(35, 412, NULL),
(35, 1398, NULL),
(35, 2100, NULL),
(36, 81, NULL),
(36, 200, NULL),
(36, 361, NULL),
(36, 787, NULL),
(36, 1166, NULL),
(36, 1398, NULL),
(36, 1455, 'M000'),
(36, 1661, NULL),
(36, 2100, NULL),
(36, 2446, NULL),
(36, 2548, NULL),
(36, 2587, NULL),
(36, 2785, NULL),
(37, 1954, NULL),
(37, 2113, NULL),
(37, 2253, NULL),
(37, 2409, NULL),
(37, 2494, NULL),
(37, 2548, NULL),
(38, 1592, NULL),
(38, 1649, NULL),
(38, 1661, NULL),
(38, 1885, NULL),
(38, 2444, NULL),
(38, 2450, NULL),
(38, 2599, NULL),
(38, 2643, NULL),
(38, 2644, NULL),
(38, 2690, NULL),
(38, 2784, NULL),
(38, 2785, NULL),
(38, 2843, NULL),
(38, 2890, NULL),
(38, 100118, 'M000'),
(39, 787, NULL),
(39, 1398, NULL),
(39, 1649, NULL),
(39, 2525, NULL),
(39, 2632, NULL),
(39, 2643, NULL),
(40, 1365, NULL),
(40, 2494, NULL),
(40, 2613, NULL),
(40, 2683, NULL),
(40, 2757, NULL),
(40, 2825, NULL),
(41, 1893, NULL),
(41, 1954, NULL),
(41, 2113, NULL),
(41, 2253, NULL),
(41, 2409, NULL),
(41, 2425, NULL),
(41, 2494, NULL),
(41, 2796, NULL),
(41, 2847, NULL),
(42, 201, NULL),
(42, 361, NULL),
(42, 1455, NULL),
(42, 1592, NULL),
(42, 2494, NULL),
(42, 2784, NULL),
(42, 2786, NULL),
(42, 2898, NULL),
(43, 68, NULL),
(43, 412, NULL),
(43, 730, NULL),
(43, 1200, NULL),
(43, 1398, NULL),
(43, 1455, NULL),
(43, 1885, NULL),
(43, 2599, NULL),
(43, 2683, NULL),
(43, 2784, NULL),
(43, 2786, NULL),
(43, 2792, NULL),
(43, 2825, NULL),
(44, 117, NULL),
(44, 1592, NULL),
(44, 2643, NULL),
(44, 2683, NULL),
(44, 2843, NULL),
(44, 2900, NULL),
(45, 1875, NULL),
(45, 2415, NULL),
(45, 2783, NULL),
(45, 2825, NULL),
(45, 2841, NULL),
(45, 2870, NULL),
(45, 2902, NULL),
(45, 2907, NULL),
(45, 2914, NULL),
(46, 81, NULL),
(46, 787, NULL),
(46, 1455, NULL),
(46, 1649, NULL),
(46, 2569, NULL),
(46, 2683, NULL),
(46, 2757, NULL),
(46, 2853, NULL),
(46, 2866, NULL),
(47, 81, NULL),
(47, 918, NULL),
(47, 1384, NULL),
(47, 1423, NULL),
(47, 2569, NULL),
(47, 2825, NULL),
(47, 2902, NULL),
(47, 2943, NULL),
(48, 361, NULL),
(48, 412, NULL),
(49, 68, NULL),
(49, 81, NULL),
(49, 117, NULL),
(49, 201, NULL),
(49, 361, NULL),
(49, 412, NULL),
(49, 601, NULL),
(49, 730, NULL),
(49, 787, NULL),
(49, 1455, NULL),
(49, 1649, NULL),
(50, 68, NULL),
(50, 117, NULL),
(51, 68, NULL),
(51, 81, NULL),
(51, 201, NULL),
(51, 361, NULL),
(51, 412, NULL),
(51, 562, NULL),
(51, 601, NULL),
(51, 730, NULL),
(51, 1365, NULL),
(51, 1649, NULL),
(51, 2166, 'M000'),
(52, 201, NULL),
(53, 68, NULL),
(53, 787, NULL),
(53, 1398, NULL),
(53, 1693, NULL),
(53, 2792, NULL),
(53, 2926, NULL),
(53, 2957, NULL),
(53, 100004, NULL),
(54, 201, NULL),
(54, 361, NULL),
(54, 732, NULL),
(54, 1200, NULL),
(54, 2683, NULL),
(54, 2734, NULL),
(54, 2900, NULL),
(54, 2933, NULL),
(54, 2943, NULL),
(54, 2967, NULL),
(56, 1885, NULL),
(56, 2494, NULL),
(56, 2569, NULL),
(56, 2784, NULL),
(56, 2785, NULL),
(56, 2792, NULL),
(57, 1837, NULL),
(57, 1954, NULL),
(57, 2113, NULL),
(57, 2783, NULL),
(57, 2825, NULL),
(57, 2939, NULL),
(57, 2962, NULL),
(57, 2983, NULL),
(58, 1200, NULL),
(58, 2784, NULL),
(59, 100118, 'M000'),
(60, 601, NULL),
(60, 2525, NULL),
(60, 2683, NULL),
(60, 2785, NULL),
(60, 2786, NULL),
(60, 2825, NULL),
(60, 2843, NULL),
(61, 2569, NULL),
(61, 2683, NULL),
(61, 2786, NULL),
(61, 2900, NULL),
(62, 2943, NULL),
(63, 81, NULL),
(63, 117, NULL),
(63, 1398, NULL),
(63, 1455, NULL),
(63, 2569, NULL),
(63, 2690, NULL),
(63, 2783, NULL),
(63, 2792, NULL),
(65, 730, NULL),
(65, 918, NULL),
(65, 1423, NULL),
(65, 2166, NULL),
(65, 2866, 'M000'),
(65, 100070, NULL),
(65, 100159, NULL),
(67, 117, NULL),
(67, 2444, NULL),
(67, 2569, NULL),
(67, 2643, NULL),
(67, 2784, NULL),
(67, 2785, NULL),
(67, 2792, NULL),
(67, 2934, NULL),
(67, 100118, NULL),
(68, 2957, 'M7271'),
(69, 2900, 'M000'),
(70, 2900, 'M000'),
(71, 2900, 'M000'),
(72, 2494, 'M000'),
(72, 100046, 'M7307'),
(72, 100118, 'M7169'),
(73, 1398, 'M000'),
(73, 1592, 'M000'),
(74, 1592, 'M000'),
(74, 2900, 'M7113'),
(74, 2975, 'M6018'),
(74, 100094, 'M7352'),
(74, 100317, 'M5975'),
(75, 81, 'M000'),
(76, 1592, 'M000'),
(77, 1455, 'M7353'),
(77, 2783, 'M6695'),
(77, 100317, 'M5975'),
(78, 1398, 'M7412'),
(78, 2900, 'M7113'),
(78, 2975, 'M6018'),
(78, 100317, 'M5975'),
(80, 2444, 'M000'),
(80, 2784, 'M000'),
(81, 412, 'M6522'),
(81, 2444, 'M000'),
(81, 100317, 'M5975'),
(82, 100263, 'M6579'),
(82, 100289, 'M6579'),
(82, 100317, 'M5975'),
(83, 100350, 'M7480'),
(84, 2934, 'M5988');

-- --------------------------------------------------------

--
-- Structure de la table `tarifs`
--

CREATE TABLE `tarifs` (
  `id_Tarifs` int(3) NOT NULL,
  `libelle_Tarifs` varchar(100) DEFAULT NULL,
  `montant_Tarifs` decimal(10,1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `tarifs`
--

INSERT INTO `tarifs` (`id_Tarifs`, `libelle_Tarifs`, `montant_Tarifs`) VALUES
(21, 'Mand REG SANS OPT 2018', 35.5),
(22, 'Mand REG AVEC ADM 2018', 51.0),
(23, 'Mand REG AVEC PAIE 2018', 44.0),
(24, 'TARIF SPECIAL', 25.0);

-- --------------------------------------------------------

--
-- Structure de la table `typeadh`
--

CREATE TABLE `typeadh` (
  `idADH_TypeADH` varchar(5) NOT NULL,
  `intitule_TypeADH` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `typeadh`
--

INSERT INTO `typeadh` (`idADH_TypeADH`, `intitule_TypeADH`) VALUES
('DISP', 'DISP'),
('MAND', 'Mandataire'),
('PREST', 'Prestataire');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` varchar(50) NOT NULL,
  `nom` varchar(20) NOT NULL,
  `prenom` varchar(20) NOT NULL,
  `mdp` varchar(60) NOT NULL,
  `mdp_sha1` char(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `users`
--

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_intervenants`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_intervenants` (
`numSalarie_Intervenants` int(5)
,`idSalarie_Intervenants` varchar(10)
,`dateEntree_Intervenants` date
,`dateSortie_Intervenants` date
,`archive_Intervenants` tinyint(1)
,`Certification_Intervenants` varchar(240)
,`tauxH_Intervenants` decimal(10,2)
,`rechCompl_Intervenants` tinyint(1)
,`nbHeureSem_Intervenants` varchar(100)
,`nbHeureMois_Intervenants` varchar(100)
,`ProposerPSC1_Intervenants` tinyint(1)
,`justificatifs_Intervenants` varchar(50)
,`candidats_numcandidat_candidats` int(4)
,`dateModif_Intervenants` varchar(100)
,`suivi_Intervenants` varchar(250)
,`arretTravail_Intervenants` tinyint(1)
,`numCandidat_Candidats` int(4)
,`titre_Candidats` varchar(3)
,`nom_Candidats` varchar(50)
,`prenom_Candidats` varchar(50)
,`dateNaiss_Candidats` datetime
,`lieuNaiss_Candidats` varchar(20)
,`paysNaiss_Candidats` varchar(20)
,`nationalite_Candidats` varchar(25)
,`numTitreSejour` varchar(15)
,`numSS_Candidats` varchar(21)
,`Mutuelle_Candidats` varchar(40)
,`CMU_Candidats` tinyint(1)
,`adresse_Candidats` varchar(50)
,`cp_Candidats` char(5)
,`ville_Candidats` varchar(50)
,`Quartier_Candidats` varchar(50)
,`telPortable_Candidats` varchar(14)
,`telFixe_Candidats` varchar(14)
,`TelUrg_Candidats` varchar(14)
,`email_Candidats` varchar(60)
,`permis_Candidats` tinyint(1)
,`vehicule_Candidats` tinyint(1)
,`statutPro_Candidats` varchar(15)
,`situationFamiliale_Candidats` varchar(15)
,`diplomes_Candidats` varchar(150)
,`qualifications_Candidats` varchar(100)
,`expBBmoins1a_Candidats` tinyint(1)
,`enfantHand_Candidats` tinyint(1)
,`disponibilites_Candidats` varchar(1000)
,`observations_Candidats` varchar(1000)
,`candidatureRetenue_Candidats` varchar(150)
,`dateEntretien_Candidats` datetime
,`travailVoulu_Candidats` varchar(50)
,`nomJF_Candidats` varchar(50)
,`year(dateNaiss_Candidats)` int(4)
,`day(dateNaiss_Candidats)` int(2)
,`month(dateNaiss_Candidats)` int(2)
);

-- --------------------------------------------------------

--
-- Structure de la vue `vue_intervenants`
--
DROP TABLE IF EXISTS `vue_intervenants`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vue_intervenants`  AS SELECT `intervenants`.`numSalarie_Intervenants` AS `numSalarie_Intervenants`, `intervenants`.`idSalarie_Intervenants` AS `idSalarie_Intervenants`, `intervenants`.`dateEntree_Intervenants` AS `dateEntree_Intervenants`, `intervenants`.`dateSortie_Intervenants` AS `dateSortie_Intervenants`, `intervenants`.`archive_Intervenants` AS `archive_Intervenants`, `intervenants`.`Certification_Intervenants` AS `Certification_Intervenants`, `intervenants`.`tauxH_Intervenants` AS `tauxH_Intervenants`, `intervenants`.`rechCompl_Intervenants` AS `rechCompl_Intervenants`, `intervenants`.`nbHeureSem_Intervenants` AS `nbHeureSem_Intervenants`, `intervenants`.`nbHeureMois_Intervenants` AS `nbHeureMois_Intervenants`, `intervenants`.`ProposerPSC1_Intervenants` AS `ProposerPSC1_Intervenants`, `intervenants`.`justificatifs_Intervenants` AS `justificatifs_Intervenants`, `intervenants`.`candidats_numcandidat_candidats` AS `candidats_numcandidat_candidats`, `intervenants`.`dateModif_Intervenants` AS `dateModif_Intervenants`, `intervenants`.`suivi_Intervenants` AS `suivi_Intervenants`, `intervenants`.`arretTravail_Intervenants` AS `arretTravail_Intervenants`, `candidats`.`numCandidat_Candidats` AS `numCandidat_Candidats`, `candidats`.`titre_Candidats` AS `titre_Candidats`, `candidats`.`nom_Candidats` AS `nom_Candidats`, `candidats`.`prenom_Candidats` AS `prenom_Candidats`, `candidats`.`dateNaiss_Candidats` AS `dateNaiss_Candidats`, `candidats`.`lieuNaiss_Candidats` AS `lieuNaiss_Candidats`, `candidats`.`paysNaiss_Candidats` AS `paysNaiss_Candidats`, `candidats`.`nationalite_Candidats` AS `nationalite_Candidats`, `candidats`.`numTitreSejour` AS `numTitreSejour`, `candidats`.`numSS_Candidats` AS `numSS_Candidats`, `candidats`.`Mutuelle_Candidats` AS `Mutuelle_Candidats`, `candidats`.`CMU_Candidats` AS `CMU_Candidats`, `candidats`.`adresse_Candidats` AS `adresse_Candidats`, `candidats`.`cp_Candidats` AS `cp_Candidats`, `candidats`.`ville_Candidats` AS `ville_Candidats`, `candidats`.`Quartier_Candidats` AS `Quartier_Candidats`, `candidats`.`telPortable_Candidats` AS `telPortable_Candidats`, `candidats`.`telFixe_Candidats` AS `telFixe_Candidats`, `candidats`.`TelUrg_Candidats` AS `TelUrg_Candidats`, `candidats`.`email_Candidats` AS `email_Candidats`, `candidats`.`permis_Candidats` AS `permis_Candidats`, `candidats`.`vehicule_Candidats` AS `vehicule_Candidats`, `candidats`.`statutPro_Candidats` AS `statutPro_Candidats`, `candidats`.`situationFamiliale_Candidats` AS `situationFamiliale_Candidats`, `candidats`.`diplomes_Candidats` AS `diplomes_Candidats`, `candidats`.`qualifications_Candidats` AS `qualifications_Candidats`, `candidats`.`expBBmoins1a_Candidats` AS `expBBmoins1a_Candidats`, `candidats`.`enfantHand_Candidats` AS `enfantHand_Candidats`, `candidats`.`disponibilites_Candidats` AS `disponibilites_Candidats`, `candidats`.`observations_Candidats` AS `observations_Candidats`, `candidats`.`candidatureRetenue_Candidats` AS `candidatureRetenue_Candidats`, `candidats`.`dateEntretien_Candidats` AS `dateEntretien_Candidats`, `candidats`.`travailVoulu_Candidats` AS `travailVoulu_Candidats`, `candidats`.`nomJF_Candidats` AS `nomJF_Candidats`, year(`candidats`.`dateNaiss_Candidats`) AS `year(dateNaiss_Candidats)`, dayofmonth(`candidats`.`dateNaiss_Candidats`) AS `day(dateNaiss_Candidats)`, month(`candidats`.`dateNaiss_Candidats`) AS `month(dateNaiss_Candidats)` FROM (`intervenants` join `candidats` on(`candidats`.`numCandidat_Candidats` = `intervenants`.`candidats_numcandidat_candidats`)) ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `antimatching`
--
ALTER TABLE `antimatching`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Index pour la table `besoinsfamille`
--
ALTER TABLE `besoinsfamille`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `candidats`
--
ALTER TABLE `candidats`
  ADD PRIMARY KEY (`numCandidat_Candidats`);

--
-- Index pour la table `disponibilitesintervenants`
--
ALTER TABLE `disponibilitesintervenants`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `enfants`
--
ALTER TABLE `enfants`
  ADD PRIMARY KEY (`idEnfant_Enfants`),
  ADD KEY `FK_Enfants_numero_Famille` (`numero_Famille`);

--
-- Index pour la table `entretiens`
--
ALTER TABLE `entretiens`
  ADD PRIMARY KEY (`num`,`numSalarie_Intervenants`),
  ADD KEY `numSalarie_Intervenants` (`numSalarie_Intervenants`);

--
-- Index pour la table `factures`
--
ALTER TABLE `factures`
  ADD PRIMARY KEY (`idFact_Factures`),
  ADD KEY `FK_Factures_numero_Famille` (`numero_Famille`),
  ADD KEY `fk_Factures` (`montantFact_Factures`);

--
-- Index pour la table `famille`
--
ALTER TABLE `famille`
  ADD PRIMARY KEY (`numero_Famille`);

--
-- Index pour la table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`idForm_Formations`);

--
-- Index pour la table `intervenants`
--
ALTER TABLE `intervenants`
  ADD PRIMARY KEY (`numSalarie_Intervenants`),
  ADD KEY `FK_Intervenants_candidats_numcandidat_candidats` (`candidats_numcandidat_candidats`);

--
-- Index pour la table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`idParent_Parents`),
  ADD KEY `FK_Parents_numeroFamille` (`numero_Famille`);

--
-- Index pour la table `partage`
--
ALTER TABLE `partage`
  ADD PRIMARY KEY (`famille1`,`famille2`),
  ADD KEY `famille2` (`famille2`);

--
-- Index pour la table `prestations`
--
ALTER TABLE `prestations`
  ADD PRIMARY KEY (`idPresta_Prestations`);

--
-- Index pour la table `proposer`
--
ALTER TABLE `proposer`
  ADD PRIMARY KEY (`idPresta_Prestations`,`numSalarie_Intervenants`,`numero_Famille`,`idADH_TypeADH`,`hDeb_Proposer`,`jour_Proposer`,`DateDeb_Proposer`),
  ADD KEY `FK_Proposer_numero_Famille` (`numero_Famille`),
  ADD KEY `FK_Proposer_idADH_TypeADH` (`idADH_TypeADH`),
  ADD KEY `FK_Proposer_numSalarie_Intervenants` (`numSalarie_Intervenants`);

--
-- Index pour la table `publipostagecontrats`
--
ALTER TABLE `publipostagecontrats`
  ADD PRIMARY KEY (`codeClient`);

--
-- Index pour la table `suivre`
--
ALTER TABLE `suivre`
  ADD PRIMARY KEY (`idForm_Formations`,`numSalarie_Intervenants`),
  ADD KEY `FK_Suivre_numSalarie_Intervenants` (`numSalarie_Intervenants`);

--
-- Index pour la table `tarifs`
--
ALTER TABLE `tarifs`
  ADD PRIMARY KEY (`id_Tarifs`);

--
-- Index pour la table `typeadh`
--
ALTER TABLE `typeadh`
  ADD PRIMARY KEY (`idADH_TypeADH`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `antimatching`
--
ALTER TABLE `antimatching`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `besoinsfamille`
--
ALTER TABLE `besoinsfamille`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1345;

--
-- AUTO_INCREMENT pour la table `candidats`
--
ALTER TABLE `candidats`
  MODIFY `numCandidat_Candidats` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11471;

--
-- AUTO_INCREMENT pour la table `disponibilitesintervenants`
--
ALTER TABLE `disponibilitesintervenants`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1457;

--
-- AUTO_INCREMENT pour la table `enfants`
--
ALTER TABLE `enfants`
  MODIFY `idEnfant_Enfants` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19600;

--
-- AUTO_INCREMENT pour la table `factures`
--
ALTER TABLE `factures`
  MODIFY `idFact_Factures` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2338;

--
-- AUTO_INCREMENT pour la table `formations`
--
ALTER TABLE `formations`
  MODIFY `idForm_Formations` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT pour la table `intervenants`
--
ALTER TABLE `intervenants`
  MODIFY `numSalarie_Intervenants` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101173;

--
-- AUTO_INCREMENT pour la table `parents`
--
ALTER TABLE `parents`
  MODIFY `idParent_Parents` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19892;

--
-- AUTO_INCREMENT pour la table `tarifs`
--
ALTER TABLE `tarifs`
  MODIFY `id_Tarifs` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `enfants`
--
ALTER TABLE `enfants`
  ADD CONSTRAINT `FK_Enfants_numero_Famille` FOREIGN KEY (`numero_Famille`) REFERENCES `famille` (`numero_Famille`);

--
-- Contraintes pour la table `entretiens`
--
ALTER TABLE `entretiens`
  ADD CONSTRAINT `entretiens_ibfk_1` FOREIGN KEY (`numSalarie_Intervenants`) REFERENCES `intervenants` (`numSalarie_Intervenants`);

--
-- Contraintes pour la table `factures`
--
ALTER TABLE `factures`
  ADD CONSTRAINT `FK_Factures_numero_Famille` FOREIGN KEY (`numero_Famille`) REFERENCES `famille` (`numero_Famille`),
  ADD CONSTRAINT `fk_Factures` FOREIGN KEY (`montantFact_Factures`) REFERENCES `tarifs` (`id_Tarifs`);

--
-- Contraintes pour la table `intervenants`
--
ALTER TABLE `intervenants`
  ADD CONSTRAINT `FK_Intervenants_candidats_numcandidat_candidats` FOREIGN KEY (`candidats_numcandidat_candidats`) REFERENCES `candidats` (`numCandidat_Candidats`);

--
-- Contraintes pour la table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `FK_Parents_numeroFamille` FOREIGN KEY (`numero_Famille`) REFERENCES `famille` (`numero_Famille`);

--
-- Contraintes pour la table `partage`
--
ALTER TABLE `partage`
  ADD CONSTRAINT `partage_ibfk_1` FOREIGN KEY (`famille1`) REFERENCES `famille` (`numero_Famille`),
  ADD CONSTRAINT `partage_ibfk_2` FOREIGN KEY (`famille2`) REFERENCES `famille` (`numero_Famille`);

--
-- Contraintes pour la table `proposer`
--
ALTER TABLE `proposer`
  ADD CONSTRAINT `FK_Proposer_idADH_TypeADH` FOREIGN KEY (`idADH_TypeADH`) REFERENCES `typeadh` (`idADH_TypeADH`),
  ADD CONSTRAINT `FK_Proposer_idPresta_Prestations` FOREIGN KEY (`idPresta_Prestations`) REFERENCES `prestations` (`idPresta_Prestations`),
  ADD CONSTRAINT `FK_Proposer_numSalarie_Intervenants` FOREIGN KEY (`numSalarie_Intervenants`) REFERENCES `intervenants` (`numSalarie_Intervenants`),
  ADD CONSTRAINT `FK_Proposer_numero_Famille` FOREIGN KEY (`numero_Famille`) REFERENCES `famille` (`numero_Famille`);

--
-- Contraintes pour la table `suivre`
--
ALTER TABLE `suivre`
  ADD CONSTRAINT `FK_Suivre_idForm_Formations` FOREIGN KEY (`idForm_Formations`) REFERENCES `formations` (`idForm_Formations`),
  ADD CONSTRAINT `FK_Suivre_numSalarie_Intervenants` FOREIGN KEY (`numSalarie_Intervenants`) REFERENCES `intervenants` (`numSalarie_Intervenants`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
