<?php
declare(strict_types=1);

$pageTitle = 'Browse PGs — PG Finder';
require_once __DIR__ . '/includes/header.php';

$initialCity = trim((string) ($_GET['city'] ?? ''));
?>

<section class="bg-white border-bottom py-4">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h1 class="h3 fw-bold mb-1">Student PG &amp; Hostel Listings</h1>
        <p class="text-muted small mb-0">
          Filter by city, gender preference, and monthly budget with instant React &amp; AJAX updates.
        </p>
      </div>
      <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Cities</a>
    </div>
  </div>
</section>

<section class="container py-4">
  <div id="react-property-root" data-initial-city="<?= htmlspecialchars($initialCity, ENT_QUOTES, 'UTF-8') ?>"></div>
</section>

<!-- React 18 + Babel Standalone for browser JSX compilation -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

<!-- React Property Listing Component -->
<script type="text/babel" src="js/react_app.js"></script>

<!-- Mount PropertyListWidget -->
<script type="text/babel">
  const mountNode = document.getElementById('react-property-root');
  if (mountNode && window.ReactDOM && typeof PropertyListWidget === 'function') {
    const startCity = mountNode.dataset.initialCity || '';
    const root = ReactDOM.createRoot(mountNode);
    root.render(<PropertyListWidget initialCity={startCity} />);
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>