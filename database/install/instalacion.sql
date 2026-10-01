-- Espectro CRM · Base inicial para importar con phpMyAdmin (hosting sin SSH).
-- Contiene: tablas, catálogos (estados, especies, formas de pago, configuración) y un administrador temporal.
-- Administrador temporal: admin@espectro.local / CambiarEsta2026  -> cambie la contraseña al entrar.
-- Generado con las migraciones del proyecto. Regenerar si cambian las migraciones (ver docs/INSTALACION_CPANEL.md).


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
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(26) DEFAULT NULL,
  `event` varchar(40) NOT NULL,
  `description` varchar(500) NOT NULL,
  `subject_type` varchar(60) DEFAULT NULL,
  `subject_id` varchar(26) DEFAULT NULL,
  `client_id` char(26) DEFAULT NULL,
  `ranch_id` char(26) DEFAULT NULL,
  `opportunity_id` char(26) DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_opportunity_id_created_at_index` (`opportunity_id`,`created_at`),
  KEY `activity_logs_ranch_id_created_at_index` (`ranch_id`,`created_at`),
  KEY `activity_logs_client_id_created_at_index` (`client_id`,`created_at`),
  KEY `activity_logs_event_index` (`event`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activity_logs_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activity_logs_ranch_id_foreign` FOREIGN KEY (`ranch_id`) REFERENCES `ranches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` char(26) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clients_created_by_foreign` (`created_by`),
  KEY `clients_name_index` (`name`),
  KEY `clients_phone_index` (`phone`),
  CONSTRAINT `clients_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` char(26) NOT NULL,
  `opportunity_id` char(26) NOT NULL,
  `user_id` char(26) DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_opportunity_id_foreign` (`opportunity_id`),
  KEY `comments_user_id_foreign` (`user_id`),
  CONSTRAINT `comments_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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

/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
DROP TABLE IF EXISTS `mexican_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mexican_states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `abbreviation` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mexican_states_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `mexican_states` DISABLE KEYS */;
INSERT INTO `mexican_states` VALUES
(1,'Aguascalientes','AGS','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(2,'Baja California','BC','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(3,'Baja California Sur','BCS','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(4,'Campeche','CAMP','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(5,'Chiapas','CHIS','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(6,'Chihuahua','CHIH','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(7,'Ciudad de México','CDMX','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(8,'Coahuila','COAH','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(9,'Colima','COL','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(10,'Durango','DGO','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(11,'Estado de México','MEX','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(12,'Guanajuato','GTO','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(13,'Guerrero','GRO','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(14,'Hidalgo','HGO','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(15,'Jalisco','JAL','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(16,'Michoacán','MICH','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(17,'Morelos','MOR','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(18,'Nayarit','NAY','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(19,'Nuevo León','NL','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(20,'Oaxaca','OAX','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(21,'Puebla','PUE','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(22,'Querétaro','QRO','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(23,'Quintana Roo','QROO','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(24,'San Luis Potosí','SLP','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(25,'Sinaloa','SIN','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(26,'Sonora','SON','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(27,'Tabasco','TAB','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(28,'Tamaulipas','TAMPS','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(29,'Tlaxcala','TLAX','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(30,'Veracruz','VER','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(31,'Yucatán','YUC','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(32,'Zacatecas','ZAC','2026-10-01 14:07:59','2026-10-01 14:07:59');
/*!40000 ALTER TABLE `mexican_states` ENABLE KEYS */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_01_01_000100_create_catalog_tables',1),
(5,'2026_01_01_000200_create_clients_and_ranches_tables',1),
(6,'2026_01_01_000300_create_opportunities_tables',1),
(7,'2026_01_01_000400_create_quotes_and_payments_tables',1),
(8,'2026_01_01_000500_create_tasks_comments_and_activity_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
DROP TABLE IF EXISTS `opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunities` (
  `id` char(26) NOT NULL,
  `ranch_id` char(26) NOT NULL,
  `stage` varchar(30) NOT NULL DEFAULT 'prospecto',
  `service_type` varchar(20) NOT NULL,
  `quoted_hectares` decimal(12,2) DEFAULT NULL,
  `tentative_census_date` date DEFAULT NULL,
  `census_date` date DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'MXN',
  `notes` text DEFAULT NULL,
  `lost_reason` varchar(255) DEFAULT NULL,
  `last_contact_at` date DEFAULT NULL,
  `stage_changed_at` timestamp NULL DEFAULT NULL,
  `owner_id` char(26) DEFAULT NULL,
  `created_by` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opportunities_ranch_id_foreign` (`ranch_id`),
  KEY `opportunities_owner_id_foreign` (`owner_id`),
  KEY `opportunities_created_by_foreign` (`created_by`),
  KEY `opportunities_stage_census_date_index` (`stage`,`census_date`),
  KEY `opportunities_census_date_index` (`census_date`),
  CONSTRAINT `opportunities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunities_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunities_ranch_id_foreign` FOREIGN KEY (`ranch_id`) REFERENCES `ranches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `opportunities` DISABLE KEYS */;
/*!40000 ALTER TABLE `opportunities` ENABLE KEYS */;
DROP TABLE IF EXISTS `opportunity_species`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunity_species` (
  `opportunity_id` char(26) NOT NULL,
  `species_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`opportunity_id`,`species_id`),
  KEY `opportunity_species_species_id_foreign` (`species_id`),
  CONSTRAINT `opportunity_species_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `opportunity_species_species_id_foreign` FOREIGN KEY (`species_id`) REFERENCES `species` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `opportunity_species` DISABLE KEYS */;
/*!40000 ALTER TABLE `opportunity_species` ENABLE KEYS */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES
(1,'Transferencia',1,1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(2,'Efectivo',1,2,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(3,'Cheque',1,3,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(4,'Otro',1,4,'2026-10-01 14:07:59','2026-10-01 14:07:59');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` char(26) NOT NULL,
  `opportunity_id` char(26) NOT NULL,
  `quote_id` char(26) DEFAULT NULL,
  `paid_at` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'MXN',
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_opportunity_id_foreign` (`opportunity_id`),
  KEY `payments_quote_id_foreign` (`quote_id`),
  KEY `payments_payment_method_id_foreign` (`payment_method_id`),
  KEY `payments_created_by_foreign` (`created_by`),
  KEY `payments_paid_at_index` (`paid_at`),
  CONSTRAINT `payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`),
  CONSTRAINT `payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  CONSTRAINT `payments_quote_id_foreign` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
DROP TABLE IF EXISTS `quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotes` (
  `id` char(26) NOT NULL,
  `opportunity_id` char(26) NOT NULL,
  `folio` int(10) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `number` varchar(30) NOT NULL,
  `issued_at` date NOT NULL,
  `hectares` decimal(12,2) NOT NULL,
  `service_amount` decimal(12,2) NOT NULL,
  `logistics_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `apply_vat` tinyint(1) NOT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL,
  `vat_amount` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'MXN',
  `status` varchar(20) NOT NULL DEFAULT 'borrador',
  `accepted_lock` tinyint(3) unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotes_number_unique` (`number`),
  UNIQUE KEY `quotes_opportunity_id_accepted_lock_unique` (`opportunity_id`,`accepted_lock`),
  KEY `quotes_created_by_foreign` (`created_by`),
  KEY `quotes_folio_version_index` (`folio`,`version`),
  KEY `quotes_issued_at_index` (`issued_at`),
  KEY `quotes_status_index` (`status`),
  CONSTRAINT `quotes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotes_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `quotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotes` ENABLE KEYS */;
DROP TABLE IF EXISTS `ranches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ranches` (
  `id` char(26) NOT NULL,
  `client_id` char(26) NOT NULL,
  `name` varchar(150) NOT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `maps_url` text DEFAULT NULL,
  `km_round_trip` decimal(8,1) DEFAULT NULL,
  `fence_type` varchar(10) DEFAULT NULL,
  `total_hectares` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ranches_client_id_foreign` (`client_id`),
  KEY `ranches_state_id_foreign` (`state_id`),
  KEY `ranches_created_by_foreign` (`created_by`),
  KEY `ranches_name_index` (`name`),
  KEY `ranches_municipality_index` (`municipality`),
  KEY `ranches_fence_type_index` (`fence_type`),
  CONSTRAINT `ranches_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `ranches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ranches_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `mexican_states` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `ranches` DISABLE KEYS */;
/*!40000 ALTER TABLE `ranches` ENABLE KEYS */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(26) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'vat_rate','16','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(2,'quote_followup_days','3','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(3,'upcoming_census_days','7','2026-10-01 14:07:59','2026-10-01 14:07:59'),
(4,'quote_folio_start','1','2026-10-01 14:07:59','2026-10-01 14:07:59');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
DROP TABLE IF EXISTS `species`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `species` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `species_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `species` DISABLE KEYS */;
INSERT INTO `species` VALUES
(1,'Venado cola blanca',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(2,'Venado bura',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(3,'Pecarí de collar',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(4,'Jabalí europeo',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(5,'Guajolote silvestre',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(6,'Codorniz cotuí',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(7,'Codorniz escamosa',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(8,'Paloma alas blancas',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(9,'Borrego cimarrón',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(10,'Berrendo',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(11,'Antílope nilgai',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(12,'Borrego berberisco (aoudad)',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(13,'Coyote',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(14,'Puma',1,'2026-10-01 14:07:59','2026-10-01 14:07:59'),
(15,'Gato montés',1,'2026-10-01 14:07:59','2026-10-01 14:07:59');
/*!40000 ALTER TABLE `species` ENABLE KEYS */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` char(26) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pendiente',
  `opportunity_id` char(26) DEFAULT NULL,
  `client_id` char(26) DEFAULT NULL,
  `ranch_id` char(26) DEFAULT NULL,
  `assigned_to` char(26) DEFAULT NULL,
  `created_by` char(26) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` char(26) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_opportunity_id_foreign` (`opportunity_id`),
  KEY `tasks_client_id_foreign` (`client_id`),
  KEY `tasks_ranch_id_foreign` (`ranch_id`),
  KEY `tasks_assigned_to_foreign` (`assigned_to`),
  KEY `tasks_created_by_foreign` (`created_by`),
  KEY `tasks_completed_by_foreign` (`completed_by`),
  KEY `tasks_status_due_date_index` (`status`,`due_date`),
  CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_ranch_id_foreign` FOREIGN KEY (`ranch_id`) REFERENCES `ranches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` char(26) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'usuario',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
('01m3wh9e89y2wswczp2f1tcaa4','Administrador','admin@espectro.local',NULL,'$2y$12$51UsfqPSJKUO3smL2G.ineKFK2rgrAlBIMGbbWGO/6nOagc94BKje','administrador',1,NULL,NULL,'2026-10-01 14:08:00','2026-10-01 14:08:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

