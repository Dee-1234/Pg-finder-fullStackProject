(() => {
  const filterForm = document.getElementById('filterForm');
  const cityInput = document.getElementById('filterCity');
  const genderSelect = document.getElementById('filterGender');
  const maxPriceSelect = document.getElementById('filterMaxPrice');
  const resetBtn = document.getElementById('resetFiltersBtn');
  const container = document.getElementById('propertiesContainer');
  const spinner = document.getElementById('loadingSpinner');
  const countBadge = document.getElementById('resultCountBadge');
  const feedbackBanner = document.getElementById('feedbackBanner');

  if (!filterForm || !cityInput || !genderSelect || !maxPriceSelect || !container) {
    return;
  }

  let debounceTimer = null;
  let activeRequestController = null;

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function showBanner(message, type = 'info') {
    if (!feedbackBanner) return;
    feedbackBanner.className = `alert alert-${type}`;
    feedbackBanner.textContent = message;
    feedbackBanner.classList.remove('d-none');
  }

  function hideBanner() {
    if (!feedbackBanner) return;
    feedbackBanner.classList.add('d-none');
  }

  function setLoading(isLoading) {
    if (spinner) {
      spinner.classList.toggle('d-none', !isLoading);
    }
    container.classList.toggle('opacity-50', isLoading);
    cityInput.disabled = false;
    genderSelect.disabled = isLoading;
    maxPriceSelect.disabled = isLoading;
    if (resetBtn) resetBtn.disabled = isLoading;
  }

  function buildQueryString() {
    const params = new URLSearchParams();
    const city = cityInput.value.trim();
    const gender = genderSelect.value;
    const maxPrice = maxPriceSelect.value;

    if (city) params.set('city', city);
    if (gender) params.set('gender', gender);
    if (maxPrice) params.set('max_price', maxPrice);

    const qs = params.toString();
    return qs ? `?${qs}` : '';
  }

  function renderPropertyCard(prop) {
    const safeName = escapeHtml(prop.name);
    const safeCity = escapeHtml(prop.city);
    const safeAddress = escapeHtml(prop.address);
    const safeGender = escapeHtml(prop.gender);
    const safeDesc = escapeHtml(prop.description);
    const safeImg = escapeHtml(
      prop.image_url || 'https://placehold.co/600x360/1e293b/ffffff?text=Student+PG'
    );
    const formattedPrice = Number(prop.price || 0).toLocaleString('en-IN');
    const ratingText = Number(prop.rating || 0).toFixed(1);
    const interested = Boolean(prop.is_interested);
    const interestedCount = Number(prop.interested_count || 0);
    const interestedClass = interested ? 'btn-danger' : 'btn-outline-danger';
    const interestedLabel = interested ? 'Shortlisted' : 'Interested';

    return `
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          <img
            src="${safeImg}"
            class="card-img-top"
            alt="${safeName}"
            style="height: 200px; object-fit: cover;"
            onerror="this.onerror=null;this.src='https://placehold.co/600x360/1e293b/ffffff?text=PG+Property';"
          />
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <span class="badge text-bg-primary">${safeGender}</span>
              <span class="badge text-bg-warning text-dark">&#9733; ${ratingText}</span>
            </div>

            <h2 class="h5 fw-bold mb-1">${safeName}</h2>
            <p class="text-muted small mb-2">${safeCity} &bull; ${safeAddress}</p>
            <p class="card-text small text-secondary flex-grow-1">${safeDesc}</p>

            <div class="border-top pt-3 mt-2 d-flex justify-content-between align-items-center gap-2">
              <div>
                <span class="fs-5 fw-bold text-dark">&#8377;${formattedPrice}</span>
                <span class="text-muted small">/month</span>
              </div>

              <div class="d-flex align-items-center gap-2">
                <button
                  type="button"
                  class="btn btn-sm ${interestedClass} interest-btn"
                  data-property-id="${Number(prop.id)}"
                  aria-pressed="${interested ? 'true' : 'false'}"
                >
                  &hearts; <span class="interest-label">${interestedLabel}</span>
                  (<span class="interest-count">${interestedCount}</span>)
                </button>
                <a href="property_detail.php?id=${Number(prop.id)}" class="btn btn-sm btn-outline-primary">
                  Details
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  async function loadProperties() {
    if (activeRequestController) {
      activeRequestController.abort();
    }
    activeRequestController = new AbortController();

    hideBanner();
    setLoading(true);

    try {
      const response = await fetch(`api/get_properties.php${buildQueryString()}`, {
        method: 'GET',
        signal: activeRequestController.signal,
        headers: {
          Accept: 'application/json',
        },
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Unable to fetch properties.');
      }

      const list = Array.isArray(data.properties) ? data.properties : [];

      if (countBadge) {
        countBadge.textContent = `${list.length} ${list.length === 1 ? 'PG found' : 'PGs found'}`;
      }

      if (list.length === 0) {
        container.innerHTML = `
          <div class="col-12">
            <div class="card border-0 shadow-sm p-4 text-center">
              <h3 class="h6 fw-bold mb-1">No matching PGs found</h3>
              <p class="text-muted small mb-0">
                Try clearing a filter, searching another city, or increasing your monthly budget.
              </p>
            </div>
          </div>
        `;
      } else {
        container.innerHTML = list.map(renderPropertyCard).join('');
      }
    } catch (err) {
      if (err.name === 'AbortError') {
        return;
      }
      if (countBadge) {
        countBadge.textContent = '0 PGs';
      }
      container.innerHTML = '';
      showBanner(err.message || 'Could not load PG listings right now.', 'danger');
    } finally {
      setLoading(false);
    }
  }

  async function handleInterestClick(button) {
    const propertyId = Number(button.dataset.propertyId);
    if (!propertyId) return;

    button.disabled = true;

    try {
      const response = await fetch('api/toggle_interest.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ property_id: propertyId }),
      });

      const data = await response.json();

      if (response.status === 401 || data.require_login) {
        const loginModalEl = document.getElementById('loginModal');
        if (loginModalEl && window.bootstrap) {
          bootstrap.Modal.getOrCreateInstance(loginModalEl).show();
        } else {
          showBanner(data.message || 'Please log in to shortlist properties.', 'warning');
        }
        return;
      }

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Could not update shortlist status.');
      }

      const isInterested = Boolean(data.interested);
      const count = Number(data.interested_count ?? 0);
      const labelEl = button.querySelector('.interest-label');
      const countEl = button.querySelector('.interest-count');

      button.classList.toggle('btn-danger', isInterested);
      button.classList.toggle('btn-outline-danger', !isInterested);
      button.setAttribute('aria-pressed', isInterested ? 'true' : 'false');

      if (labelEl) {
        labelEl.textContent = isInterested ? 'Shortlisted' : 'Interested';
      }
      if (countEl) {
        countEl.textContent = String(count);
      }
    } catch (err) {
      showBanner(err.message || 'Failed to update interest status.', 'danger');
    } finally {
      button.disabled = false;
    }
  }

  container.addEventListener('click', (event) => {
    const btn = event.target.closest('.interest-btn');
    if (btn) {
      handleInterestClick(btn);
    }
  });

  cityInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadProperties, 250);
  });

  genderSelect.addEventListener('change', loadProperties);
  maxPriceSelect.addEventListener('change', loadProperties);

  filterForm.addEventListener('submit', (event) => {
    event.preventDefault();
    clearTimeout(debounceTimer);
    loadProperties();
  });

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      clearTimeout(debounceTimer);
      cityInput.value = '';
      genderSelect.value = '';
      maxPriceSelect.value = '';
      loadProperties();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadProperties);
  } else {
    loadProperties();
  }
})();