<?php
require __DIR__ . '/lib/send_smtp.php';

// ⚠️ Mets ici ton adresse Gmail (celle de GMAIL_USER pour bien tester)
$to = 'wadiimansouri@gmail.com';
$subject = "Test envoi Gmail SMTP depuis FlexVTC";
$body = "<p>✅ Ceci est un test d'envoi SMTP via Gmail.</p><p>Si tu vois ce message, la configuration est correcte !</p>";

// Génère un petit PDF de test
$pdfPath = __DIR__ . '/tmp/test-mail.pdf';
if (!is_dir(dirname($pdfPath))) { mkdir(dirname($pdfPath), 0777, true); }
file_put_contents($pdfPath, "%PDF-1.4\n%Test\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

$result = send_smtp_gmail_with_attachment($to, $subject, $body, $pdfPath, "test-mail.pdf");

if ($result) {
    echo "✅ Email envoyé avec succès à $to (vérifie ta boîte Gmail).";
} else {
    echo "❌ Échec de l'envoi.";
}
