<?php
/**
 * One-time super-admin user creator.
 * Run from web: http://localhost/SRMT/database/create-superadmin.php
 * DELETE this file immediately after use.
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
use App\Support\Database;

$isCli  = PHP_SAPI === 'cli';
$pdo    = Database::connection();
$error  = '';
$done   = false;

if (!$isCli && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || strlen($password) < 6) {
        $error = 'All fields required. Password must be at least 6 characters.';
    } else {
        $exists = $pdo->prepare('SELECT 1 FROM Users WHERE email = :e');
        $exists->execute(['e' => $email]);
        if ($exists->fetchColumn()) {
            $error = "A user with email {$email} already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO Users (name, email, password, role, company_id) VALUES (:n, :e, :p, 0, NULL)')
                ->execute(['n' => $name, 'e' => $email, 'p' => $hash]);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Super Admin</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1rem}
  .card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:2rem;width:100%;max-width:420px}
  h1{font-size:1.25rem;font-weight:700;color:#fff;margin-bottom:.5rem}
  p{font-size:.875rem;color:#94a3b8;margin-bottom:1.5rem}
  label{display:block;font-size:.8125rem;font-weight:500;color:#cbd5e1;margin-bottom:.375rem}
  input{width:100%;background:#0f172a;border:1px solid #334155;border-radius:10px;padding:.625rem .875rem;color:#fff;font-size:.875rem;outline:none;margin-bottom:1rem}
  input:focus{border-color:#6366f1}
  button{width:100%;padding:.75rem;border-radius:10px;background:#6366f1;color:#fff;font-weight:600;font-size:.9375rem;border:none;cursor:pointer}
  button:hover{background:#4f46e5}
  .error{background:#7f1d1d33;border:1px solid #f8717133;color:#fca5a5;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.875rem}
  .success{background:#14532d33;border:1px solid #86efac33;color:#86efac;border-radius:10px;padding:1rem;font-size:.875rem;text-align:center}
</style>
</head>
<body>
<div class="card">
  <h1>Create Super Admin</h1>
  <p>This creates a role=0 user with access to all companies.<br><strong>Delete this file immediately after use.</strong></p>

  <?php if ($done): ?>
  <div class="success">
    <strong>Super admin created!</strong><br>
    You can now login at <a href="/SRMT/public/" style="color:#4ade80">/SRMT/public/</a>.<br><br>
    <strong style="color:#fcd34d">Delete this file now:</strong><br>
    <code style="font-size:.75rem">database/create-superadmin.php</code>
  </div>
  <?php else: ?>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <label>Full Name</label>
    <input type="text" name="name" placeholder="Super Admin" required>
    <label>Email</label>
    <input type="email" name="email" placeholder="superadmin@syncride.io" required>
    <label>Password (min. 6 chars)</label>
    <input type="password" name="password" minlength="6" required>
    <button type="submit">Create Super Admin</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
