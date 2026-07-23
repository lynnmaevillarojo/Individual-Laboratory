<?php
require 'config.php';
require 'includes/auth.php';
$active = 'sales';
$error = '';
$message = '';

$id = (int)($_GET['id'] ?? 0);
$custTable = isAdmin() ? 'customers' : 'customers_staff_view';

$stmt = $pdo->prepare("
  SELECT s.*, c.name AS customer_name,
         p.name AS product_name, p.category AS product_category, p.stock AS product_stock
  FROM sales s
  JOIN $custTable c ON c.id = s.customer_id
  JOIN products p ON p.id = s.product_id
  WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    header('Location: sales.php');
    exit;
}

// Pull the customer's contact details separately so we can restrict
// email/address the same way customers.php and customer_profile.php do.
$custStmt = $pdo->prepare("SELECT * FROM $custTable WHERE id = ?");
$custStmt->execute([$sale['customer_id']]);
$customer = $custStmt->fetch();

// --- Handle status update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $oldStatus = $sale['status'];
    $valid = ['Pending','Processing','Completed','Cancelled'];

    if (!in_array($newStatus, $valid, true)) {
        $error = 'Invalid status.';
    } elseif ($newStatus === $oldStatus) {
        $message = 'Status unchanged.';
    } else {
        $by = $_SESSION['user_name'] ?? 'Admin';

        // Cancelling an active order returns the stock; reinstating a
        // cancelled order takes it back out. This keeps Inventory's
        // Stock History an accurate record of what actually happened.
        if ($newStatus === 'Cancelled' && $oldStatus !== 'Cancelled') {
            $newStock = $sale['product_stock'] + $sale['quantity'];
            $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([$newStock, $sale['product_id']]);
            $pdo->prepare("INSERT INTO inventory_log (product_id, type, quantity, reason, created_by) VALUES (?, 'IN', ?, ?, ?)")
                ->execute([$sale['product_id'], $sale['quantity'], "Order #".str_pad($sale['id'],3,'0',STR_PAD_LEFT)." cancelled", $by]);
        } elseif ($oldStatus === 'Cancelled' && $newStatus !== 'Cancelled') {
            $newStock = max(0, $sale['product_stock'] - $sale['quantity']);
            $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([$newStock, $sale['product_id']]);
            $pdo->prepare("INSERT INTO inventory_log (product_id, type, quantity, reason, created_by) VALUES (?, 'OUT', ?, ?, ?)")
                ->execute([$sale['product_id'], $sale['quantity'], "Order #".str_pad($sale['id'],3,'0',STR_PAD_LEFT)." reinstated", $by]);
        }

        $pdo->prepare("UPDATE sales SET status=? WHERE id=?")->execute([$newStatus, $id]);
        $sale['status'] = $newStatus;
        $message = "Order status updated to \"$newStatus\".";
    }
}

$steps = ['Pending','Processing','Completed'];
$currentIndex = array_search($sale['status'], $steps, true);

function orderBadge($status){
    $map = ['Completed'=>'completed','Pending'=>'pending','Processing'=>'processing','Cancelled'=>'open'];
    $cls = $map[$status] ?? 'pending';
    return "<span class=\"badge badge-$cls\">".htmlspecialchars($status)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order #<?= str_pad($sale['id'],3,'0',STR_PAD_LEFT) ?> - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1><a href="sales.php" style="color:var(--muted);font-size:15px;margin-right:10px;">← Back</a>Order #<?= str_pad($sale['id'],3,'0',STR_PAD_LEFT) ?></h1>
      <div class="actions-row">
        <a href="invoice.php?id=<?= $sale['id'] ?>" class="btn btn-secondary">🧾 View Invoice</a>
      </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="panel" style="margin-bottom:16px;">
      <div class="panel-head"><h3>Order Status</h3><?= orderBadge($sale['status']) ?></div>

      <?php if ($sale['status'] === 'Cancelled'): ?>
        <div class="alert alert-error" style="margin-bottom:18px;">This order was cancelled. Stock for this item has been returned to Inventory.</div>
      <?php else: ?>
      <div class="order-timeline">
        <?php foreach ($steps as $i => $step): ?>
          <div class="order-step <?= $i <= $currentIndex ? 'done' : '' ?> <?= $i === $currentIndex ? 'current' : '' ?>">
            <div class="dot"><?= $i < $currentIndex ? '✓' : ($i+1) ?></div>
            <div class="label"><?= $step ?></div>
          </div>
          <?php if ($i < count($steps)-1): ?><div class="order-line <?= $i < $currentIndex ? 'done' : '' ?>"></div><?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="post" class="filter-row" style="margin-top:20px;">
        <select name="status">
          <?php foreach (['Pending','Processing','Completed','Cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $sale['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" name="update_status" class="btn">Update Status</button>
      </form>
    </div>

    <div class="grid-2">
      <div class="panel">
        <div class="panel-head"><h3>Customer</h3><a href="customer_profile.php?id=<?= $sale['customer_id'] ?>">View Profile</a></div>
        <div class="info-row"><span>Name</span><span><?= htmlspecialchars($customer['name']) ?></span></div>
        <?php if (isAdmin()): ?>
        <div class="info-row"><span>Email</span><span><?= htmlspecialchars($customer['email'] ?: '—') ?></span></div>
        <div class="info-row"><span>Address</span><span><?= htmlspecialchars($customer['address'] ?: '—') ?></span></div>
        <?php else: ?>
        <div class="info-row"><span>Email</span><span style="color:var(--muted);font-style:italic;">Restricted</span></div>
        <div class="info-row"><span>Address</span><span style="color:var(--muted);font-style:italic;">Restricted</span></div>
        <?php endif; ?>
        <div class="info-row"><span>Phone</span><span><?= htmlspecialchars($customer['phone'] ?: '—') ?></span></div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Order Details</h3></div>
        <div class="info-row"><span>Product</span><span><?= htmlspecialchars($sale['product_name']) ?></span></div>
        <div class="info-row"><span>Category</span><span><?= htmlspecialchars($sale['product_category']) ?></span></div>
        <div class="info-row"><span>Quantity</span><span><?= $sale['quantity'] ?></span></div>
        <div class="info-row"><span>Unit Price</span><span>₱<?= number_format($sale['price'],2) ?></span></div>
        <div class="info-row"><span>Order Date</span><span><?= date('M d, Y', strtotime($sale['order_date'])) ?></span></div>
        <div class="info-row"><span>Total</span><span><strong>₱<?= number_format($sale['quantity']*$sale['price'],2) ?></strong></span></div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
