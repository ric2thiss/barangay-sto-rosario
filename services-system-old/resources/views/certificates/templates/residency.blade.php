<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Certification</title>
    <style>
        body {
            margin: 40px;
            font-family: "Century Gothic", Arial, sans-serif;
            line-height: 1.4;
            font-size: 14px;
        }

        /* HEADER CONTAINER */
        .header-container {
            position: relative;
            height: 120px;
            margin-bottom: 20px;
        }

        /* LOGO POSITIONS */
        .logo-left {
            position: absolute;
            top: 0;
            left: 0;
            width: 110px;
            height: 110px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .logo-right {
            position: absolute;
            top: 0;
            right: 0;
            width: 110px;
            height: 110px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /* CENTER TEXT */
        .header-text {
            position: absolute;
            top: 0;
            left: 100px;
            right: 100px;
            text-align: center;
            font-family: "Century Gothic", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.2;
            text-transform: uppercase;
            font-weight: bold;
        }

        .sub-header {
            font-size: 13px;
            font-weight: normal;
            margin-top: 5px;
            text-transform: none;
        }

        .office-title {
            margin-top: 30px;
            font-family: "Agency FB", Arial, sans-serif;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }

        .cert-title {
            margin-top: 30px;
            font-size: 24px;
            font-family: Impact, "Arial Black", sans-serif;
            letter-spacing: 1px;
            text-align: center;
        }

        .content {
            margin-top: 40px;
            text-align: justify;
            text-indent: 30px;
            font-size: 14px;
        }

        .signature-block {
            margin-top: 80px;
            text-align: right;
            font-family: "Century Gothic", Arial, sans-serif;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .sig-title {
            font-size: 14px;
        }

        .footer-note {
            margin-top: 60px;
            font-size: 14px;
            font-family: "Sitka Small", serif;
        }
    </style>
</head>
<body>
    <!-- HEADER WITH LOGOS -->
    <div class="header-container">
        <div class="logo-left">
            <img src="{{ storage_path('app/public/logos/logo_left.jpg') }}" alt="Municipality Logo" onerror="this.parentElement.innerHTML='LOGO';" style="max-width: 110px; max-height: 110px;">
        </div>

        <div class="header-text">
            Republic of the Philippines<br>
            Province of Agusan Del Norte<br>
            Municipality of Magallanes<br>
            BARANGAY STO. ROSARIO<br>
            <span class="sub-header">
                Barangay Hall, Purok 1, Brgy. Sto. Rosario, Magallanes, Agusan Del Norte<br>
                Tel No. (085) 806-0050 | Email Address: barangaystorosario2t@gmail.com
            </span>
        </div>

        <div class="logo-right">
            <img src="{{ storage_path('app/public/logos/logo_right.png') }}" alt="Barangay Logo" onerror="this.parentElement.innerHTML='LOGO';" style="max-width: 110px; max-height: 110px;">
        </div>
    </div>

    <div class="office-title">OFFICE OF THE PUNONG BARANGAY</div>
    <div class="cert-title">CERTIFICATION</div>

    <div class="content">
        TO WHOM IT MAY CONCERN:<br><br>

        THIS IS TO CERTIFY that Mr./Ms./Mrs. {{ $resident->first_name }} {{ $resident->middle_name }} {{ $resident->last_name }}{{ $resident->suffix ? ', ' . $resident->suffix : '' }}, {{ $resident->age }} yrs. old, 
        resident of Purok {{ $resident->purok->purok_name ?? '' }}, Barangay Sto. Rosario, Magallanes, Agusan del Norte, 
        is a bona fide resident of this barangay.<br><br>

        This certification is being issued upon the request of the {{ $request->purpose ?? 'applicant' }} 
        for {{ $request->purpose ?? 'purposes' }}.<br><br>

        Issued this <u>{{ now()->format('j') }}{{ now()->format('S') }}</u> day of <u>{{ now()->format('F') }}</u>, {{ now()->year }} at Barangay Sto. Rosario, Magallanes, 
        Agusan del Norte.
    </div>

    <div class="signature-block">
        <span class="sig-name">CLEOPATRA C. ROCES</span><br>
        <span class="sig-title">Punong Barangay</span>
    </div>

    <div class="footer-note">
        Barangay Dry Seal
    </div>
</body>
</html>