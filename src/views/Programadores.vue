<template>
  <v-container>
                <!-- Botón Crear y Exportar -->
            <v-row class="center">
              <v-spacer></v-spacer>
              <v-col cols="8"  class="text-center">
                <h1>PROGRAMADORES</h1>
              </v-col>
              <v-spacer></v-spacer>
              <!-- <v-col cols="1" class="text-right">
                  <v-btn color="pink darken-4" dark @click="salir()">
                    <v-icon class="mr-3">mdi-exit-to-app</v-icon> Salir
                  </v-btn>
              </v-col> -->
            </v-row>
    <v-btn class="my-5 pink darken-4 white--text" @click="abrirFormulario()">
      <v-icon class="mr-2">mdi-account</v-icon>
      Dar de alta Programador
    </v-btn>

    <v-dialog v-model="dialog" max-width="500px">
      <v-card>
        <v-card-title class="pink darken-4 white--text py-1">
          <span class="headline"
            >{{ operacion === "crear" ? "Crear" : "Editar" }} Programador</span
          >
        </v-card-title>
        <v-card-text class="mt-3 mb-1 pb-1">
          <v-form ref="form">
            <v-text-field
              v-if="operacion !== 'crear'"
              v-model="programador.programador"
              label="Programador"
              :disabled="operacion === 'editar'"
              required
            ></v-text-field>
            <v-text-field
              v-model="programador.nombre_completo"
              label="Nombre Completo"
              required
            ></v-text-field>
            <v-row>
              <v-col cols="4">
                <v-text-field
                  v-model="programador.usuario"
                  label="Nombre Corto"
                  type="usuario"
                  :required="operacion === 'crear'"
                  @input="programador.usuario = programador.usuario.toUpperCase()"
                ></v-text-field>
              </v-col>
              <v-col cols="4">
                <v-switch
                  v-model="programador.estatus"
                  inset
                  class="mt-1"
                  color="success"
                  :label="programador.estatus ? 'Activo' : 'Inactivo'"
                  :disabled="operacion === 'crear'"
                ></v-switch>
              </v-col>
              <v-col cols="4">
                <v-switch
                  v-model="programador.tipo_programador"
                  inset
                  class="mt-1"
                  color="success"
                  :label="programador.tipo_programador ? 'Estatal' : 'Federal'"
                ></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions class="grey">
          <v-spacer></v-spacer>
          <v-btn class="success" text @click="guardarProgramador">
            <v-icon class="mr-2" small>mdi-content-save</v-icon>
            Guardar
          </v-btn>
          <v-btn class="black" dark text @click="dialog = false">
            <v-icon class="mr-2" small>mdi-cancel</v-icon>
            Cerrar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-data-table :headers="headers" :items="programadores" :items-per-page="20" :sort-by="['estatus', 'nombre_completo']" :sort-desc="[true, false]">
      <template v-slot:item.acciones="{ item }">
        <v-icon large color="warning" @click="editarProgramador(item)"
          >mdi-pencil</v-icon
        >
        <!-- <v-icon color="red" @click="eliminarProgramador(item.id)">mdi-delete</v-icon> -->
      </template>
      <template v-slot:item.estatus="{ item }">
        <v-chip :color="item.estatus > 0 ? 'success' : 'error'" dark>
          {{ item.estatus > 0 ? "Activo" : "Inactivo" }}
        </v-chip>
      </template>
      <template v-slot:item.tipo_programador="{ item }">
        <v-chip :color="item.tipo_programador == 1 ? 'blue' : 'orange'" dark>
        {{ item.tipo_programador == 1 ? "Federal" :  "Estatal"}}
        </v-chip>
      </template>
    </v-data-table>
  </v-container>
</template>

<script>
import axios from "axios";
import api from '@/services/apiUrls.js';

export default {
name: "Programadores",
  data() {
    return {
      dialog: false,
      operacion: "crear",
      programador: {
        usuario: "",
        nombre_completo: "",
        cargo: "",
        estatus: "",
        tipo_programador: true,
      },
      programadores: [],
      headers: [
        {
          text: "Programador",
          value: "id",
          class: "pink darken-4 white--text elevation-1",
        },
        {
          text: "Nombre Corto",
          value: "usuario",
          class: "pink darken-4 white--text elevation-1",
        },
        {
          text: "Nombre",
          value: "nombre_completo",
          class: "pink darken-4 white--text elevation-1",
        },
        {
          text: "Estatus",
          value: "estatus",
          class: "pink darken-4 white--text elevation-1",
        },
                {
          text: "Tipo_Programador",
          value: "tipo_programador",
          class: "pink darken-4 white--text elevation-1",
        },
        {
          text: "Acciones",
          value: "acciones",
          class: "pink darken-4 white--text elevation-1",
          sortable: false,
        },
      ],
    };
  },

  mounted() {
    this.obtenerProgramadores();
  },
  methods: {
    obtenerProgramadores() {
      try {
        axios
          .get(api.programadoresListado)
          .then((response) => (this.programadores = response.data));
      } catch (error) {
        console.error("Error al obtener los programadores:", error);
      }
    },
    abrirFormulario() {
      this.dialog = true;
      this.operacion = "crear";
      this.programador = { programador: "", nombre: "", nombre_completo: "", cargo: "", estatus: true, tipo_programador: true };
    },
    editarProgramador(item) {
      // editarProgramador(item) {
      // Cargar el programador seleccionado
      this.programador = {
        ...item,
        programador: item.id,
        usuario: (item.usuario || "").toUpperCase(),
        nombre_completo: item.nombre_completo,
        cargo: item.cargo,
        estatus: item.estatus,
        tipo_programador: item.tipo_programador == 1
      };
      this.dialog = true;
      this.operacion = "editar";
    },
//       salir: function(){
//     window.location.href = "logout.php";
//   },

    guardarProgramador() {
      // Construir el objeto de datos explícitamente para asegurar que los campos son correctos
      let programadorData = {
        programador: this.programador.usuario.toUpperCase(),
        nombre_completo: this.programador.nombre_completo,
        estatus: this.programador.estatus ? 1 : 0, // Convierte true a 1 (Activo) y false a 0 (Inactivo)
        tipo_programador: this.programador.tipo_programador ? 1 : 2, // Convierte true a 1 (Federal) y false a 2 (Estatal)
      };

      if (this.operacion === 'editar') {
        // Solo para editar, agregamos el ID del programador
        programadorData.id = this.programador.id;
      }

      const endpoint = this.operacion === "crear" ? "post" : "put";
      axios[endpoint](
        api.programadoresListado,
        programadorData
      )
        .then(() => {
          this.obtenerProgramadores();
          this.dialog = false;
        })
        .catch((error) => console.error(error));
    },
    eliminarProgramador(id) {
      Swal.fire({
        title: "¿Confirma Desactivar el Programador?",
        icon: "warning",
        confirmButtonColor: "#3085d6",
        confirmButtonText: "Si, desactivarlo",
        cancelButtonColor: "#d33",
        showCancelButton: true,
      }).then((result) => {
        if (result.isConfirmed) {
          axios
            .delete(api.programadoresListado, {
              data: { id },
            })
            .then(() => {
              Swal.fire(
                "¡Desactivado!",
                "se ha desactivado el Programador",
                "success"
              );
              this.obtenerProgramadores();
            });
        } else if (result.isDenied) {
          Swal.fire("¡Error!", "No se pudo desactivar el Programador", "error");
        }
      });
    },
    activoDescripcion(valor) {
      return valor ? "Activo" : "Inactivo";
    },
  },
};
</script>
