<?php include __DIR__ . '/includes/header.php'; ?>
<h1>Connexion admin</h1>
<form class="form-grid" method="post" action="/admin/login_check.php">
  <label>Utilisateur<input type="text" name="username" required></label>
  <label>Mot de passe<input type="password" name="password" required></label>
  <button class="btn btn-primary">Connexion</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
