<?php
/**
 * Envoi simple HTML via MailHog (dev)
 * - UI : http://localhost:8025
 */
if (!function_exists('send_smtp_basic')) {
function send_smtp_basic($to, $subject, $htmlBody, $from = 'no-reply@flexvtc.local') {
  $host = 'mailhog'; $port = 1025; $timeout = 5;
  $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
  if (!$socket) return false;

  $read = function() use ($socket) { return fgets($socket, 2048); };
  $cmd  = function($line) use ($socket, $read) { fputs($socket, $line . "\r\n"); return $read(); };

  $read();                       // banner
  $cmd("HELO localhost");
  $cmd("MAIL FROM:<$from>");
  $cmd("RCPT TO:<$to>");
  $cmd("DATA");

  $headers  = "From: $from\r\n";
  $headers .= "To: $to\r\n";
  $headers .= "Subject: $subject\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

  fputs($socket, $headers . "\r\n" . $htmlBody . "\r\n.\r\n");
  $read();
  $cmd("QUIT");
  fclose($socket);
  return true;
}}


/**
 * Envoi HTML + pièce jointe PDF via MailHog (dev)
 */
function send_smtp_with_attachment($to, $subject, $htmlBody, $attachmentPath, $attachmentName = 'document.pdf', $from = 'no-reply@flexvtc.local') {
  if (!is_readable($attachmentPath)) return false;

  $host = 'mailhog'; $port = 1025; $timeout = 5;
  $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
  if (!$socket) return false;

  $read = function() use ($socket) { return fgets($socket, 2048); };
  $cmd  = function($line) use ($socket, $read) { fputs($socket, $line . "\r\n"); return $read(); };

  $boundary = "=_flexvtc_" . bin2hex(random_bytes(8));
  $fileData = chunk_split(base64_encode(file_get_contents($attachmentPath)));

  $read();                       // banner
  $cmd("HELO localhost");
  $cmd("MAIL FROM:<$from>");
  $cmd("RCPT TO:<$to>");
  $cmd("DATA");

  $headers  = "From: $from\r\n";
  $headers .= "To: $to\r\n";
  $headers .= "Subject: $subject\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

  $body  = "--$boundary\r\n";
  $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
  $body .= $htmlBody . "\r\n";
  $body .= "--$boundary\r\n";
  $body .= "Content-Type: application/pdf; name=\"$attachmentName\"\r\n";
  $body .= "Content-Transfer-Encoding: base64\r\n";
  $body .= "Content-Disposition: attachment; filename=\"$attachmentName\"\r\n\r\n";
  $body .= $fileData . "\r\n";
  $body .= "--$boundary--\r\n.\r\n";

  fputs($socket, $headers . "\r\n" . $body);
  $read();
  $cmd("QUIT");
  fclose($socket);
  return true;
}


/**
 * Envoi RÉEL via Gmail (TLS implicite port 465) avec pièce jointe PDF
 * Prérequis: config/email.php (non versionné) avec:
 *   define('GMAIL_USER', 'ton.email@gmail.com');
 *   define('GMAIL_APP_PASSWORD', 'xxxxxxxxxxxxxxxx'); // mot de passe d'application
 *   define('GMAIL_FROM_EMAIL', 'no-reply@flexvtc.fr'); // ou GMAIL_USER
 *   define('GMAIL_FROM_NAME', 'FlexVTC');
 */
function send_smtp_gmail_with_attachment($to, $subject, $htmlBody, $attachmentPath, $attachmentName = 'document.pdf') {
  // ⚠️ Chemin depuis /public/lib/ vers /public/../config/
  require_once __DIR__ . '/../config/email.php';

  if (!is_readable($attachmentPath)) return false;

  // TLS implicite (pas de STARTTLS nécessaire)
  $host = 'ssl://smtp.gmail.com';
  $port = 465;
  $timeout = 15;

  $socket = @stream_socket_client("{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
  if (!$socket) return false;

  $read = function() use ($socket){ return fgets($socket, 4096); };
  $cmd  = function($line) use ($socket, $read){ fwrite($socket, $line . "\r\n"); return $read(); };

  $read(); // banner
  $cmd("EHLO localhost");

  // Auth LOGIN
  $cmd("AUTH LOGIN");
  $cmd(base64_encode(GMAIL_USER));
  $cmd(base64_encode(GMAIL_APP_PASSWORD));

  $fromEmail = defined('GMAIL_FROM_EMAIL') && GMAIL_FROM_EMAIL ? GMAIL_FROM_EMAIL : GMAIL_USER;
  $fromName  = defined('GMAIL_FROM_NAME')  && GMAIL_FROM_NAME  ? GMAIL_FROM_NAME  : 'FlexVTC';

  $boundary = "=_flexvtc_" . bin2hex(random_bytes(8));
  $fileData = chunk_split(base64_encode(file_get_contents($attachmentPath)));

  // MAIL / RCPT
  $cmd("MAIL FROM:<{$fromEmail}>");
  $cmd("RCPT TO:<{$to}>");
  $cmd("DATA");

  // En-têtes + multipart
  $headers  = "From: {$fromName} <{$fromEmail}>\r\n";
  $headers .= "To: <{$to}>\r\n";
  $headers .= "Subject: {$subject}\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

  $body  = "--{$boundary}\r\n";
  $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
  $body .= $htmlBody . "\r\n";
  $body .= "--{$boundary}\r\n";
  $body .= "Content-Type: application/pdf; name=\"{$attachmentName}\"\r\n";
  $body .= "Content-Transfer-Encoding: base64\r\n";
  $body .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n\r\n";
  $body .= $fileData . "\r\n";
  $body .= "--{$boundary}--\r\n.\r\n";

  fwrite($socket, $headers . "\r\n" . $body);
  $read(); // réponse DATA
  $cmd("QUIT");
  fclose($socket);
  return true;
}
