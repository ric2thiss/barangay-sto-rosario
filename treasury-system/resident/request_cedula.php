<?php
include "../config/database.php";
include "../config/resident_session.php";

function build_resident_name(array $resident, string $middleMode = 'full'): string
{
    $first = trim($resident['first_name'] ?? '');
    $middle = trim($resident['middle_name'] ?? '');
    $surname = trim($resident['surname'] ?? '');
    $suffix = trim($resident['suffix'] ?? '');

    $parts = [];
    if ($first !== '') {
        $parts[] = $first;
    }

    if ($middle !== '') {
        if ($middleMode === 'initial') {
            $parts[] = strtoupper(substr($middle, 0, 1)) . '.';
        } elseif ($middleMode === 'full') {
            $parts[] = $middle;
        }
    }

    if ($surname !== '') {
        $parts[] = $surname;
    }

    if ($suffix !== '') {
        $parts[] = $suffix;
    }

    return trim(implode(' ', $parts));
}

function build_cedula_full_name(array $resident): string
{
    $surname = trim($resident['surname'] ?? '');
    $first = trim($resident['first_name'] ?? '');
    $middle = trim($resident['middle_name'] ?? '');

    if ($surname !== '' && ($first !== '' || $middle !== '')) {
        return trim($surname . ', ' . $first . ($middle !== '' ? ' ' . $middle : ''));
    }

    return build_resident_name($resident, 'full');
}

function build_address(array $resident): string
{
    $parts = [];
    if (!empty($resident['household_no'])) {
        $parts[] = 'Household No. ' . $resident['household_no'];
    }
    if (!empty($resident['purok'])) {
        $parts[] = 'Purok ' . $resident['purok'];
    }
    if (!empty($resident['barangay'])) {
        $parts[] = $resident['barangay'];
    }
    if (!empty($resident['municipality'])) {
        $parts[] = $resident['municipality'];
    }
    if (!empty($resident['province'])) {
        $parts[] = $resident['province'];
    }

    return implode(', ', $parts);
}

function normalize_civil_status(?string $status): string
{
    $status = trim((string) $status);
    if ($status === 'Divorced') {
        return 'Separated';
    }

    return $status;
}

function normalize_citizenship(?string $nationality): string
{
    $nationality = trim((string) $nationality);
    if ($nationality === 'Filipino' || $nationality === '') {
        return 'Filipino';
    }

    if ($nationality === 'Dual Citizen' || $nationality === 'Foreign National') {
        return $nationality;
    }

    return 'Foreign National';
}

function compute_age_from_birthdate(?string $birthDate): int
{
    if (empty($birthDate)) {
        return 0;
    }

    $birth = new DateTime($birthDate);
    $today = new DateTime('today');

    return (int) $birth->diff($today)->y;
}

function calculate_profession_tax(float $taxableAmount): float
{
    return max(0, $taxableAmount / 1000);
}

$userType = $_SESSION['user_type'] ?? 'resident';
$residentId = intval($_SESSION['resident_id'] ?? 0);

// 1. Fetch Primary Profile
if ($userType === 'official') {
    $query = "SELECT id, first_name, middle_name, surname, suffix, username, barangay, municipality, province, purok, household_no, birthdate, birthplace, age, sex, civil_status, nationality, occupation, occupation_type, annual_income, status as account_status, height, weight FROM " . DB_PROFILING . ".barangay_official WHERE id = ? LIMIT 1";
} else {
    $query = "SELECT id, first_name, middle_name, surname, suffix, username, barangay, municipality, province, purok, household_no, birthdate, birthplace, age, sex, civil_status, nationality, occupation, occupation_type, annual_income, account_status, height, weight FROM " . DB_PROFILING . ".residents WHERE id = ? LIMIT 1";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $residentId);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resident) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session");
    exit;
}

// 2. Fallback for Height/Weight (If Official might have data in Residents table)
if ($userType === 'official' && (empty($resident['height']) || empty($resident['weight']))) {
    $stmt = $conn->prepare("SELECT height, weight FROM " . DB_PROFILING . ".residents WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $resident['username']);
    $stmt->execute();
    $fallback = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($fallback) {
        if (empty($resident['height'])) $resident['height'] = $fallback['height'];
        if (empty($resident['weight'])) $resident['weight'] = $fallback['weight'];
    }
}

$status = strtolower(trim($resident['account_status'] ?? 'active'));
if ($status !== 'active') {
    session_unset();
    session_destroy();
    header("Location: login.php?error=account");
    exit;
}

// Get last cedula number
$lastCedula = $conn->query("SELECT cedula_no FROM cedula ORDER BY id DESC LIMIT 1")->fetch_assoc();
$nextCedula = $lastCedula && isset($lastCedula['cedula_no']) ? (intval($lastCedula['cedula_no']) + 1) : 2025001;

$fullNameDefault = build_cedula_full_name($resident);
$addressDefault = build_address($resident);
$birthDateDefault = !empty($resident['birthdate']) ? date('Y-m-d', strtotime($resident['birthdate'])) : '';
$ageDefault = !empty($resident['age']) ? intval($resident['age']) : compute_age_from_birthdate($birthDateDefault);
$civilStatusDefault = normalize_civil_status($resident['civil_status'] ?? '');
$citizenshipDefault = normalize_citizenship($resident['nationality'] ?? '');
$occupationDefault = trim($resident['occupation'] ?? '') !== '' ? $resident['occupation'] : ($resident['occupation_type'] ?? '');
$placeOfIssueDefault = trim(($resident['municipality'] ?? '') . (empty($resident['province']) ? '' : ', ' . $resident['province']));
$annualIncomeDefault = isset($resident['annual_income']) && floatval($resident['annual_income']) > 0
    ? number_format((float) $resident['annual_income'], 2, '.', '')
    : '';
$additionalProfessionDefault = calculate_profession_tax((float) ($resident['annual_income'] ?? 0));
$heightDefault = !empty($resident['height'])
    ? number_format((float) $resident['height'], 2, '.', '')
    : '';
$weightDefault = !empty($resident['weight'])
    ? number_format((float) $resident['weight'], 2, '.', '')
    : '';

$fullName = build_resident_name($resident, 'full');

$success = '';
$error = '';
$warning = '';
$hasPendingRequest = false;
$isEdit = isset($_GET['edit']);
$cedulaToEdit = null;
$pendingCedulaPaymentId = null;
$rejectionRemarks = '';

// Check for pending cedula requests - resident cannot request another one
$pendingCheckStmt = $conn->prepare("SELECT id FROM payment_status WHERE resident_id = ? AND certificate_type = 'Cedula' AND payment_status = 'pending' LIMIT 1");
$pendingCheckStmt->bind_param("i", $residentId);
$pendingCheckStmt->execute();
$pendingCheckResult = $pendingCheckStmt->get_result();
if ($pendingCheckResult->num_rows > 0) {
    $hasPendingRequest = true;
    $warning = 'You already have a pending cedula request. Please wait for the treasurer to process it or pay the required amount.';
}
$pendingCheckStmt->close();

if ($isEdit) {
    $pendingStmt = $conn->prepare("SELECT id, rejection_remarks FROM payment_status WHERE resident_id = ? AND certificate_type = 'Cedula' AND payment_status = 'rejected' ORDER BY created_at DESC, id DESC LIMIT 1");
    $pendingStmt->bind_param("i", $residentId);
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result();
    if ($pendingResult->num_rows === 0) {
        $pendingStmt->close();
        header('Location: pending_payments.php?error=No rejected cedula request found.');
        exit;
    }
    $pendingRow = $pendingResult->fetch_assoc();
    $pendingStmt->close();

    $pendingCedulaPaymentId = intval($pendingRow['id']);
    $rejectionRemarks = trim((string) ($pendingRow['rejection_remarks'] ?? ''));

    $cedulaStmt = $conn->prepare("SELECT * FROM cedula WHERE resident_id = ? AND issued_by IS NULL ORDER BY created_at DESC, id DESC LIMIT 1");
    $cedulaStmt->bind_param("i", $residentId);
    $cedulaStmt->execute();
    $cedulaResult = $cedulaStmt->get_result();
    if ($cedulaResult->num_rows === 0) {
        $cedulaStmt->close();
        header('Location: pending_payments.php?error=Cedula request not found.');
        exit;
    }
    $cedulaToEdit = $cedulaResult->fetch_assoc();
    $cedulaStmt->close();
}

$cedulaNoValue = $nextCedula;
$orNumberValue = '';
$issuedDateValue = date('Y-m-d');
$yearIssuedValue = date('Y');
$placeOfIssueValue = $placeOfIssueDefault;
$fullNameValue = $fullNameDefault;
$surnameValue = $resident['surname'] ?? '';
$firstNameValue = $resident['first_name'] ?? '';
$middleNameValue = $resident['middle_name'] ?? '';
$addressValue = $addressDefault;
$birthDateValue = $birthDateDefault;
$ageValue = $ageDefault;
$sexValue = $resident['sex'] ?? '';
$birthPlaceValue = $resident['birthplace'] ?? '';
$civilStatusValue = $civilStatusDefault;
$citizenshipValue = $citizenshipDefault;
$icrNoValue = '';
$occupationValue = $occupationDefault;
$tinValue = '';
$heightValue = $heightDefault;
$weightValue = $weightDefault;
$annualIncomeValue = $annualIncomeDefault;
$basicTaxValue = '5.00';
$additionalBusinessValue = '0.00';
$additionalProfessionValue = number_format($additionalProfessionDefault, 2, '.', '');
$additionalPropertyValue = '0.00';
$communityTaxDueValue = number_format(5 + $additionalProfessionDefault, 2, '.', '');
$interestValue = '0.00';
$amountValue = $communityTaxDueValue;
$natureOfCollectionValue = 'Community Tax';
$amountInWordsValue = '';
$remarksValue = '';

if ($cedulaToEdit) {
    $cedulaNoValue = $cedulaToEdit['cedula_no'] ?? $cedulaNoValue;
    $orNumberValue = $cedulaToEdit['or_number'] ?? $orNumberValue;
    $issuedDateValue = !empty($cedulaToEdit['issued_date']) ? date('Y-m-d', strtotime($cedulaToEdit['issued_date'])) : $issuedDateValue;
    $yearIssuedValue = !empty($cedulaToEdit['year_issued']) ? intval($cedulaToEdit['year_issued']) : $yearIssuedValue;
    $placeOfIssueValue = $cedulaToEdit['place_of_issue'] ?? $placeOfIssueValue;
    $fullNameValue = $cedulaToEdit['full_name'] ?? $fullNameValue;
    $surnameValue = $cedulaToEdit['surname'] ?? $surnameValue;
    $firstNameValue = $cedulaToEdit['first_name'] ?? $firstNameValue;
    $middleNameValue = $cedulaToEdit['middle_name'] ?? $middleNameValue;
    $addressValue = $cedulaToEdit['address'] ?? $addressValue;
    $birthDateValue = !empty($cedulaToEdit['birth_date']) ? date('Y-m-d', strtotime($cedulaToEdit['birth_date'])) : $birthDateValue;
    $ageValue = isset($cedulaToEdit['age']) ? intval($cedulaToEdit['age']) : $ageValue;
    $sexValue = $cedulaToEdit['sex'] ?? $sexValue;
    $birthPlaceValue = $cedulaToEdit['birth_place'] ?? $birthPlaceValue;
    $civilStatusValue = $cedulaToEdit['civil_status'] ?? $civilStatusValue;
    $citizenshipValue = $cedulaToEdit['citizenship'] ?? $citizenshipValue;
    $icrNoValue = $cedulaToEdit['icr_no'] ?? $icrNoValue;
    $occupationValue = $cedulaToEdit['occupation'] ?? $occupationValue;
    $tinValue = $cedulaToEdit['tin'] ?? $tinValue;
    $heightValue = isset($cedulaToEdit['height']) && floatval($cedulaToEdit['height']) > 0
        ? number_format((float) $cedulaToEdit['height'], 2, '.', '')
        : $heightValue;
    $weightValue = isset($cedulaToEdit['weight']) && floatval($cedulaToEdit['weight']) > 0
        ? number_format((float) $cedulaToEdit['weight'], 2, '.', '')
        : $weightValue;
    $annualIncomeValue = isset($cedulaToEdit['annual_income']) && floatval($cedulaToEdit['annual_income']) > 0
        ? number_format((float) $cedulaToEdit['annual_income'], 2, '.', '')
        : $annualIncomeValue;
    $basicTaxValue = isset($cedulaToEdit['basic_tax'])
        ? number_format((float) $cedulaToEdit['basic_tax'], 2, '.', '')
        : $basicTaxValue;
    $additionalBusinessValue = isset($cedulaToEdit['additional_tax_business'])
        ? number_format((float) $cedulaToEdit['additional_tax_business'], 2, '.', '')
        : $additionalBusinessValue;
    $additionalProfessionValue = isset($cedulaToEdit['additional_tax_profession'])
        ? number_format((float) $cedulaToEdit['additional_tax_profession'], 2, '.', '')
        : $additionalProfessionValue;
    $additionalPropertyValue = isset($cedulaToEdit['additional_tax_property'])
        ? number_format((float) $cedulaToEdit['additional_tax_property'], 2, '.', '')
        : $additionalPropertyValue;
    $communityTaxDueValue = isset($cedulaToEdit['community_tax_due'])
        ? number_format((float) $cedulaToEdit['community_tax_due'], 2, '.', '')
        : $communityTaxDueValue;
    $interestValue = isset($cedulaToEdit['interest'])
        ? number_format((float) $cedulaToEdit['interest'], 2, '.', '')
        : $interestValue;
    $amountValue = isset($cedulaToEdit['amount'])
        ? number_format((float) $cedulaToEdit['amount'], 2, '.', '')
        : $amountValue;
    $natureOfCollectionValue = $cedulaToEdit['nature_of_collection'] ?? $natureOfCollectionValue;
    $amountInWordsValue = $cedulaToEdit['amount_in_words'] ?? $amountInWordsValue;
    $remarksValue = $cedulaToEdit['remarks'] ?? $remarksValue;
}

if (isset($_GET['submitted'])) {
    $success = 'Cedula request submitted. Please wait for treasurer approval.';
} elseif (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? 'create');
    $cedulaNo = trim($_POST['cedula_no'] ?? '');
    $orNumber = trim($_POST['or_number'] ?? '');
    $issuedDate = trim($_POST['issued_date'] ?? '');
    $yearIssued = isset($_POST['year_issued']) && $_POST['year_issued'] !== ''
        ? intval($_POST['year_issued'])
        : (empty($issuedDate) ? intval(date('Y')) : intval(date('Y', strtotime($issuedDate))));
    $placeOfIssue = trim($_POST['place_of_issue'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $age = compute_age_from_birthdate($birthDate);
    $sex = trim($_POST['sex'] ?? '');
    $birthPlace = trim($_POST['birth_place'] ?? '');
    $civilStatus = trim($_POST['civil_status'] ?? '');
    $citizenship = trim($_POST['citizenship'] ?? '');
    $icrNo = trim($_POST['icr_no'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $tin = trim($_POST['tin'] ?? '');
    $height = isset($_POST['height']) && $_POST['height'] !== '' ? floatval($_POST['height']) : 0;
    $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? floatval($_POST['weight']) : 0;
    $annualIncome = isset($_POST['annual_income']) ? floatval($_POST['annual_income']) : 0;
    $basicTax = isset($_POST['basic_tax']) ? floatval($_POST['basic_tax']) : 5.00;
    $additionalBusiness = isset($_POST['additional_tax_business']) ? floatval($_POST['additional_tax_business']) : 0;
    $additionalProfession = calculate_profession_tax($annualIncome);
    $additionalProperty = isset($_POST['additional_tax_property']) ? floatval($_POST['additional_tax_property']) : 0;
    $communityTaxDue = $basicTax + $additionalBusiness + $additionalProfession + $additionalProperty;
    $interest = isset($_POST['interest']) ? floatval($_POST['interest']) : 0;
    $amount = $communityTaxDue + $interest;
    $natureOfCollection = trim($_POST['nature_of_collection'] ?? '');
    $amountInWords = trim($_POST['amount_in_words'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($action === 'update') {
        // Validation only for edit/update action where fields are editable
        if ($cedulaNo === '' || $issuedDate === '' || $placeOfIssue === '' || $fullName === '' || $surname === '' || $firstName === '' || $address === '' || $birthDate === '' || $sex === '' || $birthPlace === '' || $civilStatus === '' || $citizenship === '' || $occupation === '' || $natureOfCollection === '') {
            $error = 'Please fill in all required fields.';
        } else {
            $cedulaId = intval($_POST['cedula_id'] ?? 0);
            $pendingPaymentId = intval($_POST['payment_id'] ?? 0);

            if ($cedulaId <= 0 || $pendingPaymentId <= 0) {
                $error = 'Invalid edit request.';
            } else {
                $conn->begin_transaction();

                $updateStmt = $conn->prepare("
                    UPDATE cedula SET
                    cedula_no = ?,
                    or_number = ?,
                    issued_date = ?,
                    year_issued = ?,
                    place_of_issue = ?,
                    full_name = ?,
                    surname = ?,
                    first_name = ?,
                    middle_name = ?,
                    address = ?,
                    birth_date = ?,
                    age = ?,
                    sex = ?,
                    birth_place = ?,
                    civil_status = ?,
                    citizenship = ?,
                    icr_no = ?,
                    occupation = ?,
                    tin = ?,
                    height = ?,
                    weight = ?,
                    annual_income = ?,
                    basic_tax = ?,
                    additional_tax_business = ?,
                    additional_tax_profession = ?,
                    additional_tax_property = ?,
                    community_tax_due = ?,
                    interest = ?,
                    amount = ?,
                    nature_of_collection = ?,
                    amount_in_words = ?,
                    remarks = ?
                WHERE id = ? AND resident_id = ? AND issued_by IS NULL
            ");
                $updateStmt->bind_param(
                    "sssisssssssisssssssddddddddddsssii",
                    $cedulaNo,
                    $orNumber,
                    $issuedDate,
                    $yearIssued,
                    $placeOfIssue,
                    $fullName,
                    $surname,
                    $firstName,
                    $middleName,
                    $address,
                    $birthDate,
                    $age,
                    $sex,
                    $birthPlace,
                    $civilStatus,
                    $citizenship,
                    $icrNo,
                    $occupation,
                    $tin,
                    $height,
                    $weight,
                    $annualIncome,
                    $basicTax,
                    $additionalBusiness,
                    $additionalProfession,
                    $additionalProperty,
                    $communityTaxDue,
                    $interest,
                    $amount,
                    $natureOfCollection,
                    $amountInWords,
                    $remarks,
                    $cedulaId,
                    $residentId
                );
                $cedulaOk = $updateStmt->execute();
                $updateStmt->close();

                $statusOk = false;
                if ($cedulaOk) {
                    $statusStmt = $conn->prepare("
                        UPDATE payment_status
                        SET payment_status = 'pending', rejection_remarks = NULL, rejected_at = NULL, created_at = NOW(),
                            amount = ?, bir_tax = 0, resident_fname = ?, purpose = 'Cedula Request', certificate_type = 'Cedula'
                        WHERE id = ? AND resident_id = ?
                    ");
                    $statusStmt->bind_param("dsii", $amount, $fullName, $pendingPaymentId, $residentId);
                    $statusOk = $statusStmt->execute();
                    $statusStmt->close();
                }

                if ($cedulaOk && $statusOk) {
                    $conn->commit();
                    header('Location: pending_payments.php?cedula_updated=1');
                    exit;
                }

                $conn->rollback();
                $error = 'Failed to update cedula request. Please try again.';
            }
        }
    } else {
        $conn->begin_transaction();

        $stmt = $conn->prepare("
            INSERT INTO cedula
            (cedula_no, or_number, issued_date, year_issued, place_of_issue, full_name, surname, first_name, middle_name, address, birth_date, age, sex, birth_place, civil_status, citizenship, icr_no, occupation, tin, height, weight, annual_income, basic_tax, additional_tax_business, additional_tax_profession, additional_tax_property, community_tax_due, interest, amount, nature_of_collection, amount_in_words, remarks, resident_id, issued_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $issuedBy = null;
        $stmt->bind_param(
            "sssisssssssisssssssddddddddddsssii",
            $cedulaNo,
            $orNumber,
            $issuedDate,
            $yearIssued,
            $placeOfIssue,
            $fullName,
            $surname,
            $firstName,
            $middleName,
            $address,
            $birthDate,
            $age,
            $sex,
            $birthPlace,
            $civilStatus,
            $citizenship,
            $icrNo,
            $occupation,
            $tin,
            $height,
            $weight,
            $annualIncome,
            $basicTax,
            $additionalBusiness,
            $additionalProfession,
            $additionalProperty,
            $communityTaxDue,
            $interest,
            $amount,
            $natureOfCollection,
            $amountInWords,
            $remarks,
            $residentId,
            $issuedBy
        );

        $cedulaOk = $stmt->execute();
        $stmt->close();

        $paymentOk = false;
        if ($cedulaOk) {
            $certificateType = 'Cedula';
            $purpose = 'Cedula Request';
            $residentName = $fullName;
            $birTax = 0;

            $payStmt = $conn->prepare("
                INSERT INTO payment_status (resident_id, certificate_type, purpose, resident_fname, payment_status, amount, bir_tax, created_at)
                VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())
            ");
            $payStmt->bind_param("isssdd", $residentId, $certificateType, $purpose, $residentName, $amount, $birTax);
            $paymentOk = $payStmt->execute();
            $payStmt->close();
        }

        if ($cedulaOk && $paymentOk) {
            $conn->commit();
            header('Location: request_cedula.php?submitted=1');
            exit;
        }

        $conn->rollback();
        $error = 'Failed to submit cedula request. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $isEdit ? 'Edit Cedula Request' : 'Request Cedula' ?> - Resident Portal
    </title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-color: #4c51bf;
            --primary-hover: #434190;
            --bg-light: #f7fafc;
            --text-dark: #1a202c;
            --text-muted: #718096;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 15px !important;
                width: 100% !important;
            }

            .content-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 8px;
                margin-bottom: 20px;
            }

            .content-header h1 {
                font-size: 1.15rem !important;
            }

            .summary-section div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }

            /* Handle the specific Tax Computation grid which has 5 columns */
            .summary-section div[style*="repeat(5, 1fr)"] {
                grid-template-columns: 1fr !important;
            }

            /* Handle second row of tax computation */
            .summary-section div[style*="align-items: start"] {
                grid-template-columns: 1fr !important;
            }

            .form-row {
                flex-direction: column !important;
                gap: 0 !important;
            }

            .form-group {
                margin-bottom: 16px !important;
            }

            .cedula-summary-container {
                padding: 16px !important;
            }

            .summary-section {
                padding: 16px !important;
                margin-bottom: 20px !important;
            }

            .card {
                border-radius: 12px !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            }

            .btn-primary,
            .btn-secondary {
                padding: 12px !important;
                font-size: 0.9rem !important;
            }

            div[style*="display: flex; gap: 10px"] {
                flex-direction: column !important;
                gap: 12px !important;
            }

            div[style*="padding-top: 20px"] {
                padding-top: 15px !important;
            }
        }

        /* Responsive Modal */
        @media (max-width: 480px) {
            .modal-content {
                width: 95% !important;
                padding: 20px !important;
                margin: 10px;
            }

            .modal-content h3 {
                font-size: 1.25rem !important;
            }
        }
    </style>
</head>

<body class="resident-portal">
    <div class="dashboard-container">
        <?php include "partials/sidebar.php"; ?>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-id-card"></i> Cedula Request</h1>
                <p>Welcome, <?= htmlspecialchars($fullName) ?></p>
            </div>

            <div class="content-body">
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($warning): ?>
                    <div class="warning-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($warning) ?>
                    </div>
                <?php endif; ?>

                <?php if ($isEdit && $rejectionRemarks !== ''): ?>
                    <div class="error-message">
                        <i class="fas fa-comment-slash"></i>
                        <strong>Treasurer remarks:</strong>
                        <?= htmlspecialchars($rejectionRemarks) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$hasPendingRequest || $isEdit): ?>
                    <div class="card"
                        style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                        <?php if (!$isEdit): ?>
                            <!-- Simplified View: Read-only Summary -->
                            <div class="cedula-summary-container" style="padding: 24px;">
                                <!-- Section 1: Cedula Details -->
                                <div class="summary-section" style="margin-bottom: 24px;">
                                    <h4
                                        style="margin-bottom: 16px; color: #1a202c; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                        📋 Cedula Details
                                    </h4>
                                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Cedula
                                                Number</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">To be assigned</span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">OR
                                                Number</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars((string) $orNumberValue) ?: 'To be assigned' ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Date
                                                Issued</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars((string) $issuedDateValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Year
                                                Issued</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars((string) $yearIssuedValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Place
                                                of Issue</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($placeOfIssueValue) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <hr style="border: 0; border-top: 1px solid #edf2f7; margin: 24px 0;">

                                <!-- Section 2: Personal Information -->
                                <div class="summary-section" style="margin-bottom: 24px;">
                                    <h4
                                        style="margin-bottom: 16px; color: #1a202c; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-user" style="color: #4c51bf; font-size: 0.85rem;"></i> Personal
                                        Information
                                    </h4>
                                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Full
                                                Name</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($fullNameValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Surname</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($surnameValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">First
                                                Name</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($firstNameValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Middle
                                                Name</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($middleNameValue) ?: 'N/A' ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Date
                                                of Birth</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($birthDateValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Age</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars((string) $ageValue) ?> years old
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Sex</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($sexValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Place
                                                of Birth</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($birthPlaceValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Civil
                                                Status</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($civilStatusValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Citizenship</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($citizenshipValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">ICR
                                                No. (If Alien)</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($icrNoValue) ?: 'N/A' ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Complete
                                                Address</strong>
                                            <span style="color: #718096; font-size: 0.85rem; line-height: 1.4;">
                                                <?= htmlspecialchars($addressValue) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <hr style="border: 0; border-top: 1px solid #edf2f7; margin: 24px 0;">

                                <!-- Section 3: Employment & Tax Information -->
                                <div class="summary-section" style="margin-bottom: 24px;">
                                    <h4
                                        style="margin-bottom: 16px; color: #1a202c; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                        💼 Employment & Tax Information
                                    </h4>
                                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Occupation</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($occupationValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">TIN</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($tinValue) ?: 'N/A' ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Height
                                                (cm)</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($heightValue) ?: 'N/A' ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Weight
                                                (kg)</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($weightValue) ?: 'N/A' ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Annual
                                                Income (PHP)</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($annualIncomeValue) ?: '0.00' ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Nature
                                                of Collection</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($natureOfCollectionValue) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <hr style="border: 0; border-top: 1px solid #edf2f7; margin: 24px 0;">

                                <!-- Section 4: Tax Computation & Amount Due -->
                                <div class="summary-section"
                                    style="margin-bottom: 24px; background: #f0f9ff; padding: 24px; border-left: 4px solid #3182ce; border-radius: 4px;">
                                    <h4
                                        style="margin-bottom: 20px; color: #1a202c; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                        💰 Tax Computation & Amount Due
                                    </h4>
                                    <div
                                        style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 24px;">
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Basic
                                                Community Tax (PHP)</strong>
                                            <span style="color: #1a202c; font-size: 0.95rem; font-weight: 700;">₱
                                                <?= htmlspecialchars($basicTaxValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Additional
                                                Tax - Business (PHP)</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($additionalBusinessValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Additional
                                                Tax - Profession (PHP)</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($additionalProfessionValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Additional
                                                Tax - Property (PHP)</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($additionalPropertyValue) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Community
                                                Tax Due (PHP)</strong>
                                            <span style="color: #1a202c; font-size: 0.95rem; font-weight: 700;">₱
                                                <?= htmlspecialchars($communityTaxDueValue) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; align-items: start;">
                                        <div>
                                            <strong
                                                style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 4px;">Interest
                                                (PHP)</strong>
                                            <span style="color: #718096; font-size: 0.9rem;">
                                                <?= htmlspecialchars($interestValue) ?>
                                            </span>
                                        </div>
                                        <div style="grid-column: span 1;">
                                            <div
                                                style="background: white; padding: 15px; border-radius: 8px; border: 1.5px solid #3182ce; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                                <strong
                                                    style="display: block; color: #2d3748; font-size: 0.85rem; font-weight: 700; margin-bottom: 8px;">Total
                                                    Amount Due (PHP)</strong>
                                                <span
                                                    style="color: #38a169; font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 4px;">
                                                    ₱ <?= htmlspecialchars($amountValue) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 10px; padding-top: 20px; border-top: 1px solid #edf2f7;">
                                    <button type="button" class="btn btn-primary" onclick="openConfirmationModal()"
                                        style="flex: 1;">
                                        <i class="fas fa-paper-plane"></i> Request Cedula
                                    </button>
                                    <a href="pending_payments.php" class="btn btn-secondary"
                                        style="flex: 1; text-align: center; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Edit Mode: Show editable form -->
                            <form method="POST" action="request_cedula.php?edit=1" style="padding: 20px;">
                                <input type="hidden" name="action" value="update">
                                <?php if ($cedulaToEdit): ?>
                                    <input type="hidden" name="cedula_id" value="<?= intval($cedulaToEdit['id']) ?>">
                                    <input type="hidden" name="payment_id" value="<?= intval($pendingCedulaPaymentId) ?>">
                                <?php endif; ?>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="cedula_no">Cedula Number *</label>
                                        <input type="text" id="cedula_no" name="cedula_no"
                                            value="<?= htmlspecialchars((string) $cedulaNoValue) ?>" readonly required>
                                    </div>
                                    <div class="form-group">
                                        <label for="issued_date">Date Issued *</label>
                                        <input type="date" id="issued_date" name="issued_date"
                                            value="<?= htmlspecialchars((string) $issuedDateValue) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="place_of_issue">Place of Issue *</label>
                                        <input type="text" id="place_of_issue" name="place_of_issue"
                                            value="<?= htmlspecialchars($placeOfIssueValue) ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="surname">Surname *</label>
                                        <input type="text" id="surname" name="surname"
                                            value="<?= htmlspecialchars($surnameValue) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="first_name">First Name *</label>
                                        <input type="text" id="first_name" name="first_name"
                                            value="<?= htmlspecialchars($firstNameValue) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="middle_name">Middle Name</label>
                                        <input type="text" id="middle_name" name="middle_name"
                                            value="<?= htmlspecialchars($middleNameValue) ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">Complete Address *</label>
                                    <textarea id="address" name="address" rows="2"
                                        required><?= htmlspecialchars($addressValue) ?></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="birth_date">Birth Date *</label>
                                        <input type="date" id="birth_date" name="birth_date"
                                            value="<?= htmlspecialchars($birthDateValue) ?>" required onchange="calculateAge()">
                                    </div>
                                    <div class="form-group">
                                        <label for="birth_place">Birth Place *</label>
                                        <input type="text" id="birth_place" name="birth_place"
                                            value="<?= htmlspecialchars($birthPlaceValue) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="age">Age *</label>
                                        <input type="number" id="age" name="age"
                                            value="<?= htmlspecialchars((string) $ageValue) ?>" readonly required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sex">Sex *</label>
                                        <select id="sex" name="sex" required>
                                            <option value="Male" <?= $sexValue === 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= $sexValue === 'Female' ? 'selected' : '' ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="civil_status">Civil Status *</label>
                                        <select id="civil_status" name="civil_status" required>
                                            <option value="Single" <?= $civilStatusValue === 'Single' ? 'selected' : '' ?>>Single
                                            </option>
                                            <option value="Married" <?= $civilStatusValue === 'Married' ? 'selected' : '' ?>>
                                                Married</option>
                                            <option value="Widowed" <?= $civilStatusValue === 'Widowed' ? 'selected' : '' ?>>
                                                Widowed</option>
                                            <option value="Separated" <?= $civilStatusValue === 'Separated' ? 'selected' : '' ?>>
                                                Separated</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="citizenship">Citizenship *</label>
                                        <input type="text" id="citizenship" name="citizenship"
                                            value="<?= htmlspecialchars($citizenshipValue) ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="occupation">Occupation *</label>
                                        <input type="text" id="occupation" name="occupation"
                                            value="<?= htmlspecialchars($occupationValue) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="tin">TIN</label>
                                        <input type="text" id="tin" name="tin" value="<?= htmlspecialchars($tinValue) ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="height">Height (cm)</label>
                                        <input type="number" step="0.01" id="height" name="height"
                                            value="<?= htmlspecialchars($heightValue) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="weight">Weight (kg)</label>
                                        <input type="number" step="0.01" id="weight" name="weight"
                                            value="<?= htmlspecialchars($weightValue) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="annual_income">Annual Income (PHP)</label>
                                        <input type="number" step="0.01" id="annual_income" name="annual_income"
                                            value="<?= htmlspecialchars($annualIncomeValue) ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="nature_of_collection">Nature of Collection *</label>
                                    <input type="text" id="nature_of_collection" name="nature_of_collection"
                                        value="<?= htmlspecialchars($natureOfCollectionValue) ?>" required>
                                </div>

                                <div style="display: flex; gap: 10px; margin-top: 25px;">
                                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                                        <i class="fas fa-save"></i> Update Request
                                    </button>
                                    <a href="pending_payments.php" class="btn btn-secondary"
                                        style="flex: 1; text-align: center; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 9999; align-items: center; justify-content: center;">
        <div class="modal-content"
            style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div style="text-align: center; margin-bottom: 20px;">
                <div
                    style="width: 60px; height: 60px; background: #ebf8ff; color: #3182ce; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 30px;">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3 style="margin: 0; color: #1a202c; font-size: 1.5rem;">Confirm Application</h3>
            </div>

            <p style="color: #4a5568; margin-bottom: 20px; text-align: center;">Are you sure you want to submit this
                cedula request? Please review your details before proceeding.</p>

            <div style="background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                <div style="margin-bottom: 8px;"><strong>Total Amount:</strong> <span id="confirmAmount"
                        style="color: #2b6cb0; font-weight: 700;">PHP
                        <?= htmlspecialchars($amountValue) ?>
                    </span></div>
                <div><strong>Purpose:</strong> Cedula Request</div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmationModal()"
                    style="flex: 1;">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitCedulaRequest()" style="flex: 1;">Confirm &
                    Submit</button>
            </div>
        </div>
    </div>

    <form id="hiddenForm" method="POST" action="request_cedula.php" style="display: none;">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="cedula_no" value="<?= htmlspecialchars((string) $cedulaNoValue) ?>">
        <input type="hidden" name="issued_date" value="<?= htmlspecialchars((string) $issuedDateValue) ?>">
        <input type="hidden" name="year_issued" value="<?= htmlspecialchars((string) $yearIssuedValue) ?>">
        <input type="hidden" name="place_of_issue" value="<?= htmlspecialchars($placeOfIssueValue) ?>">
        <input type="hidden" name="full_name" value="<?= htmlspecialchars($fullNameValue) ?>">
        <input type="hidden" name="surname" value="<?= htmlspecialchars($surnameValue) ?>">
        <input type="hidden" name="first_name" value="<?= htmlspecialchars($firstNameValue) ?>">
        <input type="hidden" name="middle_name" value="<?= htmlspecialchars($middleNameValue) ?>">
        <input type="hidden" name="address" value="<?= htmlspecialchars($addressValue) ?>">
        <input type="hidden" name="birth_date" value="<?= htmlspecialchars($birthDateValue) ?>">
        <input type="hidden" name="age" value="<?= htmlspecialchars((string) $ageValue) ?>">
        <input type="hidden" name="sex" value="<?= htmlspecialchars($sexValue) ?>">
        <input type="hidden" name="birth_place" value="<?= htmlspecialchars($birthPlaceValue) ?>">
        <input type="hidden" name="civil_status" value="<?= htmlspecialchars($civilStatusValue) ?>">
        <input type="hidden" name="citizenship" value="<?= htmlspecialchars($citizenshipValue) ?>">
        <input type="hidden" name="occupation" value="<?= htmlspecialchars($occupationValue) ?>">
        <input type="hidden" name="tin" value="<?= htmlspecialchars($tinValue) ?>">
        <input type="hidden" name="height" value="<?= htmlspecialchars($heightValue) ?>">
        <input type="hidden" name="weight" value="<?= htmlspecialchars($weightValue) ?>">
        <input type="hidden" name="annual_income" value="<?= htmlspecialchars($annualIncomeValue) ?>">
        <input type="hidden" name="basic_tax" value="<?= htmlspecialchars($basicTaxValue) ?>">
        <input type="hidden" name="amount" value="<?= htmlspecialchars($amountValue) ?>">
        <input type="hidden" name="nature_of_collection" value="<?= htmlspecialchars($natureOfCollectionValue) ?>">
    </form>

    <script>
        function openConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'flex';
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }

        function submitCedulaRequest() {
            document.getElementById('hiddenForm').submit();
        }

        function calculateAge() {
            const birthDateStr = document.getElementById('birth_date').value;
            if (!birthDateStr) return;
            const birthDate = new Date(birthDateStr);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
            document.getElementById('age').value = age;
        }

        window.onclick = function (event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target == modal) closeConfirmationModal();
        }
    </script>
</body>

</html>