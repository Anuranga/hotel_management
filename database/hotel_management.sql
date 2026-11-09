-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hotel_management
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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(255) NOT NULL COMMENT 'discount_override, item_cancellation, price_update',
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` bigint(20) unsigned NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audit_logs_event_type_index` (`event_type`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `station` enum('kitchen','bar') NOT NULL DEFAULT 'kitchen',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Starters','starters','kitchen',1,1,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(2,'Mains','mains','kitchen',2,1,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(3,'Desserts','desserts','bar',3,1,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(4,'Beverages','beverages','bar',4,1,'2026-11-09 01:42:33','2026-11-09 01:42:33');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_logs`
--

DROP TABLE IF EXISTS `inventory_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_item_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `quantity_change` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL COMMENT 'sale, spoilage, restock',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_logs_menu_item_id_foreign` (`menu_item_id`),
  KEY `inventory_logs_order_id_foreign` (`order_id`),
  CONSTRAINT `inventory_logs_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_logs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_logs`
--

LOCK TABLES `inventory_logs` WRITE;
/*!40000 ALTER TABLE `inventory_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `is_vat_exempt` tinyint(1) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT -1 COMMENT '-1 = unlimited stock',
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_category_id_foreign` (`category_id`),
  CONSTRAINT `menu_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,1,'Negombo Crispy Calamari Rings','Golden fried lagoon calamari tossed with curry leaves, crushed black pepper, and lime aioli.',1850.00,680.00,1,0,40,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(2,1,'Roasted Pumpkin & Coconut Veloute','Silky heirloom pumpkin soup infused with toasted cumin and Ceylon cinnamon croutons.',1250.00,390.00,1,0,-1,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(3,1,'Mutton Rolls with Kochchi Dip (3 Pcs)','Traditional spiced slow-cooked mutton and potato croquettes crumb-fried to perfection.',1450.00,520.00,1,0,25,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(4,2,'Jaffna Lagoon Crab Curry & Roast Paan','Signature mud crab simmered in roasted northern spices, drumstick leaves, and thick coconut milk.',4600.00,1950.00,1,0,15,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(5,2,'Odara Signature Lamprais','Dutch-Burgher banana-leaf baked samba rice with mixed meat curry, seeni sambol, frikkadels, and ash plantain.',2950.00,1100.00,1,0,30,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(6,2,'Chargrilled Mahi-Mahi Fillet','Southern coast mahi-mahi with garlic herb butter, whipped potato mash, and grilled asparagus.',3400.00,1320.00,1,0,20,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(7,3,'Kithul Jaggery Watalappan','Steamed spiced coconut custard sweetened with pure kithul jaggery and roasted cashews.',1150.00,340.00,1,0,35,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(8,3,'Warm Dark Chocolate Lava Cake','70% single-origin chocolate fondant served with Madagascar vanilla bean gelato.',1550.00,490.00,1,0,20,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(9,4,'Chilled King Coconut (Thambili)','Freshly harvested golden Sri Lankan king coconut served chilled (VAT Exempt natural produce).',650.00,180.00,1,1,-1,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(10,4,'Ceylon Artisanal Iced Peach Tea','Nuwara Eliya high-grown broken orange pekoe brewed cold with white peach and mint.',890.00,210.00,1,0,-1,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33'),(11,4,'Knuckles Mountain Spring Water (750ml)','Still natural mineral water bottled at the source.',450.00,140.00,1,1,100,NULL,'2026-11-09 01:42:33','2026-11-09 01:42:33');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0000_01_01_000000_create_roles_table',1),(2,'0001_01_01_000000_create_users_table',1),(3,'0001_01_01_000001_create_cache_table',1),(4,'0001_01_01_000002_create_jobs_table',1),(5,'2026_11_09_040038_create_restaurant_tables_table',1),(6,'2026_11_09_040039_create_categories_table',1),(7,'2026_11_09_040040_create_menu_items_table',1),(8,'2026_11_09_040041_create_orders_table',1),(9,'2026_11_09_040042_create_order_batches_table',1),(10,'2026_11_09_040043_create_order_items_table',1),(11,'2026_11_09_040044_create_inventory_logs_table',1),(12,'2026_11_09_040045_create_audit_logs_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_batches`
--

DROP TABLE IF EXISTS `order_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `batch_number` int(11) NOT NULL DEFAULT 1 COMMENT '1 = initial, 2+ = add-ons',
  `status` enum('pending','confirmed','preparing','ready','delivered') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_batches_order_id_foreign` (`order_id`),
  CONSTRAINT `order_batches_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_batches`
--

LOCK TABLES `order_batches` WRITE;
/*!40000 ALTER TABLE `order_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_batch_id` bigint(20) unsigned NOT NULL,
  `menu_item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `item_status` enum('pending','cooking','ready','served','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_batch_id_foreign` (`order_batch_id`),
  KEY `order_items_menu_item_id_foreign` (`menu_item_id`),
  CONSTRAINT `order_items_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`),
  CONSTRAINT `order_items_order_batch_id_foreign` FOREIGN KEY (`order_batch_id`) REFERENCES `order_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `restaurant_table_id` bigint(20) unsigned NOT NULL,
  `session_token` char(36) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `order_status` enum('pending_confirmation','confirmed','preparing','served','settled','cancelled') NOT NULL DEFAULT 'pending_confirmation',
  `gross_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_approved_by` bigint(20) unsigned DEFAULT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_charge_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `service_charge_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','card','online','unpaid') NOT NULL DEFAULT 'unpaid',
  `settled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_restaurant_table_id_foreign` (`restaurant_table_id`),
  KEY `orders_discount_approved_by_foreign` (`discount_approved_by`),
  KEY `orders_session_token_index` (`session_token`),
  KEY `orders_customer_phone_index` (`customer_phone`),
  KEY `orders_order_status_index` (`order_status`),
  CONSTRAINT `orders_discount_approved_by_foreign` FOREIGN KEY (`discount_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_restaurant_table_id_foreign` FOREIGN KEY (`restaurant_table_id`) REFERENCES `restaurant_tables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurant_tables`
--

DROP TABLE IF EXISTS `restaurant_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurant_tables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `table_number` varchar(255) NOT NULL,
  `qr_token` char(36) NOT NULL,
  `status` enum('vacant','occupied','reserved') NOT NULL DEFAULT 'vacant',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restaurant_tables_table_number_unique` (`table_number`),
  UNIQUE KEY `restaurant_tables_qr_token_unique` (`qr_token`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurant_tables`
--

LOCK TABLES `restaurant_tables` WRITE;
/*!40000 ALTER TABLE `restaurant_tables` DISABLE KEYS */;
INSERT INTO `restaurant_tables` VALUES (1,'T-01','dc325d32-5d25-487c-ad2a-262b30f7fb3b','vacant','2026-11-09 01:42:33','2026-11-09 01:42:33'),(2,'T-02','e9e427f2-87f2-416d-bfc2-e5b071ae117d','vacant','2026-11-09 01:42:33','2026-11-09 01:42:33'),(3,'T-03','ef0245d9-f04d-4585-8ff5-4f62f5d8b5c4','vacant','2026-11-09 01:42:33','2026-11-09 01:42:33'),(4,'T-04','aac146e0-c5ba-42dd-82b7-baa877868d22','vacant','2026-11-09 01:42:33','2026-11-09 01:42:33'),(5,'T-05','42ae29b1-ffbc-4561-bb93-d446cd26da57','vacant','2026-11-09 01:42:33','2026-11-09 01:42:33'),(6,'T-06','5c3c2050-0308-4c76-a031-a353bac6c09a','vacant','2026-11-09 01:42:33','2026-11-09 01:42:33'),(7,'T-07','d4dd5507-c4f8-47af-a7b8-42608decbdef','vacant','2026-11-09 01:42:33','2026-11-09 01:42:33'),(8,'T-08','0567cd47-f79a-465a-9f1a-f9a9a703bc70','vacant','2026-11-09 01:42:33','2026-11-09 01:42:33');
/*!40000 ALTER TABLE `restaurant_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Unique role key: admin, manager, staff, cashier, kitchen',
  `label` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','System Administrator','2026-11-09 01:42:30','2026-11-09 01:42:30'),(2,'manager','Duty Manager','2026-11-09 01:42:30','2026-11-09 01:42:30'),(3,'staff','Sales Assistant / Waitstaff','2026-11-09 01:42:30','2026-11-09 01:42:30'),(4,'cashier','Cashier & Billing Officer','2026-11-09 01:42:30','2026-11-09 01:42:30'),(5,'kitchen','Executive Chef / KDS Operator','2026-11-09 01:42:30','2026-11-09 01:42:30');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `pin_code` varchar(255) DEFAULT NULL COMMENT 'Hashed PIN code for quick manager/admin authorization',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Nuwan Wijesinghe (Admin)','admin@hotel.local',NULL,'$2y$12$guv4DdXW.ednjHZ1mp.b/.aHog5l5BZ1J/7gXNbnFArqVhKLMFRLe','$2y$12$276gBJ1sa/L0MVv4rzPrNe7ZS8ylUFWs4jCiHLec33LCjidc1ctKe',1,NULL,'2026-11-09 01:42:31','2026-11-09 01:42:31'),(2,2,'Dilshan Fernando (Manager)','manager@hotel.local',NULL,'$2y$12$JMdoZxeyjgNQVFNVNN/Lf.BN73pKe/IhhdrxpJ9jFcnHps596FgZq','$2y$12$2oIMzWOGu5HH9zITYPsClusqGItF8PFj0f1d/b6Nvj1HGSipx3flW',1,NULL,'2026-11-09 01:42:31','2026-11-09 01:42:31'),(3,3,'Kavindu Silva (Sales Assistant)','staff@hotel.local',NULL,'$2y$12$ih0z6KbvH7uD6/EvHBjD9eiE.xqZzu94rZ8/TgoMYpHc7ABrIi6Qm',NULL,1,NULL,'2026-11-09 01:42:32','2026-11-09 01:42:32'),(4,4,'Nadeesha Perera (Cashier)','cashier@hotel.local',NULL,'$2y$12$OTdgSNLgcs2STCenGhjiweEt6lCexhMTQtDElKq3Rac9kL7K0Pksy',NULL,1,NULL,'2026-11-09 01:42:32','2026-11-09 01:42:32'),(5,5,'Chef Rohan De Silva (Kitchen)','kitchen@hotel.local',NULL,'$2y$12$LSY5fDR5EpZKwCGC56OlpOQ6O74Kbuvtb/0Rpu3XuEIaCEUygdGkK',NULL,1,NULL,'2026-11-09 01:42:32','2026-11-09 01:42:32');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'hotel_management'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-09 12:43:28
