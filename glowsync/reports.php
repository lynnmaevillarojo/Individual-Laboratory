<?php
require 'config.php';
require 'includes/auth.php';
$active = 'reports';

$today   = $pdo->query("SELECT COALESCE(SUM(quantity*price),0) FROM sales WHERE order_date = CURDATE()")->fetchColumn();
$weekly  = $pdo->query("SELECT COALESCE(SUM(quantity*price),0) FROM sales WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$monthly = $pdo->query("SELECT COALESCE(SUM(quantity*price),0) FROM sales WHERE MONTH(order_date)=MONTH(CURDATE()) AND YEAR(order_date)=YEAR(CURDATE())")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();

// last 10 sales dates for the trend chart
$trend = $pdo->query("
  SELECT order_date, SUM(quantity*price) AS total
  FROM sales GROUP BY order_date ORDER BY order_date ASC LIMIT 30
")->fetchAll();

$labels = array_map(fn($r) => date('M d', strtotime($r['order_date'])), $trend);
$values = array_map(fn($r) => (float)$r['total'], $trend);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports - GlowSync</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1>Sales Report</h1>
      <div class="search-box">📅 <span>Jan 1 - Dec 31, <?= date('Y') ?></span></div>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><div class="label">Today's Sales</div><div class="value">₱<?= number_format($today,0) ?></div></div>
      <div class="stat-card"><div class="label">Weekly Sales</div><div class="value">₱<?= number_format($weekly,0) ?></div></div>
      <div class="stat-card"><div class="label">Monthly Sales</div><div class="value">₱<?= number_format($monthly,0) ?></div></div>
      <div class="stat-card"><div class="label">Total Orders</div><div class="value"><?= $totalOrders ?></div></div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Sales Overview</h3><button class="btn-secondary btn" onclick="window.print()">⭱ Export</button></div>
      <canvas id="salesChart" height="90"></canvas>
    </div>
  </main>
</div>

<script>
const ctx = document.getElementById('salesChart');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Sales (₱)',
      data: <?= json_encode($values) ?>,
      borderColor: '#9c27b0',
      backgroundColor: 'rgba(156,39,176,0.12)',
      fill: true,
      tension: 0.35,
      pointRadius: 3
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
</script>
</body>
</html>
