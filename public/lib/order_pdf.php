<?php
require_once __DIR__ . '/pdf_simple.php';

/**
 * Génère un bon de commande PDF et retourne le chemin du fichier créé.
 * @param array $data  ex: [
 *   'id'=>123, 'date'=>'2025-09-11 14:30', 'client_name'=>'Wadii Mansouri', 'client_email'=>'x@x',
 *   'from'=>'Adresse départ', 'to'=>'Adresse arrivée', 'price'=>42.50, 'distance_km'=>15.2, 'duration_min'=>28, 'option'=>'berline'
 * ]
 * @return string $filepath
 */
function generate_bon_commande_pdf(array $data): string {
  $id    = (int)($data['id'] ?? 0);
  $date  = $data['date'] ?? '';
  $name  = $data['client_name'] ?? '';
  $email = $data['client_email'] ?? '';
  $from  = $data['from'] ?? '';
  $to    = $data['to'] ?? '';
  $price = isset($data['price']) ? number_format((float)$data['price'], 2, ',', ' ') : '—';
  $dist  = isset($data['distance_km']) ? number_format((float)$data['distance_km'], 2, ',', ' ') : '—';
  $dur   = isset($data['duration_min']) ? (int)$data['duration_min'] : null;
  $opt   = $data['option'] ?? '—';

  // Mise en page (coordonnées en points)
  $y = 790; $lh = 18; $xL = 72; $xR = 360;
  $lines = [
    ['text'=>"FlexVTC",                        'x'=>$xL, 'y'=>$y,     'size'=>18],
    ['text'=>"Bon de commande #$id",           'x'=>$xL, 'y'=>$y-30,  'size'=>16],
    ['text'=>"Date : $date",                   'x'=>$xL, 'y'=>$y-30-$lh, 'size'=>12],

    ['text'=>"Client :",                       'x'=>$xL, 'y'=>$y-30-$lh*3, 'size'=>12],
    ['text'=>"$name",                          'x'=>$xL, 'y'=>$y-30-$lh*4, 'size'=>12],
    ['text'=>"$email",                         'x'=>$xL, 'y'=>$y-30-$lh*5, 'size'=>12],

    ['text'=>"Trajet",                         'x'=>$xL, 'y'=>$y-30-$lh*7, 'size'=>14],
    ['text'=>"Départ : $from",                 'x'=>$xL, 'y'=>$y-30-$lh*8, 'size'=>12],
    ['text'=>"Arrivée : $to",                  'x'=>$xL, 'y'=>$y-30-$lh*9, 'size'=>12],
    ['text'=>"Distance : $dist km",            'x'=>$xL, 'y'=>$y-30-$lh*10, 'size'=>12],
    ['text'=>"Durée estimée : ".($dur!==null?"$dur min":"—"), 'x'=>$xL, 'y'=>$y-30-$lh*11, 'size'=>12],
    ['text'=>"Option : $opt",                  'x'=>$xL, 'y'=>$y-30-$lh*12, 'size'=>12],

    ['text'=>"Total TTC : $price €",           'x'=>$xR, 'y'=>$y-30-$lh*12, 'size'=>14],
    ['text'=>"Merci pour votre confiance.",    'x'=>$xL, 'y'=>90, 'size'=>12],
    ['text'=>"— FlexVTC (document automatique, ne pas répondre) —", 'x'=>$xL, 'y'=>70, 'size'=>10],
  ];

  $dir = __DIR__ . '/../tmp/orders';
  $file = $dir . "/bon-commande-{$id}.pdf";
  pdf_create_simple_text($lines, $file, [
    'title'   => "Bon de commande #$id",
    'author'  => 'FlexVTC',
    'subject' => 'Réservation confirmée'
  ]);
  return $file;
}
