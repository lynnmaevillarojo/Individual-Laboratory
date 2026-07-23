<?php
require 'config.php';
require 'includes/auth.php';
$active = 'products';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $imageName = '';

if (!empty($_FILES['image']['name'])) {

    $imageName = time() . "_" . basename($_FILES['image']['name']);

    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        "uploads/" . $imageName
    );
}

$stmt = $pdo->prepare("
INSERT INTO products
(name, category, price, stock, low_stock_threshold, image)
VALUES
(?,?,?,?,?,?)
");

$stmt->execute([
    $_POST['name'],
    $_POST['category'],
    $_POST['price'],
    $_POST['stock'],
    $_POST['low_stock_threshold'] !== '' ? $_POST['low_stock_threshold'] : 10,
    $imageName
]);
    $message = 'Product added successfully.';
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$_GET['delete']]);
    header('Location: products.php');
    exit;
}

$search   = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}
if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

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
<title>Products - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h2>Products</h2>
      <div class="actions-row">
        <form class="search-box" method="get">
          <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg></span><input type="text" name="q" placeholder="Search Product" value="<?= htmlspecialchars($search) ?>">
        </form>
        <button class="btn" onclick="document.getElementById('addProductModal').classList.add('show')">+ Add Product</button>
      </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <form method="get" class="filter-row" style="margin-bottom:14px;">
      <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
      <select name="category" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($category !== ''): ?>
        <a class="filter-reset" href="products.php<?= $search !== '' ? '?q='.urlencode($search) : '' ?>">Clear filter ✕</a>
      <?php endif; ?>
    </form>

    <div class="panel">
      <table>
        <tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($products as $p): ?>
        <tr>
          <td>#<?= str_pad($p['id'],3,'0',STR_PAD_LEFT) ?></td>
         <td>

<?php if (!empty($p['image'])): ?>

<img
    src="uploads/<?= htmlspecialchars($p['image']) ?>"
    width="70"
    height="70"
    style="object-fit:cover;border-radius:10px;">

<?php else: ?>

No Image

<?php endif; ?>

</td>

<td><?= htmlspecialchars($p['name']) ?></td>
          <td><?= htmlspecialchars($p['category']) ?></td>
          <td>₱<?= number_format($p['price'],0) ?></td>
          <td><?= $p['stock'] ?></td>
          <td><?= stockBadge($p) ?></td>
          <td><a class="icon-action danger" href="products.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this product?')">🗑</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px;">No products found.</td></tr>
        <?php endif; ?>
      </table>
    </div>
  </main>
</div>

<div class="overlay" id="addProductModal">
  <div class="modal">
    <h3>Add Product</h3>
    <form method="post" enctype="multipart/form-data">
      <div class="field">
    <label>Product Image</label>
    <input type="file" name="image" accept="image/*">
</div>
      <div class="field"><label>Name</label><input type="text" name="name" required></div>
      <div class="field"><label>Category</label><input type="text" name="category"></div>
      <div class="form-grid">
        <div class="field"><label>Price</label><input type="number" step="0.01" name="price" required></div>
        <div class="field"><label>Stock</label><input type="number" name="stock" value="0"></div>
      </div>
      <div class="field"><label>Low Stock Threshold</label><input type="number" name="low_stock_threshold" value="10" min="0"></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addProductModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="add_product" class="btn">Save Product</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
