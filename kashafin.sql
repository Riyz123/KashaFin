
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
INSERT INTO `budgets` VALUES (1,1,1,'2026-10-01',100.00,'2026-10-07 20:59:25','2026-10-07 20:59:25'),(2,1,2,'2026-10-01',200.00,'2026-10-07 20:59:25','2026-10-07 20:59:25'),(3,1,4,'2026-10-01',100.00,'2026-10-07 20:59:25','2026-10-07 20:59:25'),(4,1,6,'2026-10-01',50.00,'2026-10-07 20:59:25','2026-10-07 20:59:25');
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_user_id_name_unique` (`user_id`,`name`),
  CONSTRAINT `categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Transporte','expense',1,'2026-10-07 20:59:23','2026-10-07 20:59:23'),(2,NULL,'Alimentación','expense',1,'2026-10-07 20:59:23','2026-10-07 20:59:23'),(3,NULL,'Materiales de estudio','document',1,'2026-10-07 20:59:23','2026-10-07 20:59:23'),(4,NULL,'Entretenimiento','flag',1,'2026-10-07 20:59:23','2026-10-07 20:59:23'),(5,NULL,'Otros','wallet',1,'2026-10-07 20:59:23','2026-10-07 20:59:23'),(6,1,'Suscripciones',NULL,0,'2026-10-07 20:59:24','2026-10-07 20:59:24');
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
INSERT INTO `expenses` VALUES (1,1,1,5.86,'2026-10-05','Pasaje','2026-10-07 20:59:24','2026-10-07 20:59:24'),(2,1,1,3.31,'2026-10-06','Pasaje','2026-10-07 20:59:24','2026-10-07 20:59:24'),(3,1,1,7.59,'2026-09-11','Pasaje','2026-10-07 20:59:25','2026-10-07 20:59:25'),(4,1,1,11.79,'2026-08-27','Pasaje','2026-10-07 20:59:25','2026-10-07 20:59:25'),(5,1,1,8.93,'2026-10-05','Pasaje','2026-10-07 20:59:25','2026-10-07 20:59:25'),(6,1,1,10.81,'2026-09-12','Pasaje','2026-10-07 20:59:25','2026-10-07 20:59:25'),(7,1,1,4.78,'2026-08-14','Pasaje','2026-10-07 20:59:25','2026-10-07 20:59:25'),(8,1,1,5.06,'2026-09-08','Pasaje','2026-10-07 20:59:25','2026-10-07 20:59:25'),(9,1,1,8.99,'2026-08-11','Pasaje','2026-10-07 20:59:25','2026-10-07 20:59:25'),(10,1,1,7.01,'2026-09-25','Pasaje','2026-10-07 20:59:25','2026-10-07 20:59:25'),(11,1,2,13.28,'2026-09-16','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(12,1,2,14.26,'2026-09-23','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(13,1,2,22.37,'2026-09-30','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(14,1,2,16.04,'2026-09-27','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(15,1,2,9.77,'2026-09-09','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(16,1,2,17.89,'2026-09-14','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(17,1,2,10.00,'2026-08-12','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(18,1,2,16.77,'2026-08-27','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(19,1,2,16.65,'2026-08-20','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(20,1,2,12.32,'2026-09-18','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(21,1,2,13.20,'2026-09-06','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(22,1,2,10.01,'2026-08-20','Almuerzo','2026-10-07 20:59:25','2026-10-07 20:59:25'),(23,1,3,53.23,'2026-09-02','Fotocopias / útiles','2026-10-07 20:59:25','2026-10-07 20:59:25'),(24,1,3,74.56,'2026-08-13','Fotocopias / útiles','2026-10-07 20:59:25','2026-10-07 20:59:25'),(25,1,3,31.48,'2026-09-24','Fotocopias / útiles','2026-10-07 20:59:25','2026-10-07 20:59:25'),(26,1,3,22.39,'2026-08-22','Fotocopias / útiles','2026-10-07 20:59:25','2026-10-07 20:59:25'),(27,1,3,52.57,'2026-08-17','Fotocopias / útiles','2026-10-07 20:59:25','2026-10-07 20:59:25'),(28,1,4,25.17,'2026-09-18','Salida con amigos','2026-10-07 20:59:25','2026-10-07 20:59:25'),(29,1,4,52.43,'2026-08-11','Salida con amigos','2026-10-07 20:59:25','2026-10-07 20:59:25'),(30,1,4,30.70,'2026-08-18','Salida con amigos','2026-10-07 20:59:25','2026-10-07 20:59:25'),(31,1,4,29.28,'2026-08-11','Salida con amigos','2026-10-07 20:59:25','2026-10-07 20:59:25'),(32,1,5,12.07,'2026-09-11','Gasto varios','2026-10-07 20:59:25','2026-10-07 20:59:25'),(33,1,5,35.96,'2026-08-27','Gasto varios','2026-10-07 20:59:25','2026-10-07 20:59:25'),(34,1,5,31.23,'2026-09-14','Gasto varios','2026-10-07 20:59:25','2026-10-07 20:59:25'),(35,1,5,21.40,'2026-10-01','Gasto varios','2026-10-07 20:59:25','2026-10-07 20:59:25'),(36,1,6,45.00,'2026-10-03','Streaming de música','2026-10-07 20:59:25','2026-10-07 20:59:25');
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
INSERT INTO `goal_contributions` VALUES (1,1,1,1000.00,'2026-09-17','Ahorro inicial','2026-10-07 20:59:25','2026-10-07 20:59:25'),(2,1,1,600.00,'2026-10-01','Aporte de freelance','2026-10-07 20:59:25','2026-10-07 20:59:25'),(3,3,1,500.00,'2026-08-28','Meta alcanzada','2026-10-07 20:59:25','2026-10-07 20:59:25');
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
INSERT INTO `incomes` VALUES (1,1,NULL,850.00,'2026-10-05','Beca universitaria','fijo','mensual','2026-11-05','2026-10-07 20:59:24','2026-10-07 20:59:24'),(2,1,NULL,150.00,'2026-09-12','Trabajo freelance','variable',NULL,NULL,'2026-10-07 20:59:24','2026-10-07 20:59:24'),(3,1,NULL,90.00,'2026-09-19','Venta de apuntes','variable',NULL,NULL,'2026-10-07 20:59:24','2026-10-07 20:59:24'),(4,1,NULL,120.00,'2026-09-25','Trabajo freelance','variable',NULL,NULL,'2026-10-07 20:59:24','2026-10-07 20:59:24'),(5,1,NULL,60.00,'2026-10-02','Venta ocasional','variable',NULL,NULL,'2026-10-07 20:59:24','2026-10-07 20:59:24'),(6,1,NULL,100.00,'2026-10-05','Trabajo freelance','variable',NULL,NULL,'2026-10-07 20:59:24','2026-10-07 20:59:24');
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
INSERT INTO `liquidity_alerts` VALUES (1,1,80.00,150.00,'dashboard','2026-10-04 20:59:25','2026-10-07 20:59:25','2026-10-07 20:59:25');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_23_035500_create_user_settings_table',1),(5,'2026_09_23_035501_create_categories_table',1),(6,'2026_09_23_035501_create_expenses_table',1),(7,'2026_09_23_035501_create_incomes_table',1),(8,'2026_09_23_035502_create_budgets_table',1),(9,'2026_09_23_035502_create_savings_goals_table',1),(10,'2026_09_23_035503_create_goal_contributions_table',1),(11,'2026_09_23_035503_create_liquidity_alerts_table',1);
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
INSERT INTO `savings_goals` VALUES (1,1,'Nueva laptop',2400.00,'2027-03-07',1600.00,'active',NULL,'2026-10-07 20:59:25','2026-10-07 20:59:25'),(2,1,'Viaje de graduación',2000.00,'2027-07-07',600.00,'active',NULL,'2026-10-07 20:59:25','2026-10-07 20:59:25'),(3,1,'Fondo de emergencia',500.00,NULL,500.00,'completed','2026-10-07 20:59:25','2026-10-07 20:59:25','2026-10-07 20:59:25');
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
INSERT INTO `sessions` VALUES ('pXlxZGcWLdixYkQVTvlEL6DPLg1lRP7Gwu8967jZ',1,'127.0.0.1','curl/8.19.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVm9xS3oyZko2RmNUc0JXZjNsSkV3UnlGT0RoN2ZkSWE2Zk1RSmJSOCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1791388788);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (1,1,'PEN','light',1,150.00,1,500.00,'2026-10-07 20:59:24','2026-10-07 20:59:24');
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
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Estudiante Demo','demo@kashafin.test','2026-10-07 20:59:24','$2y$12$ZN/liz5k20xdiK7kwnn1IO7J8XGpdWC9fWUtK7FfKhIF90z8EozX6','GLg3IvBc6T','2026-10-07 20:59:24','2026-10-07 20:59:24');
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

