<?php
require 'config.php';
require 'includes/auth.php';
$active = 'customers';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $stmt = $pdo->prepare("INSERT INTO customers (name,email,phone,address,membership,notes) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['membership'], $_POST['notes']
    ]);
    $message = 'Customer added successfully.';
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM customers WHERE id=?")->execute([$_GET['delete']]);
    header('Location: customers.php');
    exit;
}

$search = trim($_GET['q'] ?? '');

if (isAdmin()) {
    $sql = "SELECT * FROM customers";
    if ($search !== '') {
        $sql .= " WHERE name LIKE ? OR email LIKE ?";
        $params = ["%$search%","%$search%"];
    } else {
        $params = [];
    }
} else {
    // Staff visibility is restricted at the database level: this view
    // never returns email or address, so there is nothing to leak here.
    $sql = "SELECT * FROM customers_staff_view";
    if ($search !== '') {
        $sql .= " WHERE name LIKE ?";
        $params = ["%$search%"];
    } else {
        $params = [];
    }
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customers - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1>Customer Management</h1>
      <div class="actions-row">
        <form class="search-box" method="get">
          <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg></span><input type="text" name="q" placeholder="Search Customer" value="<?= htmlspecialchars($search) ?>">
        </form>
        <button class="btn" onclick="document.getElementById('addCustomerModal').classList.add('show')">+ Add Customer</button>
      </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="panel">
      <table>
        <tr><th>Customer ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Membership</th><th>Action</th></tr>
        <?php foreach ($customers as $c): ?>
        <tr>
          <td>#<?= str_pad($c['id'],3,'0',STR_PAD_LEFT) ?></td>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= isAdmin() ? htmlspecialchars($c['email']) : '<span style="color:var(--muted);font-style:italic;">Restricted</span>' ?></td>
          <td><?= htmlspecialchars($c['phone']) ?></td>
          <td><span class="badge-<?= strtolower($c['membership']) ?>"><?= htmlspecialchars($c['membership']) ?></span></td>
          <td>
            <a class="icon-action" href="customer_profile.php?id=<?= $c['id'] ?>">👁</a>
            <button class="icon-action">✏️</button>
            <a class="icon-action danger" href="customers.php?delete=<?= $c['id'] ?>" onclick="return confirm('Delete this customer?')">🗑</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">No customers found.</td></tr>
        <?php endif; ?>
      </table>
      <div class="pagination">
        <span>Showing 1 to <?= count($customers) ?> of <?= count($customers) ?> entries</span>
      </div>
    </div>
  </main>
</div>

<div class="overlay" id="addCustomerModal">
  <div class="modal">
    <h3>Add Customer</h3>
    <form method="post">
      <div class="field"><label>Name</label><input type="text" name="name" required></div>
      <div class="form-grid">
        <div class="field"><label>Email</label><input type="email" name="email"></div>
        <div class="field"><label>Phone</label><input type="text" name="phone"></div>
      </div>
      <div class="field"><label>Address</label><input type="text" name="address"></div>
      <div class="field">
        <label>Membership</label>
        <select name="membership">
          <option>Bronze</option><option>Silver</option><option>Gold</option>
        </select>
      </div>
      <div class="field"><label>Notes</label><textarea name="notes" rows="3"></textarea></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addCustomerModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="add_customer" class="btn">Save Customer</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
