<?php
$pageTitle = 'PG Finder — Find Student PGs & Hostels';
require_once __DIR__ . '/includes/header.php';

$cities = [
    ['name' => 'Delhi', 'tagline' => 'North Campus, GTB Nagar & Hauz Khas'],
    ['name' => 'Bengaluru', 'tagline' => 'Koramangala, Indiranagar & BTM Layout'],
    ['name' => 'Mumbai', 'tagline' => 'Bandra, Andheri & Vile Parle'],
    ['name' => 'Pune', 'tagline' => 'Viman Nagar, Kothrud & Hinjawadi'],
];
?>

<section class="hero-section">
    <div class="container text-center">
    <h1 class="display-5 fw-bold mb-3">Find Student-Friendly PGs Near Campus</h1>
    <p class="lead text-light mb-4">Compare monthly rent, amenities, and gender-specific stays across major student hubs.</p>

    <form action="property_list.php" method="GET" class="row g-2 justify-content-center mx-auto" style="max-width: 640px;">
        <div class="col-12 col-sm-8">
            <input type="text" name="city" class="form-control form-control-lg" placeholder="Search city (e.g., Delhi, Bengaluru, Pune)..." />
        </div>
        <div class="col-12 col-sm-4 d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Explore PGs</button>
        </div>
    </form>
    </div>
</section>

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Popular Student Cities</h2>
        <a href="property_list.php" class="btn btn-outline-primary btn-sm">View All Listings</a>
    </div>

    <div class="row g-4">
    <?php foreach ($cities as $city): ?>
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="property_list.php?city=<?= urlencode($city['name']) ?>" class="text-decoration-none text-dark">
        <div class="card city-card h-100 p-3">
            <div class="card-body">
                <div class="city-badge mb-2">Student Hub</div>
                    <h3 class="h5 fw-bold mb-2"><?= htmlspecialchars($city['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($city['tagline'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </a>
        </div>
    <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>