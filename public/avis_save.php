<?php
require __DIR__ . '/config/app.php';
require __DIR__ . '/config/database.php';

// 1) Honeypot anti-bot
if (!empty($_POST['website'] ?? '')) {
  http_response_code(204);
  exit;
}

// 2) Récolte & validation
$name    = trim($_POST['name'] ?? '');
$rating  = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

$errors = [];
if ($name === '' || mb_strlen($name) < 2)  $errors[] = "Nom trop court.";
if ($rating < 1 || $rating > 5)            $errors[] = "Note invalide.";
if ($comment === '' || mb_strlen($comment) > 1000) $errors[] = "Commentaire requis (max 1000).";

if ($errors) {
  include __DIR__ . '/includes/header.php';
  echo '<section class="container"><div class="card" style="margin-top:1.5rem">';
  echo '<h1>Erreur</h1><ul>'; foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; echo '</ul>';
  echo '<p><a class="btn" href="/avis.php">Retour</a></p></div></section>';
  include __DIR__ . '/includes/footer.php';
  exit;
}

// 3) Insertion (status = approved pour affichage direct en démo)
$stmt = $pdo->prepare("INSERT INTO reviews (name, rating, comment, status, ip) VALUES (:n, :r, :c, 'approved', :ip)");
$ipbin = isset($_SERVER['REMOTE_ADDR']) ? @inet_pton($_SERVER['REMOTE_ADDR']) : null;
$stmt->execute([':n'=>$name, ':r'=>$rating, ':c'=>$comment, ':ip'=>$ipbin]);

// 4) Confirmation
include __DIR__ . '/includes/header.php';
?>
<section class="container">
  <div class="card" style="margin-top:1.5rem">
    <h1 class="mt-0">Merci pour votre avis ✅</h1>
    <p>Votre avis a été publié. Nous apprécions votre retour !</p>
    <p><a class="btn btn-primary" href="/avis.php">Voir les avis</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
