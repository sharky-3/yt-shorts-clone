<?php
    require_once __DIR__ . '/db.php';
    $me         = current_user();
    $profile_id = (int)($_GET['id'] ?? 0);

    $stmt = get_db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$profile_id]);
    $profile = $stmt->fetch();
    if (!$profile) { http_response_code(404); exit('User not found'); }

    $db = get_db();
    $vStmt = $db->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY created_at DESC");
    $vStmt->execute([$profile_id]);
    $videos = $vStmt->fetchAll();

    $followerCount = $db->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $followerCount->execute([$profile_id]);
    $followers = $followerCount->fetchColumn();

    $isFollowing = false;
    if ($me) {
        $fCheck = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
        $fCheck->execute([$me['id'], $profile_id]);
        $isFollowing = (bool)$fCheck->fetchColumn();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?= htmlspecialchars($profile['username']) ?> </title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
        
    <!-- ── [[ Navigation ]] ──────────────────────────────────────────────────── -->

    <nav class="navbar">
        <div class="nav-actions">
            <?php if ($me): ?>
                <a href="/upload.php" class="btn btn-upload">+ Upload</a>
                <a href="/auth.php?logout=1" class="btn btn-ghost">Log out</a>
            <?php else: ?>
                <a href="/auth.php" class="btn">Sign in</a>
            <?php endif; ?>
            </div>
    </nav>

    <!-- ── [[ Profile ]] ──────────────────────────────────────────────────── -->

    <div class="profile-page">
        <div class="profile-header">
            <div class="profile-avatar"><?= strtoupper($profile['username'][0]) ?></div>
            <div>
                <h2>@<?= htmlspecialchars($profile['username']) ?></h2>
                <p><?= number_format($followers) ?> followers · <?= count($videos) ?> videos</p>
                <?php if ($profile['bio']): ?>
                    <p style="margin-top:6px"><?= htmlspecialchars($profile['bio']) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($me && $me['id'] !== $profile_id): ?>
                <button id="follow-btn"
                        class="btn <?= $isFollowing ? 'btn-ghost' : '' ?>"
                        data-id="<?= $profile_id ?>">
                    <?= $isFollowing ? 'Following' : 'Follow' ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($videos)): ?>
            <p style="color:var(--muted);padding:40px 0;text-align:center">No videos yet.</p>
        <?php else: ?>
        <div class="profile-grid">
            <?php foreach ($videos as $v): ?>
            <a href="/index.php?v=<?= $v['id'] ?>"
            class="profile-thumb"
            data-filename="<?= htmlspecialchars($v['filename']) ?>">
                <span class="thumb-loading">🎬</span>
                <canvas class="thumb-canvas" style="display:none"></canvas>
                <div class="thumb-info">
                    <!-- <span class="thumb-title"><?= htmlspecialchars($v['title']) ?></span> -->
                    <span class="thumb-views"> <?= number_format($v['views']) ?> Views </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── [[ JavaScript ]] ──────────────────────────────────────────────────── -->

    <script>
        
        // ── [[ Frames ]] ────────────────────────────────────────────────────

        function grabFrame(card) {
            const filename = card.dataset.filename;
            const canvas   = card.querySelector('.thumb-canvas');
            const loading  = card.querySelector('.thumb-loading');
            const ctx      = canvas.getContext('2d');

            const video       = document.createElement('video');
            video.muted       = true;
            video.playsInline = true;
            video.preload     = 'auto';
            video.crossOrigin = 'anonymous';

            video.addEventListener('loadeddata', () => {
                video.currentTime = Math.min(1, video.duration * 0.1);
            });

            video.addEventListener('seeked', () => {
                canvas.width  = video.videoWidth  || 360;
                canvas.height = video.videoHeight || 640;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                canvas.style.display = 'block';
                if (loading) loading.style.display = 'none';
                video.src = '';
                video.load();
            });

            video.addEventListener('error', () => {
                if (loading) loading.textContent = '🎬';
            });

            video.src = `/stream.php?f=${encodeURIComponent(filename)}`;
            video.load();
        }

        // ── [[ Load Thumbnails ]] ────────────────────────────────────────────────────

        const cards = [...document.querySelectorAll('.profile-thumb')];
        let idx = 0;
        function nextThumb() {
            if (idx >= cards.length) return;
            const card = cards[idx++];
            const filename = card.dataset.filename;
            const canvas   = card.querySelector('.thumb-canvas');
            const loading  = card.querySelector('.thumb-loading');
            const ctx      = canvas.getContext('2d');

            const video       = document.createElement('video');
            video.muted       = true;
            video.playsInline = true;
            video.preload     = 'auto';

            video.addEventListener('loadeddata', () => {
                video.currentTime = Math.min(1, video.duration * 0.1);
            });

            video.addEventListener('seeked', () => {
                canvas.width  = video.videoWidth  || 360;
                canvas.height = video.videoHeight || 640;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                canvas.style.display = 'block';
                if (loading) loading.style.display = 'none';
                video.src = '';
                nextThumb();
            });

            video.addEventListener('error', () => {
                nextThumb();
            });

            video.src = `/stream.php?f=${encodeURIComponent(filename)}`;
            video.load();
        }

        nextThumb();

        // ── [[ Follow / Unfollow ]] ────────────────────────────────────────────────────

        const followBtn = document.getElementById('follow-btn');
        if (followBtn) {
            followBtn.addEventListener('click', async () => {
                const res = await fetch('/follow.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    'user_id=' + followBtn.dataset.id,
                });
                const { following } = await res.json();
                followBtn.textContent = following ? 'Following' : 'Follow';
                followBtn.classList.toggle('btn-ghost', following);
            });
        }
    </script>
</body>
</html>