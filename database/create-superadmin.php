<?php
/**
 * SyncRide — Super Admin Creator
 *
 * Web: http://your-server/SRMT/database/create-superadmin.php
 * CLI: php database/create-superadmin.php name email password
 *
 * Creates a role=0 user with company_id=NULL (sees all tenants).
 * DELETE this file immediately after use.
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;

$isCli = PHP_SAPI === 'cli';
$pdo   = Database::connection();
$error = '';
$done  = false;
$created = [];

// ─── CLI mode ─────────────────────────────────────────────────────────────────

if ($isCli) {
    $name     = $argv[1] ?? null;
    $email    = $argv[2] ?? null;
    $password = $argv[3] ?? null;

    if (!$name || !$email || !$password) {
        echo "Usage: php database/create-superadmin.php \"Full Name\" email@example.com password\n";
        exit(1);
    }

    $exists = $pdo->prepare('SELECT 1 FROM Users WHERE email = :e');
    $exists->execute(['e' => $email]);
    if ($exists->fetchColumn()) {
        echo "Error: email {$email} already exists.\n";
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO Users (name, email, password, role, phone, company_id) VALUES (:n, :e, :p, 0, 0, NULL)')
        ->execute(['n' => $name, 'e' => $email, 'p' => $hash]);

    $id = $pdo->lastInsertId();
    echo "✓ Super admin created!\n";
    echo "  ID:    {$id}\n";
    echo "  Name:  {$name}\n";
    echo "  Email: {$email}\n\n";
    echo "Delete this file now:\n  rm database/create-superadmin.php\n";
    exit(0);
}

// ─── Web mode ─────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || strlen($password) < 8) {
        $error = 'Todos os campos são obrigatórios. Password mínimo 8 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } else {
        $exists = $pdo->prepare('SELECT 1 FROM Users WHERE email = :e');
        $exists->execute(['e' => $email]);
        if ($exists->fetchColumn()) {
            $error = "Já existe um utilizador com o email {$email}.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO Users (name, email, password, role, phone, company_id) VALUES (:n, :e, :p, 0, 0, NULL)')
                ->execute(['n' => $name, 'e' => $email, 'p' => $hash]);
            $id = (int) $pdo->lastInsertId();
            $done    = true;
            $created = ['id' => $id, 'name' => $name, 'email' => $email];
        }
    }
}

// Check existing super admins for info display
$existing = $pdo->query("SELECT id, name, email FROM Users WHERE role = 0 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Criar Super Admin — SyncRide</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:flex-start;justify-content:center;min-height:100vh;padding:2rem 1rem}
  .wrap{width:100%;max-width:460px}
  h1{font-size:1.25rem;font-weight:800;color:#fff;margin-bottom:.25rem}
  .sub{font-size:.8125rem;color:#64748b;margin-bottom:1.75rem}
  .card{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:1.5rem;margin-bottom:1rem}
  label{display:block;font-size:.8rem;font-weight:600;color:#94a3b8;margin-bottom:.375rem;text-transform:uppercase;letter-spacing:.04em}
  input{width:100%;background:#0f172a;border:1px solid #334155;border-radius:10px;padding:.625rem .875rem;color:#fff;font-size:.9375rem;outline:none;margin-bottom:1rem}
  input:focus{border-color:#6366f1;box-shadow:0 0 0 2px #6366f133}
  button{width:100%;padding:.75rem;border-radius:10px;background:#6366f1;color:#fff;font-weight:700;font-size:.9375rem;border:none;cursor:pointer;transition:background .15s}
  button:hover{background:#4f46e5}
  .error{background:#7f1d1d33;border:1px solid #f8717133;color:#fca5a5;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.875rem}
  .success{background:#14532d33;border:1px solid #16653480;color:#86efac;border-radius:12px;padding:1.25rem;font-size:.875rem;line-height:1.7}
  .success strong{color:#4ade80}
  .warn{background:#78350f33;border:1px solid #d9770633;color:#fbbf24;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.8125rem}
  .existing{margin-top:1.25rem}
  .existing h2{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#475569;margin-bottom:.75rem}
  .u-row{display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #0f172a}
  .u-row:last-child{border-bottom:none}
  .u-name{font-size:.875rem;font-weight:600}
  .u-email{font-size:.75rem;color:#64748b}
  .badge-sa{font-size:.65rem;background:#6366f122;border:1px solid #6366f144;color:#a5b4fc;padding:.1rem .5rem;border-radius:999px;font-weight:700}
  code{background:#0f172a;border:1px solid #334155;border-radius:6px;padding:.1rem .4rem;font-size:.8rem;font-family:monospace;color:#a5b4fc}
  a.btn{display:inline-block;margin-top:.75rem;padding:.6rem 1.2rem;background:#059669;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;font-size:.875rem}
  a.btn:hover{background:#047857}
</style>
</head>
<body>
<div class="wrap">
  <h1>Criar Super Admin</h1>
  <p class="sub">Cria um utilizador <code>role=0</code> sem empresa — vê todos os tenants.</p>

  <?php if ($done): ?>
  <div class="success">
    <strong>✅ Super admin criado com sucesso!</strong><br><br>
    ID: <code><?= $created['id'] ?></code><br>
    Nome: <strong><?= htmlspecialchars($created['name']) ?></strong><br>
    Email: <strong><?= htmlspecialchars($created['email']) ?></strong><br><br>
    Podes fazer login em <a href="/SRMT/public/" style="color:#4ade80">/SRMT/public/</a><br><br>
    <strong style="color:#fcd34d">⚠️ Apaga este ficheiro agora:</strong><br>
    <code>database/create-superadmin.php</code>
    <br>
    <a class="btn" href="/SRMT/scripts/smoke-test.php">→ Ir para Smoke Test</a>
  </div>
  <?php else: ?>

  <div class="warn">⚠️ <strong>Apaga este ficheiro do servidor após usar.</strong></div>

  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="POST">
      <label>Nome Completo</label>
      <input type="text" name="name" placeholder="Super Admin" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
      <label>Email</label>
      <input type="email" name="email" placeholder="superadmin@syncride.io" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      <label>Password (mín. 8 caracteres)</label>
      <input type="password" name="password" minlength="8" required>
      <button type="submit">Criar Super Admin</button>
    </form>
  </div>

  <?php if ($existing): ?>
  <div class="existing">
    <h2>Super Admins existentes</h2>
    <?php foreach ($existing as $u): ?>
    <div class="u-row">
      <div>
        <div class="u-name"><?= htmlspecialchars($u['name']) ?></div>
        <div class="u-email"><?= htmlspecialchars($u['email']) ?></div>
      </div>
      <span class="badge-sa">SUPER ADMIN</span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>
</body>
</html>
