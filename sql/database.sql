-- Full database schema for TheFinal_Thefinal2
-- Compatibility-first schema based on active PHP usage (legacy + new flows).

CREATE DATABASE IF NOT EXISTS `Ripal-Design` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `Ripal-Design`;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL UNIQUE,
  full_name VARCHAR(255) DEFAULT NULL,
  first_name VARCHAR(120) DEFAULT NULL,
  last_name VARCHAR(120) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  token_reset CHAR(64) DEFAULT NULL,
  reset_token_expires DATETIME DEFAULT NULL,
  role ENUM('client','worker','employee','admin') NOT NULL DEFAULT 'client',
  status ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
  address VARCHAR(255) DEFAULT NULL,
  city VARCHAR(120) DEFAULT NULL,
  state VARCHAR(120) DEFAULT NULL,
  zip VARCHAR(20) DEFAULT NULL,
  joined_date DATE DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_email (email),
  INDEX idx_users_reset_token (token_reset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Legacy fallback table used in login_register.php when PDO users-table flow is unavailable.
CREATE TABLE IF NOT EXISTS signup (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(255) DEFAULT NULL,
  last_name VARCHAR(255) DEFAULT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone_number VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  token_type VARCHAR(50) NOT NULL DEFAULT 'session',
  expires_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_auth_tokens_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  status ENUM('planning','ongoing','paused','completed','on-hold','cancelled') DEFAULT 'ongoing',
  budget DECIMAL(15,2) DEFAULT NULL,
  progress INT DEFAULT 0,
  due DATE DEFAULT NULL,
  location TEXT DEFAULT NULL,
  map_link TEXT DEFAULT NULL,
  site_location VARCHAR(255) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  owner_name VARCHAR(255) DEFAULT NULL,
  owner_contact VARCHAR(50) DEFAULT NULL,
  owner_email VARCHAR(255) DEFAULT NULL,
  worker_name VARCHAR(255) DEFAULT NULL,
  project_type VARCHAR(100) DEFAULT NULL,
  client_id INT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_projects_status (status),
  INDEX idx_projects_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  worker_id INT NOT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_project_assignments_project (project_id),
  INDEX idx_project_assignments_worker (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_workers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  worker_name VARCHAR(255) DEFAULT NULL,
  worker_role VARCHAR(100) DEFAULT NULL,
  worker_contact VARCHAR(50) DEFAULT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  INDEX idx_project_workers_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_milestones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  target_date DATE DEFAULT NULL,
  status ENUM('active','completed','pending') DEFAULT 'pending',
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  INDEX idx_project_milestones_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  filename VARCHAR(255) DEFAULT NULL,
  type VARCHAR(50) DEFAULT NULL,
  size VARCHAR(20) DEFAULT NULL,
  file_path VARCHAR(500) DEFAULT NULL,
  storage_path VARCHAR(1024) DEFAULT NULL,
  version INT DEFAULT 1,
  uploaded_by VARCHAR(255) DEFAULT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active','archived') DEFAULT 'active',
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  INDEX idx_project_files_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_activity (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user VARCHAR(255) NOT NULL,
  action VARCHAR(100) NOT NULL,
  item VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  INDEX idx_project_activity_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_drawings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  version VARCHAR(20) DEFAULT NULL,
  status ENUM('Approved','Under Review','Revision Needed') DEFAULT 'Under Review',
  file_path VARCHAR(500) DEFAULT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  INDEX idx_project_drawings_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_goods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  sku VARCHAR(100) DEFAULT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  unit VARCHAR(50) DEFAULT 'pcs',
  quantity INT DEFAULT 1,
  unit_price DECIMAL(12,2) DEFAULT 0,
  total_price DECIMAL(12,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  INDEX idx_project_goods_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS review_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT DEFAULT NULL,
  submitted_by INT DEFAULT NULL,
  subject VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  urgency ENUM('critical','high','normal','low') DEFAULT 'normal',
  status ENUM('pending','approved','changes_requested','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_review_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS worker_ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  worker_id INT NOT NULL,
  rated_by VARCHAR(255) NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_worker_ratings_worker (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leave_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  leave_type VARCHAR(100) DEFAULT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason TEXT DEFAULT NULL,
  status ENUM('pending','approved','rejected','on_leave') DEFAULT 'pending',
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_by INT DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_leave_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(120) DEFAULT NULL,
  last_name VARCHAR(120) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  message TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  price DECIMAL(12,2) DEFAULT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS public_page_content (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page_slug VARCHAR(100) NOT NULL,
  section_key VARCHAR(150) NOT NULL,
  content_value MEDIUMTEXT DEFAULT NULL,
  content_format ENUM('plain','html') NOT NULL DEFAULT 'plain',
  updated_by INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_public_page_content (page_slug, section_key),
  INDEX idx_public_page_slug (page_slug),
  INDEX idx_public_page_updated_by (updated_by),
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(100) DEFAULT NULL,
  title VARCHAR(255) DEFAULT NULL,
  body TEXT DEFAULT NULL,
  actor_user_id INT DEFAULT NULL,
  project_id INT DEFAULT NULL,
  entity_type VARCHAR(50) DEFAULT NULL,
  entity_id INT DEFAULT NULL,
  action_key VARCHAR(100) DEFAULT NULL,
  deep_link VARCHAR(500) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  is_read TINYINT(1) DEFAULT 0,
  read_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  INDEX idx_notifications_user (user_id),
  INDEX idx_notifications_user_read_created (user_id, is_read, created_at),
  INDEX idx_notifications_project_created (project_id, created_at),
  INDEX idx_notifications_action_created (action_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- RBAC (Role-Based Access Control) Extension
-- Supports broad user role + detailed designation-level permissions
-- ============================================================

CREATE TABLE IF NOT EXISTS role_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_group_id INT NOT NULL,
  code VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_group_id) REFERENCES role_groups(id) ON DELETE CASCADE,
  INDEX idx_roles_group (role_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(120) NOT NULL UNIQUE,
  resource VARCHAR(80) NOT NULL,
  action VARCHAR(80) NOT NULL,
  description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  is_allowed TINYINT(1) DEFAULT 1,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
  UNIQUE KEY uq_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  is_primary TINYINT(1) DEFAULT 0,
  assigned_by INT DEFAULT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_user_role (user_id, role_id),
  INDEX idx_user_roles_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dashboard_modules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  route VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_dashboard_access (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  module_id INT NOT NULL,
  can_view TINYINT(1) DEFAULT 0,
  can_create TINYINT(1) DEFAULT 0,
  can_update TINYINT(1) DEFAULT 0,
  can_delete TINYINT(1) DEFAULT 0,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (module_id) REFERENCES dashboard_modules(id) ON DELETE CASCADE,
  UNIQUE KEY uq_role_module_access (role_id, module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_access_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  rank_value INT NOT NULL,
  description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_project_access (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  project_access_level_id INT NOT NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (project_access_level_id) REFERENCES project_access_levels(id) ON DELETE CASCADE,
  UNIQUE KEY uq_role_project_access (role_id, project_access_level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_user_access (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  project_access_level_id INT NOT NULL,
  granted_by INT DEFAULT NULL,
  granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (project_access_level_id) REFERENCES project_access_levels(id) ON DELETE CASCADE,
  FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_project_user_access (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------
-- RBAC Seed: Role groups
-- ----------------------
INSERT IGNORE INTO role_groups (code, name) VALUES
('employee', 'Employee'),
('worker', 'Worker');

-- ----------------------
-- RBAC Seed: Roles
-- ----------------------
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_admin_manager', 'Admin / Manager', 'Full employee-side administration'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_valuer', 'Valuer', 'Valuation and financial estimation support'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_visitor_boy', 'Visitor Boy', 'Basic office/visit support'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_estimate', 'Estimate', 'Project estimation specialist'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_cad_operator', 'CAD Operator', 'Drawings and CAD file operations'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_interior', 'Interior', 'Interior design workflow contributor'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_architect', 'Architect', 'Architectural lead and approvals'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_3d_designer', '3D Designer', '3D visualization contributor'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_hr', 'HR', 'Leave and people operations'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_office_boy', 'Office Boy', 'Limited office support'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_accountant', 'Accountant', 'Invoice and cost management'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_site_engineer', 'Site Engineer', 'On-site progress and execution'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'emp_team_coordinator', 'Team Coordinator', 'Cross-team planning and coordination'
FROM role_groups rg WHERE rg.code = 'employee';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_contractor', 'Contractor', 'Own/assigned project execution'
FROM role_groups rg WHERE rg.code = 'worker';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_carpenter', 'Carpenter', 'Carpentry scope worker'
FROM role_groups rg WHERE rg.code = 'worker';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_electrician', 'Electrician', 'Electrical scope worker'
FROM role_groups rg WHERE rg.code = 'worker';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_material_supplier', 'Material Supplier', 'Material supply workflow'
FROM role_groups rg WHERE rg.code = 'worker';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_plumber', 'Plumber', 'Plumbing scope worker'
FROM role_groups rg WHERE rg.code = 'worker';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_glass', 'Glass', 'Glass and facade worker'
FROM role_groups rg WHERE rg.code = 'worker';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_painter', 'Painter', 'Painting scope worker'
FROM role_groups rg WHERE rg.code = 'worker';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_artist', 'Artist', 'Decorative and creative site work'
FROM role_groups rg WHERE rg.code = 'worker';

INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT rg.id, 'wrk_sofa_maker', 'Sofa Maker', 'Furniture/sofa production worker'
FROM role_groups rg WHERE rg.code = 'worker';

-- ----------------------
-- RBAC Seed: Permissions
-- ----------------------
INSERT IGNORE INTO permissions (code, resource, action, description) VALUES
('dashboard.view', 'dashboard', 'view', 'View dashboard pages'),
('dashboard.admin', 'dashboard', 'admin', 'Access admin dashboard modules'),
('project.view', 'project', 'view', 'View project details'),
('project.create', 'project', 'create', 'Create new projects'),
('project.update', 'project', 'update', 'Update project details/progress'),
('project.assign_worker', 'project', 'assign_worker', 'Assign workers to project'),
('project.files.manage', 'project_files', 'manage', 'Upload/delete project files and drawings'),
('project.review.approve', 'review_requests', 'approve', 'Approve/reject review requests'),
('finance.goods.manage', 'project_goods', 'manage', 'Manage goods and invoice entries'),
('users.manage', 'users', 'manage', 'Manage users and role assignments'),
('leave.manage', 'leave_requests', 'manage', 'Manage leave requests');

-- ----------------------
-- RBAC Seed: Access levels
-- ----------------------
INSERT IGNORE INTO project_access_levels (code, name, rank_value, description) VALUES
('none', 'No Access', 0, 'No visibility to project details'),
('own', 'Own Data Only', 1, 'Only own records/tasks'),
('assigned', 'Assigned Projects', 2, 'Projects explicitly assigned to user'),
('department', 'Department Projects', 3, 'Projects for user department/team'),
('all', 'All Projects', 4, 'Full project visibility');

-- ----------------------
-- RBAC Seed: Dashboard modules
-- ----------------------
INSERT IGNORE INTO dashboard_modules (code, name, route) VALUES
('admin_dashboard', 'Admin Dashboard', '/admin/dashboard.php'),
('project_management', 'Project Management', '/admin/project_management.php'),
('user_management', 'User Management', '/admin/user_management.php'),
('content_management', 'Content Manager', '/admin/content_management.php'),
('leave_management', 'Leave Management', '/admin/leave_management.php'),
('review_requests', 'Review Requests', '/dashboard/review_requests.php'),
('project_details', 'Project Details', '/dashboard/project_details.php'),
('worker_dashboard', 'Worker Dashboard', '/worker/dashboard.php'),
('worker_project_details', 'Worker Project Details', '/worker/project_details.php'),
('goods_manage', 'Goods Manage', '/dashboard/goods_manage.php'),
('goods_invoice', 'Goods Invoice', '/dashboard/goods_invoice.php');

-- ----------------------
-- Vendors & Scoring: Tables and RBAC seeds
-- ----------------------
-- Permission for assigning vendors to projects
INSERT IGNORE INTO permissions (code, resource, action, description) VALUES
('project.assign_vendor', 'project', 'assign_vendor', 'Assign vendors to project');

-- Dashboard module for vendor management
INSERT IGNORE INTO dashboard_modules (code, name, route) VALUES
('vendor_management', 'Vendor Management', '/admin/vendors.php');

-- Vendor categories (e.g., wallpaper, tiles)
CREATE TABLE IF NOT EXISTS vendor_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vendors master table
CREATE TABLE IF NOT EXISTS vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(100) DEFAULT NULL,
  contact_name VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  category_id INT DEFAULT NULL,
  address TEXT DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES vendor_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Project <-> Vendor assignments (similar to project_assignments for workers)
CREATE TABLE IF NOT EXISTS project_vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  vendor_id INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  assigned_by VARCHAR(255) DEFAULT NULL,
  CONSTRAINT fk_pv_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_pv_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Worker metric events (one row per project evaluation event)
CREATE TABLE IF NOT EXISTS worker_metric_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  worker_id INT NOT NULL,
  project_id INT DEFAULT NULL,
  rated_by VARCHAR(255) DEFAULT NULL,
  charges_efficiency DECIMAL(5,2) NOT NULL DEFAULT 0,
  work_quality DECIMAL(5,2) NOT NULL DEFAULT 0,
  experience DECIMAL(5,2) NOT NULL DEFAULT 0,
  speed_timing DECIMAL(5,2) NOT NULL DEFAULT 0,
  reliability DECIMAL(5,2) NOT NULL DEFAULT 0,
  rework_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  communication DECIMAL(5,2) NOT NULL DEFAULT 0,
  client_feedback DECIMAL(5,2) NOT NULL DEFAULT 0,
  flexibility DECIMAL(5,2) NOT NULL DEFAULT 0,
  safety DECIMAL(5,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wme_worker FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wme_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vendor metric events (one row per purchase order / batch)
CREATE TABLE IF NOT EXISTS vendor_metric_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  project_id INT DEFAULT NULL,
  purchase_order_ref VARCHAR(255) DEFAULT NULL,
  priced_by VARCHAR(255) DEFAULT NULL,
  pricing DECIMAL(5,2) NOT NULL DEFAULT 0,
  product_quality DECIMAL(5,2) NOT NULL DEFAULT 0,
  consistency DECIMAL(5,2) NOT NULL DEFAULT 0,
  delivery_reliability DECIMAL(5,2) NOT NULL DEFAULT 0,
  stock_availability DECIMAL(5,2) NOT NULL DEFAULT 0,
  variety DECIMAL(5,2) NOT NULL DEFAULT 0,
  warranty_replacement DECIMAL(5,2) NOT NULL DEFAULT 0,
  communication DECIMAL(5,2) NOT NULL DEFAULT 0,
  credit_terms DECIMAL(5,2) NOT NULL DEFAULT 0,
  logistics DECIMAL(5,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vme_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
  CONSTRAINT fk_vme_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggregated score stores (optional, can be recalculated on demand)
CREATE TABLE IF NOT EXISTS worker_scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  worker_id INT NOT NULL UNIQUE,
  final_score DECIMAL(5,4) DEFAULT NULL,
  risk DECIMAL(5,4) DEFAULT NULL,
  confidence DECIMAL(5,4) DEFAULT NULL,
  decision_score DECIMAL(5,4) DEFAULT NULL,
  last_computed_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_ws_worker FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vendor_scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL UNIQUE,
  final_score DECIMAL(5,4) DEFAULT NULL,
  risk DECIMAL(5,4) DEFAULT NULL,
  confidence DECIMAL(5,4) DEFAULT NULL,
  decision_score DECIMAL(5,4) DEFAULT NULL,
  last_computed_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_vs_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ----------------------
-- RBAC Seed: Role -> Project access mapping (default)
-- ----------------------
INSERT IGNORE INTO role_project_access (role_id, project_access_level_id)
SELECT r.id, pal.id
FROM roles r
JOIN project_access_levels pal ON pal.code = 'all'
WHERE r.code IN ('emp_admin_manager','emp_accountant');

INSERT IGNORE INTO role_project_access (role_id, project_access_level_id)
SELECT r.id, pal.id
FROM roles r
JOIN project_access_levels pal ON pal.code = 'department'
WHERE r.code IN ('emp_architect','emp_team_coordinator','emp_hr');

INSERT IGNORE INTO role_project_access (role_id, project_access_level_id)
SELECT r.id, pal.id
FROM roles r
JOIN project_access_levels pal ON pal.code = 'assigned'
WHERE r.code IN (
  'emp_valuer','emp_estimate','emp_cad_operator','emp_interior','emp_3d_designer','emp_site_engineer',
  'wrk_contractor','wrk_carpenter','wrk_electrician','wrk_material_supplier','wrk_plumber','wrk_glass','wrk_painter','wrk_artist','wrk_sofa_maker'
);

INSERT IGNORE INTO role_project_access (role_id, project_access_level_id)
SELECT r.id, pal.id
FROM roles r
JOIN project_access_levels pal ON pal.code = 'own'
WHERE r.code IN ('emp_visitor_boy','emp_office_boy');
