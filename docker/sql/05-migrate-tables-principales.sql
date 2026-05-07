-- Migration des tables uniques de bdchaudoudoux vers bdchaudoudoux_horaire
-- Permet d'unifier tout dans une seule base de données

USE `bdchaudoudoux_horaire`;

CREATE TABLE IF NOT EXISTS `famille`         AS SELECT * FROM `bdchaudoudoux`.`famille`;
CREATE TABLE IF NOT EXISTS `parents`         AS SELECT * FROM `bdchaudoudoux`.`parents`;
CREATE TABLE IF NOT EXISTS `enfants`         AS SELECT * FROM `bdchaudoudoux`.`enfants`;
CREATE TABLE IF NOT EXISTS `proposer`        AS SELECT * FROM `bdchaudoudoux`.`proposer`;
CREATE TABLE IF NOT EXISTS `prestations`     AS SELECT * FROM `bdchaudoudoux`.`prestations`;
CREATE TABLE IF NOT EXISTS `entretiens`      AS SELECT * FROM `bdchaudoudoux`.`entretiens`;
CREATE TABLE IF NOT EXISTS `factures`        AS SELECT * FROM `bdchaudoudoux`.`factures`;
CREATE TABLE IF NOT EXISTS `antimatching`    AS SELECT * FROM `bdchaudoudoux`.`antimatching`;
CREATE TABLE IF NOT EXISTS `besoinsfamille`  AS SELECT * FROM `bdchaudoudoux`.`besoinsfamille`;
CREATE TABLE IF NOT EXISTS `partage`         AS SELECT * FROM `bdchaudoudoux`.`partage`;
CREATE TABLE IF NOT EXISTS `publipostagecontrats` AS SELECT * FROM `bdchaudoudoux`.`publipostagecontrats`;
CREATE TABLE IF NOT EXISTS `tarifs`          AS SELECT * FROM `bdchaudoudoux`.`tarifs`;
CREATE TABLE IF NOT EXISTS `users`           AS SELECT * FROM `bdchaudoudoux`.`users`;

SELECT 'Tables principales migrées vers bdchaudoudoux_horaire' AS message;
