import axios from "axios";

// Axios configurat să folosească proxy-ul Vite
export default axios.create({
  baseURL: "/api",   // orice cerere aici va fi redirecționată de Vite la backend
  timeout: 10000,    // timeout 10 secunde
});