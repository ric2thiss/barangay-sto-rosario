<?php
include "../../config/database.php";
include "../../config/session.php";

// Handle DELETE request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM disbursements WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: list.php?deleted=1");
    } else {
        header("Location: list.php?error=1");
    }
    exit();
}

// Handle INSERT or UPDATE request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isUpdate = isset($_POST['action']) && $_POST['action'] === 'update' && !empty($_POST['id']);
    $accountingEntries = [];
    $accountNames = (array) ($_POST['account_name'] ?? []);
    $accountCodes = (array) ($_POST['account_code'] ?? []);
    $accountDebits = (array) ($_POST['account_debit'] ?? []);
    $accountCredits = (array) ($_POST['account_credit'] ?? []);

    foreach ($accountNames as $index => $name) {
        $name = trim((string) $name);
        $code = trim((string) ($accountCodes[$index] ?? ''));
        $debit = trim((string) ($accountDebits[$index] ?? ''));
        $credit = trim((string) ($accountCredits[$index] ?? ''));

        if ($name === '' && $code === '' && $debit === '' && $credit === '') {
            continue;
        }

        $accountingEntries[] = [
            'name' => $name,
            'code' => $code,
            'debit' => $debit,
            'credit' => $credit
        ];
    }

    $accountingEntriesJson = $accountingEntries ? json_encode($accountingEntries) : '';
    $birVatType = $_POST['bir_vat_type'] ?? '';
    $birGross = isset($_POST['bir_gross']) ? floatval($_POST['bir_gross']) : 0;
    $birWithholdingA = isset($_POST['bir_withholding_a']) ? floatval($_POST['bir_withholding_a']) : 0;
    $birWithholdingB = isset($_POST['bir_withholding_b']) ? floatval($_POST['bir_withholding_b']) : 0;

    if ($isUpdate) {
        $stmt = $conn->prepare(" 
        UPDATE disbursements SET
            disburse_date = ?,
            check_no = ?,
            or_no = ?,
            received_date = ?,
            payee = ?,
            payee_address = ?,
            payee_tin = ?,
            dv_no = ?,
            amount = ?,
            fund = ?,
            payroll = ?,
            bir = ?,
            bir_vat_type = ?,
            bir_gross = ?,
            bir_withholding_a = ?,
            bir_withholding_b = ?,
            purpose = ?,
            release_amount = ?,
            accounting_entries = ?,
            signatory_a = ?,
            signatory_b = ?,
            signatory_c = ?,
            signatory_prepared_by = ?,
            signatory_checked_by = ?,
            signatory_approved_by = ?,
            signatory_received_by = ?
        WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssssssdssssdddsdssssssssi",
            $_POST['date'],
            $_POST['check_no'],
            $_POST['or_no'],
            $_POST['received_date'],
            $_POST['payee'],
            $_POST['payee_address'],
            $_POST['payee_tin'],
            $_POST['dv_no'],
            $_POST['amount'],
            $_POST['fund'],
            $_POST['payroll'],
            $_POST['bir'],
            $birVatType,
            $birGross,
            $birWithholdingA,
            $birWithholdingB,
            $_POST['purpose'],
            $_POST['release'],
            $accountingEntriesJson,
            $_POST['signatory_a'],
            $_POST['signatory_b'],
            $_POST['signatory_c'],
            $_POST['signatory_prepared_by'],
            $_POST['signatory_checked_by'],
            $_POST['signatory_approved_by'],
            $_POST['signatory_received_by'],
            $_POST['id']
        );

        $stmt->execute();

        header("Location: list.php?updated=1");
    } else {
        $stmt = $conn->prepare(" 
        INSERT INTO disbursements
            (disburse_date, check_no, or_no, received_date, payee, payee_address, payee_tin, dv_no, amount, fund, payroll, bir, bir_vat_type, bir_gross, bir_withholding_a, bir_withholding_b, purpose, release_amount, accounting_entries, signatory_a, signatory_b, signatory_c, signatory_prepared_by, signatory_checked_by, signatory_approved_by, signatory_received_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssssdssssdddsdssssssss",
            $_POST['date'],
            $_POST['check_no'],
            $_POST['or_no'],
            $_POST['received_date'],
            $_POST['payee'],
            $_POST['payee_address'],
            $_POST['payee_tin'],
            $_POST['dv_no'],
            $_POST['amount'],
            $_POST['fund'],
            $_POST['payroll'],
            $_POST['bir'],
            $birVatType,
            $birGross,
            $birWithholdingA,
            $birWithholdingB,
            $_POST['purpose'],
            $_POST['release'],
            $accountingEntriesJson,
            $_POST['signatory_a'],
            $_POST['signatory_b'],
            $_POST['signatory_c'],
            $_POST['signatory_prepared_by'],
            $_POST['signatory_checked_by'],
            $_POST['signatory_approved_by'],
            $_POST['signatory_received_by']
        );

        $stmt->execute();

        header("Location: list.php");
    }
    exit;
}






