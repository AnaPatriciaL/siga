/* eslint-disable */
import Vue from "vue";
import Router from "vue-router";
import Login from "@/views/Login.vue";
import Principal from "@/views/Principal.vue";
import ConsultasIE from "@/views/ConsultasIE.vue";
import ProspectosIE from "@/views/ProspectosIE.vue";
import ListasIE from "@/views/ListasIE.vue";
import ComitesIE from "@/views/ComitesIE.vue";
import AutorizadosIE from "@/views/AutorizadosIE.vue";
import EmitidasIE from "@/views/EmitidasIE.vue";
import FoliosIE from "@/views/FoliosIE.vue";
import Usuarios from "@/views/Usuarios.vue";
import Permisos from "@/views/Permisos.vue";

// import Buscar from "@/views/Buscar.vue";

Vue.use(Router);

const router = new Router({
  mode: "hash",
  routes: [
    {
      path: "/",
      name: "Principal",
      component: Principal,
      meta: { requiresAuth: true },
    },
    {
      path: "/consultasIE",
      name: "ConsultasIE",
      component: ConsultasIE,
      meta: { requiresAuth: true },
    },
    {
      path: "/prospectosIE",
      name: "ProspectosIE",
      component: ProspectosIE,
      meta: { requiresAuth: true },
    },
    {
      path: "/listasIE",
      name: "ListasIE",
      component: ListasIE,
      meta: { requiresAuth: true },
    },
    {
      path: "/comitesIE",
      name: "ComitesIE",
      component: ComitesIE,
      meta: { requiresAuth: true },
    },
    {
      path: "/autorizadosIE",
      name: "AutorizadosIE",
      component: AutorizadosIE,
      meta: { requiresAuth: true },
    },
    {
      path: "/EmitidasIE",
      name: "EmitidasIE",
      component: EmitidasIE,
      meta: { requiresAuth: true },
    },
    {
      path: "/FoliosIE",
      name: "FoliosIE",
      component: FoliosIE,
      meta: { requiresAuth: true },
    },
    {
      path: "/usuarios",
      name: "Usuarios",
      component: Usuarios,
      meta: { requiresAuth: true },
    },
    {
      path: "/permisos",
      name: "Permisos",
      component: Permisos,
      meta: { requiresAuth: true },
    },
    { path: "/login", name: "login", component: Login },
  ],
});

// Guard de navegación
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");

  // Si el usuario está autenticado y quiere ir a /login, redirigir a la ruta principal
  if (token) {
    if (to.name === "login") {
      return next({ name: "Principal" });
    }
    return next(); // Permitir acceso a cualquier otra ruta si está autenticado
  }

  // Si no está autenticado, permitir acceso solo a /login
  if (to.name === "login") {
    return next();
  }

  // En cualquier otro caso, redirigir a /login
  return next({ name: "login" });
});

export default router;
