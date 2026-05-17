<?php
/**
 * Benchmark: measure what's slow on the visitors page init flow.
 */
session_start();
$_SESSION['is_authenticated'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_full_name'] = 'Admin User';
$_SESSION['admin_role'] = 'Administrator';

require_once __DIR__ . '/../bootstrap.php';

$t = microtime(true);

// 1. Fetch all residents (what api/visitors/residents.php does)
$residentController = new ResidentController();
$residents = $residentController->getAllResidents();
$t1 = microtime(true);
echo "1. Fetch all residents: " . round(($t1 - $t) * 1000) . "ms (" . count($residents) . " rows)\n";

// 2. Count those with photos
$withPhotos = 0;
$totalPhotos = 0;
foreach ($residents as $r) {
    $path = $r['photo_path'] ?? $r['image_path'] ?? null;
    if (!empty($path)) {
        $withPhotos++;
        $decoded = json_decode($path, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $totalPhotos += count($decoded);
        } else {
            $totalPhotos += 1;
        }
    }
}
$t2 = microtime(true);
echo "2. Residents with photos: {$withPhotos}, total photo URLs: {$totalPhotos} (" . round(($t2 - $t1) * 1000) . "ms)\n";

// 3. Face-api model files size
$modelsDir = __DIR__ . '/../visitors/models';
$modelFiles = glob($modelsDir . '/*');
$totalModelSize = 0;
foreach ($modelFiles as $f) {
    $totalModelSize += filesize($f);
}
echo "3. Face-API model files: " . count($modelFiles) . " files, " . round($totalModelSize / 1024 / 1024, 2) . " MB total\n";

// Loaded models in faceRecognition.js:
echo "\n--- Models loaded by JS ---\n";
echo "  tinyFaceDetector:    ~190 KB\n";
echo "  faceLandmark68:      ~357 KB\n";
echo "  ssdMobilenetv1:      ~5.5 MB\n";
echo "  faceRecognition:     ~6.1 MB\n";
echo "  Total download:      ~12 MB\n";

echo "\n--- Performance bottlenecks ---\n";
echo "a) face-api.js CDN lib: 0.22.2 (800+ KB unminified)\n";
echo "b) 4 neural net models: ~12 MB download on each page load\n";
echo "c) Per-resident face descriptor extraction: each photo processed through neural net\n";
echo "   {$withPhotos} residents × {$totalPhotos} photos = {$totalPhotos} neural net inferences on page load\n";
echo "d) All this happens BEFORE camera even starts\n";
