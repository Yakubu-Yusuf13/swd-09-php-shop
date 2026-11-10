-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: swd_shop
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `swd_shop`
--

/*!40000 DROP DATABASE IF EXISTS `swd_shop`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `swd_shop` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `swd_shop`;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Electronics','electronics','2026-11-10 16:31:05'),(2,'Home & Living','home-living','2026-11-10 16:31:05'),(3,'Fashion','fashion','2026-11-10 16:31:05'),(4,'Health & Beauty','health-beauty','2026-11-10 16:31:05'),(5,'Groceries','groceries','2026-11-10 16:31:05');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_reference` (`reference`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `slug` varchar(150) NOT NULL,
  `name` varchar(150) NOT NULL,
  `short_description` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'wireless-bluetooth-speaker','Wireless Bluetooth Speaker','Portable sound with rich bass and all-day battery.','Perfect for home, office, and outdoor listening with deep bass, durable design, and Bluetooth pairing.',24500.00,24,'https://placehold.co/600x400?text=Bluetooth+Speaker',1,'2026-11-10 16:31:05'),(2,1,'smart-watch-pro','Smart Watch Pro','Track workouts, sleep, and notifications with ease.','A modern smartwatch for daily fitness tracking, notifications, and stylish everyday wear.',68000.00,15,'https://placehold.co/600x400?text=Smart+Watch',1,'2026-11-10 16:31:05'),(3,1,'usb-c-fast-charger','USB-C Fast Charger','High-speed charging for phones and tablets.','Built for modern devices that need safe and efficient charging throughout the day.',12000.00,36,'https://placehold.co/600x400?text=Fast+Charger',1,'2026-11-10 16:31:05'),(4,2,'minimalist-lamp','Minimalist Desk Lamp','Warm lighting with a modern finish.','Brighten your workspace with soft, adjustable lighting designed for comfort and style.',18500.00,13,'https://placehold.co/600x400?text=Desk+Lamp',1,'2026-11-10 16:31:05'),(5,2,'cotton-bath-linen-set','Cotton Bath Linen Set','Soft towels and robes for a fresh daily ritual.','Ultra-soft and absorbent linen set made to provide a premium feel for your bathroom.',21000.00,18,'https://placehold.co/600x400?text=Bath+Linen',1,'2026-11-10 16:31:05'),(6,2,'ceramic-storage-jar','Ceramic Storage Jar','A stylish kitchen essential for pantry organization.','Keep ingredients fresh and elevate your kitchen counter with neat, modern storage.',9000.00,42,'https://placehold.co/600x400?text=Storage+Jar',1,'2026-11-10 16:31:05'),(7,3,'classic-denim-jacket','Classic Denim Jacket','Lightweight layering for casual everyday wear.','Comfortable and versatile staple piece designed for cool afternoons and city outings.',38000.00,9,'https://placehold.co/600x400?text=Denim+Jacket',1,'2026-11-10 16:31:05'),(8,3,'leather-strap-watch','Leather Strap Watch','Elegant timepiece with a premium leather band.','A refined wrist accessory suitable for workdays, events, and smart casual styling.',54000.00,11,'https://placehold.co/600x400?text=Leather+Watch',1,'2026-11-10 16:31:05'),(9,3,'travel-backpack','Travel Backpack','Functional storage for work, study, or weekend escapes.','Thoughtfully designed with padded compartments, water resistance, and practical storage.',31000.00,17,'https://placehold.co/600x400?text=Travel+Backpack',1,'2026-11-10 16:31:05'),(10,4,'vitamin-c-serum','Vitamin C Glow Serum','Daily skin-brightening support with hydration.','Helps nourish tired skin and improves radiance for a fresher, healthier look.',16000.00,28,'https://placehold.co/600x400?text=Glow+Serum',1,'2026-11-10 16:31:05'),(11,4,'electric-face-brush','Electric Face Brush','Gentle cleansing for smoother-looking skin.','A compact, easy-to-use facial care helper for deep cleansing and exfoliation.',22500.00,12,'https://placehold.co/600x400?text=Face+Brush',1,'2026-11-10 16:31:05'),(12,4,'aroma-diffuser','Aroma Diffuser','Create a calm, fresh atmosphere at home.','Enjoy a soothing fragrance experience with an elegant diffuser built for everyday relaxation.',26000.00,14,'https://placehold.co/600x400?text=Aroma+Diffuser',1,'2026-11-10 16:31:05'),(13,5,'organic-rice-bag','Organic Rice Bag','Premium staple for healthy home meals.','Naturally grown and carefully packed for families who want a dependable pantry staple.',11000.00,48,'https://placehold.co/600x400?text=Organic+Rice',1,'2026-11-10 16:31:05'),(14,5,'olive-oil-bottle','Extra Virgin Olive Oil','Rich, smooth finishing oil for your kitchen.','Adds flavor and depth to everyday cooking with a premium, cold-pressed finish.',17000.00,19,'https://placehold.co/600x400?text=Olive+Oil',1,'2026-11-10 16:31:05'),(15,5,'fruit-box','Fresh Fruit Box','Seasonal fruits picked for freshness and sweetness.','A colorful assortment of fruit delivered fresh to help you add nutrition to your routine.',14500.00,0,'https://placehold.co/600x400?text=Fruit+Box',1,'2026-11-10 16:31:05');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-10 22:51:51
