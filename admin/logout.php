<?php
require_once __DIR__ . '/../config/database.php';

// If a user is logged in, try to clear their remember tokens
if (!empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log('Failed to clear remember tokens (admin logout): ' . $e->getMessage());
    }
}

// Clear remember cookie
setcookie('remember_token', '', time() - 3600, '/');

// Destroy session
session_unset();
session_destroy();

// Redirect back to admin login (or homepage if not available)
// Use relative path so it works both on local and production
redirect('login.php');

?>
