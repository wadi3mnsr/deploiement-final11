<?php
require __DIR__ . '/config/app.php';
require __DIR__ . '/config/database.php';

// Pagination
$perPage = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Stats
$stats = $pdo->query("SELECT COUNT(*) AS c, AVG(rating) AS avg_rating FROM reviews WHERE status='approved'")->fetch();
$total = (int)($stats['c'] ?? 0);
$avg   = $stats['avg_rating'] ? round((float)$stats['avg_rating'], 1) : 0.0;
$pages = max(1, (int)ceil($total / $perPage));

// Avis
$stmt = $pdo->prepare("SELECT id, name, rating, comment, created_at FROM reviews WHERE status='approved' ORDER BY created_at DESC LIMIT :lim OFFSET :off");
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

function stars_html($n){
  $n = (int)$n; $n = max(0, min(5, $n));
  return str_repeat('★', $n) . str_repeat('☆', 5-$n);
}

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <h1>Avis clients</h1>
    <p class="lead">Votre confiance compte beaucoup pour nous. Donnez votre avis sur FlexVTC.</p>
  </div>
</section>

<section class="container">
  <div class="cards" style="align-items:start;">
    <article class="card">
      <h2 class="mt-0">Laisser un avis</h2>
      <form method="post" action="/avis_save.php" class="form-grid" novalidate>
        <!-- Honeypot anti-bot -->
        <input type="text" name="website" id="website" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

        <label>Votre nom
          <input type="text" name="name" maxlength="100" required placeholder="Ex: Martin D.">
        </label>

        <label>Note
          <select name="rating" required>
            <option value="5">★★★★★ – Excellent</option>
            <option value="4">★★★★☆ – Très bien</option>
            <option value="3">★★★☆☆ – Bien</option>
            <option value="2">★★☆☆☆ – Moyen</option>
            <option value="1">★☆☆☆☆ – À améliorer</option>
          </select>
        </label>

        <label>Votre avis
          <textarea name="comment" rows="4" maxlength="1000" required placeholder="Qualité du trajet, ponctualité, confort…"></textarea>
        </label>

        <button class="btn btn-primary" type="submit">Publier mon avis</button>
      </form>
      <p class="small" style="margin-top:.5rem;">En envoyant, vous acceptez la publication de votre prénom/initiale et votre commentaire.</p>
    </article>

    <article class="card">
      <h2 class="mt-0">Notes & avis</h2>
      <p class="mb-1"><strong><?= $total ?></strong> avis publiés</p>
      <p class="rating-badge" aria-label="Note moyenne"><?= stars_html(round($avg)) ?> <span class="small">(moyenne <?= number_format($avg,1,',',' ') ?>/5)</span></p>

      <div class="reviews-list">
        <?php if(!$reviews): ?>
          <p>Aucun avis pour le moment. Soyez le premier à donner le vôtre !</p>
        <?php else: ?>
          <?php foreach($reviews as $r): ?>
            <div class="review-item">
              <div class="review-header">
                <strong><?= htmlspecialchars($r['name']) ?></strong>
                <span class="stars"><?= stars_html((int)$r['rating']) ?></span>
              </div>
              <p><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
              <p class="small muted"><?= htmlspecialchars(date('d/m/Y', strtotime($r['created_at']))) ?></p>
            </div>
            <hr>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if($pages > 1): ?>
        <nav class="pagination">
          <?php for($i=1; $i<=$pages; $i++): ?>
            <?php if($i === $page): ?>
              <span class="page current"><?= $i ?></span>
            <?php else: ?>
              <a class="page" href="/avis.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    </article>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
