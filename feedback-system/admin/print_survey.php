<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

if (!isset($_GET['id'])) {
    die("Invalid Survey ID");
}

$survey_id = intval($_GET['id']);

// Fetch Survey
$stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if (!$survey) {
    die("Survey not found.");
}

// Fetch Questions
$q_sql = "SELECT * FROM survey_questions WHERE survey_id = $survey_id ORDER BY order_num ASC";
$questions = $conn->query($q_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Survey - <?php echo htmlspecialchars($survey['title']); ?></title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: white; color: #000; padding: 40px; }
        
        .survey-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1 { color: #1a317d; text-align: center; margin-bottom: 10px; font-size: 24px; border-bottom: 2px solid #1F3A93; padding-bottom: 15px; }
        p.description { text-align: center; color: #64748b; margin-bottom: 40px; font-style: italic; }
        
        .question-box {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #cbd5e1;
            page-break-inside: avoid;
        }
        
        .question-text {
            font-weight: 600;
            margin-bottom: 15px;
            color: #000;
            font-size: 16px;
        }
        
        /* Form Simulation Styles */
        .input-box {
            width: 100%;
            height: 100px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            margin-top: 5px;
        }
        
        .radio-option { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 8px; 
            align-items: center; 
        }
        
        .radio-circle {
            width: 16px;
            height: 16px;
            border: 1px solid #000;
            border-radius: 50%;
            display: inline-block;
        }
        
        .checkbox-square {
            width: 16px;
            height: 16px;
            border: 1px solid #000;
            border-radius: 3px;
            display: inline-block;
        }

        /* Hide elements when printing */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-print { background: #1F3A93; color: white; }
        .btn-close { background: #64748b; color: white; }
    </style>
</head>
<body>

    <div class="print-controls no-print">
        <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Print</button>
        <button onclick="window.close()" class="btn btn-close"><i class="fas fa-times"></i> Close</button>
    </div>

    <div class="survey-container">
        <h1><?php echo htmlspecialchars($survey['title']); ?></h1>
        <p class="description"><?php echo htmlspecialchars($survey['description']); ?></p>
        
        <?php $i=1; while($q = $questions->fetch_assoc()): ?>
            <div class="question-box">
                <div class="question-text">
                    <?php echo $i++ . '. ' . htmlspecialchars($q['question_text']); ?>
                    <?php if($q['is_required']) echo '<span style="font-size: 0.8em; color: #666;">(Required)</span>'; ?>
                </div>
                
                <?php if($q['question_type'] == 'text'): ?>
                    <div class="input-box"></div>
                
                <?php elseif($q['question_type'] == 'rating'): ?>
                    <div style="display: flex; gap: 30px; margin-top: 10px;">
                        <?php for($r=1; $r<=5; $r++): ?>
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                <div class="radio-circle"></div>
                                <span style="font-size: 14px;"><?php echo $r; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">(1 - Lowest, 5 - Highest)</div>
                    
                <?php elseif($q['question_type'] == 'yes_no'): ?>
                    <div style="display: flex; gap: 30px; margin-top: 10px;">
                        <div class="radio-option">
                            <div class="radio-circle"></div> Yes
                        </div>
                        <div class="radio-option">
                            <div class="radio-circle"></div> No
                        </div>
                    </div>
                    
                <?php elseif($q['question_type'] == 'multiple_choice'): ?>
                    <div style="margin-top: 10px;">
                        <?php 
                            $opts = json_decode($q['options']);
                            if ($opts) {
                                foreach($opts as $opt) {
                                    echo '<div class="radio-option" style="margin-bottom: 12px;">';
                                    echo '<div class="radio-circle"></div> ';
                                    echo htmlspecialchars($opt);
                                    echo '</div>';
                                }
                            }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        
        <div style="margin-top: 40px; border-top: 2px solid #000; padding-top: 10px; display: inline-block; min-width: 200px;">
            Signature over Printed Name
        </div>
        <div style="float: right; margin-top: 40px; color: #666; font-size: 12px;">
            Printed on: <?php echo date('F d, Y h:i A'); ?>
        </div>
    </div>

    <script>
        // Auto-print on load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
