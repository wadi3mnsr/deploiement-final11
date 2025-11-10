<?php
require __DIR__ . '/vendor/autoload.php';

// Charge les variables .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connexion à Mongo
$uri = "mongodb://{$_ENV['MONGO_USER']}:{$_ENV['MONGO_PASS']}@{$_ENV['MONGO_HOST']}:27017/?authSource=admin";
$client = new MongoDB\Client($uri);

// Sélection de la base
$db = $client->selectDatabase($_ENV['MONGO_DB']);

// Test : création d’un document
$collection = $db->selectCollection('test_collection');
$result = $collection->insertOne([
    'hello' => 'world',
    'timestamp' => new MongoDB\BSON\UTCDateTime()
]);

echo "✅ Document inséré avec l’ID : " . $result->getInsertedId() . PHP_EOL;

// Test : lecture
$doc = $collection->findOne();
 "📌 Document trouvé : " . json_encode($doc, JSON_PRETTY_PRINT) . PHP_EOL;



//note : docker exec -it flexvtc_app bash
//php /var/www/html/test_mongo.php

