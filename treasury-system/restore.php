<?php
$conn = new mysqli("localhost", "root", "");
$conn->query("CREATE DATABASE IF NOT EXISTS `profiling-system`");
echo "DB checked/created\n";
