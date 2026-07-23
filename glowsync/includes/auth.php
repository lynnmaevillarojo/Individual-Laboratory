<?php
/**
 * GlowSync - Auth guard
 * Include this AFTER config.php on every page that requires a signed-in user.
 * Redirects to login.php if there is no active session.
 */
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/**
 * Current signed-in user's role ('Admin' or 'Staff').
 * Falls back to 'Admin' for legacy sessions created before roles existed.
 */
function currentRole(): string {
    return $_SESSION['user_role'] ?? 'Admin';
}

function isAdmin(): bool {
    return currentRole() === 'Admin';
}

/**
 * Call at the top of any Admin-only page. Sends non-admins back
 * to the dashboard with nothing rendered.
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: dashboard.php?denied=1');
        exit;
    }
}
