<?php
require_once __DIR__ . '/../config/database.php';
$stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
$stmt->execute(['admin']);
$user = $stmt->fetch();
if (!$user) { echo "No admin user\n"; exit; }
$hash = $user['password'];
$pw = 'password';
var_export([ 'hash' => $hash, 'verify' => password_verify($pw, $hash) ]);
echo PHP_EOL;
