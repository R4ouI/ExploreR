import { createRouter, createWebHistory } from "vue-router";
import MapView from "../components/MapView.vue";
import LoginView from "../components/LoginView.vue";        // <-- new import
import TheWelcome from "../components/TheWelcome.vue";        // <-- new import



const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: "/home", name: "home", component: TheWelcome },
    {
      path: "/map",
      name: "map",
      component: MapView,
},
    { path: "/login", name: "login", component: LoginView },
  ],
});

export default router;
