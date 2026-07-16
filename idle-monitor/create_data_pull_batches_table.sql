-- ========================================
-- BATCH DATA PULL - CREATE TABLE
-- ========================================
-- File: create_data_pull_batches_table.sql
-- Date: 2026-07-16
-- Description: Manual SQL for creating data_pull_batches table
-- ========================================

-- Check if table exists
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'WARNING: Table data_pull_batches already exists!'
        ELSE 'OK: Ready to create table'
    END AS status
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'data_pull_batches';

-- Create table data_pull_batches
CREATE TABLE IF NOT EXISTS `data_pull_batches` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(50) NOT NULL COMMENT 'Group batches dari 1 request',
    `batch_number` INT NOT NULL COMMENT 'Batch sequence number (1, 2, 3, ...)',
    `date` DATE NOT NULL COMMENT 'Tanggal yang ditarik',
    `time_start` TIME NOT NULL COMMENT 'Waktu mulai batch (00:00:00)',
    `time_end` TIME NOT NULL COMMENT 'Waktu akhir batch (02:59:59)',
    `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending' COMMENT 'Status batch',
    `total_records` INT NOT NULL DEFAULT 0 COMMENT 'Jumlah records yang ditarik',
    `error_message` TEXT NULL COMMENT 'Error message jika failed',
    `started_at` TIMESTAMP NULL COMMENT 'Waktu mulai proses',
    `completed_at` TIMESTAMP NULL COMMENT 'Waktu selesai proses',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`date`),
    INDEX `idx_session_batch` (`session_id`, `batch_number`),
    INDEX `idx_session_status` (`session_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracking batch progress untuk data pull';

-- Insert into migrations table (Laravel tracking)
INSERT INTO `migrations` (`migration`, `batch`) 
VALUES ('2026_07_16_100000_create_data_pull_batches_table', 
        (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT * FROM migrations) AS m))
ON DUPLICATE KEY UPDATE batch = batch;

-- Verify table created
SELECT 
    TABLE_NAME,
    ENGINE,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'data_pull_batches';

-- Show table structure
DESCRIBE `data_pull_batches`;

-- ========================================
-- MIGRATION COMPLETED
-- ========================================
-- Table: data_pull_batches ✅
-- Indexes: 5 indexes created ✅
-- Laravel migrations record: Updated ✅
-- ========================================
