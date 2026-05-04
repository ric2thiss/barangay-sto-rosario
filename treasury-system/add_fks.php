<?php
$conn = new mysqli("localhost", "root", "", "treasurer_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tables = ['payments', 'cedula', 'donation', 'garbage', 'rental'];

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
