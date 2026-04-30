<?php

namespace App\Http\Controllers;

use App\Models\CertificateIssuance;
use Exception;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpWord\TemplateProcessor;

class CertificateExportController extends Controller
{
    public function export($requestId, $format, $template)
    {
        $request = CertificateIssuance::with(['resident', 'certificateType'])->findOrFail($requestId);

        $validFormats    = ['pdf', 'docx'];
        $validTemplates  = ['template1', 'template2', 'template3', 'template4', 'template5', 'residency', 'indigent', 'goodmoral', 'brgy_permit', 'barangay_clearance'];

        if (!in_array($format, $validFormats) || !in_array($template, $validTemplates)) {
            abort(404, 'Invalid format or template');
        }

        $exportData = [
            'resident'        => $request->resident,
            'certificateType' => $request->certificateType,
            'request'         => $request,
            'issueDate'       => now()->format('F j, Y'),
        ];

        return $format === 'pdf'
            ? $this->exportToPdf($exportData, $template)
            : $this->exportToWord($exportData, $template);
    }

    private function exportToPdf(array $data, string $template)
    {
        $templateMap = [
            'indigent'           => 'indigent_format.docx',
            'brgy_permit'        => 'brgy_permit.docx',
            'barangay_clearance' => 'brgy_clearance.docx',
            'goodmoral'          => 'good_moral.docx',
        ];

        $templateFile = $templateMap[$template] ?? 'residency_format.docx';
        $templatePath = resource_path('views/certificates/templates/' . $templateFile);

        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Template file not found'], 404);
        }

        // Fill the template
        $templateProcessor = new TemplateProcessor($templatePath);
        $this->fillTemplate($templateProcessor, $data, $template);

        // Save filled DOCX to temp file
        $tempDocx = tempnam(sys_get_temp_dir(), 'cert_') . '.docx';
        $templateProcessor->saveAs($tempDocx);

        // Convert to PDF via LibreOffice
        $outputDir = sys_get_temp_dir();
        $soffice   = '"C:\\Program Files\\LibreOffice\\program\\soffice.exe"';

        $command = sprintf(
            '%s --headless --convert-to pdf --outdir %s %s 2>&1',
            $soffice,
            escapeshellarg($outputDir),
            escapeshellarg($tempDocx)
        );

        exec($command, $output, $exitCode);

        // Clean up temp DOCX regardless of outcome
        if (file_exists($tempDocx)) {
            @unlink($tempDocx);
        }

        if ($exitCode !== 0) {
            return response()->json([
                'error' => 'PDF conversion failed: ' . implode(' ', $output)
            ], 500);
        }

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . pathinfo($tempDocx, PATHINFO_FILENAME) . '.pdf';

        if (!file_exists($pdfPath)) {
            return response()->json(['error' => 'PDF file not found after conversion.'], 500);
        }

        $filename = $data['certificateType']->certificate_name
            . '_' . ($data['resident']->surname ?? $data['resident']->last_name)
            . '_' . now()->format('Y-m-d') . '.pdf';

        return Response::download($pdfPath, $filename)->deleteFileAfterSend(true);
    }

    private function exportToWord(array $data, string $template)
    {
        $templateMap = [
            'indigent'           => 'indigent_format.docx',
            'brgy_permit'        => 'brgy_permit.docx',
            'barangay_clearance' => 'brgy_clearance.docx',
            'goodmoral'          => 'good_moral.docx',
        ];

        $templateFile = $templateMap[$template] ?? 'residency_format.docx';
        $templatePath = resource_path('views/certificates/templates/' . $templateFile);

        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Template file not found'], 404);
        }

        $templateProcessor = new TemplateProcessor($templatePath);
        $this->fillTemplate($templateProcessor, $data, $template);

        $tempFile = tempnam(sys_get_temp_dir(), 'cert_');
        $templateProcessor->saveAs($tempFile);

        $filename = $data['certificateType']->certificate_name
            . '_' . $data['resident']->last_name
            . '_' . now()->format('Y-m-d') . '.docx';

        return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function fillTemplate(TemplateProcessor $tp, array $data, string $template): void
    {
        $resident = $data['resident'];
        $request  = $data['request'];

      $fullName = $resident->full_name;

        $purok = $resident->purok ?? '';
        $age     = $resident->age;
        $purpose = $request->purpose ?? 'official purposes';
        $day     = now()->format('j');
        $dayOrd  = now()->format('j') . now()->format('S');
        $month   = now()->format('F');
        $year    = now()->format('Y');
        $date    = now()->format('F j, Y');
        $time    = now()->format('g:i A');

        $placeholders = [
            '${fullname}'        => $fullName,
            '${age}'             => $age,
            '${purok}'           => $purok,
            '${purpose}'         => $purpose,
            '${day}'             => $template === 'barangay_clearance' ? $day : $dayOrd,
            '${day_ord}'         => $dayOrd,
            '${month}'           => $month,
            '${year}'            => $year,
            '${time}'            => $time,
            'FULLNAME'           => $fullName,
            'RESIDENT_NAME'      => $fullName,
            'AGE'                => $age,
            'RESIDENT_AGE'       => $age,
            'PUROK'              => $purok,
            'ADDRESS'            => $purok,
            'PURPOSE'            => $purpose,
            'REQUEST_PURPOSE'    => $purpose,
            'CERT_PURPOSE'       => $purpose,
            'INDIGENT_PURPOSE'   => $purpose,
            'REQUESTER_NAME'     => $purpose,
            'INDIGENT_REQUESTER' => $purpose,
            'DATE'               => $date,
            'DATE_ISSUED'        => $date,
            'DAY'                => $day,
            'DAY_ORDINAL'        => $dayOrd,
            'MONTH'              => $month,
            'MONTH_YEAR'         => now()->format('F, Y'),
            'YEAR'               => $year,
        ];

        foreach ($placeholders as $key => $value) {
            try {
                $tp->setValue($key, $value);
            } catch (Exception $e) {
                // Placeholder not in this template — skip silently
            }
        }
    }
}