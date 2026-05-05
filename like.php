<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) { http_response_code(401); echo json_encode(['error' => 'Login required']); exit; }

$video_id = (int)($_POST['video_id'] ?? 0);
if (!$video_id) { http_response_code(400); echo json_encode(['error' => 'Bad request']); exit; }

$db = get_db();

// ── [[ Check Existing Likes ]] ────────────────────────────────────────────────────

$stmt = $db->prepare("SELECT 1 FROM likes WHERE user_id = ? AND video_id = ?");
$stmt->execute([$user['id'], $video_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    $db->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?")->execute([$user['id'], $video_id]);
    $liked = false;
} else {
    $db->prepare("INSERT INTO likes (user_id, video_id) VALUES (?, ?)")->execute([$user['id'], $video_id]);
    $liked = true;
}

$count = $db->prepare("SELECT COUNT(*) FROM likes WHERE video_id = ?");
$count->execute([$video_id]);
echo json_encode(['liked' => $liked, 'likes' => (int)$count->fetchColumn()]);
