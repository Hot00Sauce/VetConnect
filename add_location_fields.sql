-- Add location fields to users table
USE vetconnect;

ALTER TABLE users 
ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER profile_picture,
ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER address,
ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL AFTER city,
ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL AFTER latitude,
ADD COLUMN clinic_name VARCHAR(200) DEFAULT NULL AFTER longitude;

-- Create index on latitude and longitude for faster location queries
CREATE INDEX idx_location ON users(latitude, longitude);

SELECT 'Location fields added successfully!' AS Status;
