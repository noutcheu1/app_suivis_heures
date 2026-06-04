const delay = ms => new Promise(res => setTimeout(res, ms));

async function geocodeAddress(address) {
  await delay(200); 

  const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(address)}&limit=1`;

  try {
      const response = await fetch(url);
      
      if (!response.ok) {
          console.error(`Erreur API : ${response.status}`);
          return null;
      }

      const data = await response.json();
      
      if (data.features && data.features.length > 0) {
          // L'API renvoie les coordonnées au format [longitude, latitude]
          const [lon, lat] = data.features[0].geometry.coordinates;
          return [lat, lon];
      }
  } catch (error) {
      console.error('Erreur de géocodage :', error);
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
      await delay(1000);
      const route = await getRoute(coordinatesStart, coordinatesEnd);
      if (route) {
        return route.distance.toString().replace('.', ',');
      } else {
          return "Error";
      }
  } else {
      return "Error";
  }
}

