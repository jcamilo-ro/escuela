-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: escuela_db
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
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student` (
  `id` int(15) NOT NULL AUTO_INCREMENT,
  `codigo_estudiante` varchar(5) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_student_codigo_estudiante` (`codigo_estudiante`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student`
--

LOCK TABLES `student` WRITE;
/*!40000 ALTER TABLE `student` DISABLE KEYS */;
INSERT INTO `student` VALUES (1,'26001','Juan','Ruales','juan@gmail.com'),(2,'26002','Camilo','Ospina','camilo@gmail.com'),(3,'26003','Camila','Achicanoy','camilaa@gmail.com'),(4,'26004','Alejandra','Cabrera','alejaca@gmail.com'),(5,'26005','Ruby','Padilla','ruby@gmail.com'),(6,'26006','Jairo','Lagos','jairo@gmail.com'),(7,'26007','Juan','Cardenas','juancar@gmail.com'),(10,'26008','Eliana','Bravo','eliana@gmail.com'),(11,'26009','Mario','Pantoja','mario@gmail.com'),(12,'26010','Sofía','Cárdenas','sofiacar@gmail.com'),(13,'26011','Miguel','Cabrera','miguelca@gmail.com');
/*!40000 ALTER TABLE `student` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_subject`
--

DROP TABLE IF EXISTS `student_subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_student_subject` (`student_id`,`subject_id`),
  KEY `ix_student_subject_subject` (`subject_id`),
  CONSTRAINT `fk_student_subject_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_subject_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_subject`
--

LOCK TABLES `student_subject` WRITE;
/*!40000 ALTER TABLE `student_subject` DISABLE KEYS */;
INSERT INTO `student_subject` VALUES (5,1,2,'2026-04-07 02:44:57'),(6,1,4,'2026-04-07 02:44:57'),(7,1,1,'2026-04-07 02:44:57'),(8,2,3,'2026-04-07 02:45:11'),(9,2,1,'2026-04-07 02:45:11'),(10,2,5,'2026-04-07 02:45:11'),(11,3,4,'2026-04-07 02:45:17'),(12,3,3,'2026-04-07 02:45:17'),(13,3,1,'2026-04-07 02:45:17'),(14,4,2,'2026-04-07 02:45:25'),(15,4,3,'2026-04-07 02:45:25'),(16,4,5,'2026-04-07 02:45:25'),(17,5,4,'2026-04-07 02:45:31'),(18,5,1,'2026-04-07 02:45:31'),(19,5,5,'2026-04-07 02:45:31'),(20,6,4,'2026-04-07 02:45:37'),(21,6,3,'2026-04-07 02:45:37'),(22,6,1,'2026-04-07 02:45:37'),(23,7,4,'2026-04-07 02:45:43'),(24,7,1,'2026-04-07 02:45:43'),(25,7,5,'2026-04-07 02:45:43'),(26,10,2,'2026-04-07 02:45:56'),(27,10,3,'2026-04-07 02:45:56'),(28,10,5,'2026-04-07 02:45:56'),(29,11,3,'2026-04-07 02:46:03'),(30,11,1,'2026-04-07 02:46:03'),(31,11,5,'2026-04-07 02:46:03'),(32,12,4,'2026-04-07 02:46:10'),(33,12,3,'2026-04-07 02:46:10'),(34,12,5,'2026-04-07 02:46:10');
/*!40000 ALTER TABLE `student_subject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject`
--

DROP TABLE IF EXISTS `subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `creditos` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_subject_nombre` (`nombre`),
  UNIQUE KEY `ux_subject_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject`
--

LOCK TABLES `subject` WRITE;
/*!40000 ALTER TABLE `subject` DISABLE KEYS */;
INSERT INTO `subject` VALUES (1,'Programacion I','SIS101',4,'2026-03-17 13:06:35'),(2,'Bases de Datos','SIS102',4,'2026-03-17 13:06:35'),(3,'Ingenieria de Software','SIS201',4,'2026-03-17 13:06:35'),(4,'Estructuras de Datos','SIS202',4,'2026-03-17 13:06:35'),(5,'Redes de Computadores','SIS203',3,'2026-03-17 13:06:35');
/*!40000 ALTER TABLE `subject` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-06 23:10:07
