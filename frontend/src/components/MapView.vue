<template>
  <div class="map-container">
    <div class="panels-wrapper">
      <button
        @click="togglePanel"
        class="generate-btn main">
        Generează traseu
      </button>

      <div v-if="showPanel" class="panel choices-column">
        <button class="generate-btn" @click="selectRouteType('random')">Random</button>
        <button class="generate-btn" @click="selectRouteType('custom')">Personalizat</button>
      </div>

      <div v-if="showPanel && selectedRouteType === 'random'" class="panel form-column">
        <input class="input-btn" type="text" v-model="random.start" placeholder="Punct de plecare" />
        <input class="input-btn" type="number" v-model="random.length" placeholder="Lungime (km)" />

        <select class="input-btn" v-model="random.mode">
          <option value="driving-car">Mașină</option>
          <option value="cycling-regular">Bicicletă</option>
          <option value="foot-hiking">Mers pe jos</option>
        </select>

        <button class="generate-btn checkbox-btn" @click="random.loop = !random.loop">
          Buclă: {{ random.loop ? "Da" : "Nu" }}
        </button>
        <button class="generate-btn" @click="generateRandomRouteFromBackend">Generează Random</button>
      </div>

      <div v-if="showPanel && selectedRouteType === 'custom'" class="panel form-column">
        <input class="input-btn" type="text" v-model="custom.start" placeholder="Punct de plecare" />
        <input class="input-btn" type="text" v-model="custom.end" placeholder="Punct de sosire" />

        <select class="input-btn" v-model="custom.mode">
          <option value="driving-car">Mașină</option>
          <option value="cycling-regular">Bicicletă</option>
          <option value="foot-hiking">Mers pe jos</option>
        </select>

        <button class="generate-btn" @click="generateCustomRouteFromBackend">Generează Personalizat</button>
      </div>
      
      <div v-if="showPanel && selectedRouteType" class="panel form-column">
        <button class="generate-btn" @click="saveAndShare" :disabled="!lastPayload">
          Save & Share
        </button>

        <button class="generate-btn" v-if="shareUrl" @click="copyShareUrl">
          Copy link
        </button>

        <a
          class="generate-btn link-btn"
          v-if="shareUrl"
          :href="shareUrl"
          target="_blank"
          rel="noopener"
        >
          Open share page
        </a>
      </div>
    </div>

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
let startMarker = null;
let endMarker = null;

const showPanel = ref(false);
const selectedRouteType = ref("");

const lastPayload = ref(null);
const shareUrl = ref("");

const random = ref({
  start: "",
  length: "",
  mode: "driving-car",
  loop: false
});

const custom = ref({
  start: "",
  end: "",
  mode: "driving-car"
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
});

const addMarkers = (startCoords, endCoords) => {
  if (startMarker) map.removeLayer(startMarker);
  if (endMarker) map.removeLayer(endMarker);

  startMarker = L.marker([startCoords[1], startCoords[0]])
    .addTo(map)
    .bindPopup("<strong>Punct de Plecare</strong>");

  endMarker = L.marker([endCoords[1], endCoords[0]])
    .addTo(map)
    .bindPopup("<strong>Sosire</strong>");

  startMarker.openPopup();
};

const generateRandomRouteFromBackend = async () => {
  try {
    const response = await api.get("/generate-random-route", {
      params: {
        start: random.value.start,
        length: random.value.length,
        mode: random.value.mode,
        loop: random.value.loop
      }
    });

    const route = response.data.route;
    const startPoint = response.data.start;
    const endPoint = response.data.end;

    if (!route || !route.length) {
      alert("Ruta Random generată este goală!");
      return;
    }

    const coords = route.map(p => [p[1], p[0]]);
    if (currentPolyline) map.removeLayer(currentPolyline);
    currentPolyline = L.polyline(coords, { color: "blue", weight: 5 }).addTo(map);
    map.fitBounds(currentPolyline.getBounds());

    addMarkers(startPoint, endPoint);

     lastPayload.value = {
      start: startPoint,
      end: endPoint,
      mode: random.value.mode,
      route: route,
      loop: random.value.loop,
      length: random.value.length,
      type: "random",
    };
    shareUrl.value = "";

  } catch (err) {
    if (err.response && err.response.status === 404) {
      alert("Nu s-a găsit o rută validă. Încearcă alte setări.");
    } else {
      console.error(err);
      alert("Eroare la generarea traseului Random!");
    }
  }
};

const generateCustomRouteFromBackend = async () => {
  try {
    const response = await api.get("/generate-custom-route", {
      params: {
        start: custom.value.start,
        end: custom.value.end,
        mode: custom.value.mode
      }
    });

    const route = response.data.route;
    const startPoint = response.data.start;
    const endPoint = response.data.end;

    if (!route || !route.length) {
      alert("Ruta generată este goală!");
      return;
    }

    const coords = route.map(p => [p[1], p[0]]);
    if (currentPolyline) map.removeLayer(currentPolyline);
    currentPolyline = L.polyline(coords, { color: "green", weight: 5 }).addTo(map);
    map.fitBounds(currentPolyline.getBounds());

    addMarkers(startPoint, endPoint);
    lastPayload.value = {
      start: startPoint,
      end: endPoint,
      mode: custom.value.mode,
      route: route,
      type: "custom",
    };
    shareUrl.value = "";

  } catch (err) {
    console.error(err);
    alert("Eroare la generarea traseului Personalizat!");
  }
};
async function saveAndShare() {
  if (!lastPayload.value) {
    alert("Genereaza mai intai un traseu!");
    return;
  }

  try {
    const res = await api.post("/routes", {
      payload: lastPayload.value,
    });
    shareUrl.value = res.data.share_url;
  } catch (e) {
    const status = e?.response?.status;
    const msg =
      status === 401 ? "Unauthorized (token lipsa/invalid)." :
      status === 419 ? "CSRF / session expired." :
      status === 500 ? "Server error (vezi laravel.log)." :
      "Eroare necunoscuta.";

    console.error("SAVE ERROR:", status, e?.response?.data || e?.message);
    alert(msg);
  }

}

async function copyShareUrl() {
  if (!shareUrl.value) return;
  await navigator.clipboard.writeText(shareUrl.value);
  alert("Link copiat!");
}

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

.generate-btn.main {
  flex-shrink:0; width:auto;
  background-color:#2563eb; color:white;
  padding:10px 15px; border-radius:8px; border:none;
  cursor:pointer; font-weight:bold;
}
.generate-btn.main:hover { background-color:#1e40af; }

.generate-btn {
  display:block; width:auto;
  background-color:#2563eb; color:white;
  padding:10px 15px; border-radius:8px; border:none;
  cursor:pointer; font-weight:bold;
}
.generate-btn:hover { background-color:#1e40af; }

.input-btn {
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 15px;
  cursor: pointer;
}

select.input-btn {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
    padding-right: 30px;
}
select.input-btn option {
    background-color: #2563eb;
    color: white;
}

.input-btn::placeholder {
  color: white;
  opacity: 1;
}
.link-btn{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;   /* scoate underline */
  font: inherit;           /* foloseste acelasi font ca butoanele */
  line-height: normal;
}

.checkbox-btn {
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 15px;
  cursor: pointer;
  text-align: left;
}

.leaflet-control-zoom {
  display: none !important;
}

</style>
