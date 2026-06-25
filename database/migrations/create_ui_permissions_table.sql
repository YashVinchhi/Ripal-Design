CREATE TABLE IF NOT EXISTS ui_permissions (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  page_key      VARCHAR(100) NOT NULL,
  component_key VARCHAR(100) NOT NULL,
  role          VARCHAR(50)  NOT NULL,
  can_view      TINYINT(1)   DEFAULT 0,
  can_edit      TINYINT(1)   DEFAULT 0,
  can_delete    TINYINT(1)   DEFAULT 0,
  UNIQUE KEY uq_perm (page_key, component_key, role)
);
