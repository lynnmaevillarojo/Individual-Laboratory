<?php
require 'config.php';
require 'includes/auth.php';
$active = 'support';

$id = (int)($_GET['id'] ?? 0);

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, sender_name, message) VALUES (?,?,?,?)");
    $stmt->execute([$id, 'agent', $_SESSION['user_name'] ?? 'Agent', trim($_POST['message'])]);

    if (!empty($_POST['new_status'])) {
        $pdo->prepare("UPDATE tickets SET status=? WHERE id=?")->execute([$_POST['new_status'], $id]);
    }
    header("Location: ticket_details.php?id=$id");
    exit;
}

$stmt = $pdo->prepare("SELECT t.*, c.name AS customer FROM tickets t JOIN customers c ON c.id=t.customer_id WHERE t.id=?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: support.php');
    exit;
}

$msgs = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
$msgs->execute([$id]);
$msgs = $msgs->fetchAll();

function sbadge($s){
    $map=['Open'=>'open','In Progress'=>'progress','Closed'=>'closed'];
    return "<span class=\"badge badge-{$map[$s]}\">".htmlspecialchars($s)."</span>";
}
function pbadge($p){
    $map=['High'=>'open','Medium'=>'progress','Low'=>'completed'];
    return "<span class=\"badge badge-{$map[$p]}\">".htmlspecialchars($p)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ticket #<?= $ticket['id'] ?> - GlowSync</title>
<link rel="stylesheet" href="s  style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1><a href="support.php" style="color:var(--muted);font-size:15px;margin-right:10px;">← Back</a>Ticket Details</h1>
    </div>

    <div class="ticket-grid">
      <div class="panel">
        <h3 style="margin-top:0;">Ticket Information</h3>
        <div class="info-row"><span>Ticket ID</span><span><?= str_pad($ticket['id'],3,'0',STR_PAD_LEFT) ?></span></div>
        <div class="info-row"><span>Customer</span><span><?= htmlspecialchars($ticket['customer']) ?></span></div>
        <div class="info-row"><span>Issue</span><span><?= htmlspecialchars($ticket['issue']) ?></span></div>
        <div class="info-row"><span>Priority</span><span><?= pbadge($ticket['priority']) ?></span></div>
        <div class="info-row"><span>Status</span><span><?= sbadge($ticket['status']) ?></span></div>
        <div class="info-row"><span>Assigned To</span><span><?= htmlspecialchars($ticket['assigned_to']) ?></span></div>
        <div class="info-row"><span>Created At</span><span><?= date('M d, Y g:i a', strtotime($ticket['created_at'])) ?></span></div>
      </div>

      <div class="panel">
        <h3 style="margin-top:0;">Conversation</h3>
        <div class="chat-box">
          <?php foreach ($msgs as $m): ?>
            <div class="msg <?= $m['sender_type'] ?>">
              <div class="who"><?= htmlspecialchars($m['sender_name']) ?></div>
              <div><?= htmlspecialchars($m['message']) ?></div>
              <div class="when"><?= date('M d, Y g:i a', strtotime($m['created_at'])) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($msgs)): ?>
            <p style="color:var(--muted);">No messages yet.</p>
          <?php endif; ?>
        </div>
        <form method="post">
          <div class="chat-input">
            <input type="text" name="message" placeholder="Type your reply..." required>
            <select name="new_status" style="width:140px;">
              <option value="">Keep status</option>
              <option value="Open">Open</option>
              <option value="In Progress">In Progress</option>
              <option value="Closed">Closed</option>
            </select>
            <button type="submit" name="reply" class="btn">Send</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>
</body>
</html>
