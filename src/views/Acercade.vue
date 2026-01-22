<template> 
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="9" lg="7">

        <div class="pa-6 text-center about-wrapper">

          <!-- LOGO -->
          <v-img
            src="@/assets/img/logo_sates_digital.png"
            max-width="220"
            class="mx-auto mb-10"
            contain
          />

          <!-- TITULO -->
          <h1 class="titulo-acerca">
            Sistema SATES Digital
          </h1>

          <!-- DESCRIPCION -->
          <v-row justify="center">
            <v-col cols="12" sm="9">
              <p class="descripcion">
                Plataforma institucional diseñada para optimizar la gestión,
                control y seguimiento de los procesos administrativos del SATES,
                garantizando eficiencia y confiabilidad en la información.
              </p>
            </v-col>
          </v-row>

          <v-divider class="divider-institucional"></v-divider>

          <!-- EQUIPO -->
          <div class="seccion">
            <v-icon class="icono-acerca">mdi-account-group</v-icon>
            <p class="subtitulo">Equipo de desarrollo SATES</p>
            <p class="texto-secundario">
              Saúl Abraham Gonzalez Avitia<br />
              Ana Patricia López Valenzuela<br />
              Flavio Paredes Ramos
            </p>
          </div>

          <!-- AÑO -->
          <div class="seccion">
            <p class="subtitulo">Año de desarrollo</p>
            <p class="texto-secundario">2025</p>
          </div>

          <!-- TECNOLOGIAS -->
          <div class="seccion">
            <p class="subtitulo">Tecnologías utilizadas</p>
            <p class="texto-secundario">
              <v-icon small color="#880e4f">mdi-vuejs</v-icon> Vue.js ·
              <v-icon small color="#880e4f">mdi-vuetify</v-icon> Vuetify ·
              <v-icon small color="#880e4f">mdi-database</v-icon> MySQL
            </p>
          </div>

          <!-- FOOTER -->
          <p class="footer-text">
            © {{ currentYear }} SATES · Todos los derechos reservados
          </p>

        </div>

      </v-col>
    </v-row>
  </v-container>
</template>
<script>
  export default {
    name: "Acercade",
    data() {
      return {
        currentYear: new Date().getFullYear(),
      };
    },
  };
</script>
<style scoped>
.about-wrapper {
  background-color: transparent;
  margin-top: 40px;
}

/* Título */
.titulo-acerca {
  color: #880e4f;
  font-weight: 600;
  font-size: 1.7rem;
  margin-bottom: 16px;
  margin-top: 8px;
}

/* Texto principal */
.descripcion {
  color: #555;
  font-size: 0.95rem;
  line-height: 1.6;
}

/* Secciones */
.seccion {
  margin-top: 32px;
}

/* Subtítulos */
.subtitulo {
  font-weight: 600;
  color: #880e4f;
  font-size: 0.95rem;
  margin-bottom: 4px;
}

/* Texto secundario */
.texto-secundario {
  color: #666;
  font-size: 0.9rem;
}

/* Ícono */
.icono-acerca {
  color: #880e4f;
  font-size: 28px;
  margin-bottom: 8px;
}

/* Divider institucional */
.divider-institucional {
  margin: 32px auto;
  max-width: 100px;
  border-top: 1px solid #880e4f;
  opacity: 0.6;
}

/* Footer */
.footer-text {
  margin-top: 40px;
  font-size: 0.75rem;
  color: #777;
}

@media print {
  .about-wrapper {
    display: none;
  }
}
</style>
