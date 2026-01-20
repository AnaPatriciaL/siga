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
          <h1 class="titulo-memos">
            Memorandum SATES Digital
          </h1>

          <!-- FOOTER -->
          <p class="footer-text">
            © 2026 SATES · Todos los derechos reservados
          </p>

        </div>

      </v-col>
    </v-row>
  </v-container>
</template>
<style scoped>
.about-wrapper {
  background-color: transparent;
  margin-top: 40px;
}

/* Título */
.titulo-memos {
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
  margin-top: 28px;
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
</style>
