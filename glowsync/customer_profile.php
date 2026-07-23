<?php
require 'config.php';
require 'includes/auth.php';
$active = 'customers';

$id = (int)($_GET['id'] ?? 0);
$table = isAdmin() ? 'customers' : 'customers_staff_view';
$stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: customers.php');
    exit;
}

$purchases = $pdo->prepare("
  SELECT p.name AS product, s.order_date, (s.quantity*s.price) AS amount, s.status
  FROM sales s JOIN products p ON p.id=s.product_id
  WHERE s.customer_id=? ORDER BY s.order_date DESC
");
$purchases->execute([$id]);
$purchases = $purchases->fetchAll();

$totals = $pdo->prepare("SELECT COUNT(*) AS orders, COALESCE(SUM(quantity*price),0) AS spent FROM sales WHERE customer_id=?");
$totals->execute([$id]);
$totals = $totals->fetch();

$tickets = $pdo->prepare("SELECT * FROM tickets WHERE customer_id=? ORDER BY created_at DESC");
$tickets->execute([$id]);
$tickets = $tickets->fetchAll();

function sbadge($status){
    $map = ['Open'=>'open','In Progress'=>'progress','Closed'=>'closed'];
    $cls = $map[$status] ?? 'pending';
    return "<span class=\"badge badge-$cls\">".htmlspecialchars($status)."</span>";
}

function badge($status){
    $map = ['Completed'=>'completed','Pending'=>'pending','Processing'=>'processing','Cancelled'=>'open'];
    $cls = $map[$status] ?? 'pending';
    return "<span class=\"badge badge-$cls\">".htmlspecialchars($status)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($customer['name']) ?> - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h2>Customer Profile</h2>
      <button class="btn">✏️ Edit Profile</button>
    </div>

    <div class="profile-grid">
      <div class="panel">
        <div class="avatar-circle">👤</div>
        <h3 style="text-align:center;margin:6px 0 4px;"><?= htmlspecialchars($customer['name']) ?></h3>
        <div style="text-align:center;margin-bottom:14px;">
          <span class="badge-<?= strtolower($customer['membership']) ?>"><?= htmlspecialchars($customer['membership']) ?> Member</span>
        </div>
        <div class="info-row"><span>Customer ID</span><span>#<?= str_pad($customer['id'],3,'0',STR_PAD_LEFT) ?></span></div>
        <div class="info-row"><span>Email</span><span><?= isAdmin() ? htmlspecialchars($customer['email']) : '<span style="color:var(--muted);font-style:italic;">Restricted</span>' ?></span></div>
        <div class="info-row"><span>Phone</span><span><?= htmlspecialchars($customer['phone']) ?></span></div>
        <div class="info-row"><span>Address</span><span><?= isAdmin() ? htmlspecialchars($customer['address']) : '<span style="color:var(--muted);font-style:italic;">Restricted</span>' ?></span></div>
        <div class="info-row"><span>Joined Date</span><span><?= date('M d, Y', strtotime($customer['joined_date'])) ?></span></div>
        <div class="info-row"><span>Total Orders</span><span><?= $totals['orders'] ?></span></div>
        <div class="info-row"><span>Total Spent</span><span>₱<?= number_format($totals['spent'],0) ?></span></div>
      </div>

      <div>
        <div class="panel" style="margin-bottom:16px;">
          <div class="panel-head"><h3>Purchase History</h3></div>
          <table>
            <tr><th>Product</th><th>Date</th><th>Amount</th><th>Status</th></tr>
            <?php foreach ($purchases as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['product']) ?></td>
              <td><?= date('M d, Y', strtotime($p['order_date'])) ?></td>
              <td>₱<?= number_format($p['amount'],0) ?></td>
              <td><?= badge($p['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($purchases)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:20px;">No purchases yet.</td></tr>
            <?php endif; ?>
          </table>
        </div>
        <div class="panel" style="margin-bottom:16px;">
          <div class="panel-head"><h3>Support History</h3><a href="support.php">View All</a></div>
          <table>
            <tr><th>Issue</th><th>Priority</th><th>Status</th><th>Date</th></tr>
            <?php foreach ($tickets as $t): ?>
            <tr>
              <td><a href="ticket_details.php?id=<?= $t['id'] ?>"><?= htmlspecialchars($t['issue']) ?></a></td>
              <td><?= htmlspecialchars($t['priority']) ?></td>
              <td><?= sbadge($t['status']) ?></td>
              <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tickets)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:20px;">No support tickets yet.</td></tr>
            <?php endif; ?>
          </table>
        </div>
        <div class="panel">
          <div class="panel-head"><h3>Notes</h3></div>
          <p style="margin:0;color:#555;"><?= htmlspecialchars($customer['notes'] ?: 'No notes yet.') ?></p>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
