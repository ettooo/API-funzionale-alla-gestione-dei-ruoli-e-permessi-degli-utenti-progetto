CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  username TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS permissions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  description TEXT
);

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INTEGER NOT NULL,
  role_id INTEGER NOT NULL,
  PRIMARY KEY (user_id, role_id)
);

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INTEGER NOT NULL,
  permission_id INTEGER NOT NULL,
  PRIMARY KEY (role_id, permission_id)
);

INSERT OR IGNORE INTO roles (name) VALUES ('FREE'),('PREMIUM'),('ADMIN');

INSERT OR IGNORE INTO permissions (name, description) VALUES
('ACCOUNT_READ','Leggere info account'),
('ACCOUNT_UPDATE','Aggiornare profilo'),
('MARKET_VIEW_BASIC','Vedere mercati base'),
('MARKET_VIEW_ADVANCED','Vedere mercati avanzati'),
('AI_PREDICT','Analisi predittiva AI'),
('AI_STRATEGY','Simulazioni AI'),
('ALERT_BASIC','Alert base'),
('ALERT_ADVANCED','Alert avanzati'),
('DASHBOARD_BASIC','Dashboard base'),
('DASHBOARD_ADVANCED','Dashboard avanzata'),
('PORTFOLIO_BASIC','Portafoglio base'),
('PORTFOLIO_ADVANCED','Portafoglio avanzato'),
('ADMIN_PANEL','Pannello admin'),
('USER_MANAGE','Gestione utenti'),
('MODEL_MONITOR','Monitor modelli'),
('API_MANAGE','Gestione API'),
('CONTENT_MODERATE','Moderazione contenuti');

-- FREE perms
INSERT OR IGNORE INTO role_permissions(role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name='FREE' AND p.name IN (
 'ACCOUNT_READ','ACCOUNT_UPDATE',
 'MARKET_VIEW_BASIC',
 'ALERT_BASIC',
 'DASHBOARD_BASIC',
 'PORTFOLIO_BASIC'
);

-- PREMIUM perms
INSERT OR IGNORE INTO role_permissions(role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name='PREMIUM' AND p.name IN (
 'ACCOUNT_READ','ACCOUNT_UPDATE',
 'MARKET_VIEW_BASIC','MARKET_VIEW_ADVANCED',
 'AI_PREDICT','AI_STRATEGY',
 'ALERT_BASIC','ALERT_ADVANCED',
 'DASHBOARD_BASIC','DASHBOARD_ADVANCED',
 'PORTFOLIO_BASIC','PORTFOLIO_ADVANCED'
);

-- ADMIN all perms
INSERT OR IGNORE INTO role_permissions(role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name='ADMIN';

-- admin user (Admin123!)
INSERT OR IGNORE INTO users (email, username, password_hash)
VALUES (
 'admin@example.com',
 'admin',
 '$2y$10$Z7qv9TQfQk7m4yR5l2oYwOeXQpY2A2rU7u4cM1H7yYhR7oXl8d1Qm'
);

INSERT OR IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u, roles r
WHERE u.email='admin@example.com' AND r.name='ADMIN';
