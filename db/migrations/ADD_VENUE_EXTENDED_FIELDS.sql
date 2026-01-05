-- Migration: Add extended fields to venues table
-- This adds fields for venue details that can be scraped from URLs

-- Note: Using multiple ALTER TABLE statements for compatibility
-- If columns already exist, this migration will fail safely

ALTER TABLE venues ADD COLUMN description TEXT AFTER capacity;
ALTER TABLE venues ADD COLUMN phone VARCHAR(20) AFTER description;
ALTER TABLE venues ADD COLUMN postal_code VARCHAR(10) AFTER state;
ALTER TABLE venues ADD COLUMN talent_buyer VARCHAR(255) AFTER age_restriction;
ALTER TABLE venues ADD COLUMN booking_contact VARCHAR(255) AFTER talent_buyer;
ALTER TABLE venues ADD COLUMN social_facebook VARCHAR(255) AFTER booking_contact;
ALTER TABLE venues ADD COLUMN social_instagram VARCHAR(255) AFTER social_facebook;
ALTER TABLE venues ADD COLUMN social_twitter VARCHAR(255) AFTER social_instagram;
ALTER TABLE venues ADD COLUMN social_youtube VARCHAR(255) AFTER social_twitter;
