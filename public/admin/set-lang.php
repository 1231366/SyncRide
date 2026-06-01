<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Support\Database;
use App\Support\Session;
AuthMiddleware::handle(1);

$allowed = ['en', 'pt'];
$lang    = in_array($_GET['lang'] ?? '', $allowed, true) ? $_GET['lang'] : 'en';
$_SESSION['admin_lang'] = $lang;

$userId = Session::userId();
if ($userId !== null) {
    $db = Database::connection();
    $db->prepare('UPDATE Users SET lang_pref = :lang WHERE id = :id')
       ->execute(['lang' => $lang, 'id' => $userId]);
}

header('Location: /SRMT/public/admin/settings.php');
exit;
