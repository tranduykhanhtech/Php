<?php
require_once __DIR__ . '/../config/database.php';

// Create a session and set admin credentials for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Administrator';
$_SESSION['user_role'] = 'admin';

header('Content-Type: text/plain');
echo "Admin session created. SID=" . session_id() . "\n";
