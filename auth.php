<?php
require_once __DIR__ . '/db.php';
session_start();
$error = $_GET['error'] ?? '';

// ── [[ LogOut ]] ────────────────────────────────────────────────────

if (isset($_GET['logout'])) {
    if (!empty($_COOKIE['remember_token'])) {
        $hash = hash('sha256', $_COOKIE['remember_token']);
        $stmt = get_db()->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
        $stmt->execute([$hash]);
    }
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    header('Location: /auth.php');
    exit;
}

// ── [[ Register ]] ────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (strlen($username) < 3 || strlen($password) < 6) {
        header('Location: /auth.php?error=invalid'); exit;
    }

    try {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = get_db()->prepare(
            "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
        );
        $stmt->execute([$username, $email, $hash]);
        $_SESSION['user_id'] = get_db()->lastInsertId();
        header('Location: /index.php'); exit;
    } catch (PDOException $e) {
        header('Location: /auth.php?error=taken'); exit;
    }
}

// ── [[ LogIn ]] ────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $remember =     !empty($_POST['remember']);

    $stmt = get_db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];

        if ($remember) {
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires   = date('Y-m-d H:i:s', time() + 30 * 24 * 3600);

            $stmt = get_db()->prepare(
                "INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)"
            );
            $stmt->execute([$user['id'], $tokenHash, $expires]);

            setcookie('remember_token', $token, time() + 30 * 24 * 3600, '/', '', false, true);
        }

        header('Location: /index.php'); exit;
    }
    header('Location: /auth.php?error=credentials'); exit;
}

// ── [[ Auto login ]] ────────────────────────────────────────────────────

if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $hash = hash('sha256', $_COOKIE['remember_token']);
    $stmt = get_db()->prepare(
        "SELECT r.user_id FROM remember_tokens r
         WHERE r.token_hash = ? AND r.expires_at > NOW()"
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if ($row) {
        $_SESSION['user_id'] = $row['user_id'];
        header('Location: /index.php'); exit;
    }
}

// ── [[ Structure ]] ────────────────────────────────────────────────────

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-box">

            <?php if ($error === 'credentials'): ?>
                <p class="error">Wrong email or password.</p>
            <?php elseif ($error === 'taken'): ?>
                <p class="error">Username or email already taken.</p>
            <?php elseif ($error === 'invalid'): ?>
                <p class="error">Username min 3 chars, password min 6.</p>
            <?php endif; ?>

            <!-- ── [[ SignIn ]] ──────────────────────────────────────────────────── -->

            <form method="POST" action="/auth.php" id="login-form">
                <input type="hidden" name="action" value="login">
                <h2>Sign in</h2>
                <input type="email"    name="email"    placeholder="Email"    required>
                <input type="password" name="password" placeholder="Password" required>
                <label class="remember"><input type="checkbox" name="remember"> Remember me</label>
                <button type="submit">Sign in</button>
            </form>

            <hr>

            <!-- ── [[ Register ]] ──────────────────────────────────────────────────── -->

            <form method="POST" action="/auth.php" id="register-form">
                <input type="hidden" name="action" value="register">
                <h2>Create account</h2>
                <input type="text"     name="username" placeholder="Username" required>
                <input type="email"    name="email"    placeholder="Email"    required>
                <input type="password" name="password" placeholder="Password (min 6)" required>
                <button type="submit">Register</button>
            </form>
        </div>
    </div>
</body>
</html>
