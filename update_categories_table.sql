-- Modify categories table to add parent_id for subcategories
ALTER TABLE categories 
ADD COLUMN parent_id INT DEFAULT NULL,
ADD CONSTRAINT fk_parent_category 
FOREIGN KEY (parent_id) REFERENCES categories(id) 
ON DELETE CASCADE;

-- Add index for better performance
CREATE INDEX idx_parent_id ON categories(parent_id);

