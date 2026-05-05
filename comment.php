<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$method   = $_SERVER['REQUEST_METHOD'];
$video_id = (int)($_GET['video_id'] ?? $_POST['video_id'] ?? 0);

if ($method === 'GET') {
    $stmt = get_db()->prepare("
        SELECT c.id, c.body, c.created_at, u.username
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.video_id = ?
        ORDER BY c.created_at DESC
        LIMIT 30
    ");
    $stmt->execute([$video_id]);
    echo json_encode($stmt->fetchAll());
    exit;
}

$user = current_user();
if (!$user) { http_response_code(401); echo json_encode(['error' => 'Login required']); exit; }

$body = trim($_POST['body'] ?? '');
if (!$body || strlen($body) > 300) { http_response_code(400); echo json_encode(['error' => 'Invalid comment']); exit; }

$stmt = get_db()->prepare("INSERT INTO comments (user_id, video_id, body) VALUES (?, ?, ?)");
$stmt->execute([$user['id'], $video_id, $body]);

// ── [[ Json ]] ────────────────────────────────────────────────────

echo json_encode([
    'id'         => get_db()->lastInsertId(),
    'body'       => $body,
    'username'   => $user['username'],
    'created_at' => date('Y-m-d H:i:s'),
]);
