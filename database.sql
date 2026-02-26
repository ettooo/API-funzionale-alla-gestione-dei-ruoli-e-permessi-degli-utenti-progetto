-- ============================================
-- DATABASE SETUP: auth_system
-- Ruoli: free, premium, admin
-- ============================================

CREATE DATABASE IF NOT EXISTS auth_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE auth_system;

-- ============================================
-- TABELLA: permissions (privilegi atomici)
-- ============================================
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABELLA: roles (ruoli)
-- ============================================
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABELLA: role_permissions (associazione ruolo-permesso)
-- ============================================
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================
-- TABELLA: users (utenti)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- ============================================
-- DATI: permessi disponibili
-- ============================================
INSERT INTO permissions (name, description) VALUES
('view_dashboard',       'Accesso alla dashboard'),
('view_profile',         'Visualizzare il proprio profilo'),
('edit_profile',         'Modificare il proprio profilo'),
('view_free_content',    'Accesso ai contenuti gratuiti'),
('view_premium_content', 'Accesso ai contenuti premium'),
('download_files',       'Scaricare file e risorse'),
('manage_users',         'Gestione degli utenti (admin)'),
('manage_roles',         'Gestione dei ruoli (admin)'),
('view_reports',         'Visualizzare report e statistiche'),
('manage_content',       'Creare/modificare contenuti (admin)');

-- ============================================
-- DATI: ruoli
-- ============================================
INSERT INTO roles (name, description) VALUES
('free',    'Utente con accesso base gratuito'),
('premium', 'Utente con accesso completo premium'),
('admin',   'Amministratore del sistema');

-- ============================================
-- ASSEGNAZIONE PERMESSI AI RUOLI
-- ============================================

-- Ruolo FREE (id=1): permessi base
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'free'
  AND p.name IN ('view_dashboard', 'view_profile', 'edit_profile', 'view_free_content');

-- Ruolo PREMIUM (id=2): tutti i permessi free + extra
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'premium'
  AND p.name IN ('view_dashboard', 'view_profile', 'edit_profile',
                 'view_free_content', 'view_premium_content', 'download_files');

-- Ruolo ADMIN (id=3): tutti i permessi
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'admin';

-- ============================================
-- UTENTE ADMIN DI DEFAULT
-- password: Admin@1234
-- ============================================
INSERT INTO users (username, email, password_hash, role_id)
SELECT 'admin', 'admin@example.com',
       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       r.id
FROM roles r WHERE r.name = 'admin';

-- ============================================
-- MYSQL USERS & PRIVILEGES (esegui come root)
-- ============================================

-- Utente applicazione (accesso limitato)
CREATE USER IF NOT EXISTS 'app_user'@'localhost' IDENTIFIED BY 'AppPassword123!';
GRANT SELECT, INSERT, UPDATE ON auth_system.users TO 'app_user'@'localhost';
GRANT SELECT ON auth_system.roles TO 'app_user'@'localhost';
GRANT SELECT ON auth_system.permissions TO 'app_user'@'localhost';
GRANT SELECT ON auth_system.role_permissions TO 'app_user'@'localhost';
FLUSH PRIVILEGES;
