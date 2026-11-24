export function initializeMemberMap() {
    const mapElement = document.getElementById('map');
    if (!mapElement || !window.L) {
        return;
    }

    const latitudeInput = document.getElementById('lat');
    const longitudeInput = document.getElementById('lon');
    const coordinates = document.getElementById('coordinates');
    const weather = document.getElementById('weather');
    const geocodeStatus = document.getElementById('geocodeStatus');
    const addressInputs = [...document.querySelectorAll('[data-address-field]')];
    let weatherRequest;
    let addressRequest;
    let reverseRequest;

    const validCoordinates = (lat, lon) => Number.isFinite(lat) && Number.isFinite(lon) && Math.abs(lat) <= 90 && Math.abs(lon) <= 180;
    const savedLat = Number(latitudeInput.value);
    const savedLon = Number(longitudeInput.value);
    const initialLat = validCoordinates(savedLat, savedLon) ? savedLat : 14.5764;
    const initialLon = validCoordinates(savedLat, savedLon) ? savedLon : 121.0851;
    const map = window.L.map(mapElement).setView([initialLat, initialLon], 13);

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    const marker = window.L.marker([initialLat, initialLon]).addTo(map);

    async function loadWeather(lat, lon) {
        weatherRequest?.abort();
        const request = new AbortController();
        weatherRequest = request;
        weather.textContent = 'Loading local weather...';
        weather.setAttribute('aria-busy', 'true');

        try {
            const url = new URL(mapElement.dataset.weatherUrl, window.location.origin);
            url.search = new URLSearchParams({ lat, lon });
            const response = await fetch(url, { signal: request.signal });
            if (!response.ok) {
                throw new Error('Weather lookup failed');
            }
            const result = await response.json();
            if (request !== weatherRequest) {
                return;
            }
            const temperature = result.temperature == null ? 'Temperature unavailable' : `${Number(result.temperature).toFixed(1)} °C`;
            const humidity = result.humidity == null ? '' : `${result.humidity}% humidity`;
            weather.textContent = `${result.place || 'Selected location'} · ${temperature}\n${[result.description, humidity].filter(Boolean).join(' · ')}`;
        } catch (error) {
            if (error.name !== 'AbortError' && request === weatherRequest) {
                weather.textContent = 'Weather is unavailable right now.';
            }
        } finally {
            if (request === weatherRequest) {
                weather.setAttribute('aria-busy', 'false');
            }
        }
    }

    function setLocation(lat, lon, panMap = false) {
        latitudeInput.value = lat.toFixed(7);
        longitudeInput.value = lon.toFixed(7);
        coordinates.textContent = `Latitude: ${lat.toFixed(5)}\nLongitude: ${lon.toFixed(5)}`;
        marker.setLatLng([lat, lon]);
        if (panMap) {
            map.setView([lat, lon], 15);
        }
        loadWeather(lat, lon);
    }

    map.on('click', async event => {
        addressRequest?.abort();
        reverseRequest?.abort();
        const request = new AbortController();
        reverseRequest = request;
        const { lat, lng: lon } = event.latlng;
        setLocation(lat, lon);
        geocodeStatus.textContent = 'Looking up the selected address...';

        try {
            const url = new URL('https://nominatim.openstreetmap.org/reverse');
            url.search = new URLSearchParams({ format: 'jsonv2', addressdetails: '1', lat, lon });
            const response = await fetch(url, { signal: request.signal });
            if (!response.ok) {
                throw new Error('Address lookup failed');
            }
            const result = await response.json();
            if (request !== reverseRequest) {
                return;
            }
            if (!result.address) {
                throw new Error('Address details unavailable');
            }
            const address = result.address;
            const values = {
                street_name: address.quarter || address.neighbourhood || address.suburb || address.city_district || address.district || address.village || address.hamlet || '',
                city: address.city || address.town || address.municipality || address.village || address.county || '',
                state: address.state || address.province || address.region || '',
                zip_code: address.postcode || ''
            };
            addressInputs.forEach(input => { input.value = values[input.name] || ''; });
            geocodeStatus.textContent = 'Address updated from the map. Review the barangay, village, or district, city, state, and ZIP code.';
        } catch (error) {
            if (error.name !== 'AbortError' && request === reverseRequest) {
                geocodeStatus.textContent = 'Map pin updated. Enter the address fields manually.';
            }
        }
    });

    async function syncAddressToMap() {
        const query = addressInputs.map(input => input.value.trim()).filter(Boolean).join(', ');
        if (!query) {
            geocodeStatus.textContent = 'Enter an address or choose a location on the map.';
            return;
        }
        addressRequest?.abort();
        reverseRequest?.abort();
        const request = new AbortController();
        addressRequest = request;
        geocodeStatus.textContent = 'Finding the address on the map...';

        try {
            const url = new URL('https://nominatim.openstreetmap.org/search');
            url.search = new URLSearchParams({ format: 'json', limit: '1', q: query });
            const response = await fetch(url, { signal: request.signal });
            if (!response.ok) {
                throw new Error('Address lookup failed');
            }
            const results = await response.json();
            if (request !== addressRequest) {
                return;
            }
            const lat = Number(results[0]?.lat);
            const lon = Number(results[0]?.lon);
            if (!validCoordinates(lat, lon)) {
                geocodeStatus.textContent = 'Address not found. Add more details or choose a location on the map.';
                return;
            }
            setLocation(lat, lon, true);
            geocodeStatus.textContent = 'Map pin synced to the address.';
        } catch (error) {
            if (error.name !== 'AbortError' && request === addressRequest) {
                geocodeStatus.textContent = 'Could not find the address right now. You can choose a location on the map.';
            }
        }
    }

    document.getElementById('findAddressBtn').addEventListener('click', syncAddressToMap);
    addressInputs.forEach(input => {
        input.addEventListener('input', () => {
            addressRequest?.abort();
            reverseRequest?.abort();
            geocodeStatus.textContent = 'Select Find on map to update the pin from this address.';
        });
    });
    setLocation(initialLat, initialLon);
}
