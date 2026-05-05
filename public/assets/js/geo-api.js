async function geocodeAddress(address) {
  const userAgent = `user${Math.floor(Math.random() * 10 ** 10)}`;
  const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&addressdetails=1`;

  try {
      const response = await fetch(url, {
          method: 'GET',
          headers: {
              'User-Agent': userAgent
          }
      });

      if (!response.ok) {
          throw new Error(`Error fetching geocode: ${response.statusText}`);
      }

      const data = await response.json();
      if (data && data.length > 0) {
          const lat = parseFloat(data[0].lat);
          const lon = parseFloat(data[0].lon);
          return [lat, lon];
      }
  } catch (error) {
      console.error('Geocode Error:', error);
  }

  return null;
}

async function getRoute(start, end) {
  const url = `http://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=false`;

  try {
      const response = await fetch(url);

      if (!response.ok) {
          throw new Error(`Error fetching route: ${response.statusText}`);
      }

      const data = await response.json();
      if (data.routes && data.routes[0]?.legs[0]) {
          const distance = (data.routes[0].legs[0].distance / 1000).toFixed(2); // Convert to kilometers
          const duration = (data.routes[0].legs[0].duration / 60).toFixed(2); // Convert to minutes
          return { distance, duration };
      }
  } catch (error) {
      console.error('Route Error:', error);
  }

  return null;
}

export async function getDistance(addressStart, addressEnd) {
  const coordinatesStart = await geocodeAddress(addressStart);
  const coordinatesEnd = await geocodeAddress(addressEnd);

  if (coordinatesStart && coordinatesEnd) {
      const route = await getRoute(coordinatesStart, coordinatesEnd);
      console.log(route); 
      if (route) {
        return route.distance.toString();
      } else {
          return "Error";
      }
  } else {
      return "Error";
  }
}

