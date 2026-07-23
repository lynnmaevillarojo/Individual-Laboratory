<?php
require 'config.php';
require 'includes/auth.php';
$active = 'support';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ticket'])) {
    $stmt = $pdo->prepare("INSERT INTO tickets (customer_id, issue, description, priority, status, assigned_to) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['customer_id'], $_POST['issue'], $_POST['description'], $_POST['priority'], 'Open', $_POST['assigned_to']
    ]);
    $message = 'Ticket created successfully.';
}

$search = trim($_GET['q'] ?? '');
$fStatus = trim($_GET['status'] ?? '');
$fPriority = trim($_GET['priority'] ?? '');

$sql = "SELECT t.*, c.name AS customer FROM tickets t JOIN customers c ON c.id=t.customer_id WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (c.name LIKE ? OR t.issue LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($fStatus !== '') {
    $sql .= " AND t.status = ?";
    $params[] = $fStatus;
}
if ($fPriority !== '') {
    $sql .= " AND t.priority = ?";
    $params[] = $fPriority;
}
$sql .= " ORDER BY t.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$customers = $pdo->query("SELECT id,name FROM customers ORDER BY name")->fetchAll();

function pbadge($p){
    $map=['High'=>'open','Medium'=>'progress','Low'=>'completed'];
    return "<span class=\"badge badge-{$map[$p]}\">".htmlspecialchars($p)."</span>";
}
function sbadge($s){
    $map=['Open'=>'open','In Progress'=>'progress','Closed'=>'closed'];
    return "<span class=\"badge badge-{$map[$s]}\">".htmlspecialchars($s)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Support - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1>Customer Support</h1>
      <div class="actions-row">
        <form class="search-box" method="get">
          <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg></span><input type="text" name="q" placeholder="Search Ticket" value="<?= htmlspecialchars($search) ?>">
        </form>
        <button class="btn" onclick="document.getElementById('addTicketModal').classList.add('show')">+ New Ticket</button>
      </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <form method="get" class="filter-row" style="margin-bottom:14px;">
      <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
      <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="Open" <?= $fStatus === 'Open' ? 'selected' : '' ?>>Open</option>
        <option value="In Progress" <?= $fStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="Closed" <?= $fStatus === 'Closed' ? 'selected' : '' ?>>Closed</option>
      </select>
      <select name="priority" onchange="this.form.submit()">
        <option value="">All Priorities</option>
        <option value="Low" <?= $fPriority === 'Low' ? 'selected' : '' ?>>Low</option>
        <option value="Medium" <?= $fPriority === 'Medium' ? 'selected' : '' ?>>Medium</option>
        <option value="High" <?= $fPriority === 'High' ? 'selected' : '' ?>>High</option>
      </select>
      <?php if ($fStatus !== '' || $fPriority !== ''): ?>
        <a class="filter-reset" href="support.php<?= $search !== '' ? '?q='.urlencode($search) : '' ?>">Clear filters ✕</a>
      <?php endif; ?>
    </form>

    <div class="panel">
      <table>
        <tr><th>Ticket ID</th><th>Customer</th><th>Issue</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>Action</th></tr>
        <?php foreach ($tickets as $t): ?>
        <tr>
          <td>#<?= str_pad($t['id'],3,'0',STR_PAD_LEFT) ?></td>
          <td><?= htmlspecialchars($t['customer']) ?></td>
          <td><?= htmlspecialchars($t['issue']) ?></td>
          <td><?= pbadge($t['priority']) ?></td>
          <td><?= sbadge($t['status']) ?></td>
          <td><?= htmlspecialchars($t['assigned_to']) ?></td>
          <td><a class="icon-action" href="ticket_details.php?id=<?= $t['id'] ?>">👁</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px;">No tickets found.</td></tr>
        <?php endif; ?>
      </table>
      <div class="pagination">
        <span>Showing 1 to <?= count($tickets) ?> of <?= count($tickets) ?> entries</span>
      </div>
    </div>
  </main>
</div>

<div class="overlay" id="addTicketModal">
  <div class="modal">
    <h3>New Ticket</h3>
    <form method="post">
      <div class="field">
        <label>Customer</label>
        <select name="customer_id" required>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Issue</label><input type="text" name="issue" required></div>
      <div class="field"><label>Description</label><textarea name="description" rows="3"></textarea></div>
      <div class="form-grid">
        <div class="field">
          <label>Priority</label>
          <select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select>
        </div>
        <div class="field"><label>Assigned To</label><input type="text" name="assigned_to" placeholder="Staff name"></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTicketModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="add_ticket" class="btn">Save Ticket</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
