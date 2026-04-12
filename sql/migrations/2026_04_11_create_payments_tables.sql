-- PayPal/Razorpay payment tracking tables
-- Created: 2026-04-11

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider ENUM('paypal','razorpay','mock') NOT NULL DEFAULT 'paypal',
  project_id INT NULL,
  invoice_id INT NULL,
  user_id INT NULL,
  amount_paisa BIGINT NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'USD',
  status ENUM('created','approved','captured','failed','cancelled','refunded') NOT NULL DEFAULT 'created',
  provider_order_id VARCHAR(128) NOT NULL,
  provider_payment_id VARCHAR(128) NULL,
  metadata_json JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provider_order (provider, provider_order_id),
  KEY idx_payments_project (project_id),
  KEY idx_payments_invoice (invoice_id),
  KEY idx_payments_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS billing_invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_code VARCHAR(64) NOT NULL,
  project_id INT NOT NULL,
  client_name VARCHAR(255) DEFAULT NULL,
  client_contact VARCHAR(64) DEFAULT NULL,
  client_email VARCHAR(255) DEFAULT NULL,
  base_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  goods_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_rate DECIMAL(6,2) NOT NULL DEFAULT 18.00,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
  due_date DATE DEFAULT NULL,
  status ENUM('draft','issued','partially_paid','paid','overdue','cancelled') NOT NULL DEFAULT 'issued',
  notes TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_billing_invoice_code (invoice_code),
  KEY idx_billing_project (project_id),
  KEY idx_billing_status (status),
  KEY idx_billing_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_webhook_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  provider ENUM('paypal','razorpay','mock') NOT NULL DEFAULT 'paypal',
  event_id VARCHAR(128) NOT NULL,
  event_type VARCHAR(128) NOT NULL,
  payload_json JSON NOT NULL,
  processed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provider_event (provider, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
