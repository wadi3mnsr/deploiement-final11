<?php
declare(strict_types=1);

/**
 * config/app.php — bootstrap applicatif
 * - Charge l'autoload Composer
 * - Charge le .env (via vlucas/phpdotenv) si présent
 * - Définit des valeurs par défaut si .env absent
 * - Démarre la session (sécurisée) + helpers CSRF & Auth
 */

/////////////////////////
// 0) Autoload Composer
/////////////////////////
require __DIR__ . '/../vendor/autoload.php';

/////////////////////////
// 1) Variables d'environnement (.env)
/////////////////////////
$projectRoot = dirname(__DIR__); // racine du projet

// Charge .env s'il existe (ne plante pas si absent)
if (is_file($projectRoot.'/.env')) {
    Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

// Fonction utilitaire pour lire une env avec défaut
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        // $_ENV prioritaire, sinon getenv(), sinon défaut
        if (array_key_exists($key, $_ENV)) return $_ENV[$key];
        $v = getenv($key);
        return $v === false ? $default : $v;
    }
}

// Valeurs par défaut (si non définies dans .env)
$_ENV += [
    // App
    'APP_ENV'  => env('APP_ENV', 'dev'),
    'APP_TZ'   => env('APP_TZ', 'Europe/Paris'),
    'APP_DEBUG'=> env('APP_DEBUG', '1'),

    // MySQL
    'DB_HOST'  => env('DB_HOST', 'db'),
    'DB_NAME'  => env('DB_NAME', 'flexvtc_db'),
    'DB_USER'  => env('DB_USER', 'flex_user'),
    'DB_PASS'  => env('DB_PASS', 'flex_userpass'),

    // Mongo
    'MONGO_HOST'=> env('MONGO_HOST', 'mongo'),
    'MONGO_USER'=> env('MONGO_USER', 'root'),
    'MONGO_PASS'=> env('MONGO_PASS', 'rootpass'),
    'MONGO_DB'  => env('MONGO_DB', 'flexvtc'),

    // SMTP (Mailhog en dev)
    'SMTP_HOST' => env('SMTP_HOST', 'mailhog'),
    'SMTP_PORT' => env('SMTP_PORT', '1025'),
    'SMTP_USER' => env('SMTP_USER', ''),
    'SMTP_PASS' => env('SMTP_PASS', ''),
];

define('APP_ENV',   (string)$_ENV['APP_ENV']);
define('APP_DEBUG', (bool)  (int)$_ENV['APP_DEBUG']);
date_default_timezone_set((string)$_ENV['APP_TZ']);

/////////////////////////
// 2) Session (avant tout output)
/////////////////////////
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Cookies de session un peu plus stricts
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,   // true si HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/////////////////////////
// 3) Helpers généraux
/////////////////////////

// CSRF helpers (inchangés, avec légère robustesse)
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): bool {
    $ok = isset($_POST['csrf_token'], $_SESSION['csrf_token'])
       && hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token']);
    // On régénère pour éviter la réutilisation
    unset($_SESSION['csrf_token']);
    return $ok;
}

// Auth helpers (inchangés)
function is_admin_logged(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!is_admin_logged()) {
        header('Location: /admin/login.php');
        exit;
    }
}

// Petit helper redirection
function redirect(string $path, int $code = 302): void {
    http_response_code($code);
    header('Location: '.$path);
    exit;
}

// Exemple d'accès aux env :
// echo $_ENV['DB_HOST'];
