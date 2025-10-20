-- Migration: Remove VNPay and MoMo payment methods
-- Date: 2025-10-21
-- Description: Remove 'vnpay' and 'momo' from payment_method ENUM, keep only COD and Bank Transfer

-- Step 1: Update existing vnpay/momo orders to bank_transfer (optional)
-- UPDATE orders SET payment_method = 'bank_transfer' WHERE payment_method IN ('vnpay', 'momo');

-- Step 2: Modify ENUM to keep only cod and bank_transfer
ALTER TABLE `orders` 
MODIFY COLUMN `payment_method` ENUM('cod', 'bank_transfer') NOT NULL;

-- Verify the change
DESCRIBE orders;

-- Check if any orders still have vnpay/momo (should return 0 rows)
SELECT COUNT(*) as other_payment_orders FROM orders WHERE payment_method NOT IN ('cod', 'bank_transfer');
