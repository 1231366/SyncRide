<?php
/**
 * SyncRide — Migration Runner
 *
 * CLI: /Applications/XAMPP/xamppfiles/bin/php database/migrate.php
 * Web: http://localhost/SRMT/database/migrate.php
 *
 * Auto-discovers every *.sql file in database/migrations/ and runs them
 * in alphabetical order. Safe to re-run — all statements are idempotent.
 *
 * IMPORTANT: delete or move this file after running in production.
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;

$isCli = PHP_SAPI === 'cli';
$pdo   = Database::connection();

// ─── Discover migrations ──────────────────────────────────────────────────────

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

// ─── Ignored PDO error codes (all safe on MariaDB/MySQL) ─────────────────────
// 1060 duplicate column, 1061 duplicate index, 1050 table already exists

const SAFE_CODES = [1050, 1060, 1061];

// ─── Run each migration file ──────────────────────────────────────────────────

$allResults = []; // [file => [['ok','sql','msg'], ...]]

foreach ($files as $file) {
    $name    = basename($file);
    $sqlRaw  = (string) file_get_contents($file);

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
        $preview = substr($sql, 0, 140) . (strlen($sql) > 140 ? '…' : '');
        try {
            $pdo->exec($sql);
            $results[] = ['ok' => true, 'sql' => $preview, 'msg' => ''];
        } catch (\PDOException $e) {
            $code    = (int) $e->getCode();
            $ignored = in_array($code, SAFE_CODES, true);
            $results[] = [
                'ok'  => $ignored,
                'sql' => $preview,
                'msg' => $ignored ? 'already exists — safe to ignore' : $e->getMessage(),
            ];
        }
    }

    $allResults[$name] = $results;
}

// ─── Totals ───────────────────────────────────────────────────────────────────

$totalOk     = 0;
$totalFailed = 0;
foreach ($allResults as $results) {
    $totalOk     += count(array_filter($results, static fn($r) =>  $r['ok']));
    $totalFailed += count(array_filter($results, static fn($r) => !$r['ok']));
}

// ─── CLI output ───────────────────────────────────────────────────────────────

if ($isCli) {
    echo "\nSyncRide Migration Runner\n";
    echo str_repeat('─', 55) . "\n";
    foreach ($allResults as $name => $results) {
        echo "\n[ {$name} ]\n";
        foreach ($results as $r) {
            $icon = $r['ok'] ? '✓' : '✗';
            echo "  {$icon} {$r['sql']}\n";
            if ($r['msg']) echo "    → {$r['msg']}\n";
        }
    }
    echo "\n" . str_repeat('─', 55) . "\n";
    echo "OK: {$totalOk}   FAILED: {$totalFailed}\n\n";
    if ($totalFailed === 0) {
        echo "✅  All migrations complete. You can delete this file:\n";
        echo "    rm database/migrate.php\n\n";
    } else {
        echo "⚠️  Fix errors above before deploying.\n\n";
    }
    exit($totalFailed > 0 ? 1 : 0);
}

// ─── Web output ───────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SyncRide — Migration Runner</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:2rem 1rem;min-height:100vh}
  .wrap{max-width:800px;margin:0 auto}
  h1{font-size:1.375rem;font-weight:800;color:#fff;margin-bottom:.25rem}
  .sub{font-size:.8125rem;color:#64748b;margin-bottom:2rem}
  .stat{display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin-bottom:1.5rem}
  .stat-box{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:1rem;text-align:center}
  .stat-box .n{font-size:2rem;font-weight:800;line-height:1}
  .stat-box .l{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:.25rem}
  .green .n{color:#34d399} .red .n{color:#f87171}
  .alert{border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:.875rem;line-height:1.6}
  .alert.success{background:#14532d33;border:1px solid #16653480;color:#86efac}
  .alert.error{background:#7f1d1d33;border:1px solid #99171780;color:#fca5a5}
  .alert strong{font-weight:700}
  .file-card{background:#1e293b;border:1px solid #334155;border-radius:14px;margin-bottom:1.25rem;overflow:hidden}
  .file-header{padding:.75rem 1.25rem;background:#0f172a;font-size:.8125rem;font-weight:700;color:#94a3b8;letter-spacing:.04em;text-transform:uppercase}
  .row{display:flex;align-items:flex-start;gap:.75rem;padding:.5rem 1.25rem;border-bottom:1px solid #0f172a}
  .row:last-child{border-bottom:none}
  .icon{width:16px;flex-shrink:0;margin-top:2px;font-style:normal}
  .ok .icon{color:#34d399} .fail .icon{color:#f87171}
  .sql{font-family:monospace;font-size:.72rem;color:#94a3b8;word-break:break-all}
  .msg{font-size:.68rem;margin-top:.2rem;color:#fbbf24}
  .msg.err{color:#f87171}
  code{background:#0f172a;border:1px solid #334155;border-radius:6px;padding:.1rem .4rem;font-size:.8rem;font-family:monospace}
</style>
</head>
<body>
<div class="wrap">
  <h1>SyncRide — Migration Runner</h1>
  <p class="sub">Runs all <code>database/migrations/*.sql</code> files in order &mdash; safe to re-run.</p>

  <div class="stat">
    <div class="stat-box green"><div class="n"><?= $totalOk ?></div><div class="l">Statements OK</div></div>
    <div class="stat-box <?= $totalFailed > 0 ? 'red' : 'green' ?>"><div class="n"><?= $totalFailed ?></div><div class="l">Errors</div></div>
  </div>

  <?php if ($totalFailed === 0): ?>
  <div class="alert success">
    <strong>✅ All migrations complete!</strong><br>
    <strong style="color:#fcd34d">Delete this file from the server after running in production:</strong><br>
    Via SSH: <code>rm database/migrate.php</code>
  </div>
  <?php else: ?>
  <div class="alert error">
    <strong>⚠️ Some statements failed.</strong> Fix errors below before deploying.
  </div>
  <?php endif; ?>

  <?php foreach ($allResults as $name => $results): ?>
  <div class="file-card">
    <div class="file-header"><?= htmlspecialchars($name) ?></div>
    <?php foreach ($results as $r): ?>
    <div class="row <?= $r['ok'] ? 'ok' : 'fail' ?>">
      <i class="icon"><?= $r['ok'] ? '✓' : '✗' ?></i>
      <div>
        <div class="sql"><?= htmlspecialchars($r['sql']) ?></div>
        <?php if ($r['msg']): ?>
        <div class="msg <?= str_contains($r['msg'], 'safe to ignore') ? '' : 'err' ?>"><?= htmlspecialchars($r['msg']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

</div>
</body>
</html>
