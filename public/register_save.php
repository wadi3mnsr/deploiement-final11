<?php
require __DIR__ . '/config/database.php';

// ---------------------------
// 1) Récupération & nettoyage
// ---------------------------
$firstname = trim($_POST['firstname'] ?? '');
$lastname  = trim($_POST['lastname'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$password  = $_POST['password'] ?? '';

// ---------------------------
// 2) Validation basique
// ---------------------------
if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
    die("❌ Merci de remplir tous les champs obligatoires.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("❌ Email invalide.");
}

// ---------------------------
// 3) Vérification doublon email
// ---------------------------
$stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
$stmt->execute([$email]);
$exists = $stmt->fetch();

if ($exists) {
    die("❌ Cet email est déjà utilisé.");
}

// ---------------------------
// 4) Insertion en BDD
// ---------------------------
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO clients (firstname, lastname, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
$ok = $stmt->execute([$firstname, $lastname, $email, $phone, $hashed_password]);

if ($ok) {
    echo "✅ Inscription réussie, vous pouvez maintenant vous connecter.";
} else {
    echo "❌ Erreur lors de l’inscription.";
}
