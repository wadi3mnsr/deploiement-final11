<?php
// login.php
require __DIR__ . '/lib/auth.php';

// Si déjà connecté, redirige vers le compte
if (is_client_logged()) {
    header('Location: /account.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_login'];

$flashError = get_flash('error');
$flashSuccess = get_flash('success');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container" style="max-width:600px;margin:2rem auto;">
  <h1>Connexion</h1>

  <?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>

  <?php if ($flashError): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <form method="post" action="/login_save.php" style="display:grid;gap:1rem;">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

    <label>
      Email
      <input type="email" name="email" required placeholder="vous@exemple.com">
    </label>

    <label>
      Mot de passe
      <input type="password" name="password" required placeholder="••••••••">
    </label>

    <button type="submit" class="btn btn-primary">Se connecter</button>
  </form>

  <p style="margin-top:1rem;">
    Pas encore de compte ? <a href="/register.php">Créer un compte</a>
  </p>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
