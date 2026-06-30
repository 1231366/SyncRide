<?php
$sent = false;
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $reason = htmlspecialchars(trim($_POST['reason'] ?? ''), ENT_QUOTES, 'UTF-8');

    if ($email) {
        $to = 'tiagofsilva04@gmail.com';
        $subject = 'Account Deletion Request — SyncRide';
        $body = "Account deletion request received.\n\nEmail: {$email}\nReason: {$reason}\nDate: " . date('Y-m-d H:i:s') . "\n\nPlease delete this account and all associated data within 30 days.";
        $headers = "From: noreply@syncride.wmservers.pt\r\nReply-To: {$email}";
        mail($to, $subject, $body, $headers);
        $sent = true;
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Delete Account — SyncRide</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
  .card { background: #1e293b; border-radius: 16px; padding: 40px; max-width: 480px; width: 100%; }
  .logo { font-size: 22px; font-weight: 700; color: #2563eb; margin-bottom: 8px; }
  h1 { font-size: 20px; font-weight: 600; margin-bottom: 8px; }
  p { font-size: 14px; color: #94a3b8; line-height: 1.6; margin-bottom: 24px; }
  label { display: block; font-size: 13px; font-weight: 500; color: #cbd5e1; margin-bottom: 6px; }
  input, textarea { width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px; color: #e2e8f0; font-size: 14px; margin-bottom: 16px; outline: none; }
  input:focus, textarea:focus { border-color: #2563eb; }
  textarea { height: 90px; resize: vertical; }
  button { width: 100%; background: #dc2626; color: #fff; border: none; border-radius: 8px; padding: 13px; font-size: 15px; font-weight: 600; cursor: pointer; }
  button:hover { background: #b91c1c; }
  .notice { background: #172033; border: 1px solid #334155; border-radius: 8px; padding: 14px; font-size: 13px; color: #94a3b8; margin-bottom: 20px; line-height: 1.5; }
  .success { text-align: center; }
  .success .icon { font-size: 48px; margin-bottom: 16px; }
  .success h2 { font-size: 18px; margin-bottom: 8px; }
  .err { color: #f87171; font-size: 13px; margin-bottom: 12px; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">SyncRide</div>
  <?php if ($sent): ?>
  <div class="success">
    <div class="icon">✓</div>
    <h2>Request received</h2>
    <p>We will delete your account and all associated data within <strong>30 days</strong>. You will receive a confirmation email.</p>
  </div>
  <?php else: ?>
  <h1>Delete your account</h1>
  <p>Submit your request below. Your account and all associated data (trips, location history, profile) will be permanently deleted within 30 days.</p>
  <div class="notice">
    ⚠️ This action is irreversible. All your data will be permanently removed from our servers.
  </div>
  <?php if ($error): ?><p class="err">Please enter a valid email address.</p><?php endif; ?>
  <form method="POST">
    <label>Email address on your account</label>
    <input type="email" name="email" placeholder="your@email.com" required>
    <label>Reason (optional)</label>
    <textarea name="reason" placeholder="Tell us why you're leaving..."></textarea>
    <button type="submit">Request account deletion</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
