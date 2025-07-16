-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: localhost    Database: bdescueladariomontano
-- ------------------------------------------------------
-- Server version	8.0.40

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
-- Table structure for table `basurero`
--

DROP TABLE IF EXISTS `basurero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `basurero` (
  `idBasurero` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `ubicacion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `estado` tinyint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idBasurero`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `basurero`
--

LOCK TABLES `basurero` WRITE;
/*!40000 ALTER TABLE `basurero` DISABLE KEYS */;
INSERT INTO `basurero` VALUES (1,'Entrada principal','Cerca al portón de ingreso',1,NULL,NULL),(2,'Patio central','Junto a la jardinera',1,NULL,NULL),(3,'Cancha','Frente a las graderías',1,NULL,NULL),(4,'Laboratorio','Entrada principal',1,NULL,NULL);
/*!40000 ALTER TABLE `basurero` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel_cache_aronbro100@gmail.com|127.0.0.1','i:5;',1752626751),('laravel_cache_aronbro100@gmail.com|127.0.0.1:timer','i:1752626751;',1752626751);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
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
-- Table structure for table `curso`
--

DROP TABLE IF EXISTS `curso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso` (
  `idCurso` smallint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idCurso`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso`
--

LOCK TABLES `curso` WRITE;
/*!40000 ALTER TABLE `curso` DISABLE KEYS */;
INSERT INTO `curso` VALUES (1,'Primero',NULL,NULL),(2,'Segundo',NULL,NULL),(3,'Tercero',NULL,NULL),(4,'Cuarto',NULL,NULL),(10,'Quinto','2025-07-16 06:57:26','2025-07-16 06:57:26');
/*!40000 ALTER TABLE `curso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_paralelo`
--

DROP TABLE IF EXISTS `curso_paralelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso_paralelo` (
  `idCursoParalelo` smallint unsigned NOT NULL AUTO_INCREMENT,
  `idCurso` smallint unsigned NOT NULL,
  `idParalelo` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idCursoParalelo`),
  KEY `curso_paralelo_idcurso_foreign` (`idCurso`),
  KEY `curso_paralelo_idparalelo_foreign` (`idParalelo`),
  CONSTRAINT `curso_paralelo_idcurso_foreign` FOREIGN KEY (`idCurso`) REFERENCES `curso` (`idCurso`) ON DELETE CASCADE,
  CONSTRAINT `curso_paralelo_idparalelo_foreign` FOREIGN KEY (`idParalelo`) REFERENCES `paralelo` (`idParalelo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_paralelo`
--

LOCK TABLES `curso_paralelo` WRITE;
/*!40000 ALTER TABLE `curso_paralelo` DISABLE KEYS */;
INSERT INTO `curso_paralelo` VALUES (1,1,1,NULL,NULL),(2,2,2,NULL,NULL),(3,3,3,NULL,NULL),(4,4,4,NULL,NULL),(5,2,1,'2025-06-09 10:25:31','2025-06-09 10:25:31'),(6,2,3,'2025-06-09 10:25:43','2025-06-09 10:25:43'),(7,1,4,'2025-06-09 10:29:04','2025-06-09 10:29:04'),(8,4,1,'2025-06-09 10:57:49','2025-06-09 10:57:49'),(9,2,4,'2025-06-09 11:29:54','2025-06-09 11:29:54'),(10,3,2,'2025-06-09 12:24:06','2025-06-09 12:24:06'),(11,4,2,'2025-07-03 22:53:35','2025-07-03 22:53:35'),(12,1,3,'2025-07-10 06:43:08','2025-07-10 06:43:08'),(13,4,3,'2025-07-10 06:59:57','2025-07-10 06:59:57'),(34,10,1,'2025-07-16 06:57:26','2025-07-16 06:57:26'),(35,10,2,'2025-07-16 06:57:26','2025-07-16 06:57:26'),(36,10,3,'2025-07-16 06:57:26','2025-07-16 06:57:26'),(37,10,4,'2025-07-16 06:57:26','2025-07-16 06:57:26');
/*!40000 ALTER TABLE `curso_paralelo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deposito`
--

DROP TABLE IF EXISTS `deposito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deposito` (
  `idDeposito` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `idUser` smallint unsigned NOT NULL,
  `idBasurero` tinyint unsigned NOT NULL,
  `idTipoBasura` tinyint unsigned NOT NULL,
  `fechaHora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idDeposito`),
  KEY `deposito_iduser_foreign` (`idUser`),
  KEY `deposito_idbasurero_foreign` (`idBasurero`),
  KEY `deposito_idtipobasura_foreign` (`idTipoBasura`),
  CONSTRAINT `deposito_idbasurero_foreign` FOREIGN KEY (`idBasurero`) REFERENCES `basurero` (`idBasurero`),
  CONSTRAINT `deposito_idtipobasura_foreign` FOREIGN KEY (`idTipoBasura`) REFERENCES `tipobasura` (`idTipoBasura`),
  CONSTRAINT `deposito_iduser_foreign` FOREIGN KEY (`idUser`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deposito`
--

LOCK TABLES `deposito` WRITE;
/*!40000 ALTER TABLE `deposito` DISABLE KEYS */;
INSERT INTO `deposito` VALUES (1,2,1,1,'2025-06-07 00:23:45',NULL,NULL),(2,3,2,2,'2025-06-07 00:23:45',NULL,NULL),(3,7,3,3,'2025-06-07 00:23:45',NULL,NULL),(4,8,3,4,'2025-06-07 00:23:45',NULL,NULL),(5,9,4,3,'2025-06-07 00:23:45',NULL,NULL),(6,10,4,4,'2025-06-07 00:23:45',NULL,NULL);
/*!40000 ALTER TABLE `deposito` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente`
--

DROP TABLE IF EXISTS `docente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `docente` (
  `idDocente` smallint unsigned NOT NULL AUTO_INCREMENT,
  `idUser` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idDocente`),
  UNIQUE KEY `docente_iduser_unique` (`idUser`),
  CONSTRAINT `docente_iduser_foreign` FOREIGN KEY (`idUser`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente`
--

LOCK TABLES `docente` WRITE;
/*!40000 ALTER TABLE `docente` DISABLE KEYS */;
INSERT INTO `docente` VALUES (1,4,NULL,NULL),(2,5,NULL,NULL),(3,6,NULL,NULL),(4,13,NULL,NULL),(5,14,NULL,NULL);
/*!40000 ALTER TABLE `docente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_materia_curso`
--

DROP TABLE IF EXISTS `docente_materia_curso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `docente_materia_curso` (
  `idDocente` smallint unsigned NOT NULL,
  `idMateria` smallint unsigned NOT NULL,
  `idCursoParalelo` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idDocente`,`idMateria`,`idCursoParalelo`),
  KEY `idx_docente_materia_curso_docente` (`idDocente`),
  KEY `idx_docente_materia_curso_materia` (`idMateria`),
  KEY `idx_docente_materia_curso_curso_paralelo` (`idCursoParalelo`),
  CONSTRAINT `docente_materia_curso_idcursoparalelo_foreign` FOREIGN KEY (`idCursoParalelo`) REFERENCES `curso_paralelo` (`idCursoParalelo`) ON DELETE CASCADE,
  CONSTRAINT `docente_materia_curso_iddocente_foreign` FOREIGN KEY (`idDocente`) REFERENCES `docente` (`idDocente`) ON DELETE CASCADE,
  CONSTRAINT `docente_materia_curso_idmateria_foreign` FOREIGN KEY (`idMateria`) REFERENCES `materia` (`idMateria`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_materia_curso`
--

LOCK TABLES `docente_materia_curso` WRITE;
/*!40000 ALTER TABLE `docente_materia_curso` DISABLE KEYS */;
INSERT INTO `docente_materia_curso` VALUES (1,3,5,'2025-07-16 05:47:12','2025-07-16 05:47:12'),(1,5,1,'2025-07-16 05:47:12','2025-07-16 05:47:12'),(2,5,12,'2025-07-16 05:42:05','2025-07-16 05:42:05'),(3,2,1,'2025-07-16 05:52:57','2025-07-16 05:52:57'),(4,2,34,'2025-07-16 07:00:43','2025-07-16 07:00:43'),(4,3,34,'2025-07-16 07:00:43','2025-07-16 07:00:43'),(4,9,34,'2025-07-16 07:00:43','2025-07-16 07:00:43');
/*!40000 ALTER TABLE `docente_materia_curso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiante`
--

DROP TABLE IF EXISTS `estudiante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudiante` (
  `idUser` smallint unsigned NOT NULL,
  `idCursoParalelo` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idUser`),
  KEY `estudiante_idcursoparalelo_foreign` (`idCursoParalelo`),
  CONSTRAINT `estudiante_idcursoparalelo_foreign` FOREIGN KEY (`idCursoParalelo`) REFERENCES `curso_paralelo` (`idCursoParalelo`) ON DELETE CASCADE,
  CONSTRAINT `estudiante_iduser_foreign` FOREIGN KEY (`idUser`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudiante`
--

LOCK TABLES `estudiante` WRITE;
/*!40000 ALTER TABLE `estudiante` DISABLE KEYS */;
INSERT INTO `estudiante` VALUES (2,8,NULL,'2025-07-10 06:43:28'),(3,13,NULL,'2025-07-10 06:59:57'),(7,3,NULL,NULL),(8,3,NULL,NULL),(9,9,NULL,'2025-06-09 11:40:12'),(10,9,NULL,'2025-06-09 11:29:54'),(17,4,NULL,NULL),(18,3,NULL,NULL);
/*!40000 ALTER TABLE `estudiante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
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
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
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
-- Table structure for table `materia`
--

DROP TABLE IF EXISTS `materia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materia` (
  `idMateria` smallint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idMateria`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materia`
--

LOCK TABLES `materia` WRITE;
/*!40000 ALTER TABLE `materia` DISABLE KEYS */;
INSERT INTO `materia` VALUES (1,'Matemáticas',NULL,NULL),(2,'Lenguaje',NULL,NULL),(3,'Ciencias Naturales',NULL,NULL),(4,'Historia',NULL,NULL),(5,'Física',NULL,NULL),(9,'Musica','2025-07-16 06:57:59','2025-07-16 06:57:59'),(10,'Quinica','2025-07-16 06:58:12','2025-07-16 06:58:12'),(11,'Talleres','2025-07-16 06:58:18','2025-07-16 06:58:18');
/*!40000 ALTER TABLE `materia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materia_curso_paralelo`
--

DROP TABLE IF EXISTS `materia_curso_paralelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materia_curso_paralelo` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idCursoParalelo` smallint unsigned NOT NULL,
  `idMateria` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `materia_curso_paralelo_idcursoparalelo_foreign` (`idCursoParalelo`),
  KEY `materia_curso_paralelo_idmateria_foreign` (`idMateria`),
  CONSTRAINT `materia_curso_paralelo_idcursoparalelo_foreign` FOREIGN KEY (`idCursoParalelo`) REFERENCES `curso_paralelo` (`idCursoParalelo`) ON DELETE CASCADE,
  CONSTRAINT `materia_curso_paralelo_idmateria_foreign` FOREIGN KEY (`idMateria`) REFERENCES `materia` (`idMateria`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materia_curso_paralelo`
--

LOCK TABLES `materia_curso_paralelo` WRITE;
/*!40000 ALTER TABLE `materia_curso_paralelo` DISABLE KEYS */;
INSERT INTO `materia_curso_paralelo` VALUES (6,11,2,'2025-07-16 03:05:24','2025-07-16 03:05:24'),(7,12,5,'2025-07-16 03:05:24','2025-07-16 03:05:24'),(8,12,2,'2025-07-16 03:05:24','2025-07-16 03:05:24'),(9,1,5,'2025-07-16 03:05:24','2025-07-16 03:05:24'),(10,4,5,'2025-07-16 03:05:24','2025-07-16 03:05:24'),(11,5,5,'2025-07-16 03:05:24','2025-07-16 03:05:24'),(12,3,4,'2025-07-16 03:05:24','2025-07-16 03:05:24'),(13,1,2,'2025-07-16 03:05:24','2025-07-16 03:05:24'),(14,2,5,'2025-07-16 04:19:41','2025-07-16 04:19:41'),(15,7,5,'2025-07-16 04:19:41','2025-07-16 04:19:41'),(17,10,3,'2025-07-16 05:17:32','2025-07-16 05:17:32'),(18,10,5,'2025-07-16 05:17:37','2025-07-16 05:17:37'),(19,10,2,'2025-07-16 05:17:41','2025-07-16 05:17:41'),(22,3,5,'2025-07-16 05:17:55','2025-07-16 05:17:55'),(23,3,2,'2025-07-16 05:17:58','2025-07-16 05:17:58'),(30,5,4,'2025-07-16 05:46:16','2025-07-16 05:46:16'),(31,5,3,'2025-07-16 05:46:24','2025-07-16 05:46:24'),(35,34,5,'2025-07-16 06:57:36','2025-07-16 06:57:36'),(36,34,2,'2025-07-16 06:57:40','2025-07-16 06:57:40'),(38,34,4,'2025-07-16 06:57:47','2025-07-16 06:57:47'),(39,34,3,'2025-07-16 06:57:51','2025-07-16 06:57:51'),(40,34,11,'2025-07-16 06:58:24','2025-07-16 06:58:24'),(41,34,9,'2025-07-16 06:58:28','2025-07-16 06:58:28');
/*!40000 ALTER TABLE `materia_curso_paralelo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_06_19_000002_create_paralelos_table',1),(5,'2024_06_19_000003_create_materias_table',1),(6,'2024_06_19_000004_create_cursos_table',1),(7,'2024_06_19_000005_create_curso_paralelo_table',1),(8,'2024_06_19_000006_create_materias_curso_paralelo_table',1),(9,'2024_06_19_000007_create_estudiantes_table',1),(10,'2024_06_19_000008_create_docentes_table',1),(11,'2024_06_19_000009_create_docente_materia_curso_table',1),(12,'2024_06_19_000010_create_basureros_table',1),(13,'2024_06_19_000011_create_tipos_basura_table',1),(14,'2024_06_19_000012_create_depositos_table',1),(15,'2024_06_19_000013_create_puntajes_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paralelo`
--

DROP TABLE IF EXISTS `paralelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paralelo` (
  `idParalelo` smallint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idParalelo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paralelo`
--

LOCK TABLES `paralelo` WRITE;
/*!40000 ALTER TABLE `paralelo` DISABLE KEYS */;
INSERT INTO `paralelo` VALUES (1,'A',NULL,NULL),(2,'B',NULL,NULL),(3,'C',NULL,NULL),(4,'D',NULL,NULL);
/*!40000 ALTER TABLE `paralelo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('aaronjasonanzaldomamani@gmail.com','$2y$12$t23krGMa/yGMT6pDI6xNVuQJqA6FGGCRXx6Irjd1lrqexRc2E.tcm','2025-07-10 07:05:48');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `puntaje`
--

DROP TABLE IF EXISTS `puntaje`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `puntaje` (
  `idPuntaje` int unsigned NOT NULL AUTO_INCREMENT,
  `idUser` smallint unsigned NOT NULL,
  `puntajeTotal` int NOT NULL DEFAULT '0',
  `fechaActualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idPuntaje`),
  UNIQUE KEY `puntaje_iduser_unique` (`idUser`),
  CONSTRAINT `puntaje_iduser_foreign` FOREIGN KEY (`idUser`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `puntaje`
--

LOCK TABLES `puntaje` WRITE;
/*!40000 ALTER TABLE `puntaje` DISABLE KEYS */;
INSERT INTO `puntaje` VALUES (1,2,10,'2025-06-07 04:23:45',NULL,NULL),(2,3,5,'2025-06-07 04:23:45',NULL,NULL),(3,7,8,'2025-06-07 04:23:45',NULL,NULL),(4,8,6,'2025-06-07 04:23:45',NULL,NULL),(5,9,8,'2025-06-07 04:23:45',NULL,NULL),(6,10,6,'2025-06-07 04:23:45',NULL,NULL),(8,17,0,'2025-07-10 04:14:55',NULL,NULL),(9,18,0,'2025-07-10 04:14:55',NULL,NULL);
/*!40000 ALTER TABLE `puntaje` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` smallint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`),
  CONSTRAINT `sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('3d6CJhrkxfQra5VwgjdB8nvWwRbgFVGDx1Q1bMOW',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWVhWOFhaNzlSUnllcDEzRU5YMm43RmpzdkxOWldCTEhoR2VvZU51byI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9kb2NlbnRlcy8yIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjI7fQ==',1752687391);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipobasura`
--

DROP TABLE IF EXISTS `tipobasura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipobasura` (
  `idTipoBasura` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `puntos` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idTipoBasura`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipobasura`
--

LOCK TABLES `tipobasura` WRITE;
/*!40000 ALTER TABLE `tipobasura` DISABLE KEYS */;
INSERT INTO `tipobasura` VALUES (1,'Plástico','Botellas, envases plásticos',10,NULL,NULL),(2,'Papel','Hojas, cartón',5,NULL,NULL),(3,'Orgánico','Restos de comida, cáscaras',8,NULL,NULL),(4,'Vidrio','Botellas, frascos',6,NULL,NULL);
/*!40000 ALTER TABLE `tipobasura` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `primerApellido` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segundoApellido` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('estudiante','docente','administrador') COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_codigo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_email_unique` (`email`),
  UNIQUE KEY `usuario_qr_codigo_unique` (`qr_codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'Aaron Jason','Anzaldo','Mamani','aronbro100@gmail.com','administrador',NULL,'$2y$12$Ox/Bzq1tvdz/kfAiCXZql.ddf1IV.PNnkNTcQrNScd8mbB1DTAJq2','VTBJlc5kClM9Afd2BmtdRYHBUU3u2AjKYaV84Ph1V0IZzDQMHF8bda8YH8Ad','2025-06-01 07:57:05','2025-07-16 04:44:37'),(2,'Juan','Mamani','Flores','aaronjasonanzaldomamani@gmail.com','estudiante',NULL,'$2y$12$vfs/l1h78zf5/O.YZLJK.uo22wM59McQB6DaH3BCLoDCj2srq9qou',NULL,'2025-06-02 07:45:10','2025-06-09 12:03:45'),(3,'Lucia','Silva','Anzaldo','lucia.silva@example.com','estudiante',NULL,'$2y$12$.vTgByl.QJL2i/KqOwq5L.lUVs5GkZegbdNw7dUkMzqnxvDqIhvum',NULL,'2025-06-07 01:42:13','2025-06-09 10:29:04'),(4,'Carlos Marcos','Rojas','Santos','carlos.rojas@example.com','docente',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-07 04:21:34','2025-07-12 20:44:23'),(5,'María','López','García','maria.lopez@example.com','docente',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-07 04:21:34','2025-06-07 04:21:34'),(6,'Pablo','Fernández','Ruiz','pablo.fernandez@example.com','docente',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-07 04:21:34','2025-06-07 04:21:34'),(7,'Ana','Torrez','Vega','ana.torrez@example.com','estudiante',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-07 04:21:34','2025-06-07 04:21:34'),(8,'Luis','Rivera','Quispe','luis.rivera@example.com','estudiante',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-07 04:21:34','2025-06-07 04:21:34'),(9,'Sofía','Mendoza','Lara','sofia.mendoza@example.com','estudiante',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-07 04:21:34','2025-06-07 04:21:34'),(10,'Diego','Gutiérrez','Ramos','diego.gutierrez@example.com','estudiante',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-07 04:21:34','2025-06-07 04:21:34'),(13,'Elena','Ramírez','Salvatierra','elena.ramirez@example.com','docente',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-09 03:44:20','2025-06-09 03:44:20'),(14,'José','Montoya','Delgado','jose.montoya@example.com','docente',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-09 03:44:20','2025-06-09 03:44:20'),(17,'Miguel','Vargas','Paz','miguel.vargas@example.com','estudiante',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-09 03:44:20','2025-06-09 03:44:20'),(18,'Andrea','Salinas','Flores','andrea.salinas@example.com','estudiante',NULL,'$2y$12$1234567890123456789012',NULL,'2025-06-09 03:44:20','2025-06-09 09:58:11'),(22,'Aaron Anzaldo','Anzaldo','Santos','admin@softui.com','administrador',NULL,'$2y$12$0TZSPue2cwDvIWjvXQV61e2jRn9pZa7ZZkkf108Vdid1HS9glBcSq',NULL,'2025-07-16 04:46:42','2025-07-16 04:46:42');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-16 13:37:30
