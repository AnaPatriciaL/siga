<template>
  <v-container>
    <!-- Título -->
    <v-row class="center">
      <v-spacer></v-spacer>
      <v-col cols="8" class="text-center">
        <h1>OFICINAS</h1>
      </v-col>
      <v-spacer></v-spacer>
      <v-col cols="1" class="text-right">
        <v-btn color="pink darken-4" dark @click="salir()">
          <v-icon class="mr-3">mdi-exit-to-app</v-icon> Salir
        </v-btn>
      </v-col>
    </v-row>

    <!-- Formulario -->
    <v-row justify="center" class="mt-5">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-text>
            <v-form ref="form">
              <v-row>
                <!-- Combo para seleccionar oficina -->
                <v-col cols="12" md="6">
                  <v-select
                    v-model="selectedOfficeId"
                    :items="oficinas"
                    item-text="nombre"
                    item-value="id"
                    label="Seleccione una Oficina"
                    @change="onOfficeChange"
                    outlined
                    dense
                  ></v-select>
                </v-col>
                <!-- Textbox para la oficina (deshabilitado) -->
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="oficinaSeleccionada.nombre"
                    label="Oficina"
                    disabled
                    outlined
                    dense
                  ></v-text-field>
                </v-col>
              </v-row>

              <!-- Textarea para el grupo -->
              <v-textarea
                v-model="oficinaSeleccionada.grupo"
                label="Grupo"
                outlined
                dense
                rows="13"
                :disabled="!selectedOfficeId"
              ></v-textarea>

              <!-- Textarea para el domicilio -->
              <v-textarea
                v-model="oficinaSeleccionada.domicilio"
                label="Domicilio"
                outlined
                dense
                rows="4"
                :disabled="!selectedOfficeId"
              ></v-textarea>

              <v-row>
                <!-- Textbox para la Fracción -->
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="oficinaSeleccionada.fraccion"
                    label="Fracción"
                    outlined
                    dense
                    :disabled="!selectedOfficeId"
                  ></v-text-field>
                </v-col>

                <!-- Textbox para el Teléfono -->
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="oficinaSeleccionada.telefono"
                    label="Teléfono"
                    outlined
                    dense
                    :disabled="!selectedOfficeId"
                  ></v-text-field>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn
              color="success"
              :disabled="!isDataChanged"
              @click="guardarCambios"
            >
              <v-icon class="mr-2">mdi-content-save</v-icon>
              Guardar Cambios
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import axios from "axios";
import Swal from 'sweetalert2';
import api from '@/services/apiUrls.js';

export default {
  name: "Oficinas",
  data() {
    return {
      oficinas: [],
      selectedOfficeId: null,
      oficinaSeleccionada: {
        id: null,
        nombre: "",
        grupo: "",
        domicilio: "",
        fraccion: "",
        telefono: "",
      },
      originalData: {}, // Para comparar si hay cambios
    };
  },
  computed: {
    isDataChanged() {
      if (!this.selectedOfficeId) return false;
      return (
        this.oficinaSeleccionada.grupo !== this.originalData.grupo ||
        this.oficinaSeleccionada.domicilio !== this.originalData.domicilio ||
        this.oficinaSeleccionada.fraccion !== this.originalData.fraccion ||
        this.oficinaSeleccionada.telefono !== this.originalData.telefono
      );
    },
  },
  mounted() {
    this.obtenerOficinas();
  },
  methods: {
    obtenerOficinas() {
      try {
        axios
          .get(api.oficinas)
          .then((response) => {
            this.oficinas = response.data;
          });
      } catch (error) {
        console.error("Error al obtener las oficinas:", error);
      }
    },
    onOfficeChange(officeId) {
      const office = this.oficinas.find((o) => o.id === officeId);
      if (office) {
        this.oficinaSeleccionada = { ...office };
        this.originalData = { ...office }; // Guardar estado original
      }
    },
    guardarCambios() {
      if (!this.isDataChanged) return;

      const dataToSave = {
        id: this.oficinaSeleccionada.id,
        grupo: this.oficinaSeleccionada.grupo,
        domicilio: this.oficinaSeleccionada.domicilio,
        fraccion: this.oficinaSeleccionada.fraccion,
        telefono: this.oficinaSeleccionada.telefono,
      };

      axios
        .put(api.oficinas, dataToSave)
        .then((response) => {
          Swal.fire("¡Actualizado!", response.data.mensaje, "success");
          this.obtenerOficinas(); // Recargamos la lista por si hubo cambios
          this.resetFormulario(); // Limpiamos el formulario
        })
        .catch((error) => {
          Swal.fire("Error", "No se pudieron actualizar los datos de la oficina.", "error");
          console.error("Error al guardar:", error);
        });
    },
    salir: function () {
      window.location.href = "logout.php";
    },
    resetFormulario() {
      this.selectedOfficeId = null;
      this.oficinaSeleccionada = {
        id: null,
        nombre: "",
        grupo: "",
        domicilio: "",
        fraccion: "",
        telefono: "",
      };
      this.originalData = {};
    }
  },
};
</script>
