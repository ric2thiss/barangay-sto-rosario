<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Permit</title>
    <style>
        @font-face {
            font-family: 'ImpactCustom';
            src: url('{{ str_replace("\\", "/", public_path("fonts/impact.ttf")) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @page { margin: 30px 40px; }
        body { margin: 0; padding: 0; font-family: "Century Gothic", Arial, sans-serif; font-size: 12pt; line-height: 1.5; color: #000; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .header-logo { width: 85px; height: 85px; }
        .header-text { text-align: center; vertical-align: middle; font-family: "Times New Roman", Times, serif; color: #000; line-height: 1.25; }
        
        .divider-container { text-align: center; margin: 5px 0; }
        .divider-img { width: 100%; height: 12px; }

        .office-title { text-align: center; font-family: "Times New Roman", Times, serif; font-weight: bold; font-size: 13pt; margin-top: 10px; text-transform: uppercase; }

        .title-container { text-align: center; margin-top: 15px; margin-bottom: 25px; }
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

        .greeting { font-family: "Century Gothic", Arial, sans-serif; font-size: 13pt; margin: 20px 0 15px 0; }
        .content { text-align: justify; font-family: "Century Gothic", Arial, sans-serif; font-size: 12pt; line-height: 1.6; }
        .content p { margin: 0 0 12px 0; text-indent: 40px; }

        .signature-block { margin-top: 40px; text-align: center; }
        .esign-img { max-width: 180px; max-height: 70px; }
        .sig-name { font-family: "Century Gothic", Arial, sans-serif; font-weight: bold; font-size: 12pt; }
        .sig-title { font-family: "Century Gothic", Arial, sans-serif; font-size: 12pt; }
        .footer-note { margin-top: 40px; font-size: 9pt; font-style: italic; }
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
        <h1 class="certificate-title">BARANGAY PERMIT</h1>
    </div>

    {{-- ─── BODY ────────────────────────────────────── --}}
    <div class="greeting">TO WHOM IT MAY CONCERN:</div>

    <div class="content">
        <p>
            <strong><em>This is to CERTIFY that Mr./Mrs./Ms.
            {{ $resident->first_name }} {{ $resident->middle_name }} {{ $resident->surname ?? $resident->last_name }}{{ $resident->suffix ? ', ' . $resident->suffix : '' }},
            of legal age, is hereby granted a permission to {{ $request->purpose ?? 'conduct activities' }} within the jurisdiction of Barangay Sto. Rosario on {{ now()->format('F j, Y') }} at {{ now()->format('g:i A') }}, this barangay.</em></strong>
        </p>

        <p>This permit is valid only for the date and time specified.</p>

        <p>
            <strong>Issued this {{ now()->format('j') }}{{ now()->format('S') }} day of {{ now()->format('F') }}, {{ now()->year }} at the Office of the Punong Barangay, Barangay Sto. Rosario, Magallanes, Agusan del Norte.</strong>
        </p>
    </div>

    {{-- ─── E-SIGNATURE + NAME ──────────────────────── --}}
    <div class="signature-block">
        @if(file_exists(storage_path('app/public/logos/esign_permit.jpeg')))
            <img src="{{ storage_path('app/public/logos/esign_permit.jpeg') }}" alt="" class="esign-img"><br>
        @endif
        <span class="sig-name">CLEOPATRA C. ROCES</span><br>
        <span class="sig-title">Punong Barangay</span>
    </div>

    <div class="footer-note">Barangay Dry Seal</div>
</body>
</html>
