<template>
  <v-container>
    <v-container class="my-2">
      <!-- Botón Crear y Exportar -->
      <v-row class="center">
        <v-spacer></v-spacer>
        <v-col cols="8"  class="text-center">
          <h1>LISTA DE EMITIDAS</h1>
        </v-col>
        <v-spacer></v-spacer>
        <v-col cols="1" class="text-right">
            <v-btn color="pink darken-4" dark @click="salir()"><v-icon class="mr-3">mdi-exit-to-app</v-icon> Salir</v-btn>
        </v-col>
      </v-row>
      <v-row class="mb-4">
        <!-- Boton exportar Excel -->
        <vue-excel-xlsx v-if="permiso" :data="prospectosie" :columns="columnas" :file-name="'Emitidas'" :file-type="'xlsx'" :sheet-name="'ProspectosIE'">
          <v-tooltip top color="green darken-3">
            <template v-slot:activator="{ on, attrs }">
              <v-btn fab class="green ml-3 mt-2" dark v-bind="attrs" v-on="on"><v-icon large>mdi-microsoft-excel</v-icon></v-btn>
            </template>
            <span>Exportar a Excel</span>
          </v-tooltip>
        </vue-excel-xlsx>
        <!-- Boton recargar  -->
        <v-tooltip right color="light-blue darken-4">
          <template v-slot:activator="{ on, attrs }">
            <v-btn class="mt-2 ml-3" color="light-blue darken-4" fab dark @click="mostrar()" v-bind="attrs" v-on="on"><v-icon large>mdi-refresh</v-icon></v-btn>
          </template>
          <span>Recargar información</span>
        </v-tooltip>              
        <v-spacer></v-spacer>
        <v-col COL="6">
          <v-text-field v-model="busca" append-icon="mdi-magnify" label="Buscar" single-line hide-details></v-text-field>
        </v-col>
      </v-row>
      <!-- Tabla y formulario -->
      <v-data-table :headers="encabezados" :items="prospectosie" item-key="id" class="elevation-1" :search="busca">
        <template v-slot:item.tipo="{ item }">
          <v-icon v-if="item.antecedente_id==6" large class="mr-2" color="blue-grey darken-2" dark dense>mdi-cog-sync</v-icon>
          <v-icon v-if="item.antecedente_id==7" large class="mr-2" color="pink accent-3" dark dense>mdi-map-marker-remove</v-icon>
          <v-icon v-else large class="mr-2" color="teal accent-4" dark dense>mdi-progress-check</v-icon>
        </template>
        <!-- Acciones -->
        <template v-slot:item.actions="{ item }">
          <v-tooltip top>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="amber" dark dense style="font-size: 32px" @click="formEditar(item)">mdi-pencil</v-icon>
            </template>
            <span>Editar Prospecto</span>
          </v-tooltip>
          <v-tooltip top>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="black" dark dense style="font-size: 32px" @click="vistaPrevia(item)">mdi-file-eye</v-icon>
            </template>
            <span>Vista previa</span>
          </v-tooltip>                
        </template>
      </v-data-table>
    </v-container>
    <!-- Componente de Diálogo para CREAR y EDITAR (extraído) -->
    <form-crear-editar
      v-model="dialog"
      :operacion="operacion"                      
      :prospectoie-data="prospectoie"
      :municipios-listado="municipios_listado"
      :antecedentes-listado="antecedentes_listado"
      :impuestos-listado="impuestos_listado"
      :programadores-listado="programadores_listado"
      :fuentes-listado="fuentes_listado"
      :oficinas-listado="oficinas_listado"
      :cargando-prop="cargando"
      :mostrar-boton-supervisor="false"
      @cerrar="dialog = false"                      
      @guardar="handleGuardar"
      @update:prospectoieData="updateProspectoie">
    </form-crear-editar>        
  </v-container>
</template>

<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import VueExcelXlsx from "vue-excel-xlsx";
  import FormCrearEditar from '@/components/formCrearEditar.vue';

  // var crud = "./backend/crud_prospectosie.php";
  var crud = "http://10.10.120.228/siga/backend/crud_prospectosie.php";
  var urloficinas = "http://10.10.120.228/siga/backend/oficinas_listado.php";
  var urlfuentes = "http://10.10.120.228/siga/backend/fuentes_listado.php";
  var urlprogramadores = "http://10.10.120.228/siga/backend/programadores_listado.php";
  var urlimpuestos = "http://10.10.120.228/siga/backend/impuestos_listado.php";
  var urlantecedentes = "http://10.10.120.228/siga/backend/antecedentes_listado.php";
  var urlpadron = "http://10.10.120.228/siga/backend/padron_contribuyentes.php";
  var urlmunicipios ="http://10.10.120.228/siga/backend/municipios_listado.php";
  var urlgenerar_ordenes ="http://10.10.120.228/siga/backend/generar_ordenes.php";

export default {
  name: "Emitidas",
  data() {
    return {
      busca: "",
      progresoVisible: false,
      progresoMensaje: "",
      encabezados: [
        {
          text: "RFC",
          value: "rfc",
          class: "pink darken-4 white--text elevation-1 center-header",
          width:"70"
        },
        {
          text: "NOMBRE",
          value: "nombre",
          class: "pink darken-4 white--text elevation-1",
          width:"250"
        },
        {
          text: "DOMICILIO COMPLETO",
          value: "domicilio_completo",
          class: "pink darken-4 white--text elevation-1",
          width: "500"
        },
        {
          text: "PERIODOS",
          value: "periodos",
          class: "pink darken-4 white--text elevation-1",
          width:"150"
        },
        {
          text: "IMPUESTO",
          value: "impuesto",
          class: "pink darken-4 white--text elevation-1",
          width:"70"
        },
        {
          text: "TIPO",
          value: "tipo",
          class: "pink darken-4 white--text elevation-1",
          width: "50"
        },
        {
          text: "PROGRAMADOR",
          value: "programador_descripcion",
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
      columnas: [
        {label:"RFC", field:"rfc"},
        {label:"NOMBRE", field:"nombre"},
        {label:"CALLE_y_NO.", field:"domicilio"},
        {label:"COLONIA", field:"colonia"},
        {label:"C.P. Y MUNICIPIO", field:"cp_municipio"},
        {label:"PERIODOS", field:"periodos"},
        {label:"ANTECEDENTE", field:"antecedente_descripcion"},
        {label:"PRESUNTIVA", field:"determinado"},
        {label:"LOCALIDAD", field:"localidad"},
        {label:"OFICINA", field:"oficina_descripcion"},
        {label:"FUENTE", field:"fuente_descripcion"},
        {label:"CP", field:"cp"},
        {label:"DOMICILIO_COMPLETO", field:"domicilio_completo"},
        {label:"IMPUESTO", field:"impuesto"},
        {label:"FECHA CAPTURA", field:"fecha_captura"},
        {label:"PROGRAMADOR", field:"programador_descripcion"},
        {label:"REPRESETANTE LEGAL", field:"representante_legal"},
        {label:"OBSERVACIONES", field:"observaciones"},
      ],
      prospectosie: [],
      prospectosie_no_localizados: [],
      dialog: false,
      cargando: false,
      operacion: "",
      prospectoie: { id: null, fecha_captura: null, rfc: null, nombre: null, calle: null, num_exterior: null, num_interior: null,
        colonia: null, cp: null, localidad: null, municipio_id:null, municipio: null, oficina_descripcion: null, // Agregado para mostrar la descripción de la oficina
        oficina_id: null, fuente_id:null, giro: null, periodos: null, impuesto_id: null, antecedente_id:null, determinado: 0,
        programador_id: null, retenedor:null, origen_id:null, representante_legal: null, estatus: 6,
      },
      impuestos_listado: [],
      antecedentes_listado:[],
      programadores_listado: [],
      oficinas_listado: [],
      municipios_listado:[],
      fuentes_listado:[],
      permiso:false,
      dialogVistaPrevia: false,
      cargandoVistaPrevia: false, 
      pdfSrc: '',
      vistaPreviaRFC: '',
    };
  },
  components:{
    FormCrearEditar,
  },
  created() {
    this.obtenerPermisos();
    this.mostrar(),
    this.obtieneoficinas(),
    this.obtienefuentes(),
    this.obtieneimpuestos(),
    this.obtieneusuarios(),
    this.obtieneantecedentes()
    this.obtienemunicipios()
  },
 methods: {
  actualizarProgreso(texto) {
      this.progresoMensaje = texto;
      this.progresoVisible = true;
    },
  async generarDocumento(item, event, tipo, numCopias) {
    this.vistaPrevia(item);
    return { success: true, impreso: false };
  },
  async vistaPrevia(item) {
      this.cargandoVistaPrevia = true;
      this.pdfSrc = '';
      this.vistaPreviaRFC = item.rfc;
      this.dialogVistaPrevia = true;

      try {
        const response = await axios.post(urlgenerar_ordenes, {
          opcion: 4, // Opción para VISTA PREVIA
          prospecto: item,
          usuario_id: this.sessionData.id_usuario
        }, {
          responseType: 'blob'
        });

        if (response.data.size > 0) {
          const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
          this.pdfSrc = url;
        } else {
          Swal.fire('Error', 'El documento de vista previa está vacío.', 'error');
          this.dialogVistaPrevia = false;
        }
      } catch (error) {
        Swal.fire('Error', 'No se pudo generar la vista previa del documento.', 'error');
        this.dialogVistaPrevia = false;
        console.error("Error al generar la vista previa:", error);
      } finally {
        this.cargandoVistaPrevia = false;
      }
    },
    async obtenerPermisos() {
      try {
        // Hacer la solicitud al endpoint PHP
        const response = await axios.get('http://10.10.120.228/siga/backend/session_check.php');
        // Asignar la respuesta a sessionData
        this.sessionData = response.data;
        // Verificar la propiedad 'nivel' para establecer el permiso
        const nivel = Number(this.sessionData?.nivel); // Convertir nivel a número directamente
        this.permiso = [0, 2].includes(nivel); // Validar si está en los valores permitidos
      } catch (error) {
        // Manejar errores en la solicitud
        console.error('Error al obtener los datos de la sesión:', error);
      }
    },
    mostrar: function () {
      axios
        .post(crud, { opcion: 1, estatus_prospecto:6 })
        .then((response) => {
          if (Array.isArray(response.data)) {
            this.prospectosie = response.data;

            // Filtrar los registros con antecedente_id = 7
            this.prospectosie_no_localizados = this.prospectosie
            .filter(item => Number(item.antecedente_id) === 7) // fuerza a número por si viene como string
            .map(item => ({ ...item }));

            // console.log("No Localizados:", this.prospectosie_no_localizados);

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
    
    async editar(prospectoieData, periodosParaAgregar) {
      let nombre = prospectoieData.nombre != null && prospectoieData.nombre !== '' ? prospectoieData.nombre.toUpperCase() : prospectoieData.nombre;
      let calle = prospectoieData.calle != null && prospectoieData.calle !== '' ? prospectoieData.calle.toUpperCase() : prospectoieData.calle;
      let num_exterior = prospectoieData.num_exterior != null && prospectoieData.num_exterior !== '' ? prospectoieData.num_exterior.toUpperCase() : prospectoieData.num_exterior;
      let num_interior = prospectoieData.num_interior != null && prospectoieData.num_interior !== '' ? prospectoieData.num_interior.toUpperCase() : prospectoieData.num_interior;
      let colonia = prospectoieData.colonia != null && prospectoieData.colonia !== '' ? prospectoieData.colonia.toUpperCase() : prospectoieData.colonia;
      let localidad = prospectoieData.localidad != null && prospectoieData.localidad !== '' ? prospectoieData.localidad.toUpperCase() : prospectoieData.localidad;
      let giro = prospectoieData.giro != null && prospectoieData.giro !== '' ? prospectoieData.giro.toUpperCase() : prospectoieData.giro;
      let periodos = prospectoieData.periodos != null && prospectoieData.periodos !== '' ? prospectoieData.periodos.toUpperCase() : prospectoieData.periodos;
      let representante_legal = prospectoieData.representante_legal != null && prospectoieData.representante_legal !== '' ? prospectoieData.representante_legal.toUpperCase() : prospectoieData.representante_legal;
      let observaciones = prospectoieData.observaciones != null && prospectoieData.observaciones !== '' ? prospectoieData.observaciones.toUpperCase() : prospectoieData.observaciones;
      axios
        .post(crud, { // Objeto de datos
            // Cambios
            opcion: 3,
            // Campos a guardar
            rfc:prospectoieData.rfc,
            id:prospectoieData.id,
            nombre:nombre,
            calle:calle,
            num_exterior:num_exterior,
            num_interior:num_interior,
            colonia:prospectoieData.colonia,
            cp:prospectoieData.cp,
            localidad:localidad,
            municipio_id:prospectoieData.municipio_id,
            oficina_id:prospectoieData.oficina_id,
            fuente_id:prospectoieData.fuente_id,
            giro:giro,
            periodos:periodos,
            antecedente_id:prospectoieData.antecedente_id,
            impuesto_id:prospectoieData.impuesto_id,
            determinado:prospectoieData.determinado,
            programador_id:prospectoieData.programador_id,
            representante_legal:representante_legal,
            retenedor:prospectoieData.retenedor,
            origen_id: prospectoieData.origen_id,
            observaciones:observaciones,
            estatus:prospectoieData.estatus
        })
        .then(response =>{
          Swal.fire({
            title: "Exito",
            text: "La información fue actualizada satisfactoriamente",
            icon: 'success',
            showCancelButton: false,
            showConfirmButton:false,
            timer:2000,
            timerProgressBar: true,
            allowOutsideClick: false, // Bloquea clics fuera del diálogo
            allowEscapeKey: false, // Bloquea la tecla de escape
            allowEnterKey: false // Bloquea la tecla enter
          })
          this.sincronizarPeriodosDetalle(prospectoieData.id, periodosParaAgregar);
          // Si el prospecto ya tiene una orden generada, actualiza también la tabla de órdenes
          if (this.tieneOrdenGenerada(prospectoieData)) {
            axios.post(urlgenerar_ordenes, {
              opcion: 3, // Opción para actualizar datos en tabla de órdenes
              prospecto: prospectoieData
            });
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al actualizar los datos: ' + error.message,
            confirmButtonText: 'OK',
            confirmButtonAriaLabel: 'OK'
          });
        })
        .finally(() => {
          this.mostrar();
        })
    },

    obtieneoficinas: function () {
      axios.post(urloficinas).then((response) => {
        this.oficinas_listado = response.data;
      });
    },

    obtienefuentes: function () {
      axios.post(urlfuentes).then((response) => {
        this.fuentes_listado = response.data;
      });
    },

    obtieneimpuestos: function () {
      axios.post(urlimpuestos).then((response) => {
        this.impuestos_listado = response.data;
      });
    },

    obtieneantecedentes: function () {
      axios.post(urlantecedentes).then((response) => {
        this.antecedentes_listado = response.data;
      });
    },

    obtieneusuarios: function () {
      axios.post(urlprogramadores, { opcion: 1 }).then((response) => {
        this.programadores_listado = response.data;
      });
    },

    obtienemunicipios: function () {
      axios.post(urlmunicipios).then((response) => {
        this.municipios_listado = response.data;
      });   
    },
    
    salir: function(){
      // Redirigir al usuario a la vista de login
      localStorage.removeItem("id");
      localStorage.removeItem("token");
      localStorage.removeItem("nombre");
      localStorage.removeItem("nivel");
      this.$user.id = null;
      this.$user.nombre = null;
      this.isAuthenticated = false;
      this.usuarioLogueado = "";
      this.opcionesMenu = [];
      this.$router.push("/login");
    },
    //Botones y formularios
    async handleGuardar(prospectoieData, periodosParaAgregar) {
      if (this.operacion === "crear") {
        await this.crear(prospectoieData, periodosParaAgregar);
      } else if (this.operacion === "editar") {
        await this.editar(prospectoieData, periodosParaAgregar);
      }
    },

    updateProspectoie(updatedProspectoie) {
      this.prospectoie = { ...this.prospectoie, ...updatedProspectoie };
    },

    formEditar: function (objeto) {
      this.dialog = true;
      this.operacion = "editar";
      this.prospectoie.id=objeto.id;
      this.prospectoie.fecha_captura=this.convertirFecha(objeto.fecha_captura);
      this.prospectoie.rfc=objeto.rfc;
      this.prospectoie.nombre=objeto.nombre;
      this.prospectoie.calle=objeto.calle;
      this.prospectoie.num_exterior=objeto.num_exterior;
      this.prospectoie.num_interior=objeto.num_interior;
      this.prospectoie.colonia=objeto.colonia;
      this.prospectoie.cp=objeto.cp;
      this.prospectoie.localidad=objeto.localidad;
      this.prospectoie.municipio_id=objeto.municipio_id;
      this.prospectoie.giro=objeto.giro;
      this.prospectoie.oficina_id=objeto.oficina_id;
      this.prospectoie.fuente_id=objeto.fuente_id;
      this.prospectoie.periodos=objeto.periodos;
      this.prospectoie.antecedente_id=objeto.antecedente_id;
      this.prospectoie.impuesto_id=objeto.impuesto_id;
      this.prospectoie.programador_id=objeto.programador_id;
      this.prospectoie.retenedor=objeto.retenedor;
      this.prospectoie.origen_id=objeto.origen_id;
      this.prospectoie.determinado=objeto.determinado;
      this.prospectoie.representante_legal=objeto.representante_legal;
      this.prospectoie.observaciones=objeto.observaciones;
      this.prospectoie.estatus_descripcion=objeto.estatus_descripcion;
    },
    
    convertirFecha(fechaCaptura) {
    // Convertir a objeto Date
    const fecha = new Date(fechaCaptura);

    // Obtener día, mes y año
    const day = String(fecha.getDate()).padStart(2, '0');
    const month = String(fecha.getMonth() + 1).padStart(2, '0'); // Meses empiezan en 0
    const year = fecha.getFullYear();

    // Formatear fecha en DD-MM-YYYY
    const fechaFormateada = `${day}/${month}/${year}`;

    return fechaFormateada;
    },
    fechaactual: function () {
      const today = new Date();
      const day = String(today.getDate()).padStart(2, '0');
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const year = today.getFullYear();
      return `${day}/${month}/${year}`;
    },
    async sincronizarPeriodosDetalle(prospectoId, periodsArray) {
      // Valida que el parámetro sea un array y no esté vacío.
      if (!Array.isArray(periodsArray) || periodsArray.length === 0) {
        console.log("No hay periodos para sincronizar.");
        return;
      }
      try {
        // 1. Eliminar periodos existentes para este prospecto en la BD.
        await axios.post(crud, { // Objeto de datos
          opcion: 6, // Nueva opción para eliminar periodos por prospecto_id
          prospecto_id: prospectoId
        });

        // 2. Insertar los nuevos periodos uno por uno desde el array.
        for (const periodo of periodsArray) {
          if (periodo.inicio && periodo.fin) {
            await axios.post(crud, { // Objeto de datos
              opcion: 7, // Nueva opción para insertar un periodo
              prospecto_id: prospectoId,
              fecha_inicial: periodo.inicio,
              fecha_final: periodo.fin,
              status: 1 // Asumiendo un status por defecto
            });
          }
        }
        console.log('Periodos sincronizados correctamente.');
      } catch (error) {
        Swal.fire('Error', 'Hubo un problema al sincronizar los periodos: ' + error.message, 'error');
        console.error('Error al sincronizar periodos:', error);
      }
    }
  },
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
