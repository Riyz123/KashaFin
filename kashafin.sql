
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
DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `period_month` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budgets_user_id_category_id_period_month_unique` (`user_id`,`category_id`,`period_month`),
  KEY `budgets_category_id_foreign` (`category_id`),
  CONSTRAINT `budgets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budgets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
INSERT INTO `budgets` VALUES (1,2,1,'2026-10-01',100.00,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(2,2,2,'2026-10-01',200.00,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(3,2,4,'2026-10-01',100.00,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(4,2,6,'2026-10-01',50.00,'2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_user_id_name_unique` (`user_id`,`name`),
  CONSTRAINT `categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Transporte','expense',1,1,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(2,NULL,'Alimentación','expense',1,1,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(3,NULL,'Materiales de estudio','document',1,1,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(4,NULL,'Entretenimiento','flag',1,1,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(5,NULL,'Otros','wallet',1,1,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(6,2,'Suscripciones',NULL,0,1,'2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_category_id_foreign` (`category_id`),
  KEY `expenses_user_id_date_index` (`user_id`,`date`),
  KEY `expenses_user_id_category_id_index` (`user_id`,`category_id`),
  CONSTRAINT `expenses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,2,1,4.56,'2026-09-27','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(2,2,1,7.50,'2026-08-21','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(3,2,1,5.14,'2026-08-16','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(4,2,1,7.52,'2026-09-20','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(5,2,1,5.50,'2026-09-01','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(6,2,1,9.25,'2026-08-25','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(7,2,1,5.32,'2026-09-16','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(8,2,1,6.62,'2026-09-02','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(9,2,1,9.29,'2026-08-26','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(10,2,1,7.65,'2026-09-20','Pasaje','2026-10-07 21:59:51','2026-10-07 21:59:51'),(11,2,2,22.23,'2026-09-29','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(12,2,2,12.52,'2026-08-29','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(13,2,2,13.72,'2026-08-16','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(14,2,2,16.26,'2026-08-26','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(15,2,2,10.97,'2026-09-21','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(16,2,2,21.30,'2026-09-04','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(17,2,2,18.21,'2026-08-24','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(18,2,2,15.65,'2026-09-02','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(19,2,2,19.18,'2026-09-13','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(20,2,2,14.97,'2026-09-26','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(21,2,2,19.89,'2026-10-06','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(22,2,2,17.15,'2026-08-29','Almuerzo','2026-10-07 21:59:51','2026-10-07 21:59:51'),(23,2,3,64.45,'2026-08-25','Fotocopias / útiles','2026-10-07 21:59:51','2026-10-07 21:59:51'),(24,2,3,51.76,'2026-08-21','Fotocopias / útiles','2026-10-07 21:59:51','2026-10-07 21:59:51'),(25,2,3,30.17,'2026-10-06','Fotocopias / útiles','2026-10-07 21:59:51','2026-10-07 21:59:51'),(26,2,3,18.14,'2026-09-07','Fotocopias / útiles','2026-10-07 21:59:51','2026-10-07 21:59:51'),(27,2,3,28.15,'2026-09-01','Fotocopias / útiles','2026-10-07 21:59:51','2026-10-07 21:59:51'),(28,2,4,48.58,'2026-09-21','Salida con amigos','2026-10-07 21:59:51','2026-10-07 21:59:51'),(29,2,4,20.03,'2026-09-12','Salida con amigos','2026-10-07 21:59:51','2026-10-07 21:59:51'),(30,2,4,24.06,'2026-09-20','Salida con amigos','2026-10-07 21:59:51','2026-10-07 21:59:51'),(31,2,4,50.81,'2026-09-29','Salida con amigos','2026-10-07 21:59:51','2026-10-07 21:59:51'),(32,2,5,11.84,'2026-09-29','Gasto varios','2026-10-07 21:59:51','2026-10-07 21:59:51'),(33,2,5,29.73,'2026-09-08','Gasto varios','2026-10-07 21:59:51','2026-10-07 21:59:51'),(34,2,5,36.59,'2026-09-08','Gasto varios','2026-10-07 21:59:51','2026-10-07 21:59:51'),(35,2,5,19.33,'2026-10-02','Gasto varios','2026-10-07 21:59:51','2026-10-07 21:59:51'),(36,2,6,45.00,'2026-10-03','Streaming de música','2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `goal_contributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goal_contributions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `savings_goal_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goal_contributions_savings_goal_id_foreign` (`savings_goal_id`),
  KEY `goal_contributions_user_id_foreign` (`user_id`),
  CONSTRAINT `goal_contributions_savings_goal_id_foreign` FOREIGN KEY (`savings_goal_id`) REFERENCES `savings_goals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goal_contributions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `goal_contributions` WRITE;
/*!40000 ALTER TABLE `goal_contributions` DISABLE KEYS */;
INSERT INTO `goal_contributions` VALUES (1,1,2,1000.00,'2026-09-17','Ahorro inicial','2026-10-07 21:59:51','2026-10-07 21:59:51'),(2,1,2,600.00,'2026-10-01','Aporte de freelance','2026-10-07 21:59:51','2026-10-07 21:59:51'),(3,3,2,500.00,'2026-08-28','Meta alcanzada','2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `goal_contributions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `incomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incomes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `parent_income_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `type` enum('fijo','variable') NOT NULL,
  `frequency` enum('semanal','quincenal','mensual') DEFAULT NULL,
  `next_occurrence_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incomes_parent_income_id_foreign` (`parent_income_id`),
  KEY `incomes_user_id_date_index` (`user_id`,`date`),
  KEY `incomes_type_next_occurrence_date_index` (`type`,`next_occurrence_date`),
  CONSTRAINT `incomes_parent_income_id_foreign` FOREIGN KEY (`parent_income_id`) REFERENCES `incomes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incomes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `incomes` WRITE;
/*!40000 ALTER TABLE `incomes` DISABLE KEYS */;
INSERT INTO `incomes` VALUES (1,2,NULL,850.00,'2026-10-05','Beca universitaria','fijo','mensual','2026-11-05','2026-10-07 21:59:51','2026-10-07 21:59:51'),(2,2,NULL,150.00,'2026-09-12','Trabajo freelance','variable',NULL,NULL,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(3,2,NULL,90.00,'2026-09-19','Venta de apuntes','variable',NULL,NULL,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(4,2,NULL,120.00,'2026-09-25','Trabajo freelance','variable',NULL,NULL,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(5,2,NULL,60.00,'2026-10-02','Venta ocasional','variable',NULL,NULL,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(6,2,NULL,100.00,'2026-10-05','Trabajo freelance','variable',NULL,NULL,'2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `incomes` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `liquidity_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `liquidity_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `projected_balance` decimal(10,2) NOT NULL,
  `threshold` decimal(10,2) NOT NULL,
  `channel` enum('dashboard','email') NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `liquidity_alerts_user_id_sent_at_index` (`user_id`,`sent_at`),
  CONSTRAINT `liquidity_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `liquidity_alerts` WRITE;
/*!40000 ALTER TABLE `liquidity_alerts` DISABLE KEYS */;
INSERT INTO `liquidity_alerts` VALUES (1,2,80.00,150.00,'dashboard','2026-10-04 21:59:51','2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `liquidity_alerts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_23_035500_create_user_settings_table',1),(5,'2026_09_23_035501_create_categories_table',1),(6,'2026_09_23_035501_create_expenses_table',1),(7,'2026_09_23_035501_create_incomes_table',1),(8,'2026_09_23_035502_create_budgets_table',1),(9,'2026_09_23_035502_create_savings_goals_table',1),(10,'2026_09_23_035503_create_goal_contributions_table',1),(11,'2026_09_23_035503_create_liquidity_alerts_table',1),(12,'2026_10_07_163045_add_role_and_is_active_to_users_table',1),(13,'2026_10_07_163046_add_is_active_to_categories_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `savings_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `savings_goals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `target_date` date DEFAULT NULL,
  `current_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','completed') NOT NULL DEFAULT 'active',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `savings_goals_user_id_foreign` (`user_id`),
  CONSTRAINT `savings_goals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `savings_goals` WRITE;
/*!40000 ALTER TABLE `savings_goals` DISABLE KEYS */;
INSERT INTO `savings_goals` VALUES (1,2,'Nueva laptop',2400.00,'2027-03-07',1600.00,'active',NULL,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(2,2,'Viaje de graduación',2000.00,'2027-07-07',600.00,'active',NULL,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(3,2,'Fondo de emergencia',500.00,NULL,500.00,'completed','2026-10-07 21:59:51','2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `savings_goals` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'PEN',
  `theme` varchar(255) NOT NULL DEFAULT 'light',
  `week_start_day` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `liquidity_threshold` decimal(10,2) NOT NULL DEFAULT 100.00,
  `notify_low_liquidity_by_email` tinyint(1) NOT NULL DEFAULT 1,
  `starting_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_settings_user_id_unique` (`user_id`),
  CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (1,1,'PEN','light',1,100.00,1,0.00,'2026-10-07 21:59:51','2026-10-07 21:59:51'),(2,2,'PEN','light',1,150.00,1,500.00,'2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('estudiante','admin') NOT NULL DEFAULT 'estudiante',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador KashaFin','admin@kashafin.test','2026-10-07 21:59:51','$2y$12$7EK7MurlFsc5Sm8GqaJJuOEhIRVCptfHI0b7VKPWQ5q8nAmJcYAGa','admin',1,'eW62fA2QCz','2026-10-07 21:59:51','2026-10-07 21:59:51'),(2,'Estudiante Demo','demo@kashafin.test','2026-10-07 21:59:51','$2y$12$7EK7MurlFsc5Sm8GqaJJuOEhIRVCptfHI0b7VKPWQ5q8nAmJcYAGa','estudiante',1,'okLgwsEpeN','2026-10-07 21:59:51','2026-10-07 21:59:51');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

