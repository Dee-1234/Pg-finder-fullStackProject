<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/database_connect.php';

$propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

$property = null;
$amenities = [];
$interestedCount = 0;
$isInterested = false;
$errorMessage = '';

if ($propertyId === false || $propertyId === null) {
    $errorMessage = 'Invalid or missing property ID.';
} else {
    try {
        $propStmt = $pdo->prepare(
            'SELECT id, name, city, address, price, gender, rating, description, image_url, created_at
             FROM properties
             WHERE id = :id
             LIMIT 1'
        );
        $propStmt->execute([':id' => $propertyId]);
        $property = $propStmt->fetch();

        if (!$property) {
            $errorMessage = 'The requested PG property could not be found.';
        } else {
            $amenityStmt = $pdo->prepare(
                'SELECT a.id, a.name, a.category
                 FROM property_amenities pa
                 INNER JOIN amenities a ON a.id = pa.amenity_id
                 WHERE pa.property_id = :property_id
                 ORDER BY a.category ASC, a.name ASC'
            );
            $amenityStmt->execute([':property_id' => $propertyId]);
            $amenities = $amenityStmt->fetchAll();

            $countStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM interested_users WHERE property_id = :property_id'
            );
            $countStmt->execute([':property_id' => $propertyId]);
            $interestedCount = (int) $countStmt->fetchColumn();

            if ($userId > 0) {
                $userInterestStmt = $pdo->prepare(
                    'SELECT 1
                     FROM interested_users
                     WHERE user_id = :user_id AND property_id = :property_id
                     LIMIT 1'
                );
                $userInterestStmt->execute([
                    ':user_id'     => $userId,
                    ':property_id' => $propertyId,
                ]);
                $isInterested = (bool) $userInterestStmt->fetchColumn();
            }
        }
    } catch (PDOException $e) {
        $errorMessage = 'Unable to load property details right now.';
    }
}

$pageTitle = $property
    ? htmlspecialchars((string) $property['name'], ENT_QUOTES, 'UTF-8') . ' — PG Finder'
    : 'Property Details — PG Finder';

require_once __DIR__ . '/includes/header.php';

$primaryImage = $property && !empty($property['image_url'])
    ? (string) $property['image_url']
    : 'https://placehold.co/900x480/1e293b/ffffff?text=Student+PG+Room';

$galleryImages = $property ? [
    ['label' => 'Main Room View', 'src' => $primaryImage, 'fallback' => 'https://placehold.co/900x480/1e293b/ffffff?text=Main+Room+View'],
    ['label' => 'Study & Workspace', 'src' => 'img/pg-room-study.jpg', 'fallback' => 'https://placehold.co/900x480/0f172a/e2e8f0?text=Study+%26+Workspace'],
    ['label' => 'Common & Dining Area', 'src' => 'img/pg-common-area.jpg', 'fallback' => 'https://placehold.co/900x480/1e3a8a/ffffff?text=Common+%26+Dining'],
] : [];
?>

<section class="container py-4">
  <div class="mb-3">
    <a href="property_list.php" class="btn btn-link text-decoration-none px-0">
      &larr; Back to All PGs
    </a>
  </div>

  <?php if ($errorMessage !== '' || !$property): ?>
    <div class="alert alert-warning shadow-sm" role="alert">
      <h1 class="h5 fw-bold mb-1">Property Unavailable</h1>
      <p class="mb-3"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
      <a href="property_list.php" class="btn btn-primary btn-sm">Browse Available PGs</a>
    </div>
  <?php else: ?>
    <?php
      $propName = (string) $property['name'];
      $propCity = (string) $property['city'];
      $propAddress = (string) $property['address'];
      $propGender = (string) $property['gender'];
      $propDesc = (string) $property['description'];
      $propPrice = (int) $property['price'];
      $propRating = number_format((float) $property['rating'], 1);

      $groupedAmenities = [];
      foreach ($amenities as $item) {
          $cat = (string) ($item['category'] ?? 'Essentials');
          $groupedAmenities[$cat][] = (string) $item['name'];
      }
    ?>

    <div id="detailAlert" class="alert alert-danger d-none" role="alert"></div>

    <!-- Header & Quick Action Bar -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="badge text-bg-primary"><?= htmlspecialchars($propGender, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge text-bg-warning text-dark fw-semibold">&#9733; <?= htmlspecialchars($propRating, ENT_QUOTES, 'UTF-8') ?> / 5.0</span>
            <span class="badge text-bg-light border text-dark"><?= htmlspecialchars($propCity, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <h1 class="h2 fw-bold mb-1"><?= htmlspecialchars($propName, ENT_QUOTES, 'UTF-8') ?></h1>
          <p class="text-muted mb-0"><?= htmlspecialchars($propAddress, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3">
          <div class="text-lg-end">
            <div class="fs-3 fw-bold text-dark">&#8377;<?= number_format($propPrice) ?></div>
            <div class="text-muted small">per month</div>
          </div>

          <button
            type="button"
            id="detailInterestBtn"
            data-property-id="<?= (int) $property['id'] ?>"
            aria-pressed="<?= $isInterested ? 'true' : 'false' ?>"
            class="btn <?= $isInterested ? 'btn-danger' : 'btn-outline-danger' ?>"
          >
            &hearts;
            <span id="detailInterestLabel"><?= $isInterested ? 'Shortlisted' : 'Mark Interested' ?></span>
            (<span id="detailInterestCount"><?= $interestedCount ?></span>)
          </button>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Left Column: Gallery, Description & Amenities -->
      <div class="col-12 col-lg-8">
        <!-- Photo Gallery Carousel -->
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
          <div id="propertyGalleryCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
              <?php foreach ($galleryImages as $idx => $img): ?>
                <button
                  type="button"
                  data-bs-target="#propertyGalleryCarousel"
                  data-bs-slide-to="<?= $idx ?>"
                  <?= $idx === 0 ? 'class="active" aria-current="true"' : '' ?>
                  aria-label="Slide <?= $idx + 1 ?>"
                ></button>
              <?php endforeach; ?>
            </div>

            <div class="carousel-inner">
              <?php foreach ($galleryImages as $idx => $img): ?>
                <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                  <img
                    src="<?= htmlspecialchars($img['src'], ENT_QUOTES, 'UTF-8') ?>"
                    class="d-block w-100"
                    alt="<?= htmlspecialchars($propName . ' - ' . $img['label'], ENT_QUOTES, 'UTF-8') ?>"
                    style="height: 380px; object-fit: cover;"
                    onerror="this.onerror=null;this.src='<?= htmlspecialchars($img['fallback'], ENT_QUOTES, 'UTF-8') ?>';"
                  />
                  <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded px-2 py-1">
                    <small><?= htmlspecialchars($img['label'], ENT_QUOTES, 'UTF-8') ?></small>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#propertyGalleryCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#propertyGalleryCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>

          <!-- Thumbnail Strip -->
          <div class="card-footer bg-white border-top py-3">
            <div class="row g-2">
              <?php foreach ($galleryImages as $idx => $img): ?>
                <div class="col-4">
                  <button
                    type="button"
                    data-bs-target="#propertyGalleryCarousel"
                    data-bs-slide-to="<?= $idx ?>"
                    class="btn btn-outline-secondary w-100 text-truncate btn-sm"
                  >
                    <?= htmlspecialchars($img['label'], ENT_QUOTES, 'UTF-8') ?>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- About This PG -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">About This Accommodation</h2>
            <p class="text-secondary mb-0" style="line-height: 1.7;">
              <?= nl2br(htmlspecialchars($propDesc, ENT_QUOTES, 'UTF-8')) ?>
            </p>
          </div>
        </div>

        <!-- Amenities Section -->
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Amenities &amp; Facilities</h2>

            <?php if (empty($groupedAmenities)): ?>
              <p class="text-muted small mb-0">No amenities listed yet for this property.</p>
            <?php else: ?>
              <div class="row g-4">
                <?php foreach ($groupedAmenities as $categoryName => $amenityList): ?>
                  <div class="col-12 col-md-6">
                    <h3 class="h6 fw-semibold text-primary mb-2">
                      <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <ul class="list-group list-group-flush">
                      <?php foreach ($amenityList as $amenityName): ?>
                        <li class="list-group-item px-0 py-1 border-0 small">
                          &#10003; <?= htmlspecialchars($amenityName, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right Column: Rating & Student Summary Card -->
      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 5rem;">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Student Rating &amp; Overview</h2>

            <div class="d-flex align-items-baseline gap-2 mb-3">
              <span class="display-6 fw-bold text-dark"><?= htmlspecialchars($propRating, ENT_QUOTES, 'UTF-8') ?></span>
              <span class="text-muted">/ 5.0 overall rating</span>
            </div>

            <div class="progress mb-4" style="height: 8px;" role="progressbar" aria-valuenow="<?= (int) round(((float) $property['rating'] / 5) * 100) ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="progress-bar bg-warning" style="width: <?= min(100, max(0, ((float) $property['rating'] / 5) * 100)) ?>%;"></div>
            </div>

            <dl class="row small mb-4">
              <dt class="col-5 text-muted">City</dt>
              <dd class="col-7 fw-semibold"><?= htmlspecialchars($propCity, ENT_QUOTES, 'UTF-8') ?></dd>

              <dt class="col-5 text-muted">Gender Type</dt>
              <dd class="col-7 fw-semibold"><?= htmlspecialchars($propGender, ENT_QUOTES, 'UTF-8') ?></dd>

              <dt class="col-5 text-muted">Monthly Rent</dt>
              <dd class="col-7 fw-semibold">&#8377;<?= number_format($propPrice) ?></dd>

              <dt class="col-5 text-muted">Interested Students</dt>
              <dd class="col-7 fw-semibold"><span id="detailSidebarCount"><?= $interestedCount ?></span> shortlisted</dd>
            </dl>

            <div class="d-grid gap-2">
              <button
                type="button"
                id="detailSidebarInterestBtn"
                data-property-id="<?= (int) $property['id'] ?>"
                class="btn <?= $isInterested ? 'btn-danger' : 'btn-primary' ?>"
              >
                <?= $isInterested ? 'Remove from Shortlist' : 'Shortlist This PG' ?>
              </button>
              <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">View My Shortlist Dashboard</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      (() => {
        const alertBox = document.getElementById('detailAlert');
        const mainBtn = document.getElementById('detailInterestBtn');
        const sideBtn = document.getElementById('detailSidebarInterestBtn');
        const labelEl = document.getElementById('detailInterestLabel');
        const countEl = document.getElementById('detailInterestCount');
        const sidebarCountEl = document.getElementById('detailSidebarCount');

        function showDetailError(msg) {
          if (!alertBox) return;
          alertBox.textContent = msg;
          alertBox.classList.remove('d-none');
        }

        function syncInterestState(interested, count) {
          if (mainBtn) {
            mainBtn.classList.toggle('btn-danger', interested);
            mainBtn.classList.toggle('btn-outline-danger', !interested);
            mainBtn.setAttribute('aria-pressed', interested ? 'true' : 'false');
          }
          if (labelEl) {
            labelEl.textContent = interested ? 'Shortlisted' : 'Mark Interested';
          }
          if (countEl) {
            countEl.textContent = String(count);
          }
          if (sidebarCountEl) {
            sidebarCountEl.textContent = String(count);
          }
          if (sideBtn) {
            sideBtn.classList.toggle('btn-danger', interested);
            sideBtn.classList.toggle('btn-primary', !interested);
            sideBtn.textContent = interested ? 'Remove from Shortlist' : 'Shortlist This PG';
          }
        }

        async function toggleInterest(propertyId) {
          if (alertBox) alertBox.classList.add('d-none');
          if (mainBtn) mainBtn.disabled = true;
          if (sideBtn) sideBtn.disabled = true;

          try {
            const res = await fetch('api/toggle_interest.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
              },
              body: JSON.stringify({ property_id: propertyId }),
            });
            const data = await res.json();

            if (res.status === 401 || data.require_login) {
              const loginModalEl = document.getElementById('loginModal');
              if (loginModalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(loginModalEl).show();
              } else {
                showDetailError(data.message || 'Please log in to shortlist this property.');
              }
              return;
            }

            if (!res.ok || !data.success) {
              throw new Error(data.message || 'Failed to update interest status.');
            }

            syncInterestState(Boolean(data.interested), Number(data.interested_count ?? 0));
          } catch (err) {
            showDetailError(err.message || 'Network error while updating shortlist.');
          } finally {
            if (mainBtn) mainBtn.disabled = false;
            if (sideBtn) sideBtn.disabled = false;
          }
        }

        [mainBtn, sideBtn].forEach((btn) => {
          if (!btn) return;
          btn.addEventListener('click', () => {
            const propId = Number(btn.dataset.propertyId);
            if (propId > 0) toggleInterest(propId);
          });
        });
      })();
    </script>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>