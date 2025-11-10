<?php
// Générateur PDF ultra-simple (1 page, texte) – pour bons de commande.
// Pas de lib externe. Gère l'encodage basique via Windows-1252.
function pdf_escape_text($s){
  // Convertit UTF-8 -> Windows-1252 (translit) et échappe les parentheses et backslashes
  $s = iconv('UTF-8','Windows-1252//TRANSLIT', $s);
  return str_replace(['\\','(',')'], ['\\\\','\\(', '\\)'], $s);
}

/**
 * Crée un PDF texte simple.
 * @param array $lines  Liste de lignes [ ['text'=>"...", 'x'=>72, 'y'=>770, 'size'=>12], ... ]
 * @param string $filepath  Chemin de sortie
 * @param array $opts  ['title'=>"...", 'author'=>"...", 'subject'=>"..." ]
 */
function pdf_create_simple_text(array $lines, string $filepath, array $opts = []){
  // PDF objects
  $objects = [];
  $offsets = [];

  $buf = "%PDF-1.4\n";

  // 1) Font (Helvetica)
  $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

  // 2) Content stream
  // On part du coin bas-gauche. A4: 595 x 842 pts (72 dpi)
  $content = "BT\n/F1 12 Tf\n";
  foreach($lines as $ln){
    $txt  = pdf_escape_text($ln['text'] ?? '');
    $x    = isset($ln['x']) ? (float)$ln['x'] : 72;
    $y    = isset($ln['y']) ? (float)$ln['y'] : 770;
    $size = isset($ln['size']) ? (int)$ln['size'] : 12;
    $content .= "/F1 {$size} Tf\n";
    $content .= sprintf("%.2f %.2f Td (%s) Tj\n", $x, $y, $txt);
  }
  $content .= "ET\n";
  $content_stream = "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream";
  $objects[] = $content_stream;

  // 3) Page
  $page = "<< /Type /Page /Parent 4 0 R /MediaBox [0 0 595 842] /Contents 2 0 R /Resources << /Font << /F1 1 0 R >> >> >>";
  $objects[] = $page;

  // 4) Pages
  $pages = "<< /Type /Pages /Count 1 /Kids [3 0 R] >>";
  $objects[] = $pages;

  // 5) Info
  $title   = isset($opts['title'])   ? pdf_escape_text($opts['title'])   : 'Document';
  $author  = isset($opts['author'])  ? pdf_escape_text($opts['author'])  : 'FlexVTC';
  $subject = isset($opts['subject']) ? pdf_escape_text($opts['subject']) : 'Bon de commande';
  $info = "<< /Title ({$title}) /Author ({$author}) /Subject ({$subject}) /Producer (FlexVTC PDF) >>";
  $objects[] = $info;

  // 6) Catalog
  $catalog = "<< /Type /Catalog /Pages 4 0 R >>";
  $objects[] = $catalog;

  // Ecriture objets
  foreach($objects as $i => $obj){
    $offsets[$i+1] = strlen($buf);
    $buf .= ($i+1)." 0 obj\n".$obj."\nendobj\n";
  }

  // xref
  $xref_pos = strlen($buf);
  $buf .= "xref\n0 ".(count($objects)+1)."\n";
  $buf .= "0000000000 65535 f \n";
  for($i=1; $i<=count($objects); $i++){
    $buf .= sprintf("%010d 00000 n \n", $offsets[$i]);
  }

  // trailer
  $buf .= "trailer\n<< /Size ".(count($objects)+1)." /Root ".count($objects)." 0 R /Info 5 0 R >>\nstartxref\n".$xref_pos."\n%%EOF";

  // Sauvegarde
  if(!is_dir(dirname($filepath))) @mkdir(dirname($filepath), 0777, true);
  file_put_contents($filepath, $buf);
}
