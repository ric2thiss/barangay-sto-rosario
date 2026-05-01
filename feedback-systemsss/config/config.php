<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'feedback_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');      // e.g., smtp.gmail.com
define('SMTP_USER', 'renielbantilan42@gmail.com'); // Your email address
define('SMTP_PASS', 'wbcwmrzzlkmdawzz');    // Your email password or app password
define('SMTP_PORT', 587);                   // 587 for TLS, 465 for SSL
define('SMTP_SECURE', 'tls');               // tls or ssl
define('SMTP_FROM_EMAIL', 'renielbantilan42@gmail.com'); // Match SMTP_USER usually
define('SMTP_FROM_NAME', 'Brgy Rosario Feedback System');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (for protected pages)
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }
}

// Check if admin is logged in
function requireAdmin()
{
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
        header('Location: login.php');
        exit();
    }
}

// Check if user is logged in
function requireUser()
{
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['user', 'resident'])) {
        header('Location: login.php');
        exit();
    }
}

// Sentiment analysis function with weighted scoring
function analyzeSentiment($text)
{
    $positive_words = ['good', 'excellent', 'great', 'awesome', 'thank', 'happy', 'satisfied', 'improved', 'better', 'nice', 'fast', 'helpful', 'clean', 'perfect', 'excellent', 'wonderful'];
    $negative_words = ['bad', 'poor', 'terrible', 'awful', 'disappointed', 'angry', 'unsatisfied', 'slow', 'dirty', 'broken', 'mess', 'problem', 'issue', 'worst', 'horrible', 'failure'];

    // Add Tagalog positive words
    $positive_words = array_merge($positive_words, [
        'mabuti',
        'maganda',
        'masaya',
        'mahusay',
        'magaling',
        'salamat',
        'maraming salamat',
        'salamat po',
        'tuwa',
        'tuwang-tuwa',
        'saya',
        'malinis',
        'maayos',
        'matino',
        'ang galing',
        'napakaganda',
        'napakahusay',
        'tagumpay',
        'matagumpay',
        'mahal',
        'pagmamahal',
        'iniibig',
        'maayos',
        'mainam',
        'malusog'
    ]);

    // Add Tagalog negative words
    $negative_words = array_merge($negative_words, [
        'masama',
        'pangit',
        'malungkot',
        'hindi maganda',
        'problema',
        'sira',
        'basag',
        'sirain',
        'marumi',
        'madumi',
        'mabaho',
        'malansa',
        'lungkot',
        'nalulungkot',
        'galit',
        'nagagalit',
        'inis',
        'nainis',
        'nabigo',
        'bigo',
        'nakakadismaya',
        'mahirap',
        'hirap',
        'napakahirap',
        'ayaw',
        'napopoot',
        'hinanakit',
        'sakit',
        'masakit',
        'hapis'
    ]);

    // Add Bisaya positive words
    $positive_words = array_merge($positive_words, [
        'maayo',
        'nindot',
        'lipay',
        'lingaw',
        'daghang salamat',
        'salamat kaayo',
        'kalipay',
        'malipayon',
        'malipay',
        'limpyo',
        'himsog',
        'tarong',
        'gwapa',
        'gwapo',
        'nindota',
        'kaayo',
        'ayo',
        'maayo kaayo',
        'nindot kaayo',
        'gugma',
        'higugma',
        'gihigugma',
        'matahum',
        'anindot',
    ]);

    // Add Bisaya negative words
    $negative_words = array_merge($negative_words, [
        'dautan',
        'dili maayo',
        'ngil-ad',
        'maluya',
        'gubot',
        'samok',
        'hasol',
        'hugaw',
        'buling',
        'bahog',
        'subo',
        'masulub-on',
        'gimingaw',
        'kasuko',
        'suko',
        'nasuko',
        'pakyas',
        'napakyas',
        'nawad-an',
        'lisod',
        'lisura',
        'kalisod',
        'dili ganahan',
        'ayaw',
        'dumot',
        'masakiton',
        'hugaw',
        'lain',
        'pangit',
        'nagkalisud',
        'baho',
        'lisud',
        'pakyas',
        'baho',
    ]);

    $text = strtolower($text);
    $positive_score = 0;
    $negative_score = 0;

    // Count positive words
    foreach ($positive_words as $word) {
        $count = substr_count($text, $word);
        $positive_score += $count;
    }

    // Count negative words
    foreach ($negative_words as $word) {
        $count = substr_count($text, $word);
        $negative_score += $count;
    }

    // Calculate total and determine sentiment
    $total = $positive_score + $negative_score;

    if ($total == 0) {
        return 'Neutral';
    }

    // Calculate percentage
    $positive_percentage = ($positive_score / $total) * 100;
    $negative_percentage = ($negative_score / $total) * 100;

    // Determine sentiment with threshold
    if ($positive_percentage > 60) {
        return 'Positive';
    } elseif ($negative_percentage > 60) {
        return 'Negative';
    } else {
        return 'Neutral';
    }
}
?>