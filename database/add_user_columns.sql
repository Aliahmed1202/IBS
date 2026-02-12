-- Add phone and email columns to users table
ALTER TABLE `users` 
ADD COLUMN `phone` varchar(20) DEFAULT NULL AFTER `branch_id`,
ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `phone`;
