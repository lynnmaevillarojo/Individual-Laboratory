<?php
require 'config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'] ?? 'Admin';
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - GlowSync</title>
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
      <div class=""> <img src="glowcode.png" alt="logo"  style="max-width: 100%; height: auto;"></div>
      <div>
        <h1>GlowSync</h1>
        <p>Sales And Customer Support Management</p>
      </div>
    </div>
    <h2>Welcome Back!</h2>
    <div class="sub">Please sign in to your account</div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>
      </div>
      <div class="remember-row">
        <label><input type="checkbox" name="remember"> Remember me</label>
        <a href="#">Forgot Password?</a>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
    <div class="demo" style="margin-top:16px;text-align:center;">
      Don't have an account? <a href="signup.php" style="color:var(--primary);font-weight:700;">Sign up</a>
    </div>
    </div>
  </div>
</div>
</body>
</html>
