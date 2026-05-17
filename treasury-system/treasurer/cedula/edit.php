<?php
include "../../config/database.php";
include "../../config/session.php";

$cedulaId = intval($_GET['id'] ?? 0);
if ($cedulaId <= 0) {
    header("Location: list.php?error=Invalid cedula ID.");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM cedula WHERE id = ?");
$stmt->bind_param("i", $cedulaId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: list.php?error=Cedula record not found.");
    exit;
}
$cedula = $result->fetch_assoc();
$stmt->close();

$error = $_GET['error'] ?? '';
$issuedDate = !empty($cedula['issued_date']) ? date('Y-m-d', strtotime($cedula['issued_date'])) : date('Y-m-d');
$yearIssuedValue = !empty($cedula['year_issued'])
    ? intval($cedula['year_issued'])
    : intval(date('Y', strtotime($issuedDate)));
$placeOfIssueValue = $cedula['place_of_issue'] ?? '';
$surnameValue = $cedula['surname'] ?? '';
$firstNameValue = $cedula['first_name'] ?? '';
$middleNameValue = $cedula['middle_name'] ?? '';
$icrNoValue = $cedula['icr_no'] ?? '';
$birthDate = !empty($cedula['birth_date']) ? date('Y-m-d', strtotime($cedula['birth_date'])) : '';
$ageValue = isset($cedula['age']) ? intval($cedula['age']) : '';
$amountValue = number_format((float) $cedula['amount'], 2, '.', '');
$annualIncomeValue = (isset($cedula['annual_income']) && floatval($cedula['annual_income']) > 0)
    ? number_format((float) $cedula['annual_income'], 2, '.', '')
    : '';
$basicTaxValue = number_format((float) ($cedula['basic_tax'] ?? 5), 2, '.', '');
$additionalBusinessValue = number_format((float) ($cedula['additional_tax_business'] ?? 0), 2, '.', '');
$additionalProfessionValue = number_format((float) ($cedula['additional_tax_profession'] ?? 0), 2, '.', '');
$additionalPropertyValue = number_format((float) ($cedula['additional_tax_property'] ?? 0), 2, '.', '');
$communityTaxDueValue = number_format((float) ($cedula['community_tax_due'] ?? 0), 2, '.', '');
$interestValue = number_format((float) ($cedula['interest'] ?? 0), 2, '.', '');
$amountInWordsValue = $cedula['amount_in_words'] ?? '';
$heightValue = (isset($cedula['height']) && floatval($cedula['height']) > 0)
    ? number_format((float) $cedula['height'], 2, '.', '')
    : '';
$weightValue = (isset($cedula['weight']) && floatval($cedula['weight']) > 0)
    ? number_format((float) $cedula['weight'], 2, '.', '')
    : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cedula - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <?php
        $path_prefix = '../';
        include "../partials/sidebar.php";
        ?>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-id-card"></i> Edit Cedula</h1>
            </div>

            <div class="content-body">
                <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-pen"></i> Cedula Information</h3>
                        <p style="color: #666; font-size: 14px; margin-top: 5px;">Update the cedula details</p>
                    </div>

                    <form method="POST" action="save.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id"
                            value="<?= $cedulaId ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="cedula_no"><i class="fas fa-hashtag"></i> Cedula Number *</label>
                                <input type="text" id="cedula_no" name="cedula_no"
                                    value="<?= htmlspecialchars($cedula['cedula_no']) ?>"
                                    readonly required>
                            </div>

                            <div class="form-group">
                                <label for="or_number"><i class="fas fa-receipt"></i> OR Number *</label>
                                <input type="text" id="or_number" name="or_number"
                                    value="<?= htmlspecialchars($cedula['or_number'] ?? '') ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="issued_date"><i class="fas fa-calendar"></i> Date Issued *</label>
                                <input type="date" id="issued_date" name="issued_date"
                                    value="<?= htmlspecialchars($issuedDate) ?>"
                                    required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="year_issued"><i class="fas fa-calendar-check"></i> Year *</label>
                                <input type="number" id="year_issued" name="year_issued"
                                    value="<?= htmlspecialchars((string) $yearIssuedValue) ?>"
                                    required>
                            </div>

                            <div class="form-group" style="flex: 2;">
                                <label for="place_of_issue"><i class="fas fa-map"></i> Place of Issue
                                    (City/Mun/Prov) *</label>
                                <input type="text" id="place_of_issue" name="place_of_issue"
                                    value="<?= htmlspecialchars($placeOfIssueValue) ?>"
                                    required>
                            </div>
                        </div>

                        <div class="form-group" style="position: relative;">
                            <label for="full_name"><i class="fas fa-user"></i> Full Name * <small
                                    style="color: #666;">(Type to search existing records)</small></label>
                            <input type="text" id="full_name" name="full_name"
                                value="<?= htmlspecialchars($cedula['full_name']) ?>"
                                required autocomplete="off">
                            <div id="suggestions"
                                style="position: absolute; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; width: calc(100% - 40px); z-index: 1000; display: none; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 5px;">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="surname"><i class="fas fa-user"></i> Surname *</label>
                                <input type="text" id="surname" name="surname"
                                    value="<?= htmlspecialchars($surnameValue) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="first_name"><i class="fas fa-user"></i> First Name *</label>
                                <input type="text" id="first_name" name="first_name"
                                    value="<?= htmlspecialchars($firstNameValue) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="middle_name"><i class="fas fa-user"></i> Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name"
                                    value="<?= htmlspecialchars($middleNameValue) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address"><i class="fas fa-map-marker-alt"></i> Complete Address *</label>
                            <textarea id="address" name="address" rows="2"
                                required><?= htmlspecialchars($cedula['address']) ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="birth_date"><i class="fas fa-birthday-cake"></i> Birth Date *</label>
                                <input type="date" id="birth_date" name="birth_date"
                                    value="<?= htmlspecialchars($birthDate) ?>"
                                    required onchange="calculateAge()">
                            </div>

                            <div class="form-group">
                                <label for="age"><i class="fas fa-sort-numeric-up"></i> Age *</label>
                                <input type="number" id="age" name="age"
                                    value="<?= htmlspecialchars((string) $ageValue) ?>"
                                    readonly required>
                            </div>

                            <div class="form-group">
                                <label for="sex"><i class="fas fa-venus-mars"></i> Sex *</label>
                                <select id="sex" name="sex" required>
                                    <option value="">Select</option>
                                    <option value="Male" <?= $cedula['sex'] === 'Male' ? 'selected' : '' ?>>Male
                                    </option>
                                    <option value="Female" <?= $cedula['sex'] === 'Female' ? 'selected' : '' ?>>Female
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="birth_place"><i class="fas fa-hospital"></i> Place of Birth *</label>
                                <input type="text" id="birth_place" name="birth_place"
                                    value="<?= htmlspecialchars($cedula['birth_place']) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="civil_status"><i class="fas fa-ring"></i> Civil Status *</label>
                                <select id="civil_status" name="civil_status" required>
                                    <option value="">Select</option>
                                    <option value="Single" <?= $cedula['civil_status'] === 'Single' ? 'selected' : '' ?>>Single
                                    </option>
                                    <option value="Married" <?= $cedula['civil_status'] === 'Married' ? 'selected' : '' ?>>Married
                                    </option>
                                    <option value="Widowed" <?= $cedula['civil_status'] === 'Widowed' ? 'selected' : '' ?>>Widowed
                                    </option>
                                    <option value="Separated" <?= $cedula['civil_status'] === 'Separated' ? 'selected' : '' ?>>Separated
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="citizenship"><i class="fas fa-flag"></i> Citizenship *</label>
                                <select id="citizenship" name="citizenship" required>
                                    <option value="Filipino" <?= $cedula['citizenship'] === 'Filipino' ? 'selected' : '' ?>>Filipino
                                    </option>
                                    <option value="Dual Citizen" <?= $cedula['citizenship'] === 'Dual Citizen' ? 'selected' : '' ?>>Dual
                                        Citizen</option>
                                    <option value="Foreign National" <?= $cedula['citizenship'] === 'Foreign National' ? 'selected' : '' ?>>Foreign
                                        National</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="icr_no"><i class="fas fa-id-badge"></i> ICR No. (If Alien)</label>
                                <input type="text" id="icr_no" name="icr_no"
                                    value="<?= htmlspecialchars($icrNoValue) ?>">
                            </div>

                            <div class="form-group">
                                <label for="occupation"><i class="fas fa-briefcase"></i> Occupation *</label>
                                <input type="text" id="occupation" name="occupation"
                                    value="<?= htmlspecialchars($cedula['occupation']) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="tin"><i class="fas fa-id-card-alt"></i> TIN (Optional)</label>
                                <input type="text" id="tin" name="tin"
                                    value="<?= htmlspecialchars($cedula['tin'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="height"><i class="fas fa-arrows-alt-v"></i> Height (cm)</label>
                                <input type="number" id="height" name="height" step="0.01"
                                    value="<?= htmlspecialchars($heightValue) ?>">
                            </div>

                            <div class="form-group">
                                <label for="weight"><i class="fas fa-weight"></i> Weight (kg)</label>
                                <input type="number" id="weight" name="weight" step="0.01"
                                    value="<?= htmlspecialchars($weightValue) ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nature_of_collection"><i class="fas fa-list"></i> Nature of Collection
                                    *</label>
                                <input type="text" id="nature_of_collection" name="nature_of_collection"
                                    value="<?= htmlspecialchars($cedula['nature_of_collection'] ?? 'Community Tax') ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="annual_income"><i class="fas fa-coins"></i> Taxable Amount (PHP)</label>
                                <input type="number" id="annual_income" name="annual_income" step="0.01" min="0"
                                    value="<?= htmlspecialchars($annualIncomeValue) ?>"
                                    oninput="calculateAdditionalFromIncome()">
                                <small style="color:#666;">Auto-calculated for profession: Income &divide; 1,000</small>
                            </div>

                            <div class="form-group">
                                <label for="basic_tax"><i class="fas fa-receipt"></i> Basic Community Tax (PHP)</label>
                                <input type="number" id="basic_tax" name="basic_tax" step="0.01" min="0"
                                    value="<?= htmlspecialchars($basicTaxValue) ?>"
                                    oninput="computeTotals()">
                                <small style="color:#666;">Regular: 5.00, Voluntary/Exempted: 1.00</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="additional_tax_business"><i class="fas fa-store"></i> Additional Tax -
                                    Business (PHP)</label>
                                <input type="number" id="additional_tax_business" name="additional_tax_business"
                                    step="0.01" min="0"
                                    value="<?= htmlspecialchars($additionalBusinessValue) ?>"
                                    oninput="computeTotals()">
                            </div>

                            <div class="form-group">
                                <label for="additional_tax_profession"><i class="fas fa-briefcase"></i> Additional Tax
                                    - Profession (PHP)</label>
                                <input type="number" id="additional_tax_profession" name="additional_tax_profession"
                                    step="0.01" min="0"
                                    value="<?= htmlspecialchars($additionalProfessionValue) ?>"
                                    oninput="computeTotals()">
                            </div>

                            <div class="form-group">
                                <label for="additional_tax_property"><i class="fas fa-home"></i> Additional Tax -
                                    Property (PHP)</label>
                                <input type="number" id="additional_tax_property" name="additional_tax_property"
                                    step="0.01" min="0"
                                    value="<?= htmlspecialchars($additionalPropertyValue) ?>"
                                    oninput="computeTotals()">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="community_tax_due"><i class="fas fa-calculator"></i> Community Tax Due
                                    (PHP)</label>
                                <input type="number" id="community_tax_due" name="community_tax_due" step="0.01"
                                    value="<?= htmlspecialchars($communityTaxDueValue) ?>"
                                    readonly required style="background:#e8f0ff; font-weight:bold; font-size:16px;">
                            </div>

                            <div class="form-group">
                                <label for="interest"><i class="fas fa-percentage"></i> Interest (PHP)</label>
                                <input type="number" id="interest" name="interest" step="0.01" min="0"
                                    value="<?= htmlspecialchars($interestValue) ?>"
                                    oninput="computeTotals()">
                            </div>

                            <div class="form-group">
                                <label for="amount"><i class="fas fa-peso-sign"></i> Total Amount Paid *</label>
                                <input type="number" id="amount" name="amount" step="0.01"
                                    value="<?= htmlspecialchars($amountValue) ?>"
                                    required readonly style="background:#e8f0ff; font-weight:bold; font-size:16px;">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label for="amount_in_words"><i class="fas fa-spell-check"></i> Amount in Words</label>
                                <input type="text" id="amount_in_words" name="amount_in_words"
                                    value="<?= htmlspecialchars($amountInWordsValue) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="remarks"><i class="fas fa-comment"></i> Remarks</label>
                            <textarea id="remarks" name="remarks" rows="2"
                                placeholder="Additional notes..."><?= htmlspecialchars($cedula['remarks'] ?? '') ?></textarea>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 25px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="list.php" class="btn btn-secondary"
                                style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function computeTotals() {
            const basicTax = parseFloat(document.getElementById('basic_tax').value) || 0;
            const additionalBusiness = parseFloat(document.getElementById('additional_tax_business').value) || 0;
            const additionalProfession = parseFloat(document.getElementById('additional_tax_profession').value) || 0;
            const additionalProperty = parseFloat(document.getElementById('additional_tax_property').value) || 0;
            const interest = parseFloat(document.getElementById('interest').value) || 0;
            const due = basicTax + additionalBusiness + additionalProfession + additionalProperty;
            document.getElementById('community_tax_due').value = due.toFixed(2);
            document.getElementById('amount').value = (due + interest).toFixed(2);
        }

        function calculateAdditionalFromIncome() {
            const income = parseFloat(document.getElementById('annual_income').value) || 0;
            const computed = income / 1000;
            const professionField = document.getElementById('additional_tax_profession');
            if (professionField) {
                professionField.value = (computed > 0 ? computed : 0).toFixed(2);
            }
            computeTotals();
        }

        function calculateAge() {
            const birthDateValue = document.getElementById('birth_date').value;
            if (!birthDateValue) {
                return;
            }
            const birthDate = new Date(birthDateValue);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            document.getElementById('age').value = age;
        }

        const nameInput = document.getElementById('full_name');
        const suggestionsDiv = document.getElementById('suggestions');
        let debounceTimer;

        nameInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`../payments/get_people.php?search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            suggestionsDiv.innerHTML = data.map(person =>
                                `<div class="suggestion-item" style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;" data-name="${person.name}">
                                    <i class="fas fa-user"></i> ${person.name}
                                    <small style="color: #666; margin-left: 10px;">(${person.source === 'cedula' ? 'Has cedula record' : 'Payment record'})</small>
                                </div>`
                            ).join('');
                            suggestionsDiv.style.display = 'block';

                            document.querySelectorAll('.suggestion-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    const name = this.dataset.name;
                                    nameInput.value = name;
                                    suggestionsDiv.style.display = 'none';

                                    fetch(
                                            `get_person.php?name=${encodeURIComponent(name)}`
                                        )
                                        .then(response => response.json())
                                        .then(personData => {
                                            if (!personData.error) {
                                                if (personData.address) document
                                                    .getElementById('address')
                                                    .value = personData.address;
                                                if (personData.birth_date) {
                                                    document.getElementById(
                                                            'birth_date')
                                                        .value = personData
                                                        .birth_date;
                                                    calculateAge();
                                                }
                                                if (personData.surname) document
                                                    .getElementById('surname')
                                                    .value = personData.surname;
                                                if (personData.first_name)
                                                    document
                                                    .getElementById(
                                                        'first_name')
                                                    .value = personData
                                                    .first_name;
                                                if (personData.middle_name)
                                                    document
                                                    .getElementById(
                                                        'middle_name')
                                                    .value = personData
                                                    .middle_name;
                                                if (personData.citizenship)
                                                    document
                                                    .getElementById(
                                                        'citizenship')
                                                    .value = personData
                                                    .citizenship;
                                                if (personData.icr_no) document
                                                    .getElementById('icr_no')
                                                    .value = personData.icr_no;
                                                if (personData.place_of_issue)
                                                    document
                                                    .getElementById(
                                                        'place_of_issue')
                                                    .value = personData
                                                    .place_of_issue;
                                                if (personData.year_issued)
                                                    document
                                                    .getElementById(
                                                        'year_issued')
                                                    .value = personData
                                                    .year_issued;
                                                if (personData.sex) document
                                                    .getElementById('sex')
                                                    .value = personData.sex;
                                                if (personData.birth_place)
                                                    document.getElementById(
                                                        'birth_place').value =
                                                    personData.birth_place;
                                                if (personData.civil_status)
                                                    document.getElementById(
                                                        'civil_status').value =
                                                    personData.civil_status;
                                                if (personData.occupation)
                                                    document.getElementById(
                                                        'occupation').value =
                                                    personData.occupation;
                                                if (personData.tin) document
                                                    .getElementById('tin')
                                                    .value = personData.tin;
                                                if (personData.height) document
                                                    .getElementById('height')
                                                    .value = personData.height;
                                                if (personData.weight) document
                                                    .getElementById('weight')
                                                    .value = personData.weight;

                                                syncFullName();

                                                alert(
                                                    '✓ Information auto-filled from previous record!'
                                                );
                                            }
                                        });
                                });

                                item.addEventListener('mouseenter', function() {
                                    this.style.background = '#f0f4f8';
                                });

                                item.addEventListener('mouseleave', function() {
                                    this.style.background = 'white';
                                });
                            });
                        } else {
                            suggestionsDiv.style.display = 'none';
                        }
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (e.target !== nameInput && e.target !== suggestionsDiv) {
                suggestionsDiv.style.display = 'none';
            }
        });

        function syncFullName() {
            const surname = document.getElementById('surname').value.trim();
            const first = document.getElementById('first_name').value.trim();
            const middle = document.getElementById('middle_name').value.trim();
            if (!surname && !first && !middle) {
                return;
            }
            let fullName = '';
            if (surname && (first || middle)) {
                fullName = surname + ', ' + first + (middle ? ' ' + middle : '');
            } else {
                fullName = surname || first || middle;
            }
            document.getElementById('full_name').value = fullName;
        }

        ['surname', 'first_name', 'middle_name'].forEach((fieldId) => {
            const field = document.getElementById(fieldId);
            if (!field) {
                return;
            }
            field.addEventListener('input', syncFullName);
        });

        document.addEventListener('DOMContentLoaded', computeTotals);

        (function() {
            const forms = Array.from(document.querySelectorAll('form'));
            if (!forms.length) {
                return;
            }

            function serializeForm(form) {
                const data = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of data.entries()) {
                    params.append(key, value);
                }
                return params.toString();
            }

            const formSnapshots = new Map();
            forms.forEach((form) => {
                formSnapshots.set(form, serializeForm(form));
                form.addEventListener('submit', () => {
                    form.dataset.submitting = 'true';
                });
            });

            window.addEventListener('beforeunload', function(event) {
                const hasUnsaved = forms.some((form) => {
                    if (form.dataset.submitting === 'true') {
                        return false;
                    }
                    return serializeForm(form) !== formSnapshots.get(form);
                });

                if (!hasUnsaved) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            });
        })();
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>





