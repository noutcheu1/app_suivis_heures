const GEOCODE_CACHE = new Map();

async function geocodeAddress(address) {
    if (GEOCODE_CACHE.has(address)) {
        return GEOCODE_CACHE.get(address);
    }

    const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=1`;

    try {
        const response = await fetch(url, {
            headers: { 'Accept-Language': 'fr' }
        });

        if (!response.ok) {
            throw new Error(`Nominatim error: ${response.statusText}`);
        }

        const data = await response.json();
        if (data && data.length > 0) {
            const coords = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
            GEOCODE_CACHE.set(address, coords);
            return coords;
        }
    } catch (error) {
        console.error('Geocode Error:', error);
    }

    return null;
}

async function getRoute(start, end) {
    const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=false`;

    try {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`OSRM error: ${response.statusText}`);
        }

        const data = await response.json();
        if (data.routes && data.routes[0]?.legs[0]) {
            return {
                distance: (data.routes[0].legs[0].distance / 1000).toFixed(2),
                duration: (data.routes[0].legs[0].duration / 60).toFixed(0),
            };
        }
    } catch (error) {
        console.error('Route Error:', error);
    }

    return null;
}

export async function getDistance(addressStart, addressEnd) {
    const [coordsStart, coordsEnd] = await Promise.all([
        geocodeAddress(addressStart),
        geocodeAddress(addressEnd),
    ]);

    if (!coordsStart || !coordsEnd) return null;

    const route = await getRoute(coordsStart, coordsEnd);
    return route ? route.distance : null;
}

