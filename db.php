<?php
require_once __DIR__ . '/config.php';

// ── [[ Crud ]] ────────────────────────────────────────────────────

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

// ── [[ Current User ]] ────────────────────────────────────────────────────

function current_user(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['user_id'])) {
        $stmt = get_db()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }
    return null;
}

// ── [[ Register ]] ────────────────────────────────────────────────────

function require_login(): array {
    $user = current_user();
    if (!$user) {
        header('Location: /auth.php');
        exit;
    }
    return $user;
}
