-- Phase 5b: project-owned admin feature tables. Run after 001_custom_tables.sql.
CREATE TABLE IF NOT EXISTS phpretro_admin_action_log (
 id INT NOT NULL AUTO_INCREMENT, admin_id INT NOT NULL, action_type VARCHAR(100) NOT NULL,
 target_type VARCHAR(100) NOT NULL, target_id INT NULL, details TEXT NOT NULL,
 ip VARCHAR(45) NOT NULL, created_at INT NOT NULL, PRIMARY KEY (id),
 INDEX idx_admin_created (admin_id, created_at), INDEX idx_target (target_type, target_id),
 CONSTRAINT fk_phpretro_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS phpretro_staff_sessions (
 id INT NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, session_hash CHAR(64) NOT NULL,
 ip VARCHAR(45) NOT NULL, created_at INT NOT NULL, last_activity INT NOT NULL, revoked_at INT NULL,
 PRIMARY KEY (id), UNIQUE INDEX idx_session_hash (session_hash), INDEX idx_user_active (user_id, revoked_at, last_activity),
 CONSTRAINT fk_phpretro_staff_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS phpretro_staff_totp (
 user_id INT NOT NULL, secret_base32 VARCHAR(128) NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 0,
 created_at INT NOT NULL, verified_at INT NULL, PRIMARY KEY (user_id),
 CONSTRAINT fk_phpretro_staff_totp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS phpretro_user_reports (
 id INT NOT NULL AUTO_INCREMENT, reporter_id INT NOT NULL, reported_user_id INT NOT NULL,
 reason VARCHAR(100) NOT NULL, evidence TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'open',
 assigned_to INT NULL, action_notes TEXT NOT NULL, created_at INT NOT NULL, resolved_by INT NULL, resolved_at INT NULL,
 PRIMARY KEY (id), INDEX idx_reporter_created (reporter_id, created_at), INDEX idx_reported_created (reported_user_id, created_at),
 INDEX idx_status_created (status, created_at), CONSTRAINT fk_phpretro_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_phpretro_reported FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_phpretro_report_assignee FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_phpretro_report_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS phpretro_site_settings (
 setting_key VARCHAR(100) NOT NULL, setting_value VARCHAR(255) NOT NULL, updated_by INT NULL, updated_at INT NOT NULL,
 PRIMARY KEY (setting_key), CONSTRAINT fk_phpretro_site_setting_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO phpretro_site_settings (setting_key, setting_value, updated_by, updated_at) VALUES ('maintenance_mode','0',NULL,UNIX_TIMESTAMP()),('staff_2fa_rank','5',NULL,UNIX_TIMESTAMP());