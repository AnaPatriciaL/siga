<template> 
  <v-container>
     <v-container class="my-2">
      <!-- Botón Crear y Exportar -->
      <v-row class="center">
        <v-spacer></v-spacer>
        <v-col cols="12"  class="text-center">
          <h1>MEMORANDUM SATES Digital</h1>
        </v-col>
      </v-row>
      <v-row class="mb-4" align="center">
        <v-col class="d-flex align-center">
          <!-- Boton Nuevo Memo -->
          <v-tooltip top color="pink darken-4">
            <template v-slot:activator="{ on, attrs }">
              <v-btn color="pink darken-4" fab dark @click="formNuevo()" v-bind="attrs" v-on="on"><v-icon large>mdi-plus-thick</v-icon></v-btn>
            </template>
            <span>Generar Nuevo Memo</span>
          </v-tooltip>      
        </v-col>
        <v-spacer></v-spacer>
        <v-col cols="6">
          <v-text-field v-model="busca" append-icon="mdi-magnify" label="Buscar" single-line hide-details></v-text-field>
        </v-col>
      </v-row>
      <!-- Tabla y formulario -->
      <v-data-table :headers="encabezados" :items="memos" item-key="id" class="elevation-1" 
      :search="busca">
        <!-- Acciones -->
        <template v-slot:item.actions="{ item }">
          <div class="d-flex">
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="amber" dark dense style="font-size: 32px" @click="formEditar(item)">mdi-pencil</v-icon>
              </template>
              <span>Editar Memorandum</span>
            </v-tooltip>
            <v-tooltip top v-if="item.puede_reimprimir === 1">
              <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs" v-on="on" large class="ml-2" dark dense style="font-size: 32px" color="success" :disabled="cargando"
                  :class="{ 'opacity-50': cargando }" @click="!cargando && generarDocumentoMemo(item)">mdi-file-word</v-icon>
              </template>
              <span>Imprimir Memorandum</span>
            </v-tooltip>
          </div>
        </template>
      </v-data-table>
    </v-container>
      <FormMemosCrearEditar
        v-model="dialog"
        :operacion="operacion"
        :memo="memo"
        :oficinas="oficinas"
        :departamentos="departamentos"
        :destinatarios="destinatarios"
        :prospectosie="prospectosie"
        :impresora="impresoraPredeterminada"
        @guardar="handleGuardar"
        @cerrar="dialog = false"
      />
  </v-container>
</template>
<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import FormMemosCrearEditar from '@/components/formMemosCrearEditar.vue';
  import api from '@/services/apiUrls.js';

export default {
  name: "Consultas",
  data() {
    return {
      impresoraPredeterminada: '',
      busca: "",
      encabezados: [
        {
          text: "FOLIO",
          value: "folio_memo",
          class: "pink darken-4 white--text elevation-1 center-header",
          width:"70"
        },
        {
          text: "FECHA",
          value: "fecha_memo",
          class: "pink darken-4 white--text elevation-1",
          width:"100"
        },
        {
          text: "DESTINATARIO",
          value: "destinatario",
          class: "pink darken-4 white--text elevation-1",
          width: "300"
        },
        {
          text: "OFICINA",
          value: "oficina_descripcion",
          class: "pink darken-4 white--text elevation-1",
          width:"150"
        },
        {
          text: "DEPARTAMENTO",
          value: "departamento_descripcion",
          class: "pink darken-4 white--text elevation-1",
          width:"70"
        },
        {
          text: "ASUNTO",
          value: "asunto",
          class: "pink darken-4 white--text elevation-1",
          width: "150"
        },
        {
          text: "USUARIO",
          value: "usuario_nombre",
          class: "pink darken-4 white--text elevation-1",
          width:"70"
        },
        {
          text: "ACCIONES",
          value: "actions",
          class: "pink darken-4 white--text elevation-1",
          width:"140"
        },
      ],
      memos: [],
      dialog: false,
      cargando: false,
      operacion: "",
      memo: { id: null, fecha_memo: null, destinatario: null, oficina_id: null, departamento_id: null, asunto: null, usuario: null },
      oficinas: [],
      departamentos: [],
      destinatarios: [],
      prospectosie: [],
      permiso:false,
    };
  },
  components:{
    FormMemosCrearEditar,
  },
  created() {
    this.obtenerPermisos();
    this.mostrar();
    this.obtieneoficinas();
    this.obtienedepartamentos();
    this.obtienedestinatarios();
    this.cargarProspectosMemo();
    this.obtenerImpresora();
  },
  methods: {
    async obtenerImpresora() {
      try {
        const { data } = await axios.post(api.generarOrdenes, {opcion: 7});
        this.impresoraPredeterminada = data.impresora || 'No detectada';
      } catch (e) {
        console.error('No se pudo obtener la impresora', e);
        this.impresoraPredeterminada = 'No disponible';
      }
    },
    async obtenerPermisos() {
      try {
        const response = await axios.get(api.sessionCheck);
        this.sessionData = response.data;
        const nivel = Number(this.sessionData?.nivel); 
        this.permiso = [0, 2].includes(nivel);
      } catch (error) {
        console.error('Error al obtener los datos de la sesión:', error);
      }
    },
    mostrar() {
      axios.post(api.memos, { opcion: 9 })
        .then((response) => {
          if (response.data.success && Array.isArray(response.data.data)) {
            this.memos = response.data.data.map(m => ({
              ...m,
              puede_reimprimir: m.puede_reimprimir == 1 ? 1 : 0,

              destinatario: typeof m.destinatario === 'object'
                ? m.destinatario?.nombre_completo || ''
                : m.destinatario,

              oficina_descripcion: typeof m.oficina_descripcion === 'object'
                ? m.oficina_descripcion?.nombre || ''
                : m.oficina_descripcion,

              departamento_descripcion: typeof m.departamento_descripcion === 'object'
                ? m.departamento_descripcion?.nombre || ''
                : m.departamento_descripcion,

              usuario_nombre: typeof m.usuario_nombre === 'object'
                ? m.usuario_nombre?.nombre || ''
                : m.usuario_nombre,
            }));
          }
        });
    },
    async crear(memoData) {
      try{
        const response = await axios.post(api.memos, {
          opcion: 2,
          fecha: memoData.fecha_memo,
          destinatario: memoData.destinatario?.toUpperCase() || null,
          oficina_id: memoData.oficina_id,
          departamento_id: memoData.departamento_id,
          asunto: memoData.asunto?.toUpperCase() || null,
          siga_usuario_id: localStorage.getItem('siga_id'),
          copias: memoData.copias,
          prospectos: memoData.prospectos
        });
        if (response.data.success === false) {
          await Swal.fire('Error al crear', response.data.mensaje, 'error');
          return;
        } 
          await Swal.fire({
            title: "Éxito",
            text: "La información fue guardada satisfactoriamente",
            icon: 'success',
            showCancelButton: false,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
          });
          await this.cargarProspectosMemo();
          this.mostrar(); 
          this.limpiar();
          this.dialog = false; 
      }
      catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al guardar los datos: ' + error.message,
            showCancelButton: false,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
          });
      }
    },
    limpiar: function(){
      this.memo = {
        id: null,
        fecha_memo: null,
        destinatario: null,
        oficina_id: null,
        departamento_id: null,
        asunto: null,
        usuario: null
      };
    },   
    async editar(memoData) {
      axios
        .post(api.memos, { 
          opcion: 3,
          id: memoData.id,
          destinatario: memoData.destinatario?.toUpperCase(),
          oficina_id: memoData.oficina_id,
          departamento_id: memoData.departamento_id,
          asunto: memoData.asunto?.toUpperCase()
        })
        .then(response =>{
          if (response.data.success === false) {
            Swal.fire('Error al crear', response.data.mensaje, 'error');
          } 
          else {
            Swal.fire({
              title: "Exito",
              text: "La información fue actualizada satisfactoriamente",
              icon: 'success',
              showCancelButton: false,
              showConfirmButton:false,
              timer:2000,
              timerProgressBar: true,
              allowOutsideClick: false, 
              allowEscapeKey: false,
              allowEnterKey: false
            })
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al actualizar los datos: ' + error.message,
            showCancelButton: false,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
          });
        })
        .finally(() => {
          this.mostrar();
          this.limpiar();
          this.dialog = false;
        })
      
    },

    obtienedepartamentos() {
      axios.post(api.memos,{ opcion:5 }).then(response => {
        this.departamentos = response.data.map(d => ({
          ...d,
          id: Number(d.id),
          oficina_id: Number(d.oficina_id)
        }));
      });
    },

    obtienedestinatarios() {
      axios.post(api.memos,{ opcion:6 }).then(response => {
        this.destinatarios = response.data.map(d => ({
          ...d,
          oficina_id: Number(d.oficina_id),
          departamento_id: Number(d.departamento_id)
        }));
      });
    },

    obtieneoficinas() {
      axios.post(api.memos, { opcion: 7 }).then(response => {
        this.oficinas = response.data.map(o => ({
          ...o,
          id: Number(o.id)
        }));
      });
    },

    cargarProspectosMemo: function() {
      axios.post(api.memos, { opcion: 8 }).then(response => {
        this.prospectosie = response.data;
      });
    },

    //Botones y formularios
    async handleGuardar(memoData) {
      try {
        if (this.operacion === "crear") {
          if (!this.impresoraPredeterminada || this.impresoraPredeterminada === 'No disponible') {
            await Swal.fire('Error', 'No hay impresora configurada en el servidor', 'error');
            return;
          }
          const { value: numCopias } = await Swal.fire({
            title: 'Número de impresiones',
            html: `
              <p style="margin-bottom:6px">
                El documento se enviará a la impresora predeterminada del servidor:
              </p>
              <b>${this.impresoraPredeterminada}</b>
            `,
            input: 'number',
            inputLabel: '¿Cuántos juegos desea imprimir?',
            inputValue: 1,
            showCancelButton: true,
            inputValidator: (value) => {
              if (!value || value < 1) {
                return '¡Necesitas ingresar un número válido de impresiones!';
              }
            }
          });

          if (!numCopias) return;
          await this.crear({
            ...memoData,
            copias: numCopias
          });

        } else if (this.operacion === "editar") {
          await this.editar(memoData);
        }

      } catch (error) {
        console.error("Error en handleGuardar:", error);
      }
    },

    formNuevo() {
      this.limpiar();
      this.operacion = "crear";
      this.dialog = true;
    },

    formEditar: function (objeto) {
      this.operacion = "editar";
      this.memo = {
        id: objeto.id,
        fecha_memo: objeto.fecha_memo,
        destinatario: objeto.destinatario,
        oficina_id: objeto.oficina_id,
        departamento_id: objeto.departamento_id,
        asunto: objeto.asunto,
        usuario: objeto.usuario_nombre
      };
      this.$nextTick(() => {
        this.dialog = true;
      });
    },
    async generarDocumentoMemo(item) {
      if (!this.impresoraPredeterminada || this.impresoraPredeterminada === 'No disponible') {
        Swal.fire('Error', 'No hay impresora configurada en el servidor', 'error');
        return;
      }
      const { value: numCopias } = await Swal.fire({
        title: 'Número de impresiones',
        html: `
          <p style="margin-bottom:6px">
            El documento se enviará a la impresora predeterminada del servidor:
          </p>
          <b>${this.impresoraPredeterminada}</b>
        `,
        input: 'number',
        inputLabel: '¿Cuántos juegos desea imprimir?',
        inputValue: 1,
        showCancelButton: true,
        inputValidator: (value) => {
          if (!value || value < 1) {
            return '¡Necesitas ingresar un número válido de impresiones!'
          }
        }
      });

      if (!numCopias) return;

      this.cargando = true;
      const resp = await this.generarDocumento(item, numCopias);
      this.cargando = false;
      if (resp && resp.success) {        
        if (resp.impreso === true) {
          Swal.fire({
            title: "Impresión completada",
            text: "El documento se generó y se envió a la impresora.",
            icon: "success",
            timer: 2500
          });
        } else {
          Swal.fire({
            title: "Generado pero no impreso",
            text: "El documento se generó correctamente pero no se imprimió.",
            icon: "warning"
          });
        }
      } else {
        Swal.fire("Error", "No se pudo generar el documento.", "error");
      }
    },
    async generarDocumento(item, numCopias) {      
      try {
        let data = {};
          data = {
            opcion: 4,
            memo_id: item.id,
            copias: numCopias,
            usuarioId: localStorage.getItem('siga_id'),
          };
          console.log("Enviando datos para REIMPRESIÓN:", data);
        
        const response = await axios.post(api.memos, data, { responseType: 'blob' });
        console.log("Respuesta del servidor (blob):", response);

        if (response.data.size > 0) {
          this.mostrar();
          return { success: true, impreso: true };
        } else {
          return { success: false, impreso: false };
          }
        } catch (error) {
            this.cargando = false;
            console.error("Error en la petición a memos.php:", error);
            Swal.fire('Error', 'Hubo un problema de comunicación con el servidor.', 'error');
            return { success: false, impreso: false };
          }
    }
    
  }
};
</script>
<style>

.v-input--is-focused .v-label.theme--light {
  color: #880e4f !important;
}

.v-input--is-focused .v-input__slot {
  background-color: #e6e4e5 !important;
  color: #880e4f !important;
}

tbody tr:nth-of-type(odd) {
  background-color: rgba(0, 0, 0, 0.05);
}
.swal2-title {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", 
  "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif !important;
}
.swal2-text{
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", 
  "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif !important;
}
.swal2-popup {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", 
  "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif !important;
} 
.mayusculas input{
  text-transform: uppercase
}

.center-header {
  text-align: center;
}
</style>
