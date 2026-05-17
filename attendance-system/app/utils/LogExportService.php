<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

/**
 * Export attendance or visitor logs (non-deleted rows only) in multiple formats.
 */
class LogExportService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? (new Database())->connect();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAttendanceRows(?string $fromYmd, ?string $toYmd, int $maxRows = 50000): array
    {
        $sql = 'SELECT * FROM attendances WHERE 1=1';
        if (SchemaColumnCache::attendancesHasDeletedAt()) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $params = [];
        if ($fromYmd !== null && $fromYmd !== '') {
            $sql .= ' AND DATE(COALESCE(`timestamp`, created_at)) >= ?';
            $params[] = $fromYmd;
        }
        if ($toYmd !== null && $toYmd !== '') {
            $sql .= ' AND DATE(COALESCE(`timestamp`, created_at)) <= ?';
            $params[] = $toYmd;
        }
        $sql .= ' ORDER BY id ASC LIMIT ' . max(1, min(100000, $maxRows));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchVisitorRows(?string $fromYmd, ?string $toYmd, int $maxRows = 50000): array
    {
        $sql = 'SELECT * FROM visitor_logs WHERE 1=1';
        if (SchemaColumnCache::visitorLogsHasDeletedAt()) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $params = [];
        if ($fromYmd !== null && $fromYmd !== '') {
            $sql .= ' AND DATE(created_at) >= ?';
            $params[] = $fromYmd;
        }
        if ($toYmd !== null && $toYmd !== '') {
            $sql .= ' AND DATE(created_at) <= ?';
            $params[] = $toYmd;
        }
        $sql .= ' ORDER BY id ASC LIMIT ' . max(1, min(100000, $maxRows));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function toSqlInserts(string $table, array $rows): string
    {
        if ($rows === []) {
            return "-- No rows\n";
        }
        $cols = array_keys($rows[0]);
        $colList = '`' . implode('`,`', $cols) . '`';
        $out = "-- Export {$table}\nSET NAMES utf8mb4;\n";
        foreach ($rows as $row) {
            $vals = [];
            foreach ($cols as $c) {
                $v = $row[$c] ?? null;
                if ($v === null) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = $this->pdo->quote((string) $v);
                }
            }
            $out .= 'INSERT INTO `' . $table . '` (' . $colList . ') VALUES (' . implode(',', $vals) . ");\n";
        }

        return $out;
    }

    public function toPdf(string $title, array $headers, array $rows, int $maxDisplay = 500): string
    {
        if (!class_exists('TCPDF')) {
            throw new RuntimeException('TCPDF not available');
        }
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Attendance System');
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Write(0, $title, '', 0, 'L', true, 0, false, false, 0);
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 7);

        $slice = array_slice($rows, 0, max(1, min(2000, $maxDisplay)));
        $html = '<table border="1" cellpadding="3"><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . htmlspecialchars((string) $h, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($slice as $r) {
            $html .= '<tr>';
            foreach ($headers as $h) {
                $cell = $r[$h] ?? '';
                $html .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        if (count($rows) > count($slice)) {
            $html .= '<p>Showing ' . count($slice) . ' of ' . count($rows) . ' rows.</p>';
        }
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }

    public function toXlsx(string $title, array $headers, array $rows): string
    {
        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9_]/', '_', $title), 0, 31) ?: 'Sheet1');
        $data = [array_values($headers)];
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = $row[$h] ?? '';
            }
            $data[] = $line;
        }
        $sheet->fromArray($data, null, 'A1', true);
        $writer = new Xlsx($ss);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    public function toDocx(string $title, array $headers, array $rows, int $maxDisplay = 400): string
    {
        $word = new PhpWord();
        $section = $word->addSection();
        $section->addText($title, ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        $slice = array_slice($rows, 0, max(1, min(2000, $maxDisplay)));
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        $table->addRow();
        foreach ($headers as $h) {
            $table->addCell(2000)->addText((string) $h, ['bold' => true]);
        }
        foreach ($slice as $r) {
            $table->addRow();
            foreach ($headers as $h) {
                $table->addCell(2000)->addText((string) ($r[$h] ?? ''));
            }
        }

        $writer = WordIOFactory::createWriter($word, 'Word2007');
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * @param list<string> $formats sql pdf docx xlsx
     * @return string binary zip
     */
    public function toZip(string $baseName, string $logType, array $formats, ?string $fromYmd, ?string $toYmd): string
    {
        $rows = $logType === 'visitor'
            ? $this->fetchVisitorRows($fromYmd, $toYmd)
            : $this->fetchAttendanceRows($fromYmd, $toYmd);
        $table = $logType === 'visitor' ? 'visitor_logs' : 'attendances';
        $headers = $rows !== [] ? array_keys($rows[0]) : [];

        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'lgexp');
        if ($tmp === false) {
            throw new RuntimeException('temp file');
        }
        @unlink($tmp);
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('zip open');
        }

        foreach ($formats as $fmt) {
            $fmt = strtolower(trim($fmt));
            try {
                if ($fmt === 'sql') {
                    $zip->addFromString("{$baseName}.sql", $this->toSqlInserts($table, $rows));
                } elseif ($fmt === 'pdf') {
                    $zip->addFromString(
                        "{$baseName}.pdf",
                        $this->toPdf($baseName, $headers, $rows)
                    );
                } elseif ($fmt === 'xlsx') {
                    $zip->addFromString(
                        "{$baseName}.xlsx",
                        $this->toXlsx($baseName, $headers, $rows)
                    );
                } elseif ($fmt === 'docx') {
                    $zip->addFromString(
                        "{$baseName}.docx",
                        $this->toDocx($baseName, $headers, $rows)
                    );
                }
            } catch (Throwable $e) {
                $zip->addFromString("{$baseName}_{$fmt}_error.txt", $e->getMessage());
            }
        }
        $zip->close();
        $bin = file_get_contents($tmp) ?: '';
        @unlink($tmp);

        return $bin;
    }
}
