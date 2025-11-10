<?php include __DIR__ . '/includes/header.php'; ?>

<section class="hero hero-contact">
  <div class="container">
    <h1>Contact</h1>
    <p class="lead">Une question ? Besoin d’un devis personnalisé ? Écrivez-nous.</p>
  </div>
</section>

<section class="container">
  <div class="cards" style="align-items:start;">
    <article class="card">
      <h2 class="mt-0">Formulaire de contact</h2>
      <form id="contact-form" class="form-grid" method="post" action="/contact_save.php" novalidate>
        <!-- Honeypot (anti-bot) -->
        <input type="text" name="company" id="company"
               style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

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

        <label>Message
          <textarea name="message" rows="5" required placeholder="Expliquez votre besoin"></textarea>
        </label>

        <div class="grid-2">
          <button class="btn btn-primary" type="submit">Envoyer</button>
          <button class="btn" type="reset">Annuler</button>
        </div>
        <p class="small">Nous répondons généralement sous 24h ouvrées.</p>
      </form>
    </article>

    <article class="card">
      <h2 class="mt-0">Nos infos</h2>
      <p class="mb-1"><strong>FlexVTC</strong><br>Nantes & alentours</p>
      <p class="mb-1">Email : wadiimansouri@gmail.com<br>Tél : 06 25 25 54 84</p>
      <p class="small">Service 24/7 sur réservation</p>


