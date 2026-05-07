<?php
declare(strict_types=1);

ob_start(); // capture accidental output

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

function out(bool $success, string $message = '', array $extra = []): void {
    if (ob_get_length()) ob_clean(); // remove accidental output
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}


if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  out(false, 'Unauthorized.');
}
try {
    require_once __DIR__ . '/../config/conn.php';

    $stmt = $pdo->query("
        SELECT
            d.id,
            d.title,
            d.status,
            d.created_at,
            (
                SELECT COUNT(*)
                FROM minutes_attachments a
                WHERE a.minutes_document_id = d.id
            ) AS file_count
        FROM minutes_documents d
        ORDER BY d.created_at DESC
        LIMIT 300
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    out(true, '', ['rows' => $rows]);

} catch (Throwable $e) {
    http_response_code(500);
    out(false, 'Server error.');
}