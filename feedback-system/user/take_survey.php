<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireUser();

if (!isset($_GET['id'])) {
    header("Location: surveys.php");
    exit();
}

$survey_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch Survey
$stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ? AND status = 'Active'");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if (!$survey) {
    echo __('survey_not_found');
    exit();
}

// Check if already taken
$check_sql = "SELECT id FROM survey_responses WHERE user_id = $user_id AND survey_id = $survey_id LIMIT 1";
if ($conn->query($check_sql)->num_rows > 0) {
    // Redirect or show message
    echo "<script>alert('" . __('already_taken_survey') . "'); window.location.href='surveys.php';</script>";
    exit();
}

// Fetch Questions
$q_sql = "SELECT * FROM survey_questions WHERE survey_id = $survey_id ORDER BY order_num ASC";
$questions = $conn->query($q_sql);

// Handle Submission
if (isset($_POST['submit_survey'])) {
    foreach ($_POST['answers'] as $q_id => $answer) {
        $q_id = intval($q_id);

        // Sanitize
        if (is_array($answer)) {
            $answer = implode(', ', $answer); // Handle multiple choice checkboxes if any
        }
        $answer = trim($answer);

        if (!empty($answer)) {
            $stmt = $conn->prepare("INSERT INTO survey_responses (survey_id, user_id, question_id, answer_text) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $survey_id, $user_id, $q_id, $answer);
            $stmt->execute();
        }
    }

    $_SESSION['success_message'] = __('thank_you_feedback');
    header("Location: surveys.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('take_survey'); ?> - <?php echo htmlspecialchars(__($survey['title'])); ?></title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../css/theme.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #f8fafc;
            display: flex;
            justify-content: center;
            padding: 40px 20px;
        }

        .survey-container {
            width: 100%;
            max-width: 700px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .question-box {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .question-text {
            font-weight: 600;
            margin-bottom: 15px;
            color: #0f172a;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F3A93;
        }

        .radio-option {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            align-items: center;
        }

        .btn-submit {
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
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
            color: #000;
            /* Matching the official document */
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

        /* Mobile View Styles */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .survey-container {
                padding: 20px;
            }

            .doc-header {
                position: relative;
                flex-direction: column;
                border-bottom: 2px solid #000;
                padding-top: 60px;
                /* Space for absolute logos */
                gap: 10px;
            }

            .doc-header img:first-child {
                position: absolute;
                top: 0;
                left: 0;
                width: 60px;
                height: 60px;
            }

            .doc-header img:last-child {
                position: absolute;
                top: 0;
                right: 0;
                width: 60px;
                height: 60px;
            }

            .header-text {
                margin-top: 10px;
                text-align: center;
                line-height: 1.3;
            }

            .header-address,
            .header-contact {
                font-size: 8pt;
            }

            .header-rep,
            .header-prov,
            .header-muni,
            .header-brgy {
                font-size: 9pt;
            }

            h1 {
                font-size: 22px;
                line-height: 1.4;
            }

            .radio-group {
                flex-wrap: wrap;
                gap: 10px !important;
            }
        }
    </style>
</head>

<body>

    <div class="survey-container">
        <div class="doc-header">
            <!-- Left Logo: Rosario -->
            <img src="../img/rosario_logo.png" alt="Rosario Logo" class="header-logo"
                onerror="this.src='../img/logo.png'">

            <!-- Center Text -->
            <div class="header-text">
                <div class="header-rep"><?php echo __('republic_ph'); ?></div>
                <div class="header-prov"><?php echo __('province_adn'); ?></div>
                <div class="header-muni"><?php echo __('muni_magallanes'); ?></div>
                <div class="header-brgy"><?php echo __('brgy_rosario'); ?></div>
                <div class="header-address"><?php echo __('brgy_address'); ?></div>
                <div class="header-contact"><?php echo __('tel_email'); ?></div>
            </div>

            <!-- Right Logo: ADN -->
            <img src="../img/adn_logo.png" alt="ADN Logo" class="header-logo" onerror="this.src='../img/logo.png'">
        </div>

        <h1 style="color: #1a317d; text-align: center; margin-bottom: 10px;">
            <?php echo htmlspecialchars(__($survey['title'])); ?>
        </h1>
        <p style="text-align: center; color: #64748b; margin-bottom: 40px;">
            <?php echo htmlspecialchars(__($survey['description'])); ?>
        </p>

        <form method="POST">
            <?php $i = 1;
            while ($q = $questions->fetch_assoc()): ?>
                <div class="question-box">
                    <div class="question-text">
                        <?php echo $i++ . '. ' . htmlspecialchars(__($q['question_text'])); ?>
                        <?php if ($q['is_required'])
                            echo '<span style="color:red">*</span>'; ?>
                    </div>

                    <?php if ($q['question_type'] == 'text'): ?>
                        <textarea name="answers[<?php echo $q['id']; ?>]" class="form-control" rows="3" <?php echo $q['is_required'] ? 'required' : ''; ?>></textarea>

                    <?php elseif ($q['question_type'] == 'rating'): ?>
                        <div class="radio-group" style="display: flex; gap: 20px;">
                            <?php for ($r = 1; $r <= 5; $r++): ?>
                                <label class="radio-option">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $r; ?>" <?php echo $q['is_required'] ? 'required' : ''; ?>>
                                    <?php echo $r; ?>
                                </label>
                            <?php endfor; ?>
                        </div>

                    <?php elseif ($q['question_type'] == 'yes_no'): ?>
                        <div class="radio-group" style="display: flex; gap: 20px;">
                            <label class="radio-option"><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="Yes"
                                    <?php echo $q['is_required'] ? 'required' : ''; ?>> <?php echo __('yes_no_yes'); ?></label>
                            <label class="radio-option"><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="No"
                                    <?php echo $q['is_required'] ? 'required' : ''; ?>> <?php echo __('yes_no_no'); ?></label>
                        </div>

                    <?php elseif ($q['question_type'] == 'multiple_choice'): ?>
                        <?php
                        $opts = json_decode($q['options']);
                        if ($opts) {
                            foreach ($opts as $opt) {
                                echo '<label class="radio-option">';
                                echo '<input type="radio" name="answers[' . $q['id'] . ']" value="' . htmlspecialchars($opt) . '" ' . ($q['is_required'] ? 'required' : '') . '>';
                                echo htmlspecialchars($opt);
                                echo '</label>';
                            }
                        }
                        ?>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

            <button type="submit" name="submit_survey" class="btn-submit"><?php echo __('submit_responses'); ?></button>
            <a href="surveys.php"
                style="display: block; text-align: center; margin-top: 20px; color: #64748b; text-decoration: none;"><?php echo __('cancel_survey'); ?></a>
        </form>
    </div>

</body>

</html>