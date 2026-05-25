<?php
/**
 * SyncRide — Multi-Tenant Migration Runner
 *
 * CLI:  php database/migrate.php
 * Web:  http://your-server/SRMT/database/migrate.php
 *
 * Safe to re-run: all statements are idempotent.
 * DELETE this file after a successful migration.
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;

$isCli = PHP_SAPI === 'cli';

// ─── Run migration ───────────────────────────────────────────────────────────

$pdo    = Database::connection();
$sqlRaw = (string) file_get_contents(__DIR__ . '/migrations/001_multi_tenant.sql');

// Strip comment lines before splitting — prevents semicolons in comments
// from creating broken fragments.
$stripped = implode("\n", array_filter(
    explode("\n", $sqlRaw),
    static fn(string $line): bool => !str_starts_with(ltrim($line), '--')
));

$statements = array_filter(
    array_map('trim', explode(';', $stripped)),
    static fn(string $s): bool => $s !== ''
);

$results = [];
foreach ($statements as $sql) {
    $preview = substr($sql, 0, 120) . (strlen($sql) > 120 ? '…' : '');
    try {
        $pdo->exec($sql);
        $results[] = ['ok' => true,  'sql' => $preview, 'msg' => ''];
    } catch (\PDOException $e) {
        $code = (int) $e->getCode();
        // Ignorable: duplicate column (1060), duplicate index (1061), missing table (1146)
        $ignored = in_array($code, [1060, 1061, 1146], true);
        $results[] = [
            'ok'  => $ignored,
            'sql' => $preview,
            'msg' => $ignored ? 'already exists (safe to ignore)' : $e->getMessage(),
        ];
    }
}

// ─── Verification queries ─────────────────────────────────────────────────────

$checks = [];
foreach (['Users', 'Services', 'Vehicles', 'Expenses', 'Logs'] as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table} WHERE company_id IS NULL");
        $count = (int) $stmt->fetchColumn();
        $checks[$table] = $count;
    } catch (\Throwable) {
        $checks[$table] = -1; // table doesn't exist yet or no column
    }
}

$totalOk     = count(array_filter($results, static fn($r) => $r['ok']));
$totalFailed = count(array_filter($results, static fn($r) => !$r['ok']));
$allOrphansOk = array_sum(array_filter($checks, static fn($v) => $v >= 0)) === 0;

// ─── CLI output ───────────────────────────────────────────────────────────────

if ($isCli) {
    echo "\nSyncRide Multi-Tenant Migration\n";
    echo str_repeat('─', 50) . "\n";
    foreach ($results as $r) {
        $icon = $r['ok'] ? '✓' : '✗';
        echo "{$icon} {$r['sql']}\n";
        if ($r['msg']) echo "  → {$r['msg']}\n";
    }
    echo str_repeat('─', 50) . "\n";
    echo "OK: {$totalOk}  FAILED: {$totalFailed}\n\n";
    echo "Orphan check (should all be 0):\n";
    foreach ($checks as $table => $n) {
        $icon = ($n === 0) ? '✓' : (($n === -1) ? '?' : '✗');
        echo "  {$icon} {$table}: {$n} orphans\n";
    }
    echo "\n";
    if ($totalFailed === 0 && $allOrphansOk) {
        echo "✅  Migration complete! Delete this file now:\n";
        echo "    rm database/migrate.php\n\n";
    } else {
        echo "⚠️  Check errors above before proceeding.\n\n";
    }
    exit($totalFailed > 0 ? 1 : 0);
}

// ─── Web output ──────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SyncRide — Migration Runner</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:2rem 1rem;min-height:100vh}
  .wrap{max-width:760px;margin:0 auto}
  h1{font-size:1.375rem;font-weight:800;color:#fff;margin-bottom:.25rem}
  .sub{font-size:.8125rem;color:#64748b;margin-bottom:2rem}
  .card{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:1.5rem;margin-bottom:1.25rem}
  .card h2{font-size:.8125rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:1rem}
  .row{display:flex;align-items:flex-start;gap:.75rem;padding:.5rem 0;border-bottom:1px solid #1e293b}
  .row:last-child{border-bottom:none}
  .icon{width:20px;text-align:center;flex-shrink:0;margin-top:1px}
  .ok .icon{color:#34d399}
  .fail .icon{color:#f87171}
  .sql{font-family:monospace;font-size:.75rem;color:#94a3b8;word-break:break-all}
  .msg{font-size:.7rem;color:#f87171;margin-top:.2rem}
  .msg.warn{color:#fbbf24}
  .stat{display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin-bottom:1.25rem}
  .stat-box{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:1rem;text-align:center}
  .stat-box .n{font-size:2rem;font-weight:800;line-height:1}
  .stat-box .l{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:.25rem}
  .green .n{color:#34d399}
  .red .n{color:#f87171}
  .check-row{display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #0f172a}
  .check-row:last-child{border-bottom:none}
  .check-row .t{font-weight:600;font-size:.875rem}
  .badge{font-size:.7rem;font-weight:700;padding:.2rem .6rem;border-radius:999px}
  .badge.ok{background:#14532d55;color:#4ade80;border:1px solid #166534}
  .badge.fail{background:#7f1d1d55;color:#f87171;border:1px solid #991b1b}
  .alert{border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.25rem;font-size:.875rem;line-height:1.5}
  .alert.success{background:#14532d33;border:1px solid #16653480;color:#86efac}
  .alert.error{background:#7f1d1d33;border:1px solid #99171780;color:#fca5a5}
  .alert strong{font-weight:700}
  code{background:#0f172a;border:1px solid #334155;border-radius:6px;padding:.1rem .4rem;font-size:.8125rem;font-family:monospace}
  a.btn{display:inline-block;margin-top:1rem;padding:.625rem 1.25rem;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem}
  a.btn:hover{background:#4f46e5}
</style>
</head>
<body>
<div class="wrap">
  <h1>SyncRide — Migration Runner</h1>
  <p class="sub">Multi-Tenant Migration v2.0 — reads <code>database/migrations/001_multi_tenant.sql</code></p>

  <div class="stat">
    <div class="stat-box green"><div class="n"><?= $totalOk ?></div><div class="l">Statements OK</div></div>
    <div class="stat-box <?= $totalFailed > 0 ? 'red' : 'green' ?>"><div class="n"><?= $totalFailed ?></div><div class="l">Errors</div></div>
  </div>

  <?php if ($totalFailed === 0 && $allOrphansOk): ?>
  <div class="alert success">
    <strong>✅ Migration complete!</strong> All statements ran and all orphan checks passed.<br>
    <strong style="color:#fcd34d">Delete this file from the server now:</strong><br>
    Via SSH: <code>rm database/migrate.php</code><br>
    <a class="btn" href="create-superadmin.php">→ Next: Create Super Admin</a>
  </div>
  <?php elseif ($totalFailed > 0): ?>
  <div class="alert error">
    <strong>⚠️ Some statements failed.</strong> Review the errors below before proceeding.
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>Statement Results</h2>
    <?php foreach ($results as $r): ?>
    <div class="row <?= $r['ok'] ? 'ok' : 'fail' ?>">
      <span class="icon"><?= $r['ok'] ? '✓' : '✗' ?></span>
      <div>
        <div class="sql"><?= htmlspecialchars($r['sql']) ?></div>
        <?php if ($r['msg']): ?>
        <div class="msg <?= str_contains($r['msg'], 'safe to ignore') ? 'warn' : '' ?>"><?= htmlspecialchars($r['msg']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>Orphan Check — every value must be 0</h2>
    <?php foreach ($checks as $table => $n): ?>
    <div class="check-row">
      <span class="t"><?= $table ?></span>
      <?php if ($n === -1): ?>
        <span class="badge fail">? (table/column missing)</span>
      <?php elseif ($n === 0): ?>
        <span class="badge ok">0 orphans ✓</span>
      <?php else: ?>
        <span class="badge fail"><?= $n ?> orphans ✗</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

</div>
</body>
</html>
