<?php
require 'config.php';
require 'includes/auth.php';
$active = 'sales';
$message = '';

// --- Handle new order ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_order'])) {
    $productId = (int)$_POST['product_id'];
    $qty       = (int)$_POST['quantity'];

    $stmt = $pdo->prepare("INSERT INTO sales (customer_id, product_id, quantity, price, status, order_date) VALUES (?,?,?,?,?,CURDATE())");
    $stmt->execute([
        $_POST['customer_id'],
        $productId,
        $qty,
        $_POST['price'],
        $_POST['status'],
    ]);

    // Keep inventory in sync: selling a product is a Stock Out movement.
    $prod = $pdo->prepare("SELECT stock FROM products WHERE id=?");
    $prod->execute([$productId]);
    $prod = $prod->fetch();
    if ($prod) {
        $newStock = max(0, $prod['stock'] - $qty);
        $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([$newStock, $productId]);
        $pdo->prepare("INSERT INTO inventory_log (product_id, type, quantity, reason, created_by) VALUES (?, 'OUT', ?, 'Sold via order', ?)")
            ->execute([$productId, $qty, $_SESSION['user_name'] ?? 'Admin']);
    }

    $message = 'Order added successfully.';
}

// --- Handle delete ---
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM sales WHERE id=?")->execute([$_GET['delete']]);
    header('Location: sales.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$fCustomer = trim($_GET['customer'] ?? '');
$fDate = trim($_GET['date'] ?? '');

$sql = "SELECT s.id, s.customer_id, c.name AS customer, p.name AS product, s.quantity, s.price, s.status, s.order_date
        FROM sales s JOIN customers c ON c.id=s.customer_id JOIN products p ON p.id=s.product_id WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (c.name LIKE ? OR p.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($fCustomer !== '') {
    $sql .= " AND s.customer_id = ?";
    $params[] = $fCustomer;
}
if ($fDate !== '') {
    $sql .= " AND s.order_date = ?";
    $params[] = $fDate;
}
$sql .= " ORDER BY s.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$customers = $pdo->query("SELECT id,name FROM customers ORDER BY name")->fetchAll();
$products  = $pdo->query("SELECT id,name,price FROM products ORDER BY name")->fetchAll();

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
<title>Sales - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1>Sales Management</h1>
      <div class="actions-row">
        <form class="search-box" method="get">
          <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg></span><input type="text" name="q" placeholder="Search Order" value="<?= htmlspecialchars($search) ?>">
        </form>
        <button class="btn" onclick="document.getElementById('addOrderModal').classList.add('show')">+ New Order</button>
      </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <form method="get" class="filter-row" style="margin-bottom:14px;">
      <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
      <select name="customer" onchange="this.form.submit()">
        <option value="">All Customers</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $fCustomer == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="date" value="<?= htmlspecialchars($fDate) ?>" onchange="this.form.submit()">
      <?php if ($fCustomer !== '' || $fDate !== ''): ?>
        <a class="filter-reset" href="sales.php<?= $search !== '' ? '?q='.urlencode($search) : '' ?>">Clear filters ✕</a>
      <?php endif; ?>
    </form>

    <div class="panel">
      <table>
        <tr><th>Order ID</th><th>Customer</th><th>Product</th><th>Qty</th><th>Price</th><th>Date</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($sales as $s): ?>
        <tr>
          <td>#<?= str_pad($s['id'],3,'0',STR_PAD_LEFT) ?></td>
          <td><?= htmlspecialchars($s['customer']) ?></td>
          <td><?= htmlspecialchars($s['product']) ?></td>
          <td><?= $s['quantity'] ?></td>
          <td>₱<?= number_format($s['price'],0) ?></td>
          <td><?= date('M d, Y', strtotime($s['order_date'])) ?></td>
          <td><?= badge($s['status']) ?></td>
          <td>
            <a class="icon-action" href="order_details.php?id=<?= $s['id'] ?>" title="Manage Order">👁</a>
            <a class="icon-action" href="invoice.php?id=<?= $s['id'] ?>" title="View Invoice">🧾</a>
            <a class="icon-action danger" href="sales.php?delete=<?= $s['id'] ?>" onclick="return confirm('Delete this order?')">🗑</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($sales)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px;">No orders found.</td></tr>
        <?php endif; ?>
      </table>

      <div class="pagination">
        <span>Showing 1 to <?= count($sales) ?> of <?= count($sales) ?> entries</span>
      </div>
    </div>
  </main>
</div>

<!-- Add Order Modal -->
<div class="overlay" id="addOrderModal">
  <div class="modal">
    <h3>New Order</h3>
    <form method="post">
      <div class="field">
        <label>Customer</label>
        <select name="customer_id" required>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Product</label>
        <select name="product_id" id="productSelect" required onchange="fillPrice()">
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['name']) ?> (₱<?= number_format($p['price'],0) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-grid">
        <div class="field">
          <label>Quantity</label>
          <input type="number" name="quantity" min="1" value="1" required>
        </div>
        <div class="field">
          <label>Price</label>
          <input type="number" step="0.01" name="price" id="priceInput" value="<?= $products[0]['price'] ?? 0 ?>" required>
        </div>
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <option>Pending</option>
          <option>Processing</option>
          <option>Completed</option>
          <option>Cancelled</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addOrderModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="add_order" class="btn">Save Order</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillPrice(){
  const sel = document.getElementById('productSelect');
  document.getElementById('priceInput').value = sel.options[sel.selectedIndex].dataset.price;
}
</script>
</body>
</html>
