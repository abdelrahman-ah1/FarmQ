-- Wave 4: agronomist invites and access roles
ALTER TABLE farm_access
    MODIFY access_role ENUM('viewer', 'editor', 'consultant') NOT NULL DEFAULT 'viewer';

UPDATE farm_access SET access_role = 'editor' WHERE access_role = 'consultant';

CREATE TABLE IF NOT EXISTS farm_invites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id BIGINT UNSIGNED NOT NULL,
    invite_code VARCHAR(32) NOT NULL,
    access_role ENUM('viewer', 'editor') NOT NULL DEFAULT 'viewer',
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    used_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_farm_invites_code (invite_code),
    KEY idx_farm_invites_farm (farm_id),
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id),
    FOREIGN KEY (used_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
