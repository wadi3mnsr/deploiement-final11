<?php
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../config/database.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
  http_response_code(400);
  echo "Requête invalide.";
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed = ['new','confirmed','done','canceled'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
  http_response_code(400);
  echo "Paramètres invalides.";
  exit;
}

$stmt = $pdo->prepare("UPDATE reservations SET status = :s WHERE id = :id");
$stmt->execute([':s' => $status, ':id' => $id]);

header('Location: /admin/index.php');
exit;
