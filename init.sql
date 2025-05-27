-- init.sql

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS zerodha_admin_db;

-- Use the newly created database
USE zerodha_admin_db;

-- 1. users table: For admin login
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert a default admin user (you should change the password in production)
-- Password 'password' is hashed using bcrypt in Laravel, so store a hashed password here.
-- For a real application, you'd typically run a seeder or migration from Laravel.
-- Example: INSERT INTO users (name, email, password) VALUES ('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- 'password'
-- For now, we'll just create the table.

-- 2. admin_settings table: System-level configuration
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buy_logic ENUM('fixed_percent', 'offset_ltp') NOT NULL DEFAULT 'fixed_percent',
    buy_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    stoploss_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    auto_sell_cutoff_time TIME NOT NULL DEFAULT '15:20:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO admin_settings (buy_logic, buy_percentage, stoploss_percentage, auto_sell_cutoff_time)
VALUES ('fixed_percent', 1.00, 0.50, '15:20:00');


-- 3. zerodha_accounts table: Trading credentials for up to 5 accounts
CREATE TABLE IF NOT EXISTS zerodha_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    api_secret VARCHAR(255) NOT NULL,
    access_token TEXT, -- Access token can be long and changes
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. instruments table: Maps symbol to Zerodha token
CREATE TABLE IF NOT EXISTS instruments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrument_token VARCHAR(255) NOT NULL UNIQUE,
    exchange VARCHAR(50) NOT NULL,
    tradingsymbol VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    last_price DECIMAL(10, 2),
    expiry DATE,
    strike DECIMAL(10, 2),
    instrument_type VARCHAR(50),
    segment VARCHAR(50),
    lot_size INT,
    tick_size DECIMAL(5, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. watchlist_symbols table: Admin-managed list of symbols to track
CREATE TABLE IF NOT EXISTS watchlist_symbols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(255) NOT NULL UNIQUE,
    instrument_token VARCHAR(255), -- Optional: Link to instruments table
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instrument_token) REFERENCES instruments(instrument_token) ON DELETE SET NULL ON UPDATE CASCADE
);

-- 6. orders table: Uploaded orders with status
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zerodha_account_id INT NOT NULL,
    symbol VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    product ENUM('MIS', 'CNC') NOT NULL,
    target_percentage DECIMAL(5, 2) NOT NULL,
    status ENUM('pending', 'placed', 'bought', 'sold', 'cancelled', 'failed') NOT NULL DEFAULT 'pending',
    entry_price DECIMAL(10, 2), -- Price at which the order was placed/bought
    exit_price DECIMAL(10, 2),  -- Price at which the order was sold
    order_id VARCHAR(255),      -- Zerodha order ID
    transaction_type ENUM('BUY', 'SELL'), -- To track if it's a buy or sell order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zerodha_account_id) REFERENCES zerodha_accounts(id) ON DELETE CASCADE
);

-- Add a unique constraint to prevent duplicate pending orders for the same symbol+account
ALTER TABLE orders
ADD CONSTRAINT uc_pending_order UNIQUE (zerodha_account_id, symbol) WHERE status = 'pending';


-- 7. order_logs table: History of all order actions (success/failure)
CREATE TABLE IF NOT EXISTS order_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT, -- Link to the orders table
    log_type ENUM('info', 'warning', 'error', 'success') NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- 8. positions table: Zerodha open positions (synced periodically)
CREATE TABLE IF NOT EXISTS positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zerodha_account_id INT NOT NULL,
    instrument_token VARCHAR(255) NOT NULL,
    tradingsymbol VARCHAR(255) NOT NULL,
    exchange VARCHAR(50) NOT NULL,
    product VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    average_price DECIMAL(10, 2) NOT NULL,
    last_price DECIMAL(10, 2),
    pnl DECIMAL(10, 2),
    day_or_overall ENUM('day', 'overall') NOT NULL, -- To distinguish between day and overall positions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zerodha_account_id) REFERENCES zerodha_accounts(id) ON DELETE CASCADE
);

-- 9. cron_logs table: Background job results (for ticker, monitor_orders, sync_positions)
CREATE TABLE IF NOT EXISTS cron_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    status ENUM('success', 'failed', 'running') NOT NULL,
    message TEXT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP
);

-- data_sync_status file
CREATE TABLE IF NOT EXISTS data_sync_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_type VARCHAR(255) NOT NULL UNIQUE,
    last_synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);