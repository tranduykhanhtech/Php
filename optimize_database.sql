-- Performance Optimization Indexes
-- Run this SQL to optimize database queries

-- Posts table indexes
ALTER TABLE `posts` 
ADD INDEX `idx_published_created` (`is_published`, `created_at` DESC),
ADD INDEX `idx_view_count` (`view_count` DESC),
ADD INDEX `idx_slug` (`slug`),
ADD INDEX `idx_author` (`author_id`);

-- Products table indexes  
ALTER TABLE `products`
ADD INDEX `idx_active_created` (`is_active`, `created_at` DESC),
ADD INDEX `idx_category` (`category_id`),
ADD INDEX `idx_price` (`price`);

-- Orders table indexes
ALTER TABLE `orders`
ADD INDEX `idx_user_created` (`user_id`, `created_at` DESC),
ADD INDEX `idx_status` (`status`);

-- Order items table indexes
ALTER TABLE `order_items`
ADD INDEX `idx_order` (`order_id`),
ADD INDEX `idx_product` (`product_id`);

-- Users table indexes
ALTER TABLE `users`
ADD INDEX `idx_email` (`email`),
ADD INDEX `idx_created` (`created_at` DESC);

-- Notifications table indexes (if exists)
-- ALTER TABLE `notifications`
-- ADD INDEX `idx_user_read` (`user_id`, `is_read`, `created_at` DESC);

SHOW INDEX FROM `posts`;
SHOW INDEX FROM `products`;
SHOW INDEX FROM `orders`;
