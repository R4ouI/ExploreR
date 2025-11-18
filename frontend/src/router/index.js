import { createRouter, createWebHistory } from "vue-router";
import MapView from "../components/MapView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "map",
      component: MapView,
    },
  ],
});

export default router;
