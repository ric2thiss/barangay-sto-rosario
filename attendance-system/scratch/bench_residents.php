<?php
require_once __DIR__ . '/../bootstrap.php';
$rc = new ResidentController();

$t = microtime(true);
$old = $rc->getAllResidents();
$t1 = microtime(true);
$new = $rc->getResidentsForFaceRecognition();
$t2 = microtime(true);

echo 'Old method (getAllResidents): ' . count($old) . ' rows in ' . round(($t1-$t)*1000) . "ms\n";
echo 'New method (getForFaceRecognition): ' . count($new) . ' rows in ' . round(($t2-$t1)*1000) . "ms\n";
echo 'Speedup: ' . round(($t1-$t)/max(0.001,($t2-$t1)), 1) . "x\n";
echo 'Sample row: ' . json_encode($new[0] ?? 'empty') . "\n";
