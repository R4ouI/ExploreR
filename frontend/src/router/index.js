import { createRouter, createWebHistory } from "vue-router";
import MapView from "../components/MapView.vue";
import LoginView from "../components/LoginView.vue";
import TheWelcome from "../components/TheWelcome.vue";
import SignupView from "../components/SignupView.vue";
import SharedRouteView from "../components/SharedRouteView.vue";
import SavedRoutesView from "../components/SavedRoutesView.vue";


const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: "/", name: "home", component: TheWelcome },
    {
      path: "/map",
      name: "map",
      component: MapView,
      meta: { requiresAuth: true }, // nu intra fara login
    },
    { path: "/login", name: "login", component: LoginView },
    { path: "/signup", name: "signup", component: SignupView },
    {
      path: "/share/:slug",
      name: "share",
      component: SharedRouteView,
    },
    {
      path: "/saved",
      name: "saved",
      component: SavedRoutesView,
      meta: { requiresAuth: true },
    },
    { path: "/share", name: "shareHub", component: SharedRouteView },
    { path: "/share/:slug", name: "share", component: SharedRouteView },



  ],
});

// simpla functie: esti logat daca ai token in localStorage
function isAuthenticated() {
  return !!localStorage.getItem("authToken");
}

// guard global
router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth && !isAuthenticated()) {
    return next("/login"); // trimite la login daca nu esti logat
  }
  next();
});

export default router;
