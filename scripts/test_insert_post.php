<?php
require_once __DIR__ . '/../config/database.php';

try {
    $title = 'Test post from CLI';
    $slug = 'test-post-cli-' . time();
    $excerpt = 'Excerpt for test post';
    $content = 'This is a test post created by CLI to validate DB insert.';
    $featured_image = '';
    $author_id = 1; // assuming admin user exists with id 1
    $is_published = 1;

    $stmt = $pdo->prepare("INSERT INTO posts (title, slug, excerpt, content, featured_image, author_id, is_published, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$title, $slug, $excerpt, $content, $featured_image, $author_id, $is_published]);

    echo "Inserted post ID: " . $pdo->lastInsertId() . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
