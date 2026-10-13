
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
DROP TABLE IF EXISTS `ai_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_providers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `driver` enum('openai_compatible','gemini') NOT NULL,
  `base_url` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `api_key` text NOT NULL,
  `priority` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_exhausted` tinyint(1) NOT NULL DEFAULT 0,
  `requests_used` int(10) unsigned NOT NULL DEFAULT 0,
  `quota_limit` int(10) unsigned DEFAULT NULL,
  `quota_period_days` int(10) unsigned NOT NULL DEFAULT 1,
  `period_reset_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ai_providers` WRITE;
/*!40000 ALTER TABLE `ai_providers` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_providers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ai_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `system_prompt` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ai_settings` WRITE;
/*!40000 ALTER TABLE `ai_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ai_usage_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_usage_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ai_provider_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('success','error','quota_exceeded') NOT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_usage_logs_user_id_foreign` (`user_id`),
  KEY `ai_usage_logs_ai_provider_id_created_at_index` (`ai_provider_id`,`created_at`),
  CONSTRAINT `ai_usage_logs_ai_provider_id_foreign` FOREIGN KEY (`ai_provider_id`) REFERENCES `ai_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_usage_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ai_usage_logs` WRITE;
/*!40000 ALTER TABLE `ai_usage_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_usage_logs` ENABLE KEYS */;
UNLOCK TABLES;
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
INSERT INTO `budgets` VALUES (1,3,1,'2026-10-01',100.00,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(2,3,2,'2026-10-01',200.00,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(3,3,4,'2026-10-01',100.00,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(4,3,6,'2026-10-01',50.00,'2026-10-13 06:47:22','2026-10-13 06:47:22');
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
INSERT INTO `categories` VALUES (1,NULL,'Transporte','expense',1,1,'2026-10-13 06:47:21','2026-10-13 06:47:21'),(2,NULL,'Alimentación','expense',1,1,'2026-10-13 06:47:21','2026-10-13 06:47:21'),(3,NULL,'Materiales de estudio','document',1,1,'2026-10-13 06:47:21','2026-10-13 06:47:21'),(4,NULL,'Entretenimiento','flag',1,1,'2026-10-13 06:47:21','2026-10-13 06:47:21'),(5,NULL,'Otros','wallet',1,1,'2026-10-13 06:47:21','2026-10-13 06:47:21'),(6,3,'Suscripciones',NULL,0,1,'2026-10-13 06:47:22','2026-10-13 06:47:22');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `chat_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `chat_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `pending_intent` varchar(255) DEFAULT NULL,
  `pending_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pending_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_states_user_id_unique` (`user_id`),
  CONSTRAINT `chat_states_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `chat_states` WRITE;
/*!40000 ALTER TABLE `chat_states` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_states` ENABLE KEYS */;
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
INSERT INTO `expenses` VALUES (1,3,1,11.44,'2026-09-11','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(2,3,1,11.91,'2026-09-06','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(3,3,1,5.68,'2026-09-22','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(4,3,1,4.39,'2026-09-28','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(5,3,1,9.75,'2026-09-15','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(6,3,1,7.79,'2026-10-10','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(7,3,1,10.70,'2026-09-16','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(8,3,1,8.84,'2026-09-14','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(9,3,1,11.91,'2026-08-30','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(10,3,1,8.77,'2026-08-23','Pasaje','2026-10-13 06:47:22','2026-10-13 06:47:22'),(11,3,2,14.33,'2026-10-01','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(12,3,2,19.70,'2026-09-21','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(13,3,2,12.88,'2026-09-23','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(14,3,2,20.24,'2026-09-03','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(15,3,2,11.79,'2026-09-10','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(16,3,2,20.18,'2026-08-23','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(17,3,2,17.30,'2026-09-10','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(18,3,2,16.92,'2026-10-05','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(19,3,2,22.30,'2026-08-21','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(20,3,2,22.20,'2026-10-12','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(21,3,2,23.44,'2026-08-17','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(22,3,2,22.56,'2026-09-20','Almuerzo','2026-10-13 06:47:22','2026-10-13 06:47:22'),(23,3,3,45.86,'2026-09-05','Fotocopias / útiles','2026-10-13 06:47:22','2026-10-13 06:47:22'),(24,3,3,55.25,'2026-09-23','Fotocopias / útiles','2026-10-13 06:47:22','2026-10-13 06:47:22'),(25,3,3,71.47,'2026-09-24','Fotocopias / útiles','2026-10-13 06:47:22','2026-10-13 06:47:22'),(26,3,3,58.57,'2026-09-14','Fotocopias / útiles','2026-10-13 06:47:22','2026-10-13 06:47:22'),(27,3,3,38.83,'2026-09-28','Fotocopias / útiles','2026-10-13 06:47:22','2026-10-13 06:47:22'),(28,3,4,52.28,'2026-10-01','Salida con amigos','2026-10-13 06:47:22','2026-10-13 06:47:22'),(29,3,4,58.98,'2026-09-02','Salida con amigos','2026-10-13 06:47:22','2026-10-13 06:47:22'),(30,3,4,44.21,'2026-09-10','Salida con amigos','2026-10-13 06:47:22','2026-10-13 06:47:22'),(31,3,4,22.13,'2026-08-26','Salida con amigos','2026-10-13 06:47:22','2026-10-13 06:47:22'),(32,3,5,27.30,'2026-08-20','Gasto varios','2026-10-13 06:47:22','2026-10-13 06:47:22'),(33,3,5,28.63,'2026-09-05','Gasto varios','2026-10-13 06:47:22','2026-10-13 06:47:22'),(34,3,5,31.86,'2026-09-03','Gasto varios','2026-10-13 06:47:22','2026-10-13 06:47:22'),(35,3,5,22.57,'2026-10-12','Gasto varios','2026-10-13 06:47:22','2026-10-13 06:47:22'),(36,3,6,45.00,'2026-10-03','Streaming de música','2026-10-13 06:47:22','2026-10-13 06:47:22');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `faculties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `faculties` WRITE;
/*!40000 ALTER TABLE `faculties` DISABLE KEYS */;
INSERT INTO `faculties` VALUES (1,'Ingeniería','2026-10-13 06:47:21','2026-10-13 06:47:21'),(2,'Ciencias de la Salud','2026-10-13 06:47:21','2026-10-13 06:47:21'),(3,'Negocios','2026-10-13 06:47:21','2026-10-13 06:47:21'),(4,'Comunicaciones','2026-10-13 06:47:21','2026-10-13 06:47:21'),(5,'Derecho','2026-10-13 06:47:21','2026-10-13 06:47:21'),(6,'Arquitectura','2026-10-13 06:47:21','2026-10-13 06:47:21');
/*!40000 ALTER TABLE `faculties` ENABLE KEYS */;
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
INSERT INTO `goal_contributions` VALUES (1,1,3,1000.00,'2026-09-23','Ahorro inicial','2026-10-13 06:47:22','2026-10-13 06:47:22'),(2,1,3,600.00,'2026-10-07','Aporte de freelance','2026-10-13 06:47:22','2026-10-13 06:47:22'),(3,3,3,500.00,'2026-09-03','Meta alcanzada','2026-10-13 06:47:22','2026-10-13 06:47:22');
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
INSERT INTO `incomes` VALUES (1,3,NULL,850.00,'2026-10-05','Beca universitaria','fijo','mensual','2026-11-05','2026-10-13 06:47:22','2026-10-13 06:47:22'),(2,3,NULL,150.00,'2026-09-18','Trabajo freelance','variable',NULL,NULL,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(3,3,NULL,90.00,'2026-09-25','Venta de apuntes','variable',NULL,NULL,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(4,3,NULL,120.00,'2026-10-01','Trabajo freelance','variable',NULL,NULL,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(5,3,NULL,60.00,'2026-10-08','Venta ocasional','variable',NULL,NULL,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(6,3,NULL,100.00,'2026-10-11','Trabajo freelance','variable',NULL,NULL,'2026-10-13 06:47:22','2026-10-13 06:47:22');
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
INSERT INTO `liquidity_alerts` VALUES (1,3,80.00,150.00,'dashboard','2026-10-10 06:47:22','2026-10-13 06:47:22','2026-10-13 06:47:22');
/*!40000 ALTER TABLE `liquidity_alerts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `person_name` varchar(255) NOT NULL,
  `direction` enum('prestado','recibido') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loans_user_id_foreign` (`user_id`),
  CONSTRAINT `loans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_23_035500_create_user_settings_table',1),(5,'2026_09_23_035501_create_categories_table',1),(6,'2026_09_23_035501_create_expenses_table',1),(7,'2026_09_23_035501_create_incomes_table',1),(8,'2026_09_23_035502_create_budgets_table',1),(9,'2026_09_23_035502_create_savings_goals_table',1),(10,'2026_09_23_035503_create_goal_contributions_table',1),(11,'2026_09_23_035503_create_liquidity_alerts_table',1),(12,'2026_10_07_163045_add_role_and_is_active_to_users_table',1),(13,'2026_10_07_163046_add_is_active_to_categories_table',1),(14,'2026_10_07_173755_create_ai_providers_table',1),(15,'2026_10_07_173755_create_ai_usage_logs_table',1),(16,'2026_10_07_173755_create_chat_messages_table',1),(17,'2026_10_07_173756_create_chat_states_table',1),(18,'2026_10_07_211430_create_ai_settings_table',1),(19,'2026_10_07_211616_add_language_to_user_settings_table',1),(20,'2026_10_07_211921_create_faculties_table',1),(21,'2026_10_07_211922_add_faculty_id_to_users_table',1),(22,'2026_10_07_215156_add_approval_fields_to_users_table',1),(23,'2026_10_12_000001_create_support_requests_table',1),(24,'2026_10_12_000002_create_notifications_table',1),(25,'2026_10_12_000003_add_reminder_toggles_to_user_settings_table',1),(26,'2026_10_12_000004_add_weekly_summary_toggle_to_user_settings_table',1),(27,'2026_10_12_000005_create_loans_table',1),(28,'2026_10_12_000006_create_shared_expenses_table',1),(29,'2026_10_12_000007_add_onboarding_completed_at_to_user_settings_table',1),(30,'2026_10_12_000008_add_font_size_to_user_settings_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
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
INSERT INTO `savings_goals` VALUES (1,3,'Nueva laptop',2400.00,'2027-03-13',1600.00,'active',NULL,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(2,3,'Viaje de graduación',2000.00,'2027-07-13',600.00,'active',NULL,'2026-10-13 06:47:22','2026-10-13 06:47:22'),(3,3,'Fondo de emergencia',500.00,NULL,500.00,'completed','2026-10-13 06:47:22','2026-10-13 06:47:22','2026-10-13 06:47:22');
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
DROP TABLE IF EXISTS `shared_expense_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shared_expense_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shared_expense_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount_owed` decimal(10,2) NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shared_expense_participants_shared_expense_id_foreign` (`shared_expense_id`),
  CONSTRAINT `shared_expense_participants_shared_expense_id_foreign` FOREIGN KEY (`shared_expense_id`) REFERENCES `shared_expenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `shared_expense_participants` WRITE;
/*!40000 ALTER TABLE `shared_expense_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `shared_expense_participants` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `shared_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shared_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `expense_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `my_share` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shared_expenses_user_id_foreign` (`user_id`),
  KEY `shared_expenses_category_id_foreign` (`category_id`),
  KEY `shared_expenses_expense_id_foreign` (`expense_id`),
  CONSTRAINT `shared_expenses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shared_expenses_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shared_expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `shared_expenses` WRITE;
/*!40000 ALTER TABLE `shared_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `shared_expenses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `support_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` enum('asesor','incidencia') NOT NULL,
  `message` text NOT NULL,
  `status` enum('pendiente','atendido') NOT NULL DEFAULT 'pendiente',
  `attended_by_id` bigint(20) unsigned DEFAULT NULL,
  `attended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_requests_user_id_foreign` (`user_id`),
  KEY `support_requests_attended_by_id_foreign` (`attended_by_id`),
  CONSTRAINT `support_requests_attended_by_id_foreign` FOREIGN KEY (`attended_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `support_requests` WRITE;
/*!40000 ALTER TABLE `support_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_requests` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'PEN',
  `theme` varchar(255) NOT NULL DEFAULT 'light',
  `font_size` varchar(255) NOT NULL DEFAULT 'normal',
  `language` varchar(2) NOT NULL DEFAULT 'es',
  `week_start_day` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `liquidity_threshold` decimal(10,2) NOT NULL DEFAULT 100.00,
  `notify_low_liquidity_by_email` tinyint(1) NOT NULL DEFAULT 1,
  `notify_inactivity_by_email` tinyint(1) NOT NULL DEFAULT 1,
  `notify_income_confirmation_by_email` tinyint(1) NOT NULL DEFAULT 1,
  `notify_weekly_summary_by_email` tinyint(1) NOT NULL DEFAULT 1,
  `starting_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_settings_user_id_unique` (`user_id`),
  CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (1,1,'PEN','light','normal','es',1,100.00,1,1,1,1,0.00,NULL,'2026-10-13 06:47:21','2026-10-13 06:47:21'),(2,2,'PEN','light','normal','es',1,100.00,1,1,1,1,0.00,NULL,'2026-10-13 06:47:21','2026-10-13 06:47:21'),(3,3,'PEN','light','normal','es',1,150.00,1,1,1,1,500.00,NULL,'2026-10-13 06:47:21','2026-10-13 06:47:22');
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
  `role` enum('estudiante','decano','admin') NOT NULL DEFAULT 'estudiante',
  `faculty_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_faculty_id_foreign` (`faculty_id`),
  CONSTRAINT `users_faculty_id_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador KashaFin','admin@kashafin.test','2026-10-13 06:47:21','$2y$12$A9prVLNeYeXw7i2ccCzpH.NPCbHx8TLLDMWV8W5.7xKm4GA4/Vxcm','admin',NULL,'2026-10-13 06:47:21',0,1,'13EyakxiDX','2026-10-13 06:47:21','2026-10-13 06:47:21'),(2,'Decano Demo','decano@kashafin.test','2026-10-13 06:47:21','$2y$12$A9prVLNeYeXw7i2ccCzpH.NPCbHx8TLLDMWV8W5.7xKm4GA4/Vxcm','decano',1,'2026-10-13 06:47:21',0,1,'Zmi6WTm3BD','2026-10-13 06:47:21','2026-10-13 06:47:21'),(3,'Estudiante Demo','demo@kashafin.test','2026-10-13 06:47:21','$2y$12$A9prVLNeYeXw7i2ccCzpH.NPCbHx8TLLDMWV8W5.7xKm4GA4/Vxcm','estudiante',1,'2026-10-13 06:47:21',0,1,'JOVnb3XE6t','2026-10-13 06:47:21','2026-10-13 06:47:21');
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

