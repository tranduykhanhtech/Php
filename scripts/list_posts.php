<?php
require_once __DIR__ . '/../config/database.php';
$stmt = $pdo->query("SELECT id, title, slug, is_published, created_at FROM posts ORDER BY id DESC");
$rows = $stmt->fetchAll();
if (empty($rows)) { echo "No posts\n"; exit; }
foreach ($rows as $r) {
    echo sprintf("%d | %s | %s | published=%d | %s\n", $r['id'], $r['title'], $r['slug'], $r['is_published'], $r['created_at']);
}
