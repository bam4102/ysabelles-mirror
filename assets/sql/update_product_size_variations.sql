-- First, drop the product_scan_codes table if it exists
DROP TABLE IF EXISTS product_scan_codes;

-- Create a backup of the existing product_size_variations table
CREATE TABLE IF NOT EXISTS product_size_variations_backup AS 
SELECT * FROM product_size_variations;

-- Drop the existing product_size_variations table
DROP TABLE IF EXISTS product_size_variations;

-- Create the new product_size_variations table with the simplified structure
CREATE TABLE product_size_variations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES product(productID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data from the backup (if possible)
-- This assumes we can map old records to the new structure
-- You might need to adjust this based on your actual data
INSERT INTO product_size_variations (group_id, product_id)
SELECT 
    product_id AS group_id,  -- Use the original product_id as the group_id
    p.productID              -- Get actual product IDs from the product table
FROM 
    product_size_variations_backup psv
JOIN 
    product p ON p.sizeProduct = psv.size AND p.priceProduct = psv.price
WHERE 
    p.productID IN (SELECT product_id FROM product_size_variations_backup);

-- Cleanup (optional - remove this if you want to keep the backup)
-- DROP TABLE product_size_variations_backup;
