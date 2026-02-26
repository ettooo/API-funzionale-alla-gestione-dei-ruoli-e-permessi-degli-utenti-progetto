-- ============================================
-- MIGRATION: JWT & Refresh Token support
-- Da eseguire DOPO database.sql
-- ============================================

USE auth_system;

-- ============================================
-- TABELLA: refresh_tokens
-- Ogni riga è un refresh token valido (rotating)
-- ============================================
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    token_hash  VARCHAR(64)  NOT NULL UNIQUE,   -- SHA-256 del token
    expires_at  DATETIME     NOT NULL,
    revoked     TINYINT(1)   DEFAULT 0,
    user_agent  VARCHAR(255) DEFAULT NULL,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id    (user_id)
);

-- ============================================
-- Aggiungi permesso manage_permissions
-- (per CRUD permessi degli utenti via API)
-- ============================================
INSERT IGNORE INTO permissions (name, description) VALUES
('manage_permissions', 'CRUD sui permessi degli utenti via API REST');

-- Assegna manage_permissions all'admin
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'admin' AND p.name = 'manage_permissions';

-- ============================================
-- Privilegi DB aggiuntivi per app_user
-- ============================================
GRANT SELECT, INSERT, UPDATE, DELETE ON auth_system.refresh_tokens TO 'app_user'@'localhost';
GRANT INSERT, DELETE ON auth_system.role_permissions TO 'app_user'@'localhost';
FLUSH PRIVILEGES;
