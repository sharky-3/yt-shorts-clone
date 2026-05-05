<?php
    require_once __DIR__ . '/db.php';
    $user = current_user();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shorts</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

    <!-- ── [[ Navigation ]] ──────────────────────────────────────────────────── -->

    <nav class="navbar">
        <div class="nav-actions">
            <?php if ($user): ?>
                <a href="/upload.php" class="btn btn-upload">+ Upload</a>
                <a href="/profile.php?id=<?= $user['id'] ?>" class="nav-avatar">
                    <?= htmlspecialchars($user['username']) ?>
                </a>
                <a href="/auth.php?logout=1" class="btn btn-ghost">Log out</a>
            <?php else: ?>
                <a href="/auth.php" class="btn">Sign in</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ── [[ Feed ]] ──────────────────────────────────────────────────── -->

    <main class="feed" id="feed"></main>

    <!-- ── [[ Video Template ]] ──────────────────────────────────────────────────── -->

    <template id="short-tpl">
        <div class="short">
            <video loop playsinline preload="metadata"></video>
            <div class="short-overlay">
                <div class="short-meta">
                    <a class="short-author" href="#"></a>
                    <p class="short-title"></p>
                    <p class="short-views"></p>
                </div>
                <div class="short-actions">
                    <button class="like-btn" data-id="">
                        <span class="heart">♥</span>
                        <span class="like-count">0</span>
                    </button>
                    <button class="comment-toggle" data-id="">💬</button>
                </div>
            </div>
            <div class="comments-panel" style="display:none">
                <div class="comments-list"></div>
                <?php if ($user): ?>
                <form class="comment-form">
                    <input type="text" placeholder="Add a comment…" maxlength="300">
                    <button type="submit">Post</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </template>

    <!-- ── [[ JavaScript ]] ──────────────────────────────────────────────────── -->

    <script> const LOGGED_IN = <?= $user ? 'true' : 'false' ?>; </script>
    <script src="/assets/app.js"></script>
</body>
</html>
