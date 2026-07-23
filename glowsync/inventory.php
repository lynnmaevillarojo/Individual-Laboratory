<?php
require 'config.php';
require 'includes/auth.php';
$active = 'inventory';
$message = '';
$error = '';

// --- Handle Stock In / Stock Out ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_move'])) {
    $productId = (int)$_POST['product_id'];
    $type      = $_POST['type'] === 'OUT' ? 'OUT' : 'IN';
    $qty       = (int)$_POST['quantity'];
    $reason    = trim($_POST['reason'] ?? '');
    $by        = $_SESSION['user_name'] ?? 'Admin';

    $prod = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $prod->execute([$productId]);
    $prod = $prod->fetch();

    if (!$prod) {
        $error = 'Product not found.';
    } elseif ($qty <= 0) {
        $error = 'Quantity must be greater than zero.';
    } elseif ($type === 'OUT' && $qty > $prod['stock']) {
        $error = "Not enough stock. Only {$prod['stock']} unit(s) of \"{$prod['name']}\" available.";
    } else {
        $pdo->beginTransaction();
        $newStock = $type === 'IN' ? $prod['stock'] + $qty : $prod['stock'] - $qty;
        $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([$newStock, $productId]);
        $pdo->prepare("INSERT INTO inventory_log (product_id, type, quantity, reason, created_by) VALUES (?,?,?,?,?)")
            ->execute([$productId, $type, $qty, $reason, $by]);
        $pdo->commit();
        $message = ($type === 'IN' ? 'Stock in' : 'Stock out') . " recorded for \"{$prod['name']}\".";
    }
}

// --- Stats ---
$inventoryValue = $pdo->query("SELECT COALESCE(SUM(price*stock),0) FROM products")->fetchColumn();
$totalProducts  = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockCount  = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= low_stock_threshold")->fetchColumn();
$outOfStock     = $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();

// --- Low stock list ---
$lowStock = $pdo->query("
  SELECT * FROM products WHERE stock <= low_stock_threshold ORDER BY stock ASC
")->fetchAll();

// --- Stock history with filters ---
$fProduct = trim($_GET['product'] ?? '');
$fType    = trim($_GET['type'] ?? '');

$sql = "SELECT l.*, p.name AS product_name
        FROM inventory_log l JOIN products p ON p.id = l.product_id WHERE 1=1";
$params = [];
if ($fProduct !== '') {
    $sql .= " AND l.product_id = ?";
    $params[] = $fProduct;
}
if ($fType !== '' && in_array($fType, ['IN','OUT'], true)) {
    $sql .= " AND l.type = ?";
    $params[] = $fType;
}
$sql .= " ORDER BY l.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll();

$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();

function stockBadge($p){
    if ($p['stock'] == 0) return '<span class="badge badge-outofstock">Out of Stock</span>';
    if ($p['stock'] <= $p['low_stock_threshold']) return '<span class="badge badge-lowstock">Low Stock</span>';
    return '<span class="badge badge-instock">In Stock</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1>Inventory Management</h1>
      <div class="actions-row">
        <button class="btn btn-secondary" onclick="openMove('IN')">📥 Stock In</button>
        <button class="btn" onclick="openMove('OUT')">📤 Stock Out</button>
      </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#f3e8fb;">💰</div><div class="label">Inventory Value</div></div>
        <div class="value">₱<?= number_format($inventoryValue,0) ?></div>
        <div class="delta up">▲ price × stock, all products</div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#eef2ff;">🧴</div><div class="label">Total Products</div></div>
        <div class="value"><?= $totalProducts ?></div>
        <div class="delta up">▲ active SKUs</div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#fef9c3;">⚠️</div><div class="label">Low Stock</div></div>
        <div class="value"><?= $lowStockCount ?></div>
        <div class="delta down">▼ at or below threshold</div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#fee2e2;">🚫</div><div class="label">Out of Stock</div></div>
        <div class="value"><?= $outOfStock ?></div>
        <div class="delta down">▼ needs restock</div>
      </div>
    </div>

    <?php if (!empty($lowStock)): ?>
    <div class="panel" style="margin-bottom:16px;">
      <div class="panel-head"><h3>⚠️ Low Stock Alerts</h3></div>
      <div class="low-stock-list">
        <?php foreach ($lowStock as $p): ?>
        <div class="low-stock-row">
          <div>
            <div class="name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="sub"><?= htmlspecialchars($p['category']) ?> · Threshold: <?= $p['low_stock_threshold'] ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            <?= stockBadge($p) ?>
            <strong><?= $p['stock'] ?> left</strong>
            <button class="btn" style="padding:6px 12px;font-size:12.5px;" onclick="openMove('IN', <?= $p['id'] ?>)">Restock</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head"><h3>Stock History</h3></div>
      <form method="get" class="filter-row" style="margin-bottom:14px;">
        <select name="product" onchange="this.form.submit()">
          <option value="">All Products</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $fProduct == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="type" onchange="this.form.submit()">
          <option value="">All Movements</option>
          <option value="IN" <?= $fType === 'IN' ? 'selected' : '' ?>>Stock In</option>
          <option value="OUT" <?= $fType === 'OUT' ? 'selected' : '' ?>>Stock Out</option>
        </select>
        <?php if ($fProduct !== '' || $fType !== ''): ?>
          <a class="filter-reset" href="inventory.php">Clear filters ✕</a>
        <?php endif; ?>
      </form>
      <table>
        <tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Reason</th><th>By</th></tr>
        <?php foreach ($history as $h): ?>
        <tr>
          <td><?= date('M d, Y g:i a', strtotime($h['created_at'])) ?></td>
          <td><?= htmlspecialchars($h['product_name']) ?></td>
          <td><span class="badge badge-<?= strtolower($h['type']) ?>"><?= $h['type'] === 'IN' ? 'Stock In' : 'Stock Out' ?></span></td>
          <td><?= $h['type'] === 'IN' ? '+' : '-' ?><?= $h['quantity'] ?></td>
          <td><?= htmlspecialchars($h['reason'] ?: '—') ?></td>
          <td><?= htmlspecialchars($h['created_by'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($history)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">No stock movements found.</td></tr>
        <?php endif; ?>
      </table>
    </div>
  </main>
</div>

<div class="overlay" id="moveModal">
  <div class="modal">
    <h3 id="moveTitle">Stock In</h3>
    <form method="post">
      <input type="hidden" name="type" id="moveType" value="IN">
      <div class="field">
        <label>Product</label>
        <select name="product_id" id="moveProduct" required>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['stock'] ?> in stock)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Quantity</label><input type="number" name="quantity" min="1" value="1" required></div>
      <div class="field"><label>Reason / Note</label><input type="text" name="reason" placeholder="e.g. New delivery, Damaged, Sold in-store"></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('moveModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="stock_move" class="btn" id="moveSubmit">Save Stock In</button>
      </div>
    </form>
  </div>
</div>

<script>
function openMove(type, productId) {
  document.getElementById('moveType').value = type;
  document.getElementById('moveTitle').textContent = type === 'IN' ? 'Stock In' : 'Stock Out';
  document.getElementById('moveSubmit').textContent = type === 'IN' ? 'Save Stock In' : 'Save Stock Out';
  if (productId) {
    document.getElementById('moveProduct').value = productId;
  }
  document.getElementById('moveModal').classList.add('show');
}
</script>
</body>
</html>
