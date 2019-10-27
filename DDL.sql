/*
 Navicat Premium Data Transfer

 Source Server         : Dedic 7 [Mugann]
 Source Server Type    : MySQL
 Source Server Version : 50727
 Source Host           : 127.0.0.1:3306
 Source Schema         : mxtest

 Target Server Type    : MySQL
 Target Server Version : 50727
 File Encoding         : 65001

 Date: 28/10/2019 01:42:59
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for goods
-- ----------------------------
DROP TABLE IF EXISTS `goods`;
CREATE TABLE `goods`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Артикул товара',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Название товара',
  `price` decimal(10, 2) NOT NULL COMMENT 'Цена',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Описание товара',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 569 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of goods
-- ----------------------------
INSERT INTO `goods` VALUES (1, 'Туфли Klk', 100.00, 'Просто туфли');
INSERT INTO `goods` VALUES (2, 'Ноутбук Gnusmus K1', 10.00, 'Обычный ноутбук');
INSERT INTO `goods` VALUES (3, 'Смартфон Koku P23', 15.00, 'Хороший смартфон');
INSERT INTO `goods` VALUES (4, 'Смартфон Uin Go5', 15.00, 'Плохой смартфон');

-- ----------------------------
-- Table structure for goods_feature
-- ----------------------------
DROP TABLE IF EXISTS `goods_feature`;
CREATE TABLE `goods_feature`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL COMMENT 'ID товара',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Название характеристики',
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Значение характеристики',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `goods_id`(`goods_id`, `name`) USING BTREE,
  INDEX `name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1404 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of goods_feature
-- ----------------------------
INSERT INTO `goods_feature` VALUES (1, 1, 'fea1', '1234');
INSERT INTO `goods_feature` VALUES (2, 1, 'fea2', 'Valu');
INSERT INTO `goods_feature` VALUES (3, 2, 'fea1', 'Olu');
INSERT INTO `goods_feature` VALUES (4, 2, 'type', 'Unf-unf');
INSERT INTO `goods_feature` VALUES (5, 2, 'noom', 'Паук');
INSERT INTO `goods_feature` VALUES (6, 3, 'fea2', 'Hotel');
INSERT INTO `goods_feature` VALUES (7, 4, 'type', 'mng');
INSERT INTO `goods_feature` VALUES (8, 4, 'talosta', 'Q');

SET FOREIGN_KEY_CHECKS = 1;
