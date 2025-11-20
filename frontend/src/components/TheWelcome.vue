<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

// read login state from localStorage
const user = computed(() => {
  const stored = localStorage.getItem("authUser");
  return stored ? JSON.parse(stored) : null;
});

const goToMap = () => router.push("/map");
const goToLogin = () => router.push("/login");

const logout = () => {
  localStorage.removeItem("authToken");
  localStorage.removeItem("authUser");
  router.push("/login");
};
</script>

<template>
  <div class="home-container">
    <!-- Top-right Login / User state -->
    <div class="top-right">
      <template v-if="user">
        <span class="username">Hello, {{ user.name }}</span>
        <button class="logout-btn" @click="logout">Logout</button>
      </template>

      <template v-else>
        <button class="login-btn" @click="goToLogin">Login</button>
      </template>
    </div>

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
  font-family: sans-serif;
  color: white;

  /* Background image */
  background-image: url("/src/assets/backgroundimg.png");
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;

  /* Dark overlay for low opacity effect */
  background-color: rgba(0, 0, 0, 0.6);
  background-blend-mode: darken;
}


/* Top-right user/login area */
.top-right {
  position: absolute;
  top: 20px;
  right: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.username {
  font-size: 1rem;
  opacity: 0.9;
}

/* Login button */
.login-btn {
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

/* Logout button */
.logout-btn {
  background: #ff4444;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  color: white;
  cursor: pointer;
  transition: 0.2s;
}

.logout-btn:hover {
  background: #cc0000;
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
