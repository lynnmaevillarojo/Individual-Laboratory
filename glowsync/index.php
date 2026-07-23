<?php
require 'config.php';
header('Location: ' . (!empty($_SESSION['user_id']) ? 'dashboard.php' : 'landing.php'));
exit;
