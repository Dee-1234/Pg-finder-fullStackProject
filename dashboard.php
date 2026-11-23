<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'My Shortlist Dashboard — PG Finder';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/database_connect.php';

$userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$student = null;
$shortlistedProperties = [];
$dbError = '';

if ($userId > 0) {
    try {
        $userStmt = $pdo->prepare(
            'SELECT id, name, email, phone, college, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $userStmt->execute([':id' => $userId]);
        $student = $userStmt->fetch();

        if (!$student) {
            $userId = 0;
        } else {
            $listStmt = $pdo->prepare(
                'SELECT
                    p.id,
                    p.name,
                    p.city,
                    p.address,
                    p.price,
                    p.gender,
                    p.rating,
                    p.description,
                    p.image_url,
                    iu.created_at AS shortlisted_at,
                    COUNT(iu_all.user_id) AS interested_count
                 FROM interested_users iu
                 INNER JOIN properties p ON p.id = iu.property_id
                 LEFT JOIN interested_users iu_all ON iu_all.property_id = p.id
                 WHERE iu.user_id = :user_id
                 GROUP BY p.id, iu.created_at
                 ORDER BY iu.created_at DESC'
            );
            $listStmt->execute([':user_id' => $userId]);
            $shortlistedProperties = $listStmt->fetchAll();
        }
    } catch (PDOException $e) {
        $dbError = 'Could not load your dashboard data right now.';
    }
}
?>

<section class="container py-4">
  <?php if ($userId <= 0 || !$student): ?>
    <div class="card border-0 shadow-sm p-4 text-center mx-auto" style="max-width: 540px;">
      <h1 class="h4 fw-bold mb-2">Student Shortlist Dashboard</h1>
      <p class="text-muted mb-4">
        Log in or create a student account to view and manage your shortlisted PG properties.
      </p>
      <div class="d-flex justify-content-center gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
          Login
        </button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#signupModal">
          Sign Up
        </button>
      </div>
    </div>
  <?php else: ?>
    <?php
      $totalMonthlyRent = 0;
      foreach ($shortlistedProperties as $sp) {
          $totalMonthlyRent += (int) $sp['price'];
      }
      $avgRent = count($shortlistedProperties) > 0
          ? (int) round($totalMonthlyRent / count($shortlistedProperties))
          : 0;
    ?>

    <?php if ($dbError !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div id="dashboardAlert" class="alert alert-info d-none" role="alert"></div>

    <!-- Student Profile & Summary Row -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <span class="badge text-bg-primary mb-2">Student Profile</span>
            <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars((string) $student['name'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted small mb-3"><?= htmlspecialchars((string) $student['email'], ENT_QUOTES, 'UTF-8') ?></p>

            <div class="row g-2 small">
              <div class="col-12 col-sm-6">
                <span class="text-muted">Phone:</span>
                <strong class="ms-1"><?= htmlspecialchars((string) $student['phone'], ENT_QUOTES, 'UTF-8') ?></strong>
              </div>
              <div class="col-12 col-sm-6">
                <span class="text-muted">College:</span>
                <strong class="ms-1">
                  <?= htmlspecialchars(!empty($student['college']) ? (string) $student['college'] : 'Not specified', ENT_QUOTES, 'UTF-8') ?>
                </strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <h2 class="h6 text-uppercase text-muted fw-semibold mb-3">Shortlist Summary</h2>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary">Shortlisted PGs</span>
                <span class="fs-5 fw-bold" id="shortlistTotalCount"><?= count($shortlistedProperties) ?></span>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-secondary">Average Monthly Rent</span>
                <span class="fs-5 fw-bold" id="shortlistAvgRent">&#8377;<?= number_format($avgRent) ?></span>
              </div>
            </div>

            <div class="mt-3">
              <a href="property_list.php" class="btn btn-outline-primary btn-sm w-100">
                Explore More PGs
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Shortlisted Properties Section -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h4 fw-bold mb-0">Shortlisted Properties</h2>
    </div>

    <div id="shortlistEmptyState" class="card border-0 shadow-sm p-4 text-center <?= empty($shortlistedProperties) ? '' : 'd-none' ?>">
      <h3 class="h6 fw-bold mb-1">No shortlisted PGs yet</h3>
      <p class="text-muted small mb-3">Browse properties and click Interested or Shortlist to save your favorites here.</p>
      <div>
        <a href="property_list.php" class="btn btn-primary btn-sm">Browse PGs</a>
      </div>
    </div>

    <div id="shortlistGrid" class="row g-4">
      <?php foreach ($shortlistedProperties as $prop): ?>
        <?php
          $pid = (int) $prop['id'];
          $pname = (string) $prop['name'];
          $pcity = (string) $prop['city'];
          $paddress = (string) $prop['address'];
          $pgender = (string) $prop['gender'];
          $pprice = (int) $prop['price'];
          $prating = number_format((float) $prop['rating'], 1);
          $pdesc = (string) $prop['description'];
          $pimg = !empty($prop['image_url']) ? (string) $prop['image_url'] : 'https://placehold.co/600x360?text=Student+PG';
          $pinterestedCount = (int) $prop['interested_count'];
        ?>
        <div class="col-12 col-md-6 col-lg-4 shortlist-card-col" data-property-id="<?= $pid ?>" data-price="<?= $pprice ?>">
          <div class="card h-100 shadow-sm border-0">
            <img
              src="<?= htmlspecialchars($pimg, ENT_QUOTES, 'UTF-8') ?>"
              class="card-img-top"
              alt="<?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?>"
              style="height: 200px; object-fit: cover;"
              onerror="this.onerror=null;this.src='https://placehold.co/600x360/1e293b/ffffff?text=PG+Property';"
            />
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <span class="badge text-bg-primary"><?= htmlspecialchars($pgender, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="badge text-bg-warning text-dark">&#9733; <?= htmlspecialchars($prating, ENT_QUOTES, 'UTF-8') ?></span>
              </div>

              <h3 class="h5 fw-bold mb-1"><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="text-muted small mb-2"><?= htmlspecialchars($pcity . ' • ' . $paddress, ENT_QUOTES, 'UTF-8') ?></p>
              <p class="card-text small text-secondary flex-grow-1"><?= htmlspecialchars($pdesc, ENT_QUOTES, 'UTF-8') ?></p>

              <div class="border-top pt-3 mt-2 d-flex justify-content-between align-items-center">
                <div>
                  <span class="fs-5 fw-bold text-dark">&#8377;<?= number_format($pprice) ?></span>
                  <span class="text-muted small">/month</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger remove-shortlist-btn"
                    data-property-id="<?= $pid ?>"
                  >
                    Remove (<span class="card-interest-count"><?= $pinterestedCount ?></span>)
                  </button>
                  <a href="property_detail.php?id=<?= $pid ?>" class="btn btn-sm btn-outline-primary">
                    Details
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <script>
      (() => {
        const grid = document.getElementById('shortlistGrid');
        const emptyState = document.getElementById('shortlistEmptyState');
        const totalCountEl = document.getElementById('shortlistTotalCount');
        const avgRentEl = document.getElementById('shortlistAvgRent');
        const alertBox = document.getElementById('dashboardAlert');

        function showNotice(msg, type = 'info') {
          if (!alertBox) return;
          alertBox.className = `alert alert-${type}`;
          alertBox.textContent = msg;
          alertBox.classList.remove('d-none');
        }

        function recomputeSummary() {
          const cards = grid ? Array.from(grid.querySelectorAll('.shortlist-card-col')) : [];
          const count = cards.length;
          let sum = 0;

          cards.forEach((card) => {
            sum += Number(card.dataset.price || 0);
          });

          const avg = count > 0 ? Math.round(sum / count) : 0;

          if (totalCountEl) totalCountEl.textContent = String(count);
          if (avgRentEl) avgRentEl.textContent = `₹${avg.toLocaleString('en-IN')}`;
          if (emptyState) emptyState.classList.toggle('d-none', count > 0);
        }

        if (!grid) return;

        grid.addEventListener('click', async (event) => {
          const btn = event.target.closest('.remove-shortlist-btn');
          if (!btn) return;

          const propertyId = Number(btn.dataset.propertyId);
          const cardCol = btn.closest('.shortlist-card-col');
          if (!propertyId || !cardCol) return;

          btn.disabled = true;
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

            if (!res.ok || !data.success) {
              throw new Error(data.message || 'Unable to remove property from shortlist.');
            }

            if (!data.interested) {
              cardCol.remove();
              recomputeSummary();
              showNotice('Removed property from your shortlist.', 'info');
            }
          } catch (err) {
            showNotice(err.message || 'Error updating shortlist.', 'danger');
            btn.disabled = false;
          }
        });
      })();
    </script>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>