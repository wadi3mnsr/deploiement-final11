<?php
declare(strict_types=1);

// Autoload + connexions
require __DIR__ . '/../vendor/autoload.php';
$pdo     = require __DIR__ . '/config/database.php';
$dbMongo = require __DIR__ . '/config/mongo.php';

// --- Params UI ---
$q     = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$off   = ($page - 1) * $limit;

// --- Recherche MySQL : reservations ---
$where = '';
$args  = [];
if ($q !== '') {
  $where = "WHERE r.firstname LIKE :q
            OR r.lastname LIKE :q
            OR r.email LIKE :q
            OR r.phone LIKE :q
            OR r.start_address LIKE :q
            OR r.end_address LIKE :q";
  $args[':q'] = "%{$q}%";
}

$countSql = "SELECT COUNT(*) FROM reservations r {$where}";
$stmt = $pdo->prepare($countSql);
$stmt->execute($args);
$total = (int)$stmt->fetchColumn();

$sql = "
SELECT
  r.id, r.created_at, r.updated_at,
  r.client_id,
  r.firstname, r.lastname, r.email, r.phone,
  r.start_address, r.end_address,
  r.pickup_date, r.pickup_time,
  r.passengers, r.luggage, r.notes,
  r.pickup_lat, r.pickup_lng, r.dropoff_lat, r.dropoff_lng,
  r.status,
  r.start_lat, r.start_lng, r.end_lat, r.end_lng,
  cl1.id   AS client_id_join,
  cl1.firstname AS client_firstname_join, cl1.lastname AS client_lastname_join,
  cl2.id   AS client_id_email,
  cl2.firstname AS client_firstname_email, cl2.lastname AS client_lastname_email
FROM reservations r
LEFT JOIN clients cl1 ON cl1.id = r.client_id        -- priorité au lien direct
LEFT JOIN clients cl2 ON cl2.email = r.email         -- fallback par email
{$where}
ORDER BY r.created_at DESC
LIMIT :limit OFFSET :off
";
$stmt = $pdo->prepare($sql);
foreach ($args as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $off, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// Déduire le meilleur client lié (client_id direct > email)
foreach ($rows as &$r) {
  $r['client_id_effective']   = $r['client_id_join']   ?: $r['client_id_email'] ?: null;
  $r['client_firstname_eff']  = $r['client_firstname_join'] ?: $r['client_firstname_email'] ?: null;
  $r['client_lastname_eff']   = $r['client_lastname_join']  ?: $r['client_lastname_email']  ?: null;
}
unset($r);

// --- Vue Mongo (optionnel) : stats par email dans collection 'reservations'
$mongoStats = [];
try {
  $collection = $dbMongo->selectCollection('reservations');
  if ($rows) {
    $emails = array_values(array_unique(array_column($rows, 'email')));
    if ($emails) {
      $cursor = $collection->find(['email' => ['$in' => $emails]]);
      foreach ($cursor as $doc) {
        $email = (string)$doc['email'];
        $count = isset($doc['reservations']) ? count($doc['reservations']) : 0;
        $last  = isset($doc['last_reservation_at']) ? $doc['last_reservation_at']->toDateTime() : null;
        $mongoStats[$email] = ['count' => $count, 'last' => $last];
      }
    }
  }
} catch (Throwable $e) {
  // si la collection n'existe pas, on ignore
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$totalPages = max(1, (int)ceil($total / $limit));
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Admin Réservations — MySQL (+ Mongo)</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{--bg:#f7f7f8;--card:#fff;--txt:#111;--muted:#666;--accent:#2563eb}
  body{font-family:system-ui,Arial,sans-serif;background:var(--bg);color:var(--txt);margin:0;padding:24px}
  h1{margin:0 0 16px}
  .bar{display:flex;gap:8px;align-items:center;margin:0 0 16px}
  .bar form{display:flex;gap:8px;flex:1}
  input[type="text"]{flex:1;padding:10px;border:1px solid #ddd;border-radius:10px}
  button{padding:10px 14px;border:1px solid #ddd;border-radius:10px;background:var(--card);cursor:pointer}
  button.primary{background:var(--accent);border-color:var(--accent);color:#fff}
  table{width:100%;border-collapse:collapse}
  th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
  th{background:#fafafa}
  .muted{color:var(--muted);font-size:12px}
  .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid #ddd;background:#fafafa}
  .pag{display:flex;gap:6px;margin-top:12px}
  .pag a, .pag span{padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#fff;text-decoration:none;color:inherit}
  .pag .active{background:var(--accent);border-color:var(--accent);color:#fff}
  pre{white-space:pre-wrap;word-break:break-word;margin:0}
</style>
</head>
<body>

<h1>Réservations — MySQL ↔ Clients (+ Mongo)</h1>

<div class="bar">
  <form method="get">
    <input type="text" name="q" placeholder="Rechercher (nom, email, adresse...)" value="<?=h($q)?>">
    <button class="primary" type="submit">Rechercher</button>
    <?php if ($q!==''): ?><a href="admin_reservations.php"><button type="button">Réinitialiser</button></a><?php endif; ?>
  </form>
  <span class="pill"><?= (int)$total ?> résultat(s)</span>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Client</th>
        <th>Coordonnées</th>
        <th>Trajet</th>
        <th>Pickup</th>
        <th>Pass./Bag.</th>
        <th>Notes</th>
        <th>Status</th>
        <th>Client lié</th>
        <th>Mongo</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): 
      $ms = $mongoStats[$r['email']] ?? null;
    ?>
      <tr>
        <td class="muted">
          <?= (int)$r['id'] ?><br>
          <span class="muted"><?= h($r['created_at']) ?></span>
        </td>
        <td><strong><?= h($r['firstname'].' '.$r['lastname']) ?></strong></td>
        <td><?= h($r['email']) ?><br><span class="muted"><?= h($r['phone']) ?></span></td>
        <td><?= h($r['start_address']) ?><br>→ <?= h($r['end_address']) ?></td>
        <td><?= h($r['pickup_date'].' '.$r['pickup_time']) ?></td>
        <td><?= (int)$r['passengers'] ?> / <?= (int)$r['luggage'] ?></td>
        <td><pre><?= h($r['notes']) ?></pre></td>
        <td><?= h($r['status']) ?></td>
        <td>
          <?php if ($r['client_id_effective']): ?>
            <span class="pill">client_id #<?= (int)$r['client_id_effective'] ?></span><br>
            <span class="muted"><?= h(trim(($r['client_firstname_eff'] ?? '').' '.($r['client_lastname_eff'] ?? ''))) ?></span>
          <?php else: ?>
            <span class="pill">Aucun</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($ms): ?>
            <span class="pill"><?= (int)$ms['count'] ?> doc(s)</span><br>
            <?php if ($ms['last']): ?><span class="muted">Dernier: <?= $ms['last']->format('Y-m-d H:i') ?></span><?php endif; ?>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="10" class="muted">Aucune réservation trouvée</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <?php
  $totalPages = max(1, (int)ceil($total / $limit));
  if ($totalPages > 1): ?>
    <div class="pag">
      <?php for ($p=1;$p<=$totalPages;$p++):
        $url = 'admin_reservations.php?'.http_build_query(['q'=>$q,'page'=>$p]); ?>
        <?php if ($p == $page): ?>
          <span class="active"><?= $p ?></span>
        <?php else: ?>
          <a href="<?= h($url) ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
