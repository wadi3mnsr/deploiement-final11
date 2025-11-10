<?php
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../config/database.php';
require_admin();

// Récupérer toutes les réservations
$stmt = $pdo->query("SELECT * FROM reservations ORDER BY ride_datetime DESC, id DESC");
$reservations = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container">
  <section style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
    <h1 class="mt-0">Réservations</h1>
    <a class="btn" href="/admin/logout.php">Déconnexion</a>
  </section>

  <section class="cards" style="margin-top:1rem;">
    <?php if (!$reservations): ?>
      <article class="card"><p>Aucune réservation trouvée.</p></article>
    <?php endif; ?>

    <?php foreach ($reservations as $r): ?>
      <article class="card">
        <h3 class="mt-0">#<?= (int)$r['id'] ?> — <?= htmlspecialchars($r['status']) ?></h3>
        <p><strong><?= htmlspecialchars($r['firstname'].' '.$r['lastname']) ?></strong><br>
           <?= htmlspecialchars($r['email']) ?> — <?= htmlspecialchars($r['phone']) ?></p>
        <p>
          <strong>Trajet :</strong><br>
          <?= htmlspecialchars($r['start_address']) ?> → <?= htmlspecialchars($r['end_address']) ?><br>
          <strong>Date :</strong> <?= htmlspecialchars($r['ride_datetime']) ?>
        </p>
        <p>
          <?php if ($r['distance_km'] !== null): ?>
            Distance : <?= number_format((float)$r['distance_km'],2,',',' ') ?> km — 
          <?php endif; ?>
          <?php if ($r['duration_min'] !== null): ?>
            Durée : <?= (int)$r['duration_min'] ?> min — 
          <?php endif; ?>
          <?php if ($r['price_eur'] !== null): ?>
            Prix : <?= number_format((float)$r['price_eur'],2,',',' ') ?> €
          <?php endif; ?>
        </p>

        <form method="post" action="/admin/update_status.php" class="grid-2">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <select name="status">
            <?php
              $opts = ['new'=>'Nouveau','confirmed'=>'Confirmé','done'=>'Terminé','canceled'=>'Annulé'];
              foreach ($opts as $k=>$label) {
                $sel = ($r['status']===$k) ? 'selected' : '';
                echo "<option value=\"$k\" $sel>$label</option>";
              }
            ?>
          </select>
          <button class="btn btn-primary" type="submit">Mettre à jour</button>
        </form>
      </article>
    <?php endforeach; ?>
  </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
