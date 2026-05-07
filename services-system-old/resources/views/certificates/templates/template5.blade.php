<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificateType->certificate_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .content {
            margin: 30px 0;
        }
        .signature-section {
            margin-top: 50px;
            text-align: right;
        }
        .signature-line {
            margin-top: 60px;
            width: 300px;
            border-top: 1px solid #000;
            display: inline-block;
        }
        .official-name {
            font-weight: bold;
            text-align: center;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">BARANGAY CLEARANCE CERTIFICATE</div>
    </div>
    
    <div class="content">
        <p><em>We, the undersigned Barangay Officials of Barangay {{ $resident->purok->purok_name }} do hereby certify:</em></p>
        
        <p>That {{ strtoupper($resident->first_name . " " . $resident->middle_name . " " . $resident->last_name) }},</p>
        <p>is a resident of this barangay with address at {{ $resident->address }}.</p>
        
        <p>This certification is issued in lieu of {{ $request->purpose }}.</p>
    </div>
    
    <div class="signature-section">
        <p>Prepared this {{ $issueDate }}.</p>
        <div class="signature-line"></div>
        <div class="official-name">Barangay Captain</div>
    </div>
</body>
</html>