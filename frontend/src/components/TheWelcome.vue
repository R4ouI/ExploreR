<script setup>
import { useRouter } from "vue-router";

const router = useRouter();

const goToMap = () => router.push("/map");
const goToLogin = () => router.push("/login");
</script>

<template>
  <div class="home-container">
    <!-- Top-right Login -->
    <button class="login-btn" @click="goToLogin">Login</button>

    <!-- Center Big Button -->
    <div class="center-content">
      <h1>ExploreR</h1>
      <button class="map-btn" @click="goToMap">Go to Map</button>
    </div>
  </div>
</template>

<style scoped>
.home-container {
  position: relative;
  width: 100vw;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #111;
  color: white;
  font-family: sans-serif;
}

html, body, #app {
  width: 100%;
  height: 100%;
  margin: 0;
  padding: 0;
}

/* Login button top-right */
.login-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  background: transparent;
  border: 1px solid white;
  padding: 8px 16px;
  border-radius: 6px;
  color: white;
  cursor: pointer;
  transition: 0.2s;
}

.login-btn:hover {
  background: white;
  color: black;
}

/* Center section */
.center-content {
  text-align: center;
}

.center-content h1 {
  font-size: 3rem;
  margin-bottom: 30px;
}

/* Big button to map */
.map-btn {
  font-size: 1.5rem;
  padding: 18px 40px;
  border-radius: 12px;
  border: none;
  background: #00aaff;
  color: white;
  cursor: pointer;
  transition: 0.2s;
}

.map-btn:hover {
  transform: scale(1.05);
  background: #0088dd;
}
</style>
