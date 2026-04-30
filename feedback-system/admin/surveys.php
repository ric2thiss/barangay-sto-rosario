<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

// Auto-Close Expired Surveys
$current_date = date('Y-m-d');
$conn->query("UPDATE surveys SET status = 'Closed' WHERE end_date < '$current_date' AND status = 'Active'");

// --- AJAX HANDLERS ---

// AJAX: Fetch Edit Survey Modal Content
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'edit_modal' && isset($_GET['id'])) {
    $survey_id = intval($_GET['id']);

    // Fetch Survey
    $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $survey = $stmt->get_result()->fetch_assoc();

    // Fetch Questions
    $questions = $conn->query("SELECT * FROM survey_questions WHERE survey_id = $survey_id ORDER BY order_num ASC");

    ?>
    <div class="modal-header">
        <h3><i class="fas fa-edit"></i> Edit Survey Questions:
            <?php echo htmlspecialchars($survey['title']); ?>
        </h3>
        <div style="display:flex; gap:10px;">
            <button onclick="closeModal('editSurveyModal')" class="btn-primary"
                style="padding: 5px 15px; font-size: 13px; background: white; color: #1F3A93; border: none; box-shadow: none;"><i
                    class="fas fa-check"></i> Done</button>
            <button class="modal-close" onclick="closeModal('editSurveyModal')"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="modal-body">
        <div style="display: flex; gap: 20px;">
            <!-- Questions List -->
            <div style="flex: 3; max-height: 60vh; overflow-y: auto; padding-right: 10px;">
                <h4 style="margin-bottom: 15px;">Current Questions</h4>
                <?php if ($questions->num_rows > 0): ?>
                    <?php $i = 1;
                    while ($q = $questions->fetch_assoc()): ?>
                        <div class="question-item"
                            style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between;">
                            <div>
                                <strong>Q
                                    <?php echo $i++; ?>.
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                </strong>
                                <div style="font-size: 12px; color: #64748b; margin-top: 5px;">
                                    Type:
                                    <?php echo str_replace('_', ' ', $q['question_type']); ?>
                                    <?php if ($q['options'])
                                        echo '| Options: ' . implode(', ', json_decode($q['options'])); ?>
                                    <?php if ($q['is_required'])
                                        echo '| Required'; ?>
                                </div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this question?')" style="margin: 0;">
                                <input type="hidden" name="delete_question_id" value="<?php echo $q['id']; ?>">
                                <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
                                <button type="submit" name="delete_question"
                                    style="color: #ef4444; background: none; border: none; cursor: pointer;"><i
                                        class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #94a3b8; font-style: italic;">No questions yet.</p>
                <?php endif; ?>
            </div>

            <!-- Add Form -->
            <div style="flex: 2; background: #f0f7ff; padding: 20px; border-radius: 12px; height: fit-content;">
                <h4 style="margin-bottom: 15px; color: #1a317d;">Add New Question</h4>
                <form method="POST">
                    <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">

                    <div class="form-group">
                        <label class="form-label">Question Text</label>
                        <textarea name="question_text" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="question_type" class="form-control" id="q_type_<?php echo $survey_id; ?>"
                            onchange="toggleOptions(this.value, 'opts_<?php echo $survey_id; ?>')">
                            <option value="text">Text / Comment</option>
                            <option value="rating">Star Rating (1-5)</option>
                            <option value="yes_no">Yes / No</option>
                            <option value="multiple_choice">Multiple Choice</option>
                        </select>
                    </div>

                    <div class="form-group" id="opts_<?php echo $survey_id; ?>" style="display: none;">
                        <label class="form-label">Options (One per line)</label>
                        <textarea name="options" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label><input type="checkbox" name="is_required" checked> Required</label>
                    </div>

                    <button type="submit" name="add_question" class="btn-primary" style="width: 100%;">Add Question</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    exit();
}

// AJAX: Fetch Results Modal Content
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'results_modal' && isset($_GET['id'])) {
    $survey_id = intval($_GET['id']);

    // Fetch Survey
    $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $survey = $stmt->get_result()->fetch_assoc();

    // Fetch Questions
    $questions = $conn->query("SELECT * FROM survey_questions WHERE survey_id = $survey_id ORDER BY order_num ASC");
    ?>
    <div class="modal-header">
        <h3><i class="fas fa-eye"></i> Results:
            <?php echo htmlspecialchars($survey['title']); ?>
        </h3>
        <div style="display:flex; gap:10px;">
            <button class="modal-close" onclick="closeModal('resultsSurveyModal')"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="modal-body">

        <!-- Tabs for Results / Respondents -->
        <div style="border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; display: flex; gap: 20px;">
            <button onclick="switchTab('questions')" id="tab-questions"
                style="padding: 10px 0; background: none; border: none; border-bottom: 2px solid #1F3A93; color: #1F3A93; font-weight: 600; cursor: pointer;">Questions
                Analysis</button>
            <button onclick="switchTab('respondents')" id="tab-respondents"
                style="padding: 10px 0; background: none; border: none; border-bottom: 2px solid transparent; color: #64748b; font-weight: 600; cursor: pointer;">Respondents
                List</button>
        </div>

        <!-- Questions View -->
        <div id="view-questions">
            <?php while ($q = $questions->fetch_assoc()): ?>
                <div style="margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                    <h4 style="margin-bottom: 10px;">Q.
                        <?php echo htmlspecialchars($q['question_text']); ?>
                    </h4>
                    <?php
                    $a_sql = "SELECT answer_text, COUNT(*) as count FROM survey_responses WHERE question_id = {$q['id']} GROUP BY answer_text";
                    if ($q['question_type'] == 'text') {
                        $text_res = $conn->query("SELECT answer_text, created_at FROM survey_responses WHERE question_id = {$q['id']} ORDER BY created_at DESC LIMIT 10");
                        if ($text_res->num_rows > 0) {
                            while ($ans = $text_res->fetch_assoc()) {
                                echo '<div style="background: #f8fafc; padding: 8px; margin-bottom: 5px; border-radius: 5px; font-size: 13px;">' . htmlspecialchars($ans['answer_text']) . '</div>';
                            }
                        } else {
                            echo '<i style="color:#cbd5e1">No responses.</i>';
                        }
                    } else {
                        $agg = $conn->query($a_sql);
                        $total = 0;
                        $data = [];
                        while ($row = $agg->fetch_assoc()) {
                            $data[] = $row;
                            $total += $row['count'];
                        }
                        if ($total > 0) {
                            foreach ($data as $d) {
                                $pct = round(($d['count'] / $total) * 100);
                                echo '<div style="margin-bottom: 5px; font-size: 13px;">';
                                echo '<div style="display:flex; justify-content:space-between;"><span>' . $d['answer_text'] . '</span><span>' . $d['count'] . ' (' . $pct . '%)</span></div>';
                                echo '<div style="background:#e2e8f0; height:8px; border-radius:4px;"><div style="background:#1F3A93; width:' . $pct . '%; height:100%; border-radius:4px;"></div></div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<i style="color:#cbd5e1">No responses.</i>';
                        }
                    }
                    ?>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Respondents View -->
        <div id="view-respondents" style="display: none;">
            <?php
            // Fetch Respondents
            $resp_sql = "SELECT u.first_name as firstname, u.surname as lastname, u.purok, MIN(sr.created_at) as date_taken 
                         FROM survey_responses sr 
                         JOIN `profiling-system`.residents u ON sr.user_id = u.id 
                         WHERE sr.survey_id = ? 
                         GROUP BY u.id 
                         ORDER BY date_taken DESC";
            $resp_stmt = $conn->prepare($resp_sql);
            $resp_stmt->bind_param("i", $survey_id);
            $resp_stmt->execute();
            $respondents = $resp_stmt->get_result();
            ?>

            <p style="margin-bottom: 15px; font-weight: 500; color: #1a317d;">
                Total Respondents: <span
                    style="background: #1F3A93; color: white; padding: 2px 8px; border-radius: 12px; font-size: 14px;">
                    <?php echo $respondents->num_rows; ?>
                </span>
            </p>

            <?php if ($respondents->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: #f0f7ff; color: #1a317d;">
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #bae6fd;">Name</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #bae6fd;">Purok</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #bae6fd;">Date Taken</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $respondents->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px;">
                                        <?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?>
                                    </td>
                                    <td style="padding: 10px;">
                                        <?php echo htmlspecialchars($r['purok']); ?>
                                    </td>
                                    <td style="padding: 10px; color: #64748b;">
                                        <?php echo date('M d, Y h:i A', strtotime($r['date_taken'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; color: #94a3b8;">
                    <i class="fas fa-users-slash" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p>No respondents yet.</p>
                </div>
            <?php endif; ?>
        </div>


    </div>
    <?php
    exit();
}

// --- FORM HANDLING ---

// Create Survey
if (isset($_POST['create_survey'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    // $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : NULL; // Removed
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $created_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO surveys (title, description, created_by, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, 'Active')");
    $stmt->bind_param("ssiss", $title, $desc, $created_by, $start, $end);
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $_SESSION['success_message'] = "Survey created! You can now add questions in the new document tab.";
        // $_SESSION['open_edit_modal'] = $new_id; // Auto-open edit modal removed
        header("Location: survey_document.php?id=$new_id");
        exit();
    }
    header("Location: surveys.php");
    exit();
}

// Add Question
if (isset($_POST['add_question'])) {
    $s_id = intval($_POST['survey_id']);
    $q_text = trim($_POST['question_text']);
    $q_type = $_POST['question_type'];
    $req = isset($_POST['is_required']) ? 1 : 0;

    $options = NULL;
    if ($q_type == 'multiple_choice' && !empty($_POST['options'])) {
        $raw = explode("\n", trim($_POST['options']));
        $options = json_encode(array_values(array_filter(array_map('trim', $raw))));
    }

    $max = $conn->query("SELECT MAX(order_num) as m FROM survey_questions WHERE survey_id = $s_id")->fetch_assoc()['m'] ?? 0;
    $order = $max + 1;

    $stmt = $conn->prepare("INSERT INTO survey_questions (survey_id, question_text, question_type, options, is_required, order_num) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssii", $s_id, $q_text, $q_type, $options, $req, $order);
    $stmt->execute();

    $_SESSION['open_edit_modal'] = $s_id; // Keep modal open
    header("Location: surveys.php");
    exit();
}

// Delete Question
if (isset($_POST['delete_question'])) {
    $q_id = intval($_POST['delete_question_id']);
    $s_id = intval($_POST['survey_id']);
    $conn->query("DELETE FROM survey_questions WHERE id=$q_id");
    $_SESSION['open_edit_modal'] = $s_id; // Keep modal open
    header("Location: surveys.php");
    exit();
}

// Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = $_GET['toggle_status'];

    if ($status === 'Active') {
        // If reactivating, check if expired and extend to today if needed
        $s = $conn->query("SELECT end_date FROM surveys WHERE id = $id")->fetch_assoc();
        if ($s && $s['end_date'] < date('Y-m-d')) {
            $conn->query("UPDATE surveys SET end_date = CURDATE() WHERE id = $id");
            $_SESSION['success_message'] = "Survey reactivated. End date extended to today.";
        } else {
            $_SESSION['success_message'] = "Survey activated.";
        }
    } else {
        $_SESSION['success_message'] = "Survey closed.";
    }

    $stmt = $conn->prepare("UPDATE surveys SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    header("Location: surveys.php");
    exit();
}

// Update Survey Dates
if (isset($_POST['update_dates'])) {
    $id = intval($_POST['survey_id']);
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $stmt = $conn->prepare("UPDATE surveys SET start_date = ?, end_date = ? WHERE id = ?");
    $stmt->bind_param("ssi", $start, $end, $id);
    $stmt->execute();
    $_SESSION['success_message'] = "Survey dates updated.";
    header("Location: surveys.php");
    exit();
}

// Delete Survey
if (isset($_POST['delete_survey'])) {
    $id = intval($_POST['survey_id']);
    $conn->query("DELETE FROM surveys WHERE id=$id");
    $_SESSION['success_message'] = "Survey deleted.";
    header("Location: surveys.php");
    exit();
}

// --- MAIN VIEW ---

// Fetch Surveys
$surveys = $conn->query("SELECT s.*, (SELECT COUNT(DISTINCT user_id) FROM survey_responses WHERE survey_id = s.id) as response_count FROM surveys s ORDER BY s.created_at DESC");

// Fetch Admins for Dropdown - REMOVED
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Surveys - Admin Dashboard</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/admin_dark_mode.css?v=<?php echo time(); ?>">
    <style>
        /* Exact styles copied from index.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }






        /* FIXED STICKY SIDEBAR */
        .sidebar {
            width: 280px;
            background: #1F3A93;
            color: #ffffff;
            height: 100vh;
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: none;
            display: flex;
            flex-direction: column;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .sidebar.closed {
            width: 80px;
        }

        /* Logo Area */
        .logo-area {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 25px;
            position: sticky;
            top: 0;
            background: #1F3A93;
            z-index: 10;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            background: white;
            padding: 2px;
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            transition: all 0.3s;
            white-space: nowrap;
            overflow: visible;
            flex: 1;
            min-width: 0;
        }

        .sidebar.closed .logo-text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            margin: 0;
            flex: 0;
        }

        /* Toggle Button */
        .toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
            flex-shrink: 0;
            position: relative;
            z-index: 11;
            margin-left: 10px;
        }

        .toggle-btn:hover {
            background: #ffffff;
            color: #1F3A93;
        }

        .toggle-btn .fa-times {
            display: block;
        }

        .toggle-btn .fa-bars {
            display: none;
        }

        .sidebar.closed .toggle-btn .fa-times {
            display: none;
        }

        .sidebar.closed .toggle-btn .fa-bars {
            display: block;
        }

        /* Login Indicator */
        .login-indicator {
            padding: 0 20px;
            margin-bottom: 30px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffffff, #bae6fd);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 600;
            color: #1F3A93;
            position: relative;
            flex-shrink: 0;
        }

        .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #1a317d;
            border: 2px solid #1F3A93;
            box-shadow: 0 0 0 2px #1a317d;
        }

        .user-details {
            transition: all 0.3s;
            overflow: hidden;
            flex: 1;
            min-width: 0;
        }

        .sidebar.closed .user-details {
            opacity: 0;
            visibility: hidden;
            width: 0;
            flex: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
            color: #ffffff !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Menu Items */
        .menu-items {
            list-style: none;
            padding: 0 15px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .menu-item {
            margin-bottom: 5px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .menu-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateX(5px);
        }

        .menu-link.active {
            background: #ffffff;
            color: #1F3A93;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .menu-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #ffffff;
            border-radius: 0 3px 3px 0;
        }

        .menu-icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .menu-link.active .menu-icon {
            transform: scale(1.1);
        }

        .menu-text {
            transition: all 0.3s;
            font-weight: 500;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .sidebar.closed .menu-text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            flex: 0;
        }

        /* Logout Section */
        .logout-section {
            padding: 20px;
            background: #1F3A93;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            position: sticky;
            bottom: 0;
        }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
            width: 100%;
            cursor: pointer;
            font-family: inherit;
            font-size: inherit;
        }

        .logout-link:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            transform: translateX(5px);
        }

        .logout-text {
            transition: all 0.3s;
            font-weight: 500;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .sidebar.closed .logout-text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            flex: 0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            transition: all 0.3s;
            overflow-y: auto;
            background: #f8fafc;
            margin-left: 280px;
            min-height: 100vh;
        }

        .sidebar.closed~.main-content {
            margin-left: 80px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }

        .page-title {
            font-size: 28px;
            color: #1a317d;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .title-icon {
            color: #1F3A93;
            margin-right: 10px;
        }

        /* Header Styles Matched to Dashboard */
        .theme-toggle-container .btn {
            background: #1F3A93;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(31, 58, 147, 0.2);
        }

        .theme-toggle-container .btn:hover {
            background: #152c71;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(31, 58, 147, 0.3);
        }

        .date-display {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 10px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        /* --- SURVEY SPECIFIC STYLES --- */
        .btn-primary {
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            padding: 12px 25px;
            border-radius: 12px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.3);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(31, 58, 147, 0.4);
        }

        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .table-container {
                padding: 15px;
            }

            th,
            td {
                padding: 10px;
                font-size: 13px;
            }
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        th {
            color: #94a3b8;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            background: white;
            padding: 20px 15px;
            vertical-align: middle;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-active {
            background: #e0f2fe;
            color: #1a317d;
        }

        .status-draft {
            background: #f1f5f9;
            color: #475569;
        }

        .status-closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 5px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-edit {
            background: #3b82f6;
        }

        .btn-results {
            background: #1F3A93;
        }

        .btn-delete {
            background: #ef4444;
        }

        .btn-active {
            background: #1F3A93;
        }

        .btn-close {
            background: #f59e0b;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 900px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: translateY(20px);
            transition: transform 0.3s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header-custom {
            padding: 25px 30px;
            background: #f0f7ff;
            border-bottom: 1px solid #bae6fd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header-custom h2 {
            color: #1a317d;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: #1a317d;
            cursor: pointer;
        }

        .modal-body-custom {
            padding: 30px;
            overflow-y: auto;
        }

        /* Form Inputs */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #334155;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F3A93;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }

        textarea.form-control {
            resize: vertical;
        }

        .sentiment-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            animation: slideInUp 0.5s;
        }

        .empty-state {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px dashed #cbd5e1;
        }

        /* --- DARK MODE OVERRIDES (Local Fallback) --- */
        body.dark-mode .table-container,
        body.dark-mode .sentiment-card {
            background: #1f2937 !important;
            border: 1px solid #374151;
            color: #e5e7eb;
        }

        body.dark-mode .empty-state {
            background: #374151 !important;
            border-color: #4b5563 !important;
            color: #9ca3af !important;
        }

        body.dark-mode td {
            background-color: #1f2937 !important;
            border-bottom-color: #374151 !important;
            color: #e5e7eb !important;
        }

        /* Fix Table Header Text - Light Blue for contrast against dark bg */
        body.dark-mode th {
            background-color: #374151 !important;
            color: #bae6fd !important;
            border-bottom-color: #4b5563 !important;
        }

        body.dark-mode tr:hover td {
            background-color: #374151 !important;
        }

        /* NEW CLASSES FOR REPLACEMENT */
        .card-header-title {
            color: #1a317d;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .sentiment-select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            color: #1a317d;
            font-size: 14px;
            outline: none;
            cursor: pointer;
            min-width: 200px;
        }

        /* Dark Mode for New Classes */
        body.dark-mode .card-header-title {
            color: #e5e7eb !important;
        }

        body.dark-mode .sentiment-select {
            background: #374151;
            border-color: #4b5563;
            color: #e5e7eb;
        }

        body.dark-mode .sentiment-select option {
            background: #374151;
            color: white;
        }

        body.dark-mode .sentiment-label {
            color: #d1d5db !important;
        }

        body.dark-mode .sentiment-summary {
            color: #9ca3af !important;
            border-top-color: #4b5563 !important;
        }

        body.dark-mode .sentiment-summary strong {
            color: #e5e7eb !important;
        }

        /* --- STANDARDIZED MODAL STYLES (From User Management & Manage Feedback) --- */

        /* Green Theme Modal (Create Survey) */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeInOverlay 0.4s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-container {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 3px solid #1F3A93;
            transform-origin: center bottom;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal-header {
            background: linear-gradient(135deg, #1F3A93, #152c71, #1e3a8a);
            background-size: 200% 200%;
            color: white;
            padding: 28px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            position: relative;
            overflow: hidden;
            animation: gradientShift 8s ease infinite;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .user-badge {
            background: #3b82f6;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            margin-left: 10px;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        /* ========== LOGOUT MODAL STYLES - GREEN THEME (Scoped to #logoutModal) ========== */

        #logoutModal .modal-container {
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
        }

        #logoutModal .modal-container.closing {
            animation: slideOutDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        #logoutModal .modal-header {
            justify-content: flex-start;
        }

        #logoutModal .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5, #1F3A93);
            animation: shimmer 3s linear infinite;
            background-size: 200% 100%;
        }

        #logoutModal .modal-header h2 {
            font-size: 24px;
            font-weight: 700;
            flex: 1;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #logoutModal h4 {
            text-align: center;
            color: #1a317d;
        }

        .modal-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            backdrop-filter: blur(10px);
            border: 3px solid #bae6fd;
            animation: iconPulse 2s ease-in-out infinite;
            box-shadow: 0 10px 30px rgba(31, 58, 147, 0.15);
            margin: 0 auto;
            color: #1F3A93;
        }

        #logoutModal .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        #logoutModal .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        #logoutModal .modal-body {
            padding: 32px;
            animation: fadeInBody 0.6s ease 0.2s both;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin: 20px;
            background: #f9fafb;
            overflow: visible;
        }

        #logoutModal .modal-body p {
            margin-bottom: 16px;
            line-height: 1.7;
            color: #4b5563;
            font-size: 16.5px;
            animation: textSlideUp 0.6s ease 0.3s both;
        }

        .modal-subtext {
            font-size: 14.5px;
            color: #6b7280;
            font-style: italic;
            margin-top: 8px;
            opacity: 0;
            animation: fadeInText 0.6s ease 0.4s forwards;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px 12px;
            background: rgba(209, 213, 219, 0.1);
        }

        #logoutModal .modal-footer {
            padding: 24px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            animation: fadeInFooter 0.6s ease 0.5s both;
        }

        .modal-btn {
            padding: 15px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15.5px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .modal-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .modal-btn:active::after {
            animation: ripple 0.6s ease-out;
        }

        .modal-btn-secondary {
            background: white;
            color: #6b7280;
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .modal-btn-secondary:hover {
            background: linear-gradient(135deg, #f0f7ff, #e0f2fe);
            color: #1a317d;
            border-color: #86efac;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 20px rgba(31, 58, 147, 0.15);
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, #1F3A93, #152c71, #1e3a8a);
            background-size: 200% 200%;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(31, 58, 147, 0.3);
            animation: gradientShift 8s ease infinite;
        }

        .modal-btn-primary:hover {
            background: linear-gradient(135deg, #152c71, #1e3a8a, #1a317d);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(31, 58, 147, 0.4);
            animation: gradientShiftFast 4s ease infinite;
        }

        .modal-btn-primary:active {
            transform: translateY(-1px) scale(1.01);
            transition: all 0.1s ease;
        }

        /* Micro-interactions */
        #logoutModal .modal-container:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }

        /* Logout modal mobile */
        @media (max-width: 480px) {
            #logoutModal .modal-container {
                max-width: 95%;
                border-radius: 16px;
                animation: slideInUpMobile 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            }

            #logoutModal .modal-header {
                padding: 24px 20px;
                gap: 15px;
                flex-direction: column;
            }

            #logoutModal .modal-icon,
            .modal-icon {
                width: 48px;
                height: 48px;
                font-size: 22px;
                margin: 0 auto;
            }

            #logoutModal .modal-body {
                padding: 20px 16px;
                margin: 15px;
            }

            .modal-subtext {
                padding: 8px 10px;
            }

            #logoutModal .modal-footer {
                padding: 20px;
                flex-direction: column;
            }

            .modal-btn {
                width: 100%;
                justify-content: center;
                padding: 14px 24px;
            }

            @keyframes slideInUpMobile {
                0% {
                    opacity: 0;
                    transform: translateY(30px) scale(0.98);
                }

                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
        }

        /* Reduced motion preferences */
        @media (prefers-reduced-motion: reduce) {

            #logoutModal .modal-overlay,
            #logoutModal .modal-container,
            #logoutModal .modal-header,
            .modal-icon,
            #logoutModal .modal-body,
            #logoutModal .modal-footer,
            .modal-btn {
                animation: none !important;
                transition: none !important;
            }
        }

        @keyframes iconPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        @keyframes fadeOutOverlay {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        @keyframes slideOutDown {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-8px) scale(1.02);
            }

            100% {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
        }

        @keyframes gradientShiftFast {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes fadeInBody {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes textSlideUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInText {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInFooter {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }

            20% {
                transform: scale(25, 25);
                opacity: 0.3;
            }

            100% {
                transform: scale(40, 40);
                opacity: 0;
            }
        }

        /* ========== END LOGOUT MODAL STYLES ========== */

        .modal-body {
            padding: 32px;
            overflow-y: auto;
        }

        /* Delete Modal Styles (Red Theme) */
        .modal-danger {
            border: 2px solid #1F3A93;
            border-radius: 15px;
            max-height: 85vh;
            max-width: 450px;
            width: 90%;
            margin: 0 auto;
        }

        .modal-danger .modal-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            padding: 20px 25px;
        }

        .modal-danger .modal-header h2 {
            color: white;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-danger .close-btn {
            color: white;
            opacity: 0.8;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
        }

        .modal-danger .close-btn:hover {
            opacity: 1;
        }

        .warning-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: #fee2e2;
            border-radius: 50%;
            font-size: 30px;
            color: #dc2626;
            border: 2px solid #fecaca;
        }

        .delete-confirmation {
            text-align: center;
            padding: 15px;
        }

        .warning-note {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 12px;
            margin-top: 15px;
            color: #92400e;
            font-size: 13px;
            text-align: left;
            border-left: 3px solid #f59e0b;
        }

        .modal-footer.danger {
            background: #fef2f2;
            padding: 15px 25px;
            border-top: 1px solid #fee2e2;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Animations */
        @keyframes fadeInOverlay {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideInUp {
            0% {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* Sentiment Chart Styles from Index */
        .sentiment-chart {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            height: 200px;
            margin: 30px 0;
            padding-bottom: 30px;
            /* Space for labels */
        }

        .sentiment-bar {
            flex: 1;
            background: #1a317d;
            border-radius: 5px 5px 0 0;
            position: relative;
            min-height: 20px;
            transition: height 0.5s ease;
        }

        .sentiment-bar.negative {
            background: #dc3545;
        }

        .sentiment-bar.neutral {
            background: #ffc107;
        }

        .sentiment-label {
            position: absolute;
            bottom: -45px;
            left: 0;
            right: 0;
            text-align: center;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .sentiment-summary {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 14px;
        }

        /* Inline Date Edit Buttons */
        .date-edit-btn {
            background: none;
            border: 1px solid #cbd5e1;
            color: #64748b;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 4px;
        }

        .date-edit-btn:hover {
            background: #1F3A93;
            color: white;
            border-color: #1F3A93;
        }

        .date-save-btn {
            background: #22c55e;
            color: white;
            border: none;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s;
        }

        .date-save-btn:hover {
            background: #16a34a;
        }

        .date-cancel-btn {
            background: #94a3b8;
            color: white;
            border: none;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s;
        }

        .date-cancel-btn:hover {
            background: #64748b;
        }

        body.dark-mode .date-edit-btn {
            border-color: #4b5563;
            color: #9ca3af;
        }

        body.dark-mode .date-edit-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
    </style>
    <script>
        function toggleOptions(val, targetId) {
            document.getElementById(targetId).style.display = (val === 'multiple_choice') ? 'block' : 'none';
        }

        function toggleDateEdit(id) {
            const display = document.getElementById('dateDisplay_' + id);
            const edit = document.getElementById('dateEdit_' + id);
            if (edit.style.display === 'none') {
                display.style.display = 'none';
                edit.style.display = 'block';
            } else {
                display.style.display = 'block';
                edit.style.display = 'none';
            }
        }

        // Tab Switching Logic for Results Modal
        function switchTab(tab) {
            // Hide all
            const qView = document.getElementById('view-questions');
            const rView = document.getElementById('view-respondents');

            if (qView) qView.style.display = 'none';
            if (rView) rView.style.display = 'none';

            // Reset buttons
            const qTab = document.getElementById('tab-questions');
            const rTab = document.getElementById('tab-respondents');

            if (qTab) {
                qTab.style.color = '#64748b';
                qTab.style.borderBottomColor = 'transparent';
            }
            if (rTab) {
                rTab.style.color = '#64748b';
                rTab.style.borderBottomColor = 'transparent';
            }

            // Show active
            const activeView = document.getElementById('view-' + tab);
            const activeTab = document.getElementById('tab-' + tab);

            if (activeView) activeView.style.display = 'block';
            if (activeTab) {
                activeTab.style.color = '#1F3A93';
                activeTab.style.borderBottomColor = '#1F3A93';
            }
        }

        // Standardized Modal Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            // Clear any leftover inline styles from closeModal
            modal.style.display = '';
            modal.style.opacity = '';
            modal.classList.add('active'); // For Create User style overlay
            modal.classList.add('show');   // For Delete/Edit modal styles (if mixed)

            // Explicitly handle display for overlays that tend to stay hidden
            if (modal.classList.contains('modal-overlay')) {
                modal.style.display = 'flex';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                modal.classList.remove('show');
                modal.style.display = '';
                modal.style.opacity = '';
            }
        }

        // Delete Modal Logic
        function openDeleteModal(id) {
            document.getElementById('delete_survey_id').value = id;
            const modal = document.getElementById('deleteSurveyModal');
            modal.classList.add('show');
            modal.style.display = 'flex'; // Ensure flex for centering
            modal.style.opacity = '1';
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteSurveyModal');
            modal.style.display = 'none';
            modal.style.opacity = '0';
            modal.classList.remove('show');
        }

        // AJAX Loaders
        function openEditModal(id) {
            const modal = document.getElementById('editSurveyModal');
            const content = document.getElementById('editSurveyContent');
            content.innerHTML = '<div style="padding:40px; text-align:center;"><i class="fas fa-spinner fa-spin fa-3x" style="color:#1F3A93;"></i></div>';
            modal.classList.add('active'); // Add active for overlay
            modal.style.display = 'flex';
            modal.style.opacity = '1';

            fetch('surveys.php?ajax_action=edit_modal&id=' + id)
                .then(r => r.text())
                .then(html => content.innerHTML = html);
        }

        function openResultsModal(id) {
            const modal = document.getElementById('resultsSurveyModal');
            const content = document.getElementById('resultsSurveyContent');
            content.innerHTML = '<div style="padding:40px; text-align:center;"><i class="fas fa-spinner fa-spin fa-3x" style="color:#8b5cf6;"></i></div>';
            modal.classList.add('active');
            modal.style.display = 'flex';
            modal.style.opacity = '1';

            fetch('surveys.php?ajax_action=results_modal&id=' + id)
                .then(r => r.text())
                .then(html => content.innerHTML = html);
        }

        // Sidebar Toggle & Dark Mode
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('toggleBtn');
            const sidebar = document.getElementById('sidebar');
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const body = document.body;


            // Sidebar
            if (toggleBtn) toggleBtn.addEventListener('click', () => sidebar.classList.toggle('closed'));

            // ========== LOGOUT MODAL FUNCTIONALITY ==========
            const logoutModal = document.getElementById('logoutModal');
            const logoutTrigger = document.getElementById('logoutTrigger');
            const closeModalBtn = document.getElementById('closeModal');
            const cancelLogout = document.getElementById('cancelLogout');

            function openLogoutModal(e) {
                if (e) e.preventDefault();
                logoutModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeLogoutModal() {
                logoutModal.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (logoutTrigger) logoutTrigger.addEventListener('click', openLogoutModal);
            if (closeModalBtn) closeModalBtn.addEventListener('click', closeLogoutModal);
            if (cancelLogout) cancelLogout.addEventListener('click', closeLogoutModal);

            if (logoutModal) {
                logoutModal.addEventListener('click', function (e) {
                    if (e.target === logoutModal) closeLogoutModal();
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
                    closeLogoutModal();
                }
            });
            // ========== END LOGOUT MODAL FUNCTIONALITY ==========

            // Auto open modal from Session
            <?php if (isset($_SESSION['open_edit_modal'])): ?>
                openEditModal(<?php echo $_SESSION['open_edit_modal']; ?>);
                <?php unset($_SESSION['open_edit_modal']); ?>
            <?php endif; ?>
        });

        window.onclick = function (event) {
            if (event.target.classList.contains('modal') && !event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('show');
                event.target.classList.remove('active');
                if (event.target.id === 'deleteSurveyModal') {
                    event.target.style.display = 'none';
                    event.target.style.opacity = '0';
                }
            }
        }

        function printSurveyResults(id) {
            const content = document.querySelector('#resultsSurveyContent .modal-body').innerHTML;
            const title = document.querySelector('#resultsSurveyContent h3').innerText;

            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print Results</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: sans-serif; padding: 20px; }');
            printWindow.document.write('.result-item { margin-bottom: 20px; page-break-inside: avoid; border-bottom: 1px solid #eee; padding-bottom: 20px; }');
            printWindow.document.write('h2 { color: #1a317d; border-bottom: 2px solid #1F3A93; padding-bottom: 10px; margin-bottom: 20px; }');
            printWindow.document.write('h4 { margin: 0 0 10px 0; color: #333; }');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h2>' + title + '</h2>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); }, 500);
        }
    </script>
    <script src="../js/theme.js"></script>
</head>

<body>

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div class="logo-area">
            <div class="logo-wrapper">
                <div class="logo">
                    <div class="logo-icon">
                        <img src="../img/logo.png" alt="Logo">
                    </div>
                    <h1 class="logo-text">Admin Panel</h1>
                </div>
                <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i><i
                        class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="login-indicator">
            <div class="user-info">
                <div class="user-avatar">
                    <span>
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                    </span>
                    <div class="status-dot"></div>
                </div>
                <div class="user-details">
                    <div class="user-name">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                    <div class="user-role">
                        <?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin') ? 'Super Admin' : 'Admin'; ?>
                    </div>
                </div>
            </div>
        </div>
        <ul class="menu-items">
            <li class="menu-item"><a href="index.php" class="menu-link"><i
                        class="fas fa-tachometer-alt menu-icon"></i><span class="menu-text">Dashboard</span></a></li>
            <li class="menu-item"><a href="manage_feedback.php" class="menu-link"><i
                        class="fas fa-list-alt menu-icon"></i><span class="menu-text">Manage Feedback</span></a></li>
            <li class="menu-item"><a href="surveys.php" class="menu-link active"><i
                        class="fas fa-poll menu-icon"></i><span class="menu-text">Surveys</span></a></li>
            <li class="menu-item"><a href="admin_settings.php" class="menu-link"><i
                        class="fas fa-cog menu-icon"></i><span class="menu-text">Admin Settings</span></a></li>
            <li class="menu-item"><a href="user_management.php" class="menu-link"><i
                        class="fas fa-users-cog menu-icon"></i><span class="menu-text">User Management</span></a></li>
        </ul>
        <div class="logout-section">
            <a href="#" class="logout-link" id="logoutTrigger" data-tooltip="Logout"><i
                    class="fas fa-sign-out-alt menu-icon"></i><span class="logout-text">Logout</span></a>
        </div>

        <!-- ========== LOGOUT MODAL ========== -->
        <div class="modal-overlay" id="logoutModal">
            <div class="modal-container">
                <div class="modal-header">
                    <i class="fas fa-sign-out-alt"></i>
                    <h2>Confirm Logout</h2>
                    <button class="modal-close" id="closeModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <br>
                <div class="modal-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div><br>
                <h4>Ready to Leave?</h4>
                <div class="modal-body">
                    <p>Are you sure you want to logout from the admin panel?</p>
                    <p class="modal-subtext">You will need to login again to access the admin dashboard.</p>
                </div>

                <div class="modal-footer">
                    <button class="modal-btn modal-btn-secondary" id="cancelLogout">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <a href="/htdocs/dashboard.php" class="modal-btn modal-btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Yes, Logout
                    </a>
                </div>
            </div>
        </div>
        <!-- ========== END LOGOUT MODAL ========== -->
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="header">
            <h1 class="page-title">
                <i class="fas fa-poll title-icon"></i>
                Manage Surveys
            </h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="theme-toggle-container">
                    <button class="btn" id="themeToggleBtn">
                        <i class="fas fa-moon"></i>
                        <span>Dark Mode</span>
                    </button>
                </div>
                <div class="date-display">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div style="background:#e0f2fe; color:#1a317d; padding:15px; border-radius:10px; margin-bottom:20px;">
                <?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php
        // Filter Logic
        $filter_id = isset($_GET['sentiment_filter']) ? intval($_GET['sentiment_filter']) : 0;
        $filter_title = "Overall Feedback Sentiment";

        // Sentiment Analysis Logic
        $global_sentiments = ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];
        $global_total = 0;

        // Base Query
        $g_text_sql = "SELECT sr.answer_text FROM survey_responses sr 
                       JOIN survey_questions sq ON sr.question_id = sq.id 
                       WHERE sq.question_type = 'text'";

        // Apply Filter
        if ($filter_id > 0) {
            $g_text_sql .= " AND sq.survey_id = $filter_id";

            // Get specific survey title for display
            $t_res = $conn->query("SELECT title FROM surveys WHERE id = $filter_id");
            if ($t_res->num_rows > 0) {
                $filter_title = "Sentiment: " . htmlspecialchars($t_res->fetch_assoc()['title']);
            }
        }

        $g_text_res = $conn->query($g_text_sql);

        if ($g_text_res->num_rows > 0) {
            while ($grow = $g_text_res->fetch_assoc()) {
                $s = analyzeSentiment($grow['answer_text']);
                $global_sentiments[$s]++;
                $global_total++;
            }
        }
        ?>

        <div class="sentiment-card">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h3 class="card-header-title">
                    <i class="fas fa-chart-pie" style="color: #1F3A93;"></i>
                    <?php echo $filter_title; ?>
                </h3>

                <form method="GET" style="display: flex; align-items: center; gap: 10px;">
                    <select name="sentiment_filter" onchange="this.form.submit()" class="sentiment-select">
                        <option value="0" <?php echo $filter_id == 0 ? 'selected' : ''; ?>>All Surveys</option>
                        <?php
                        if ($surveys->num_rows > 0) {
                            $surveys->data_seek(0); // Reset pointer
                            while ($s_opt = $surveys->fetch_assoc()) {
                                $sel = $filter_id == $s_opt['id'] ? 'selected' : '';
                                echo '<option value="' . $s_opt['id'] . '" ' . $sel . '>' . htmlspecialchars($s_opt['title']) . '</option>';
                            }
                            $surveys->data_seek(0); // Reset pointer again for table below
                        }
                        ?>
                    </select>
                </form>
            </div>

            <?php if ($global_total > 0):
                $pos_pct = round(($global_sentiments['Positive'] / $global_total) * 100);
                $neu_pct = round(($global_sentiments['Neutral'] / $global_total) * 100);
                $neg_pct = round(($global_sentiments['Negative'] / $global_total) * 100);
                ?>

                <div class="sentiment-chart">
                    <div class="sentiment-bar" style="height: <?php echo ($pos_pct / 100) * 200; ?>px;">
                        <div class="sentiment-label">Positive<br>
                            <?php echo $global_sentiments['Positive']; ?> (
                            <?php echo $pos_pct; ?>%)
                        </div>
                    </div>
                    <div class="sentiment-bar neutral" style="height: <?php echo ($neu_pct / 100) * 200; ?>px;">
                        <div class="sentiment-label">Neutral<br>
                            <?php echo $global_sentiments['Neutral']; ?> (
                            <?php echo $neu_pct; ?>%)
                        </div>
                    </div>
                    <div class="sentiment-bar negative" style="height: <?php echo ($neg_pct / 100) * 200; ?>px;">
                        <div class="sentiment-label">Negative<br>
                            <?php echo $global_sentiments['Negative']; ?> (
                            <?php echo $neg_pct; ?>%)
                        </div>
                    </div>
                </div>

                <div class="sentiment-summary">
                    <p>Positive: <strong>
                            <?php echo $global_sentiments['Positive']; ?>
                        </strong> |
                        Neutral: <strong>
                            <?php echo $global_sentiments['Neutral']; ?>
                        </strong> |
                        Negative: <strong>
                            <?php echo $global_sentiments['Negative']; ?>
                        </strong></p>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-bar" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>No text responses available for analysis.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
            <button onclick="openModal('createSurveyModal')" class="btn-primary">
                <i class="fas fa-plus"></i> Create New Survey
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Period</th>
                        <th>Responses</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($surveys->num_rows > 0): ?>
                        <?php while ($row = $surveys->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </div>
                                    <div style="font-size: 12px; opacity: 0.8;">
                                        <?php echo htmlspecialchars(substr($row['description'], 0, 50)); ?>...
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $cls = $row['status'] == 'Active' ? 'status-active' : ($row['status'] == 'Closed' ? 'status-closed' : 'status-draft');
                                    $icon = $row['status'] == 'Active' ? 'check-circle' : 'circle';
                                    ?>
                                    <span class="status-badge <?php echo $cls; ?>"><i class="fas fa-<?php echo $icon; ?>"></i>
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td style="font-size: 13px;">
                                    <!-- Display Mode -->
                                    <div id="dateDisplay_<?php echo $row['id']; ?>">
                                        <div><i class="fas fa-play" style="font-size: 10px;"></i>
                                            <?php echo date('M d, Y', strtotime($row['start_date'])); ?>
                                        </div>
                                        <div><i class="fas fa-stop" style="font-size: 10px;"></i>
                                            <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                        </div>
                                        <button type="button" onclick="toggleDateEdit(<?php echo $row['id']; ?>)"
                                            class="date-edit-btn" title="Edit Dates"><i class="fas fa-pen"
                                                style="font-size: 10px;"></i></button>
                                    </div>
                                    <!-- Edit Mode -->
                                    <div id="dateEdit_<?php echo $row['id']; ?>" style="display:none;">
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="survey_id" value="<?php echo $row['id']; ?>">
                                            <input type="date" name="start_date" value="<?php echo $row['start_date']; ?>"
                                                style="font-size:12px; padding:3px 5px; border:1px solid #cbd5e1; border-radius:5px; width:130px; margin-bottom:4px;">
                                            <input type="date" name="end_date" value="<?php echo $row['end_date']; ?>"
                                                style="font-size:12px; padding:3px 5px; border:1px solid #cbd5e1; border-radius:5px; width:130px; margin-bottom:4px;">
                                            <div style="display:flex; gap:4px; margin-top:2px;">
                                                <button type="submit" name="update_dates" class="date-save-btn" title="Save"><i
                                                        class="fas fa-check"></i></button>
                                                <button type="button" onclick="toggleDateEdit(<?php echo $row['id']; ?>)"
                                                    class="date-cancel-btn" title="Cancel"><i class="fas fa-times"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                                <td style="font-weight: 700; font-size: 16px; text-align: center;">
                                    <?php echo $row['response_count']; ?>
                                </td>
                                <td>
                                    <a href="survey_document.php?id=<?php echo $row['id']; ?>" target=" _blank"
                                        class="action-btn btn-edit" title="Edit Questions"><i class="fas fa-edit"></i></a>

                                    <?php if ($row['status'] == 'Active'): ?>
                                        <a href="surveys.php?toggle_status=Closed&id=<?php echo $row['id']; ?>"
                                            class="action-btn btn-close" title="Close Survey"><i class="fas fa-pause"></i></a>
                                    <?php else: ?>
                                        <a href="surveys.php?toggle_status=Active&id=<?php echo $row['id']; ?>"
                                            class="action-btn btn-active" title="Activate Survey"><i class="fas fa-play"></i></a>
                                    <?php endif; ?>

                                    <button onclick="openResultsModal(<?php echo $row['id']; ?>)" class="action-btn btn-results"
                                        title="View Results"><i class="fas fa-eye"></i></button>

                                    <a href="survey_document.php?id=<?php echo $row['id']; ?>" target="_blank"
                                        class="action-btn" style="background: #64748b;" title="Print Form"><i
                                            class="fas fa-print"></i></a>

                                    <button type="button" onclick="openDeleteModal(<?php echo $row['id']; ?>)"
                                        class="action-btn btn-delete" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 40px;">No surveys found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- CREATE MODAL (Standardized like Create User) -->
    <div class="modal-overlay" id="createSurveyModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-poll-h"></i> Create New Survey
                    <span class="user-badge">NEW</span>
                </h3>
                <button class="modal-close" onclick="closeModal('createSurveyModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" target="_blank"
                    onsubmit="setTimeout(function(){ window.location.reload(); }, 1000);">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="Survey Name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <!-- Assign To Field Removed -->
                    <div style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex:1">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" name="create_survey" class="btn-primary" style="width:100%">Create & Add
                        Questions</button>
                </form>
            </div>
        </div>
    </div>

    <!-- DELETE SURVEY MODAL (Standardized like Delete Feedback) -->
    <div id="deleteSurveyModal" class="modal">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i> Delete Survey
                </h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-confirmation">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4>Confirm Deletion</h4>
                    <p>Are you sure you want to delete this survey?</p>
                    <p>This action <strong>cannot be undone</strong> and will remove all associated questions and
                        responses.</p>
                    <div class="warning-note">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Warning:</strong> Deleting this survey is permanent.
                    </div>
                    <form method="POST" id="deleteSurveyForm">
                        <input type="hidden" name="survey_id" id="delete_survey_id" value="">
                        <input type="hidden" name="delete_survey" value="1">
                    </form>
                </div>
            </div>
            <div class="modal-footer danger">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="deleteSurveyForm" class="btn-danger">
                    <i class="fas fa-trash"></i> Delete Survey
                </button>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL (Dynamic Content) -->
    <div class="modal-overlay" id="editSurveyModal">
        <div class="modal-container" id="editSurveyContent" style="max-width: 900px; width: 95%; max-height: 90vh;">
            <!-- Loaded via Ajax -->
        </div>
    </div>

    <!-- RESULTS MODAL (Dynamic Content) -->
    <div class="modal-overlay" id="resultsSurveyModal">
        <div class="modal-container" id="resultsSurveyContent" style="max-width: 800px; width: 95%; max-height: 90vh;">
            <!-- Loaded via Ajax -->
        </div>
    </div>

</body>

</html>