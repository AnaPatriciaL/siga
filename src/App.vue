/* eslint-disable */
<template>
  <v-app>
    <v-navigation-drawer
      app
      class="grey darken-3"
      v-model="drawer"
      v-if="isAuthenticated"
    >
      <!-- Navigation Drawer Header -->
      <v-list-item class="grey darken-3 py-1">
        <v-list-item-content class="text-center">
          <v-list-item-title class="title white--text center"
            >SIGA</v-list-item-title
          >
        </v-list-item-content>
      </v-list-item>

      <v-divider></v-divider>

      <v-list dense class="grey darken-3">
        <!-- Principal -->
        <v-list-item
          link
          :to="{ name: 'Principal' }"
          @click="cerrarOpcion"
          active-class="menu-item--active"
          exact
          class="grey--text"
        >
          <v-list-item-icon>
            <v-icon :color="isActive('Principal') ? 'white' : 'teal'"
              >mdi-home</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title :class="{ 'white--text': isActive('Principal') }"
              >Principal</v-list-item-title
            >
          </v-list-item-content>
        </v-list-item>

        <!-- Opciones de usuario dinámicas -->
        <v-list-item
          v-for="opcion in opcionesMenu"
          :key="opcion.orden"
          :to="{ name: opcion.ruta }"
          @click="cerrarOpcion"
          active-class="menu-item--active"
          class="grey--text"
        >
          <v-list-item-icon>
            <v-icon :color="isActive(opcion.ruta) ? 'white' : (opcion.color || 'grey')">
              {{ opcion.icono || "mdi-view-dashboard" }}
              <!-- Icono por defecto si no hay color o icono -->
            </v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title
              :class="{ 'white--text': isActive(opcion.ruta) }"
              >{{ opcion.nombre_opcion }}</v-list-item-title
            >
          </v-list-item-content>
        </v-list-item>
        <!-- Acerca de (opción fija al final del menú) -->
        <v-list-item link :to="{ name: 'Acercade' }" @click="cerrarOpcion" active-class="menu-item--active" class="grey--text">
          <v-list-item-icon>
            <v-icon :color="isActive('Acercade') ? 'white' : 'blue-grey'">mdi-information</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title :class="{ 'white--text': isActive('Acercade') }">Acerca de</v-list-item-title>
          </v-list-item-content>
        </v-list-item>

        <v-spacer></v-spacer>
        <div class="bottom-item">
          <v-list-item link @click="logout" active-class="menu-item--active">
            <v-list-item-icon>
              <v-icon class="white--text">mdi-account</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title class="white--text bold"
                >Salir</v-list-item-title
              >
            </v-list-item-content>
          </v-list-item>
        </div>
      </v-list>
    </v-navigation-drawer>

    <!-- App Toolbar -->
    <v-app-bar app color="pink darken-4" dark v-if="isAuthenticated">
      <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title>Sistema Integral de Gestión de Auditorías</v-toolbar-title>
      <v-spacer></v-spacer>
      <span v-if="isAuthenticated" class="white--text"
        >Bienvenido, {{ usuarioLogueado }}</span
      >
    </v-app-bar>

    <!-- Main Content -->
    <v-main class="grey lighten-2">
      <router-view></router-view>
    </v-main>
  </v-app>
</template>

<script>
import Swal from 'sweetalert2';
import api from "@/services/apiUrls";

export default {
  name: "App",
  data() {
    return {
      drawer: false,
      isAuthenticated: !!localStorage.getItem("token"),
      usuarioLogueado: localStorage.getItem("nombre") || "",
      opcionesMenu: [],
    };
  },
  mounted() {
    document.title = "SATES - SIGA";
    this.checkAuthentication();
    this.$root.$on("authenticated", this.checkAuthentication);
  },
  methods: {
    async cargarOpcionesMenu() {
      try {
        const usuarioId = localStorage.getItem("id");
        if (!usuarioId) {
          console.warn("No se encontró un ID de usuario en localStorage.");
          return;
        }

        const response = await fetch(api.obtenerOpcionesMenu(usuarioId));

        // Validar si la respuesta es correcta
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }

        // Validar que la respuesta sea un JSON válido
        const responseText = await response.text();
        if (!responseText) {
          throw new Error("La respuesta del servidor está vacía.");
        }

        const data = JSON.parse(responseText);
        if (!data || typeof data !== "object") {
          throw new Error("La respuesta del servidor no es un JSON válido.");
        }

        if (data.success) {
          const menuOrdenado = [...data.data].sort(
            (a, b) => Number(a.orden) - Number(b.orden)
          );
          this.opcionesMenu = menuOrdenado;
        } else {
          throw new Error(data.message || "Error desconocido en el servidor.");
        }

      } catch (error) {
        console.error("Error al cargar las opciones del menú:", error.message);
        Swal.fire(
          "Error",
          `No se pudieron cargar las opciones del menú: ${error.message}`,
          "error"
        );
      }
    },
   
    cerrarOpcion() {
      this.drawer = false;
    },
    isActive(routeName) {
      return this.$route.name === routeName;
    },
    logout() {
      // Redirigir al usuario a la vista de login
      localStorage.removeItem("id");
      localStorage.removeItem("token");
      localStorage.removeItem("nombre");
      localStorage.removeItem("nivel");
      this.$user.id = null;
      this.$user.nombre = null;
      this.$user.nivel = null;
      this.isAuthenticated = false;
      this.usuarioLogueado = "";
      this.opcionesMenu = [];
      this.$router.push("/login");
    },
    
    checkAuthentication() {
      const token = localStorage.getItem("token");
      const id = localStorage.getItem("id");
      const nombre = localStorage.getItem("nombre");
      const nivel = localStorage.getItem("nivel");

      // Convierte el token en un valor booleano (true si existe, false si no)
      this.isAuthenticated = !!token; 
      this.usuarioLogueado = nombre || "";

      // Validar si los datos esenciales existen
      if (!token || !id || !nombre) {
        console.warn("Faltan datos en localStorage. El usuario debe iniciar sesión nuevamente.");
        this.isAuthenticated = false;
        localStorage.clear(); // Limpia datos corruptos

        // Evita navegación redundante
        if (this.$route.path !== "/login") {
          this.$router.push("/login").catch(err => {
            if (err.name !== "NavigationDuplicated") {
              console.error("Error en la navegación:", err);
            }
          });
        }
        return;
      }

      // Si está autenticado, carga el menú
      if (this.isAuthenticated) {
        this.cargarOpcionesMenu();
      }
    }

  },
};
</script>

<style scoped>
.menu-item--active {
  background-color: #880e4f;
}
.bottom-item {
  position: absolute;
  bottom: 0;
  width: 100%;
}
  .no-print {
    display: none !important;
  }
@media print {
  .v-app-bar {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
    min-height: 40px !important;
  }

  .v-toolbar__content {
    padding: 0 !important;
    min-height: 40px !important;
  }

  .v-app-bar-nav-icon,
  .v-toolbar-title,
  .v-spacer,
  .white--text {
    font-size: 12px !important;
    margin: 0 !important;
  }
}
  
</style>
