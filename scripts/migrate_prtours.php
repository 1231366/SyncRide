<?php
/**
 * SyncRide — Migração PRtours (idempotente)
 *
 * CLI:  php scripts/migrate_prtours.php
 * Web:  http://your-server/SRMT/scripts/migrate_prtours.php
 *
 * Aplica as alterações ADITIVAS de `database/2026_prtours.sql` de forma
 * segura: introspeciona `information_schema` e só executa o que falta, por
 * isso pode ser corrido vezes sem conta sem erro. Não altera nem apaga nada
 * do schema existente.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Support\Database;

$cli = \PHP_SAPI === 'cli';
$nl  = $cli ? "\n" : "<br>\n";
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
}

$pdo    = Database::connection();
$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
$log    = [];

/** Executa um passo e regista o resultado. */
function step(PDO $pdo, array &$log, string $label, string $sql): void
{
    global $nl;
    try {
        $pdo->exec($sql);
        $log[] = "  ✓ {$label}";
    } catch (\Throwable $e) {
        $log[] = "  ✗ {$label} — " . $e->getMessage();
    }
    echo end($log) . $nl;
}

function columnExists(PDO $pdo, string $db, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$db, $table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $db, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$db, $table, $index]);
    return (int) $stmt->fetchColumn() > 0;
}

/** Adiciona uma coluna só se ainda não existir. */
function addColumn(PDO $pdo, array &$log, string $db, string $table, string $column, string $definition): void
{
    if (columnExists($pdo, $db, $table, $column)) {
        global $nl; echo "  · {$table}.{$column} já existe" . $nl;
        return;
    }
    step($pdo, $log, "ADD {$table}.{$column}", "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
}

/** Cria um índice só se ainda não existir. */
function addIndex(PDO $pdo, array &$log, string $db, string $table, string $index, string $columns): void
{
    if (indexExists($pdo, $db, $table, $index)) {
        global $nl; echo "  · índice {$table}.{$index} já existe" . $nl;
        return;
    }
    step($pdo, $log, "ADD INDEX {$table}.{$index}", "ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
}

echo "SyncRide — migração PRtours sobre `{$dbName}`{$nl}{$nl}";

// ── Services ────────────────────────────────────────────────────────────────
echo "Services:{$nl}";
$serviceColumns = [
    'supplier'         => "VARCHAR(40)  NULL",
    'grouping_ref'     => "VARCHAR(40)  NULL",
    'distributor_code' => "VARCHAR(20)  NULL",
    'resort'           => "VARCHAR(60)  NULL",
    'vehicle_label'    => "VARCHAR(40)  NULL",
    'leg_code'         => "VARCHAR(4)   NULL",
    'reference_no'     => "VARCHAR(40)  NULL",
    'valor_motorista'  => "DECIMAL(8,2) NULL",
    'pay_basis'        => "ENUM('company_vehicle','own_vehicle') NULL",
    'hotel_extra'      => "TINYINT(1)   NOT NULL DEFAULT 0",
    'import_notes'     => "TEXT         NULL",
    'import_batch_id'  => "INT          NULL",
];
foreach ($serviceColumns as $col => $def) {
    addColumn($pdo, $log, $dbName, 'Services', $col, $def);
}
addIndex($pdo, $log, $dbName, 'Services', 'idx_services_grouping',  '`grouping_ref`');
addIndex($pdo, $log, $dbName, 'Services', 'idx_services_reference', '`reference_no`');
addIndex($pdo, $log, $dbName, 'Services', 'idx_services_supplier',  '`supplier`');
addIndex($pdo, $log, $dbName, 'Services', 'idx_services_batch',     '`import_batch_id`');

// ── Users ───────────────────────────────────────────────────────────────────
echo "{$nl}Users:{$nl}";
addColumn($pdo, $log, $dbName, 'Users', 'driver_code',       "VARCHAR(12) NULL");
addColumn($pdo, $log, $dbName, 'Users', 'default_pay_basis', "ENUM('company_vehicle','own_vehicle') NOT NULL DEFAULT 'company_vehicle'");
addIndex($pdo, $log, $dbName, 'Users', 'idx_users_driver_code', '`driver_code`');

// ── ImportBatches ───────────────────────────────────────────────────────────
echo "{$nl}ImportBatches:{$nl}";
step($pdo, $log, 'CREATE TABLE ImportBatches', "
    CREATE TABLE IF NOT EXISTS `ImportBatches` (
      `id`            INT AUTO_INCREMENT PRIMARY KEY,
      `company_id`    INT          NULL,
      `filename`      VARCHAR(255) NULL,
      `source`        ENUM('excel','xml') NOT NULL DEFAULT 'excel',
      `rows_total`    INT NOT NULL DEFAULT 0,
      `rows_inserted` INT NOT NULL DEFAULT 0,
      `rows_skipped`  INT NOT NULL DEFAULT 0,
      `rows_failed`   INT NOT NULL DEFAULT 0,
      `created_by`    INT          NULL,
      `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_batches_company` (`company_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Services: colunas para viagens multi-paragem ────────────────────────────
echo "{$nl}Services (agregação):{$nl}";
addColumn($pdo, $log, $dbName, 'Services', 'aggregated_into',     'INT NULL');
addColumn($pdo, $log, $dbName, 'Services', 'is_aggregate_master', 'TINYINT(1) NOT NULL DEFAULT 0');
addIndex($pdo, $log, $dbName, 'Services', 'idx_services_agg_into',   '`aggregated_into`');
addIndex($pdo, $log, $dbName, 'Services', 'idx_services_agg_master', '`is_aggregate_master`');

// ── ServiceStops ────────────────────────────────────────────────────────────
echo "{$nl}ServiceStops:{$nl}";
step($pdo, $log, 'CREATE TABLE ServiceStops', "
    CREATE TABLE IF NOT EXISTS `ServiceStops` (
      `id`                INT AUTO_INCREMENT PRIMARY KEY,
      `master_service_id` INT NOT NULL,
      `source_service_id` INT NULL,
      `stop_order`        SMALLINT NOT NULL DEFAULT 0,
      `stop_type`         ENUM('pickup','dropoff') NOT NULL,
      `location`          VARCHAR(255) NOT NULL,
      `scheduled_time`    TIME NULL,
      `client_name`       VARCHAR(255) NULL,
      `pax_total`         TINYINT NULL,
      `reference_no`      VARCHAR(40) NULL,
      `notes`             TEXT NULL,
      INDEX `idx_stops_master` (`master_service_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── PricingRates ────────────────────────────────────────────────────────────
echo "{$nl}PricingRates:{$nl}";
step($pdo, $log, 'CREATE TABLE PricingRates', "
    CREATE TABLE IF NOT EXISTS `PricingRates` (
      `id`               INT AUTO_INCREMENT PRIMARY KEY,
      `company_id`       INT NULL,
      `card`             ENUM('mts','prtours_retail','driver_company_vehicle','driver_own_vehicle') NOT NULL,
      `supplier`         VARCHAR(40) NULL,
      `resort`           VARCHAR(60) NULL,
      `distributor_code` VARCHAR(20) NULL,
      `vehicle_label`    VARCHAR(40) NULL,
      `pax_tier`         TINYINT NULL,
      `price`            DECIMAL(8,2) NULL,
      `hotel_extra`      DECIMAL(8,2) NULL,
      `valid_until`      DATE NULL,
      UNIQUE KEY `uq_rate` (`company_id`,`card`,`supplier`,`resort`,`distributor_code`,`vehicle_label`,`pax_tier`),
      INDEX `idx_rates_lookup` (`card`,`resort`,`distributor_code`,`vehicle_label`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── UserInvites: metadados de condutor pré-definidos ────────────────────────
echo "{$nl}UserInvites:{$nl}";
addColumn($pdo, $log, $dbName, 'UserInvites', 'driver_meta', 'TEXT NULL');

$failed = array_filter($log, static fn(string $l): bool => str_contains($l, '✗'));
echo "{$nl}" . (empty($failed) ? '✅ Migração concluída sem erros.' : '⚠️  Concluída com ' . count($failed) . ' erro(s) — ver acima.') . $nl;
