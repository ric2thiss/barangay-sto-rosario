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
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$issuedDate = !empty($cedula['issued_date']) ? date('m d Y', strtotime($cedula['issued_date'])) : '';
$birthDate = !empty($cedula['birth_date']) ? date('m d Y', strtotime($cedula['birth_date'])) : '';
$yearIssued = !empty($cedula['year_issued']) ? $cedula['year_issued'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title></title>
    <style>
        :root {
            --paper-width: 8.5in;
            --paper-height: 11in;
            --cedula-width: 5.2in;
            --cedula-height: 4.55in;
            --cedula-offset-x: 0.55in;
            --cedula-offset-y: 0in;
            --design-width: 1344px;
            --design-height: 1056px;
            --fit-x: 0.371429;
            --fit-y: 0.413636;
        }

        @page {
            size: 8.9in 11in;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: white;
            font-family: Arial, sans-serif;
            width: var(--paper-width);
            min-height: var(--paper-height);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            position: relative;
            width: var(--paper-width);
            height: var(--paper-height);
            margin: 0 auto;
            background: white;
            overflow: hidden;
        }

        .page {
            position: absolute;
            top: var(--cedula-offset-y);
            left: var(--cedula-offset-x);
            width: var(--cedula-width);
            height: var(--cedula-height);
            overflow: hidden;
        }

        .cedula-map {
            position: absolute;
            inset: 0 auto auto 0;
            width: var(--design-width);
            height: var(--design-height);
            transform: scale(var(--fit-x), var(--fit-y));
            transform-origin: top left;
        }

        .field {
            position: absolute;
            font-size: 26px;
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

            html,
            body {
                width: var(--paper-width);
                height: var(--paper-height);
            }

            body {
                overflow: hidden;
            }

            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>

    <button class="print-btn" onclick="window.print()">Print</button>

    <div class="sheet">
        <div class="page">
            <div class="cedula-map">

                <!-- YEAR -->
                <div class="field bold" style="top: 158px; left: 0px;">
                    <?= e($yearIssued) ?>
                </div>

                <!-- PLACE OF ISSUE -->
                <div class="field" style="top: 158px; left: 170px;">
                    <?= e($cedula['place_of_issue'] ?? '') ?>
                </div>

                <!-- DATE ISSUED -->
                <div class="field" style="top: 138px; left: 690px;">
                    <?= e($issuedDate) ?>
                </div>

                <!-- SURNAME -->
                <div class="field" style="top: 200px; left: 150px;">
                    <?= e($cedula['surname'] ?? '') ?>
                </div>

                <!-- FIRST NAME -->
                <div class="field" style="top: 200px; left: 405px;">
                    <?= e($cedula['first_name'] ?? '') ?>
                </div>

                <!-- MIDDLE NAME -->
                <div class="field" style="top: 200px; left: 620px;">
                    <?= e($cedula['middle_name'] ?? '') ?>
                </div>

                <!-- ADDRESS -->
                <div class="field" style="top: 251px; left: 140px;">
                    <?= e($cedula['address'] ?? '') ?>
                </div>

                <!-- CITIZENSHIP -->
                <div class="field" style="top: 310px; left: 52px;">
                    <?= e($cedula['citizenship'] ?? '') ?>
                </div>

                <!-- PLACE OF BIRTH -->
                <div class="field" style="top: 320px; left: 640px;">
                    <?= e($cedula['birth_place'] ?? '') ?>
                </div>

                <!-- DATE OF BIRTH -->
                <div class="field" style="top: 370px; left: 885px;">
                    <?= e($birthDate) ?>
                </div>

                <!-- HEIGHT -->
                <div class="field" style="top: 310px; left: 1130px;">
                    <?= e($cedula['height'] ?? '') ?>
                </div>

                <!-- WEIGHT -->
                <div class="field" style="top: 370px; left: 1130px;">
                    <?= e($cedula['weight'] ?? '') ?>
                </div>

                <!-- OCCUPATION -->
                <div class="field" style="top: 410px; left: 55px;">
                    <?= e($cedula['occupation'] ?? '') ?>
                </div>

                <!-- BASIC TAX -->
                <div class="field" style="top: 465px; left: 1150px;">
                    <?= e(number_format(5, 2)) ?>
                </div>
                <!-- addtional TAX  (1 every 1k-->
                <div class="field" style="top: 600px; left: 1230px;">
                    <?= e(number_format((float) ($cedula['additional_tax_profession'] ?? 0), 2)) ?>
                </div>




                <!-- TOTAL -->
                <div class="field" style="top: 705px; left: 1230px;">
                    <?= e(number_format((float) ($cedula['amount'] ?? 0), 2)) ?>
                </div>

                <!-- INTEREST -->
                <div class="field" style="top: 776px; left: 1230px;">
                    <?= e(number_format((float) ($cedula['interest'] ?? 0), 2)) ?>
                </div>

                <!-- TOTAL AMOUNT PAID -->
                <div class="field" style="top: 850px; left: 1215px;">
                    <?= e(number_format((float) ($cedula['amount'] ?? 0) + (float) ($cedula['interest'] ?? 0), 2)) ?>
                </div>

                <!-- AMOUNT IN WORDS -->
                <div class="field" style="top: 930px; left: 950px;">
                    <?= e($cedula['amount_in_words'] ?? '') ?>
                </div>
            </div>
        </div>
    </div>

</body>

</html>