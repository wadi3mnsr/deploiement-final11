<?php
// 1) Charger la session/CSRF + la connexion PDO
require __DIR__ . '/config/app.php';
require __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/send_smtp.php'; // OK si tu utilises MailHog, sinon retire

// 2) Honeypot (anti-bot) : si ce champ est rempli, on ignore
if (!empty($_POST['company'] ?? '')) {
  http_response_code(204);
  exit;
}

// 3) Récupération & validation
$firstname = trim($_POST['firstname'] ?? '');
$lastname  = trim($_POST['lastname'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$message   = trim($_POST['message'] ?? '');

$errors = [];
if ($firstname === '') $errors[] = "Prénom obligatoire.";
if ($lastname  === '') $errors[] = "Nom obligatoire.";
if ($email     === '') $errors[] = "Email obligatoire.";
if ($phone     === '') $errors[] = "Téléphone obligatoire.";
if ($message   === '') $errors[] = "Message obligatoire.";
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

// 4) Si erreurs → afficher une page d’erreur
if ($errors) {
  include __DIR__ . '/includes/header.php';
  echo '<section class="container"><div class="card" style="margin-top:1.5rem">';
  echo '<h1 class="mt-0">Erreur</h1><ul>';
  foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>';
  echo '</ul><p><a class="btn" href="/contact.php">Retour</a></p></div></section>';
  include __DIR__ . '/includes/footer.php';
  exit;
}

// 5) Insertion en base
$stmt = $pdo->prepare("
  INSERT INTO contacts (firstname, lastname, email, phone, message)
  VALUES (:fn, :ln, :em, :ph, :msg)
");
$stmt->execute([
  ':fn'  => $firstname,
  ':ln'  => $lastname,
  ':em'  => $email,
  ':ph'  => $phone,
  ':msg' => $message,
]);

// 6) (Option) Envoi d'e-mails via MailHog si la fonction existe
if (function_exists('send_smtp_basic')) {
  $clientSubject = "Votre message a bien été reçu – FlexVTC";
  $clientBody = "<p>Bonjour ".htmlspecialchars($firstname).",</p>
  <p>Merci pour votre message. Nous reviendrons vers vous rapidement.</p>
  <hr>
  <p><strong>Récapitulatif :</strong><br>
  Nom : ".htmlspecialchars($firstname.' '.$lastname)."<br>
  Email : ".htmlspecialchars($email)."<br>
  Téléphone : ".htmlspecialchars($phone)."</p>
  <p><strong>Message :</strong><br>".nl2br(htmlspecialchars($message))."</p>
  <p>— FlexVTC</p>";
  @send_smtp_basic($email, $clientSubject, $clientBody);

  $adminSubject = "Nouveau message de contact – ".htmlspecialchars($firstname.' '.$lastname);
  $adminBody = "<p>Nouveau message reçu :</p>
  <ul>
    <li>Nom : ".htmlspecialchars($firstname.' '.$lastname)."</li>
    <li>Email : ".htmlspecialchars($email)."</li>
    <li>Tél : ".htmlspecialchars($phone)."</li>
  </ul>
  <p><strong>Message :</strong><br>".nl2br(htmlspecialchars($message))."</p>";
  @send_smtp_basic('gerant@flexvtc.local', $adminSubject, $adminBody);
}

// 7) Page de confirmation
include __DIR__ . '/includes/header.php';
?>
<section class="container">
  <div class="card" style="margin-top:1.5rem">
    <h1 class="mt-0">Message envoyé ✅</h1>
    <p>Merci <?= htmlspecialchars($firstname) ?>, votre message a bien été enregistré<?php if (function_exists('send_smtp_basic')) echo " et un e-mail de confirmation vous a été envoyé"; ?>.</p>
    <p><a class="btn btn-primary" href="/index.php">Retour à l’accueil</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
