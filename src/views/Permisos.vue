<template>
  <v-container>
    <v-row class="center">
      <v-col cols="12"  class="text-center mb-5">
        <h1>Administración de Accesos a Usuarios</h1>
      </v-col>
    </v-row>
    <!-- Tabla de Usuarios -->
    <v-data-table
      :headers="encabezados"
      :items="usuarios"
      item-value="id"
      class="elevation-1"
    >
      <!-- Acciones -->
      <template v-slot:item.actions="{ item }">
        <!-- Icono Editar en el data-table -->
        <v-icon
          large
          class="mr-2"
          color="amber"
          dark
          dense
          alt="Asignar Opciones"
          @click="mostrarDialog(item)"
        >
          mdi-pencil
        </v-icon>
      </template>
      <!-- <template v-slot:items="props">
        <tr>
          <td>{{ props.item.usuario }}</td>
          <td>{{ props.item.nombre }}</td>
          <td>
            <v-btn
              color="primary"
              @click="mostrarDialog(props.item)"
            >
              <v-icon large class="mr-2">mdi-pencil</v-icon>
              Asignar Opciones
            </v-btn>
          </td>
        </tr> 
      </template> -->
    </v-data-table>

    <!-- Dialogo para Asignar Opciones -->
    <v-dialog v-model="dialog" max-width="600px">
      <v-card>
        <v-card-title class="pink darken-4 white--text py-1">
          <span class="headline mr-3">Asignar opciones al usuario: </span>
          <span class="yellow--text">{{usuarioSeleccionadoMostrar}}</span>  
          
        </v-card-title>
        <!-- <v-card-title>Asignar Opciones</v-card-title> -->
        <v-card-text>
          <v-checkbox
            v-for="opcion in opciones"
            :key="opcion.id"
            :label="opcion.nombre_opcion"
            v-model="opcionesSeleccionadas"
            :value="opcion.id"
            hide-details
          ></v-checkbox>
        </v-card-text>
        <v-card-actions class="grey">
          <v-spacer></v-spacer>
          <v-btn class="success" text @click="guardar">
            <v-icon class="mr-2" small>mdi-content-save</v-icon>
            Guardar</v-btn>
          <v-btn class="black white--text" text @click="cerrarDialog">
            <v-icon class="mr-2" small>mdi-cancel</v-icon>
            Cerrar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>
<script>
  import axios from "axios";
  import api from '@/services/apiUrls';
export default {
  data() {
    return {
      usuarios: [],
      opciones: [],
      encabezados: [
        { text: 'USUARIO', value: 'usuario', class: "pink darken-4 white--text elevation-1" },
        { text: 'NOMBRE', value: 'nombre', class: "pink darken-4 white--text elevation-1" },
        { text: 'OPCIONES ASIGNADAS', value: 'opciones', class: "pink darken-4 white--text elevation-1"},
        { text: 'Acciones', value: 'actions', class: "pink darken-4 white--text elevation-1", sortable: false }
      ],
      dialog: false,
      usuarioSeleccionado: null,
      opcionesSeleccionadas: [],
      usuarioSeleccionadoMostrar:''
    };
  },
  mounted() {
    this.obtieneOpciones();
    this.obtieneUsuarios();
    // this.cargarOpcionesUsuario();
  },
  methods: {
    obtieneUsuarios() {
      fetch(api.obtenerUsuarios)
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            this.usuarios = data.usuarios; // ← ARRAY, como antes
          } else {
            console.error(data.message);
          }
        })
        .catch(error => console.error('Error al obtener usuarios:', error));
    },
    obtieneOpciones() {
      fetch(api.obtenerOpciones)
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            this.opciones = data.opciones;
          } else {
            console.error(data.message);
          }
        })
        .catch(error => console.error('Error al obtener opciones:', error));
    },
    async cargarOpcionesUsuario(usuario_id) {
      try {
        const response = await fetch(api.opcionesUsuario(usuario_id));
        const opcionesAsignadas = await response.json();

        // el backend ya devuelve [1,2,3]
        this.opcionesSeleccionadas = opcionesAsignadas;
      } catch (error) {
        console.error('Error al cargar opciones del usuario:', error);
      }
    },
    mostrarDialog(usuario) {
      this.usuarioSeleccionado = usuario;
      // console.log(this.usuarioSeleccionado);
      this.usuarioSeleccionadoMostrar=usuario.nombre;
      this.cargarOpcionesUsuario(usuario.id);
      this.dialog = true;

      // Aquí podrías cargar las opciones asignadas al usuario desde el servidor si es necesario
    },
    guardar() {
      fetch(api.asignarOpciones, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          usuario_id: this.usuarioSeleccionado.id,
          opciones_id: this.opcionesSeleccionadas
        })
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            this.obtieneUsuarios();
            this.dialog = false;
            this.usuarioSeleccionado = null;
            this.opcionesSeleccionadas = [];
          } else {
            console.error(data.message);
          }
        })
        .catch(error => console.error('Error al asignar opciones:', error));
    },
    cerrarDialog(){
      this.usuarioSeleccionado= null;
      this.opcionesSeleccionadas= [];
      this.usuarioSeleccionadoMostrar='';
      this.dialog = false;
    }
  }
};
</script>
  
  