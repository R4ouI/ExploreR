<script setup>
import { ref, onMounted } from "vue";
import api from "../api";

const message = ref("Se încarcă...");

onMounted(async () => {
  try {
    const response = await api.get("/hello");
    message.value = response.data.message;
  } catch (error) {
    console.error(error);
    message.value = "Eroare la conectarea cu backend-ul!";
  }
});
</script>

<template>
  <main class="flex flex-col items-center justify-center min-h-screen text-center">
    <h1 class="text-4xl font-bold text-blue-600 mb-4">ExploreR</h1>
    <p class="text-lg text-gray-700">{{ message }}</p>
  </main>
</template>

<style>
body {
  background-color: #f7fafc;
  font-family: sans-serif;
}
</style>
