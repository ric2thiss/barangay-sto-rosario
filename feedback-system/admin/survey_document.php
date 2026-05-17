<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: surveys.php");
    exit();
}

$survey_id = intval($_GET['id']);

// AJAX Update Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_question') {
    $q_id = intval($_POST['question_id']);
    $new_text = trim($_POST['question_text']);

    $stmt = $conn->prepare("UPDATE survey_questions SET question_text = ? WHERE id = ?");
    $stmt->bind_param("si", $new_text, $q_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// AJAX Update Option
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_option') {
    $q_id = intval($_POST['question_id']);
    $opt_index = intval($_POST['opt_index']);
    $new_text = trim($_POST['option_text']);

    // Fetch current options
    $stmt = $conn->prepare("SELECT options FROM survey_questions WHERE id = ?");
    $stmt->bind_param("i", $q_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res && !empty($res['options'])) {
        $opts = json_decode($res['options'], true);
        if (isset($opts[$opt_index])) {
            $opts[$opt_index] = $new_text;
            $new_options_json = json_encode(array_values(array_filter(array_map('trim', $opts))));

            $u_stmt = $conn->prepare("UPDATE survey_questions SET options = ? WHERE id = ?");
            $u_stmt->bind_param("si", $new_options_json, $q_id);
            if ($u_stmt->execute()) {
                echo json_encode(['success' => true]);
                exit();
            }
        }
    }
    echo json_encode(['success' => false]);
    exit();
}

// AJAX Update Survey Details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_survey') {
    $s_id = intval($_POST['survey_id']);
    $field = $_POST['field']; // 'title' or 'description'
    $new_value = trim($_POST['value']);

    if (in_array($field, ['title', 'description'])) {
        $stmt = $conn->prepare("UPDATE surveys SET " . $field . " = ? WHERE id = ?");
        $stmt->bind_param("si", $new_value, $s_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit();
        }
    }
    echo json_encode(['success' => false]);
    exit();
}

// Fetch Survey
$stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if (!$survey) {
    echo "Survey not found.";
    exit();
}

// Fetch Questions
$questions = $conn->query("SELECT * FROM survey_questions WHERE survey_id = $survey_id ORDER BY order_num ASC");

// Add Question Logic
if (isset($_POST['add_question'])) {
    $q_text = trim($_POST['question_text']);
    $q_type = $_POST['question_type'];
    $req = isset($_POST['is_required']) ? 1 : 0;

    $options = NULL;
    if ($q_type == 'multiple_choice' && !empty($_POST['options'])) {
        $raw = explode(",", trim($_POST['options'])); // Comma separated for inline text input
        $options = json_encode(array_values(array_filter(array_map('trim', $raw))));
    }

    $max = $conn->query("SELECT MAX(order_num) as m FROM survey_questions WHERE survey_id = $survey_id")->fetch_assoc()['m'] ?? 0;
    $order = $max + 1;

    $stmt = $conn->prepare("INSERT INTO survey_questions (survey_id, question_text, question_type, options, is_required, order_num) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssii", $survey_id, $q_text, $q_type, $options, $req, $order);
    $stmt->execute();

    header("Location: survey_document.php?id=" . $survey_id);
    exit();
}

// Delete Question Logic
if (isset($_POST['delete_question'])) {
    $q_id = intval($_POST['delete_question_id']);
    $conn->query("DELETE FROM survey_questions WHERE id=$q_id");
    header("Location: survey_document.php?id=" . $survey_id);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Survey Document - <?php echo htmlspecialchars($survey['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Document Styles */
        body {
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .document-container {
            background-color: white;
            width: 21cm;
            min-height: 29.7cm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 1.5cm 2cm;
            box-sizing: border-box;
            position: relative;
            margin-bottom: 20px;
        }

        /* Header Grid */
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }

        .header-text {
            text-align: center;
            line-height: 1.2;
        }

        @font-face {
            font-family: 'Forte';
            src: local('Forte'), local('ForteMT');
        }

        .header-rep {
            font-family: 'Forte', cursive, sans-serif;
            font-size: 10pt;
        }

        .header-prov,
        .header-muni,
        .header-brgy {
            font-family: 'Sitka Small', 'Georgia', serif;
            font-size: 10pt;
        }

        .header-brgy {
            font-weight: bold;
        }

        .header-address,
        .header-contact {
            font-family: 'Century Gothic', sans-serif;
            font-size: 9pt;
        }

        .survey-title {
            text-align: center;
            font-family: 'Arial', sans-serif;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .survey-desc {
            text-align: center;
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            margin-bottom: 30px;
            font-style: italic;
        }

        .question-list {
            margin-bottom: 30px;
        }

        .q-item {
            margin-bottom: 20px;
            position: relative;
        }

        .q-text {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 5px;
        }

        .editable-text {
            padding: 2px 5px;
            border-radius: 3px;
            transition: background 0.2s;
            border: 1px transparent dashed;
            display: inline-block;
            min-width: 50px;
        }

        .editable-text:hover {
            background: #f1f5f9;
            cursor: text;
        }

        .editable-text:focus {
            background: #fff;
            border-color: #cbd5e1;
            outline: none;
        }

        .q-options {
            margin-left: 20px;
            font-size: 11pt;
        }

        .q-options div {
            margin-bottom: 3px;
        }

        .delete-btn {
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 10pt;
            padding: 0;
            margin-left: 10px;
            opacity: 0.5;
            transition: opacity 0.2s;
        }

        .delete-btn:hover {
            opacity: 1;
        }

        /* Print Styles */
        @page {
            size: A4;
            margin: 1.5cm 2cm;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .document-container {
                box-shadow: none;
                width: 100%;
                min-height: auto;
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .editable-text {
                border: none !important;
            }
        }

        /* Inline Editing section */
        .add-inline-container {
            margin-top: 30px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 20px;
        }

        .inline-add-input {
            border: none;
            border-bottom: 1px solid #cbd5e1;
            background: transparent;
            width: 100%;
            font-size: 12pt;
            padding: 5px 0;
            outline: none;
            font-family: inherit;
            margin-bottom: 10px;
        }

        .inline-add-input:focus {
            border-bottom: 2px solid #1F3A93;
        }

        .inline-add-select {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 12px;
            color: #475569;
            outline: none;
        }

        .inline-add-btn {
            background: #1F3A93;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-family: inherit;
        }

        .inline-add-btn:hover {
            background: #152c71;
        }

        .btn-primary {
            background: #1F3A93;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-family: 'Arial', sans-serif;
        }

        .btn-primary:hover {
            background: #152c71;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-family: 'Arial', sans-serif;
        }

        .btn-secondary:hover {
            background: #475569;
        }
    </style>
    <script>
        function toggleOptions(val) {
            document.getElementById('opts_container').style.display = (val === 'multiple_choice') ? 'block' : 'none';
            if (val === 'multiple_choice') {
                document.getElementById('opts_container').required = true;
            } else {
                document.getElementById('opts_container').required = false;
            }
        }

        function saveQuestionText(qId, inputElement) {
            const newText = inputElement.innerText.trim();
            if (newText === '') {
                // If empty, reset or warn
                alert('Question text cannot be empty');
                return;
            }

            fetch('survey_document.php?id=<?php echo $survey_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_question&question_id=' + qId + '&question_text=' + encodeURIComponent(newText)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        inputElement.style.backgroundColor = '#dcfce3';
                        setTimeout(() => inputElement.style.backgroundColor = 'transparent', 500);
                    } else {
                        alert('Error saving question.');
                    }
                })
                .catch(err => console.error(err));
        }

        function saveOptionText(qId, optIndex, inputElement) {
            const newText = inputElement.innerText.trim();
            if (newText === '') {
                alert('Option text cannot be empty');
                return;
            }
            fetch('survey_document.php?id=<?php echo $survey_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_option&question_id=' + qId + '&opt_index=' + optIndex + '&option_text=' + encodeURIComponent(newText)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        inputElement.style.backgroundColor = '#dcfce3';
                        setTimeout(() => inputElement.style.backgroundColor = 'transparent', 500);
                    } else {
                        alert('Error saving option.');
                    }
                })
                .catch(err => console.error(err));
        }

        function saveSurveyDetails(field, inputElement) {
            const newValue = inputElement.innerText.trim();
            if (field === 'title' && newValue === '') {
                alert('Title cannot be empty');
                return;
            }
            fetch('survey_document.php?id=<?php echo $survey_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_survey&survey_id=<?php echo $survey_id; ?>&field=' + field + '&value=' + encodeURIComponent(newValue)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        inputElement.style.backgroundColor = '#dcfce3';
                        setTimeout(() => inputElement.style.backgroundColor = 'transparent', 500);
                    } else {
                        alert('Error saving ' + field + '.');
                    }
                })
                .catch(err => console.error(err));
        }

        // Prevent pressing enter from creating new lines in contenteditable
        document.addEventListener('DOMContentLoaded', () => {
            const editables = document.querySelectorAll('.editable-text');
            editables.forEach(el => {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        el.blur(); // Trigger save
                    }
                });
            });
        });
    </script>
</head>

<body>

    <div class="no-print" style="width: 21cm; display: flex; justify-content: space-between; margin-bottom: 10px;">
        <a href="surveys.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Surveys</a>
        <div style="color: #64748b; font-size: 14px; align-self: center;">
            <i class="fas fa-info-circle"></i> Click any question text to edit it inline
        </div>
        <button onclick="window.print()" class="btn-primary" style="display: flex; align-items: center; gap: 8px;"><i
                class="fas fa-print"></i> Print Document</button>
    </div>

    <div class="document-container">
        <div class="doc-header">
            <!-- Left Logo: Rosario -->
            <img src="../img/rosario_logo.png" alt="Rosario Logo" class="header-logo"
                onerror="this.src='../img/logo.png'">

            <!-- Center Text -->
            <div class="header-text">
                <div class="header-rep">Republic of the Philippines</div>
                <div class="header-prov">Province of Agusan del Norte</div>
                <div class="header-muni">Municipality of Magallanes</div>
                <div class="header-brgy">Barangay Sto. Rosario</div>
                <div class="header-address">Barangay Hall, Purok 1, Brgy. Sto. Rosario, Magallanes, Agusan del Norte
                </div>
                <div class="header-contact">Tel No. (085) 806-0050 | Email Address: barangaystorosario2t@gmail.com</div>
            </div>

            <!-- Right Logo: ADN -->
            <img src="../img/adn_logo.png" alt="ADN Logo" class="header-logo" onerror="this.src='../img/logo.png'">
        </div>

        <div class="survey-title">
            <span contenteditable="true" onblur="saveSurveyDetails('title', this)" class="editable-text"
                title="Click to Edit Title"><?php echo htmlspecialchars($survey['title']); ?></span>
        </div>
        <div class="survey-desc">
            <span contenteditable="true" onblur="saveSurveyDetails('description', this)" class="editable-text"
                title="Click to Edit Description"
                style="min-width: 200px; display: inline-block; min-height: 1.5em;"><?php echo nl2br(htmlspecialchars($survey['description'])); ?></span>
        </div>

        <div class="question-list">
            <?php if ($questions->num_rows > 0): ?>
                <?php $i = 1;
                while ($q = $questions->fetch_assoc()): ?>
                    <div class="q-item">
                        <div class="q-text">
                            <?php echo $i++; ?>.
                            <span contenteditable="true" onblur="saveQuestionText(<?php echo $q['id']; ?>, this)"
                                class="editable-text" title="Click to Edit">
                                <?php echo htmlspecialchars($q['question_text']); ?>
                            </span>
                            <?php if ($q['is_required']): ?><span style="color:#ef4444;">*</span><?php endif; ?>

                            <form method="POST" class="no-print" style="display:inline;"
                                onsubmit="return confirm('Delete this question?')">
                                <input type="hidden" name="delete_question_id" value="<?php echo $q['id']; ?>">
                                <button type="submit" name="delete_question" class="delete-btn" title="Delete Question"><i
                                        class="fas fa-times"></i></button>
                            </form>
                        </div>
                        <div class="q-options">
                            <?php
                            if ($q['question_type'] == 'multiple_choice' && $q['options']) {
                                $opts = json_decode($q['options']);
                                foreach ($opts as $index => $o) {
                                    echo "<div>[ &nbsp; ] <span contenteditable='true' class='editable-text' onblur='saveOptionText(" . $q['id'] . ", " . $index . ", this)' title='Click to Edit'>" . htmlspecialchars($o) . "</span></div>";
                                }
                            } elseif ($q['question_type'] == 'yes_no') {
                                echo "<div>[ &nbsp; ] Yes</div>";
                                echo "<div>[ &nbsp; ] No</div>";
                            } elseif ($q['question_type'] == 'rating') {
                                echo "<div>[ &nbsp; ] 1  &nbsp; [ &nbsp; ] 2  &nbsp; [ &nbsp; ] 3  &nbsp; [ &nbsp; ] 4  &nbsp; [ &nbsp; ] 5</div>";
                            } else {
                                echo "<div style='border-bottom: 1px solid #000; height: 30px; width: 100%; max-width: 600px;'></div>";
                                echo "<div style='border-bottom: 1px solid #000; height: 30px; width: 100%; max-width: 600px;'></div>";
                            }
                            ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; color:#94a3b8; font-style:italic;" class="no-print">No questions added yet.
                </div>
            <?php endif; ?>
        </div>

        <!-- Add Question Form Inline -->
        <div class="no-print add-inline-container">
            <div style="font-weight: 600; color: #1a317d; margin-bottom: 10px; font-size: 13px;">
                <i class="fas fa-plus"></i> ADD NEW QUESTION (Will not appear in print)
            </div>
            <form method="POST" style="margin: 0;">
                <input type="text" name="question_text" class="inline-add-input"
                    placeholder="Type question text here..." required autocomplete="off">
                <input type="text" name="options" id="opts_container" class="inline-add-input"
                    placeholder="Options (comma separated, e.g., Cat, Dog, Bird)"
                    style="display: none; font-size: 11pt; color: #475569;">

                <div style="display:flex; gap: 15px; align-items: center; margin-top: 5px;">
                    <select name="question_type" class="inline-add-select" onchange="toggleOptions(this.value)">
                        <option value="text">Text / Comment</option>
                        <option value="rating">Star Rating (1-5)</option>
                        <option value="yes_no">Yes / No</option>
                        <option value="multiple_choice">Multiple Choice</option>
                    </select>
                    <label style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" name="is_required" checked> Required
                    </label>
                    <button type="submit" name="add_question" class="inline-add-btn">
                        <i class="fas fa-save"></i> Save Question
                    </button>
                </div>
            </form>
        </div>

    </div>

</body>

</html>