<?php
// login_save.php
require __DIR__ . '/config/database.php';
require __DIR__ . '/lib/auth.php';

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$csrf     = $_POST['csrf'] ?? '';

if (empty($csrf) || empty($_SESSION['csrf_login']) || !hash_equals($_SESSION['csrf_login'], $csrf)) {
    set_flash('error', "Requête invalide (CSRF). Veuillez réessayer.");
    header('Location: /login.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    set_flash('error', "Email ou mot de passe invalide.");
    header('Location: /login.php');
    exit;
}

// Récupère le client par email
$stmt = $pdo->prepare("SELECT id, firstname, lastname, email, phone, password_hash, created_at FROM clients WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client || !password_verify($password, $client['password_hash'])) {
    // réponse générique pour ne pas révéler lequel est faux
    set_flash('error', "Identifiants incorrects.");
    header('Location: /login.php');
    exit;
}

// OK : on connecte
login_client($client);
unset($_SESSION['csrf_login']);
set_flash('success', "Bienvenue, {$client['firstname']} !");
header('Location: /account.php');
exit;
