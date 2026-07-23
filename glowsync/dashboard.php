<?php
require 'config.php';
require 'includes/auth.php';
$active = 'dashboard';

$totalSales   = $pdo->query("SELECT COALESCE(SUM(quantity*price),0) FROM sales WHERE status='Completed'")->fetchColumn();
$customers    = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$pendingOrders= $pdo->query("SELECT COUNT(*) FROM sales WHERE status='Pending'")->fetchColumn();
$openTickets  = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='Open'")->fetchColumn();

$recentSales = $pdo->query("
  SELECT s.id, c.name AS customer, p.name AS product, (s.quantity*s.price) AS amount, s.status
  FROM sales s JOIN customers c ON c.id=s.customer_id JOIN products p ON p.id=s.product_id
  ORDER BY s.id DESC LIMIT 3
")->fetchAll();

$recentTickets = $pdo->query("
  SELECT t.id, c.name AS customer, t.issue, t.status
  FROM tickets t JOIN customers c ON c.id=t.customer_id
  ORDER BY t.id DESC LIMIT 3
")->fetchAll();

// --- Chart: Monthly Sales (last 6 months) ---
$monthlySalesRaw = $pdo->query("
  SELECT DATE_FORMAT(order_date,'%Y-%m') AS ym, SUM(quantity*price) AS total
  FROM sales
  WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
  GROUP BY ym ORDER BY ym ASC
")->fetchAll();
$monthlySalesMap = [];
foreach ($monthlySalesRaw as $r) { $monthlySalesMap[$r['ym']] = (float)$r['total']; }
$monthlySalesLabels = [];
$monthlySalesValues = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $monthlySalesLabels[] = date('M Y', strtotime("-$i months"));
    $monthlySalesValues[] = $monthlySalesMap[$ym] ?? 0;
}

// --- Chart: Product Sales (top 6 by revenue) ---
$productSales = $pdo->query("
  SELECT p.name, SUM(s.quantity*s.price) AS total
  FROM sales s JOIN products p ON p.id = s.product_id
  GROUP BY p.id ORDER BY total DESC LIMIT 6
")->fetchAll();
$productSalesLabels = array_map(fn($r) => $r['name'], $productSales);
$productSalesValues = array_map(fn($r) => (float)$r['total'], $productSales);

// --- Chart: Ticket Status ---
$ticketStatus = $pdo->query("SELECT status, COUNT(*) AS c FROM tickets GROUP BY status")->fetchAll();
$ticketStatusMap = ['Open'=>0,'In Progress'=>0,'Closed'=>0];
foreach ($ticketStatus as $r) { $ticketStatusMap[$r['status']] = (int)$r['c']; }

// --- Chart: Customer Growth (cumulative, last 6 months) ---
$totalCustomersNow = (int)$customers;
$newPerMonthRaw = $pdo->query("
  SELECT DATE_FORMAT(joined_date,'%Y-%m') AS ym, COUNT(*) AS c
  FROM customers
  WHERE joined_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
  GROUP BY ym
")->fetchAll();
$newPerMonthMap = [];
foreach ($newPerMonthRaw as $r) { $newPerMonthMap[$r['ym']] = (int)$r['c']; }
$newSinceWindow = 0;
foreach ($newPerMonthMap as $c) { $newSinceWindow += $c; }
$runningTotal = $totalCustomersNow - $newSinceWindow;
$growthLabels = [];
$growthValues = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $runningTotal += $newPerMonthMap[$ym] ?? 0;
    $growthLabels[] = date('M Y', strtotime("-$i months"));
    $growthValues[] = $runningTotal;
}

function badge($status){
    $map = ['Completed'=>'completed','Pending'=>'pending','Processing'=>'processing',
            'Open'=>'open','In Progress'=>'progress','Closed'=>'closed'];
    $cls = $map[$status] ?? 'pending';
    return "<span class=\"badge badge-$cls\">".htmlspecialchars($status)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - GlowSync</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head"><h2>Dashboard</h2></div>

    <?php if (isset($_GET['denied'])): ?>
      <div class="alert alert-error">That page is only available to Admin accounts.</div>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#f3e8fb;">🤝</div><div class="label">Total Sales</div></div>
        <div class="value">₱ <?= number_format($totalSales,0) ?></div>
        <div class="delta up">▲ from completed orders</div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#eef2ff;">👥</div><div class="label">Customers</div></div>
        <div class="value"><?= $customers ?></div>
        <div class="delta up">▲ registered customers</div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#fef9c3;">⏱</div><div class="label">Pending Orders</div></div>
        <div class="value"><?= $pendingOrders ?></div>
        <div class="delta down">▼ needs attention</div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#fee2e2;">💳</div><div class="label">Open Tickets</div></div>
        <div class="value"><?= $openTickets ?></div>
        <div class="delta down">▼ awaiting reply</div>
      </div>
    </div>

    <div class="chart-grid">
      <div class="panel">
        <div class="panel-head"><h3>Monthly Sales</h3></div>
        <div class="chart-box"><canvas id="monthlySalesChart"></canvas></div>
      </div>
      <div class="panel">
        <div class="panel-head"><h3>Product Sales</h3></div>
        <div class="chart-box"><canvas id="productSalesChart"></canvas></div>
      </div>
      <div class="panel">
        <div class="panel-head"><h3>Ticket Status</h3></div>
        <div class="chart-box"><canvas id="ticketStatusChart"></canvas></div>
      </div>
      <div class="panel">
        <div class="panel-head"><h3>Customer Growth</h3></div>
        <div class="chart-box"><canvas id="customerGrowthChart"></canvas></div>
      </div>
    </div>

    <div class="grid-2">
      <div class="panel">
        <div class="panel-head"><h3>Recent Sales</h3><a href="sales.php">View All</a></div>
        <table>
          <tr><th>Order ID</th><th>Customer</th><th>Product</th><th>Amount</th><th>Status</th></tr>
          <?php foreach ($recentSales as $s): ?>
          <tr>
            <td>#<?= str_pad($s['id'],3,'0',STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars($s['customer']) ?></td>
            <td><?= htmlspecialchars($s['product']) ?></td>
            <td>₱<?= number_format($s['amount'],0) ?></td>
            <td><?= badge($s['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Recent Support Ticket</h3><a href="support.php">View All</a></div>
        <table>
          <tr><th>Ticket ID</th><th>Customer</th><th>Issue</th><th>Status</th></tr>
          <?php foreach ($recentTickets as $t): ?>
          <tr>
            <td>#<?= str_pad($t['id'],3,'0',STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars($t['customer']) ?></td>
            <td><?= htmlspecialchars($t['issue']) ?></td>
            <td><?= badge($t['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </main>
</div>

<script>
new Chart(document.getElementById('monthlySalesChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($monthlySalesLabels) ?>,
    datasets: [{
      label: 'Sales (₱)',
      data: <?= json_encode($monthlySalesValues) ?>,
      borderColor: '#9c27b0',
      backgroundColor: 'rgba(156,39,176,0.12)',
      fill: true, tension: 0.35, pointRadius: 3
    }]
  },
  options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('productSalesChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($productSalesLabels) ?>,
    datasets: [{
      label: 'Revenue (₱)',
      data: <?= json_encode($productSalesValues) ?>,
      backgroundColor: '#ba55e0',
      borderRadius: 6
    }]
  },
  options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('ticketStatusChart'), {
  type: 'doughnut',
  data: {
    labels: ['Open','In Progress','Closed'],
    datasets: [{
      data: [<?= $ticketStatusMap['Open'] ?>, <?= $ticketStatusMap['In Progress'] ?>, <?= $ticketStatusMap['Closed'] ?>],
      backgroundColor: ['#dc2626','#b45309','#16a34a']
    }]
  },
  options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('customerGrowthChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($growthLabels) ?>,
    datasets: [{
      label: 'Total Customers',
      data: <?= json_encode($growthValues) ?>,
      borderColor: '#1d4ed8',
      backgroundColor: 'rgba(29,78,216,0.12)',
      fill: true, tension: 0.35, pointRadius: 3
    }]
  },
  options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>
