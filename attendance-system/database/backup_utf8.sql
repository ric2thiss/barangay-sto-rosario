-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: attendance-system
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','admin@gmail.com','$2y$10$yt4x02zonQ9TaoZ55aVaDuJMxY9XVj2RdrU38Q.9e9ZZ89SmCeYV6','System Administrator','administrator',1,'2026-01-24 01:58:32','2025-12-13 15:36:27','2026-04-02 06:14:10');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'app_name','Attendance System','string','Application name',1,'2026-01-14 19:59:38','2026-04-02 04:56:33'),(2,'maintenance_mode','0','boolean','Enable maintenance mode (1 = enabled, 0 = disabled)',1,'2026-01-14 19:59:38','2026-01-15 20:53:17'),(3,'maintenance_message','The system is currently under maintenance. Please try again later.','string','Message shown during maintenance mode',1,'2026-01-14 19:59:38','2026-01-15 06:39:40'),(4,'timezone','Asia/Manila','string','Default timezone',1,'2026-01-14 19:59:38','2026-01-24 07:01:50'),(5,'data_retention_days','365','integer','Number of days to retain attendance logs before archival',1,'2026-01-14 19:59:38','2026-01-24 07:01:50'),(6,'allow_registration','1','boolean','Allow new user registration (1 = enabled, 0 = disabled)',NULL,'2026-01-14 19:59:38',NULL),(7,'active_attendance_activity_id','','string','Activity ID applied when attendance API request omits activity_id (e.g. biometric client)',1,'2026-03-30 09:42:55','2026-03-30 15:57:09'),(8,'user_access_control','{\"attendance_admins\":true,\"profiling_admin\":true,\"barangay_officials\":true,\"residents\":false}','json','Which account categories may log in (checked at login only)',NULL,'2026-04-02 04:17:41','2026-04-02 12:31:44'),(9,'apache_access_log_path','','string','Optional full path to Apache access.log (empty = auto-detect XAMPP / common paths)',NULL,'2026-04-02 04:17:41',NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-06 12:07:27
