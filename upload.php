<?php
    require_once __DIR__ . '/db.php';
    $user = require_login();

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $file  = $_FILES['video'] ?? null;

        if (!$title) { $error = 'Please add a title.'; }
        elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) { $error = 'Upload failed.'; }
        elseif ($file['size'] > MAX_FILE_SIZE) { $error = 'File too large (max 200MB).'; }
        else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);

            if (!in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime'])) {
                $error = 'Only MP4/WebM/MOV files allowed.';
            } else {
                $ext      = 'mp4';
                $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                $dest     = UPLOAD_DIR . $filename;

                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $error = 'Could not save file.';
                } else {
                    // Generate thumbnail with FFmpeg (if available)
                    $thumb = null;
                    $thumbFile = 'thumbs/' . $filename . '.jpg';
                    $thumbPath = UPLOAD_DIR . $thumbFile;
                    $ffmpeg = trim(shell_exec('which ffmpeg'));
                    if ($ffmpeg) {
                        $cmd = escapeshellcmd("$ffmpeg -i " . escapeshellarg($dest) .
                            " -ss 00:00:01 -vframes 1 " . escapeshellarg($thumbPath) .
                            " 2>/dev/null");
                        shell_exec($cmd);
                        if (file_exists($thumbPath)) $thumb = $thumbFile;
                    }

                    $stmt = get_db()->prepare(
                        "INSERT INTO videos (user_id, title, filename, thumbnail) VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([$user['id'], $title, $filename, $thumb]);
                    header('Location: /index.php'); exit;
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

    <!-- ── [[ Navigation ]] ──────────────────────────────────────────────────── -->

    <nav class="navbar">
        <a href="/index.php" class="logo">🎬 Shorts</a>
        <a href="/index.php" class="btn btn-ghost">← Back</a>
    </nav>

    <!-- ── [[ Upload Box ]] ──────────────────────────────────────────────────── -->

    <div class="upload-box">
        <h2>Upload a Short</h2>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Title
                <input type="text" name="title" placeholder="Say something catchy…" maxlength="200" required>
            </label>
            <label>Video file (MP4, WebM — max 200MB)
                <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" required>
            </label>
            <div id="preview-wrap" style="display:none">
                <video id="preview" controls style="width:100%;max-height:300px;border-radius:8px"></video>
            </div>
            <button type="submit" class="btn btn-upload">Upload</button>
        </form>
    </div>

    <!-- ── [[ JavaScript ]] ──────────────────────────────────────────────────── -->

    <script>
        document.querySelector('input[type=file]').addEventListener('change', function() {
            const wrap  = document.getElementById('preview-wrap');
            const video = document.getElementById('preview');
            if (this.files[0]) {
                video.src = URL.createObjectURL(this.files[0]);
                wrap.style.display = 'block';
            }
        });
    </script>
</body>
</html>
