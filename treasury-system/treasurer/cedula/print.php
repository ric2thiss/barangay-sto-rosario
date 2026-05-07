<?php
include "../../config/database.php";
include "../../config/session.php";

if (!isset($_GET['id'])) {
    exit('Missing ID');
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM cedula WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit('Cedula not found');
}

$cedula = $result->fetch_assoc();

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$issuedDate = !empty($cedula['issued_date']) ? date('m d Y', strtotime($cedula['issued_date'])) : '';
$birthDate  = !empty($cedula['birth_date']) ? date('m d Y', strtotime($cedula['birth_date'])) : '';
$yearIssued = !empty($cedula['year_issued']) ? $cedula['year_issued'] : '';

// Name Fallbacks
$surname = $cedula['surname'] ?? '';
$firstName = $cedula['first_name'] ?? '';
$middleName = $cedula['middle_name'] ?? '';

if (empty($surname) && empty($firstName) && !empty($cedula['full_name'])) {
    $nameParts = explode(',', $cedula['full_name']);
    if (count($nameParts) > 1) {
        $surname = trim($nameParts[0]);
        $firstName = trim($nameParts[1]);
    } else {
        $firstName = $cedula['full_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print Cedula - <?= e($cedula['full_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: 14in 8.5in;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family: 'Inter', Arial, sans-serif;
            color: #1e293b;
        }

        .controls {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 9999;
        }

        .controls-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-print {
            background: #1F3A93;
            color: white;
        }

        .btn-print:hover {
            background: #1a317d;
            transform: translateY(-1px);
        }

        .btn-back {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-back:hover {
            background: #f1f5f9;
        }

        .toggle-container {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #1F3A93;
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        .preview-container {
            padding-top: 80px;
            padding-bottom: 40px;
            display: flex;
            justify-content: center;
            overflow-x: auto;
        }

        .page {
            position: relative;
            width: 14in;
            height: 8.5in;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .page.show-bg {
            background-image: url('../../assets/images/cedula_template.png');
            background-size: 100% 100%;
            background-repeat: no-repeat;
        }

        .field {
            position: absolute;
            font-size: 15px;
            line-height: 1.1;
            white-space: nowrap;
            color: #000;
            font-weight: 500;
            cursor: default;
        }

        .page.show-bg .field {
            color: rgba(31, 58, 147, 0.9) !important;
            background: rgba(255, 255, 255, 0.6);
            padding: 2px 4px;
            border-radius: 4px;
            cursor: move;
            border: 1px dashed #1F3A93;
        }

        .field:hover::after {
            content: "top: " attr(data-top) ", left: " attr(data-left);
            position: absolute;
            bottom: 100%;
            left: 0;
            background: #1e293b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            z-index: 100;
            white-space: nowrap;
            display: none;
        }

        .page.show-bg .field:hover::after {
            display: block;
        }

        .bold {
            font-weight: bold;
        }

        .calibration-hint {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #1F3A93;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: none;
            z-index: 10000;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translate(-50%, 100%); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }

        @media print {
            .controls, .calibration-hint {
                display: none !important;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .preview-container {
                padding: 0;
            }

            .page {
                margin: 0 !important;
                box-shadow: none !important;
                /* Background is kept if show-bg is present */
            }

            .page:not(.show-bg) {
                background-image: none !important;
            }
            
            .page .field {
                color: #000 !important;
                background: none !important;
                padding: 0 !important;
                border: none !important;
            }
        }

        #styleSnippet {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 300px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 10001;
            display: none;
            font-size: 12px;
        }

        #styleSnippet h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #1F3A93;
        }

        #styleSnippet pre {
            background: #f8fafc;
            padding: 10px;
            border-radius: 6px;
            overflow-x: auto;
            max-height: 200px;
            margin: 0;
        }
    </style>
</head>

<body>

    <div class="controls no-print">
        <div class="controls-left">
            <a href="list.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <div class="toggle-container">
                <span>Interactive Calibration</span>
                <label class="switch">
                    <input type="checkbox" id="toggleBG" onchange="toggleBackground()">
                    <span class="slider"></span>
                </label>
            </div>
            <button class="btn btn-secondary" onclick="generateStyles()" id="saveBtn" style="display:none;">
                <i class="fas fa-code"></i> Get Coordinates
            </button>
        </div>
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Document
        </button>
    </div>

    <div id="styleSnippet" class="no-print">
        <h4>Updated Coordinates</h4>
        <p>Copy these into your code:</p>
        <pre id="snippetContent"></pre>
        <button class="btn btn-secondary" style="width:100%; margin-top:10px;" onclick="$('#styleSnippet').hide()">Close</button>
    </div>

    <div class="calibration-hint no-print" id="calibHint">
        <i class="fas fa-mouse-pointer"></i> &nbsp; Drag fields to align. Click 'Get Coordinates' to save your work.
    </div>

    <div class="preview-container">
        <div id="printPage" class="page">

            <!-- TOP SECTION -->
            <div class="field bold field-item" id="f-year" style="top: 410px; left: 160px;" data-top="410" data-left="160">
                <?= e($yearIssued) ?>
            </div>

            <div class="field field-item" id="f-place" style="top: 410px; left: 240px;" data-top="410" data-left="240">
                <?= e($cedula['place_of_issue'] ?? '') ?>
            </div>

            <div class="field field-item" id="f-date" style="top: 410px; left: 350px;" data-top="410" data-left="350">
                <?= e($issuedDate) ?>
            </div>

            <!-- NAME SECTION -->
            <div class="field field-item" id="f-surname" style="top: 540px; left: 160px;" data-top="540" data-left="160">
                <?= e($surname) ?>
            </div>

            <div class="field field-item" id="f-first" style="top: 540px; left: 300px;" data-top="540" data-left="300">
                <?= e($firstName) ?>
            </div>

            <div class="field field-item" id="f-middle" style="top: 540px; left: 450px;" data-top="540" data-left="450">
                <?= e($middleName) ?>
            </div>

            <!-- ADDRESS -->
            <div class="field field-item" id="f-address" style="top: 590px; left: 160px;" data-top="590" data-left="160">
                <?= e($cedula['address'] ?? '') ?>
            </div>

            <!-- BIO DATA -->
            <div class="field field-item" id="f-citizenship" style="top: 640px; left: 160px;" data-top="640" data-left="160">
                <?= e($cedula['citizenship'] ?? '') ?>
            </div>

            <div class="field field-item" id="f-sex" style="top: 640px; left: 290px;" data-top="290" data-left="160">
                <?= e($cedula['sex'] ?? '') ?>
            </div>

            <div class="field field-item" id="f-civil" style="top: 640px; left: 370px;" data-top="370" data-left="160">
                <?= e($cedula['civil_status'] ?? '') ?>
            </div>

            <div class="field field-item" id="f-birthplace" style="top: 640px; left: 460px;" data-top="640" data-left="460">
                <?= e($cedula['birth_place'] ?? '') ?>
            </div>

            <div class="field field-item" id="f-birthdate" style="top: 720px; left: 250px;" data-top="720" data-left="250">
                <?= e($birthDate) ?>
            </div>

            <div class="field field-item" id="f-height" style="top: 790px; left: 180px;" data-top="790" data-left="180">
                <?= e($cedula['height'] ?? '') ?>
            </div>

            <div class="field field-item" id="f-weight" style="top: 790px; left: 350px;" data-top="790" data-left="350">
                <?= e($cedula['weight'] ?? '') ?>
            </div>

            <div class="field field-item" id="f-occupation" style="top: 760px; left: 160px;" data-top="760" data-left="160">
                <?= e($cedula['occupation'] ?? '') ?>
            </div>

            <!-- TAX TABLE (RIGHT) -->
            <div class="field field-item" id="f-tax1" style="top: 455px; left: 1220px;" data-top="455" data-left="1220">
                <?= e(number_format((float)($cedula['basic_tax'] ?? 0), 2)) ?>
            </div>

            <div class="field field-item" id="f-tax2" style="top: 510px; left: 1220px;" data-top="510" data-left="1220">
                <?= e(number_format((float)($cedula['additional_tax_business'] ?? 0), 2)) ?>
            </div>

            <div class="field field-item" id="f-tax3" style="top: 615px; left: 1220px;" data-top="615" data-left="1220">
                <?= e(number_format((float)($cedula['additional_tax_profession'] ?? 0), 2)) ?>
            </div>
            
            <div class="field field-item" id="f-tax4" style="top: 715px; left: 1220px;" data-top="715" data-left="1220">
                <?= e(number_format((float)($cedula['additional_tax_property'] ?? 0), 2)) ?>
            </div>

            <div class="field field-item" id="f-tax-due" style="top: 760px; left: 1220px;" data-top="760" data-left="1220">
                <?= e(number_format((float)($cedula['community_tax_due'] ?? 0), 2)) ?>
            </div>

            <div class="field field-item" id="f-tax-total" style="top: 805px; left: 1220px;" data-top="805" data-left="1220">
                <?= e(number_format((float)($cedula['amount'] ?? 0), 2)) ?>
            </div>

            <div class="field field-item" id="f-tax-interest" style="top: 845px; left: 1220px;" data-top="845" data-left="1220">
                <?= e(number_format((float)($cedula['interest'] ?? 0), 2)) ?>
            </div>

            <div class="field bold field-item" id="f-tax-paid" style="top: 890px; left: 1220px;" data-top="890" data-left="1220">
                <?= e(number_format((float)($cedula['amount'] ?? 0) + (float)($cedula['interest'] ?? 0), 2)) ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        function toggleBackground() {
            const page = document.getElementById('printPage');
            const toggle = document.getElementById('toggleBG');
            const hint = document.getElementById('calibHint');
            const saveBtn = document.getElementById('saveBtn');
            const fields = $('.field');

            if (toggle.checked) {
                page.classList.add('show-bg');
                hint.style.display = 'block';
                saveBtn.style.display = 'inline-flex';
                fields.draggable({
                    containment: "#printPage",
                    stop: function(event, ui) {
                        const top = ui.position.top;
                        const left = ui.position.left;
                        $(this).attr('data-top', top.toFixed(0));
                        $(this).attr('data-left', left.toFixed(0));
                    }
                });
            } else {
                page.classList.remove('show-bg');
                hint.style.display = 'none';
                saveBtn.style.display = 'none';
                if (fields.data('ui-draggable')) {
                    fields.draggable('destroy');
                }
            }
        }

        function generateStyles() {
            let css = "";
            $('.field-item').each(function() {
                const id = $(this).attr('id');
                const top = $(this).css('top');
                const left = $(this).css('left');
                css += `#${id} { top: ${top}; left: ${left}; }\n`;
            });
            $('#snippetContent').text(css);
            $('#styleSnippet').show();
        }
    </script>
</body>

</html>





