<?php
require 'config.php';
require 'includes/auth.php';
$active = 'feedback';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_feedback'])) {
    $customerId = $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : null;
    $name    = trim($_POST['name']);
    $rating  = max(1, min(5, (int)$_POST['rating']));
    $comment = trim($_POST['comment']);

    $pdo->prepare("INSERT INTO feedback (customer_id, name, rating, comment) VALUES (?,?,?,?)")
        ->execute([$customerId, $name, $rating, $comment]);
    $message = 'Thank you — feedback recorded.';
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM feedback WHERE id=?")->execute([$_GET['delete']]);
    header('Location: feedback.php');
    exit;
}

// --- Filter by rating ---
$fRating = trim($_GET['rating'] ?? '');
$sql = "SELECT * FROM feedback WHERE 1=1";
$params = [];
if ($fRating !== '' && ctype_digit($fRating)) {
    $sql .= " AND rating = ?";
    $params[] = $fRating;
}
$sql .= " ORDER BY created_at DESC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedback = $stmt->fetchAll();

$avgRating   = $pdo->query("SELECT COALESCE(AVG(rating),0) FROM feedback")->fetchColumn();
$totalCount  = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$fiveStar    = $pdo->query("SELECT COUNT(*) FROM feedback WHERE rating=5")->fetchColumn();
$lowRatings  = $pdo->query("SELECT COUNT(*) FROM feedback WHERE rating<=2")->fetchColumn();

$customers = $pdo->query("SELECT id,name FROM customers ORDER BY name")->fetchAll();

function starDisplay($rating){
    $out = '<span class="stars">';
    for ($i=1;$i<=5;$i++){
        $out .= $i <= $rating ? '★' : '<span class="off">★</span>';
    }
    return $out.'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Feedback - GlowSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="page-head">
      <h1>Customer Feedback</h1>
      <button class="btn" onclick="document.getElementById('addFeedbackModal').classList.add('show')">+ Add Feedback</button>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#fef9c3;">⭐</div><div class="label">Average Rating</div></div>
        <div class="value"><?= number_format($avgRating,1) ?> / 5</div>
        <div class="delta up">▲ across all reviews</div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#eef2ff;">💬</div><div class="label">Total Reviews</div></div>
        <div class="value"><?= $totalCount ?></div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#dcfce7;">🌟</div><div class="label">5-Star Reviews</div></div>
        <div class="value"><?= $fiveStar ?></div>
      </div>
      <div class="stat-card">
        <div class="top"><div class="ico" style="background:#fee2e2;">⚠️</div><div class="label">1–2 Star Reviews</div></div>
        <div class="value"><?= $lowRatings ?></div>
        <div class="delta down">▼ may need follow-up</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>All Feedback</h3></div>
      <form method="get" class="filter-row" style="margin-bottom:14px;">
        <select name="rating" onchange="this.form.submit()">
          <option value="">All Ratings</option>
          <?php for ($i=5;$i>=1;$i--): ?>
            <option value="<?= $i ?>" <?= $fRating == $i ? 'selected' : '' ?>><?= $i ?> Star<?= $i>1?'s':'' ?></option>
          <?php endfor; ?>
        </select>
        <?php if ($fRating !== ''): ?><a class="filter-reset" href="feedback.php">Clear filter ✕</a><?php endif; ?>
      </form>

      <?php if (empty($feedback)): ?>
        <p style="text-align:center;color:var(--muted);padding:24px;">No feedback yet.</p>
      <?php else: ?>
      <div class="feedback-grid">
        <?php foreach ($feedback as $f): ?>
        <div class="feedback-card">
          <div class="head">
            <div>
              <div class="who"><?= htmlspecialchars($f['name']) ?></div>
              <?= starDisplay($f['rating']) ?>
            </div>
            <a class="icon-action danger" href="feedback.php?delete=<?= $f['id'] ?>" onclick="return confirm('Delete this feedback?')">🗑</a>
          </div>
          <p><?= htmlspecialchars($f['comment'] ?: 'No comment left.') ?></p>
          <div class="when" style="margin-top:8px;"><?= date('M d, Y', strtotime($f['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<div class="overlay" id="addFeedbackModal">
  <div class="modal">
    <h3>Add Feedback</h3>
    <form method="post">
      <div class="field">
        <label>Existing Customer (optional)</label>
        <select name="customer_id" id="fbCustomer" onchange="fillName()">
          <option value="">— Walk-in / Not linked —</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Name</label><input type="text" name="name" id="fbName" required></div>
      <div class="field">
        <label>Rating</label>
        <div class="rating-input">
          <input type="radio" name="rating" id="r5" value="5" checked><label for="r5">★</label>
          <input type="radio" name="rating" id="r4" value="4"><label for="r4">★</label>
          <input type="radio" name="rating" id="r3" value="3"><label for="r3">★</label>
          <input type="radio" name="rating" id="r2" value="2"><label for="r2">★</label>
          <input type="radio" name="rating" id="r1" value="1"><label for="r1">★</label>
        </div>
      </div>
      <div class="field"><label>Comment</label><textarea name="comment" rows="3"></textarea></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addFeedbackModal').classList.remove('show')">Cancel</button>
        <button type="submit" name="add_feedback" class="btn">Save Feedback</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillName(){
  const sel = document.getElementById('fbCustomer');
  const opt = sel.options[sel.selectedIndex];
  if (opt.dataset.name) document.getElementById('fbName').value = opt.dataset.name;
}
</script>
</body>
</html>
