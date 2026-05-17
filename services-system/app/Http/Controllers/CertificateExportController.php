<?php

namespace App\Http\Controllers;

use App\Models\CertificateIssuance;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Exception;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpWord\TemplateProcessor;

use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use ConvertApi\ConvertApi;

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
        $convertApiSecret = env('CONVERTAPI_SECRET');
        $cloudConvertApiKey = env('CLOUDCONVERT_API_KEY');
        $filename = $data['certificateType']->certificate_name
            . '_' . ($data['resident']->surname ?? $data['resident']->last_name)
            . '_' . now()->format('Y-m-d') . '.pdf';

        $templateMap = [
            'indigent'           => 'indigent_format.docx',
            'brgy_permit'        => 'brgy_permit.docx',
            'barangay_clearance' => 'brgy_clearance.docx',
            'goodmoral'          => 'good_moral.docx',
        ];

        $templateFile = $templateMap[$template] ?? 'residency_format.docx';
        $templatePath = resource_path('views/certificates/templates/' . $templateFile);

        // 1. ConvertAPI (Generous 250 free conversions/month, super fast and simple)
        if (!empty($convertApiSecret)) {
            try {
                if (!file_exists($templatePath)) {
                    throw new Exception("Template file not found: " . $templatePath);
                }

                $templateProcessor = new TemplateProcessor($templatePath);
                $this->fillTemplate($templateProcessor, $data, $template);

                $tempDocx = tempnam(sys_get_temp_dir(), 'cert_') . '.docx';
                $templateProcessor->saveAs($tempDocx);

                ConvertApi::setApiCredentials($convertApiSecret);
                
                $result = ConvertApi::convert('pdf', [
                    'File' => $tempDocx,
                ], 'office'); // High-fidelity MS Office engine

                $tempPdf = tempnam(sys_get_temp_dir(), 'cert_') . '.pdf';
                $result->getFile()->save($tempPdf);

                @unlink($tempDocx);

                return response()->download($tempPdf, $filename)->deleteFileAfterSend(true);

            } catch (Exception $e) {
                logger()->error('ConvertAPI PDF conversion failed: ' . $e->getMessage());
            }
        }

        // 2. CloudConvert (Fallback 1)
        if (!empty($cloudConvertApiKey)) {
            try {
                if (!file_exists($templatePath)) {
                    throw new Exception("Template file not found: " . $templatePath);
                }

                $templateProcessor = new TemplateProcessor($templatePath);
                $this->fillTemplate($templateProcessor, $data, $template);

                $tempDocx = tempnam(sys_get_temp_dir(), 'cert_') . '.docx';
                $templateProcessor->saveAs($tempDocx);

                $cloudconvert = new CloudConvert([
                    'api_key' => $cloudConvertApiKey,
                    'sandbox' => false
                ]);

                $job = (new Job())
                    ->addTask(new Task('import/upload', 'upload-my-file'))
                    ->addTask(
                        (new Task('convert', 'convert-my-file'))
                            ->set('input', 'upload-my-file')
                            ->set('output_format', 'pdf')
                    )
                    ->addTask(
                        (new Task('export/url', 'export-my-file'))
                            ->set('input', 'convert-my-file')
                    );

                $job = $cloudconvert->jobs()->create($job);

                $uploadTask = $job->getTasks()->whereName('upload-my-file')[0];
                $cloudconvert->tasks()->upload($uploadTask, fopen($tempDocx, 'r'), basename($tempDocx));

                $job = $cloudconvert->jobs()->wait($job);

                $exportTask = $job->getTasks()->whereName('export-my-file')[0];
                $file = $exportTask->getResult()->files[0];
                $source = $cloudconvert->getHttpTransport()->download($file->url)->detach();

                $tempPdf = tempnam(sys_get_temp_dir(), 'cert_') . '.pdf';
                $dest = fopen($tempPdf, 'w');
                stream_copy_to_stream($source, $dest);
                fclose($dest);

                @unlink($tempDocx);

                return response()->download($tempPdf, $filename)->deleteFileAfterSend(true);

            } catch (Exception $e) {
                logger()->error('CloudConvert PDF conversion failed: ' . $e->getMessage());
            }
        }

        // 2. Fallback to local DomPDF if CloudConvert key is missing or failed
        $templateMap = [
            'indigent'           => 'indigent',
            'brgy_permit'        => 'brgy_permit',
            'barangay_clearance' => 'brgy_clearance',
            'goodmoral'          => 'good_moral',
            'residency'          => 'residency',
            'template1'          => 'template1',
            'template2'          => 'template2',
            'template3'          => 'template3',
            'template4'          => 'template4',
            'template5'          => 'template5',
        ];

        $viewName = $templateMap[$template] ?? 'residency';

        try {
            $pdf = PDF::loadView('certificates.templates.' . $viewName, $data)
                ->setPaper('letter', 'portrait');

            return $pdf->stream($filename);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'PDF generation failed: ' . $e->getMessage()
            ], 500);
        }
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