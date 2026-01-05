-- Migration: Add resource_type column to resources table
-- This column categorizes resources into types like Shirts, Stickers, Vinyl, etc.

ALTER TABLE resources
ADD COLUMN resource_type VARCHAR(100) DEFAULT 'Other' AFTER description;

-- Add index for filtering by type
CREATE INDEX idx_resource_type ON resources(resource_type);
