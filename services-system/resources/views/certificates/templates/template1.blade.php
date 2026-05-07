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
        .subtitle {
            font-size: 16px;
            font-style: italic;
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
        <div class="title">TO WHOM IT MAY CONCERN</div>
    </div>
    
    <div class="content">
        <p>This is to certify that:</p>
        
        <div style="margin: 20px 0; text-align: center; font-weight: bold; text-transform: uppercase;">
            {{ strtoupper($resident->first_name . ' ' . $resident->middle_name . ' ' . $resident->last_name) }}
        </div>
        
        <p>s/o {{ $resident->mothers_maiden_name }}</p>
        
        <p>Is bonafide resident of {{ $resident->address }}, Purok {{ $resident->purok->purok_name }}.</p>
        
        <p>This certification is being issued upon his/her request for {{ $request->purpose }}.</p>
    </div>
    
    <div class="signature-section">
        <p>Issued this {{ $issueDate }}.</p>
        <div class="signature-line"></div>
        <div class="official-name">Barangay Captain</div>
    </div>
</body>
</html>