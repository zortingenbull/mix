-- Database Schema for DTF & T-Shirt Printing System

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role TINYINT NOT NULL DEFAULT 5, -- 1=Admin, 2=Manager, 3=Production, 4=Shipping, 5=Viewer
    reset_token VARCHAR(100) NULL,
    reset_token_expiry DATETIME NULL,
    created_at DATETIME NOT NULL
);

-- Permissions table
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
);

-- User roles junction table
CREATE TABLE user_roles (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipstation_order_id VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(255) NULL,
    order_number VARCHAR(50) NOT NULL,
    order_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status TINYINT NOT NULL DEFAULT 1, -- 1=Pending, 2=Queued, 3=In Progress, 4=Printed, 5=Shipped
    assigned_to INT NULL,
    shipping_method VARCHAR(100) NULL,
    shipping_address TEXT NULL,
    order_details TEXT NULL,
    tracking_number VARCHAR(100) NULL,
    shipped_date DATETIME NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Jobs table
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status TINYINT NOT NULL DEFAULT 1, -- 1=Pending, 2=Queued, 3=In Progress, 4=Printed, 5=Shipped
    notes TEXT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Files table
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_type TINYINT NOT NULL DEFAULT 1, -- 1=Artwork, 2=Mockup, 3=Final
    file_size INT NOT NULL DEFAULT 0,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Logs table
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id INT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
('view_dashboard', 'Can view dashboard'),
('manage_orders', 'Can manage orders'),
('view_orders', 'Can view orders'),
('manage_jobs', 'Can manage print jobs'),
('view_jobs', 'Can view print jobs'),
('manage_shipping', 'Can manage shipping'),
('upload_files', 'Can upload files'),
('view_files', 'Can view files'),
('manage_users', 'Can manage users'),
('view_logs', 'Can view system logs');

-- Insert default admin user
-- Password is "admin123" (hashed with bcrypt)
INSERT INTO users (name, email, password, role, created_at) VALUES
('Admin User', 'admin@example.com', '$2y$12$ZP9GjY3/KF5uxgV1VNHX6eGPwSXI3NTrDDz4NWmgdt35MrcIf6k5W', 1, NOW());

-- Assign all permissions to admin
INSERT INTO user_roles (user_id, permission_id)
SELECT 1, id FROM permissions;