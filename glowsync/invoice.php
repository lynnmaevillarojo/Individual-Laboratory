<?php
require 'config.php';
require 'includes/auth.php';
$active = 'sales';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
  SELECT s.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address,
         p.name AS product_name, p.category AS product_category
  FROM sales s
  JOIN customers c ON c.id = s.customer_id
  JOIN products p ON p.id = s.product_id
  WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    header('Location: sales.php');
    exit;
}

$invoiceNo = 'INV-' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT);
$subtotal  = $sale['quantity'] * $sale['price'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $invoiceNo ?> - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head no-print">
      <h1><a href="sales.php" style="color:var(--muted);font-size:15px;margin-right:10px;">← Back</a>Sales Invoice</h1>
    </div>

    <div class="invoice-actions no-print">
      <a href="sales.php" class="btn btn-secondary">Back to Sales</a>
      <button class="btn" onclick="window.print()">🖨 Print Receipt</button>
      <button class="btn" onclick="window.print()">⭳ Download PDF</button>
    </div>

    <div class="panel invoice-wrap">
      <div class="invoice-head">
        <div class="brand">
          <img src="glowcode.png" alt="GlowSync">
          <div>
            <h1>GlowSync</h1>
            <div style="font-size:12px;color:var(--muted);">Sales &amp; Customer Support Management</div>
          </div>
        </div>
        <div class="invoice-meta">
          <div class="num"><?= $invoiceNo ?></div>
          <div>Order #<?= str_pad($sale['id'],3,'0',STR_PAD_LEFT) ?></div>
          <div>Date: <?= date('M d, Y', strtotime($sale['order_date'])) ?></div>
          <div>Status: <strong><?= htmlspecialchars($sale['status']) ?></strong></div>
        </div>
      </div>

      <div class="invoice-parties">
        <div>
          <div class="label">Billed To</div>
          <div style="font-weight:700;"><?= htmlspecialchars($sale['customer_name']) ?></div>
          <div style="font-size:13px;color:var(--muted);"><?= htmlspecialchars($sale['customer_email'] ?: '—') ?></div>
          <div style="font-size:13px;color:var(--muted);"><?= htmlspecialchars($sale['customer_phone'] ?: '—') ?></div>
          <div style="font-size:13px;color:var(--muted);"><?= htmlspecialchars($sale['customer_address'] ?: '—') ?></div>
        </div>
        <div>
          <div class="label">Issued By</div>
          <div style="font-weight:700;">GlowSync</div>
          <div style="font-size:13px;color:var(--muted);">Handled by <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
        </div>
      </div>

      <table>
        <tr><th>Product</th><th>Category</th><th>Qty</th><th>Unit Price</th><th>Amount</th></tr>
        <tr>
          <td><?= htmlspecialchars($sale['product_name']) ?></td>
          <td><?= htmlspecialchars($sale['product_category']) ?></td>
          <td><?= $sale['quantity'] ?></td>
          <td>₱<?= number_format($sale['price'],2) ?></td>
          <td>₱<?= number_format($subtotal,2) ?></td>
        </tr>
      </table>

      <div class="invoice-total-row">Total: ₱<?= number_format($subtotal,2) ?></div>

      <p style="text-align:center;color:var(--muted);font-size:12.5px;margin-top:10px;">
        Thank you for shopping with GlowSync!
      </p>
    </div>
  </main>
</div>
</body>
</html>
