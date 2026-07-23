<?php
/**
 * Run this ONCE in your browser (e.g. http://localhost/glowsync/setup_admin.php)
 * to set/reset the admin password. DELETE this file afterwards.
 */
require 'config.php';

$message = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $pdo->prepare("UPDATE users SET password=? WHERE email=?")->execute([$hash, $email]);
        } else {
            $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)")
                ->execute(['Admin', $email, $hash, 'Admin']);
        }
        $done = true;
        $message = "Password set for $email. You can now log in. Please delete setup_admin.php for security.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Setup Admin - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h2>Setup Admin Account</h2>
    <div class="sub">Run this once to create/reset your login credentials.</div>
    <?php if ($message): ?>
      <div class="alert <?= $done ? 'alert-success' : 'alert-error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!$done): ?>
    <form method="post">
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="admin@glowsync.com" required>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="At least 6 characters" required>
      </div>
      <button type="submit" class="btn">Save Password</button>
    </form>
    <?php else: ?>
      <a href="login.php" class="btn" style="display:block;text-align:center;">Go to Login</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
