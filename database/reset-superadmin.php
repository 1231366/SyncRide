<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
use App\Support\Database;

$pdo  = Database::connection();
$temp = 'SyncRide2025!';
$hash = password_hash($temp, PASSWORD_BCRYPT);

$stmt = $pdo->prepare('SELECT id, name, email FROM Users WHERE role = 0 LIMIT 1');
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $pdo->prepare('UPDATE Users SET password = :p WHERE role = 0')->execute(['p' => $hash]);
}
?>
<!DOCTYPE html><meta charset="utf-8">
<style>
  body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
  .card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:2rem;max-width:420px;width:100%;margin:1rem}
  h1{font-size:1.25rem;font-weight:700;color:#fff;margin:0 0 1.5rem}
  .row{display:flex;justify-content:space-between;align-items:center;padding:.75rem 1rem;background:#0f172a;border-radius:10px;margin-bottom:.75rem}
  .label{font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
  .value{font-size:.9375rem;color:#fff;font-weight:600;font-family:monospace}
  .warn{background:#422006;border:1px solid #92400e;border-radius:10px;padding:.875rem 1rem;color:#fbbf24;font-size:.875rem;margin-top:1.5rem}
  a{display:block;text-align:center;margin-top:1rem;padding:.75rem;background:#6366f1;border-radius:10px;color:#fff;font-weight:600;text-decoration:none}
  a:hover{background:#4f46e5}
  .none{color:#ef4444;font-size:.875rem}
</style>
<div class="card">
  <h1>🔑 Super Admin Reset</h1>
  <?php if ($user): ?>
  <div class="row"><span class="label">Name</span><span class="value"><?= htmlspecialchars($user['name']) ?></span></div>
  <div class="row"><span class="label">Email</span><span class="value"><?= htmlspecialchars($user['email']) ?></span></div>
  <div class="row"><span class="label">New password</span><span class="value"><?= $temp ?></span></div>
  <div class="warn">⚠️ <strong>Delete this file now</strong> and change your password after login.<br><code>database/reset-superadmin.php</code></div>
  <a href="/SRMT/public/">Go to Login →</a>
  <?php else: ?>
  <p class="none">No super admin (role=0) found in the database.</p>
  <?php endif; ?>
</div>
