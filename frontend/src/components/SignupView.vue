<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import api from "../api";

const router = useRouter();

const name = ref("");
const email = ref("");
const password = ref("");
const passwordConfirm = ref("");
const loading = ref(false);
const error = ref("");

const handleSubmit = async () => {
  error.value = "";

  if (password.value !== passwordConfirm.value) {
    error.value = "Parolele nu coincid.";
    return;
  }

  loading.value = true;

  try {
    console.log("Sending register request...", email.value.trim());

    const response = await api.post("/register", {
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
    });

    console.log("REGISTER OK:", response.data);

    localStorage.setItem("authToken", response.data.token);
    localStorage.setItem("authUser", JSON.stringify(response.data.user));

    router.push("/");
  } catch (err) {
    console.log(
      "REGISTER ERROR:",
      err.response?.status,
      err.response?.data || err.message
    );
    error.value =
      err.response?.data?.message || "Nu s-a putut crea contul.";
  } finally {
    loading.value = false;
  }
};

const goToLogin = () => {
  router.push("/login");
};
</script>

<template>
  <div class="signup-page">
    <div class="signup-card">
      <h1>Sign up</h1>

      <form class="signup-form" @submit.prevent="handleSubmit">
        <label>
          Nume
          <input v-model="name" type="text" required />
        </label>

        <label>
          Email
          <input v-model="email" type="email" required />
        </label>

        <label>
          Parola
          <input v-model="password" type="password" required />
        </label>

        <label>
          Confirma parola
          <input v-model="passwordConfirm" type="password" required />
        </label>

        <p v-if="error" class="error">{{ error }}</p>

        <button type="submit" :disabled="loading">
          {{ loading ? "Se creeaza contul..." : "Creeaza cont" }}
        </button>
      </form>

      <div class="login-section">
        <span>Ai deja cont?</span>
        <button class="login-btn" @click="goToLogin">
          Intra in cont
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.signup-page {
  height: 100vh;
  width: 100vw;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #111;
  color: #fff;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
    sans-serif;
}

.signup-card {
  background: #181818;
  padding: 2rem 2.5rem;
  border-radius: 1rem;
  border: 1px solid #333;
  min-width: 340px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
}

.signup-card h1 {
  margin-bottom: 1.5rem;
  text-align: center;
}

.signup-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

label {
  display: flex;
  flex-direction: column;
  font-size: 0.9rem;
  gap: 0.3rem;
}

input {
  padding: 0.5rem 0.6rem;
  border-radius: 0.5rem;
  border: 1px solid #444;
  background: #111;
  color: #fff;
}

button {
  margin-top: 0.75rem;
  padding: 0.6rem 1rem;
  border-radius: 0.7rem;
  border: none;
  cursor: pointer;
  font-weight: 600;
  background: #00aaff;
  color: #fff;
  transition: transform 0.15s ease, background 0.15s ease, opacity 0.15s ease;
}

button:hover:not(:disabled) {
  transform: translateY(-1px);
  background: #0088dd;
}

button:disabled {
  opacity: 0.6;
  cursor: default;
}

.error {
  color: #ff4d4f;
  font-size: 0.85rem;
}

.login-section {
  margin-top: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.login-btn {
  padding: 0.4rem 0.9rem;
  border-radius: 0.6rem;
  border: 1px solid #00aaff;
  background: transparent;
  color: #00aaff;
  cursor: pointer;
  transition: 0.15s ease;
}

.login-btn:hover {
  background: #00aaff;
  color: #fff;
}
</style>
