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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print Cedula Overlay</title>
    <style>
        @page {
            size: 14in 8.5in;
            /* adjust to your real paper */
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: white;
            font-family: Arial, sans-serif;
        }

        .page {
            position: relative;
            width: 14in;
            /* adjust */
            height: 8.5in;
            /* adjust */
            margin: 0 auto;
            background: white;
        }

        .field {
            position: absolute;
            font-size: 14px;
            line-height: 1;
            white-space: nowrap;
        }

        .small {
            font-size: 12px;
        }

        .tiny {
            font-size: 10px;
        }

        .bold {
            font-weight: bold;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 10px 16px;
        }

        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>

    <button class="print-btn" onclick="window.print()">Print</button>

    <div class="page">

        <!-- YEAR -->
        <div class="field bold" style="top: 78px; left: 52px;">
            <?= e($yearIssued) ?>
        </div>

        <!-- PLACE OF ISSUE -->
        <div class="field" style="top: 78px; left: 170px;">
            <?= e($cedula['place_of_issue'] ?? '') ?>
        </div>

        <!-- DATE ISSUED -->
        <div class="field" style="top: 78px; left: 690px;">
            <?= e($issuedDate) ?>
        </div>

        <!-- SURNAME -->
        <div class="field" style="top: 128px; left: 150px;">
            <?= e($cedula['surname'] ?? '') ?>
        </div>

        <!-- FIRST NAME -->
        <div class="field" style="top: 128px; left: 405px;">
            <?= e($cedula['first_name'] ?? '') ?>
        </div>

        <!-- MIDDLE NAME -->
        <div class="field" style="top: 128px; left: 620px;">
            <?= e($cedula['middle_name'] ?? '') ?>
        </div>

        <!-- ADDRESS -->
        <div class="field" style="top: 171px; left: 140px;">
            <?= e($cedula['address'] ?? '') ?>
        </div>

        <!-- CITIZENSHIP -->
        <div class="field" style="top: 220px; left: 52px;">
            <?= e($cedula['citizenship'] ?? '') ?>
        </div>

        <!-- PLACE OF BIRTH -->
        <div class="field" style="top: 220px; left: 640px;">
            <?= e($cedula['birth_place'] ?? '') ?>
        </div>

        <!-- DATE OF BIRTH -->
        <div class="field" style="top: 298px; left: 825px;">
            <?= e($birthDate) ?>
        </div>

        <!-- HEIGHT -->
        <div class="field" style="top: 258px; left: 1120px;">
            <?= e($cedula['height'] ?? '') ?>
        </div>

        <!-- WEIGHT -->
        <div class="field" style="top: 300px; left: 1120px;">
            <?= e($cedula['weight'] ?? '') ?>
        </div>

        <!-- OCCUPATION -->
        <div class="field" style="top: 356px; left: 55px;">
            <?= e($cedula['occupation'] ?? '') ?>
        </div>

        <!-- BASIC TAX -->
        <div class="field" style="top: 465px; left: 980px;">
            <?= e(number_format((float)($cedula['basic_tax'] ?? 0), 2)) ?>
        </div>

        <!-- ADDITIONAL BUSINESS -->
        <div class="field" style="top: 565px; left: 980px;">
            <?= e(number_format((float)($cedula['additional_tax_business'] ?? 0), 2)) ?>
        </div>

        <!-- ADDITIONAL PROFESSION -->
        <div class="field" style="top: 645px; left: 980px;">
            <?= e(number_format((float)($cedula['additional_tax_profession'] ?? 0), 2)) ?>
        </div>

        <!-- COMMUNITY TAX DUE -->
        <div class="field" style="top: 430px; left: 1230px;">
            <?= e(number_format((float)($cedula['community_tax_due'] ?? 0), 2)) ?>
        </div>

        <!-- TOTAL -->
        <div class="field" style="top: 775px; left: 1230px;">
            <?= e(number_format((float)($cedula['amount'] ?? 0), 2)) ?>
        </div>

        <!-- INTEREST -->
        <div class="field" style="top: 846px; left: 1230px;">
            <?= e(number_format((float)($cedula['interest'] ?? 0), 2)) ?>
        </div>

        <!-- TOTAL AMOUNT PAID -->
        <div class="field" style="top: 920px; left: 1215px;">
            <?= e(number_format((float)($cedula['amount'] ?? 0) + (float)($cedula['interest'] ?? 0), 2)) ?>
        </div>

        <!-- AMOUNT IN WORDS -->
        <div class="field" style="top: 1000px; left: 950px;">
            <?= e($cedula['amount_in_words'] ?? '') ?>
        </div>
    </div>

</body>

</html>





