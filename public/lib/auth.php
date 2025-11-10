<?php
// lib/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 /* Vérifie si un client est connecté
 */
function is_client_logged(): bool {
    return !empty($_SESSION['client']) && !empty($_SESSION['client']['id']);
}

 /* Redirige vers /login.php si non connecté
 */
function require_client_login(): void {
    if (!is_client_logged()) {
        header('Location: /login.php');
        exit;
    }
}
/* Retourne les infos du client connecté
 */
function current_client(): ?array {
    return $_SESSION['client'] ?? null;
}
 /* Connecte un client (stocke ses infos en session)
 */
function login_client(array $client): void {
    session_regenerate_id(true);
    $_SESSION['client'] = [
        'id'        => (int)$client['id'],
        'firstname' => $client['firstname'],
        'lastname'  => $client['lastname'],
        'email'     => $client['email'],
        'phone'     => $client['phone'] ?? null,
        'created_at'=> $client['created_at'] ?? null,
    ];
}

/**
 * Déconnecte le client
 */
function logout_client(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Stocke un message flash en session
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Récupère et supprime un message flash
 */
function get_flash(?string $type=null): ?string {
    if ($type === null) return null;
    if (!empty($_SESSION['flash'][$type])) {
        $m = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $m;
    }
    return null;
}
