<?php
declare(strict_types=1);

// Autoload Composer (vendor installé dans /var/www/vendor)
require __DIR__ . '/../vendor/autoload.php';

// Charger les connexions (chaque config inclut déjà app.php une seule fois)
$pdo     = require __DIR__ . '/config/database.php';
$dbMongo = require __DIR__ . '/config/mongo.php';

use MongoDB\BSON\UTCDateTime;

echo "<h1> Test connexions DB</h1>";

// ==========================
// 1) Test MySQL
// ==========================
echo "<h2>MySQL</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_clients FROM clients");
    $row = $stmt->fetch();
    echo "✅ Connexion MySQL OK<br>";
    echo "Nombre de clients : " . (int)$row['total_clients'] . "<br>";
} catch (Throwable $e) {
    echo "❌ Erreur MySQL : " . htmlspecialchars($e->getMessage()) . "<br>";
}

// ==========================
// 2) Test MongoDB
// ==========================
echo "<h2>MongoDB</h2>";
try {
    $collection = $dbMongo->selectCollection('test_collection');

    // Insertion d'un doc de test
    $insert = $collection->insertOne([
        'hello' => 'world',
        'ts' => new UTCDateTime()
    ]);

    // Lecture du doc
    $doc = $collection->findOne(['_id' => $insert->getInsertedId()]);

    echo "✅ Connexion Mongo OK<br>";
    echo "Doc inséré et retrouvé : <pre>" . json_encode($doc, JSON_PRETTY_PRINT) . "</pre>";
} catch (Throwable $e) {
    echo "❌ Erreur MongoDB : " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr><p>Test terminé.</p>";



//-- Lister tous les contacts avec éventuel rattachement client
//SELECT c.id AS contact_id,
  //     c.firstname, c.lastname, c.email, c.message,
    //   cl.id AS client_id, cl.firstname AS client_firstname
//FROM contacts c
//LEFT JOIN clients cl ON c.email = cl.email
//ORDER BY c.created_at DESC
//LIMIT 10;
