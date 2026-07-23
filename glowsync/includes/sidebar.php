<?php
// Expects $active to be set by the including page (e.g. 'dashboard', 'sales', ...)
$active = $active ?? '';

$nav = [
    'dashboard' => ['dashboard.php', '📊', 'Dashboard'],
    'sales'     => ['sales.php', '🛒', 'Sales'],
    'inventory' => ['inventory.php', '📦', 'Inventory'],
    'customers' => ['customers.php', '👥', 'Customers'],
    'products'  => ['products.php', '🧴', 'Products'],
    'feedback'  => ['feedback.php', '⭐', 'Feedback'],
    'support'   => ['support.php', '💬', 'Support'],
    'reports'   => ['reports.php', '📈', 'Reports'],
];
?>
<aside class="sidebar">
  <div class="brand">
    <div class="logo-circle"><img src="glowcode.png" alt="logo" style="width:26px;height:26px;"></div>
    <h1>GlowSync</h1>
  </div>
  <nav>
    <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
      <a href="<?= $href ?>" class="<?= $active === $key ? 'active' : '' ?>">
        <span><?= $icon ?></span><span class="label"><?= $label ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <a href="logout.php" class="logout">
    <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-logout"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg></span><span class="label">Logout</span>
  </a>
</aside>
