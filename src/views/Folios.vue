<template>
  <v-container>
    <v-row class="center">
      <v-spacer></v-spacer>
      <v-col cols="12" class="text-center">
        <h1>ALTA DE FOLIOS</h1>
      </v-col>
    </v-row>

    <v-row justify="center" class="mt-4">
      <v-col cols="12" md="4">
        <v-card>
          <v-card-title class="pink darken-4 white--text py-2">
            Alta de Folios
          </v-card-title>
          <v-card-text>
            <v-form>
              <v-container>
                <v-row>
                  <!-- Siguiente Folio -->
                  <v-col cols="12" md="12">
                    <v-text-field
                      label="Siguiente Folio"
                      outlined
                      dense
                      :value="siguienteFolioTexto"
                      disabled
                    ></v-text-field>
                  </v-col>
                </v-row>
                <v-row>
                  <!-- Folios disponibles -->
                  <v-col cols="12" md="12">
                    <v-text-field
                      label="Folios disponibles"
                      outlined
                      dense
                      type="number"
                      :value="foliosDisponiblesCount"
                      disabled
                    ></v-text-field>
                  </v-col>
                </v-row>
                <v-row>
                  <!-- Agregar mas folios -->
                  <v-col cols="12" md="8">
                    <div class="d-flex align-center">
                      <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                          <v-btn icon color="pink darken-4" v-bind="attrs" v-on="on" @click="dialogFolios = true">
                            <v-icon large>mdi-plus-circle</v-icon>
                          </v-btn>
                        </template>
                        <span>Agregar Folios</span>
                      </v-tooltip>
                      <span class="ml-2">Agregar folios</span>
                    </div>
                  </v-col>
                </v-row>
              </v-container>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <!-- Componente de Diálogo para agregar folios -->
    <v-dialog v-model="dialogFolios" max-width="600px" persistent>
      <v-card>
        <v-card-title class="pink darken-4 white--text py-2">
          Agregar Folios
        </v-card-title>
        <v-card-text>
          <v-container>
            <v-row class="mt-4">
              <!-- Folio Inicial -->
              <v-col cols="12" md="6">
                <v-text-field
                  label="Folio Inicial"
                  outlined
                  dense
                  v-model.number="folioInicial"
                  type="number"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row class="mt-4">
              <!-- Cantidad de Folios -->
              <v-col cols="12" md="6">
                <v-text-field
                  label="Cantidad de Folios"
                  outlined
                  dense
                  v-model.number="cantidadFolios"
                  type="number"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
        <v-card-actions class="grey lighten-2 py-2">
          <v-spacer></v-spacer>
          <v-btn color="success" dark @click="guardarFolios()">Guardar</v-btn>
          <v-btn color="blue-grey" dark @click="dialogFolios = false">Cerrar</v-btn>
          <v-spacer></v-spacer>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import api from '@/services/apiUrls.js';

  var urlfolios_oficios = api.foliosOficios;

export default {
  name: "Folios",
  data() {
    return {
      folioInicial: null,
      cantidadFolios: null,
      dialogFolios: false,
      ultimoFolioUtilizado: {},
      siguientefolio: {},
      foliosDisponiblesCount: 0,
    };
  },
  computed: {
    siguienteFolioTexto() {
      if (this.siguientefolio && this.siguientefolio.num_folio && this.siguientefolio.anio) {
        return `SATES-DA-DP-${this.siguientefolio.num_folio}/${this.siguientefolio.anio}`;
      }
      return 'N/A'; // Mensaje por defecto
    },
  },
  created() {
    this.obtienefoliosoficios();
  },
  methods: {
    guardarFolios() {
      if (!this.folioInicial || !this.cantidadFolios) {
        Swal.fire('Error', 'Debe especificar el folio inicial y la cantidad.', 'warning');
        return;
      }

      axios.post(urlfolios_oficios, {
        opcion: 2,
        folio_inicial: this.folioInicial,
        cantidad: this.cantidadFolios,
      })
      .then(response => {
        Swal.fire('Éxito', 'Folios guardados correctamente', 'success');
        this.dialogFolios = false;
        this.folioInicial = null;
        this.cantidadFolios = null;
        this.obtienefoliosoficios(); // Actualizar la información
      })
      .catch(error => {
        console.error('Error al guardar folios:', error);
        Swal.fire('Error', 'No se pudieron guardar los folios.', 'error');
      });
    },
    obtienefoliosoficios: function () {
      axios
        .post(urlfolios_oficios,{ opcion: 1})
        .then((response) => {
          if (Array.isArray(response.data)) {
            this.folios_oficios = response.data;
            // Filtrar los registros con estatus=0 y obtener el primero
            const foliosConEstatusDisponible = this.folios_oficios.filter(item => Number(item.estatus) === 0);
            if (foliosConEstatusDisponible.length > 0) {
              this.siguientefolio = foliosConEstatusDisponible[0];
            }
            this.foliosDisponiblesCount = foliosConEstatusDisponible.length;

          } else if (response.data.error) {
            console.error('Error desde el servidor:', response.data.error);
            Swal.fire('Error', response.data.error, 'error');
          } else {
            console.warn('Respuesta inesperada:', response.data);
          }
        })
        .catch((error) => {
          console.error('Error en la solicitud:', error);
          Swal.fire('Error de conexión', 'No se pudo obtener la información', 'error');
      });
    },
  },
};
</script>

<style scoped>
.center {
  text-align: center;
}
.mt-4 {
  margin-top: 16px;
}
</style>
