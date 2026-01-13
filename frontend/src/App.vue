<template>
  <router-view />
  <BackToWelcome />
</template>

<script setup>
import BackToWelcome from "@/components/BackToWelcome.vue";
</script>


<style>
html, body, #app {
  margin: 0;
  padding: 0;
  height: 100%;
  width: 100%;
}
</style>
