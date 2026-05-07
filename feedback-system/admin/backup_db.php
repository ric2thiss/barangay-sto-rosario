<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

// Set memory limit and execution time for large databases
ini_set('memory_limit', '256M');
set_time_limit(300);

$backup_dir = __DIR__ . '/backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Handle Actions
if (isset($_GET['action'])) {
    
    // Create Backup
    if ($_GET['action'] == 'create') {
        try {
            $tables = array();
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }

            $return = "-- Feedback System Database Backup\n";
            $return .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
            $return .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $return .= "SET time_zone = \"+00:00\";\n\n";

            foreach ($tables as $table) {
                $return .= "-- Table structure for table `$table`\n";
                $return .= "DROP TABLE IF EXISTS `$table`;\n";
                
                $row2 = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
                $return .= $row2[1] . ";\n\n";
                
                $return .= "-- Dumping data for table `$table`\n";
                $result = $conn->query("SELECT * FROM `$table`");
                $num_fields = $result->field_count;
                
                if ($result->num_rows > 0) {
                    $return .= "INSERT INTO `$table` VALUES";
                    $count = 0;
                    while ($row = $result->fetch_row()) {
                        $count++;
                        $return .= "\n(";
                        for ($j = 0; $j < $num_fields; $j++) {
                            $row[$j] = addslashes($row[$j]);
                            $row[$j] = str_replace("\n", "\\n", $row[$j]);
                            if (isset($row[$j])) {
                                $return .= '"' . $row[$j] . '"';
                            } else {
                                $return .= '""';
                            }
                            if ($j < ($num_fields - 1)) {
                                $return .= ',';
                            }
                        }
                        $return .= ")";
                        if ($count < $result->num_rows) {
                            $return .= ",";
                        } else {
                            $return .= ";";
                        }
                    }
                    $return .= "\n\n";
                } else {
                    $return .= "-- No data for table `$table`\n\n";
                }
            }

            $filename = 'backup_' . date("Y-m-d_H-i-s") . '.sql';
            $handle = fopen($backup_dir . $filename, 'w+');
            fwrite($handle, $return);
            fclose($handle);

            $_SESSION['backup_message'] = "Backup created successfully!";
            $_SESSION['backup_message_type'] = "success";
            
        } catch (Exception $e) {
            $_SESSION['backup_message'] = "Error creating backup: " . $e->getMessage();
            $_SESSION['backup_message_type'] = "error";
        }
        
        header('Location: admin_settings.php');
        exit;
    }

    // Download Backup
    if ($_GET['action'] == 'download' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $filepath = $backup_dir . $file;
        
        if (file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filepath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        } else {
            $_SESSION['backup_message'] = "File not found.";
            $_SESSION['backup_message_type'] = "error";
            header('Location: admin_settings.php');
            exit;
        }
    }

    // Delete Backup
    if ($_GET['action'] == 'delete' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $filepath = $backup_dir . $file;
        
        if (file_exists($filepath)) {
            unlink($filepath);
            $_SESSION['backup_message'] = "Backup deleted successfully!";
            $_SESSION['backup_message_type'] = "success";
        } else {
            $_SESSION['backup_message'] = "File not found.";
            $_SESSION['backup_message_type'] = "error";
        }
        header('Location: admin_settings.php');
        exit;
    }
}
?>
