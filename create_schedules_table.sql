-- Create schedules table for VetConnect
USE vetconnect;

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

CREATE INDEX idx_user_id ON schedules(user_id);
CREATE INDEX idx_schedule_date ON schedules(schedule_date);
CREATE INDEX idx_status ON schedules(status);

SELECT 'Schedules table created successfully!' AS Status;
