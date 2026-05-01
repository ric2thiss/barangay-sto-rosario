<!-- <?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireUser();


// Add maintenance mode check
checkMaintenanceMode();

$category_id = $_GET['category'] ?? 0;

// Fetch category details
$sql = "SELECT * FROM categories WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category_result = $stmt->get_result();
$category = $category_result->fetch_assoc();


if (!$category) {
    header('Location: index.php');
    exit();
}

// Check for pending ratings on resolved feedback
$user_id = $_SESSION['user_id'];
$pending_rating_sql = "SELECT id FROM feedback WHERE user_id = ? AND is_resolved = 1 AND rating = 0 LIMIT 1";
$stmt = $conn->prepare($pending_rating_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_rating_result = $stmt->get_result();

if ($pending_rating_result->num_rows > 0) {
    $_SESSION['error_message'] = "You must rate your previous resolved transaction before submitting a new one.";
    header('Location: my_feedback.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = 0; // Default to 0 (unrated)
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];
    
    // Analyze sentiment
    $sentiment = analyzeSentiment($comment);
    
    // Handle file upload
    $attachment_path = null;
    $upload_result = handleFileUpload($_FILES['attachment']);
    
    if ($upload_result['success']) {
        $attachment_path = $upload_result['path'];
    }

    // Insert feedback
    $sql = "INSERT INTO feedback (user_id, category_id, rating, comment, sentiment, attachment_path) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisss", $user_id, $category_id, $rating, $comment, $sentiment, $attachment_path);
    
    if ($stmt->execute()) {
        $feedback_id = $conn->insert_id;
        
        // Auto-assign personnel for negative feedback
        if ($sentiment === 'Negative' || $rating <= 2) {
            // Check if category is related to infrastructure/services (e.g., Roads)
            // Using a generic function for now that handles logic inside
            autoAssignPersonnel($conn, $feedback_id, $category_id, $rating, $sentiment);
        }
        
        $_SESSION['success_message'] = "Feedback submitted successfully! Sentiment detected: $sentiment";
        header('Location: index.php');
        exit();
    } else {
        $error = "Error submitting feedback. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - <?php echo htmlspecialchars($category['name']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/user_header.php'; ?>

    <main class="container">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="<?php echo getCategoryIcon($category['icon']); ?>"></i> 
                    Submit Feedback: <?php echo htmlspecialchars($category['name']); ?>
                </h2>
                <p><?php echo htmlspecialchars($category['description']); ?></p>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Rating section removed - Rating will be available after resolution -->
                <input type="hidden" name="rating" value="0">
                
                <div class="form-group">
                    <label for="comment">Your Feedback / Suggestion:</label>
                    <textarea class="form-control" id="comment" name="comment" 
                              placeholder="Please provide detailed feedback about the service. The system will automatically analyze the sentiment of your feedback..."
                              rows="6" required></textarea>
                    <small class="text-muted">Minimum 10 characters required</small>
                </div>

                <div class="form-group">
                    <label for="attachment">Attach Image (Optional)</label>
                    <input type="file" class="form-control" id="attachment" name="attachment" accept="image/*">
                    <small style="color: #6b7280; font-size: 12px;">Supported formats: JPG, PNG, GIF. Max size: 5MB.</small>
                </div>
                
                <div class="form-group">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Submit Feedback
                        </button>
                        <a href="index.php" class="btn">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="guidelines">
            <h3><i class="fas fa-lightbulb"></i> Tips for Good Feedback:</h3>
            <ul>
                <li>Be specific about what you like or dislike</li>
                <li>Provide constructive suggestions for improvement</li>
                <li>Mention location and time if relevant</li>
                <li>Keep your feedback respectful and helpful</li>
            </ul>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Resident Feedback and Survey System</p>
        </div>
    </footer>
    
    <script>
        // Auto-expand textarea
        const textarea = document.getElementById('comment');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Character counter
        const charCounter = document.createElement('small');
        charCounter.className = 'char-counter';
        charCounter.style.display = 'block';
        charCounter.style.textAlign = 'right';
        charCounter.style.marginTop = '5px';
        charCounter.style.color = '#666';
        textarea.parentNode.appendChild(charCounter);
        
        function updateCharCounter() {
            const length = textarea.value.length;
            charCounter.textContent = `${length} characters`;
            
            if (length < 10) {
                charCounter.style.color = '#dc3545';
            } else if (length < 50) {
                charCounter.style.color = '#ffc107';
            } else {
                charCounter.style.color = '#1a317d';
            }
        }
        
        textarea.addEventListener('input', updateCharCounter);
        updateCharCounter();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (textarea.value.length < 10) {
                e.preventDefault();
                alert('Please enter at least 10 characters for your feedback.');
                textarea.focus();
            }
        });
    </script>
</body>
</html> -->