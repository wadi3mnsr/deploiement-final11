<?php
// reserv.php
require __DIR__ . '/lib/auth.php';

// 🔒 Si non connecté → mémorise l'URL puis redirige vers /login.php
if (!is_client_logged()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/reserv.php';
    header('Location: /login.php');
    exit;
}

$me = current_client();

// CSRF token (spécifique à la réservation)
if (empty($_SESSION['csrf_reservation'])) {
    $_SESSION['csrf_reservation'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_reservation'];

include __DIR__ . '/includes/header.php';
?>

<section class="hero hero-reservation">
  <div class="container">
    <h1>Réserver votre trajet</h1>
    <p class="lead">Indiquez votre départ et votre arrivée, l’itinéraire et le prix s’affichent.</p>
  </div>
</section>

<section class="container">
  <form id="booking-form" class="form-grid" method="post" action="reservation_save.php" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

    <fieldset class="form-grid">
      <legend>Votre trajet</legend>

      <label>Adresse de départ
        <input type="text" name="from" id="from" required placeholder="Ex : 1 Rue de Strasbourg, Nantes" autocomplete="off">
      </label>

      <label>Adresse d’arrivée
        <input type="text" name="to" id="to" required placeholder="Ex : Aéroport Nantes Atlantique" autocomplete="off">
      </label>

      <div class="grid-2">
        <label>Date
          <input type="date" name="date" required>
        </label>
        <label>Heure
          <input type="time" name="time" required>
        </label>
      </div>

      <label>Options
        <select name="option" id="option">
          <option value="">Aucune</option>
          <option value="seat">Siège enfant (+3€)</option>
          <option value="van">Van (+15€)</option>
          <option value="xl">Bagage XL (+5€)</option>
        </select>
      </label>

      <div class="grid-2">
        <label>Prénom
          <input type="text" name="firstname" required value="<?= htmlspecialchars($me['firstname'] ?? '') ?>" readonly>
        </label>
        <label>Nom
          <input type="text" name="lastname" required value="<?= htmlspecialchars($me['lastname'] ?? '') ?>" readonly>
        </label>
      </div>
      <div class="grid-2">
        <label>Téléphone
          <input type="tel" name="phone" required value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
        </label>
        <label>Email
          <input type="email" name="email" required value="<?= htmlspecialchars($me['email'] ?? '') ?>" readonly>
        </label>
      </div>
      <label>Commentaires
        <textarea name="notes" rows="3" placeholder="Infos vol, digicode, bagages, etc."></textarea>
      </label>

      <!-- Champs cachés remplis par le calcul (pour sauvegarder en base) -->
      <input type="hidden" name="distance" id="distance_hidden">
      <input type="hidden" name="duration" id="duration_hidden">
      <input type="hidden" name="price_estimate" id="price_hidden">
      <input type="hidden" name="from_lat" id="from_lat">
      <input type="hidden" name="from_lng" id="from_lng">
      <input type="hidden" name="to_lat" id="to_lat">
      <input type="hidden" name="to_lng" id="to_lng">
    </fieldset>

    <!-- Carte -->
    <div class="form-grid">
      <label>Carte</label>
      <div id="map" aria-label="Carte de réservation" style="height:420px;border-radius:var(--radius);border:1px solid var(--border);"></div>

      <div class="grid-2" style="align-items:center;">
        <button class="btn" type="button" id="route-btn">Tracer l’itinéraire & calculer</button>

        <!-- Résultats -->
        <div class="route-results" id="route-results" hidden style="display:flex;gap:1rem;flex-wrap:wrap;">
          <div class="stat">
            <span class="label">Distance</span>
            <span class="value" id="stat-distance">—</span>
          </div>
          <div class="stat">
            <span class="label">Durée</span>
            <span class="value" id="stat-duration">—</span>
          </div>
          <div class="stat">
            <span class="label">Prix estimé</span>
            <span class="value" id="stat-price">—</span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid-2">
      <button class="btn btn-primary" type="submit">Envoyer ma réservation</button>
      <button class="btn" type="reset" id="reset-btn">Annuler</button>
    </div>

    <p class="form-hint">
      Entrez les adresses puis cliquez « Tracer l’itinéraire & calculer ». Vous pouvez ajuster les options pour mettre à jour le prix.
    </p>
  </form>
</section>

<script>
// === Mini logique front pour empêcher l'envoi sans calcul ===
(function(){
  const form = document.getElementById('booking-form');
  const calcBtn = document.getElementById('route-btn');
  const results = document.getElementById('route-results');
  const statDistance = document.getElementById('stat-distance');
  const statDuration = document.getElementById('stat-duration');
  const statPrice    = document.getElementById('stat-price');

  const distanceHidden = document.getElementById('distance_hidden');
  const durationHidden = document.getElementById('duration_hidden');
  const priceHidden    = document.getElementById('price_hidden');

  const fromLat = document.getElementById('from_lat');
  const fromLng = document.getElementById('from_lng');
  const toLat   = document.getElementById('to_lat');
  const toLng   = document.getElementById('to_lng');

  const fromInput = document.getElementById('from');
  const toInput   = document.getElementById('to');
  const optionSel = document.getElementById('option');
  const resetBtn  = document.getElementById('reset-btn');

  // TODO: remplace par ton propre calcul/routeur si tu as une API (OSRM, GraphHopper…)
  function fakeGeocode(address){
    // Faux géocodage : retourne une coordonnée pseudo-aléatoire pour démonstration
    const base = address.length || 1;
    return [ -1.55 + (base % 50) * 0.001, 47.21 + (base % 50) * 0.001 ]; // [lng, lat] proche Nantes
  }
  function fakeRoute(from, to){
    // distance en km + durée en min, calcul bidon pour l'exemple
    const dx = (from[0]-to[0]) * 85; // approx km/deg lon à cette latitude
    const dy = (from[1]-to[1]) * 111; // km/deg lat
    const dist = Math.max(1, Math.hypot(dx, dy)); // km
    const durationMin = Math.round(dist / 35 * 60); // 35 km/h moyenne urbaine
    return { distanceKm: Math.round(dist*10)/10, durationMin };
  }
  function computePrice(distanceKm, option){
    // Barème simple : prise en charge 4€, 1.3€/km, options : seat +3, van +15, xl +5
    let price = 4 + distanceKm * 1.3;
    if (option === 'seat') price += 3;
    if (option === 'van')  price += 15;
    if (option === 'xl')   price += 5;
    return Math.round(price * 100) / 100;
  }

  // Init MapLibre très simple (carte muette, sans routing réel)
  let map;
  try {
    map = new maplibregl.Map({
      container: 'map',
      style: 'https://demotiles.maplibre.org/style.json',
      center: [-1.5536, 47.2184], // Nantes
      zoom: 11
    });
    map.addControl(new maplibregl.NavigationControl());
  } catch(e){ /* ignore si maplibre non chargé */ }

  calcBtn.addEventListener('click', function(){
    const from = fromInput.value.trim();
    const to   = toInput.value.trim();
    if (!from || !to) {
      alert('Merci de saisir une adresse de départ et d’arrivée.');
      return;
    }

    // (Remplace par ton vrai géocoder)
    const fromLL = fakeGeocode(from); // [lng, lat]
    const toLL   = fakeGeocode(to);

    fromLng.value = fromLL[0];
    fromLat.value = fromLL[1];
    toLng.value   = toLL[0];
    toLat.value   = toLL[1];

    const r = fakeRoute(fromLL, toLL);
    const price = computePrice(r.distanceKm, optionSel.value);

    distanceHidden.value = r.distanceKm;
    durationHidden.value = r.durationMin;
    priceHidden.value    = price;

    statDistance.textContent = r.distanceKm + ' km';
    statDuration.textContent = r.durationMin + ' min';
    statPrice.textContent    = price.toFixed(2) + ' €';

    results.hidden = false;

    // Zoom carte (si disponible)
    if (map) {
      const bounds = new maplibregl.LngLatBounds();
      bounds.extend(fromLL).extend(toLL);
      map.fitBounds(bounds, { padding: 60, duration: 400 });
      // Ajout marqueurs basiques
      new maplibregl.Marker().setLngLat(fromLL).addTo(map);
      new maplibregl.Marker().setLngLat(toLL).addTo(map);
    }
  });

  // Empêche l'envoi si pas de calcul effectué
  form.addEventListener('submit', function(e){
    if (!distanceHidden.value || !durationHidden.value || !priceHidden.value) {
      e.preventDefault();
      alert('Merci de cliquer sur « Tracer l’itinéraire & calculer » avant d’envoyer.');
    }
  });

  // Reset : efface résultats
  resetBtn.addEventListener('click', function(){
    results.hidden = true;
    statDistance.textContent = '—';
    statDuration.textContent = '—';
    statPrice.textContent = '—';
    distanceHidden.value = '';
    durationHidden.value = '';
    priceHidden.value = '';
    fromLat.value = fromLng.value = toLat.value = toLng.value = '';
  });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
