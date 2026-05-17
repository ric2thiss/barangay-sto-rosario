<?php
$conn = new mysqli("localhost", "root", "", "treasurer_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tables = ['payments', 'cedula', 'donation', 'garbage', 'rental'];

foreach ($tables as $table) {
    // Find foreign key name for resident_id
    $query = "SELECT CONSTRAINT_NAME 
              FROM information_schema.KEY_COLUMN_USAGE 
              WHERE TABLE_SCHEMA = 'treasurer_management' 
                AND TABLE_NAME = '$table' 
                AND COLUMN_NAME = 'resident_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $fkName = $row['CONSTRAINT_NAME'];
            echo "Dropping FK $fkName from $table...\n";
            $conn->query("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
        }
    }
}

echo "Dropping local residents table...\n";
if ($conn->query("DROP TABLE IF EXISTS `residents`")) {
    echo "Successfully dropped local residents table.\n";
} else {
    echo "Error dropping residents: " . $conn->error . "\n";
}

// Optionally, recreate foreign keys to profiling-system.residents
foreach ($tables as $table) {
    echo "Adding cross-database FK to $table...\n";
    $sql = "ALTER TABLE `$table` ADD CONSTRAINT `fk_{$table}_resident_id` FOREIGN KEY (`resident_id`) REFERENCES `profiling-system`.`residents`(`id`) ON DELETE SET NULL";
    if ($conn->query($sql)) {
        echo "Successfully added FK to $table.\n";
    } else {
        echo "Error adding FK to $table: " . $conn->error . "\n";
    }
}

echo "Done.\n";
