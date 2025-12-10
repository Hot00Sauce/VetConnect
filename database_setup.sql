-- VetConnect Database Setup
-- Run this SQL script to set up the database for VetConnect

-- Create database
CREATE DATABASE IF NOT EXISTS vetconnect;
USE vetconnect;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_name VARCHAR(100) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT 'assets/profile-user-svgrepo-com.svg',
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    clinic_name VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create index on email for faster lookups
CREATE INDEX idx_email ON users(email);

-- Create index on role for filtering
CREATE INDEX idx_role ON users(role);

-- Create schedules table
CREATE TABLE IF NOT EXISTS schedules (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    vet_id INT(11) DEFAULT NULL,
    pet_name VARCHAR(100) NOT NULL,
    schedule_type ENUM('clinic_visit', 'vaccination', 'medication') NOT NULL,
    schedule_date DATETIME NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vet_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create index on user_id for faster lookups
CREATE INDEX idx_user_id ON schedules(user_id);

-- Create index on schedule_date for date-based queries
CREATE INDEX idx_schedule_date ON schedules(schedule_date);

-- Create index on status for filtering
CREATE INDEX idx_status ON schedules(status);

-- Optional: Create sample users (passwords are all 'password123')
-- Uncomment the following lines if you want sample data

/*
INSERT INTO users (role, name, email, password, profile_name, profile_picture) VALUES
('Veterinarian', 'Dr. John Smith', 'john.smith@vetconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith', 'assets/profile-user-svgrepo-com.svg'),
('PetOwner', 'Jane Doe', 'jane.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Doe', 'assets/profile-user-svgrepo-com.svg'),
('Veterinarian', 'Dr. Sarah Johnson', 'sarah.johnson@vetconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sarah Johnson', 'assets/profile-user-svgrepo-com.svg'),
('PetOwner', 'Mike Wilson', 'mike.wilson@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Wilson', 'assets/profile-user-svgrepo-com.svg');
*/

-- Display success message
SELECT 'Database setup completed successfully!' AS Status;
