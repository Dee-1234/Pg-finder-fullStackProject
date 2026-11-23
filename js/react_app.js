const { useState, useEffect, useCallback } = React;

function PropertyCard({ prop, onToggleInterest, busyId }) {
    const isBusy = busyId === prop.id;
    const formattedPrice = Number(prop.price).toLocaleString('en-IN');

return (
    <div className="col-12 col-md-6 col-lg-4">
    <div className="card h-100 shadow-sm border-0">
        <img
        src={prop.image_url || 'https://placehold.co/600x360?text=Student+PG'}
        className="card-img-top"
        alt={prop.name}
        style={{ height: '200px', objectFit: 'cover' }}
        onError={(e) => {
            e.currentTarget.onerror = null;
            e.currentTarget.src = 'https://placehold.co/600x360/1e293b/ffffff?text=PG+Property';
        }}
        />
        <div className="card-body d-flex flex-column">
        <div className="d-flex justify-content-between align-items-start gap-2 mb-2">
            <span className="badge text-bg-primary">{prop.gender}</span>
            <span className="badge text-bg-warning text-dark">★ {Number(prop.rating).toFixed(1)}</span>
        </div>

        <h2 className="h5 fw-bold mb-1">{prop.name}</h2>
        <p className="text-muted small mb-2">
            {prop.city} • {prop.address}
        </p>
        <p className="card-text small text-secondary flex-grow-1">{prop.description}</p>

        <div className="border-top pt-3 mt-2 d-flex justify-content-between align-items-center">
            <div>
            <span className="fs-5 fw-bold text-dark">₹{formattedPrice}</span>
            <span className="text-muted small">/month</span>
            </div>

            <div className="d-flex align-items-center gap-2">
            <button
                type="button"
                disabled={isBusy}
                onClick={() => onToggleInterest(prop.id)}
                className={`btn btn-sm ${prop.is_interested ? 'btn-danger' : 'btn-outline-danger'}`}
            >
                ♥ {prop.is_interested ? 'Shortlisted' : 'Interested'} ({prop.interested_count})
            </button>
            <a href={`property_detail.php?id=${prop.id}`} className="btn btn-sm btn-outline-primary">
                Details
            </a>
            </div>
        </div>
        </div>
    </div>
    </div>
);
}

function PropertyListWidget({ initialCity = '' }) {
const [city, setCity] = useState(initialCity);
const [gender, setGender] = useState('');
const [maxPrice, setMaxPrice] = useState('');
const [properties, setProperties] = useState([]);
const [loading, setLoading] = useState(true);
const [error, setError] = useState('');
const [busyId, setBusyId] = useState(null);

const fetchList = useCallback(async () => {
    setLoading(true);
    setError('');

    const params = new URLSearchParams();
    if (city.trim()) params.set('city', city.trim());
    if (gender) params.set('gender', gender);
    if (maxPrice) params.set('max_price', maxPrice);

    try {
    const res = await fetch(`api/get_properties.php?${params.toString()}`);
    const data = await res.json();
    if (!res.ok || !data.success) {
        throw new Error(data.message || 'Could not load properties.');
    }
    setProperties(data.properties || []);
    } catch (err) {
    setError(err.message || 'Network error.');
    } finally {
    setLoading(false);
    }
}, [city, gender, maxPrice]);

useEffect(() => {
    const timer = setTimeout(fetchList, 200);
    return () => clearTimeout(timer);
}, [fetchList]);

const handleToggleInterest = async (propertyId) => {
    setBusyId(propertyId);
    try {
    const res = await fetch('api/toggle_interest.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ property_id: propertyId }),
    });
    const data = await res.json();

    if (res.status === 401 || data.require_login) {
        const loginModalEl = document.getElementById('loginModal');
        if (loginModalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(loginModalEl).show();
        }
        return;
    }

    if (res.ok && data.success) {
        setProperties((prev) =>
        prev.map((item) =>
            item.id === propertyId
            ? {
                ...item,
                is_interested: data.interested,
                interested_count: data.interested_count,
                }
            : item
        )
        );
    }
    } finally {
    setBusyId(null);
    }
};

return (
    <div>
    <div className="row g-3 mb-4">
        <div className="col-12 col-md-4">
        <input
            type="text"
            className="form-control"
            placeholder="Filter by city..."
            value={city}
            onChange={(e) => setCity(e.target.value)}
        />
        </div>
        <div className="col-12 col-md-4">
        <select className="form-select" value={gender} onChange={(e) => setGender(e.target.value)}>
            <option value="">All Genders</option>
            <option value="Female">Female Only</option>
            <option value="Male">Male Only</option>
            <option value="Unisex">Unisex / Co-ed</option>
        </select>
        </div>
        <div className="col-12 col-md-4">
        <select className="form-select" value={maxPrice} onChange={(e) => setMaxPrice(e.target.value)}>
            <option value="">Any Budget</option>
            <option value="9000">Up to ₹9,000 / mo</option>
            <option value="11000">Up to ₹11,000 / mo</option>
            <option value="13000">Up to ₹13,000 / mo</option>
            <option value="15000">Up to ₹15,000 / mo</option>
        </select>
        </div>
    </div>

    {error && <div className="alert alert-danger">{error}</div>}

    {loading ? (
        <div className="text-center py-4">
        <div className="spinner-border text-primary" role="status" />
        </div>
    ) : (
        <div className="row g-4">
        {properties.map((prop) => (
            <PropertyCard
            key={prop.id}
            prop={prop}
            onToggleInterest={handleToggleInterest}
            busyId={busyId}
            />
        ))}
        </div>
    )}
    </div>
);
}