<template>
  <div class="page">
    <div class="card">
      <div class="header">
        <h2>Saved routes</h2>
        <div class="actions">
          <button class="btn" @click="goHome">Back</button>
          <button class="btn" @click="load" :disabled="loading">Refresh</button>
        </div>
      </div>

      <div v-if="error" class="error">{{ error }}</div>
      <div v-if="loading" class="muted">Loading...</div>

      <div v-if="!loading && routes.length === 0" class="muted">
        No saved routes yet. Generate one on the map and press “Save & Share”.
      </div>

      <div class="list" v-if="routes.length">
        <div class="item" v-for="r in routes" :key="r.slug">
          <div class="meta">
            <div class="title">
              <strong>{{ formatType(r.payload?.type) }}</strong>
              <span class="chip">{{ formatMode(r.payload?.mode) }}</span>
              <span class="chip" v-if="r.payload?.length">~{{ r.payload.length }} km</span>
              <span class="chip" v-if="r.payload?.loop">loop</span>
            </div>
            <div class="small">
              {{ new Date(r.created_at).toLocaleString() }}
            </div>
            <div class="small mono">
              /share/{{ r.slug }}
            </div>
          </div>

          <div class="buttons">
            <button class="btn" @click="openShare(r.slug)">Open</button>
            <button class="btn" @click="copyShare(r.slug)">Copy link</button>
            <button class="btn danger" @click="del(r.slug)">Delete</button>
          </div>
        </div>
      </div>

      <!-- pagination minimal -->
      <div class="pager" v-if="pagination">
        <button class="btn" @click="prev" :disabled="!pagination.prev_page_url || loading">Prev</button>
        <div class="muted">Page {{ pagination.current_page }} / {{ pagination.last_page }}</div>
        <button class="btn" @click="next" :disabled="!pagination.next_page_url || loading">Next</button>
      </div>
    </div>
  </div>
 

</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "../api";

const router = useRouter();
const routes = ref([]);
const pagination = ref(null);
const loading = ref(false);
const error = ref("");

const pageUrl = ref("/routes"); // backend returns paginated

function formatMode(m) {
  if (m === "cycling-regular") return "bike";
  if (m === "foot-hiking") return "walk";
  if (m === "driving-car") return "car";
  return m || "unknown";
}

function formatType(t) {
  return t === "custom" ? "Custom route" : "Random route";
}

function openShare(slug) {
  window.open(`/share/${slug}`, "_blank");
}

async function copyShare(slug) {
  const url = `${window.location.origin}/share/${slug}`;
  await navigator.clipboard.writeText(url);
  alert("Link copied!");
}

async function load(url = "/routes") {
  loading.value = true;
  error.value = "";
  try {
    const res = await api.get(url); // axios baseURL=/api
    routes.value = res.data.data || [];
    pagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page,
      next_page_url: res.data.next_page_url,
      prev_page_url: res.data.prev_page_url,
    };
    pageUrl.value = url;
  } catch (e) {
    console.error(e);
    const status = e?.response?.status;
    if (status === 401) error.value = "You are not logged in.";
    else error.value = "Failed to load saved routes.";
  } finally {
    loading.value = false;
  }
}

async function del(slug) {
  if (!confirm("Delete this route?")) return;
  try {
    await api.delete(`/routes/${slug}`);
    await load(pageUrl.value);
  } catch (e) {
    console.error(e);
    alert("Delete failed.");
  }
}

function prev() {
  if (pagination.value?.prev_page_url) {
    // backend returns full URL; axios can handle it, but safer to strip /api domain issues:
    load(pagination.value.prev_page_url.replace("http://127.0.0.1:8000/api", ""));
  }
}
function next() {
  if (pagination.value?.next_page_url) {
    load(pagination.value.next_page_url.replace("http://127.0.0.1:8000/api", ""));
  }
}

function goHome() {
  router.push("/");
}


onMounted(() => load());
</script>

<style scoped>
.page{
  height: 100vh;
  width: 100vw;
  margin: 0;
  padding: 0;
  background: #0b1220;
  display: flex;
}
.card{
  width: 100%;
  height: 100%;
  border-radius: 0;
  padding: 24px;
  overflow: auto; /* ca lista sa fie scroll daca e mare */
  background: rgba(255,255,255,0.06);
  border: none; /* optional */
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}
.actions { display:flex; gap:10px; }
.list { display:flex; flex-direction:column; gap:12px; margin-top: 12px; }
.item {
  display:flex;
  justify-content: space-between;
  gap: 12px;
  padding: 12px;
  border-radius: 14px;
  background: rgba(255,255,255,0.06);
}
.meta { display:flex; flex-direction:column; gap: 6px; }
.title { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.small { font-size: 12px; opacity: .8; }
.muted { opacity: .8; }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
.chip {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(255,255,255,0.12);
}
.buttons { display:flex; gap: 10px; align-items:center; }
.btn {
  border: none;
  padding: 8px 12px;
  border-radius: 10px;
  background: #2563eb;
  color: white;
  font-weight: 700;
  cursor: pointer;
}
.btn:disabled { opacity: .5; cursor: default; }
.danger { background: #dc2626; }
.error { background: rgba(220,38,38,0.2); padding: 10px 12px; border-radius: 10px; margin-top: 10px; }
.pager { display:flex; justify-content: space-between; align-items:center; margin-top: 14px; }
</style>
