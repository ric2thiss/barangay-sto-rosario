<?php
include "../../config/database.php";
include "../../config/session.php";

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM disbursements WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list.php");
    exit;
}

$disbursement = $result->fetch_assoc();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value)
{
    return number_format((float) $value, 2);
}

function parseAccountingEntries($text)
{
    $rows = [];
    $text = trim((string) $text);

    if ($text === '') {
        return $rows;
    }

    if ($text[0] === '[' || $text[0] === '{') {
        $decoded = json_decode($text, true);

        if (is_array($decoded)) {
            if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
                $decoded = [$decoded];
            }

            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $rows[] = [
                    'name'   => $row['name'] ?? '',
                    'code'   => $row['code'] ?? '',
                    'debit'  => $row['debit'] ?? '',
                    'credit' => $row['credit'] ?? ''
                ];
            }

            if (!empty($rows)) {
                return $rows;
            }
        }
    }

    $lines = preg_split('/\R/', $text);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        $rows[] = [
            'name'   => $parts[0] ?? '',
            'code'   => $parts[1] ?? '',
            'debit'  => $parts[2] ?? '',
            'credit' => $parts[3] ?? ''
        ];
    }

    return $rows;
}

$accountingRows = parseAccountingEntries($disbursement['accounting_entries'] ?? '');

$disburseDate = !empty($disbursement['disburse_date'])
    ? date('F j-Y', strtotime($disbursement['disburse_date']))
    : '';

$receivedDate = !empty($disbursement['received_date'])
    ? date('F j-Y', strtotime($disbursement['received_date']))
    : $disburseDate;

$amount = (float) ($disbursement['release_amount'] ?? 0);
$amountFormatted = money($amount);

$birVatType = $disbursement['bir_vat_type'] ?? '';
$birGross = isset($disbursement['bir_gross']) ? (float) $disbursement['bir_gross'] : 0.0;
$birWithholdingA = isset($disbursement['bir_withholding_a']) ? (float) $disbursement['bir_withholding_a'] : 0.0;
$birWithholdingB = isset($disbursement['bir_withholding_b']) ? (float) $disbursement['bir_withholding_b'] : 0.0;
$birTotal = isset($disbursement['bir']) ? (float) $disbursement['bir'] : 0.0;

$hasBirBreakdown =
    $birGross > 0 ||
    $birWithholdingA > 0 ||
    $birWithholdingB > 0 ||
    $birTotal > 0 ||
    $birVatType !== '';

switch ($birVatType) {
    case 'Reg. VAT':
        $birLabelA = '1%';
        $birLabelB = '5%';
        break;
    case 'Non-VAT Services':
        $birLabelA = '2%';
        $birLabelB = '3%';
        break;
    default:
        $birLabelA = '1%';
        $birLabelB = '5%';
        break;
}

$bankName = trim((string)($disbursement['bank_name'] ?? 'Land Bank'));
$blankRowsNeeded = max(0, 3 - count($accountingRows));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Disbursement Voucher -
        <?= e($disbursement['dv_no'] ?? '') ?>
    </title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        :root {
            --border: #111;
            --shade: #efb083;
        }

        @page {
            size: A4 portrait;
            margin: 4mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #e9e9e9;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }

        body {
            padding: 10px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 10px;
        }

        .btn {
            border: 1px solid #333;
            background: #1f3a93;
            color: #fff;
            padding: 8px 12px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        .btn.back {
            background: #5b6b8a;
        }

        .sheet {
            width: 202mm;
            margin: 0 auto;
            background: #fff;
            border: 2px solid var(--border);
            overflow: hidden;
        }

        .header {
            display: grid;
            grid-template-columns: 78px 1fr 78px;
            align-items: center;
            border-bottom: 2px solid var(--border);
            padding: 2px 6px 1px;
        }

        .logo-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .header-text {
            text-align: center;
            line-height: 1.12;
        }

        .header-text .line {
            font-size: 8px;
            font-weight: 400;
        }

        .header-text .barangay {
            margin-top: 4px;
            font-size: 8px;
            font-weight: 700;
        }

        .title {
            text-align: center;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 1px;
            line-height: 1;
            padding: 1px 0 3px;
            border-bottom: 2px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td,
        th {
            border: 1px solid var(--border);
            padding: 1px 4px;
            vertical-align: top;
        }

        .meta-table td {
            font-size: 10px;
            line-height: 1;
            height: 20px;
            padding: 1px 4px;
        }

        .meta-table .label,
        .meta-table .right-label,
        .received-table .mini-label,
        .section-title,
        .accounting-table th {
            background: var(--shade);
            font-weight: 700;
        }

        .meta-table .label {
            width: 68px;
            white-space: nowrap;
        }

        .meta-table .right-label {
            width: 74px;
            white-space: nowrap;
        }

        .meta-table .value {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: clip;
            font-size: 9px;
        }

        .particulars-head th {
            background: #fff;
            font-size: 16px;
            font-weight: 900;
            text-align: center;
            padding: 3px 2px;
        }

        .particulars-cell {
            height: 105px;
            font-size: 9px;
            line-height: 1.15;
            padding-top: 2px;
        }

        .particulars-text {
            white-space: pre-line;
            min-height: 28px;
        }

        .support-line {
            margin-top: 4px;
        }

        .bir-lines {
            margin-top: 2px;
            font-size: 9px;
        }

        .bir-line {
            display: grid;
            grid-template-columns: 1fr 82px;
            gap: 6px;
            align-items: end;
            margin-top: 1px;
        }

        .bir-line .right {
            text-align: right;
        }

        .bir-line .under {
            border-top: 1px solid #666;
            padding-top: 1px;
        }

        .amount-box {
            font-size: 9px;
            line-height: 1.1;
            height: 105px;
            position: relative;
            padding-top: 6px;
        }

        .amount-box .currency {
            display: block;
            margin-top: 4px;
        }

        .amount-box .value {
            display: block;
            text-align: right;
            font-size: 9px;
        }

        .amount-box .line {
            border-top: 1px solid #666;
            margin: 2px 0;
        }

        .cert-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .cert-box {
            min-height: 86px;
            border-right: 1px solid var(--border);
            padding: 4px 4px 2px;
            font-size: 8px;
            line-height: 1.28;
            overflow: hidden;
        }

        .cert-box:last-child {
            border-right: 0;
        }

        .sign-name {
            margin-top: 10px;
            text-align: center;
            font-weight: 900;
            font-size: 10px;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .sign-role {
            text-align: center;
            font-size: 7px;
            font-weight: 700;
            margin-top: 3px;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
        }

        .sign-date {
            margin-top: 4px;
            text-align: center;
            font-size: 8px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 900;
            line-height: 1;
            padding: 2px 4px;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .received-table td {
            font-size: 9px;
            line-height: 1;
            height: 18px;
            padding: 1px 4px;
        }

        .received-table .mini-label {
            width: 95px;
            white-space: nowrap;
        }

        .received-name {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 10px;
            white-space: nowrap;
            overflow: hidden;
            font-size: 8px;
        }

        .received-caption {
            text-align: center;
            font-weight: 700;
            font-size: 9px;
            margin-top: 1px;
        }

        .received-value-center {
            text-align: center;
        }

        .received-value-right {
            text-align: right;
        }

        .accounting-table th {
            font-size: 11px;
            font-weight: 900;
            text-align: center;
            line-height: 1;
            padding: 2px 2px;
        }

        .accounting-table td {
            font-size: 9px;
            line-height: 1;
            height: 18px;
            padding: 1px 4px;
        }

        .accounting-table .center {
            text-align: center;
        }

        .accounting-table .money {
            text-align: right;
            white-space: nowrap;
        }

        .footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .footer-left,
        .footer-right {
            padding: 6px 4px 4px;
            position: relative;
        }

        .footer-right {
            border-left: 1px solid var(--border);
        }

        .footer-left {
            display: grid;
            grid-template-rows: auto auto;
            row-gap: 10px;
        }

        .footer-label {
            font-size: 10px;
            margin-bottom: 4px;
        }

        .footer-name {
            text-align: center;
            font-size: 11px;
            font-weight: 900;
            text-decoration: underline;
            text-underline-offset: 2px;
            margin-top: 4px;
        }

        .footer-role {
            text-align: center;
            font-size: 9px;
            margin-top: 2px;
            font-weight: 700;
        }

        .footer-date {
            text-align: center;
            font-size: 10px;
            margin-top: 8px;
        }

        .footer-date-line {
            display: inline-block;
            min-width: 110px;
            border-bottom: 1px solid #666;
            height: 10px;
            vertical-align: bottom;
        }

        @media print {

            html,
            body {
                background: #fff;
                width: 210mm;
            }

            body {
                padding: 0;
                margin: 0;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                width: 202mm;
                margin: 0 auto;
                border: 2px solid var(--border);
                box-shadow: none;
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
        <div class="header">
            <div class="logo-wrap">
                <img src="../../assets/images/logo2.jpeg" alt="Logo" class="logo">
            </div>

            <div class="header-text">
                <div class="line">REPUBLIC OF THE PHILIPPINES</div>
                <div class="line">PROVINCE OF AGUSAN DEL NORTE</div>
                <div class="line">MUNICIPALITY OF MAGALLANES</div>
                <div class="barangay">BARANGAY STO ROSARIO / TIN NO.: 004-375-387- 000</div>
            </div>

            <div class="logo-wrap">
                <img src="../../assets/images/logo.jpg" alt="Logo" class="logo">
            </div>
        </div>

        <div class="title">DISBURSEMENT VOUCHER</div>

        <table class="meta-table">
            <colgroup>
                <col style="width:68px;">
                <col>
                <col style="width:74px;">
                <col style="width:120px;">
            </colgroup>
            <tr>
                <td class="label">PAYEE:</td>
                <td class="value">
                    <?= e($disbursement['payee'] ?? '') ?>
                </td>
                <td class="right-label">DV NO.:</td>
                <td class="value">
                    <?= e($disbursement['dv_no'] ?? '') ?>
                </td>
            </tr>
            <tr>
                <td class="label">ADDRESS</td>
                <td class="value">
                    <?= e($disbursement['payee_address'] ?? '') ?>
                </td>
                <td class="right-label">DATE:</td>
                <td class="value">
                    <?= e($disbursement['disburse_date'] ? date('F j-Y', strtotime($disbursement['disburse_date'])) : '') ?>
                </td>
            </tr>
            <tr>
                <td class="label">TIN NO:</td>
                <td class="value">
                    <?= e($disbursement['payee_tin'] ?? '') ?>
                </td>
                <td class="right-label">FUND:</td>
                <td class="value">
                    <?= e($disbursement['fund'] ?? '') ?>
                </td>
            </tr>
        </table>

        <table>
            <colgroup>
                <col>
                <col style="width:190px;">
            </colgroup>
            <thead class="particulars-head">
                <tr>
                    <th>PARTICULARS</th>
                    <th>AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="particulars-cell">
                        <div class="particulars-text">
                            <?= nl2br(e($disbursement['purpose'] ?? '')) ?>
                        </div>

                        <?php if ($hasBirBreakdown): ?>
                        <div class="bir-lines">
                            <?php if ($birTotal > 0): ?>
                            <div class="bir-line">
                                <span></span>
                                <span
                                    class="right"><?= money($birTotal) ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="support-line">Supporting papers hereto attached covering the amount of .</div>

                            <?php if ($birGross > 0): ?>
                            <div class="bir-line">
                                <span><?= e($birLabelA) ?></span>
                                <span
                                    class="right"><?= money($birGross) ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if ($birWithholdingA > 0): ?>
                            <div class="bir-line">
                                <span><?= e($birLabelA) ?></span>
                                <span
                                    class="right"><?= money($birWithholdingA) ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if ($birWithholdingB > 0): ?>
                            <div class="bir-line">
                                <span><?= e($birLabelB) ?></span>
                                <span
                                    class="right under"><?= money($birWithholdingB) ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if ($birTotal > 0): ?>
                            <div class="bir-line">
                                <span></span>
                                <span
                                    class="right"><?= money($birTotal) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="support-line">Supporting papers hereto attached covering the amount of .</div>
                        <?php endif; ?>
                    </td>

                    <td class="amount-box">
                        <span class="currency">Php</span>
                        <span
                            class="value"><?= $amountFormatted ?></span>

                        <?php if ($birTotal > 0): ?>
                        <span
                            class="value"><?= money($birTotal) ?></span>
                        <div class="line"></div>
                        <span
                            class="value"><?= money($amount - $birTotal) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="cert-row">
            <div class="cert-box">
                <strong>A.</strong> Certified as to availability<br>
                of appropriation for obligation.
                <div class="sign-name">
                    <?= e($disbursement['signatory_a'] ?? '') ?>
                </div>
                <div class="sign-role">CHAIRMAN, COMMITTEE ON APPROPRIATION</div>
                <div class="sign-date">
                    DATE:&nbsp;&nbsp;&nbsp;<?= e($disburseDate) ?>
                </div>
            </div>

            <div class="cert-box">
                <strong>B.</strong> Certified as to availability of funds<br>
                for the purpose and completeness and<br>
                propriety of supporting documents.
                <div class="sign-name">
                    <?= e($disbursement['signatory_b'] ?? '') ?>
                </div>
                <div class="sign-role">BARANGAY TREASURER</div>
                <div class="sign-date">
                    DATE:&nbsp;&nbsp;&nbsp;<?= e($disburseDate) ?>
                </div>
            </div>

            <div class="cert-box">
                <strong>C.</strong> Certified as to validity propriety<br>
                and legality of claim &amp; approved<br>
                for payment .
                <div class="sign-name">
                    <?= e($disbursement['signatory_c'] ?? '') ?>
                </div>
                <div class="sign-role">PUNONG BARANGAY</div>
                <div class="sign-date">
                    DATE:&nbsp;&nbsp;&nbsp;<?= e($disburseDate) ?>
                </div>
            </div>
        </div>

        <div class="section-title">D. RECEIVED PAYMENT</div>

        <table class="received-table">
            <colgroup>
                <col>
                <col style="width:95px;">
                <col style="width:115px;">
            </colgroup>
            <tr>
                <td rowspan="4" style="vertical-align: bottom;">
                    <div class="received-name">
                        <?= e($disbursement['signatory_received_by'] ?? $disbursement['payee'] ?? '') ?>
                    </div>
                    <div class="received-caption">SIGNATURE OVER PRINTED NAME</div>
                </td>
                <td class="mini-label">CHECK NO.</td>
                <td class="received-value-right">
                    <?= e($disbursement['check_no'] ?? '') ?>
                </td>
            </tr>
            <tr>
                <td class="mini-label">BANK NAME</td>
                <td class="received-value-center"><?= e($bankName) ?>
                </td>
            </tr>
            <tr>
                <td class="mini-label">OR. NO.</td>
                <td><?= e($disbursement['or_no'] ?? '') ?>
                </td>
            </tr>
            <tr>
                <td class="mini-label">DATE</td>
                <td><?= e($receivedDate) ?></td>
            </tr>
        </table>

        <div class="section-title">E. ACCOUNTING ENTRIES</div>

        <table class="accounting-table">
            <colgroup>
                <col>
                <col style="width:95px;">
                <col style="width:115px;">
                <col style="width:105px;">
            </colgroup>
            <thead>
                <tr>
                    <th>ACCOUNT NAME</th>
                    <th>ACCOUNT CODE</th>
                    <th>DEBIT</th>
                    <th>CREDIT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accountingRows as $row): ?>
                <tr>
                    <td class="center">
                        <?= e($row['name']) ?>
                    </td>
                    <td class="center">
                        <?= e($row['code']) ?>
                    </td>
                    <td class="money">
                        <?= e($row['debit']) ?>
                    </td>
                    <td class="money">
                        <?= e($row['credit']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php for ($i = 0; $i < $blankRowsNeeded; $i++): ?>
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="footer">
            <div class="footer-left">
                <div>
                    <div class="footer-label">PREPARED BY:</div>
                    <div class="footer-name">
                        <?= e($disbursement['signatory_prepared_by'] ?? '') ?>
                    </div>
                    <div class="footer-role">BARANGAY TREASURER</div>
                </div>

                <div>
                    <div class="footer-label">CHECKED BY:</div>
                    <div class="footer-name">
                        <?= e($disbursement['signatory_checked_by'] ?? '') ?>
                    </div>
                    <div class="footer-role">BARANGAY BOOKKEEPER</div>
                </div>
            </div>

            <div class="footer-right">
                <div class="footer-label">APPROVED BY:</div>
                <div class="footer-name">
                    <?= e($disbursement['signatory_approved_by'] ?? '') ?>
                </div>
                <div class="footer-role">MUNICIPAL ACCOUNTANT</div>
                <div class="footer-date">DATE: <span class="footer-date-line"></span></div>
            </div>
        </div>
    </div>
</body>

</html>





