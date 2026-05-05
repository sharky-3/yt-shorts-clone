<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$offset  = max(0, (int)($_GET['offset'] ?? 0));
$user    = current_user();
$user_id = $user['id'] ?? 0;

// ── [[ Get Videos ]] ────────────────────────────────────────────────────

$stmt = get_db()->prepare("
    SELECT
        v.id, v.title, v.filename, v.thumbnail, v.views, v.created_at,
        u.id   AS author_id,
        u.username,
        COUNT(DISTINCT l.user_id)  AS like_count,
        COUNT(DISTINCT c.id)       AS comment_count,
        MAX(ml.user_id IS NOT NULL) AS liked_by_me
    FROM videos v
    JOIN users u ON u.id = v.user_id
    LEFT JOIN likes    l  ON l.video_id  = v.id
    LEFT JOIN comments c  ON c.video_id  = v.id
    LEFT JOIN likes    ml ON ml.video_id = v.id AND ml.user_id = ?
    GROUP BY v.id
    ORDER BY v.created_at DESC
    LIMIT 5 OFFSET ?
");
$stmt->execute([$user_id, $offset]);
$videos = $stmt->fetchAll();

// ── [[ View Increment ]] ────────────────────────────────────────────────────

if ($videos) {
    $ids = implode(',', array_column($videos, 'id'));
    get_db()->exec("UPDATE videos SET views = views + 1 WHERE id IN ($ids)");
}

echo json_encode($videos);
