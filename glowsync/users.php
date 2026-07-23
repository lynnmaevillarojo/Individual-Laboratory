<?php
require 'config.php';
require 'includes/auth.php';
requireAdmin();
$active = 'users';
$message = '';
$error = '';

// --- Add user ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'] === 'Staff' ? 'Staff' : 'Admin';
    $pass  = $_POST['password'];

    $exists = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $exists->execute([$email]);

    if ($name === '' || $email === '' || strlen($pass) < 6) {
        $error = 'Please fill out all fields. Password must be at least 6 characters.';
    } elseif ($exists->fetch()) {
        $error = 'A user with that email already exists.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
            ->execute([$name, $email, $hash, $role]);
        $message = 'User added successfully.';
    }
}

// --- Edit user (name / email / role) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id    = (int)$_POST['id'];
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'] === 'Staff' ? 'Staff' : 'Admin';

    if ($id === (int)$_SESSION['user_id'] && $role !== 'Admin') {
        $error = "You can't remove your own Admin role.";
    } else {
        $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?")
            ->execute([$name, $email, $role, $id]);
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = $role;
        }
        $message = 'User updated successfully.';
    }
}

// --- Change password ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $id   = (int)$_POST['id'];
    $pass = $_POST['new_password'];

    if (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $id]);
        $message = 'Password changed successfully.';
    }
}

// --- Delete user ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='Admin'")->fetchColumn();
    $target = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $target->execute([$id]);
    $target = $target->fetch();

    if ($id === (int)$_SESSION['user_id']) {
        $error = "You can't delete your own account while signed in.";
    } elseif ($target && $target['role'] === 'Admin' && $adminCount <= 1) {
        $error = "You can't delete the last remaining Admin.";
    } elseif ($target) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        $message = 'User deleted successfully.';
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1>User Management</h1>
      <button class="btn" onclick="document.getElementById('addUserModal').classList.add('show')">+ Add User</button>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="panel">
      <table>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>#<?= str_pad($u['id'],3,'0',STR_PAD_LEFT) ?></td>
          <td><?= htmlspecialchars($u['name']) ?><?= $u['id'] === (int)$_SESSION['user_id'] ? ' <span style="color:var(--muted);font-weight:400;">(you)</span>' : '' ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="role-pill role-<?= strtolower($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
          <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <button class="icon-action" title="Edit"
              onclick='openEdit(<?= json_encode(["id"=>$u["id"],"name"=>$u["name"],"email"=>$u["email"],"role"=>$u["role"]]) ?>)'>✏️</button>
            <button class="icon-action" title="Change Password" onclick="openPassword(<?= $u['id'] ?>, <?= json_encode($u['name']) ?>)">🔑</button>
            <a class="icon-action danger" title="Delete" href="users.php?delete=<?= $u['id'] ?>" onclick="return confirm('Delete this user?')">🗑</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
      <div class="pagination">
        <span>Showing 1 to <?= count($users) ?> of <?= count($users) ?> entries</span>
      </div>
    </div>
  </main>
</div>

<!-- Add User -->
<div class="overlay" id="addUserModal">
  <div class="modal">
    <h3>Add User</h3>
    <form method="post">
      <div class="field"><label>Full Name</label><input type="text" name="name" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Password</label><input type="password" name="password" placeholder="At least 6 characters" required></div>
      <div class="field">
        <label>Role</label>
        <select name="role">
          <option value="Staff">Staff</option>
          <option value="Admin">Admin</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addUserModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="add_user" class="btn">Save User</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User -->
<div class="overlay" id="editUserModal">
  <div class="modal">
    <h3>Edit User</h3>
    <form method="post">
      <input type="hidden" name="id" id="editId">
      <div class="field"><label>Full Name</label><input type="text" name="name" id="editName" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" id="editEmail" required></div>
      <div class="field">
        <label>Role</label>
        <select name="role" id="editRole">
          <option value="Staff">Staff</option>
          <option value="Admin">Admin</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editUserModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="edit_user" class="btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Change Password -->
<div class="overlay" id="passwordModal">
  <div class="modal">
    <h3>Change Password</h3>
    <p style="color:var(--muted);font-size:13.5px;margin-top:-8px;" id="passwordFor"></p>
    <form method="post">
      <input type="hidden" name="id" id="passwordId">
      <div class="field"><label>New Password</label><input type="password" name="new_password" placeholder="At least 6 characters" required></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('passwordModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="change_password" class="btn">Update Password</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(u) {
  document.getElementById('editId').value = u.id;
  document.getElementById('editName').value = u.name;
  document.getElementById('editEmail').value = u.email;
  document.getElementById('editRole').value = u.role;
  document.getElementById('editUserModal').classList.add('show');
}
function openPassword(id, name) {
  document.getElementById('passwordId').value = id;
  document.getElementById('passwordFor').textContent = 'Set a new password for ' + name + '.';
  document.getElementById('passwordModal').classList.add('show');
}
</script>
</body>
</html>
