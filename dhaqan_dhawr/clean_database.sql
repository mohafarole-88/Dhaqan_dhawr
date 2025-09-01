-- ============================================
-- DATABASE CLEANUP SCRIPT FOR DHAQAN DHOWR
-- ============================================
-- This script will clean all tables except keep admin users
-- BACKUP YOUR DATABASE BEFORE RUNNING THIS SCRIPT!

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Clear all data in correct order (child tables first)
DELETE FROM reviews;
DELETE FROM product_images;
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM sellers;
DELETE FROM contact_messages;
DELETE FROM conversations;
DELETE FROM messages;
DELETE FROM moderation_logs;
DELETE FROM cart_items;

-- 2. Clear categories (no foreign key dependencies)
DELETE FROM categories;

-- 3. Clear all users EXCEPT admin users
DELETE FROM users WHERE role != 'admin';

-- 4. If you want to keep only ONE specific admin user, use this instead:
-- Replace 'admin@example.com' with your actual admin email
-- DELETE FROM users WHERE role != 'admin' OR email != 'admin@example.com';

-- 5. Reset AUTO_INCREMENT counters to start fresh
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;
ALTER TABLE orders AUTO_INCREMENT = 1;
ALTER TABLE order_items AUTO_INCREMENT = 1;
ALTER TABLE sellers AUTO_INCREMENT = 1;
ALTER TABLE product_images AUTO_INCREMENT = 1;
ALTER TABLE reviews AUTO_INCREMENT = 1;
ALTER TABLE contact_messages AUTO_INCREMENT = 1;
ALTER TABLE conversations AUTO_INCREMENT = 1;
ALTER TABLE messages AUTO_INCREMENT = 1;
ALTER TABLE moderation_logs AUTO_INCREMENT = 1;
ALTER TABLE cart_items AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify remaining data
SELECT 'REMAINING USERS:' as info;
SELECT id, name, email, role, created_at FROM users;

SELECT 'REMAINING PRODUCTS:' as info;
SELECT COUNT(*) as product_count FROM products;

SELECT 'REMAINING ORDERS:' as info;
SELECT COUNT(*) as order_count FROM orders;

SELECT 'REMAINING SELLERS:' as info;
SELECT COUNT(*) as seller_count FROM sellers;

SELECT 'REMAINING CATEGORIES:' as info;
SELECT COUNT(*) as category_count FROM categories;

-- ============================================
-- EXECUTION INSTRUCTIONS:
-- ============================================
-- 1. BACKUP your database first: mysqldump -u root -p dhaqan_dhowr > backup.sql
-- 2. Open phpMyAdmin or MySQL command line
-- 3. Select dhaqan_dhowr database
-- 4. Run this script (it handles foreign key constraints automatically)
-- 5. Verify the results with the SELECT statements at the end
-- 
-- This script will clean ALL tables visible in your sidebar except users table
-- It preserves admin users and resets all counters to start fresh
-- ============================================
