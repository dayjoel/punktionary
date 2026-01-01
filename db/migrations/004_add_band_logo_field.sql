-- Add logo field to bands table
ALTER TABLE bands ADD COLUMN logo VARCHAR(255) NULL AFTER photo_references;
