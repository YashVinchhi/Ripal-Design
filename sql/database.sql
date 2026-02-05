-- database.sql - placeholder
-- Create your database schema here

CREATE DATABASE IF NOT EXISTS ripal_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ripal_db;

-- Example users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
