<?php

namespace App\Services;

use App\Models\BlotterRecord;
use App\Models\Resident;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

/**
 * Export blotter DOCX by emitting exactly the template page for that form.
 * No placeholder replacement — just export the page as-is.
 *
 * Mirrors certificate export: templates from resource_path (and storage override),
 * write to temp first, then copy to output path.
 *
 * Template lookup (first found):
 *   1. storage/app/private/public/blotter_docs/
 *   2. resources/views/certificates/templates/  (same as certificates)
 *
 * - kp_form1.docx, kp_form2.docx, ...  → one file per form; copy as-is.
 * - kp_form.docx                       → multi-page; Form 1 → page 1, Form 2 → page 2, etc.
 */
class KpFormDocxExporter
{
    private string $storageDir;

    private string $resourceDir;

    public function __construct()
    {
        $this->storageDir = rtrim(str_replace('/', DIRECTORY_SEPARATOR, storage_path('app/private/public/blotter_docs')), DIRECTORY_SEPARATOR);
        $this->resourceDir = rtrim(str_replace('/', DIRECTORY_SEPARATOR, resource_path('views/certificates/templates')), DIRECTORY_SEPARATOR);
        // Ensure we're pointing to the correct directory structure
        if (! is_dir($this->resourceDir)) {
            $this->resourceDir = rtrim(str_replace('/', DIRECTORY_SEPARATOR, base_path('resources/views/certificates/templates')), DIRECTORY_SEPARATOR);
        }
    }

    private function resolveTemplate(string $basename = 'kp_form.docx'): ?string
    {
        $sep = DIRECTORY_SEPARATOR;
        // Look for the main template file
        $path = $this->resourceDir.$sep.$basename;

        if (is_file($path)) {
            return $path;
        }

        $path = $this->storageDir.$sep.$basename;

        if (is_file($path)) {
            return $path;
        }

        return null;
    }

    private function formId(BlotterRecord $blotter): int
    {
        $id = $blotter->form_id ?? $blotter->form_number;
        $id = trim((string) $id);

        // Extract form number from various formats like "KP Form No. 1", "KP Form Number 1", etc.
        if (preg_match('/KP\s+FORM\s+(?:Number|No\.?)\s+(\d+)/i', $id, $matches)) {
            return (int) $matches[1];
        }

        // Try to extract number from other formats
        if (preg_match('/^\\D*(\d+)/', $id, $matches)) {
            return (int) $matches[1];
        }

        return 1; // Default to page 1 if no form number found
    }

    /**
     * Export the exact template page. No placeholders.
     * Writes to temp first, then copies to outputPath (certificate-style).
     *
     * @return bool true if exported from template, false to use PhpWord fallback
     */
    public function exportToFile(BlotterRecord $blotter, string $outputPath): bool
    {
        // Get the form number to determine which page to extract
        $rawId = trim((string) ($blotter->form_id ?? $blotter->form_number));
        $formNumber = $this->formId($blotter);

        // Try per-form template first: kp_form{N}.docx
        $perForm = $this->resolveTemplate('kp_form'.$formNumber.'.docx');
        if ($perForm !== null) {
            return $this->exportWholeWithReplacement($perForm, $outputPath, $blotter);
        }

        // Fallback to multi-page template: kp_form.docx
        $template = $this->resolveTemplate('kp_form.docx');
        if ($template === null) {
            Log::info('KpFormDocxExporter: no kp_form.docx in storage or resources/templates.');

            return false;
        }
        $rawIdLower = strtolower($rawId);
        $explicitIdx = null;
        if (str_contains($rawIdLower, '9-a') || str_contains($rawIdLower, '9a')) {
            $explicitIdx = 10;
        } elseif (str_contains($rawIdLower, 'page 2') || str_contains($rawIdLower, '9-2')) {
            $explicitIdx = 9;
        } elseif (preg_match('/\b20[-\s]?b[-\s]?a\b/i', $rawId)) {
            $explicitIdx = 24;
        } elseif (preg_match('/\b20[-\s]?b\b/i', $rawId)) {
            $explicitIdx = 23;
        } elseif (preg_match('/\b20[-\s]?a\b/i', $rawId)) {
            $explicitIdx = 22;
        } elseif (str_contains($rawIdLower, 'dismissal-order') || str_contains($rawIdLower, 'dismissal order') || str_contains($rawIdLower, 'improvised kp form')) {
            $explicitIdx = 30;
        } elseif (str_contains($rawIdLower, 'minutes-of-proceedings') || str_contains($rawIdLower, 'minutes of proceedings')) {
            $explicitIdx = 31;
        } elseif (str_contains($rawIdLower, 'case-record') || str_contains($rawIdLower, 'case record')) {
            $explicitIdx = 32;
        } else {
            if ($formNumber >= 1 && $formNumber <= 8) {
                $explicitIdx = $formNumber - 1;
            } elseif ($formNumber === 9) {
                $explicitIdx = 8;
            } elseif ($formNumber >= 10 && $formNumber <= 19) {
                $explicitIdx = ($formNumber - 1) + 2;
            } elseif ($formNumber === 20) {
                $explicitIdx = 21;
            } elseif ($formNumber >= 21) {
                $explicitIdx = ($formNumber - 1) + 5;
            }
        }
        if ($explicitIdx !== null) {
            $ok = $this->exportPageFromMultiPage($template, $explicitIdx, $outputPath, $blotter);
            if ($ok) {
                return true;
            }
        }

        // Fallback: locate Case Record page by text if explicit mapping failed
        if (str_contains($rawIdLower, 'case-record') || str_contains($rawIdLower, 'case record')) {
            $idx = $this->findPageIndexByNeedles($template, ['case', 'record']);
            if ($idx < 0) {
                $idx = $this->findPageIndexByNeedles($template, ['nature', 'case']);
            }
            if ($idx >= 0) {
                $ok = $this->exportPageFromMultiPage($template, $idx, $outputPath, $blotter);
                if ($ok) {
                    return true;
                }
            }
        }

        // Default mapping: Form N → Page N
        if ($formNumber >= 1) {
            $ok = $this->exportPageFromMultiPage($template, $formNumber - 1, $outputPath, $blotter);
            if ($ok) {
                return true;
            }
            Log::warning("KpFormDocxExporter: page extraction failed for form {$formNumber}; copying whole template.");
        }

        // Fallback for invalid form numbers or failed extraction
        return $this->copyViaTemp($template, $outputPath);
    }

    private function findPageIndexByNeedles(string $docxPath, array $needles, int $nth = 1): int
    {
        $zip = new \ZipArchive;
        if ($zip->open($docxPath, \ZipArchive::RDONLY) !== true) {
            return -1;
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            return -1;
        }
        $dom = new DOMDocument;
        @$dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $pages = $this->splitBodyIntoPages($dom, $xpath);
        $count = 0;
        foreach ($pages as $i => $nodes) {
            $textParts = [];
            foreach ($nodes as $node) {
                $texts = $xpath->query('.//w:t', $node);
                if ($texts && $texts->length > 0) {
                    foreach ($texts as $t) {
                        $textParts[] = strtolower($t->textContent ?? '');
                    }
                }
            }
            $pageText = implode(' ', $textParts);
            $ok = true;
            foreach ($needles as $n) {
                if (! str_contains($pageText, strtolower($n))) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $count++;
                if ($count === $nth) {
                    return $i;
                }
            }
        }

        return -1;
    }

    /**
     * Certificate-style: copy source to temp, then temp → output. Never write directly to output.
     */
    private function copyViaTemp(string $sourcePath, string $outputPath): bool
    {
        $tmp = tempnam(sys_get_temp_dir(), 'blotter_docx_');
        if ($tmp === false) {
            return false;
        }
        if (! @copy($sourcePath, $tmp)) {
            @unlink($tmp);

            return false;
        }
        $ok = $this->writeTempToOutput($tmp, $outputPath);
        if (file_exists($tmp)) {
            @unlink($tmp);
        }

        return $ok;
    }

    private function writeTempToOutput(string $tempPath, string $outputPath): bool
    {
        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (file_exists($outputPath)) {
            @unlink($outputPath);
        }
        if (@copy($tempPath, $outputPath)) {
            return true;
        }
        if (@rename($tempPath, $outputPath)) {
            return true;
        }

        return false;
    }

    private function exportWholeWithReplacement(string $docxPath, string $outputPath, BlotterRecord $blotter): bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($docxPath, \ZipArchive::RDONLY) !== true) {
            return false;
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            return false;
        }
        $newXml = $this->replacePlaceholders($xml, $blotter);
        $tmp = tempnam(sys_get_temp_dir(), 'blotter_docx_');
        if ($tmp === false) {
            return false;
        }
        $zipIn = new \ZipArchive;
        if ($zipIn->open($docxPath, \ZipArchive::RDONLY) !== true) {
            @unlink($tmp);

            return false;
        }
        $zipOut = new \ZipArchive;
        if ($zipOut->open($tmp, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            $zipIn->close();
            @unlink($tmp);

            return false;
        }
        for ($i = 0; $i < $zipIn->numFiles; $i++) {
            $name = $zipIn->getNameIndex($i);
            $data = $zipIn->getFromIndex($i);
            if ($name === 'word/document.xml') {
                $data = $newXml;
            }
            if ($data !== false) {
                $zipOut->addFromString($name, $data);
            }
        }
        $zipIn->close();
        $zipOut->close();
        $ok = $this->writeTempToOutput($tmp, $outputPath);
        if (file_exists($tmp)) {
            @unlink($tmp);
        }

        return $ok;
    }

    private function exportPageFromMultiPage(string $docxPath, int $pageIndex, string $outputPath, ?BlotterRecord $blotter = null): bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($docxPath, \ZipArchive::RDONLY) !== true) {
            return false;
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            return false;
        }

        $dom = new DOMDocument;
        @$dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $pages = $this->splitBodyIntoPages($dom, $xpath);
        if (! isset($pages[$pageIndex])) {
            Log::warning("KpFormDocxExporter: page index {$pageIndex} not found (pages: ".count($pages).').');

            return false;
        }

        $pageNodes = $pages[$pageIndex];
        $outDom = new DOMDocument('1.0', 'UTF-8');
        $outDom->formatOutput = false;
        $outDom->loadXML($dom->saveXML());
        $outXpath = new DOMXPath($outDom);
        $outXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $body = $outXpath->query('//w:body')->item(0);
        if (! $body) {
            return false;
        }
        while ($body->firstChild) {
            $body->removeChild($body->firstChild);
        }
        foreach ($pageNodes as $n) {
            $body->appendChild($outDom->importNode($n->cloneNode(true), true));
        }

        $newXml = $outDom->saveXML();
        if ($blotter) {
            $newXml = $this->replacePlaceholders($newXml, $blotter);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'blotter_docx_');
        if ($tmp === false) {
            return false;
        }

        $zipIn = new \ZipArchive;
        if ($zipIn->open($docxPath, \ZipArchive::RDONLY) !== true) {
            @unlink($tmp);

            return false;
        }
        $zipOut = new \ZipArchive;
        if ($zipOut->open($tmp, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            $zipIn->close();
            @unlink($tmp);

            return false;
        }
        for ($i = 0; $i < $zipIn->numFiles; $i++) {
            $name = $zipIn->getNameIndex($i);
            $data = $zipIn->getFromIndex($i);
            if ($name === 'word/document.xml') {
                $data = $newXml;
            }
            if ($data !== false) {
                $zipOut->addFromString($name, $data);
            }
        }
        $zipIn->close();
        $zipOut->close();

        $ok = $this->writeTempToOutput($tmp, $outputPath);
        if (file_exists($tmp)) {
            @unlink($tmp);
        }

        return $ok;
    }

    private function exportCombinedPagesFromMultiPage(string $docxPath, array $pageIndices, string $outputPath, ?BlotterRecord $blotter = null): bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($docxPath, \ZipArchive::RDONLY) !== true) {
            return false;
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            return false;
        }
        $dom = new DOMDocument;
        @$dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $pages = $this->splitBodyIntoPages($dom, $xpath);
        $outDom = new DOMDocument('1.0', 'UTF-8');
        $outDom->formatOutput = false;
        $outDom->loadXML($dom->saveXML());
        $outXpath = new DOMXPath($outDom);
        $outXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $body = $outXpath->query('//w:body')->item(0);
        if (! $body) {
            return false;
        }
        while ($body->firstChild) {
            $body->removeChild($body->firstChild);
        }
        foreach ($pageIndices as $pi) {
            if (! isset($pages[$pi])) {
                continue;
            }
            foreach ($pages[$pi] as $n) {
                $body->appendChild($outDom->importNode($n->cloneNode(true), true));
            }
        }
        $newXml = $outDom->saveXML();
        if ($blotter) {
            $newXml = $this->replacePlaceholders($newXml, $blotter);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'blotter_docx_');
        if ($tmp === false) {
            return false;
        }
        $zipIn = new \ZipArchive;
        if ($zipIn->open($docxPath, \ZipArchive::RDONLY) !== true) {
            @unlink($tmp);
            return false;
        }
        $zipOut = new \ZipArchive;
        if ($zipOut->open($tmp, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            $zipIn->close();
            @unlink($tmp);
            return false;
        }
        for ($i = 0; $i < $zipIn->numFiles; $i++) {
            $name = $zipIn->getNameIndex($i);
            $data = $zipIn->getFromIndex($i);
            if ($name === 'word/document.xml') {
                $data = $newXml;
            }
            if ($data !== false) {
                $zipOut->addFromString($name, $data);
            }
        }
        $zipIn->close();
        $zipOut->close();
        $ok = $this->writeTempToOutput($tmp, $outputPath);
        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        return $ok;
    }

    /**
     * Split body into pages: by section (sectPr) first; if only one section, by explicit page breaks.
     *
     * @return array<int, list<\DOMNode>>
     */
    private function splitBodyIntoPages(DOMDocument $dom, DOMXPath $xpath): array
    {
        $bySection = $this->splitBodyIntoSections($dom, $xpath);
        if (count($bySection) > 1) {
            return $bySection;
        }

        return $this->splitBodyByPageBreaks($dom, $xpath);
    }

    private function splitBodyIntoSections(DOMDocument $dom, DOMXPath $xpath): array
    {
        $body = $xpath->query('//w:body')->item(0);
        if (! $body) {
            return [];
        }
        $sections = [];
        $current = [];
        foreach ($body->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $current[] = $node;
            $hasSectPr = false;
            if (($node->localName ?? '') === 'sectPr' || str_ends_with($node->nodeName ?? '', ':sectPr')) {
                $hasSectPr = true;
            } else {
                $pr = $xpath->query('.//w:sectPr', $node)->item(0);
                $hasSectPr = $pr !== null;
            }
            if ($hasSectPr) {
                $sections[] = $current;
                $current = [];
            }
        }
        if (count($current) > 0) {
            $sections[] = $current;
        }

        return $sections;
    }

    /**
     * Split body by <w:br w:type="page"/> so we get one page per break.
     */
    private function splitBodyByPageBreaks(DOMDocument $dom, DOMXPath $xpath): array
    {
        $body = $xpath->query('//w:body')->item(0);
        if (! $body) {
            return [];
        }
        $pages = [];
        $current = [];
        foreach ($body->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $current[] = $node;
            $hasPageBreak = $xpath->query('.//w:br[@w:type="page"]', $node)->length > 0;
            if ($hasPageBreak) {
                $pages[] = $current;
                $current = [];
            }
        }
        if (count($current) > 0) {
            $pages[] = $current;
        }

        return $pages;
    }

    private function replacePlaceholders(string $xml, BlotterRecord $blotter): string
    {
        // Prepare data for replacement
        $complainant = $blotter->complainant;
        $respondent = $blotter->respondent;

        // Define placeholder mappings
        $placeholders = [];

        // Basic blotter info
        $placeholders['{form_number}'] = $blotter->form_number ?? '';
        $placeholders['{FORM_NUMBER}'] = $blotter->form_number ?? '';
        $formData = $blotter->form_data ?? [];
       
// After
$placeholders['{case_number}'] = (string) ($formData['case_number'] ?? $formData['case_reference'] ?? $blotter->blotter_id ?? '');
$placeholders['{CASE_NUMBER}'] = (string) ($formData['case_number'] ?? $formData['case_reference'] ?? $blotter->blotter_id ?? '');
        $placeholders['{status}'] = $blotter->status ?? '';
        $placeholders['{STATUS}'] = $blotter->status ?? '';
        $placeholders['{incident_date}'] = $blotter->created_at->format('F j, Y') ?? '';
        $placeholders['{INCIDENT_DATE}'] = $blotter->created_at->format('F j, Y') ?? '';
        $placeholders['{purpose}'] = $blotter->purpose ?? '';
        $placeholders['{PURPOSE}'] = $blotter->purpose ?? '';
        $placeholders['{incident_description}'] = $blotter->incident_details ?? '';
        $placeholders['{INCIDENT_DESCRIPTION}'] = $blotter->incident_details ?? '';

        // Complainant info
        if ($complainant) {
            $placeholders['{complainant_fullname}'] = $complainant->full_name ?? '';
            $placeholders['{COMPLAINANT_FULLNAME}'] = $complainant->full_name ?? '';
            $placeholders['{complainant_name}'] = $complainant->full_name ?? '';
            $placeholders['{COMPLAINANT_NAME}'] = $complainant->full_name ?? '';
            $placeholders['{complainant_address}'] = $complainant->address ?? '';
            $placeholders['{COMPLAINANT_ADDRESS}'] = $complainant->address ?? '';
            $placeholders['{complainant_age}'] = $complainant->age ?? '';
            $placeholders['{COMPLAINANT_AGE}'] = $complainant->age ?? '';
        }

        // Respondent info
        if ($respondent) {
            $placeholders['{respondent_fullname}'] = $respondent->full_name ?? '';
            $placeholders['{RESPONDENT_FULLNAME}'] = $respondent->full_name ?? '';
            $placeholders['{respondent_name}'] = $respondent->full_name ?? '';
            $placeholders['{RESPONDENT_NAME}'] = $respondent->full_name ?? '';
            $placeholders['{respondent_address}'] = $respondent->address ?? '';
            $placeholders['{RESPONDENT_ADDRESS}'] = $respondent->address ?? '';
            $placeholders['{respondent_age}'] = $respondent->age ?? '';
            $placeholders['{RESPONDENT_AGE}'] = $respondent->age ?? '';
        }

        // Handle form-specific data
        $formData = $blotter->form_data ?? [];
        foreach ($formData as $key => $value) {
            $val = $value;
            if (is_array($val)) {
                $val = implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $val));
            } elseif ($val instanceof \DateTimeInterface) {
                $val = $val->format('F j, Y');
            } elseif (! is_scalar($val) && $val !== null) {
                $val = (string) $val;
            }
            $placeholders['{'.$key.'}'] = $val ?? '';
            $placeholders['{'.strtoupper($key).'}'] = $val ?? '';
        }

        $formNumber = $this->formId($blotter);
        if ($formNumber === 1) {
            $issueDate = now();
            $placeholders['{date}'] = $issueDate->format('F j, Y');
            $placeholders['{DATE}'] = $issueDate->format('F j, Y');
            $placeholders['{date_issued}'] = $issueDate->format('F j, Y');
            $placeholders['{DATE_ISSUED}'] = $issueDate->format('F j, Y');
            $deadlineBase = isset($formData['deadline_date']) ? \Carbon\Carbon::parse($formData['deadline_date']) : now()->addDays(7);
            $placeholders['{deadline_day_ordinal}'] = $this->dayOrdinal((int) $deadlineBase->format('j'));
            $placeholders['{DEADLINE_DAY_ORDINAL}'] = $this->dayOrdinal((int) $deadlineBase->format('j'));
            $placeholders['{deadline_month}'] = $deadlineBase->format('F');
            $placeholders['{DEADLINE_MONTH}'] = $deadlineBase->format('F');
            $placeholders['{deadline_year}'] = $deadlineBase->format('Y');
            $placeholders['{DEADLINE_YEAR}'] = $deadlineBase->format('Y');
            $members = [];
            if (isset($formData['member_ids']) && is_array($formData['member_ids'])) {
                $ids = array_slice(array_filter($formData['member_ids']), 0, 25);
                if (! empty($ids)) {
                    $names = Resident::whereIn('id', $ids)->get()->pluck('full_name')->toArray();
                    $members = $names;
                }
            }
            if (! empty($formData['manual_members'])) {
                $manualLines = preg_split('/\r\n|\r|\n/', (string) $formData['manual_members']);
                $manualLines = array_filter(array_map(fn ($s) => trim($s), $manualLines), fn ($s) => $s !== '');
                $members = array_slice(array_merge($members, $manualLines), 0, 25);
            }
            for ($i = 1; $i <= 25; $i++) {
                $key = 'member_'.$i;
                $val = $formData[$key] ?? ($members[$i - 1] ?? '');
                $placeholders['{'.$key.'}'] = $val;
                $placeholders['{'.strtoupper($key).'}'] = $val;
            }
        }
        if ($formNumber === 2) {
            $issueDate = isset($formData['oath_date']) && $formData['oath_date']
                ? \Carbon\Carbon::parse($formData['oath_date'])
                : now();
            $placeholders['{date_issued}'] = $issueDate->format('F j, Y');
            $placeholders['{DATE_ISSUED}'] = $issueDate->format('F j, Y');
            $appointeeName = '';
            if (! empty($formData['appointee_id'])) {
                $app = Resident::find($formData['appointee_id']);
                if ($app) {
                    $appointeeName = $app->full_name ?? '';
                    if ($appointeeName === '' || $appointeeName === null) {
                        $parts = array_filter([
                            $app->first_name ?? '',
                            $app->middle_name ?? '',
                            $app->last_name ?? '',
                        ], fn ($s) => $s !== '');
                        $appointeeName = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
                        if (! empty($app->suffix)) {
                            $appointeeName .= ', '.$app->suffix;
                        }
                    }
                }
            }
            $placeholders['{resident_name}'] = $appointeeName;
            $placeholders['{RESIDENT_NAME}'] = $appointeeName;
            $placeholders['{resident _name}'] = $appointeeName;
            $placeholders['{RESIDENT _NAME}'] = $appointeeName;
        }
        if ($formNumber === 3) {
            $issueDate = isset($formData['date_issued']) ? \Carbon\Carbon::parse($formData['date_issued']) : (
                isset($formData['oath_date']) ? \Carbon\Carbon::parse($formData['oath_date']) : now()
            );
            $placeholders['{date_issued}'] = $issueDate->format('F j, Y');
            $placeholders['{DATE_ISSUED}'] = $issueDate->format('F j, Y');

            $appointeeName = '';
            $purokName = '';
            $barangay = 'Sto. Rosario';
            $city = 'Magallanes';
            $province = 'Agusan Del Norte';

            if (! empty($formData['appointee_id'])) {
                $app = Resident::find($formData['appointee_id']);
                if ($app) {
                    $appointeeName = $app->full_name ?? '';
                    if ($appointeeName === '' || $appointeeName === null) {
                        $parts = array_filter([
                            $app->first_name ?? '',
                            $app->middle_name ?? '',
                            $app->last_name ?? '',
                        ], fn ($s) => $s !== '');
                        $appointeeName = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
                        if (! empty($app->suffix)) {
                            $appointeeName .= ', '.$app->suffix;
                        }
                    }
                 $purokName = $app->purok ?? '';
                }
            }
            $placeholders['{resident_name}'] = $appointeeName;
            $placeholders['{RESIDENT_NAME}'] = $appointeeName;
            $placeholders['{resident _name}'] = $appointeeName;
            $placeholders['{RESIDENT _NAME}'] = $appointeeName;
            $placeholders['{purok}'] = $purokName;
            $placeholders['{PUROK}'] = $purokName;
            $placeholders['{barangay}'] = $barangay;
            $placeholders['{BARANGAY}'] = $barangay;
            $placeholders['{city}'] = $city;
            $placeholders['{CITY}'] = $city;
            $placeholders['{municipality}'] = $city;
            $placeholders['{MUNICIPALITY}'] = $city;
            $placeholders['{province}'] = $province;
            $placeholders['{PROVINCE}'] = $province;

            $oathDate = isset($formData['oath_date']) ? \Carbon\Carbon::parse($formData['oath_date']) : now();
            $placeholders['{oath_date}'] = $oathDate->format('F j, Y');
            $placeholders['{OATH_DATE}'] = $oathDate->format('F j, Y');

            $oathPlace = $formData['oath_place'] ?? ($formData['oath_venue'] ?? 'Barangay Hall, Purok 1, Brgy. Sto. Rosario, Magallanes, Agusan Del Norte');
            $placeholders['{oath_place}'] = $oathPlace;
            $placeholders['{OATH_PLACE}'] = $oathPlace;
        }
        if ($formNumber === 4) {
            $issueDate = isset($formData['record_date']) && $formData['record_date']
                ? \Carbon\Carbon::parse($formData['record_date'])
                : now();
            $placeholders['{date_issued}'] = $issueDate->format('F j, Y');
            $placeholders['{DATE_ISSUED}'] = $issueDate->format('F j, Y');
            $members = [];
            if (isset($formData['member_ids']) && is_array($formData['member_ids'])) {
                $ids = array_slice(array_filter($formData['member_ids']), 0, 20);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $members[] = $name;
                    }
                }
            }
            for ($i = 1; $i <= 20; $i++) {
                $key = 'member_'.$i;
                $val = $formData[$key] ?? ($members[$i - 1] ?? '');
                $placeholders['{'.$key.'}'] = $val;
                $placeholders['{'.strtoupper($key).'}'] = $val;
            }
        }
        if ($formNumber === 5) {
            $issueDate = isset($formData['oath_date']) && $formData['oath_date']
                ? \Carbon\Carbon::parse($formData['oath_date'])
                : now();
            $placeholders['{date_issued}'] = $issueDate->format('F j, Y');
            $placeholders['{DATE_ISSUED}'] = $issueDate->format('F j, Y');
            $placeholders['{oath_day ordinal}'] = $this->dayOrdinal((int) $issueDate->format('j'));
            $placeholders['{OATH_DAY ORDINAL}'] = $this->dayOrdinal((int) $issueDate->format('j'));
            $placeholders['{oath_month}'] = $issueDate->format('F');
            $placeholders['{OATH_MONTH}'] = $issueDate->format('F');
            $placeholders['{oath_year}'] = $issueDate->format('Y');
            $placeholders['{OATH_YEAR}'] = $issueDate->format('Y');
            $memberName = '';
            if (! empty($formData['member_id'])) {
                $m = Resident::find($formData['member_id']);
                if ($m) {
                    $memberName = $m->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $m->first_name ?? '',
                        $m->middle_name ?? '',
                        $m->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($m->suffix)) {
                        $memberName .= ', '.$m->suffix;
                    }
                }
            }
            $placeholders['{resident_name}'] = $memberName;
            $placeholders['{RESIDENT_NAME}'] = $memberName;
            $placeholders['{resident _name}'] = $memberName;
            $placeholders['{RESIDENT _NAME}'] = $memberName;
        }
        if ($formNumber === 6) {
            $issueDate = now();
            $placeholders['{date_issued}'] = $issueDate->format('F j, Y');
            $placeholders['{DATE_ISSUED}'] = $issueDate->format('F j, Y');
            $placeholders['{disqualification_reason}'] = (string) ($formData['reason'] ?? '');
            $placeholders['{DISQUALIFICATION_REASON}'] = (string) ($formData['reason'] ?? '');
            $key = 'incapacity_example_line1';
            $val = (string) ($formData[$key] ?? '');
            $placeholders['{'.$key.'}'] = $val;
            $placeholders['{'.strtoupper($key).'}'] = $val;
            $placeholders['{incapacity_example_line2}'] = '';
            $placeholders['{INCAPACITY_EXAMPLE_LINE2}'] = '';
            $placeholders['{incapacity_example_line3}'] = '';
            $placeholders['{INCAPACITY_EXAMPLE_LINE3}'] = '';
            $memberName = '';
            if (! empty($formData['member_id'])) {
                $m = Resident::find($formData['member_id']);
                if ($m) {
                    $memberName = $m->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $m->first_name ?? '',
                        $m->middle_name ?? '',
                        $m->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($m->suffix)) {
                        $memberName .= ', '.$m->suffix;
                    }
                }
            }
            $placeholders['{resident_name}'] = $memberName;
            $placeholders['{RESIDENT_NAME}'] = $memberName;
            $placeholders['{resident _name}'] = $memberName;
            $placeholders['{RESIDENT _NAME}'] = $memberName;
            // Witness approvals 1–16
            $approvals = [];
            if (isset($formData['witness_ids']) && is_array($formData['witness_ids'])) {
                $ids = array_slice(array_filter($formData['witness_ids']), 0, 16);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $approvals[] = $name;
                    }
                }
            }
            for ($i = 1; $i <= 16; $i++) {
                $key = 'approval_'.$i;
                $val = $formData[$key] ?? ($approvals[$i - 1] ?? '');
                $placeholders['{'.$key.'}'] = $val;
                $placeholders['{'.strtoupper($key).'}'] = $val;
            }
            $recv = isset($formData['received_date']) && $formData['received_date']
                ? \Carbon\Carbon::parse($formData['received_date'])
                : $issueDate;
            $placeholders['{received_day_ordinal}'] = $this->dayOrdinal((int) $recv->format('j'));
            $placeholders['{RECEIVED_DAY_ORDINAL}'] = $this->dayOrdinal((int) $recv->format('j'));
            $placeholders['{received_month}'] = $recv->format('F');
            $placeholders['{RECEIVED_MONTH}'] = $recv->format('F');
            $placeholders['{received_year}'] = $recv->format('Y');
            $placeholders['{RECEIVED_YEAR}'] = $recv->format('Y');
        }
        if ($formNumber === 7) {
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('compliant_'.$i).'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('complainant_'.$i).'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('respondent_'.$i).'}'] = $val;
                $placeholders['{respondent _'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('respondent _'.$i).'}'] = $val;
            }
            $subject = (string) ($formData['violation'] ?? ($formData['case_subject'] ?? ($blotter->incident_type ?? '')));
            $placeholders['{case_subject}'] = $subject;
            $placeholders['{CASE_SUBJECT}'] = $subject;
            $placeholders['{case subject}'] = $subject;
            $placeholders['{CASE SUBJECT}'] = $subject;
            $placeholders['{case-subject}'] = $subject;
            $placeholders['{CASE-SUBJECT}'] = $subject;
            $placeholders['{case subject}'] = $subject;
            $placeholders['{CASE SUBJECT}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($formData['incident_details'] ?? ($blotter->incident_details ?? '')));
            $placeholders['{case_description}'] = $desc;
            $placeholders['{CASE_DESCRIPTION}'] = $desc;
            $placeholders['{case description}'] = $desc;
            $placeholders['{CASE DESCRIPTION}'] = $desc;
            $placeholders['{complaint_facts}'] = $desc;
            $placeholders['{COMPLAINT_FACTS}'] = $desc;
            $placeholders['{relief_requested}'] = (string) ($formData['relief_requested'] ?? '');
            $placeholders['{RELIEF_REQUESTED}'] = (string) ($formData['relief_requested'] ?? '');
            $recv = isset($formData['received_date']) && $formData['received_date']
                ? \Carbon\Carbon::parse($formData['received_date'])
                : now();
            $recvDayOrd = $this->dayOrdinal((int) $recv->format('j'));
            $recvMonth = $recv->format('F');
            $recvYear = $recv->format('Y');
            $placeholders['{received_day_ordinal}'] = $recvDayOrd;
            $placeholders['{RECEIVED_DAY_ORDINAL}'] = $recvDayOrd;
            $placeholders['{received_month}'] = $recvMonth;
            $placeholders['{RECEIVED_MONTH}'] = $recvMonth;
            $placeholders['{received_year}'] = $recvYear;
            $placeholders['{RECEIVED_YEAR}'] = $recvYear;
            $placeholders['{recieve_day_ordinal}'] = $recvDayOrd;
            $placeholders['{RECIEVE_DAY_ORDINAL}'] = $recvDayOrd;
            $placeholders['{recieve_month}'] = $recvMonth;
            $placeholders['{RECIEVE_MONTH}'] = $recvMonth;
            $placeholders['{recieve_month_ordinal}'] = $recvMonth;
            $placeholders['{RECIEVE_MONTH_ORDINAL}'] = $recvMonth;
            $placeholders['{recieve_year}'] = $recvYear;
            $placeholders['{RECIEVE_YEAR}'] = $recvYear;
            $placeholders['{recieve_year_ordinal}'] = $recvYear;
            $placeholders['{RECIEVE_YEAR_ORDINAL}'] = $recvYear;
            $exec = isset($formData['execution_date']) && $formData['execution_date']
                ? \Carbon\Carbon::parse($formData['execution_date'])
                : now();
            $placeholders['{execution_day}'] = $this->dayOrdinal((int) $exec->format('j'));
            $placeholders['{EXECUTION_DAY}'] = $this->dayOrdinal((int) $exec->format('j'));
            $placeholders['{execution_month}'] = $exec->format('F');
            $placeholders['{EXECUTION_MONTH}'] = $exec->format('F');
            $placeholders['{execution_year}'] = $exec->format('Y');
            $placeholders['{EXECUTION_YEAR}'] = $exec->format('Y');
        }
        if ($formNumber === 11) {
           $placeholders['{case_number}'] = (string) ($formData['case_number'] ?? $formData['case_reference'] ?? $blotter->blotter_id ?? '');
$placeholders['{CASE_NUMBER}'] = (string) ($formData['case_number'] ?? $formData['case_reference'] ?? $blotter->blotter_id ?? '');
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ($blotter->incident_details ?? '')));
            $placeholders['{case_subject}'] = $subject;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            $memberName = '';
            if (! empty($formData['member_id'])) {
                $m = Resident::find($formData['member_id']);
                if ($m) {
                    $memberName = $m->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $m->first_name ?? '',
                        $m->middle_name ?? '',
                        $m->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($m->suffix)) {
                        $memberName .= ', '.$m->suffix;
                    }
                }
            }
            $placeholders['{member_name}'] = $memberName;
            $issued = isset($formData['issued_date']) && $formData['issued_date']
                ? \Carbon\Carbon::parse($formData['issued_date'])
                : (isset($formData['selection_date']) && $formData['selection_date'] ? \Carbon\Carbon::parse($formData['selection_date']) : now());
            $placeholders['{issued_date}'] = $issued->format('F j, Y');
            if (isset($formData['received_date']) && $formData['received_date']) {
                $recv = \Carbon\Carbon::parse($formData['received_date']);
                $placeholders['{received_day}'] = $recv->format('j');
                $placeholders['{received_month}'] = $recv->format('F');
                $placeholders['{received_year}'] = $recv->format('Y');
            }
        }
        if ($formNumber === 12) {
           $placeholders['{case_number}'] = (string) ($formData['case_number'] ?? $formData['case_reference'] ?? $blotter->blotter_id ?? '');
$placeholders['{CASE_NUMBER}'] = (string) ($formData['case_number'] ?? $formData['case_reference'] ?? $blotter->blotter_id ?? '');
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            $concDate = null;
            if (! empty($formData['conciliation_date'])) {
                $concDate = \Carbon\Carbon::parse($formData['conciliation_date']);
            } elseif (! empty($formData['hearing_date'])) {
                $concDate = \Carbon\Carbon::parse($formData['hearing_date']);
            }
            if ($concDate) {
                $placeholders['{conciliation_day}'] = $concDate->format('j');
                $placeholders['{conciliation_month}'] = $concDate->format('F');
                $placeholders['{conciliation_year}'] = $concDate->format('Y');
            }
            $placeholders['{conciliation_time}'] = (string) ($formData['conciliation_time'] ?? ($formData['hearing_time'] ?? ''));
            if (! empty($formData['issued_date'])) {
                $id = \Carbon\Carbon::parse($formData['issued_date']);
                $placeholders['{issued_day}'] = $id->format('j');
                $placeholders['{issued_month}'] = $id->format('F');
                $placeholders['{issued_year}'] = $id->format('Y');
            }
            if (! empty($formData['served_date'])) {
                $sd = \Carbon\Carbon::parse($formData['served_date']);
                $placeholders['{served_day}'] = $sd->format('j');
                $placeholders['{served_month}'] = $sd->format('F');
                $placeholders['{served_year}'] = $sd->format('Y');
            }
        }
        if ($formNumber === 13) {
            $placeholders['{case_number}'] = (string) ($formData['case_number'] ?? $formData['case_reference'] ?? $blotter->blotter_id ?? '');
$placeholders['{CASE_NUMBER}'] = (string) ($formData['case_number'] ?? $formData['case_reference'] ?? $blotter->blotter_id ?? '');
            
            $subject = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $subject;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{complainant_'.$i.'}'] = $val;
                $placeholders['{compliant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            // Witness placeholders 1..8
            $witnesses = [];
            if (isset($formData['witness_ids']) && is_array($formData['witness_ids'])) {
                $ids = array_slice(array_filter($formData['witness_ids']), 0, 8);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $w) {
                        $name = $w->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $w->first_name ?? '',
                            $w->middle_name ?? '',
                            $w->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($w->suffix)) {
                            $name .= ', '.$w->suffix;
                        }
                        $witnesses[] = $name;
                    }
                }
            }
            for ($i = 1; $i <= 8; $i++) {
                $val = $witnesses[$i - 1] ?? '';
                $placeholders['{witness_'.$i.'_name}'] = $val;
                $placeholders['{'.strtoupper('witness_'.$i.'_name').'}'] = $val;
            }
            // Appearance date/time
            $app = isset($formData['appearance_date']) && $formData['appearance_date']
                ? \Carbon\Carbon::parse($formData['appearance_date'])
                : now();
            $placeholders['{appearance_day}'] = $app->format('j');
            $placeholders['{appearance_month}'] = $app->format('F');
            $placeholders['{appearance_year}'] = $app->format('Y');
            $placeholders['{appearance_time}'] = (string) ($formData['appearance_time'] ?? '');
            // Issued date
            if (! empty($formData['issued_date'])) {
                $id = \Carbon\Carbon::parse($formData['issued_date']);
                $placeholders['{issued_day}'] = $id->format('j');
                $placeholders['{issued_month}'] = $id->format('F');
                $placeholders['{issued_year}'] = $id->format('Y');
            }
        }
        if ($formNumber === 14) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $placeholders['{'.strtoupper('case_subject').'}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $placeholders['{'.strtoupper('case_description').'}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('compliant_'.$i).'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('complainant_'.$i).'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('respondent_'.$i).'}'] = $val;
            }
            $agree = isset($formData['settlement_date']) && $formData['settlement_date']
                ? \Carbon\Carbon::parse($formData['settlement_date'])
                : now();
            $placeholders['{agreement_day}'] = $agree->format('j');
            $placeholders['{'.strtoupper('agreement_day').'}'] = $agree->format('j');
            $placeholders['{agreement_month}'] = $agree->format('F');
            $placeholders['{'.strtoupper('agreement_month').'}'] = $agree->format('F');
            $placeholders['{agreement_year}'] = $agree->format('Y');
            $placeholders['{'.strtoupper('agreement_year').'}'] = $agree->format('Y');
        }
        if ($formNumber === 15) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $award = (string) ($formData['conciliation_award_details'] ?? ($formData['decision'] ?? ''));
            $placeholders['{conciliation_award_details}'] = $award;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['decision_date'])) {
                $dd = \Carbon\Carbon::parse($formData['decision_date']);
                $placeholders['{decision_day}'] = $dd->format('j');
                $placeholders['{decision_month}'] = $dd->format('F');
                $placeholders['{decision_year}'] = $dd->format('Y');
            }
        }
        if ($formNumber === 16) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $details = (string) ($formData['amicable_settlement_details'] ?? ($formData['settlement_terms'] ?? ''));
            $placeholders['{amicable_settlement_details}'] = $details;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['delivery_date'])) {
                $dd = \Carbon\Carbon::parse($formData['delivery_date']);
                $placeholders['{delivery_day}'] = $dd->format('j');
                $placeholders['{delivery_month}'] = $dd->format('F');
                $placeholders['{delivery_year}'] = $dd->format('Y');
            }
        }
        if ($formNumber === 17) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $placeholders['{reason_fraud}'] = (string) ($formData['reason_fraud'] ?? '');
            $placeholders['{reason_coercion}'] = (string) ($formData['reason_coercion'] ?? '');
            $placeholders['{reason_threat}'] = (string) ($formData['reason_threat'] ?? '');
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name += ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            $placeholders['{sworn_place}'] = (string) ($formData['sworn_place'] ?? '');
            if (! empty($formData['sworn_date'])) {
                $sd = \Carbon\Carbon::parse($formData['sworn_date']);
                $placeholders['{sworn_day}'] = $sd->format('j');
                $placeholders['{sworn_month}'] = $sd->format('F');
                $placeholders['{sworn_year}'] = $sd->format('Y');
            }
            if (! empty($formData['received_date'])) {
                $rd = \Carbon\Carbon::parse($formData['received_date']);
                $placeholders['{received_day}'] = $rd->format('j');
                $placeholders['{received_month}'] = $rd->format('F');
                $placeholders['{received_year}'] = $rd->format('Y');
            }
        }
        if ($formNumber === 18) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
                $placeholders['{respondent _'.$i.'}'] = $val;
            }
            if (! empty($formData['hearing_date'])) {
                $hd = \Carbon\Carbon::parse($formData['hearing_date']);
                $placeholders['{hearing_day}'] = $hd->format('j');
                $placeholders['{hearing_month}'] = $hd->format('F');
                $placeholders['{hearing_year}'] = $hd->format('Y');
            }
            $placeholders['{hearing_time}'] = (string) ($formData['hearing_time'] ?? '');
            if (! empty($formData['issued_date'])) {
                $id = \Carbon\Carbon::parse($formData['issued_date']);
                $placeholders['{issued_day}'] = $id->format('j');
                $placeholders['{issued_month}'] = $id->format('F');
                $placeholders['{issued_year}'] = $id->format('Y');
            }
            if (! empty($formData['original_appointment_date'])) {
                $od = \Carbon\Carbon::parse($formData['original_appointment_date']);
                $placeholders['{original_appointment_date}'] = $od->format('F j, Y');
            }
            if (! empty($formData['delivered_date'])) {
                $dd = \Carbon\Carbon::parse($formData['delivered_date']);
                $placeholders['{delivered_day}'] = $dd->format('j');
                $placeholders['{delivered_month}'] = $dd->format('F');
                $placeholders['{delivered_year}'] = $dd->format('Y');
            }
        }
        if ($formNumber === 19) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
                $placeholders['{respondent _'.$i.'}'] = $val;
            }
            if (! empty($formData['hearing_date'])) {
                $hd = \Carbon\Carbon::parse($formData['hearing_date']);
                $placeholders['{hearing_day}'] = $hd->format('j');
                $placeholders['{hearing_month}'] = $hd->format('F');
                $placeholders['{hearing_year}'] = $hd->format('Y');
            }
            $placeholders['{hearing_time}'] = (string) ($formData['hearing_time'] ?? '');
            if (! empty($formData['issued_date'])) {
                $id = \Carbon\Carbon::parse($formData['issued_date']);
                $placeholders['{issued_day}'] = $id->format('j');
                $placeholders['{issued_month}'] = $id->format('F');
                $placeholders['{issued_year}'] = $id->format('Y');
            }
            if (! empty($formData['original_appointment_date'])) {
                $od = \Carbon\Carbon::parse($formData['original_appointment_date']);
                $placeholders['{original_appointment_date}'] = $od->format('F j, Y');
            }
            if (! empty($formData['delivered_date'])) {
                $dd = \Carbon\Carbon::parse($formData['delivered_date']);
                $placeholders['{delivered_day}'] = $dd->format('j');
                $placeholders['{delivered_month}'] = $dd->format('F');
                $placeholders['{delivered_year}'] = $dd->format('Y');
            }
        }
        if ($formNumber === 20) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $placeholders['{official_name}'] = (string) ($formData['official_name'] ?? '');
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['certification_date'])) {
                $cd = \Carbon\Carbon::parse($formData['certification_date']);
                $placeholders['{cert_day}'] = $cd->format('j');
                $placeholders['{cert_month}'] = $cd->format('F');
                $placeholders['{cert_year}'] = $cd->format('Y');
            }
        }
        if ($formNumber === 22) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['certification_date'])) {
                $cd = \Carbon\Carbon::parse($formData['certification_date']);
                $placeholders['{cert_day}'] = $cd->format('j');
                $placeholders['{cert_month}'] = $cd->format('F');
                $placeholders['{cert_,month}'] = $cd->format('F');
                $placeholders['{cert_year}'] = $cd->format('Y');
            }
        }
        if ($formNumber === 23) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['arbitration_date'])) {
                $ad = \Carbon\Carbon::parse($formData['arbitration_date']);
                $placeholders['{arbitration_day}'] = $ad->format('j');
                $placeholders['{arbitration_month}'] = $ad->format('F');
                $placeholders['{arbitration_year }'] = $ad->format('Y');
            }
            $placeholders['{writ_type}'] = (string) ($formData['writ_type'] ?? '');
            if (! empty($formData['date_issued'])) {
                $di = \Carbon\Carbon::parse($formData['date_issued']);
                $placeholders['{date_issued}'] = $di->format('F j, Y');
            }
        }
        if ($formNumber === 24) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            for ($i = 1; $i <= 3; $i++) {
                $placeholders['{complainant_'.$i.'}'] = $complainants[$i - 1] ?? '';
            }
            if (! empty($formData['hearing_date'])) {
                $hd = \Carbon\Carbon::parse($formData['hearing_date']);
                $placeholders['{hearing_day}'] = $hd->format('j');
                $placeholders['{hearing_month}'] = $hd->format('F');
                $placeholders['{hearing_year}'] = $hd->format('Y');
            }
            $placeholders['{hearing_time}'] = (string) ($formData['hearing_time'] ?? '');
            $placeholders['{filing_party}'] = (string) ($formData['filing_party'] ?? '');
            if (! empty($formData['issued_date'])) {
                $id = \Carbon\Carbon::parse($formData['issued_date']);
                $placeholders['{issued_date}'] = $id->format('F j, Y');
            }
            if (! empty($formData['delivered_date'])) {
                $dd = \Carbon\Carbon::parse($formData['delivered_date']);
                $placeholders['{delivered_day}'] = $dd->format('j');
                $placeholders['{delivered_month}'] = $dd->format('F');
                $placeholders['{delivered_year}'] = $dd->format('Y');
            }
        }
        if ($formNumber === 25) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            $placeholders['{respondent_name}'] = $respondents[0] ?? '';
            if (! empty($formData['agreement_date'])) {
                $ag = \Carbon\Carbon::parse($formData['agreement_date']);
                $placeholders['{agreement_date}'] = $ag->format('F j, Y');
            }
            $placeholders['{award_terms}'] = (string) ($formData['award_terms'] ?? '');
            $placeholders['{amount}'] = (string) ($formData['amount'] ?? '');
            if (! empty($formData['issued_date'])) {
                $id = \Carbon\Carbon::parse($formData['issued_date']);
                $placeholders['{issued_day}'] = $id->format('j');
                $placeholders['{issued_month}'] = $id->format('F');
                $placeholders['{issued_year}'] = $id->format('Y');
            }
        }
        if ($formNumber === 21) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $placeholders['{basis_for_bar}'] = (string) ($formData['basis_for_bar'] ?? '');
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['certification_date'])) {
                $cd = \Carbon\Carbon::parse($formData['certification_date']);
                $placeholders['{cert_day}'] = $cd->format('j');
                $placeholders['{cert_month}'] = $cd->format('F');
                $placeholders['{cert_,month}'] = $cd->format('F');
                $placeholders['{cert_year}'] = $cd->format('Y');
            }
        }
        if ($formNumber === 8) {
            $names = [];
            if (isset($formData['id']) && is_array($formData['id'])) {
                $ids = array_slice(array_filter($formData['id']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $c) {
                        $name = $c->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $c->first_name ?? '',
                            $c->middle_name ?? '',
                            $c->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($c->suffix)) {
                            $name .= ', '.$c->suffix;
                        }
                        $names[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $c = Resident::find($blotter->complainant_id);
                if ($c) {
                    $name = $c->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $c->first_name ?? '',
                        $c->middle_name ?? '',
                        $c->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($c->suffix)) {
                        $name .= ', '.$c->suffix;
                    }
                    $names[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $names[$i - 1] ?? '';
                $placeholders['{resident_name'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('resident_name'.$i).'}'] = $val;
                $placeholders['{resident _name'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('resident _name'.$i).'}'] = $val;
            }
            $hearing = isset($formData['hearing_date']) && $formData['hearing_date']
                ? \Carbon\Carbon::parse($formData['hearing_date'])
                : now();
            $placeholders['{hearing_day_ordinal}'] = $this->dayOrdinal((int) $hearing->format('j'));
            $placeholders['{HEARING_DAY_ORDINAL}'] = $this->dayOrdinal((int) $hearing->format('j'));
            $placeholders['{hearing_month}'] = $hearing->format('F');
            $placeholders['{HEARING_MONTH}'] = $hearing->format('F');
            $placeholders['{hearing_year}'] = $hearing->format('Y');
            $placeholders['{HEARING_YEAR}'] = $hearing->format('Y');
            $placeholders['{hearing_time}'] = (string) ($formData['hearing_time'] ?? '');
            $placeholders['{HEARING_TIME}'] = (string) ($formData['hearing_time'] ?? '');
            $placeholders['{hearing_place}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{HEARING_PLACE}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{hearing_venue}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{HEARING_VENUE}'] = (string) ($formData['hearing_venue'] ?? '');
            $deliv = isset($formData['delivered_date']) && $formData['delivered_date']
                ? \Carbon\Carbon::parse($formData['delivered_date'])
                : $hearing;
            $placeholders['{delivered_day_ordinal}'] = $this->dayOrdinal((int) $deliv->format('j'));
            $placeholders['{DELIVERED_DAY_ORDINAL}'] = $this->dayOrdinal((int) $deliv->format('j'));
            $placeholders['{delivered_month}'] = $deliv->format('F');
            $placeholders['{DELIVERED_MONTH}'] = $deliv->format('F');
            $placeholders['{delivered_year}'] = $deliv->format('Y');
            $placeholders['{DELIVERED_YEAR}'] = $deliv->format('Y');
            $ack = isset($formData['ack_date']) && $formData['ack_date']
                ? \Carbon\Carbon::parse($formData['ack_date'])
                : $deliv;
            $placeholders['{ack_day_ordinal}'] = $this->dayOrdinal((int) $ack->format('j'));
            $placeholders['{ACK_DAY_ORDINAL}'] = $this->dayOrdinal((int) $ack->format('j'));
            $placeholders['{ack_month}'] = $ack->format('F');
            $placeholders['{ACK_MONTH}'] = $ack->format('F');
            $placeholders['{ack_year}'] = $ack->format('Y');
            $placeholders['{ACK_YEAR}'] = $ack->format('Y');
        }
        if ($formNumber === 9) {
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('compliant_'.$i).'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('complainant_'.$i).'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 4);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 4; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('respondent_'.$i).'}'] = $val;
                $placeholders['{respondent _'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('respondent _'.$i).'}'] = $val;
            }
            $subject = (string) ($formData['case_subject'] ?? ($formData['violation'] ?? ($blotter->incident_type ?? '')));
            $placeholders['{case_subject}'] = $subject;
            $placeholders['{CASE_SUBJECT}'] = $subject;
            $placeholders['{case subject}'] = $subject;
            $placeholders['{CASE SUBJECT}'] = $subject;
            $placeholders['{case-subject}'] = $subject;
            $placeholders['{CASE-SUBJECT}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($formData['incident_details'] ?? ($blotter->incident_details ?? '')));
            $placeholders['{case_description}'] = $desc;
            $placeholders['{CASE_DESCRIPTION}'] = $desc;
            $placeholders['{case description}'] = $desc;
            $placeholders['{CASE DESCRIPTION}'] = $desc;
            $placeholders['{case-description}'] = $desc;
            $placeholders['{CASE-DESCRIPTION}'] = $desc;
            $placeholders['{casae_description}'] = $desc;
            $placeholders['{CASAE_DESCRIPTION}'] = $desc;
            $placeholders['{complaint_facts}'] = $desc;
            $placeholders['{COMPLAINT_FACTS}'] = $desc;
            $hearing = isset($formData['hearing_date']) && $formData['hearing_date']
                ? \Carbon\Carbon::parse($formData['hearing_date'])
                : now();
            $placeholders['{hearing_day}'] = $hearing->format('j');
            $placeholders['{HEARING_DAY}'] = $hearing->format('j');
            $placeholders['{hearing_month}'] = $hearing->format('F');
            $placeholders['{HEARING_MONTH}'] = $hearing->format('F');
            $placeholders['{hearing_year}'] = $hearing->format('Y');
            $placeholders['{HEARING_YEAR}'] = $hearing->format('Y');
            $placeholders['{hearing_time}'] = (string) ($formData['hearing_time'] ?? '');
            $placeholders['{HEARING_TIME}'] = (string) ($formData['hearing_time'] ?? '');
            $issued = isset($formData['issued_date']) && $formData['issued_date']
                ? \Carbon\Carbon::parse($formData['issued_date'])
                : now();
            $placeholders['{issued_day}'] = $issued->format('j');
            $placeholders['{ISSUED_DAY}'] = $issued->format('j');
            $placeholders['{issued_month}'] = $issued->format('F');
            $placeholders['{ISSUED_MONTH}'] = $issued->format('F');
            $placeholders['{issued_year}'] = $issued->format('Y');
            $placeholders['{ISSUED_YEAR}'] = $issued->format('Y');
        }
        $rawFormLower = strtolower((string) ($blotter->form_id ?? $blotter->form_number));
        if (str_contains($rawFormLower, '9-2') || str_contains($rawFormLower, 'page 2')) {
            $names = [];
            $ids = [];
            if (! empty($formData['respondent_id_1'])) {
                $ids[] = $formData['respondent_id_1'];
            }
            if (! empty($formData['respondent_id_2'])) {
                $ids[] = $formData['respondent_id_2'];
            }
            if (empty($ids) && isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 2);
            }
            if (empty($ids) && ! empty($blotter->respondent_id)) {
                $ids = [$blotter->respondent_id];
            }
            if (! empty($ids)) {
                $rows = Resident::whereIn('id', $ids)->get();
                foreach ($rows as $r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $names[] = $name;
                }
            }
            $placeholders['{respondent_1_name}'] = $names[0] ?? '';
            $placeholders['{RESPONDENT_1_NAME}'] = $names[0] ?? '';
            $placeholders['{respondent_2_name}'] = $names[1] ?? '';
            $placeholders['{RESPONDENT_2_NAME}'] = $names[1] ?? '';
            $s1 = isset($formData['summon1_date']) && $formData['summon1_date'] ? \Carbon\Carbon::parse($formData['summon1_date']) : null;
            $s2 = isset($formData['summon2_date']) && $formData['summon2_date'] ? \Carbon\Carbon::parse($formData['summon2_date']) : null;
            if ($s1) {
                $placeholders['{summon1_day_ordinal}'] = $this->dayOrdinal((int) $s1->format('j'));
                $placeholders['{SUMMON1_DAY_ORDINAL}'] = $this->dayOrdinal((int) $s1->format('j'));
                $placeholders['{summon1_month}'] = $s1->format('F');
                $placeholders['{SUMMON1_MONTH}'] = $s1->format('F');
                $placeholders['{summon1_year}'] = $s1->format('Y');
                $placeholders['{SUMMON1_YEAR}'] = $s1->format('Y');
            }
            if ($s2) {
                $placeholders['{summon2_day_ordinal}'] = $this->dayOrdinal((int) $s2->format('j'));
                $placeholders['{SUMMON2_DAY_ORDINAL}'] = $this->dayOrdinal((int) $s2->format('j'));
                $placeholders['{summon2_month}'] = $s2->format('F');
                $placeholders['{SUMMON2_MONTH}'] = $s2->format('F');
                $placeholders['{summon2_year}'] = $s2->format('Y');
                $placeholders['{SUMMON2_YEAR}'] = $s2->format('Y');
            }
            $placeholders['{hearing_place}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{HEARING_PLACE}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{hearing_venue}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{HEARING_VENUE}'] = (string) ($formData['hearing_venue'] ?? '');
            for ($i = 1; $i <= 4; $i++) {
                $key = 'summon_result_'.$i;
                $val = (string) ($formData[$key] ?? '');
                if ($i === 4 && $val === '') {
                    $val = (string) ($formData['summon_result_3'] ?? '');
                }
                $placeholders['{'.$key.'}'] = $val;
                $placeholders['{'.strtoupper($key).'}'] = $val;
            }
            if (! empty($formData['recipient_date_1'])) {
                $rd = \Carbon\Carbon::parse($formData['recipient_date_1']);
                $placeholders['{recipient_date_1}'] = $rd->format('F j, Y');
                $placeholders['{RECIPIENT_DATE_1}'] = $rd->format('F j, Y');
            }
        }
        if (str_contains($rawFormLower, '9-a') || str_contains($rawFormLower, '9a')) {
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 4);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 4; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('compliant_'.$i).'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('complainant_'.$i).'}'] = $val;
                $placeholders['{compliant _'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('compliant _'.$i).'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 4);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 4; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('respondent_'.$i).'}'] = $val;
                $placeholders['{respondent _'.$i.'}'] = $val;
                $placeholders['{'.strtoupper('respondent _'.$i).'}'] = $val;
            }
            $subject = (string) ($formData['case_subject'] ?? ($formData['violation'] ?? ($blotter->incident_type ?? '')));
            $placeholders['{case_subject}'] = $subject;
            $placeholders['{CASE_SUBJECT}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($formData['incident_details'] ?? ($blotter->incident_details ?? '')));
            $placeholders['{case_description}'] = $desc;
            $placeholders['{CASE_DESCRIPTION}'] = $desc;
            $hearing = isset($formData['hearing_date']) && $formData['hearing_date']
                ? \Carbon\Carbon::parse($formData['hearing_date'])
                : now();
            $placeholders['{hearing_day}'] = $hearing->format('j');
            $placeholders['{HEARING_DAY}'] = $hearing->format('j');
            $placeholders['{hearing_month}'] = $hearing->format('F');
            $placeholders['{HEARING_MONTH}'] = $hearing->format('F');
            $placeholders['{hearing_year}'] = $hearing->format('Y');
            $placeholders['{HEARING_YEAR}'] = $hearing->format('Y');
            $placeholders['{hearing_time}'] = (string) ($formData['hearing_time'] ?? '');
            $placeholders['{HEARING_TIME}'] = (string) ($formData['hearing_time'] ?? '');
            $placeholders['{hearing_place}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{HEARING_PLACE}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{hearing_venue}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{HEARING_VENUE}'] = (string) ($formData['hearing_venue'] ?? '');
            $issued = isset($formData['issued_date']) && $formData['issued_date']
                ? \Carbon\Carbon::parse($formData['issued_date'])
                : now();
            $placeholders['{issued_day}'] = $issued->format('j');
            $placeholders['{ISSUED_DAY}'] = $issued->format('j');
            $placeholders['{issued_month}'] = $issued->format('F');
            $placeholders['{ISSUED_MONTH}'] = $issued->format('F');
            $placeholders['{issued_year}'] = $issued->format('Y');
            $placeholders['{ISSUED_YEAR}'] = $issued->format('Y');
        }
        if ($this->formId($blotter) === 10) {
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 4);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 4);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 4; $i++) {
                $cval = $complainants[$i - 1] ?? '';
                $rval = $respondents[$i - 1] ?? '';
                $placeholders['{complainant_'.$i.'}'] = $cval;
                $placeholders['{'.strtoupper('complainant_'.$i).'}'] = $cval;
                $placeholders['{ complainant_'.$i.'}'] = $cval;
                $placeholders['{'.strtoupper(' complainant_'.$i).'}'] = $cval;
                $placeholders['{respondent_'.$i.'}'] = $rval;
                $placeholders['{'.strtoupper('respondent_'.$i).'}'] = $rval;
                $placeholders['{respondent _'.$i.'}'] = $rval;
                $placeholders['{'.strtoupper('respondent _'.$i).'}'] = $rval;
            }
            if (! empty($formData['formation_date'])) {
                $fd = \Carbon\Carbon::parse($formData['formation_date']);
                $placeholders['{formation_day_ordinal}'] = $this->dayOrdinal((int) $fd->format('j'));
                $placeholders['{FORMATION_DAY_ORDINAL}'] = $this->dayOrdinal((int) $fd->format('j'));
                $placeholders['{formation_month}'] = $fd->format('F');
                $placeholders['{FORMATION_MONTH}'] = $fd->format('F');
                $placeholders['{formation_year}'] = $fd->format('Y');
                $placeholders['{FORMATION_YEAR}'] = $fd->format('Y');
            }
            $placeholders['{formation_time}'] = (string) ($formData['formation_time'] ?? '');
            $placeholders['{FORMATION_TIME}'] = (string) ($formData['formation_time'] ?? '');
            $placeholders['{hearing_place}'] = (string) ($formData['hearing_venue'] ?? '');
            $placeholders['{HEARING_PLACE}'] = (string) ($formData['hearing_venue'] ?? '');
            if (! empty($formData['issued_date'])) {
                $idc = \Carbon\Carbon::parse($formData['issued_date']);
                $placeholders['{issued_day}'] = $idc->format('j');
                $placeholders['{ISSUED_DAY}'] = $idc->format('j');
                $placeholders['{issued_month}'] = $idc->format('F');
                $placeholders['{ISSUED_MONTH}'] = $idc->format('F');
                $placeholders['{issued_year}'] = $idc->format('Y');
                $placeholders['{ISSUED_YEAR}'] = $idc->format('Y');
            }
            if (! empty($formData['deadline_date'])) {
                $ddc = \Carbon\Carbon::parse($formData['deadline_date']);
                $placeholders['{deadline_day}'] = $ddc->format('j');
                $placeholders['{DEADLINE_DAY}'] = $ddc->format('j');
                $placeholders['{deadline_month}'] = $ddc->format('F');
                $placeholders['{DEADLINE_MONTH}'] = $ddc->format('F');
                $placeholders['{deadline_year}'] = $ddc->format('Y');
                $placeholders['{DEADLINE_YEAR}'] = $ddc->format('Y');
            }
        }
        if (str_contains($rawFormLower, 'dismissal-order') || str_contains($rawFormLower, 'dismissal order')) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['issued_date'])) {
                $id = \Carbon\Carbon::parse($formData['issued_date']);
                $placeholders['{issued_day}'] = $id->format('j');
                $placeholders['{issued_month}'] = $id->format('F');
                $placeholders['{issued_year}'] = $id->format('Y');
            }
        }
        if (str_contains($rawFormLower, 'minutes-of-proceedings') || str_contains($rawFormLower, 'minutes of proceedings')) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['date'])) {
                $dt = \Carbon\Carbon::parse($formData['date']);
                $placeholders['{date}'] = $dt->format('F j, Y');
            }
            $placeholders['{time}'] = (string) ($formData['time'] ?? '');
            $placeholders['{mediation_summary}'] = (string) ($formData['mediation_summary'] ?? '');
        }
        if (str_contains($rawFormLower, 'case-record') || str_contains($rawFormLower, 'case record')) {
            $subject = (string) ($formData['case_subject'] ?? ($blotter->incident_type ?? ''));
            $placeholders['{case_subject}'] = $subject;
            $desc = (string) ($formData['case_description'] ?? ($blotter->incident_details ?? ''));
            $placeholders['{case_description}'] = $desc;
            $complainants = [];
            if (isset($formData['complainant_ids']) && is_array($formData['complainant_ids'])) {
                $ids = array_slice(array_filter($formData['complainant_ids']), 0, 3);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $complainants[] = $name;
                    }
                }
            } elseif (! empty($blotter->complainant_id)) {
                $r = Resident::find($blotter->complainant_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $complainants[] = $name;
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $val = $complainants[$i - 1] ?? '';
                $placeholders['{compliant_'.$i.'}'] = $val;
                $placeholders['{complainant_'.$i.'}'] = $val;
            }
            $respondents = [];
            if (isset($formData['respondent_ids']) && is_array($formData['respondent_ids'])) {
                $ids = array_slice(array_filter($formData['respondent_ids']), 0, 2);
                if (! empty($ids)) {
                    $rows = Resident::whereIn('id', $ids)->get();
                    foreach ($rows as $r) {
                        $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                            $r->first_name ?? '',
                            $r->middle_name ?? '',
                            $r->last_name ?? '',
                        ], fn ($s) => $s !== ''))));
                        if (! empty($r->suffix)) {
                            $name .= ', '.$r->suffix;
                        }
                        $respondents[] = $name;
                    }
                }
            } elseif (! empty($blotter->respondent_id)) {
                $r = Resident::find($blotter->respondent_id);
                if ($r) {
                    $name = $r->full_name ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                        $r->first_name ?? '',
                        $r->middle_name ?? '',
                        $r->last_name ?? '',
                    ], fn ($s) => $s !== ''))));
                    if (! empty($r->suffix)) {
                        $name .= ', '.$r->suffix;
                    }
                    $respondents[] = $name;
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $val = $respondents[$i - 1] ?? '';
                $placeholders['{respondent_'.$i.'}'] = $val;
            }
            if (! empty($formData['date_filed'])) {
                $df = \Carbon\Carbon::parse($formData['date_filed']);
                $placeholders['{date_filed}'] = $df->format('F j, Y');
            }
            if (! empty($formData['date_served'])) {
                $ds = \Carbon\Carbon::parse($formData['date_served']);
                $placeholders['{date_served}'] = $ds->format('F j, Y');
            }
            $placeholders['{summons_served_by}'] = (string) ($formData['summons_served_by'] ?? '');
            $placeholders['{summons_served_to}'] = (string) ($formData['summons_served_to'] ?? '');
            $placeholders['{mediation_dates}'] = (string) ($formData['mediation_dates'] ?? '');
            $placeholders['{conciliation_dates}'] = (string) ($formData['conciliation_dates'] ?? '');
            $placeholders['{mediator_name}'] = (string) ($formData['mediator_name'] ?? '');
            $placeholders['{conciliator_name}'] = (string) ($formData['conciliator_name'] ?? '');
            $placeholders['{chairman_name}'] = (string) ($formData['chairman_name'] ?? '');
            $placeholders['{secretary_name}'] = (string) ($formData['secretary_name'] ?? '');
            $placeholders['{member_name}'] = (string) ($formData['member_name'] ?? '');
            $placeholders['{gist_of_settlement}'] = (string) ($formData['gist_of_settlement'] ?? '');
            $placeholders['{remarks}'] = (string) ($formData['remarks'] ?? '');
        }

        // Also handle ${placeholder} syntax
        foreach ($placeholders as $placeholder => $value) {
            $placeholderWithoutBraces = substr($placeholder, 1, -1); // Remove { and }
            $dollarPlaceholder = '${'.$placeholderWithoutBraces.'}';
            $placeholders[$dollarPlaceholder] = $value;
        }

        $dom = new DOMDocument;
        @$dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $paras = $xpath->query('//w:p');
        if ($paras && $paras->length > 0) {
            foreach ($paras as $p) {
                $texts = $xpath->query('.//w:t', $p);
                if (! $texts || $texts->length === 0) {
                    continue;
                }
                $orig = '';
                foreach ($texts as $t) {
                    $orig .= $t->textContent ?? '';
                }
                $remove = false;
                foreach ($placeholders as $ph => $val) {
                    if ($ph === '' || $orig === '') {
                        continue;
                    }
                    if (strpos($orig, $ph) !== false) {
                        $isEmpty = is_array($val)
                            ? count(array_filter($val, fn ($v) => (string) $v !== '')) === 0
                            : ((string) ($val ?? '') === '');
                        if ($isEmpty && (strpos($ph, 'member_') !== false || strpos($ph, 'approval_') !== false || strpos($ph, 'incapacity_example_line') !== false || strpos($ph, 'compliant_') !== false || strpos($ph, 'respondent_') !== false || preg_match('/resident_name\\d+/', $ph) || preg_match('/respondent_\\d+_name/', $ph) || strpos($ph, 'summon_result_') !== false)) {
                            $remove = true;
                            break;
                        }
                    }
                }
                if ($remove) {
                    $p->parentNode->removeChild($p);

                    continue;
                }
                $new = $orig;
                foreach ($placeholders as $placeholder => $value) {
                    $rep = is_array($value)
                        ? implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $value))
                        : (string) ($value ?? '');
                    if ($placeholder !== '' && $new !== '') {
                        $new = str_replace($placeholder, $rep, $new);
                    }
                }
                if ($new !== $orig) {
                    $firstText = $texts->item(0);
                    if ($firstText) {
                        while ($firstText->firstChild) {
                            $firstText->removeChild($firstText->firstChild);
                        }
                        $firstText->setAttribute('xml:space', 'preserve');
                        $firstText->appendChild($dom->createTextNode($new));
                    }
                    // Remove subsequent text nodes to avoid mixed content with old split runs
                    for ($i = 1; $i < $texts->length; $i++) {
                        $node = $texts->item($i);
                        if ($node && $node->parentNode) {
                            $node->parentNode->removeChild($node);
                        }
                    }
                }
            }
            $xml = $dom->saveXML();
        }

        return $xml;
    }

    private function dayOrdinal(int $day): string
    {
        $n = $day % 100;
        if ($n >= 11 && $n <= 13) {
            return $day.'th';
        }
        $last = $day % 10;
        if ($last === 1) {
            return $day.'st';
        }
        if ($last === 2) {
            return $day.'nd';
        }
        if ($last === 3) {
            return $day.'rd';
        }

        return $day.'th';
    }
}

