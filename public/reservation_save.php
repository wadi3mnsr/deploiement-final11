<?php
// public/reservation_save.php
require __DIR__ . '/config/app.php';
require __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/send_smtp.php';  // contient send_smtp_gmail_with_attachment(...)
require_once __DIR__ . '/lib/order_pdf.php';  // contient generate_bon_commande_pdf(...)

// ---------------------------
// 1) Récupération & nettoyage
// ---------------------------
$from      = trim($_POST['from'] ?? '');
$to        = trim($_POST['to'] ?? '');
$date      = $_POST['date'] ?? '';
$time      = $_POST['time'] ?? '';
$option    = $_POST['option'] ?? '';

$firstname = trim($_POST['firstname'] ?? '');
$lastname  = trim($_POST['lastname'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$email     = trim($_POST['email'] ?? '');
$notes     = trim($_POST['notes'] ?? '');

$distance  = $_POST['distance'] ?? null;          // km (nombre)
$duration  = $_POST['duration'] ?? null;          // min (nombre)
$price     = $_POST['price_estimate'] ?? null;    // € (nombre)

$from_lat  = $_POST['from_lat'] ?? null;
$from_lng  = $_POST['from_lng'] ?? null;
$to_lat    = $_POST['to_lat'] ?? null;
$to_lng    = $_POST['to_lng'] ?? null;

// -----------------------------------
// 2) Validation minimale côté serveur
// -----------------------------------
$errors = [];
if ($from === '' || $to === '')            $errors[] = "Adresses départ et arrivée obligatoires.";
if ($date === '' || $time === '')          $errors[] = "Date et heure obligatoires.";
if ($firstname === '' || $lastname === '') $errors[] = "Nom et prénom obligatoires.";
if ($phone === '')                         $errors[] = "Téléphone obligatoire.";
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

$distance_km  = is_numeric($distance) ? round((float)$distance, 2) : null;
$duration_min = is_numeric($duration) ? (int)$duration : null;
$price_eur    = is_numeric($price)    ? round((float)$price, 2) : null;

$pickup_lat = is_numeric($from_lat) ? (float)$from_lat : null;
$pickup_lng = is_numeric($from_lng) ? (float)$from_lng : null;
$dropoff_lat = is_numeric($to_lat)  ? (float)$to_lat  : null;
$dropoff_lng = is_numeric($to_lng)  ? (float)$to_lng  : null;

// Construit les champs conformes au schéma: pickup_date + pickup_time
$pickup_ts   = strtotime("$date $time");
$pickup_date = date('Y-m-d', $pickup_ts);
$pickup_time = date('H:i:s', $pickup_ts);

if ($errors) {
  http_response_code(400);
  include __DIR__ . '/includes/header.php';
  echo '<section class="container"><div class="card" style="margin-top:1.5rem">';
  echo '<h1 class="mt-0">Erreur</h1><ul>';
  foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>';
  echo '</ul><p><a class="btn" href="/reservation.php">Retour</a></p></div></section>';
  include __DIR__ . '/includes/footer.php';
  exit;
}

// ---------------------
// 3) Insertion en base
// ---------------------
// On concatène les estimations dans "notes" pour ne pas nécessiter de colonnes dédiées.
$notes_db = $notes;
$meta = [];
if ($price_eur !== null)   $meta[] = "Prix estimé: ".number_format($price_eur, 2, ',', ' ')." €";
if ($distance_km !== null) $meta[] = "Distance: ".number_format($distance_km, 2, ',', ' ')." km";
if ($duration_min !== null)$meta[] = "Durée: ".(int)$duration_min." min";
if (!empty($option))       $meta[] = "Option: ".$option;
if ($meta) {
  $notes_db = trim(($notes_db ? $notes_db."\n" : '').implode(' — ', $meta));
}

$stmt = $pdo->prepare("
  INSERT INTO reservations
    (firstname, lastname, phone, email,
     start_address, end_address,
     pickup_date, pickup_time,
     pickup_lat, pickup_lng, dropoff_lat, dropoff_lng,
     notes, status)
  VALUES
    (:firstname, :lastname, :phone, :email,
     :start_address, :end_address,
     :pickup_date, :pickup_time,
     :pickup_lat, :pickup_lng, :dropoff_lat, :dropoff_lng,
     :notes, 'pending')
");

$stmt->execute([
  ':firstname'     => $firstname,
  ':lastname'      => $lastname,
  ':phone'         => $phone,
  ':email'         => $email,
  ':start_address' => $from,
  ':end_address'   => $to,
  ':pickup_date'   => $pickup_date,
  ':pickup_time'   => $pickup_time,
  ':pickup_lat'    => $pickup_lat,
  ':pickup_lng'    => $pickup_lng,
  ':dropoff_lat'   => $dropoff_lat,
  ':dropoff_lng'   => $dropoff_lng,
  ':notes'         => $notes_db,
]);

$id = (int)$pdo->lastInsertId();

// ----------------------------------------------------
// 4) Génération du bon de commande PDF (pièce jointe)
// ----------------------------------------------------
$pdfPath = generate_bon_commande_pdf([
  'id'           => $id,
  'date'         => date('d/m/Y H:i', $pickup_ts),
  'client_name'  => $firstname.' '.$lastname,
  'client_email' => $email,
  'from'         => $from,
  'to'           => $to,
  'price'        => $price_eur,     // peut être null
  'distance_km'  => $distance_km,   // peut être null
  'duration_min' => $duration_min,  // peut être null
  'option'       => $option ?: '—',
]);

// ----------------------------------------
// 5) Envoi e-mails réels via Gmail (PJ)
// ----------------------------------------
$subjectClient = "Votre bon de commande #$id – FlexVTC";
$subjectAdmin  = "Bon de commande #$id (client : ".$firstname." ".$lastname.")";

$bodyClient = "<p>Bonjour ".htmlspecialchars($firstname).",</p>
<p>Veuillez trouver en pièce jointe votre bon de commande (PDF) pour la réservation #$id.</p>
<p>Résumé :</p>
<ul>
  <li><strong>Trajet :</strong> ".htmlspecialchars($from)." → ".htmlspecialchars($to)."</li>
  <li><strong>Date :</strong> ".htmlspecialchars(date('d/m/Y H:i', $pickup_ts))."</li>"
  . ($price_eur !== null ? "<li><strong>Prix estimé :</strong> ".number_format($price_eur,2,',',' ')." €</li>" : "") .
"</ul>
<p>Merci pour votre confiance.<br>— FlexVTC</p>";

$bodyAdmin = "<p>Nouveau bon de commande en pièce jointe (PDF) pour la réservation #$id.</p>
<ul>
  <li><strong>Client :</strong> ".htmlspecialchars($firstname.' '.$lastname)." (".htmlspecialchars($email).")</li>
  <li><strong>Trajet :</strong> ".htmlspecialchars($from)." → ".htmlspecialchars($to)."</li>
  <li><strong>Date :</strong> ".htmlspecialchars(date('d/m/Y H:i', $pickup_ts))."</li>
</ul>";

// (envoi via Gmail SMTP - nécessite config/email.php et un mot de passe d'application)
send_smtp_gmail_with_attachment($email, $subjectClient, $bodyClient, $pdfPath, "bon-commande-{$id}.pdf");
send_smtp_gmail_with_attachment('wadiimansouri@gmail.com', $subjectAdmin, $bodyAdmin, $pdfPath, "bon-commande-{$id}.pdf");

// --------------------------------------
// 6) Page de confirmation utilisateur
// --------------------------------------
include __DIR__ . '/includes/header.php';
?>
<section class="container">
  <div class="card" style="margin-top:1.5rem">
    <h1 class="mt-0">Réservation envoyée ✅</h1>
    <p>Numéro de demande : <strong>#<?= $id ?></strong></p>
    <p>Merci <?= htmlspecialchars($firstname) ?>, un e-mail avec votre <strong>bon de commande (PDF)</strong> vous a été envoyé.</p>
    <hr>
    <p class="small">
      <strong>Trajet :</strong> <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?><br>
      <strong>Quand :</strong> <?= htmlspecialchars(date('d/m/Y H:i', $pickup_ts)) ?><br>
      <?php if($distance_km!==null): ?>Distance : <?= number_format($distance_km, 2, ',', ' ') ?> km — <?php endif; ?>
      <?php if($duration_min!==null): ?>Durée : <?= (int)$duration_min ?> min — <?php endif; ?>
      <?php if($price_eur!==null): ?>Prix estimé : <?= number_format($price_eur, 2, ',', ' ') ?> €<?php endif; ?>
    </p>
    <p>
      <a class="btn" href="/reservation.php">Nouvelle réservation</a>
      <a class="btn btn-primary" href="/index.php">Retour Accueil</a>
    </p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
