<template>
  <div class="map-container">
    <div class="panels-wrapper">
      <!-- Butonul principal -->
      <button 
        @click="togglePanel"
        class="generate-btn main">
        Generează traseu
      </button>

      <!-- Random / Personalizat -->
      <div v-if="showPanel" class="panel choices-column">
        <button class="generate-btn" @click="selectRouteType('random')">Random</button>
        <button class="generate-btn" @click="selectRouteType('custom')">Personalizat</button>
      </div>

      <!-- Alegeri Random -->
      <div v-if="showPanel && selectedRouteType === 'random'" class="panel form-column">
        <input class="input-btn" type="text" v-model="random.start" placeholder="Punct de plecare" />
        <input class="input-btn" type="number" v-model="random.length" placeholder="Lungime (km)" />
        <select class="input-btn" v-model="random.terrain">
          <option value="Montan">Montan</option>
          <option value="Nemontan">Nemontan</option>
          <option value="Mixt">Mixt</option>
        </select>
        <button class="generate-btn checkbox-btn" @click="random.loop = !random.loop">
          Buclă: {{ random.loop ? "Da" : "Nu" }}
        </button>
        <button class="generate-btn" @click="generateRandomRouteFromBackend">Generează Random</button>
      </div>

      <!-- Alegeri Personalizat -->
      <div v-if="showPanel && selectedRouteType === 'custom'" class="panel form-column">
        <input class="input-btn" type="text" v-model="custom.start" placeholder="Punct de plecare" />
        <input class="input-btn" type="text" v-model="custom.end" placeholder="Punct de sosire" />
        <button class="generate-btn" @click="generateCustomRouteFromBackend">Generează Personalizat</button>
      </div>
    </div>

    <!-- Harta -->
    <div id="map"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "../api";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet-defaulticon-compatibility";
import "leaflet-defaulticon-compatibility/dist/leaflet-defaulticon-compatibility.css";

let map;
let currentPolyline;

const showPanel = ref(false);
const selectedRouteType = ref("");

const random = ref({
  start: "",
  length: "",
  terrain: "Montan",
  loop: false
});

const custom = ref({
  start: "",
  end: ""
});

const togglePanel = () => {
  showPanel.value = !showPanel.value;
  if (!showPanel.value) selectedRouteType.value = "";
};

const selectRouteType = (type) => {
  selectedRouteType.value = type;
};

onMounted(() => {
  map = L.map("map").setView([45.9432, 24.9668], 7);
  setTimeout(() => map.invalidateSize(), 300);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
  L.marker([45.9432, 24.9668]).addTo(map).bindPopup("ExploreR — punct de pornire");
});

const generateRandomRouteFromBackend = async () => {
  try {
    const response = await api.get("/generate-random-route", {
      params: {
        start: random.value.start,      
        length: random.value.length,
        terrain: random.value.terrain,
        loop: random.value.loop
      }
    });
    const coords = response.data.route.map(p => [p[1], p[0]]); // inversăm pentru Leaflet
    if (currentPolyline) map.removeLayer(currentPolyline);
    currentPolyline = L.polyline(coords, { color: "blue" }).addTo(map);
    map.fitBounds(currentPolyline.getBounds());
  } catch (err) {
    console.error(err);
    alert("Eroare la generarea traseului Random!");
  }
};

const generateCustomRouteFromBackend = async () => {
  try {
    const response = await api.get("/generate-custom-route", {
      params: {
        start: custom.value.start, 
        end: custom.value.end      
      }
    });
    const coords = response.data.route.map(p => [p.lat, p.lng]);
    if (currentPolyline) map.removeLayer(currentPolyline);
    currentPolyline = L.polyline(coords, { color: "green" }).addTo(map);
    map.fitBounds(currentPolyline.getBounds());
  } catch (err) {
    console.error(err);
    alert("Eroare la generarea traseului Personalizat!");
  }
};
</script>

<style>
html, body { margin:0; padding:0; height:100%; }
.map-container { height:100vh; width:100vw; position:relative; }
#map { height:100%; width:100%; }


.panels-wrapper {
  position: absolute;
  top: 20px;
  left: 20px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  z-index: 1000;
}

.choices-column { display: flex; flex-direction: column; gap:5px; }
.form-column { display: flex; flex-direction: column; gap:5px; }

/* Buton principal */
.generate-btn.main {
  flex-shrink:0; width:auto;
  background-color:#2563eb; color:white;
  padding:10px 15px; border-radius:8px; border:none;
  cursor:pointer; font-weight:bold;
}
.generate-btn.main:hover { background-color:#1e40af; }

/* Butoane generale */
.generate-btn {
  display:block; width:auto;
  background-color:#2563eb; color:white;
  padding:10px 15px; border-radius:8px; border:none;
  cursor:pointer; font-weight:bold;
}
.generate-btn:hover { background-color:#1e40af; }

/* Input și select */
.input-btn {
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 15px;
  cursor: text;
}

.input-btn::placeholder {
  color: white;
  opacity: 1; 
}

/* Checkbox */
.checkbox-btn {
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 15px;
  cursor: pointer;
  text-align: left;
}
</style>
