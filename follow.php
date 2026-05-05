<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) { http_response_code(401); echo json_encode(['error' => 'Login required']); exit; }

$target_id = (int)($_POST['user_id'] ?? 0);
if (!$target_id || $target_id === $user['id']) {
    http_response_code(400); echo json_encode(['error' => 'Bad request']); exit;
}

$db   = get_db();
$stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$user['id'], $target_id]);

if ($stmt->fetchColumn()) {
    $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")
       ->execute([$user['id'], $target_id]);
    echo json_encode(['following' => false]);
} else {
    $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)")
       ->execute([$user['id'], $target_id]);
    echo json_encode(['following' => true]);
}
