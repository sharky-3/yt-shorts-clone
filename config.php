<?php

// ── [[ Configs ]] ────────────────────────────────────────────────────

define('DB_HOST', 'localhost;unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');
define('DB_NAME', 'yt_shorts');
define('DB_USER', 'root');
define('DB_PASS', '');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('THUMB_DIR',  __DIR__ . '/uploads/thumbs/');
define('MAX_FILE_SIZE', 200 * 1024 * 1024); // 200MB

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
]);