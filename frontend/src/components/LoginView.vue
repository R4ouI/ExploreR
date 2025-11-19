<!-- src/views/LoginView.vue -->
<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import api from "../api"; // adjust path if needed

const router = useRouter();

const email = ref("");
const password = ref("");
const loading = ref(false);
const error = ref("");

const handleSubmit = async () => {
  error.value = "";
  loading.value = true;

  try {
    // adjust URL/body to match your backend (Laravel, etc.)
    const response = await api.post("/login", {
      email: email.value,
      password: password.value,
    });

    // example: save token / user in localStorage
    localStorage.setItem("token", response.data.token);

    // redirect after login
    router.push("/");
  } catch (err) {
    error.value = "Login failed. Check email or password.";
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="login-page">
    <h1>Login</h1>

    <form class="login-form" @submit.prevent="handleSubmit">
      <label>
        Email
        <input v-model="email" type="email" required />
      </label>

      <label>
        Password
        <input v-model="password" type="password" required />
      </label>

      <p v-if="error" class="error">{{ error }}</p>

      <button @click="router.push('/')" :disabled="loading">
        {{ loading ? "Logging in..." : "Login" }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 2rem;
  border-radius: 0.75rem;
  border: 1px solid #333;
}

input {
  width: 250px;
  padding: 0.5rem;
}

button {
  padding: 0.5rem 1rem;
  cursor: pointer;
}

.error {
  color: red;
  font-size: 0.9rem;
}
</style>
