<template>
  <button v-if="show" class="back-btn" @click="goWelcome">
    ⬅ Welcome
  </button>
</template>

<script setup>
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";

const router = useRouter();
const route = useRoute();

// ascunde butonul chiar pe welcome (ruta ta e "/")
const show = computed(() => route.path !== "/");

function goWelcome() {
  router.push("/");
}
</script>

<style scoped>
.back-btn {
  position: fixed;
  left: 20px;
  bottom: 20px;
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.25);
  background: rgba(0,0,0,0.55);
  color: white;
  cursor: pointer;
  z-index: 9999;
  backdrop-filter: blur(8px);
}
.back-btn:hover {
  background: rgba(0,0,0,0.75);
}
</style>
