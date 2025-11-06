<?php
// Minimal PHP wrapper - change or remove includes as needed
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ticket Rates — Mothers Wonderland</title>

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.4/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#0B9FCF;
      --accent:#A8FC59;
    }
    body { background:#fff; color:#111; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
    .hero {
      background: linear-gradient(0deg, rgba(0,0,0,0.46), rgba(0,0,0,0.46)), var(--accent);
      color:#fff;
    }
    .card-img-placeholder { background:#e9ecef; height:200px; display:flex; align-items:center; justify-content:center; color:#6c757d; }
    .rounded-pill-shadow { border-radius:9999px; box-shadow:0 6px 4px rgba(0,0,0,0.12); }
    .price-badge { font-size:1.5rem; font-weight:700; }
    footer { background:#f8f9fa; }
    .muted-sm { color: rgba(0,0,0,0.65); font-size:0.95rem; }
  </style>
</head>
<body>

<?php include 'topbar-user.php'; ?>
 
<?php
// changed code: load subscriptions dynamically
require_once __DIR__ . '/database/function.php';
$db = new DBFunctions();
// changed code: exclude type = 1 (venues) from rates
$subsRes = $db->custom('subscriptions', '*', [], "type IS NULL OR type <> '1' ORDER BY id ASC");
$subscriptions = [];
if ($subsRes['status'] === 'success') {
    $subscriptions = $subsRes['data'];
}
?>
<!-- Hero -->
<section class="hero py-5">
  <div class="container py-4">
    <div class="row align-items-center">
      <div class="col-lg-7 text-lg-start text-center">
        <h1 class="display-6 fw-bold">Open every Thursday through Sunday, and on holidays!</h1>
        <p class="lead mt-3">Ticket rates and inclusions. Grand Opening Promo discounts applied where indicated. All-day passes available onsite.</p>
      </div>

      <div class="col-lg-5 text-center mt-4 mt-lg-0">
        <img src="assets/images/ticket.jpg" alt="Tickets" class="img-fluid rounded shadow-sm">
      </div>
    </div>
  </div>
</section>

<!-- Notice bar -->
<section class="container my-4">
  <div class="row">
    <div class="col">
      <div class="rounded-pill-shadow bg-white p-3 d-flex align-items-center justify-content-between">
        <div>
          <strong class="me-2">Note:</strong>
          <span class="muted-sm">*Grand Opening Promo discount already applied to Adult & Senior/PWD Day Passes. Kids below 3ft enter free.</span>
        </div>
        <div>
          <a href="contact.php" class="btn btn-outline-secondary btn-sm">Need help?</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
function parse_price_string($price) {
    $out = [];
    if (!$price) return $out;
    foreach (explode(',', $price) as $part) {
        $p = explode(':', $part, 2);
        if (count($p) === 2) $out[trim($p[0])] = trim($p[1]);
    }
    return $out;
}

// changed code: fetch latest featured news (promo)
$promoRes = $db->custom('news', '*', [], "featured = 1 ORDER BY created_at DESC LIMIT 1");
$promo = null;
$promoDiscount = [];
if ($promoRes['status'] === 'success' && !empty($promoRes['data'][0])) {
    $promo = $promoRes['data'][0];
    if (!empty($promo['discount'])) {
        $promoDiscount = parse_price_string($promo['discount']);
    }
}
?>
<!-- Rates cards -->
<section id="rates" class="container mb-5">
  <div class="row g-4">
    <?php if (empty($subscriptions)): ?>
      <div class="col-12">
        <div class="alert alert-warning">No rate information available.</div>
      </div>
    <?php else: ?>
      <?php foreach ($subscriptions as $sub): 
          $priceObj = parse_price_string($sub['price'] ?? '');
          $next = 'subscribe.php?subscription_id=' . urlencode($sub['id']);
          $loginHref = 'login.php?next=' . urlencode($next);
      ?>
        <div class="col-lg-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body text-center d-flex flex-column">
              <div class="mb-3">
                <img src="<?= !empty($sub['image']) ? 
   'assets/images/subscriptions/' . htmlspecialchars($sub['image']) : 
   'https://placehold.co/210x210' ?>" alt="<?=htmlspecialchars($sub['name'])?>" class="img-fluid rounded">
              </div>
              <h5 class="card-title"><?=htmlspecialchars($sub['name'])?></h5>
              <?php if (!empty($priceObj)): ?>
                <ul class="list-unstyled small text-start mb-3">
                  <?php foreach ($priceObj as $label => $amt): ?>
                    <li><strong><?=htmlspecialchars($label)?>:</strong>
                      <?= is_numeric($amt) ? '₱' . number_format((float)$amt, 2) : htmlspecialchars($amt) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div class="mb-3 text-success">Contact for pricing</div>
              <?php endif; ?>

              <ul class="list-unstyled text-start small mb-3">
                <li><strong>Pax:</strong> <?=htmlspecialchars($sub['pax'] ?? '1')?></li>
              </ul>

              <div class="mt-auto">
                <a href="<?=htmlspecialchars($loginHref)?>" class="btn btn-danger rounded-pill px-4">Book Now</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- Ticket inclusions & activities -->
<section id="inclusions" class="container mb-5">
  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h4 class="mb-3">Ticket Inclusions</h4>
          <p class="muted-sm">All day passes include general access to attractions listed below. Specific rides or activities may have separate reservation or height requirements.</p>

          <div class="accordion" id="inclusionsAccord">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                  Included Attractions (summary)
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#inclusionsAccord">
                <div class="accordion-body small">
                  <ul>
                    <li>GAIA Peak viewing platform</li>
                    <li>Zipline (one-time)</li>
                    <li>Horseback riding (subject to availability)</li>
                    <li>Dance studio access (selected hours)</li>
                    <li>Tunnel of Lights, Treehouses, Hobbit House</li>
                    <li>Museum of Wonders & themed shows</li>
                    <li>Children's playgrounds, kiddie boating, carousel</li>
                    <li>Guided areas and free activities (painting, baking demos)</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                  Full Activities & Description
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#inclusionsAccord">
                <div class="accordion-body small" style="max-height:420px; overflow:auto; white-space:pre-wrap;">
                  Complete your experience of WONDER here at Mother’s Wonderland by choosing your own kind of free and fun activities:
                  
                  1. CLIMB THE PEAK of the tallest statue in Quezon Province — the MOTHER GAIA. Be awed by the breathtaking view of Mt. Banahaw and experience how it feels to be at 35-meters high while enjoying a 360 degree view of beautiful sceneries of mountain and sea.
                  
                  2. ZIPLINE: Enjoy the freedom and thrill of riding the eagle zipline amidst the treetops of Gat Gubat.
                  
                  3. HORSEBACK RIDING: Saddle up! Our Wonder Cowboys can’t wait for you to experience riding colorful PINTO horses in a safe corral. No riding experience needed!
                  
                  4. DANCE STUDIO: Dance your heart out all-day in our dance studio that brings back the glory of disco! Move to the beat of pop music, salsa or to whatever beat is in your head! Dance like nobody's watching!
                  
                  5. TUNNEL OF LIGHTS: Be mesmerized by this tunnel that plays on your senses. From darkness to a sensation of weaving lights, colors and pictures, it is a glowing adventure!
                  
                  6. ANIMAL RENDEZVOUS: Ever wondered how it feels to touch a python? Touch and take pictures with RINGO, our friendly Burmese python. Enjoy FISH FEEDING in any area of our long Cascading Brook, and enjoy our other exotic animal encounters as we slowly acclimate them with love and care to the Wonderland environment.
                  
                  7. HOBBIT HOUSE: Let your kids experience being a hobbit by exploring a realistic hobbit house nestled under a hill. Inside they will find a relaxing living area where a hobbit teacher is ready to tell them a story about fairies and magic!
                  
                  8. TREEHOUSE: Gat Gubat has 3 treehouses that offer a unique experience of being inside a treehouse that is designed to be a living area complete with a balcony, a loft and a bathroom.
                  
                  9. MUSEUM OF WONDERS: A collection that was built from many travels and exploration, curated with the intention of educating, entertaining and sharing the feelings evoked when looking at these fascinating pieces, this area holds the most wonder of all. Get awed and see actual dinosaur and real-life animal replicas; ancient Egyptian culture; Bronze, Stone & Wooden sculptures. On display are also a variety of art pieces as inspiration to future artists.
                  
                  10. The CHAPEL OF QUEZON ARTISTS: Mother’s Wonderland supports and promotes artists from QUEZON Province and has allocated a prominent exhibit space for them so guests can enjoy and purchase the artworks.
                  
                  12. HUGE MAJESTIC SCULPTURES: Be captivated by the masterpiece sculptures of Wonderland artists from the tallest Mother GAIA, to the larger than life Buddha, the ALINDOG lying on her side, the Alhambra dragon, the American Ninja and its dragon obstacle course, the Tanod at Magical Hillside, Gat Gubat’s Malakas at si Maganda, the 15-feet high Machete Archangels at the portal of Gaia’s tower, Zeus the Protector with the 4 Warrior Guards of GAIA with the Samurai Ninja Warrior guarding the East, the Native American Indian Sachem guarding the West, the Vikings Warlord guarding the North and the African Hunter Woman guarding the Southern front. See the detailed artwork and wonder how they were accomplished. Admire the artful mixing of paints to create a beautiful hue of different shades and colors. Mga tunay na OBRA MAESTRA.
                  
                  13. CHILDREN’S OUTDOOR AND INDOOR PLAYGROUND: Let your children frolic under the sun playing in our colorful playset with slides, swing and crawling spaces. Or run to the Magical Hillside to use our “adrenaline rush” grand slide. Challenge the children (and adults!) to test their motor skills by completing the American Ninja obstacle course. Play and interact safely with other kids, run and let their energy flow for INDOORS at the GLACIER, your children will truly enjoy the free ride all-you-can carousel complete with lights and music. Practice basketball shots, do bouldering and wall climbing on real boulder-looking panels, and enjoy playing in another differently- designed play set with a standing xylophone. Beside the Glacier is a shallow water feature under the real-life wooden shipwreck where children can wade, wet their feet and enjoy the AQUATICA play infinity pool.
                  
                  14. KIDDIE BOATING: Children may also go boating on Goblin’s Mini-Lake and paddle around the fairy island while a visible school of fishes swim alongside the boats.
                  
                  15. MASSAGE: Free re-energizing and healing massage from our well-trained massage therapists and relax a little after walking around Wonderland. Located at the Ground floor of the Wellness Building.
                  
                  16. SKYWALK, SKYBED and HANGING BRIDGE: Experience walking along treetops by strolling at Wonder SKYWALK. Get excited by also bouncing at Skywalk’s Hanging Bridge. Summon enough courage to also step on some transparent glass parts of the Skywalk. You may also relax and lie down on Skywalk’s Skybed.
                  
                  17. BEAUTIFUL LANDSCAPING USING ROCKS AND DIFFERENT VARIETIES OF PLANTS: Get relaxed and enjoy the soothing feeling simply by walking through Wonderland’s pathways. Be greeted by different flowers and many varieties of plants that stand beside rocks singing to the same spirit that resides in you.
                  
                  18. NATURE NOOKS TO RELAX: You may simply find your nature nook to sit and relax. There are several cabanas or nooks where you and your family or friends can stay to eat, relax, and bond. You may also purchase picnic mats or “banig” which you may spread on the grass under the tree to enjoy its shade. As the saying goes, “Being with nature, one gets more than he seeks.”
                  
                  19. PAINTING LESSONS, & CROFFLE-BAKING: Guests, adults or children alike may book time slots in our free painting class (pay only for consumables like canvass & ink paint). Slots are limited so plan your booking. You may also want to awaken the culinary interest of your children by having them join croffle baking where they get to taste and eat their cooked croffles (pay only for the ingredients).
                  
                  20. FILIPINO CULTURAL RE-ENACTMENT OF “YUBAKAN COURTSHIP” and “ PALARONG LAHI” GAMES: Many Filipino traditions have been lost due to cultural revolution and one of which involves courtship. Much like the famous “harana”, YUBAKAN is a famous Quezon activity of a bygone era which involves a group of young men and young women, boiled “saba”, margarine, sugar, grated coconut and “yubakan”.
                  
                  The word “yubakan” can both be a noun and a verb depending on how it is used. As a noun it is like a giant mortar and pestle made of wood. As a verb it is the act of pounding. Boiled and peeled bananas are pounded by the men to show off their strength and stamina while vying for the women’s admiration and attention. The women show how good of a cook they are by making sure they put the right amount of sugar and margarine. This simple gathering might be boring to the present generation but this activity brought so much excitement to these groups of people specially when they get to enjoy the product of their efforts: the delicious “niyubak” while eyeing their prospective future spouse.
                  
                  Meanwhile, the “PALARONG LAHI” invites guests and their children to participate in the ever-popular Filipino games like “tumbang preso”, “basag-palayok” etc. Again, these Filipino games have been part of the past generation’s growing up years when electronic gadgets were non-existent and games happened in the field and not on a tablet or phone. This is a great activity for the whole family to bring back games that involve running and jumping using the legs and not the thumbs!
                  
                  21. LOVE LOCK BRIDGE: Promise forever by locking your love with a key to someone’s heart. Lock in Your Memories at Mother’s Wonderland! Personalize your love with a lock on the Lovelock bridge and create a sweet memory at the House of Elves, where your union is blessed inside by the magic of elves.
                  
                  22. AWARD-WINNING PERFORMERS: Enjoy a themed variety show by talented dancers and singers for an exciting entertainment ending with a spectacular Fire dance performance.
                  
                  23. CHRISTMAS VILLAGE WINTER WONDERLAND AT SUNSET: The most wondrous season of all is celebrated at Mother’s Wonderland with a giant Christmas tree lit with a thousand lights! Walk under the tree and feel the holiday spirit. Hop onto Santa’s snow sleigh driven by a reindeer. Visit the Nativity Scene (“belen”) with life-sized characters. Let the kids go inside Santa’s Gift Factory and light up your hearts with a multitude of flickering lights amidst a vista of another towering Christmas tree, and finally a SNOW SHOW. Watch or get soaked by the unique and much-awaited Wonderland’s SNOW-MUCH-FUN bubbles made more magically jolly by a continuous play of lights and music.
                  
                  24. MOTHER’S WONDERLAND WONDER WORKERS: Each person working at Mother’s Wonderland was carefully selected and vetted to give the best service-friendly entertainment to guests. They were trained to be helpful and attentive with the goal of keeping guests happy, safe and comfortable. We deeply appreciate customer feedback, both positive and negative, so that we may learn and continually improve in giving joy and excellent service to our guests.
                </div>
              </div>
            </div>
          </div>

          <div class="mt-3">
            <a href="terms.php" class="btn btn-outline-secondary btn-sm">View terms & conditions</a>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="row g-0">
          <div class="col-md-4">
            <div class="card-img-placeholder h-100">Promo</div>
          </div>
          <div class="col-md-8">
            <div class="card-body">
              <h5 class="card-title">
                <?= $promo ? htmlspecialchars($promo['name']) : 'Grand Opening Promo' ?>
              </h5>
              <p class="muted-sm mb-0">
                <?= $promo ? nl2br(htmlspecialchars($promo['description'] ?? 'Promo discounts are already applied to Adult & Senior/PWD Day Passes. Promo cannot be combined with other discounts.')) : 'Promo discounts are already applied to Adult & Senior/PWD Day Passes. Promo cannot be combined with other discounts.' ?>
              </p>

              <?php if (!empty($promoDiscount)): ?>
                <hr>
                <div class="small mt-2">
                  <strong>Promo rates:</strong>
                  <ul class="mb-0">
                    <?php foreach ($promoDiscount as $label => $amt): ?>
                      <li><?=htmlspecialchars($label)?>: <?= is_numeric($amt) ? '₱' . number_format((float)$amt, 2) : htmlspecialchars($amt) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

            </div>
          </div>
        </div>
      </div>

    </div>

    <aside class="col-lg-4">
      <div class="card shadow-sm mb-4 p-4">
        <h5 class="mb-3">Quick Info</h5>
        <p class="small mb-2"><strong>Tickets:</strong> All-day passes (onsite & online)</p>
        <p class="small mb-2"><strong>Kids below 3ft:</strong> Free entry</p>
        <p class="small mb-2"><strong>Promo:</strong> Grand Opening applied</p>
        <hr>
        <a href="subscribe.php" class="btn btn-danger w-100 rounded-pill mb-2">Book Tickets</a>
        <a href="contact.php" class="btn btn-outline-secondary w-100 rounded-pill">Contact Support</a>
      </div>

      <div class="card shadow-sm p-3">
        <h6 class="mb-2">FAQ</h6>
        <ul class="small mb-0">
          <li>Are pets allowed? — Only service animals permitted.</li>
          <li>Is parking available? — Yes, ample parking on site.</li>
          <li>Can I re-enter? — Re-entry policy depends on ticket type.</li>
        </ul>
      </div>
    </aside>
  </div>
</section>

<!-- Footer -->
<footer class="py-4">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
        <small class="text-muted">&copy; <?=date('Y')?> Mothers Wonderland. All rights reserved.</small>
      </div>
      <div class="col-md-6 text-center text-md-end">
        <a href="#" class="text-muted me-3"><i class="bi bi-facebook"></i></a>
        <a href="#" class="text-muted me-3"><i class="bi bi-twitter"></i></a>
        <a href="#" class="text-muted"><i class="bi bi-instagram"></i></a>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS (bundle) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>