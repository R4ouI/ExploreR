<template>
  <div class="map-container">
    <button 
      @click="generateRouteFromBackend"
      class="generate-btn">
      Generează traseu
    </button>

    <div id="map"></div>
  </div>
</template>



<script setup>
import { onMounted } from "vue";
import api from "../api"; // Axios instance
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet-defaulticon-compatibility";
import "leaflet-defaulticon-compatibility/dist/leaflet-defaulticon-compatibility.css";

let map;
let currentPolyline;

onMounted(() => {
  map = L.map("map").setView([45.9432, 24.9668], 7);
setTimeout(() => {
  map.invalidateSize();
}, 300);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);

  L.marker([45.9432, 24.9668])
    .addTo(map)
    .bindPopup("ExploreR — punct de pornire 🏍️");
});


const generateRouteFromBackend = async () => {
  try {
    const response = await api.get("/generate-route");
    const data = response.data;

    const coords = data.route.map(p => [p.lat, p.lng]);

   
    if (currentPolyline) {
      map.removeLayer(currentPolyline);
    }

    currentPolyline = L.polyline(coords, { color: "blue" }).addTo(map);

    map.fitBounds(currentPolyline.getBounds());
  } catch (err) {
    console.error(err);
    alert("Eroare la generarea traseului!");
  }
};
</script>


<style>
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
}

.map-container {
  height: 100vh;   
  width: 100vw;  
  position: relative;
}

#map {
  height: 100%;
  width: 100%;
}

.generate-btn {
  position: absolute;
  top: 20px;
  left: 20px;
  z-index: 1000;
  background-color: #2563eb;
  color: white;
  padding: 10px 15px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: bold;
}
.generate-btn:hover {
  background-color: #1e40af;
}
</style>


