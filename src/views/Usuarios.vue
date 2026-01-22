<template>
  <v-container>
    <v-btn class="my-5 pink darken-4 white--text" @click="abrirFormulario()">
      <v-icon class="mr-2">mdi-account</v-icon>
      Crear Usuario
    </v-btn>

    <v-dialog v-model="dialog" max-width="500px">
      <v-card>
        <v-card-title class="pink darken-4 white--text py-1">
          <span class="headline"
            >{{ operacion === "crear" ? "Crear" : "Editar" }} Usuario</span
          >
        </v-card-title>
        <v-card-text class="mt-3 mb-1 pb-1">
          <v-form ref="form">
            <v-row>
              <v-col cols="4">
                <v-text-field
                  v-model="usuario.usuario"
                  label="Usuario"
                  :disabled="operacion === 'editar'"
                  @input="usuario.usuario = usuario.usuario.toLowerCase()"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="8">
                <v-text-field
                  v-model="usuario.nombre"
                  label="Nombre Completo"
                  required
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6">
                <v-select
                  v-model="usuario.nivel"
                  :items="niveles"
                  label="Nivel de Usuario"
                  required
                ></v-select>
              </v-col>
              <v-col cols="6">
                <v-select
                  v-if="usuario.nivel === 1"
                  v-model="usuario.programador_id"
                  :items="programadores"
                  label="Programador"
                  item-text="usuario"
                  item-value="id"
                  @change="actualizarNombreCompleto"
                ></v-select>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6">
                <v-text-field
                  v-model="usuario.iniciales"
                  label="Iniciales"
                ></v-text-field>
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model="usuario.contrasena"
                  label="Contraseña"
                  type="password"
                  :required="operacion === 'crear'"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-switch
              v-model="usuario.activo"
              inset
              class="mt-1"
              color="success"
              :label="usuario.activo ? 'Activo' : 'Inactivo'"
              :disabled="operacion === 'crear'"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions class="grey">
          <v-spacer></v-spacer>
          <v-btn class="success" text @click="guardarUsuario">
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

    <v-data-table :headers="headers" :items="usuarios" :items-per-page="20" sort-by="nombre">
      <template v-slot:item.acciones="{ item }">
        <v-icon large color="warning" @click="editarUsuario(item)"
          >mdi-pencil</v-icon
        >
        <!-- <v-icon color="red" @click="eliminarUsuario(item.id)">mdi-delete</v-icon> -->
      </template>
      <template v-slot:item.activo="{ item }">
        <!-- {{ activoDescripcion(item.activo) }} -->
        <v-icon
          v-if="item.activo === true"
          large
          class="mr-2"
          color="success"
          dark
          dense
        >
          mdi-check-circle
        </v-icon>
        <v-icon v-else large class="mr-2" color="red" dark dense>
          mdi-close-circle
        </v-icon>
      </template>
    </v-data-table>
  </v-container>
</template>

<script>
import axios from "axios";
import api from "@/services/apiUrls.js";

export default {
  data() {
    return {
      dialog: false,
      operacion: "crear",
      usuario: {
        usuario: "",
        nombre: "",
        iniciales: "",
        contrasena: "",
        activo: true,
        nivel: null,
        programador_id: null,
      },
      niveles: [
        { text: "Supervisor", value: 0 },
        { text: "Programador", value: 1 },
      ],
      programadores: [],
      usuarios: [],
      headers: [
        {
          text: "Usuario",
          value: "usuario",
          class: "pink darken-4 white--text elevation-1",
        },
        {
          text: "Nombre",
          value: "nombre",
          class: "pink darken-4 white--text elevation-1",
        },
        {
          text: "Activo",
          value: "activo",
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
    this.obtenerUsuarios();
    this.obtenerProgramadores();
  },
  methods: {
    obtenerUsuarios() {
      axios
        .get(api.usuarios)
        .then((response) => {
          this.usuarios = response.data.map((usuario) => ({
            ...usuario,
            activo: usuario.activo > 0,
          }));
        })
        .catch((error) => {
          console.error("Error al obtener los usuarios:", error);
        });
    },
    obtenerProgramadores() {
      axios.post(api.programadores, { opcion: 1 }).then((response) => {
        this.programadores = response.data;
      });
    },
    actualizarNombreCompleto(id) {
      const programador = this.programadores.find((p) => p.id === id);
      if (programador) {
        this.usuario.nombre = programador.nombre_completo;
      }
    },
    abrirFormulario() {
      this.dialog = true;
      this.operacion = "crear";
      this.usuario = { usuario: "", nombre: "", iniciales: "", contrasena: "", activo: true, nivel: null, programador_id: null };
    },
    editarUsuario(item) {
      // editarUsuario(item) {
      // Cargar el usuario seleccionado
      this.usuario = {
        ...item,
        usuario: (item.usuario || "").toLowerCase(),
        contrasena: "",
        nivel: Number(item.nivel), // Asegurar que sea número para que coincida con el select
        programador_id: item.programador_id ? Number(item.programador_id) : null,
        activo: item.activo > 0, // Asegura que activo sea booleano
        iniciales: item.iniciales || "",
      };
      this.dialog = true;
      this.operacion = "editar";
    },
    // guardarUsuario() {
    //   const endpoint = this.operacion === 'crear' ? 'post' : 'put';
    //   axios[endpoint]('http://10.10.120.180/efos/backend/usuarios.php', this.usuario)
    //     .then(() => {
    //       this.obtenerUsuarios();
    //       this.dialog = false;
    //     })
    //     .catch(error => console.error(error));
    // },
    guardarUsuario() {
      if (this.operacion === "crear" && !this.usuario.contrasena) {
        this.$Swal.fire("Atención", "La contraseña es obligatoria al crear un usuario.", "warning");
        return;
      }

      const usuarioData = {
        ...this.usuario,
        usuario: this.usuario.usuario.toLowerCase(),
        activo: this.usuario.activo ? 1 : 0,
      };

      if (usuarioData.nivel === 0) {
        usuarioData.programador_id = 0;
      }

      const endpoint = this.operacion === "crear" ? "post" : "put";

      axios[endpoint](api.usuarios, usuarioData)
        .then(() => {
          this.obtenerUsuarios();
          this.dialog = false;
        })
        .catch((error) => {
          console.error("Error al guardar usuario:", error);
        });
    },
    eliminarUsuario(id) {
      Swal.fire({
        title: "¿Confirma Desactivar el Usuario?",
        icon: "warning",
        confirmButtonColor: "#3085d6",
        confirmButtonText: "Sí, desactivarlo",
        cancelButtonColor: "#d33",
        showCancelButton: true,
      }).then((result) => {
        if (result.isConfirmed) {
          axios
            .delete(api.usuarios, {
              data: { id },
            })
            .then(() => {
              Swal.fire(
                "¡Desactivado!",
                "Se ha desactivado el Usuario",
                "success"
              );
              this.obtenerUsuarios();
            })
            .catch((error) => {
              console.error("Error al eliminar usuario:", error);
            });
        }
      });
    },
    activoDescripcion(valor) {
      return valor ? "Activo" : "Inactivo";
    },
  },
  watch: {
    operacion(newValue) {
      if (newValue === "crear") {
        this.usuario.activo = 1; // Establecer a 1 cuando operacion es 'crear'
      }
    },
  },
};
</script>
