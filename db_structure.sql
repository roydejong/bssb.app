/*
 Navicat MySQL Data Transfer

 Source Server         : Local WSL
 Source Server Type    : MySQL
 Source Server Version : 80027
 Source Host           : ubuntu.wsl:3306
 Source Schema         : bssb

 Target Server Type    : MySQL
 Target Server Version : 80027
 File Encoding         : 65001

 Date: 17/01/2022 00:40:47
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for hosted_game_announcers
-- ----------------------------
DROP TABLE IF EXISTS `hosted_game_announcers`;
CREATE TABLE `hosted_game_announcers`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `player_id` int UNSIGNED NOT NULL,
  `game_id` int UNSIGNED NOT NULL,
  `last_announce` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `id_UNIQUE`(`id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for hosted_game_players
-- ----------------------------
DROP TABLE IF EXISTS `hosted_game_players`;
CREATE TABLE `hosted_game_players`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `hosted_game_id` int UNSIGNED NOT NULL,
  `sort_index` int NOT NULL,
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_host` tinyint NOT NULL DEFAULT 0,
  `is_announcer` tinyint NOT NULL DEFAULT 0,
  `latency` decimal(8, 4) NOT NULL DEFAULT 0.0000,
  `is_connected` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `id_UNIQUE`(`id` ASC) USING BTREE,
  UNIQUE INDEX `hgp_game_and_sort_index`(`hosted_game_id` ASC, `sort_index` ASC) USING BTREE,
  INDEX `hgp_game_id_idx`(`hosted_game_id` ASC) USING BTREE,
  CONSTRAINT `hgp_game_id` FOREIGN KEY (`hosted_game_id`) REFERENCES `hosted_games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 262238 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for hosted_games
-- ----------------------------
DROP TABLE IF EXISTS `hosted_games`;
CREATE TABLE `hosted_games`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `server_code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `game_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `owner_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `owner_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `player_count` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `player_limit` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `is_modded` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `first_seen` datetime NOT NULL,
  `last_update` datetime NOT NULL,
  `lobby_state` tinyint UNSIGNED NOT NULL,
  `level_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `song_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `song_author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `difficulty` tinyint UNSIGNED NULL DEFAULT NULL,
  `characteristic` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `platform` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'unknown',
  `master_server_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `master_server_port` int UNSIGNED NULL DEFAULT NULL,
  `ended_at` datetime NULL DEFAULT NULL,
  `mp_core_version` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `mp_ex_version` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `mod_name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'ServerBrowser',
  `mod_version` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `game_version` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `server_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'player_host',
  `host_secret` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `manager_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 67350 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for level_records
-- ----------------------------
DROP TABLE IF EXISTS `level_records`;
CREATE TABLE `level_records`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `level_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `beatsaver_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `cover_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `song_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `song_author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `level_author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `duration` int NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `stat_play_count` int UNSIGNED NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `level_id_UNIQUE`(`level_id` ASC) USING BTREE,
  UNIQUE INDEX `beatsaver_id_UNIQUE`(`beatsaver_id` ASC) USING BTREE,
  UNIQUE INDEX `hash_UNIQUE`(`hash` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 45896 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for system_config
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `server_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `id_UNIQUE`(`id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
