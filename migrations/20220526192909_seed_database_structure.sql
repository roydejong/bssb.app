/*
 Navicat MySQL Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 80029
 Source Host           : localhost:3306
 Source Schema         : bssb

 Target Server Type    : MySQL
 Target Server Version : 80029
 File Encoding         : 65001

 Date: 26/05/2022 21:36:41
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for hosted_game_announcers
-- ----------------------------
DROP TABLE IF EXISTS `hosted_game_announcers`;
CREATE TABLE `hosted_game_announcers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `hosted_game_id` int unsigned NOT NULL,
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_seen` datetime NOT NULL,
  `last_seen` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id_UNIQUE` (`id`) USING BTREE,
  UNIQUE KEY `game_endpoint_UNIQUE` (`hosted_game_id`,`endpoint`) USING BTREE,
  CONSTRAINT `hosted_game_id` FOREIGN KEY (`hosted_game_id`) REFERENCES `hosted_games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for hosted_game_players
-- ----------------------------
DROP TABLE IF EXISTS `hosted_game_players`;
CREATE TABLE `hosted_game_players` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `hosted_game_id` int unsigned NOT NULL,
  `sort_index` int NOT NULL,
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_host` tinyint NOT NULL DEFAULT '0',
  `is_announcer` tinyint NOT NULL DEFAULT '0',
  `latency` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `is_connected` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id_UNIQUE` (`id`) USING BTREE,
  UNIQUE KEY `hgp_game_and_sort_index` (`hosted_game_id`,`sort_index`) USING BTREE,
  KEY `hgp_game_id_idx` (`hosted_game_id`) USING BTREE,
  CONSTRAINT `hgp_game_id` FOREIGN KEY (`hosted_game_id`) REFERENCES `hosted_games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=406181 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for hosted_games
-- ----------------------------
DROP TABLE IF EXISTS `hosted_games`;
CREATE TABLE `hosted_games` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `server_code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `game_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `player_count` tinyint unsigned NOT NULL DEFAULT '0',
  `player_limit` tinyint unsigned NOT NULL DEFAULT '0',
  `is_modded` tinyint unsigned NOT NULL DEFAULT '0',
  `first_seen` datetime NOT NULL,
  `last_update` datetime NOT NULL,
  `lobby_state` tinyint unsigned NOT NULL,
  `level_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `song_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `song_author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `difficulty` tinyint unsigned DEFAULT NULL,
  `characteristic` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `master_server_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `master_server_port` int unsigned DEFAULT NULL,
  `master_status_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `mp_core_version` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mp_ex_version` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mod_name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ServerBrowser',
  `mod_version` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `game_version` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'player_host',
  `host_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=98574 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for level_histories
-- ----------------------------
DROP TABLE IF EXISTS `level_histories`;
CREATE TABLE `level_histories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `session_game_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hosted_game_id` int unsigned NOT NULL,
  `level_record_id` int unsigned NOT NULL,
  `difficulty` int NOT NULL,
  `characteristic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modifiers` json DEFAULT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `played_player_count` int unsigned DEFAULT NULL,
  `finished_player_count` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `session_game_id` (`session_game_id`) USING BTREE,
  KEY `hosted_game_id` (`hosted_game_id`) USING BTREE,
  KEY `lh_level_record` (`level_record_id`) USING BTREE,
  CONSTRAINT `lh_hosted_game` FOREIGN KEY (`hosted_game_id`) REFERENCES `hosted_games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `lh_level_record` FOREIGN KEY (`level_record_id`) REFERENCES `level_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for level_history_players
-- ----------------------------
DROP TABLE IF EXISTS `level_history_players`;
CREATE TABLE `level_history_players` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `level_history_id` int unsigned NOT NULL,
  `player_id` int unsigned NOT NULL,
  `end_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `multiplied_score` int DEFAULT NULL,
  `modified_score` int DEFAULT NULL,
  `score_rank` int DEFAULT NULL,
  `good_cuts` int DEFAULT NULL,
  `bad_cuts` int DEFAULT NULL,
  `miss_count` int DEFAULT NULL,
  `full_combo` tinyint(1) DEFAULT NULL,
  `max_combo` int DEFAULT NULL,
  `badge_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placement` int DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `lhp_level_history_id` (`level_history_id`) USING BTREE,
  KEY `lhp_player_id` (`player_id`) USING BTREE,
  CONSTRAINT `lhp_level_history_id` FOREIGN KEY (`level_history_id`) REFERENCES `level_histories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `lhp_player_id` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for level_records
-- ----------------------------
DROP TABLE IF EXISTS `level_records`;
CREATE TABLE `level_records` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `level_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beatsaver_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `song_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `song_sub_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `song_author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level_author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `stat_play_count` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `level_id_UNIQUE` (`level_id`) USING BTREE,
  UNIQUE KEY `beatsaver_id_UNIQUE` (`beatsaver_id`) USING BTREE,
  UNIQUE KEY `hash_UNIQUE` (`hash`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=50472 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for player_avatars
-- ----------------------------
DROP TABLE IF EXISTS `player_avatars`;
CREATE TABLE `player_avatars` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int unsigned NOT NULL,
  `head_top_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'None',
  `head_top_primary_color` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `head_top_secondary_color` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `glasses_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'None',
  `glasses_color` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `facial_hair_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'None',
  `facial_hair_color` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hands_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BareHands',
  `hands_color` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `clothes_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `clothes_primary_color` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `clothes_secondary_color` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `clothes_detail_color` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `skin_color_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Default',
  `eyes_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mouth_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `avatar_player_id` (`player_id`) USING BTREE,
  CONSTRAINT `avatar_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for players
-- ----------------------------
DROP TABLE IF EXISTS `players`;
CREATE TABLE `players` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_seen` datetime NOT NULL,
  `platform_type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform_user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen` datetime NOT NULL,
  `profile_bio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `show_steam` tinyint unsigned NOT NULL DEFAULT '1',
  `show_score_saber` tinyint unsigned NOT NULL DEFAULT '1',
  `show_history` tinyint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `user_id_unique` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=98122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for system_config
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `server_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id_UNIQUE` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
