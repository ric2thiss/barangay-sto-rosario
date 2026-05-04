<?php
include "../../config/database.php";
include "../../config/session.php";

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM cedula WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: list.php?deleted=1");
    exit();
}

// Handle insert or update action (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update' && !empty($_POST['id'])) {
        $cedulaId = intval($_POST['id']);
        $annual_income = isset($_POST['annual_income']) ? floatval($_POST['annual_income']) : 0;
        $height = isset($_POST['height']) && $_POST['height'] !== '' ? floatval($_POST['height']) : 0;
        $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? floatval($_POST['weight']) : 0;
        $basic_tax = isset($_POST['basic_tax']) ? floatval($_POST['basic_tax']) : 5.00;
        $additional_tax_business = isset($_POST['additional_tax_business']) ? floatval($_POST['additional_tax_business']) : 0;
        $additional_tax_profession = isset($_POST['additional_tax_profession']) ? floatval($_POST['additional_tax_profession']) : 0;
        $additional_tax_property = isset($_POST['additional_tax_property']) ? floatval($_POST['additional_tax_property']) : 0;
        $community_tax_due = $basic_tax + $additional_tax_business + $additional_tax_profession + $additional_tax_property;
        $interest = isset($_POST['interest']) ? floatval($_POST['interest']) : 0;
        $amount = $community_tax_due + $interest;
        $issued_date = $_POST['issued_date'];
        $year_issued = isset($_POST['year_issued']) && $_POST['year_issued'] !== ''
            ? intval($_POST['year_issued'])
            : intval(date('Y', strtotime($issued_date)));
        $place_of_issue = $_POST['place_of_issue'] ?? '';
        $surname = $_POST['surname'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $middle_name = $_POST['middle_name'] ?? '';
        $icr_no = $_POST['icr_no'] ?? '';
        $amount_in_words = $_POST['amount_in_words'] ?? '';
        $remarks = $_POST['remarks'] ?? '';

        $stmt = $conn->prepare("
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
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssisssssssisssssssddddddddddsssi",
            $_POST['cedula_no'],
            $_POST['or_number'],
            $issued_date,
            $year_issued,
            $place_of_issue,
            $_POST['full_name'],
            $surname,
            $first_name,
            $middle_name,
            $_POST['address'],
            $_POST['birth_date'],
            $_POST['age'],
            $_POST['sex'],
            $_POST['birth_place'],
            $_POST['civil_status'],
            $_POST['citizenship'],
            $icr_no,
            $_POST['occupation'],
            $_POST['tin'],
            $height,
            $weight,
            $annual_income,
            $basic_tax,
            $additional_tax_business,
            $additional_tax_profession,
            $additional_tax_property,
            $community_tax_due,
            $interest,
            $amount,
            $_POST['nature_of_collection'],
            $amount_in_words,
            $remarks,
            $cedulaId
        );

        if ($stmt->execute()) {
            header("Location: list.php?updated=1");
        } else {
            header("Location: edit.php?id=" . $cedulaId . "&error=Failed to update cedula");
        }
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO cedula
        (cedula_no, or_number, issued_date, year_issued, place_of_issue, full_name, surname, first_name, middle_name, address, birth_date, age, sex, birth_place, civil_status, citizenship, icr_no, occupation, tin, height, weight, annual_income, basic_tax, additional_tax_business, additional_tax_profession, additional_tax_property, community_tax_due, interest, amount, nature_of_collection, amount_in_words, remarks, issued_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $annual_income = isset($_POST['annual_income']) ? floatval($_POST['annual_income']) : 0;
    $height = isset($_POST['height']) && $_POST['height'] !== '' ? floatval($_POST['height']) : 0;
    $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? floatval($_POST['weight']) : 0;
    $basic_tax = isset($_POST['basic_tax']) ? floatval($_POST['basic_tax']) : 5.00;
    $additional_tax_business = isset($_POST['additional_tax_business']) ? floatval($_POST['additional_tax_business']) : 0;
    $additional_tax_profession = isset($_POST['additional_tax_profession']) ? floatval($_POST['additional_tax_profession']) : 0;
    $additional_tax_property = isset($_POST['additional_tax_property']) ? floatval($_POST['additional_tax_property']) : 0;
    $community_tax_due = $basic_tax + $additional_tax_business + $additional_tax_profession + $additional_tax_property;
    $interest = isset($_POST['interest']) ? floatval($_POST['interest']) : 0;
    $amount = $community_tax_due + $interest;
    $issued_date = $_POST['issued_date'];
    $year_issued = isset($_POST['year_issued']) && $_POST['year_issued'] !== ''
        ? intval($_POST['year_issued'])
        : intval(date('Y', strtotime($issued_date)));
    $place_of_issue = $_POST['place_of_issue'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $icr_no = $_POST['icr_no'] ?? '';
    $amount_in_words = $_POST['amount_in_words'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    // Safely resolve issued_by — use NULL if session user doesn't exist in users table
    $issued_by = null;
    if (!empty($_SESSION['user_id'])) {
        $userCheck = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $userCheck->bind_param("i", $_SESSION['user_id']);
        $userCheck->execute();
        $userCheck->store_result();
        if ($userCheck->num_rows > 0) {
            $issued_by = intval($_SESSION['user_id']);
        }
        $userCheck->close();
    }

    $stmt->bind_param(
        "sssisssssssisssssssddddddddddsssi",
        $_POST['cedula_no'],
        $_POST['or_number'],
        $issued_date,
        $year_issued,
        $place_of_issue,
        $_POST['full_name'],
        $surname,
        $first_name,
        $middle_name,
        $_POST['address'],
        $_POST['birth_date'],
        $_POST['age'],
        $_POST['sex'],
        $_POST['birth_place'],
        $_POST['civil_status'],
        $_POST['citizenship'],
        $icr_no,
        $_POST['occupation'],
        $_POST['tin'],
        $height,
        $weight,
        $annual_income,
        $basic_tax,
        $additional_tax_business,
        $additional_tax_profession,
        $additional_tax_property,
        $community_tax_due,
        $interest,
        $amount,
        $_POST['nature_of_collection'],
        $amount_in_words,
        $remarks,
        $issued_by
    );

    $conn->begin_transaction();

    $cedulaOk = $stmt->execute();
    $stmt->close();

    if (!$cedulaOk) {
        $conn->rollback();
        header("Location: add.php?error=Failed to issue cedula");
        exit();
    }

    $receiptNo = trim((string) ($_POST['or_number'] ?? ''));
    if ($receiptNo === '') {
        $receiptNo = trim((string) ($_POST['cedula_no'] ?? ''));
    }

    $paymentStmt = $conn->prepare("
        INSERT INTO payments (receipt_no, payment_date, payer_name, service_type, purpose, amount, bir_tax, remarks, received_by, resident_id, created_at)
        VALUES (?, ?, ?, 'Cedula', 'Cedula Issuance', ?, 0, ?, ?, NULL, NOW())
    ");

    $paymentRemarks = trim((string) ($remarks ?? ''));
    $paymentStmt->bind_param(
        "sssdsi",
        $receiptNo,
        $issued_date,
        $_POST['full_name'],
        $amount,
        $paymentRemarks,
        $issued_by
    );

    $paymentOk = $paymentStmt->execute();
    $paymentStmt->close();

    if ($paymentOk) {
        $conn->commit();
        header("Location: list.php?success=1");
    } else {
        $conn->rollback();
        header("Location: add.php?error=Failed to save payment");
    }
    exit();
}






