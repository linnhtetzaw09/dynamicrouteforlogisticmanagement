-- Logistics Web Application Database Schema
-- This script creates all necessary tables for the logistics management system

-- Create the database
CREATE DATABASE IF NOT EXISTS lhz_db;
USE lhz_db;

-- Table: site
-- Stores information about company sites
CREATE TABLE IF NOT EXISTS site (
    site_id INT PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(100) NOT NULL UNIQUE,
    address_line_1 VARCHAR(255) NOT NULL,
    address_city VARCHAR(100) NOT NULL,
    address_postcode VARCHAR(20) NOT NULL,
    contact_phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: vehicle_type
-- Stores specifications for different vehicle models
CREATE TABLE IF NOT EXISTS vehicle_type (
    vehicle_type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    max_weight_kg INT NOT NULL, 
    max_volume_m3 INT NOT NULL, 
    description TEXT
);


-- Table: vehicle
-- Stores information about individual vehicles
CREATE TABLE IF NOT EXISTS vehicle (
    vehicle_id INT PRIMARY KEY AUTO_INCREMENT,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    vehicle_type_id INT NOT NULL,
    home_site_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_type(vehicle_type_id),
    FOREIGN KEY (home_site_id) REFERENCES site(site_id)
);

-- Table: employee
-- Stores staff and admin user accounts
CREATE TABLE IF NOT EXISTS employee (
    employee_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    role ENUM('Admin', 'Staff') NOT NULL,
    site_id INT,
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES site(site_id)
);

-- Table: job
-- Stores details of logistics tasks
CREATE TABLE IF NOT EXISTS job (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    goods_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    total_weight_kg INT NOT NULL,  
    total_volume_m3 INT NOT NULL, 
    is_hazardous TINYINT(1) NOT NULL,
    start_date DATE NOT NULL,
    deadline DATE NOT NULL,
    status ENUM('Outstanding', 'Completed') NOT NULL DEFAULT 'Outstanding',
    origin_site_id INT NOT NULL,
    destination_site_id INT NOT NULL,
    created_employee_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    assigned_vehicle_id INT, 
    FOREIGN KEY (origin_site_id) REFERENCES site(site_id),
    FOREIGN KEY (destination_site_id) REFERENCES site(site_id),
    FOREIGN KEY (created_employee_id) REFERENCES employee(employee_id),
    FOREIGN KEY (assigned_vehicle_id) REFERENCES vehicle(vehicle_id) 
);



-- Create indexes for better query performance
CREATE INDEX idx_vehicle_type ON vehicle(vehicle_type_id);
CREATE INDEX idx_vehicle_site ON vehicle(home_site_id);
CREATE INDEX idx_employee_site ON employee(site_id);
CREATE INDEX idx_job_origin ON job(origin_site_id);
CREATE INDEX idx_job_destination ON job(destination_site_id);
CREATE INDEX idx_job_status ON job(status);
CREATE INDEX idx_job_created_by ON job(created_employee_id);

-- Insert sample data for testing

-- Insert sample sites
INSERT INTO site (site_name, address_line_1, address_city, address_postcode, contact_phone) VALUES
('Liverpool Hub', '123 Dock Street', 'Liverpool', 'L1 1AA', '0151-123-4567'),
('Manchester Distribution Center', '456 Industrial Road', 'Manchester', 'M1 1AA', '0161-234-5678'),
('Birmingham Logistics', '789 Factory Lane', 'Birmingham', 'B1 1AA', '0121-345-6789'),
('London Central', '321 Thames Street', 'London', 'E1 1AA', '020-456-7890'),
('Leeds Warehouse', '654 Market Road', 'Leeds', 'LS1 1AA', '0113-567-8901');

-- Insert sample vehicle types
INSERT INTO vehicle_type (type_name, max_weight_kg, max_volume_m3, description) VALUES
('LWB Transit', 2500.00, 10.50, 'Light commercial vehicle for urban deliveries'),
('Luton', 3500.00, 15.00, 'Medium-sized van with box body'),
('HGV', 20000.00, 60.00, 'Heavy goods vehicle for long-distance transport'),
('Articulated Lorry', 25000.00, 85.00, 'Large articulated vehicle for bulk transport'),
('Refrigerated Truck', 18000.00, 50.00, 'Vehicle equipped with refrigeration for perishable goods');

-- Insert sample vehicles
INSERT INTO vehicle (registration_number, vehicle_type_id, home_site_id, notes) VALUES
('LV21ABC', 1, 1, 'Recently serviced'),
('LV21DEF', 2, 1, 'New vehicle'),
('LV21GHI', 3, 2, 'Refrigerated unit'),
('LV21JKL', 4, 3, 'Flatbed trailer'),
('LV21MNO', 1, 4, 'Standard configuration');

-- Insert sample admin user (password: admin123)
INSERT INTO employee (first_name, last_name, email, password_hash, phone_number, role, site_id, is_approved) VALUES
('Admin', 'User', 'admin@gmail.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/TVm', '0151-999-9999', 'Admin', 1, 1);

-- Insert sample jobs
INSERT INTO job (goods_name, quantity, total_weight_kg, total_volume_m3, is_hazardous, start_date, deadline, status, origin_site_id, destination_site_id, created_employee_id, description, assigned_vehicle_id) VALUES
('Electronics Package', 50, 1500, 8, 0, '2025-12-13', '2025-12-20', 'Outstanding', 1, 2, 1, 'Fragile electronics requiring careful handling', 1),
('Chemical Supplies', 100, 5000, 20, 1, '2025-12-13', '2025-12-18', 'Completed', 2, 3, 1, 'Hazardous chemicals - special handling required', 2),
('Office Furniture', 30, 3000, 25, 0, '2025-12-10', '2025-12-15', 'Completed', 3, 4, 1, 'Desks, chairs, and filing cabinets', 3),
('Textiles', 200, 2000, 12, 0, '2025-12-12', '2025-12-25', 'Outstanding', 4, 1, 1, 'Bulk fabric shipment', 4),
('Perishable Food Items', 150, 4000, 18, 0, '2025-12-14', '2025-12-19', 'Outstanding', 5, 2, 1, 'Refrigerated transport required for fresh produce', 5);
