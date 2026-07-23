<?php
require 'config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please fill out all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)')
                ->execute([$name, $email, $hash, 'Staff']);
            $success = 'Account created! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrap">
  <div style="display:flex;flex-direction:column;align-items:center;">
    <div style="width:420px;max-width:92vw;margin-bottom:12px;">
      <a href="landing.php" style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:600;color:var(--muted);">← Back to home</a>
    </div>
    <div class="login-card">
    <div class="brand">
      <div class=""> <img src="glowcode.png" alt="logo" style="max-width: 100%; height: auto;"></div>
      <div>
        <h1>GlowSync</h1>
        <p>Sales And Customer Support Management</p>
      </div>
    </div>
    <h2>Create Account</h2>
    <div class="sub">Sign up for a GlowSync account</div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="post" action="signup.php">
      <div class="field">
        <label>Full Name</label>
        <input type="text" name="name" placeholder="Enter your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="At least 6 characters" required>
      </div>
      <div class="field">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Re-enter your password" required>
      </div>
      <button type="submit" class="btn">Sign Up</button>
    </form>
    <div class="demo" style="margin-top:16px;text-align:center;">
      Already have an account? <a href="login.php" style="color:var(--primary);font-weight:700;">Sign in</a>
    </div>
    <?php else: ?>
      <a href="login.php" class="btn" style="display:block;text-align:center;">Go to Login</a>
    <?php endif; ?>
  </div>
  </div>
</div>
</body>
</html>
