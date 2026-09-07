-- =============================================================================
-- RepoScope — único archivo SQL
-- =============================================================================
-- install.php aplica este archivo. Schema::ensure lo usa si la base está vacía.
-- MySQL 8+ / MariaDB · utf8mb4
-- Usuario inicial: admin@local / reposcope  (cambiarlo en Perfil)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(64) NOT NULL,
  setting_value TEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(64) NOT NULL,
  name VARCHAR(120) NOT NULL,
  color VARCHAR(16) NOT NULL DEFAULT '#3DDCFF',
  sort_order INT NOT NULL DEFAULT 0,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repositories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  github_id BIGINT UNSIGNED NOT NULL,
  full_name VARCHAR(200) NOT NULL,
  name VARCHAR(120) NOT NULL,
  owner VARCHAR(120) NOT NULL,
  description TEXT NULL,
  html_url VARCHAR(400) NOT NULL,
  homepage VARCHAR(400) NULL,
  language VARCHAR(64) NULL,
  visibility VARCHAR(16) NOT NULL DEFAULT 'public',
  is_private TINYINT(1) NOT NULL DEFAULT 0,
  is_fork TINYINT(1) NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  is_template TINYINT(1) NOT NULL DEFAULT 0,
  default_branch VARCHAR(80) NULL,
  stars INT UNSIGNED NOT NULL DEFAULT 0,
  forks_count INT UNSIGNED NOT NULL DEFAULT 0,
  open_issues INT UNSIGNED NOT NULL DEFAULT 0,
  size_kb INT UNSIGNED NOT NULL DEFAULT 0,
  topics TEXT NULL,
  root_files TEXT NULL,
  license_key VARCHAR(80) NULL,
  license_name VARCHAR(160) NULL,
  has_readme TINYINT(1) NOT NULL DEFAULT 0,
  has_license_file TINYINT(1) NOT NULL DEFAULT 0,
  pushed_at DATETIME NULL,
  github_created_at DATETIME NULL,
  github_updated_at DATETIME NULL,
  last_synced_at DATETIME NULL,
  root_scanned_at DATETIME NULL,
  category_id BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  is_ignored TINYINT(1) NOT NULL DEFAULT 0,
  ignored_findings TEXT NULL,
  health_score TINYINT UNSIGNED NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_repositories_github_id (github_id),
  UNIQUE KEY uq_repositories_full_name (full_name),
  KEY idx_repositories_language (language),
  KEY idx_repositories_category (category_id),
  KEY idx_repositories_health (health_score),
  KEY idx_repositories_pushed (pushed_at),
  CONSTRAINT fk_repositories_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS findings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  repository_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(24) NOT NULL,
  severity VARCHAR(16) NOT NULL DEFAULT 'warn',
  message VARCHAR(255) NOT NULL,
  detail VARCHAR(190) NOT NULL DEFAULT '',
  is_open TINYINT(1) NOT NULL DEFAULT 1,
  detected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at DATETIME NULL,
  UNIQUE KEY uq_findings_repo_type_detail (repository_id, type, detail),
  KEY idx_findings_open_type (is_open, type),
  CONSTRAINT fk_findings_repository FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'running',
  source VARCHAR(190) NULL,
  repos_fetched INT UNSIGNED NOT NULL DEFAULT 0,
  repos_upserted INT UNSIGNED NOT NULL DEFAULT 0,
  repos_scanned INT UNSIGNED NOT NULL DEFAULT 0,
  findings_open INT UNSIGNED NOT NULL DEFAULT 0,
  error_message TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO users (id, name, email, password_hash, is_active) VALUES
(1, 'Admin', 'admin@local', '$2y$10$FKwEJl3C18ID9SW2wdx2y.p4Pntu8pwhCA/vvS5y7XzkL3LsZTmB2', 1);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('github_token', ''),
('github_owner', ''),
('github_orgs', ''),
('include_forks', '1'),
('include_archived', '1'),
('findings_skip_forks', '1'),
('findings_skip_archived', '1'),
('stale_days', '180'),
('required_files', '.gitignore'),
('theme', 'dark');

INSERT IGNORE INTO categories (id, slug, name, color, sort_order, is_system) VALUES
(1, 'personal', 'Personal', '#3DDCFF', 1, 1),
(2, 'trabajo', 'Trabajo', '#7C9CFF', 2, 1),
(3, 'cliente', 'Cliente', '#F0B429', 3, 1),
(4, 'experimento', 'Experimento', '#C084FC', 4, 1),
(5, 'aprendizaje', 'Aprendizaje', '#3DDC97', 5, 1),
(6, 'fork', 'Fork', '#8B9BB4', 6, 1),
(7, 'archivo', 'Archivo', '#64748B', 7, 1);
