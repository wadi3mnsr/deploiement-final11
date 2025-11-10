<?php
declare(strict_types=1);

// Autoload + connexions (s'appuient sur config/app.php)
require __DIR__ . '/../vendor/autoload.php';
$pdo     = require __DIR__ . '/config/database.php';
$dbMongo = require __DIR__ . '/config/mongo.php';

// --- Params UI ---
$q     = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$off   = ($page - 1) * $limit;

// --- Requête MySQL : contacts + client_id par email ---
$where = '';
$args  = [];
if ($q !== '') {
  $where = "WHERE c.email LIKE :q OR c.firstname LIKE :q OR c.lastname LIKE :q";
  $args[':q'] = "%{$q}%";
}

$countSql = "SELECT COUNT(*) FROM contacts c " . $where;
$stmt = $pdo->prepare($countSql);
$stmt->execute($args);
$total = (int)$stmt->fetchColumn();

$sql = "
SELECT
  c.id AS contact_id,
  c.created_at,
  c.firstname, c.lastname, c.email, c.phone, c.message,
  cl.id AS client_id,
  cl.firstname AS client_firstname, cl.lastname AS client_lastname
FROM contacts c
LEFT JOIN clients cl ON c.email = cl.email
{$where}
ORDER BY c.created_at DESC
LIMIT :limit OFFSET :off
";
$stmt = $pdo->prepare($sql);
foreach ($args as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $off, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// --- Côté Mongo : stats par email (count + last_message_at) ---
$collection = $dbMongo->selectCollection('contacts');
$mongoStats = []; // email => ['count'=>n, 'last'=>DateTime, 'client_id'=>x]
if ($rows) {
  $emails = array_values(array_unique(array_column($rows, 'email')));
  $cursor = $collection->find(
    ['email' => ['$in' => $emails]],
    ['projection' => ['email'=>1, 'client_id'=>1, 'last_message_at'=>1, 'messages'=>1]]
  );
  foreach ($cursor as $doc) {
    $email = (string)$doc['email'];
    $count = isset($doc['messages']) ? count($doc['messages']) : 0;
    $last  = isset($doc['last_message_at']) ? $doc['last_message_at']->toDateTime() : null;
    $mongoStats[$email] = [
      'count' => $count,
      'last'  => $last,
      'client_id' => $doc['client_id'] ?? null,
    ];
  }
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$totalPages = max(1, (int)ceil($total / $limit));
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Admin Contacts — MySQL + Mongo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{--bg:#f7f7f8;--card:#fff;--txt:#111;--muted:#666;--accent:#2563eb;--ok:#16a34a;--warn:#d97706}
  *{box-sizing:border-box}
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--txt);margin:0;padding:24px}
  h1{margin:0 0 16px}
  .bar{display:flex;gap:8px;align-items:center;margin:0 0 16px}
  .bar form{display:flex;gap:8px;flex:1}
  input[type="text"]{flex:1;padding:10px 12px;border:1px solid #ddd;border-radius:10px}
  button{padding:10px 14px;border:1px solid #ddd;border-radius:10px;background:var(--card);cursor:pointer}
  button.primary{background:var(--accent);border-color:var(--accent);color:#fff}
  .grid{display:grid;grid-template-columns:1.6fr .8fr;gap:16px}
  .card{background:var(--card);border:1px solid #eee;border-radius:14px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
  .card h2{font-size:16px;margin:0;padding:14px 16px;border-bottom:1px solid #eee}
  table{width:100%;border-collapse:collapse}
  th,td{padding:10px 12px;border-bottom:1px solid #f0f0f0;vertical-align:top}
  th{background:#fafafa;text-align:left;font-weight:600}
  .muted{color:var(--muted);font-size:12px}
  .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid #ddd;background:#fafafa}
  .ok{color:var(--ok)} .warn{color:var(--warn)}
  .meta{font-size:12px;color:var(--muted)}
  .flex{display:flex;align-items:center;gap:6px}
  .right{display:flex;gap:8px;align-items:center}
  .pag{display:flex;gap:6px;margin-top:12px}
  .pag a, .pag span{padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#fff;text-decoration:none;color:inherit}
  .pag .active{background:var(--accent);border-color:var(--accent);color:#fff}
  @media (max-width:1000px){.grid{grid-template-columns:1fr}}
  pre{white-space:pre-wrap;word-break:break-word;margin:6px 0 0}
</style>
</head>
<body>

<h1>Contacts — MySQL ↔ Clients + Mongo</h1>

<div class="bar">
  <form method="get">
    <input type="text" name="q" placeholder="Rechercher par email / prénom / nom" value="<?=h($q)?>">
    <button class="primary" type="submit">Rechercher</button>
    <?php if ($q!==''): ?><a href="admin_contacts.php"><button type="button">Réinitialiser</button></a><?php endif; ?>
  </form>
  <div class="right">
    <span class="pill"><?= (int)$total ?> résultat(s)</span>
    <a class="pill" href="test_db.php">Tester connexions</a>
  </div>
</div>

<div class="grid">
  <!-- Colonne gauche : MySQL contacts + lien clients -->
  <div class="card">
    <h2>Contacts MySQL (<?= (int)$total ?>)</h2>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Contact</th>
          <th>Email / Téléphone</th>
          <th>Message</th>
          <th>Client lié</th>
          <th>Mongo (messages)</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): 
        $email = $r['email'];
        $ms = $mongoStats[$email] ?? null;
        $mongoCount = $ms['count'] ?? 0;
        $mongoLast  = isset($ms['last']) ? $ms['last']->format('Y-m-d H:i') : '—';
        $mongoClient= $ms['client_id'] ?? null;
      ?>
        <tr>
          <td class="muted"><?= (int)$r['contact_id'] ?><br><span class="meta"><?= h((string)$r['created_at']) ?></span></td>
          <td><strong><?= h($r['firstname'].' '.$r['lastname']) ?></strong></td>
          <td>
            <div><?= h($email) ?></div>
            <div class="muted"><?= h($r['phone']) ?></div>
          </td>
          <td><pre><?= h($r['message']) ?></pre></td>
          <td>
            <?php if ($r['client_id']): ?>
              <div class="flex"><span class="pill ok">client_id #<?= (int)$r['client_id'] ?></span></div>
              <div class="meta"><?= h($r['client_firstname'].' '.$r['client_lastname']) ?></div>
            <?php else: ?>
              <span class="pill">Aucun (par email)</span>
            <?php endif; ?>
          </td>
          <td>
            <div>Mongo docs: <strong><?= (int)$mongoCount ?></strong></div>
            <div class="meta">Dernier: <?= h($mongoLast) ?></div>
            <?php if ($mongoClient): ?>
              <div class="meta ok">client_id Mongo: <?= (int)$mongoClient ?></div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="muted">Aucun résultat</td></tr>
      <?php endif; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="pag">
        <?php for ($p=1;$p<=$totalPages;$p++):
          $url = 'admin_contacts.php?'.http_build_query(['q'=>$q,'page'=>$p]); ?>
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
