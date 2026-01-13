<template>
  <div class="layout">
    <aside class="sidebar">
      <div class="side-title">Shared feed</div>

      <div class="cards">
        <div
          class="card"
          v-for="it in feed"
          :key="it.slug"
          @click="openFromFeed(it.slug)"
          :class="{ active: it.slug === currentSlug }"
        >
          <div class="card-top">
            <div class="author">{{ it.user_name }}</div>
            <div class="date">{{ formatDate(it.created_at) }}</div>
          </div>

          <div class="card-bottom">
            <button class="pill action" @click.stop="toggleLike(it)">
              ❤️ {{ it.likes_count }}
            </button>

            <button class="pill action" @click.stop="openComments(it)">
              💬 {{ it.comments_count }}
            </button>

            <div class="pill slug">{{ it.slug.slice(0, 8) }}...</div>
          </div>

        </div>

        <div class="empty" v-if="feed.length === 0 && !feedError">
          No shared routes yet.
        </div>
        <div class="empty err" v-if="feedError">
          {{ feedError }}
        </div>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <button class="btn" @click="copyLink" :disabled="!loaded">Copy link</button>
        <button class="btn" @click="openGoogleMaps" :disabled="!loaded">Open Google Maps</button>
        <button class="btn" @click="openWaze" :disabled="!loaded">Open Waze</button>
      </div>

      <div class="info" v-if="error">{{ error }}</div>
      <div class="hint" v-if="!currentSlug && !error">Pick a route from the left feed.</div>

      <div id="map" class="map"></div>

      <div class="modal-backdrop" v-if="commentsOpen" @click="closeComments">
        <div class="modal" @click.stop>
          <div class="modal-head">
            <div class="modal-title">Comments · {{ commentsSlug.slice(0, 8) }}...</div>
            <button class="x" @click="closeComments">✕</button>
          </div>

          <div class="modal-body">
            <div v-if="commentsBusy" class="muted">Loading...</div>

            <div v-else class="comments">
              <div class="comment" v-for="c in commentsList" :key="c.id">
                <div class="comment-top">
                  <div class="c-author">{{ c.user_name }}</div>
                  <div class="c-date">{{ formatDate(c.created_at) }}</div>
                </div>
                <div class="c-text">{{ c.text }}</div>
              </div>

              <div class="muted" v-if="commentsList.length === 0">
                No comments yet.
              </div>
            </div>
          </div>

          <div class="modal-foot">
            <input
              class="input"
              v-model="commentsText"
              placeholder="Write a comment..."
              :disabled="commentsBusy"
              @keydown.enter="postComment"
            />
            <button class="btn" @click="postComment" :disabled="commentsBusy || !commentsText.trim()">
              Send
            </button>
          </div>
        </div>
      </div>

    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../api";

import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet-defaulticon-compatibility";
import "leaflet-defaulticon-compatibility/dist/leaflet-defaulticon-compatibility.css";

const r = useRoute();
const router = useRouter();

const loaded = ref(false);
const error = ref("");

const feed = ref([]);
const feedError = ref("");

const commentsOpen = ref(false);
const commentsSlug = ref("");
const commentsList = ref([]);
const commentsText = ref("");
const commentsBusy = ref(false);


let payload = null;
let map = null;
let poly = null;
let startMarker = null;
let endMarker = null;

const currentSlug = computed(() => r.params.slug || "");

function toLatLng(pointsLonLat) {
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

function formatDate(x) {
  try {
    return new Date(x).toLocaleString();
  } catch {
    return "";
  }
}

function openFromFeed(slug) {
  router.push(`/share/${slug}`);
}

function clearRouteOnMap() {
  if (poly) { map.removeLayer(poly); poly = null; }
  if (startMarker) { map.removeLayer(startMarker); startMarker = null; }
  if (endMarker) { map.removeLayer(endMarker); endMarker = null; }
  payload = null;
  loaded.value = false;
}

async function loadFeed() {
  feedError.value = "";
  try {
    const res = await api.get("/shares");
    feed.value = res.data.items || [];
  } catch (e) {
    feedError.value = "Feed not available / server error.";
    console.error(e);
  }
}

async function loadRoute(slug) {
  error.value = "";
  clearRouteOnMap();

  if (!slug) {
    map.setView([45.94, 24.96], 7);
    return;
  }

  try {
    const res = await api.get(`/routes/${slug}`);
    payload = res.data.payload;

    const latlng = toLatLng(payload.route);
    poly = L.polyline(latlng, { color: "blue", weight: 5 }).addTo(map);

    startMarker = L.marker([payload.start[1], payload.start[0]]).addTo(map);
    endMarker = L.marker([payload.end[1], payload.end[0]]).addTo(map);

    map.fitBounds(poly.getBounds());
    loaded.value = true;
  } catch (e) {
    error.value = "Route not found / server error.";
    console.error(e);
  }
}

async function toggleLike(item) {
  try {
    const res = await api.post(`/routes/${item.slug}/like`, null);
    item.likes_count = res.data.likes_count;
  } catch (e) {
    console.error(e);
    alert("Nu pot da like acum (verifica login / token).");
  }
}

async function openComments(item) {
  commentsSlug.value = item.slug;
  commentsText.value = "";
  commentsList.value = [];
  commentsOpen.value = true;
  await loadComments(item.slug);
}

async function loadComments(slug) {
  commentsBusy.value = true;
  try {
    const res = await api.get(`/routes/${slug}/comments`);
    commentsList.value = res.data.items || [];
  } catch (e) {
    console.error(e);
    alert("Nu pot incarca comentariile.");
  } finally {
    commentsBusy.value = false;
  }
}

async function postComment() {
  const slug = commentsSlug.value;
  const text = commentsText.value.trim();
  if (!slug || !text) return;

  commentsBusy.value = true;
  try {
    await api.post(`/routes/${slug}/comments`, { text });
    commentsText.value = "";
    await loadComments(slug);

    const it = feed.value.find((x) => x.slug === slug);
    if (it) it.comments_count = (it.comments_count || 0) + 1;
  } catch (e) {
    console.error(e);
    alert("Nu pot posta comentariul (verifica login / token).");
  } finally {
    commentsBusy.value = false;
  }
}

function closeComments() {
  commentsOpen.value = false;
}


onMounted(async () => {
  map = L.map("map").setView([45.94, 24.96], 7);
  setTimeout(() => map.invalidateSize(), 200);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
  }).addTo(map);

  await loadFeed();
  await loadRoute(currentSlug.value);
});

watch(
  () => r.params.slug,
  async (newSlug) => {
    await loadRoute(newSlug || "");
  }
);
</script>

<style scoped>
.layout {
  height: 100vh;
  width: 100vw;
  display: flex;
  overflow: hidden;
}

.sidebar {
  width: 380px;
  border-right: 1px solid rgba(0,0,0,0.08);
  background: #0b1220;
  color: white;
  padding: 14px;
  overflow: auto;
}

.side-title {
  font-weight: 900;
  font-size: 18px;
  margin-bottom: 12px;
}

.cards {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.card {
  border-radius: 14px;
  padding: 12px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.12);
  cursor: pointer;
  transition: transform 0.08s ease, background 0.08s ease;
}
.card:hover {
  transform: translateY(-1px);
  background: rgba(255,255,255,0.10);
}
.card.active {
  background: rgba(37, 99, 235, 0.22);
  border-color: rgba(37, 99, 235, 0.45);
}

.card-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}
.author {
  font-weight: 900;
}
.date {
  font-size: 12px;
  opacity: 0.75;
}

.card-bottom {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.pill {
  font-size: 12px;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255,255,255,0.10);
  border: 1px solid rgba(255,255,255,0.14);
}
.pill.slug {
  opacity: 0.8;
}

.empty {
  padding: 10px 4px;
  opacity: 0.8;
  font-size: 13px;
}
.empty.err {
  color: #ffb4b4;
}

.main {
  flex: 1;
  position: relative;
}

.map {
  height: 100%;
  width: 100%;
}

.topbar {
  position: absolute;
  top: 16px;
  left: 16px;
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
  bottom: 16px;
  left: 16px;
  z-index: 1000;
  background: white;
  padding: 10px 12px;
  border-radius: 10px;
}

.hint {
  color: #000;
  position: absolute;
  bottom: 16px;
  left: 16px;
  z-index: 1000;
  background: rgba(255,255,255,0.92);
  padding: 10px 12px;
  border-radius: 10px;
}

.pill.action {
  cursor: pointer;
  border: 1px solid rgba(255,255,255,0.18);
  color: #ffffff;
}
.pill.action:hover {
  background: rgba(255,255,255,0.14);
}

.pill.slug{
  color: #a7a7a7;
}

.modal-backdrop {
  position: absolute;
  inset: 0;
  z-index: 2000;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
}

.modal {
  width: min(520px, 92vw);
  max-height: 80vh;
  background: white;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 40px rgba(0,0,0,0.25);
}

.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}
.modal-title { color: #5f5f5f; font-weight: 900; }
.x {
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 16px;
  padding: 6px 10px;
}

.modal-body {
  padding: 12px 14px;
  overflow: auto;
  flex: 1;
}

.comments { display: flex; flex-direction: column; gap: 10px; }
.comment {
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 12px;
  padding: 10px 10px;
}
.comment-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}
.c-author { font-weight: 800; }
.c-date { font-size: 12px; opacity: 0.65; }
.c-text { font-size: 14px; }

.modal-foot {
  display: flex;
  gap: 10px;
  padding: 12px 14px;
  border-top: 1px solid rgba(0,0,0,0.08);
}

.input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid rgba(0,0,0,0.15);
  border-radius: 12px;
  outline: none;
}

.muted { color: #000; opacity: 0.65; font-size: 13px; }

.c-author {
  color: #222;
}

.c-text {
  color: #333;
}
</style>
