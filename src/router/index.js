/* eslint-disable */
import Vue from "vue";
import Router from "vue-router";
import Login from "@/views/Login.vue";
import Principal from "@/views/Principal.vue";
import Consultas from "@/views/Consultas.vue";
import Prospectos from "@/views/Prospectos.vue";
import Listas from "@/views/Listas.vue";
import Comites from "@/views/Comites.vue";
import Autorizados from "@/views/Autorizados.vue";
import Emitidas from "@/views/Emitidas.vue";
import Folios from "@/views/Folios.vue";
import Canceladas from "@/views/Canceladas.vue";
import Usuarios from "@/views/Usuarios.vue";
import Permisos from "@/views/Permisos.vue";
import Programadores from "@/views/Programadores.vue";
import Oficinas from "@/views/Oficinas.vue";

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
      path: "/consultas",
      name: "Consultas",
      component: Consultas,
      meta: { requiresAuth: true },
    },
    {
      path: "/prospectos",
      name: "Prospectos",
      component: Prospectos,
      meta: { requiresAuth: true },
    },
    {
      path: "/listas",
      name: "Listas",
      component: Listas,
      meta: { requiresAuth: true },
    },
    {
      path: "/comites",
      name: "Comites",
      component: Comites,
      meta: { requiresAuth: true },
    },
    {
      path: "/autorizados",
      name: "Autorizados",
      component: Autorizados,
      meta: { requiresAuth: true },
    },
    {
      path: "/Emitidas",
      name: "Emitidas",
      component: Emitidas,
      meta: { requiresAuth: true },
    },
    {
      path: "/Folios",
      name: "Folios",
      component: Folios,
      meta: { requiresAuth: true },
    },
    {
      path: "/Canceladas",
      name: "Canceladas",
      component: Canceladas,
      meta: { requiresAuth: true },
    },
    {
      path: "/usuarios",
      name: "Usuarios",
      component: Usuarios,
      meta: { requiresAuth: true },
    },
    {
      path: "/Programadores",
      name: "Programadores",
      component: Programadores,
      meta: { requiresAuth: true },
    },
    {
      path: "/Oficinas",
      name: "Oficinas",
      component: Oficinas,
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
