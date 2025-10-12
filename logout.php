<?php
require_once 'config/database.php';

// Clear remember token(s) for this user (if any) and cookie
if (!empty($_SESSION['user_id'])) {
	try {
		$stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
		$stmt->execute([$_SESSION['user_id']]);
	} catch (Exception $e) {
		error_log('Failed to clear remember tokens: ' . $e->getMessage());
	}
}
setcookie('remember_token', '', time() - 3600, '/');

// Destroy session
session_destroy();

// Redirect to home page
redirect('index.php');
?>
