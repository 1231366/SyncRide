-- Migration 001: Multi-tenancy foundation
-- Safe to re-run: ADD COLUMN IF NOT EXISTS is idempotent on MariaDB 10+
-- The runner ignores errors 1060 (duplicate col), 1061 (duplicate index), 1146 (no table)

-- 1. Companies master table
CREATE TABLE IF NOT EXISTS Companies (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(100) NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed the first company
INSERT INTO Companies (id, name, slug, created_at)
VALUES (1, 'Welcome Agitation', 'welcome-agitation', NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 3. Add company_id to every scoped table
ALTER TABLE Users
    ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;

ALTER TABLE Services
    ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER ID;

ALTER TABLE Vehicles
    ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;

ALTER TABLE Expenses
    ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;

ALTER TABLE Logs
    ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER logID;

-- 4. Backfill existing rows to company 1
UPDATE Users    SET company_id = 1 WHERE company_id IS NULL;
UPDATE Services SET company_id = 1 WHERE company_id IS NULL;
UPDATE Vehicles SET company_id = 1 WHERE company_id IS NULL;
UPDATE Expenses SET company_id = 1 WHERE company_id IS NULL;
UPDATE Logs     SET company_id = 1 WHERE company_id IS NULL;

-- 5. Indexes for company_id (speeds up all scoped queries)
ALTER TABLE Users    ADD INDEX IF NOT EXISTS idx_users_company    (company_id);
ALTER TABLE Services ADD INDEX IF NOT EXISTS idx_services_company (company_id);
ALTER TABLE Vehicles ADD INDEX IF NOT EXISTS idx_vehicles_company (company_id);
ALTER TABLE Expenses ADD INDEX IF NOT EXISTS idx_expenses_company (company_id);
ALTER TABLE Logs     ADD INDEX IF NOT EXISTS idx_logs_company     (company_id);
