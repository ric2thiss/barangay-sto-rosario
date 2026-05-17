-- Migration: Add department_id column to employees table
-- Run this SQL to add the department_id column to the employees table

ALTER TABLE `employees` 
ADD COLUMN `department_id` int(11) NULL DEFAULT NULL AFTER `position_id`,
ADD KEY `department_id` (`department_id`),
ADD CONSTRAINT `employees_ibfk_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;
