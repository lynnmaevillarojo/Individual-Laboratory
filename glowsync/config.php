<?php
/**
 * GlowSync - Database configuration
 * Update the 4 constants below to match your local MySQL setup.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'glowsync');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Start session for every page that includes this file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
