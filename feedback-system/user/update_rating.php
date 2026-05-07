<?php
// update_rating.php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireUser();

// Add maintenance mode check
checkMaintenanceMode();


$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $feedback_id = isset($_POST['feedback_id']) ? (int) $_POST['feedback_id'] : 0;
    $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;

    if ($feedback_id <= 0 || $rating < 1 || $rating > 5) {
        $response['message'] = 'Invalid data provided';
        echo json_encode($response);
        exit;
    }

    // Verify that the feedback belongs to the current user and hasn't been updated yet
    $check_sql = "SELECT id, is_updated_rating FROM feedback WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $feedback_id, $user_id);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows === 0) {
        $response['message'] = 'Feedback not found or access denied';
        echo json_encode($response);
        exit;
    }

    $feedback = $check_result->fetch_assoc();
    if ($feedback['is_updated_rating'] == 1) {
        $response['message'] = 'You have already updated the rating for this feedback once.';
        echo json_encode($response);
        exit;
    }

    // Update the rating
    $update_sql = "UPDATE feedback SET rating = ?, updated_at = NOW(), is_updated_rating = 1 WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $rating, $feedback_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Rating updated successfully';

        // Ensure we update the personnel's overall star rating as well
        $assignment = getAssignmentByFeedback($conn, $feedback_id);
        if ($assignment && !empty($assignment['personnel_id'])) {
            updatePersonnelRating($conn, $assignment['personnel_id']);
        }
    } else {
        $response['message'] = 'Failed to update rating';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>