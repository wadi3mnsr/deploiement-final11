<?php
require_once __DIR__ . '/../lib/auth.php';
$me = current_client();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>FlexVTC</title>

  <!-- Fonts & CSS -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/style.css" />

  <!-- MapLibre (optionnel) -->
  <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
  <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
</head>

<body class="site">
<header role="banner">
  <div class="container nav">
    <!-- Logo -->
    <a href="index.php" class="logo" aria-label="Accueil FlexVTC">Flex<span>VTC</span></a>

    <!-- Navigation (wrap en mobile, pas de burger) -->
    <nav id="primary-nav" aria-label="Navigation principale">
      <a href="index.php">Accueil</a>
      <a href="about.php">À propos</a>
      <a href="reservation.php">Réservation</a>
      <a href="avis.php">Avis</a>
      <a href="contact.php">Contact</a>
    </nav>

    <!-- Actions compte -->
    <div class="nav-actions">
      <?php if (is_client_logged()): ?>
        <a href="account.php" class="btn btn-ghost">Bonjour <?= htmlspecialchars($me['firstname']) ?></a>
        <a href="logout.php" class="btn btn-danger">Déconnexion</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-ghost">Se connecter</a>
        <a href="register.php" class="btn btn-primary">Créer un compte</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="container">
