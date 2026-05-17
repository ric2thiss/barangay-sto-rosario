<?php
require 'bootstrap.php';
$repo = new AttendanceRepository((new Database())->connect());
$last = $repo->getLast();
var_dump($last);
