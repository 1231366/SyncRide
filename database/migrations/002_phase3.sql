-- Migration 002: Phase 3 — TenantSettings + paxBBY passenger field
-- Safe to re-run: all statements are idempotent on MariaDB 10+

-- 1. Per-tenant key-value settings store
CREATE TABLE IF NOT EXISTS TenantSettings (
    id         INT          NOT NULL AUTO_INCREMENT,
    company_id INT          NOT NULL,
    `key`      VARCHAR(100) NOT NULL,
    `value`    TEXT         NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_tenant_settings (company_id, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Infant/baby passenger count (crianças already covered by paxCHD)
ALTER TABLE Services
    ADD COLUMN IF NOT EXISTS paxBBY TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER paxCHD;
