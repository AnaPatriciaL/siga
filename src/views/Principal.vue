<template>
  <!-- <v-container fluid v-if="esNivel0 && ambitoUsuario === 'Estatales'">
    <dashboard></dashboard>
  </v-container>
  <v-container fluid v-if="esNivel0 && ambitoUsuario === 'Federales'">
    <dashboard-federales></dashboard-federales>
  </v-container>
  <v-container fluid v-if="esNivel0 && ambitoUsuario === 'Ambos'">
    <dashboard/>
    <dashboard-federales/>
  </v-container> -->
  <v-container v-if="esNivel0" fluid>
    <dashboard v-if="ambitoUsuario === 'Estatales'" />
    <dashboard-federales v-else-if="ambitoUsuario === 'Federales'" />
    <template v-else-if="ambitoUsuario === 'Ambos'">
      <dashboard />
      <dashboard-federales />
    </template>
  </v-container>
  <v-container fill-height  v-else>
    <v-row  justify="center" align="center" class="text-center">
      <v-col cols="12" class="d-flex flex-column align-center">
        <v-img
          src="@/assets/img/logo_sates_digital.png"
          max-width="550px"
          class="mx-auto"
        ></v-img>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
  import Dashboard from "@/views/Dashboard.vue";
  import DashboardFederales from "./DashboardFederales.vue";

export default {
  name: "Principal",
  data() {
    return {
      nivelUsuario: Number(localStorage.getItem("siga_nivel") || 99),
      ambitoUsuario: localStorage.getItem("siga_acceso_ambito") || "Estatales"
    };
  },
  components: {
    Dashboard,
    DashboardFederales
  },
  computed: {
    esNivel0() {
      return this.nivelUsuario === 0;
    }
  },
  watch: {
    '$route'() {
      this.ambitoUsuario = localStorage.getItem("siga_ambito_seleccionado") || "Estatales";
    }
  }
};
</script>

<style scoped>
.mx-auto {
  margin-left: auto;
  margin-right: auto;
}
.mt-4 {
  margin-top: 16px;
}
</style>

<!-- <template>
  <v-container class="d-flex align-center justify-center" style="height: 100vh;">
    <v-row class="d-flex justify-center align-center ma-0">
        <v-col cols="l2" sm="12" class="align-center" >
          <div class="my-5 pl-3">
            <v-img 
                max-height="120"
                max-width="280"
                justify="center"
                src="/img/logo_sates_digital.png"></v-img>
          </div>
        </v-col>
      </v-row>
  </v-container>
</template> -->

<!-- <script>
  export default {
    name: "Principal",
    
  };
  </script> -->

<!-- <h1>Página Principal</h1>
<p>Bienvenido a la página principal de la aplicación de arministracion de EFOS del Estado de Sinaloa.</p> -->
