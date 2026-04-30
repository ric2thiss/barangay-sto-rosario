<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
$tables = array_map(function ($r) {
    return $r->name;
}, DB::select('SELECT name FROM sqlite_master WHERE type="table" AND name NOT LIKE "sqlite_%"'));
$exportDir = storage_path('app/private/export');
if (!is_dir($exportDir)) {
    mkdir($exportDir, 0777, true);
}
$csvDir = $exportDir . DIRECTORY_SEPARATOR . 'csv';
if (!is_dir($csvDir)) {
    mkdir($csvDir, 0777, true);
}
$sqlFile = $exportDir . DIRECTORY_SEPARATOR . 'export.sql';
$sqlFp = fopen($sqlFile, 'w');
$summary = [];
foreach ($tables as $table) {
    $colsInfo = DB::select('PRAGMA table_info("' . $table . '")');
    $cols = array_map(function ($c) {
        return $c->name;
    }, $colsInfo);
    if (empty($cols)) {
        continue;
    }
    $rows = DB::table($table)->get();
    $summary[$table] = ['columns' => count($cols), 'rows' => $rows->count()];
    $csvPath = $csvDir . DIRECTORY_SEPARATOR . $table . '.csv';
    $csvFp = fopen($csvPath, 'w');
    fputcsv($csvFp, $cols);
    $insertPrefix = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES ';
    foreach ($rows as $row) {
        $rowArr = [];
        foreach ($cols as $c) {
            $v = $row->$c ?? null;
            if ($v === null) {
                $rowArr[] = null;
            } else {
                $rowArr[] = is_scalar($v) ? (string) $v : json_encode($v);
            }
        }
        fputcsv($csvFp, $rowArr);
        $values = [];
        foreach ($cols as $c) {
            $v = $row->$c ?? null;
            if ($v === null) {
                $values[] = 'NULL';
            } elseif ($v instanceof \DateTimeInterface) {
                $values[] = "'" . $v->format('Y-m-d H:i:s') . "'";
            } elseif (is_bool($v)) {
                $values[] = $v ? '1' : '0';
            } elseif (is_numeric($v)) {
                $values[] = (string) $v;
            } else {
                $s = (string) $v;
                $s = str_replace('\\', '\\\\', $s);
                $s = str_replace("'", "\\'", $s);
                $s = str_replace("\r", '\\r', $s);
                $s = str_replace("\n", '\\n', $s);
                $values[] = "'" . $s . "'";
            }
        }
        fwrite($sqlFp, $insertPrefix . '(' . implode(',', $values) . ');' . PHP_EOL);
    }
    fclose($csvFp);
}
fclose($sqlFp);
$summaryPath = $exportDir . DIRECTORY_SEPARATOR . 'summary.json';
file_put_contents($summaryPath, json_encode($summary, JSON_PRETTY_PRINT));
echo "OK\n";
echo "SQL: " . $sqlFile . "\n";
echo "CSV dir: " . $csvDir . "\n";
echo "Summary: " . $summaryPath . "\n";
