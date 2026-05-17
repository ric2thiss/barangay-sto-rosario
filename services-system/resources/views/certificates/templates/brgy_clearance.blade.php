<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Clearance</title>
    <style>
        @font-face {
            font-family: 'ImpactCustom';
            src: url('{{ str_replace("\\", "/", public_path("fonts/impact.ttf")) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @page { margin: 25px 40px; }
        body { margin: 0; padding: 0; font-family: "Century Gothic", Arial, sans-serif; font-size: 11pt; line-height: 1.4; color: #000; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .header-logo { width: 85px; height: 85px; }
        .header-text { text-align: center; vertical-align: middle; font-family: "Times New Roman", Times, serif; color: #000; line-height: 1.25; }
        
        .divider-container { text-align: center; margin: 5px 0; }
        .divider-img { width: 100%; height: 12px; }

        .office-title { text-align: center; font-family: "Times New Roman", Times, serif; font-weight: bold; font-size: 13pt; margin-top: 10px; text-transform: uppercase; }

        .title-container { text-align: center; margin-top: 15px; margin-bottom: 20px; }
        .certificate-title {
            font-family: 'ImpactCustom', 'Impact', 'Arial Black', Arial, sans-serif;
            font-size: 36pt;
            font-weight: bold;
            color: #00A859; /* Perfect green */
            text-align: center;
            margin: 0;
            padding: 0;
            text-transform: uppercase;
            letter-spacing: -1.5px;
            /* Sleek, thin black outline to keep font letters thick and bold */
            text-shadow: 
                -1px -1px 0 #000, 
                 1px -1px 0 #000, 
                -1px  1px 0 #000, 
                 1px  1px 0 #000;
        }

        .greeting { font-family: "Century Gothic", Arial, sans-serif; font-size: 11pt; font-weight: bold; margin: 15px 0 12px 0; }
        .content { text-align: justify; font-family: "Century Gothic", Arial, sans-serif; font-size: 11pt; line-height: 1.5; }
        .content p { margin: 0 0 10px 0; text-indent: 35px; }

        .sig-table { width: 100%; margin-top: 25px; border-collapse: collapse; }
        .sig-table td { width: 50%; vertical-align: top; padding: 5px; }
        .esign-img { max-width: 170px; max-height: 65px; }
        .sig-name { font-weight: bold; font-size: 11pt; }
        .sig-title { font-size: 10pt; }

        .footer-seal { font-size: 9pt; font-style: italic; margin-top: 12px; }

        .meta-table { width: 100%; margin-top: 25px; border-collapse: collapse; }
        .meta-table td { vertical-align: top; padding: 3px; }
        .receipt-block { font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 1.6; }
        .receipt-block .label { font-weight: bold; }
        .applicant-sig { font-family: Arial, sans-serif; font-size: 11pt; text-align: center; }
    </style>
</head>
<body>
    {{-- ─── PURE HTML/CSS HEADER ────────────────────── --}}
    <table class="header-table">
        <tr>
            <td style="width: 15%; text-align: left; vertical-align: middle;">
                @if(file_exists(storage_path('app/public/logos/logo_left.jpg')))
                    <img src="{{ storage_path('app/public/logos/logo_left.jpg') }}" class="header-logo">
                @endif
            </td>
            <td style="width: 70%;" class="header-text">
                <div style="font-size: 12pt; font-style: italic; font-family: 'Georgia', serif;">Republic of the Philippines</div>
                <div style="font-size: 11pt;">Province of Agusan del Norte</div>
                <div style="font-size: 11pt;">Municipality of Magallanes</div>
                <div style="font-weight: bold; font-size: 14pt; letter-spacing: 0.5px; margin-top: 2px;">BARANGAY STO. ROSARIO</div>
                <div style="font-size: 8.5pt; color: #333; margin-top: 3px; font-weight: bold;">BARANGAY HALL, PUROK 1, BRGY. STO. ROSARIO, MAGALLANES, AGUSAN DEL NORTE</div>
                <div style="font-size: 7.5pt; font-style: italic; color: #555; margin-top: 1px;">Tel No. (085) 806-0050 | Email Address: barangaystorosario2t@gmail.com</div>
            </td>
            <td style="width: 15%; text-align: right; vertical-align: middle;">
                @if(file_exists(storage_path('app/public/logos/logo_right.png')))
                    <img src="{{ storage_path('app/public/logos/logo_right.png') }}" class="header-logo">
                @endif
            </td>
        </tr>
    </table>

    <div class="divider-container">
        @if(file_exists(storage_path('app/public/logos/separator_line.png')))
            <img src="{{ storage_path('app/public/logos/separator_line.png') }}" class="divider-img">
        @endif
    </div>

    <div class="office-title">OFFICE OF THE PUNONG BARANGAY</div>

    <div class="title-container">
        <h1 class="certificate-title">BARANGAY CLEARANCE</h1>
    </div>

    {{-- ─── BODY ────────────────────────────────────── --}}
    <div class="greeting">TO WHOM IT MAY CONCERN:</div>

    <div class="content">
        <p>
            <strong>Pursuant to the Local Government Code, otherwise known as R.A No. 7160 Section - 152, Barangay Clearance is hereby granted to
            Mr./Ms./Mrs. {{ $resident->first_name }} {{ $resident->middle_name }} {{ $resident->surname ?? $resident->last_name }}{{ $resident->suffix ? ', ' . $resident->suffix : '' }},
            of legal age, Filipino, a resident of {{ $resident->purok->purok_name ?? $resident->purok ?? '' }}, Barangay Sto. Rosario, Magallanes, Agusan del Norte.</strong>
        </p>

        <p>According to our record now filed in this office, he/she was never being accused nor has any pending case as of this date.</p>

        <p>He/she is personally known to me to be a person of good moral character and law-abiding citizen.</p>

        <p><strong>This certification is being issued upon the request of the above-named person for {{ $request->purpose ?? 'whatever purpose it may serve' }} purposes.</strong></p>

        <p><strong>Issued this {{ now()->format('j') }}{{ now()->format('S') }} day of {{ now()->format('F') }}, {{ now()->year }} at the Office of the undersigned, Barangay Sto. Rosario, Magallanes, Agusan del Norte.</strong></p>
    </div>

    {{-- ─── DUAL SIGNATURE BLOCK ────────────────────── --}}
    <table class="sig-table">
        <tr>
            <td style="text-align: left;">
                <span style="font-size: 10pt;">For and in the absence of the Punong Barangay</span><br><br>
                @if(file_exists(storage_path('app/public/logos/esign_clearance.jpeg')))
                    <img src="{{ storage_path('app/public/logos/esign_clearance.jpeg') }}" alt="" class="esign-img"><br>
                @endif
                <span class="sig-name">HON. CLEOPATRA C. ROCES</span><br>
                <span class="sig-title">Punong Barangay</span>
            </td>
            <td style="text-align: right;">
                <br><br><br>
                <span class="sig-name">HON. MARGIE M. GABATO</span><br>
                <span class="sig-title">Barangay Kagawad</span>
            </td>
        </tr>
    </table>

    <div class="footer-seal">Barangay Dry Seal</div>

    {{-- ─── RECEIPT + APPLICANT ──────────────────────── --}}
    <table class="meta-table">
        <tr>
            <td style="width: 55%;">
                <div class="receipt-block">
                    <span class="label" style="text-decoration: underline;">OR NO.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $request->or_number ?? '____________' }}<br>
                    <span class="label">PLACE ISSUED</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong>BARANGAY STO. ROSARIO</strong><br>
                    <span style="font-style: italic; text-decoration: underline;">RES. CERT.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ________________________________<br>
                    <span style="font-style: italic; text-decoration: underline;">DATE ISSUED</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $request->created_at ? $request->created_at->format('F j, Y') : now()->format('F j, Y') }}<br>
                    <span style="font-style: italic;">PLACE ISSUED</span>&nbsp;&nbsp;&nbsp;&nbsp;: BARANGAY STO. ROSARIO
                </div>
            </td>
            <td style="width: 45%; text-align: center; vertical-align: bottom;">
                <div class="applicant-sig">
                    <br><br><br>
                    <div style="border-top: 1px solid #000; width: 220px; margin: 0 auto; padding-top: 5px;">
                        Applicant Signature over Printed Name
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
