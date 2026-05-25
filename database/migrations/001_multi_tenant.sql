-- ============================================================
-- Migration 001 — Multi-tenancy foundation
-- Run ONCE against the production database.
-- Safe: only ADD columns / tables; no existing data is dropped.
-- ============================================================

-- 1. Companies master table
CREATE TABLE IF NOT EXISTS Companies (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(100) NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed the first (and currently only) company
INSERT INTO Companies (id, name, slug, created_at)
VALUES (1, 'Welcome Agitation', 'welcome-agitation', NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 3. Add company_id to every scoped table (nullable so the ALTER is safe
--    even when rows already exist; we backfill immediately after).

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

-- Schedule table may or may not exist in all environments
ALTER TABLE Schedule
    ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;

-- 4. Backfill: every existing row belongs to company 1
UPDATE Users    SET company_id = 1 WHERE company_id IS NULL;
UPDATE Services SET company_id = 1 WHERE company_id IS NULL;
UPDATE Vehicles SET company_id = 1 WHERE company_id IS NULL;
UPDATE Expenses SET company_id = 1 WHERE company_id IS NULL;
UPDATE Logs     SET company_id = 1 WHERE company_id IS NULL;
UPDATE Schedule SET company_id = 1 WHERE company_id IS NULL;

-- 5. Add indexes for the new column (speeds up all scoped queries)
ALTER TABLE Users    ADD INDEX IF NOT EXISTS idx_users_company    (company_id);
ALTER TABLE Services ADD INDEX IF NOT EXISTS idx_services_company (company_id);
ALTER TABLE Vehicles ADD INDEX IF NOT EXISTS idx_vehicles_company (company_id);
ALTER TABLE Expenses ADD INDEX IF NOT EXISTS idx_expenses_company (company_id);
ALTER TABLE Logs     ADD INDEX IF NOT EXISTS idx_logs_company     (company_id);

-- 6. Super-admin user (role=0, no company_id)
-- Replace 'your@email.com' and '$2y$12$...' with real values.
-- Generate a hash in PHP: echo password_hash('yourpassword', PASSWORD_BCRYPT);
-- INSERT INTO Users (email, password, role, name, company_id)
-- VALUES ('superadmin@syncride.io', '$2y$12$REPLACE_WITH_REAL_HASH', 0, 'Super Admin', NULL);

-- Done. Verify with:
-- SELECT 'Users'    t, COUNT(*) total, SUM(company_id IS NULL) missing FROM Users
-- UNION ALL
-- SELECT 'Services' t, COUNT(*) total, SUM(company_id IS NULL) missing FROM Services
-- UNION ALL
-- SELECT 'Vehicles' t, COUNT(*) total, SUM(company_id IS NULL) missing FROM Vehicles
-- UNION ALL
-- SELECT 'Expenses' t, COUNT(*) total, SUM(company_id IS NULL) missing FROM Expenses
-- UNION ALL
-- SELECT 'Logs'     t, COUNT(*) total, SUM(company_id IS NULL) missing FROM Logs;
