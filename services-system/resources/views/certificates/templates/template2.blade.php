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
            font-size: 24px;
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
        <div class="title">BARANGAY CERTIFICATION</div>
    </div>
    
    <div class="content">
        <p><strong>KNOW ALL MEN BY THESE PRESENTS:</strong></p>
        
        <p>That {{ $resident->first_name }} {{ $resident->middle_name }} {{ $resident->last_name }},</p>
        <p>of legal age, {{ $resident->civil_status }}, {{ $resident->sex }},</p>
        <p>is a resident of {{ $resident->address }}, Purok {{ $resident->purok->purok_name }}.</p>
        
        <p>This certification is issued in connection with {{ $request->purpose }}.</p>
    </div>
    
    <div class="signature-section">
        <p>Done at Barangay Hall this {{ $issueDate }}.</p>
        <div class="signature-line"></div>
        <div class="official-name">Barangay Captain</div>
    </div>
</body>
</html>