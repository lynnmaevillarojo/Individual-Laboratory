<?php
$userName = $_SESSION['user_name'] ?? 'Admin';
$userRole = $_SESSION['user_role'] ?? 'Admin';
$initial  = strtoupper(substr($userName, 0, 1));
?>
<div class="topbar">
  <a href="support.php" class="icon-btn" title="Notifications">🔔</a>
  <div class="admin">
    <div class="ico" style="width:34px;height:34px;border-radius:50%;background:#f3e8fb;display:flex;align-items:center;justify-content:center;color:var(--primary-dark);font-weight:800;">
      <?= htmlspecialchars($initial) ?>
    </div>
    <span><?= htmlspecialchars($userName) ?></span>
    <span class="role-pill role-<?= strtolower($userRole) ?>"><?= htmlspecialchars($userRole) ?></span>
  </div>
</div>
