<?php include __DIR__ . '/includes/header.php'; ?>

<section class="hero">
  <div class="container">
    <h1>Créer un compte</h1>
    <p class="lead">Inscrivez-vous pour faciliter vos prochaines réservations.</p>
  </div>
</section>

<section class="container">
  <article class="card">
    <h2 class="mt-0">Inscription</h2>
    <form class="form-grid" method="post" action="/register_save.php" novalidate>
      <div class="grid-2">
        <label>Prénom
          <input type="text" name="firstname" required>
        </label>
        <label>Nom
          <input type="text" name="lastname" required>
        </label>
      </div>

      <div class="grid-2">
        <label>Email
          <input type="email" name="email" required>
        </label>
        <label>Téléphone
          <input type="tel" name="phone" required>
        </label>
      </div>

      <div class="grid-2">
        <label>Mot de passe
          <input type="password" name="password" minlength="8" required>
        </label>
        <label>Confirmer le mot de passe
          <input type="password" name="password_confirm" minlength="8" required>
        </label>
      </div>

      <!-- Honeypot anti-bot -->
      <input type="text" name="website" id="website" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

      <label class="checkbox">
        <input type="checkbox" name="terms" required>
        J’accepte les conditions d’utilisation et la politique de confidentialité.
      </label>

      <div class="grid-2">
        <button class="btn btn-primary" type="submit">Créer mon compte</button>
        <button class="btn" type="reset">Annuler</button>
      </div>
    </form>
  </article>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
