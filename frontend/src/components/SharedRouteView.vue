<template>
  <div class="wrap">
    <div class="topbar">
      <button class="btn" @click="copyLink" :disabled="!loaded">Copy link</button>
      <button class="btn" @click="openGoogleMaps" :disabled="!loaded">Open Google Maps</button>
      <button class="btn" @click="openWaze" :disabled="!loaded">Open Waze</button>
    </div>

    <div class="info" v-if="error">{{ error }}</div>
    <div id="map" class="map"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import api from "../api";

import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet-defaulticon-compatibility";
import "leaflet-defaulticon-compatibility/dist/leaflet-defaulticon-compatibility.css";

const r = useRoute();
const loaded = ref(false);
const error = ref("");

let payload = null;
let map, poly;

function toLatLng(pointsLonLat) {
  // backend payload route: [[lon,lat], ...]
  return pointsLonLat.map((p) => [p[1], p[0]]);
}

function sampleWaypoints(points, max = 8) {
  if (!points || points.length < 2) return [];
  const step = Math.floor(points.length / (max + 1));
  const wps = [];
  for (let i = 1; i <= max; i++) {
    const idx = i * step;
    if (idx > 0 && idx < points.length - 1) wps.push(points[idx]);
  }
  return wps;
}

function googleMode(mode) {
  if (mode === "cycling-regular") return "bicycling";
  if (mode === "foot-hiking") return "walking";
  return "driving";
}

function googleUrl(p) {
  const origin = `${p.start[1]},${p.start[0]}`;
  const destination = `${p.end[1]},${p.end[0]}`;
  const waypoints = sampleWaypoints(p.route).map(pt => `${pt[1]},${pt[0]}`).join("|");

  const qs = new URLSearchParams({
    api: "1",
    origin,
    destination,
    travelmode: googleMode(p.mode),
  });
  if (waypoints) qs.set("waypoints", waypoints);

  return `https://www.google.com/maps/dir/?${qs.toString()}`;
}

function wazeUrl(p) {
  const ll = `${p.end[1]},${p.end[0]}`;
  const qs = new URLSearchParams({ ll, navigate: "yes" });
  return `https://waze.com/ul?${qs.toString()}`;
}

function openGoogleMaps() {
  if (!payload) return;
  window.open(googleUrl(payload), "_blank");
}

function openWaze() {
  if (!payload) return;
  window.open(wazeUrl(payload), "_blank");
}

async function copyLink() {
  await navigator.clipboard.writeText(window.location.href);
  alert("Link copied!");
}

onMounted(async () => {
  map = L.map("map").setView([45.94, 24.96], 7);
  setTimeout(() => map.invalidateSize(), 200);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
  }).addTo(map);

  try {
    const slug = r.params.slug;
    const res = await api.get(`/routes/${slug}`);
    payload = res.data.payload;

    const latlng = toLatLng(payload.route);
    poly = L.polyline(latlng, { color: "blue", weight: 5 }).addTo(map);

    L.marker([payload.start[1], payload.start[0]]).addTo(map);
    L.marker([payload.end[1], payload.end[0]]).addTo(map);

    map.fitBounds(poly.getBounds());
    loaded.value = true;
  } catch (e) {
    error.value = "Route not found / server error.";
    console.error(e);
  }
});
</script>

<style scoped>
.wrap { height: 100vh; width: 100vw; position: relative; }
.map { height: 100%; width: 100%; }
.topbar {
  position: absolute;
  top: 16px; left: 16px;
  z-index: 1000;
  display: flex;
  gap: 10px;
}
.btn {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  background: #2563eb;
  color: white;
  font-weight: 700;
  cursor: pointer;
}
.btn:disabled { opacity: .5; cursor: default; }
.info {
  position: absolute;
  bottom: 16px; left: 16px;
  z-index: 1000;
  background: white;
  padding: 10px 12px;
  border-radius: 10px;
}
</style>
