-- MySQL dump 10.13  Distrib 8.0.17, for Win64 (x86_64)
--
-- Host: wsl.local    Database: bssb
-- ------------------------------------------------------
-- Server version	8.0.21-12

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `hosted_games`
--

DROP TABLE IF EXISTS `hosted_games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hosted_games` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `server_code` varchar(5) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `owner_id` varchar(255) NOT NULL,
  `owner_name` varchar(255) NOT NULL,
  `player_count` tinyint unsigned NOT NULL DEFAULT '0',
  `player_limit` tinyint unsigned NOT NULL DEFAULT '0',
  `is_modded` tinyint unsigned NOT NULL DEFAULT '0',
  `first_seen` datetime NOT NULL,
  `last_update` datetime NOT NULL,
  `lobby_state` tinyint unsigned NOT NULL,
  `level_id` varchar(255) DEFAULT NULL,
  `song_name` varchar(255) DEFAULT NULL,
  `song_author` varchar(255) DEFAULT NULL,
  `difficulty` tinyint unsigned DEFAULT NULL,
  `platform` varchar(16) NOT NULL DEFAULT 'unknown',
  `master_server_host` varchar(255) DEFAULT NULL,
  `master_server_port` int unsigned DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `level_records`
--

DROP TABLE IF EXISTS `level_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `level_records` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `level_id` varchar(255) NOT NULL,
  `hash` varchar(128) DEFAULT NULL,
  `beatsaver_id` varchar(128) DEFAULT NULL,
  `cover_url` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `song_name` varchar(255) NOT NULL,
  `song_author` varchar(255) DEFAULT NULL,
  `level_author` varchar(255) DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `level_id_UNIQUE` (`level_id`),
  UNIQUE KEY `beatsaver_id_UNIQUE` (`beatsaver_id`),
  UNIQUE KEY `hash_UNIQUE` (`hash`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-11-20 16:41:28
