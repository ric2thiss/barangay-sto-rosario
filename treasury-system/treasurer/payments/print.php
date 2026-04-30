<?php
include "../../config/database.php";
include "../../config/session.php";

$paymentId = intval($_GET['id'] ?? 0);
if ($paymentId <= 0) {
    header("Location: list.php?error=Invalid payment ID.");
    exit;
}

$stmt = $conn->prepare("
    SELECT payments.*, users.name AS received_by_name
    FROM payments
    LEFT JOIN users ON payments.received_by = users.id
    WHERE payments.id = ?
");
$stmt->bind_param("i", $paymentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: list.php?error=Payment not found.");
    exit;
}

$payment = $result->fetch_assoc();
$stmt->close();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value)
{
    return number_format((float)$value, 2);
}

$paymentDate = !empty($payment['payment_date'])
    ? date('m/d/Y', strtotime($payment['payment_date']))
    : date('m/d/Y');

$amount = (float)($payment['amount'] ?? 0);
$birTax = (float)($payment['bir_tax'] ?? 0);
$total = $amount + $birTax;

$receiptNo   = trim((string)($payment['receipt_no'] ?? ''));
$payerName   = trim((string)($payment['payer_name'] ?? ''));
$serviceType = trim((string)($payment['service_type'] ?? ''));
$purpose     = trim((string)($payment['purpose'] ?? ''));
$remarks     = trim((string)($payment['remarks'] ?? ''));
$receivedBy  = trim((string)($payment['received_by_name'] ?? ''));

// optional splits for better fitting
$descLine1 = $purpose;
$descLine2 = $remarks;

// combine if needed
if ($descLine2 === '') {
    $descLine2 = $serviceType;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print Official Receipt</title>
    <style>
        @page {
            size: 95mm 165mm;
            /* adjust to your actual OR paper */
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f3f3f3;
        }

        .toolbar {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 9999;
            display: flex;
            gap: 8px;
        }

        .btn {
            border: 1px solid #333;
            background: #1f3a93;
            color: #fff;
            padding: 8px 12px;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.back {
            background: #666;
        }

        .sheet {
            position: relative;
            width: 95mm;
            /* adjust */
            height: 165mm;
            /* adjust */
            margin: 10px auto;
            background: #fff;
            overflow: hidden;
        }

        .field {
            position: absolute;
            font-size: 9px;
            line-height: 1;
            white-space: nowrap;
            color: #000;
        }

        .small {
            font-size: 8px;
        }

        .tiny {
            font-size: 7px;
        }

        .bold {
            font-weight: 700;
        }

        .wrap {
            white-space: normal;
            line-height: 1.15;
            word-break: break-word;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                margin: 0 auto;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <button class="btn" onclick="window.print()">Print</button>
        <a href="list.php" class="btn back">Back</a>
    </div>

    <div class="sheet">

        <!-- RECEIPT NUMBER -->
        <div class="field bold" style="top: 27mm; left: 61mm; font-size: 10px; letter-spacing: 1px;">
            <?= e($receiptNo) ?>
        </div>

        <!-- RECEIVED FROM -->
        <div class="field" style="top: 37.5mm; left: 24mm; width: 42mm;">
            <?= e($payerName) ?>
        </div>

        <!-- DATE -->
        <div class="field" style="top: 37.5mm; left: 72mm; width: 18mm;">
            <?= e($paymentDate) ?>
        </div>

        <!-- TIN OF BUYER -->
        <div class="field" style="top: 42.8mm; left: 23mm; width: 33mm;">
            <?= e($payment['payer_tin'] ?? '') ?>
        </div>

        <!-- OSCA/PWD NO -->
        <div class="field" style="top: 42.8mm; left: 63mm; width: 24mm;">
            <?= e($payment['osca_pwd_no'] ?? '') ?>
        </div>

        <!-- ADDRESS -->
        <div class="field" style="top: 48.3mm; left: 17mm; width: 72mm;">
            <?= e($payment['payer_address'] ?? '') ?>
        </div>

        <!-- BUSINESS STYLE -->
        <div class="field" style="top: 53.7mm; left: 24mm; width: 33mm;">
            <?= e($serviceType) ?>
        </div>

        <!-- SIGNATURE -->
        <div class="field" style="top: 53.7mm; left: 62mm; width: 24mm;">
            <?= e($receivedBy) ?>
        </div>

        <!-- DESCRIPTION LINES -->
        <div class="field wrap" style="top: 69mm; left: 4mm; width: 42mm; height: 6mm;">
            <?= e($descLine1) ?>
        </div>

        <div class="field wrap" style="top: 75mm; left: 4mm; width: 42mm; height: 6mm;">
            <?= e($descLine2) ?>
        </div>

        <!-- QTY -->
        <div class="field center" style="top: 69mm; left: 47.5mm; width: 7mm;">
            <?= e($payment['qty'] ?? '1') ?>
        </div>

        <!-- UNIT -->
        <div class="field center" style="top: 69mm; left: 57mm; width: 8mm;">
            <?= e($payment['unit'] ?? '') ?>
        </div>

        <!-- UNIT PRICE -->
        <div class="field right" style="top: 69mm; left: 66.5mm; width: 10mm;">
            <?= e(money($amount)) ?>
        </div>

        <!-- AMOUNT -->
        <div class="field right" style="top: 69mm; left: 79mm; width: 12mm;">
            <?= e(money($amount)) ?>
        </div>

        <!-- SELLER INFO -->
        <div class="field tiny" style="top: 117.5mm; left: 4mm; width: 36mm;">
            <?= e($payment['seller_registered_name'] ?? 'Barangay Sto. Rosario') ?>
        </div>

        <div class="field tiny" style="top: 126.5mm; left: 4mm; width: 36mm;">
            <?= e($payment['seller_tin'] ?? '') ?>
        </div>

        <div class="field tiny" style="top: 135mm; left: 4mm; width: 36mm;">
            <?= e($payment['seller_trade_name'] ?? '') ?>
        </div>

        <div class="field tiny" style="top: 143.8mm; left: 4mm; width: 36mm;">
            <?= e($payment['seller_address'] ?? '') ?>
        </div>

        <div class="field tiny" style="top: 152.5mm; left: 4mm; width: 36mm;">
            <?= e($payment['seller_business_style'] ?? '') ?>
        </div>

        <!-- TOTALS RIGHT SIDE -->
        <div class="field right" style="top: 118mm; left: 73mm; width: 16mm;">
            <?= e(money($amount)) ?>
        </div>

        <div class="field right" style="top: 126.5mm; left: 73mm; width: 16mm;">
            <?= e(money((float)($payment['sc_pwd_discount'] ?? 0))) ?>
        </div>

        <div class="field right" style="top: 135mm; left: 73mm; width: 16mm;">
            <?= e(money((float)($payment['total_due'] ?? $amount))) ?>
        </div>

        <div class="field right" style="top: 143.7mm; left: 73mm; width: 16mm;">
            <?= e(money($birTax)) ?>
        </div>

        <div class="field right bold" style="top: 152.2mm; left: 73mm; width: 16mm;">
            <?= e(money($total)) ?>
        </div>

        <div class="field right" style="top: 161mm; left: 73mm; width: 16mm;">
            <?= e(money((float)($payment['sales_subject_vat'] ?? 0))) ?>
        </div>

        <div class="field right" style="top: 166.2mm; left: 73mm; width: 16mm;">
            <?= e(money((float)($payment['vat_exempt_sales'] ?? 0))) ?>
        </div>

    </div>

</body>

</html>




