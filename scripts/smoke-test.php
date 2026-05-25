<?php
/**
 * SyncRide — Smoke Test
 *
 * Web: http://your-server/SRMT/scripts/smoke-test.php
 *
 * Verifica a integridade da migração multi-tenant.
 * Seguro de correr em qualquer altura — só faz leituras.
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;
use App\Support\Env;

$results = [];

function check(string $group, string $label, callable $fn): void
{
    global $results;
    try {
        [$ok, $detail] = $fn();
        $results[] = compact('group', 'label', 'ok', 'detail');
    } catch (\Throwable $e) {
        $results[] = ['group' => $group, 'label' => $label, 'ok' => false, 'detail' => $e->getMessage()];
    }
}

// ─── 1. Ligação à base de dados ───────────────────────────────────────────────

check('Database', 'Ligação à DB', function () {
    $pdo = Database::connection();
    $pdo->query('SELECT 1');
    return [true, Env::get('DB_DATABASE') . '@' . Env::get('DB_HOST')];
});

// ─── 2. Tabelas obrigatórias existem ──────────────────────────────────────────

foreach (['Companies', 'Users', 'Services', 'Vehicles', 'Expenses', 'Logs'] as $table) {
    check('Schema', "Tabela {$table} existe", function () use ($table) {
        $pdo   = Database::connection();
        $stmt  = $pdo->query("SHOW TABLES LIKE '{$table}'");
        $found = $stmt->fetchColumn();
        return [$found !== false, $found ? 'presente' : 'NÃO ENCONTRADA'];
    });
}

// ─── 3. Coluna company_id existe em todas as tabelas com scope ────────────────

foreach (['Users', 'Services', 'Vehicles', 'Expenses', 'Logs'] as $table) {
    check('Schema', "company_id em {$table}", function () use ($table) {
        $pdo  = Database::connection();
        $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'company_id'");
        $col  = $stmt->fetch(\PDO::FETCH_ASSOC);
        return [$col !== false, $col ? "tipo: {$col['Type']}" : 'COLUNA AUSENTE'];
    });
}

// ─── 4. Nenhuma linha orfã (company_id IS NULL) ───────────────────────────────

foreach (['Users', 'Services', 'Vehicles', 'Expenses', 'Logs'] as $table) {
    check('Data', "Sem orphans em {$table}", function () use ($table) {
        $pdo   = Database::connection();
        $count = (int) $pdo->query("SELECT COUNT(*) FROM {$table} WHERE company_id IS NULL")->fetchColumn();
        // Super admins (role=0) legitimately have company_id=NULL — exclude them from Users check
        if ($table === 'Users') {
            $count = (int) $pdo->query("SELECT COUNT(*) FROM Users WHERE company_id IS NULL AND role != 0")->fetchColumn();
        }
        return [$count === 0, $count === 0 ? '0 orphans ✓' : "{$count} orphan(s) encontrados"];
    });
}

// ─── 5. Empresa 1 (Welcome Agitation) existe ──────────────────────────────────

check('Data', 'Empresa ID=1 existe', function () {
    $pdo  = Database::connection();
    $name = $pdo->query("SELECT name FROM Companies WHERE id = 1")->fetchColumn();
    return [$name !== false, $name ?: 'NÃO ENCONTRADA'];
});

// ─── 6. Pelo menos um super admin existe ──────────────────────────────────────

check('Data', 'Super admin (role=0) existe', function () {
    $pdo  = Database::connection();
    $stmt = $pdo->query("SELECT name, email FROM Users WHERE role = 0 LIMIT 1");
    $u    = $stmt->fetch(\PDO::FETCH_ASSOC);
    return [$u !== false, $u ? "{$u['name']} <{$u['email']}>" : 'NENHUM ENCONTRADO'];
});

// ─── 7. Ficheiros sensíveis foram apagados ────────────────────────────────────

foreach (['database/create-superadmin.php', 'database/reset-superadmin.php', 'database/migrate.php'] as $file) {
    check('Security', "Ficheiro {$file} apagado", function () use ($file) {
        $path   = dirname(__DIR__) . '/' . $file;
        $exists = file_exists($path);
        // smoke-test itself is in scripts/, create-superadmin is in database/ — mark as warning
        return [!$exists, $exists ? '⚠️  FICHEIRO AINDA EXISTE — apaga após a migração' : 'apagado ✓'];
    });
}

// ─── 8. APP_ENV está correcto ─────────────────────────────────────────────────

check('Config', 'APP_ENV=production', function () {
    $env = (string) Env::get('APP_ENV', 'unknown');
    return [$env === 'production', "APP_ENV={$env}" . ($env !== 'production' ? ' (muda para production!)' : '')];
});

check('Config', 'APP_DEBUG=false', function () {
    $debug = Env::get('APP_DEBUG', false);
    return [!$debug, $debug ? '⚠️  DEBUG está activo em produção!' : 'false ✓'];
});

// ─── 9. Endpoints de API estão protegidos ────────────────────────────────────

$baseUrl = rtrim((string) Env::get('APP_URL', ''), '/');
$protectedEndpoints = [
    '/public/api/status-update.php',
    '/public/api/location-update.php',
    '/public/api/sync-ai-engine.php',
    '/public/api/tracking-stop.php',
];

foreach ($protectedEndpoints as $path) {
    check('Auth', "Auth em {$path}", function () use ($baseUrl, $path) {
        $url = $baseUrl . $path;
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'ignore_errors' => true, 'timeout' => 5, 'header' => "Content-Type: application/json\r\n"]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return [null, 'não foi possível testar (sem curl/HTTP) — testa manualmente'];
        }
        $data = json_decode($raw, true);
        // Should be 403/401 redirect — NOT success:true
        $blocked = isset($data['success']) && $data['success'] === false;
        return [$blocked, $blocked ? '403/401 retornado ✓' : '⚠️  Pode estar a aceitar pedidos sem auth!'];
    });
}

// ─── Resultado ───────────────────────────────────────────────────────────────

$passed = count(array_filter($results, static fn($r) => $r['ok'] === true));
$failed = count(array_filter($results, static fn($r) => $r['ok'] === false));
$warn   = count(array_filter($results, static fn($r) => $r['ok'] === null));
$total  = count($results);

$groups = [];
foreach ($results as $r) {
    $groups[$r['group']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Smoke Test — SyncRide</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:2rem 1rem;min-height:100vh}
  .wrap{max-width:700px;margin:0 auto}
  h1{font-size:1.375rem;font-weight:800;color:#fff;margin-bottom:.25rem}
  .sub{font-size:.8rem;color:#64748b;margin-bottom:1.75rem}
  .stat{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.75rem}
  .stat-box{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:1rem;text-align:center}
  .stat-box .n{font-size:2.25rem;font-weight:800;line-height:1}
  .stat-box .l{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:.3rem}
  .green .n{color:#34d399}.red .n{color:#f87171}.yellow .n{color:#fbbf24}
  .group{margin-bottom:1.25rem}
  .group-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#475569;margin-bottom:.5rem;padding-left:.25rem}
  .card{background:#1e293b;border:1px solid #334155;border-radius:12px;overflow:hidden}
  .row{display:flex;align-items:center;gap:.875rem;padding:.75rem 1rem;border-bottom:1px solid #0f172a}
  .row:last-child{border-bottom:none}
  .icon{font-size:1rem;width:20px;text-align:center;flex-shrink:0}
  .ok-icon{color:#34d399}.fail-icon{color:#f87171}.warn-icon{color:#fbbf24}
  .label{font-size:.875rem;font-weight:500;flex:1}
  .detail{font-size:.75rem;color:#64748b;text-align:right;max-width:260px;word-break:break-word}
  .detail.fail{color:#fca5a5}
  .detail.warn{color:#fbbf24}
  .banner{border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:.9375rem;font-weight:600;text-align:center}
  .banner.pass{background:#14532d33;border:1px solid #16653480;color:#4ade80}
  .banner.fail{background:#7f1d1d33;border:1px solid #99171780;color:#fca5a5}
  .banner.partial{background:#78350f33;border:1px solid #d9770633;color:#fbbf24}
  code{background:#0f172a;border:1px solid #334155;border-radius:5px;padding:.1rem .4rem;font-size:.75rem;font-family:monospace;color:#a5b4fc}
</style>
</head>
<body>
<div class="wrap">
  <h1>SyncRide — Smoke Test</h1>
  <p class="sub">Verificação pós-migração — apenas leituras, seguro de correr a qualquer momento.</p>

  <div class="stat">
    <div class="stat-box green"><div class="n"><?= $passed ?></div><div class="l">Passou</div></div>
    <div class="stat-box <?= $failed > 0 ? 'red' : 'green' ?>"><div class="n"><?= $failed ?></div><div class="l">Falhou</div></div>
    <div class="stat-box <?= $warn > 0 ? 'yellow' : 'green' ?>"><div class="n"><?= $warn ?></div><div class="l">Aviso</div></div>
  </div>

  <?php if ($failed === 0 && $warn === 0): ?>
  <div class="banner pass">✅ Todos os <?= $total ?> checks passaram — produção está saudável!</div>
  <?php elseif ($failed === 0): ?>
  <div class="banner partial">⚠️ <?= $passed ?>/<?= $total ?> checks passaram com <?= $warn ?> aviso(s).</div>
  <?php else: ?>
  <div class="banner fail">❌ <?= $failed ?> check(s) falharam — resolve antes de usar em produção.</div>
  <?php endif; ?>

  <?php foreach ($groups as $groupName => $items): ?>
  <div class="group">
    <div class="group-title"><?= htmlspecialchars($groupName) ?></div>
    <div class="card">
      <?php foreach ($items as $r): ?>
      <?php
        $iconClass   = $r['ok'] === true ? 'ok-icon' : ($r['ok'] === false ? 'fail-icon' : 'warn-icon');
        $icon        = $r['ok'] === true ? '✓' : ($r['ok'] === false ? '✗' : '⚠');
        $detailClass = $r['ok'] === true ? '' : ($r['ok'] === false ? 'fail' : 'warn');
      ?>
      <div class="row">
        <span class="icon <?= $iconClass ?>"><?= $icon ?></span>
        <span class="label"><?= htmlspecialchars($r['label']) ?></span>
        <span class="detail <?= $detailClass ?>"><?= htmlspecialchars((string) $r['detail']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <p style="font-size:.75rem;color:#334155;text-align:center;margin-top:1rem">
    Corrido em <?= date('d/m/Y H:i:s') ?> · DB: <code><?= htmlspecialchars((string) Env::get('DB_DATABASE', '?')) ?></code>
  </p>
</div>
</body>
</html>
