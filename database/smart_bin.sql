-- Smart Bin Waste Management System Database
-- Complete schema with all required tables

CREATE DATABASE IF NOT EXISTS smart_bin_db;
USE smart_bin_db;

-- System Settings Table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    is_configurable BOOLEAN DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
);

-- Locations Table
CREATE TABLE locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    address VARCHAR(500),
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
);

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'EMPLOYEE') DEFAULT 'EMPLOYEE',
    contact_number VARCHAR(20),
    location_id INT,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    password_changed_at TIMESTAMP NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_location (location_id)
);

-- Bins Table
CREATE TABLE bins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_code VARCHAR(50) UNIQUE NOT NULL,
    device_id VARCHAR(100) UNIQUE NOT NULL,
    device_token VARCHAR(255) UNIQUE NOT NULL,
    location_id INT NOT NULL,
    current_fill_percentage INT DEFAULT 0,
    current_state ENUM('NORMAL', 'NEARLY_FULL', 'FULL') DEFAULT 'NORMAL',
    previous_state ENUM('NORMAL', 'NEARLY_FULL', 'FULL') DEFAULT 'NORMAL',
    status ENUM('ACTIVE', 'INACTIVE', 'MAINTENANCE') DEFAULT 'ACTIVE',
    last_reading_at TIMESTAMP NULL,
    last_seen_at TIMESTAMP NULL,
    last_collection_at TIMESTAMP NULL,
    trash_distance INT,
    hand_distance INT,
    lid_status ENUM('OPEN', 'CLOSED', 'LOCKED') DEFAULT 'CLOSED',
    device_status ENUM('ONLINE', 'OFFLINE') DEFAULT 'OFFLINE',
    notification_sent BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id),
    INDEX idx_bin_code (bin_code),
    INDEX idx_location (location_id),
    INDEX idx_state (current_state),
    INDEX idx_status (status),
    INDEX idx_device_status (device_status)
);

-- Bin Readings Table
CREATE TABLE bin_readings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_id INT NOT NULL,
    fill_percentage INT NOT NULL,
    trash_distance INT,
    hand_distance INT,
    bin_state ENUM('NORMAL', 'NEARLY_FULL', 'FULL') NOT NULL,
    lid_state ENUM('OPEN', 'CLOSED', 'LOCKED') DEFAULT 'CLOSED',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE,
    INDEX idx_bin_id (bin_id),
    INDEX idx_created_at (created_at),
    INDEX idx_bin_created (bin_id, created_at)
);

-- Notifications Table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_id INT NOT NULL,
    location_id INT NOT NULL,
    notification_type ENUM('NEARLY_FULL', 'FULL', 'SYSTEM') DEFAULT 'SYSTEM',
    fill_level INT,
    bin_code VARCHAR(50),
    location_name VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('PENDING', 'SENT', 'FAILED', 'READ') DEFAULT 'PENDING',
    is_read BOOLEAN DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id),
    INDEX idx_status (status),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Notification Deliveries Table (SMS, Email, Website)
CREATE TABLE notification_deliveries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id INT NOT NULL,
    recipient_id INT,
    recipient_phone VARCHAR(20),
    recipient_email VARCHAR(255),
    channel ENUM('WEBSITE', 'SMS', 'EMAIL') NOT NULL,
    message TEXT,
    status ENUM('PENDING', 'SENT', 'FAILED') DEFAULT 'PENDING',
    provider VARCHAR(100),
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    INDEX idx_channel (channel),
    INDEX idx_status (status),
    INDEX idx_recipient (recipient_id),
    INDEX idx_created_at (created_at)
);

-- Collection Logs Table
CREATE TABLE collection_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_id INT NOT NULL,
    location_id INT NOT NULL,
    collector_id INT NOT NULL,
    previous_fill_level INT,
    current_fill_level INT DEFAULT 0,
    collection_notes TEXT,
    status ENUM('PENDING', 'COMPLETED', 'CANCELLED') DEFAULT 'PENDING',
    collected_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (collector_id) REFERENCES users(id),
    INDEX idx_bin_id (bin_id),
    INDEX idx_status (status),
    INDEX idx_collector_id (collector_id),
    INDEX idx_collected_at (collected_at)
);

-- Maintenance Logs Table
CREATE TABLE maintenance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_id INT NOT NULL,
    reported_by INT NOT NULL,
    maintenance_type VARCHAR(100),
    issue_description TEXT NOT NULL,
    status ENUM('PENDING', 'IN_PROGRESS', 'RESOLVED') DEFAULT 'PENDING',
    resolution_notes TEXT,
    maintenance_date TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    INDEX idx_bin_id (bin_id),
    INDEX idx_status (status),
    INDEX idx_reported_by (reported_by)
);

-- Device Logs Table
CREATE TABLE device_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_id INT NOT NULL,
    event_type ENUM('ONLINE', 'OFFLINE', 'ERROR', 'RECONNECT') DEFAULT 'ERROR',
    event_message TEXT,
    device_id VARCHAR(100),
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE,
    INDEX idx_bin_id (bin_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
);

-- Audit Logs Table
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(100),
    target_id INT,
    description TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Location User Assignment Table
CREATE TABLE location_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_location_user (location_id, user_id),
    INDEX idx_user_id (user_id)
);

-- Insert Default System Settings
INSERT INTO system_settings (setting_key, setting_value, description, is_configurable) VALUES
('YELLOW_THRESHOLD', '50', 'Fill percentage threshold for NEARLY_FULL state', 1),
('RED_THRESHOLD', '80', 'Fill percentage threshold for FULL state', 1),
('DEVICE_OFFLINE_TIMEOUT', '300', 'Seconds before device is marked OFFLINE', 1),
('SENSOR_EMPTY_DISTANCE', '333', 'Distance in cm when bin is empty', 1),
('SENSOR_FULL_DISTANCE', '66', 'Distance in cm when bin is full', 1),
('ESP32_POLLING_INTERVAL', '5', 'Seconds between ESP32 sensor readings', 1),
('SMS_ENABLED', '1', 'Enable SMS notifications', 1),
('EMAIL_ENABLED', '1', 'Enable Email notifications', 1),
('NOTIFICATION_ENABLED', '1', 'Enable all notifications', 1),
('SMTP_HOST', 'smtp.gmail.com', 'SMTP server hostname', 1),
('SMTP_PORT', '587', 'SMTP server port', 1),
('SMTP_USERNAME', '', 'SMTP username (set in environment)', 1),
('SMTP_PASSWORD', '', 'SMTP password (set in environment)', 1),
('SMTP_FROM_EMAIL', 'smart-bin@example.com', 'Sender email address', 1),
('SMTP_FROM_NAME', 'Smart Bin System', 'Sender name', 1),
('SMS_PROVIDER', 'twilio', 'SMS provider (twilio, vonage, etc)', 1),
('SMS_API_KEY', '', 'SMS API key (set in environment)', 1),
('SMS_SENDER', 'SmartBin', 'SMS sender ID', 1),
('APP_NAME', 'Smart Bin Waste Management System', 'Application name', 0),
('APP_VERSION', '1.0.0', 'Application version', 0);

-- Insert Initial Admin Account
INSERT INTO users (employee_id, username, email, password, role, contact_number, status) VALUES
('ADM-001', 'admin', 'admin@bin.com', '$2y$10$QIvzH4A2tJ9.7b8K6f3m1e5nX2pL8dY9qR3sT1uW4vZ6xC7bA2fH6', 'ADMIN', '+1-555-0001', 'ACTIVE');

-- Create indexes for performance
CREATE INDEX idx_notifications_unread ON notifications(is_read, created_at);
CREATE INDEX idx_bin_readings_latest ON bin_readings(bin_id, created_at DESC);
CREATE INDEX idx_collection_pending ON collection_logs(status, created_at);
CREATE INDEX idx_maintenance_pending ON maintenance_logs(status, created_at);
