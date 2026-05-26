-- Migration 003: Indexes for server-side DataTables performance
-- Safe to re-run: CREATE INDEX IF NOT EXISTS is idempotent on MariaDB 10.1+

-- Covers: today filter (WHERE company_id=? AND serviceDate=CURDATE())
--         and ORDER BY serviceDate, serviceStartTime
CREATE INDEX IF NOT EXISTS idx_svc_company_date_time
    ON Services (company_id, serviceDate, serviceStartTime);

-- Covers: requests filter (WHERE company_id=? AND status_pedido='pendente')
--         and general status + company filtering
CREATE INDEX IF NOT EXISTS idx_svc_company_status
    ON Services (company_id, status_pedido);

-- Covers: Services_Rides JOIN lookups (RideID is likely already a FK index,
--         but make sure UserID is indexed for the driver-name JOIN)
CREATE INDEX IF NOT EXISTS idx_sr_userid
    ON Services_Rides (UserID);
