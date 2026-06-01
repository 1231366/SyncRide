<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Env;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/**
 * Returns a PHPMailer instance pre-configured from .env MAIL_* variables.
 * Throws if the required SMTP credentials are not set so callers can catch
 * and log rather than silently sending nothing.
 */
final class Mailer
{
    public static function make(): PHPMailer
    {
        // PHPMailer is vendored manually (no Composer autoload) — load once.
        $base = dirname(__DIR__, 2) . '/vendor/phpmailer/PHPMailer/';
        require_once $base . 'Exception.php';
        require_once $base . 'PHPMailer.php';
        require_once $base . 'SMTP.php';

        $host     = (string) (Env::get('MAIL_HOST')         ?: '');
        $port     = (int)    (Env::get('MAIL_PORT')         ?: 587);
        $user     = (string) (Env::get('MAIL_USERNAME')     ?: '');
        $pass     = (string) (Env::get('MAIL_PASSWORD')     ?: '');
        $enc      = strtolower((string) (Env::get('MAIL_ENCRYPTION') ?: 'tls'));
        $from     = (string) (Env::get('MAIL_FROM_ADDRESS') ?: '');
        $fromName = (string) (Env::get('MAIL_FROM_NAME')    ?: 'SyncRide');

        if ($host === '' || $user === '' || $pass === '') {
            throw new RuntimeException(
                'SMTP not configured — set MAIL_HOST, MAIL_USERNAME and MAIL_PASSWORD in .env'
            );
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPSecure = ($enc === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom($from !== '' ? $from : $user, $fromName);

        return $mail;
    }
}
