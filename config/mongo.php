<?php
/**
 * config/mongo.php
 * Connexion MongoDB via l’extension PHP + mongodb/mongodb
 */

require_once __DIR__ . '/app.php';

try {
    // URI MongoDB
    $uri = sprintf(
        'mongodb://%s:%s@%s:27017/?authSource=admin',
        $_ENV['MONGO_USER'],
        $_ENV['MONGO_PASS'],
        $_ENV['MONGO_HOST']
    );

    // Client Mongo
    $client = new MongoDB\Client($uri);

    // Sélection de la base
    $db = $client->selectDatabase($_ENV['MONGO_DB']);

    // Index utiles (idempotents)
    $contacts = $db->selectCollection('contacts');
    $contacts->createIndex(['email' => 1]);
    $contacts->createIndex(['client_id' => 1]);
    $contacts->createIndex(['last_message_at' => -1]);

} catch (Throwable $e) {
    if (APP_DEBUG) {
        die("❌ Erreur connexion MongoDB : " . $e->getMessage());
    } else {
        error_log("[MongoDB] " . $e->getMessage());
        http_response_code(500);
        die("Erreur de connexion à la base MongoDB.");
    }
}

return $db;
