<template>
  <v-container>
    <v-container class="my-2">
      <!-- Botón Crear y Exportar -->
      <v-row class="center">
        <v-spacer></v-spacer>
        <v-col cols="12"  class="text-center">
          <h1>LISTA DE EMITIDAS</h1>
        </v-col>
      </v-row>
      <v-row class="mb-4" align="center">
        <v-col class="d-flex align-center">
          <!-- Boton exportar Excel -->
          <v-tooltip top color="indigo darken-3" v-if="permiso">
            <template v-slot:activator="{ on, attrs }">
              <v-btn fab class="ml-3" color="green darken-3" dark v-bind="attrs" v-on="on" @click="generarFiscaweb"><v-icon large>mdi-microsoft-excel</v-icon></v-btn>
            </template>
            <span>Generar archivo FISCAWEB</span>
          </v-tooltip>
          <!-- Boton recargar  -->
          <v-tooltip right color="light-blue darken-4">
            <template v-slot:activator="{ on, attrs }">
              <v-btn class="ml-3" color="light-blue darken-4" fab dark @click="mostrar()" v-bind="attrs" v-on="on"><v-icon large>mdi-refresh</v-icon></v-btn>
            </template>
            <span>Recargar información</span>
          </v-tooltip>
        </v-col>              
        <v-spacer></v-spacer>
        <v-col cols="6">
          <v-text-field v-model="busca" append-icon="mdi-magnify" label="Buscar" single-line hide-details></v-text-field>
        </v-col>
      </v-row>
      <v-row class="mb-4" align="center">
        <v-col class="d-flex align-center" cols="12" md="2"><v-select v-model="filtros.anio" :items="anios" label="Año" dense outlined hide-details class="mr-4"/></v-col>
        <v-col cols="12" md="2"><v-btn color="pink darken-4 white--text" @click="consultar">CONSULTAR</v-btn></v-col>       
        <v-spacer></v-spacer>
      </v-row>
      <!-- Tabla y formulario -->
      <v-data-table :headers="encabezados" :items="prospectosie" item-key="id" :search="busca" class="tabla-emitidas">
        <template v-slot:item.tipo="{ item }">
          <v-icon v-if="item.antecedente_id==6" large class="mr-2" color="blue-grey darken-2" dark dense>mdi-cog-sync</v-icon>
          <v-icon v-if="item.antecedente_id==7" large class="mr-2" color="pink accent-3" dark dense>mdi-map-marker-remove</v-icon>
          <v-icon v-else large class="mr-2" color="teal accent-4" dark dense>mdi-progress-check</v-icon>
        </template>
        <!-- Acciones -->
        <template v-slot:item.actions="{ item }">
          <!-- <v-tooltip top>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="amber" dark dense style="font-size: 32px" @click="formEditar(item)">mdi-pencil</v-icon>
            </template>
            <span>Editar Prospecto</span>
          </v-tooltip> -->
          <v-tooltip top>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="black" dark dense style="font-size: 32px" @click="vistaPrevia(item)">mdi-file-eye</v-icon>
            </template>
            <span>Ver documento</span>
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
    <!-- Componente de Diálogo para VISTA PREVIA -->
    <v-dialog v-model="dialogVistaPrevia" max-width="1000" transition="dialog-top-transition" persistent>
      <v-card>
        <v-card-title class="pink darken-4 white--text">
          VISTA PREVIA - {{ vistaPreviaRFC }}
          <v-spacer></v-spacer>
          <v-btn icon dark @click="cerrarVistaPrevia"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-card-text>
          <div v-if="cargandoVistaPrevia" class="text-center py-5">
            <v-progress-circular indeterminate color="pink darken-4"></v-progress-circular>
            <p class="mt-2">Generando vista previa...</p>
          </div>
          <embed v-else-if="pdfSrc" :src="pdfSrc" type="application/pdf" width="100%" height="600px" />
        </v-card-text>
      </v-card>
    </v-dialog>
    <v-dialog v-model="progresoVisible" persistent max-width="400">
      <v-card class="pa-4" style="text-align: center;">
        <v-progress-circular indeterminate size="50"></v-progress-circular>
        <div class="mt-3">{{ progresoMensaje }}</div>
      </v-card>
    </v-dialog>      
  </v-container>
</template>

<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import FormCrearEditar from '@/components/formCrearEditar.vue';
  import api from '@/services/apiUrls.js';
  import * as XLSX from 'xlsx-js-style';

export default {
  name: "Emitidas",
  data() {
    return {
      busca: "",
      filtros: {anio: null},
      anios: [],
      anioActual: new Date().getFullYear(),
      progresoVisible: false,
      progresoMensaje: "",
      encabezados: [
        {
          text: "ACCIONES",
          value: "actions",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "100"
        },
        {
          text: "OFICIO",
          value: "num_oficio",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "70"
        },
        {
          text: "ORDEN",
          value: "num_orden",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "100"
        },
        {
          text: "FECHA DE ORDEN",
          value: "fecha_orden",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "120"
        },
         {
          text: "RFC",
          value: "rfc",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "70"
        },
        {
          text: "NOMBRE",
          value: "nombre",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "250"
        },
        {
          text: "DOMICILIO COMPLETO",
          value: "domicilio_completo",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "350"
        },
        {
          text: "PERIODOS",
          value: "periodos",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "100"
        },
        {
          text: "IMPUESTO",
          value: "impuesto",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "50"
        },
        {
          text: "PROGRAMADOR",
          value: "programador_descripcion",
          class: "pink darken-4 white--text elevation-1 center-header",
          width: "40"
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
        colonia: null, cp: null, localidad: null, municipio_id:null, municipio: null, oficina_descripcion: null, oficina_id: null, 
        fuente_id:null, giro: null, periodos: null, impuesto_id: null, antecedente_id:null, determinado: 0, programador_id: null, 
        programador_descripcion: null, retenedor:null, cambio_domicilio:null, domicilio_anterior:null, notificador:null, fecha_acta:null, 
        origen_id:null, representante_legal: null, estatus: 6,
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
    this.obtieneoficinas();
    this.obtienefuentes();
    this.obtieneimpuestos();
    this.obtieneusuarios();
    this.obtieneantecedentes();
    this.obtienemunicipios();
    this.obtenerAnios();
  },
 methods: {
  async generarFiscaweb() {
    if (this.progresoVisible) return;
    try {
      this.actualizarProgreso('Generando archivo ORDENES_FISCAWEB...');
      const { data } = await axios.post(api.fiscaweb, {opcion: 1});

      if (!Array.isArray(data) || !data.length) {
        this.progresoVisible = false;
        Swal.fire('Aviso', 'No hay información para generar el archivo.', 'warning');
        return;
      }

      const wb = XLSX.utils.book_new();
      const border = {top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' }};
      const headerStyle = { alignment: { horizontal: 'center', vertical: 'center' }, border};
      const cellCenter = {alignment: { horizontal: 'center', vertical: 'center' }, border};
      const cellLeft = {alignment: { horizontal: 'left', vertical: 'center' }, border};
      const cCenterYellow = {alignment: { horizontal: 'center', vertical: 'center' }, fill: { fgColor: { rgb: 'FFF9C4' } }, border};
      const cLeftYellow = {alignment: { horizontal: 'left', vertical: 'center' }, fill: { fgColor: { rgb: 'FFF9C4' } }, border};
      const rows = [];
      rows.push([
        { v: 'ORDEN', s: headerStyle },
        { v: 'EXPEDIENTE', s: headerStyle },
        { v: 'OFICIO', s: headerStyle },
        { v: 'FECORDEN', s: headerStyle },
        { v: 'NUMOFICIO', s: headerStyle },
        { v: 'RFC', s: headerStyle },
        { v: 'NOMBRE', s: headerStyle },
        { v: 'DIRECCION', s: headerStyle },
        { v: 'OFICINA_OFICINA', s: headerStyle },
        { v: 'FUENTE_FUENTE', s: headerStyle },
        { v: 'SUBPROG_SUBPROG', s: headerStyle },
        { v: 'EJERCICIO_EJERCICIO', s: headerStyle },
        { v: 'MUNICIPIO_MUNICIPIO', s: headerStyle },
        { v: 'METODO_METODO', s: headerStyle },
        { v: 'JEFEDEPTO_RFC', s: headerStyle },
        { v: 'PRESUNTIVA', s: headerStyle },
        { v: 'PER1', s: headerStyle },
        { v: 'EJE1_EJERCICIO', s: headerStyle },
        { v: 'PER2', s: headerStyle },
        { v: 'EJE2_EJERCICIO', s: headerStyle },
        { v: 'PROGRAMADOR_RFC', s: headerStyle },
        { v: 'PORTALUSER', s: headerStyle },
        { v: 'FECALTASIS', s: headerStyle },
        { v: 'ESTATUS_ESTATUS', s: headerStyle },
        { v: 'PERIODO', s: headerStyle }
      ]);
      data.forEach(item => {
        const yellow = Number(item.MODIFICADO) === 1;
        const cCenter = yellow ? cCenterYellow : cellCenter;
        const cLeft = yellow ? cLeftYellow   : cellLeft;
        rows.push([
          { v: item.ORDEN || '', s: cCenter },                 
          { v: item.EXPEDIENTE || '', s: cCenter },                        
          { v: item.OFICIO || '', s: cCenter },                
          { v: item.FECORDEN || '', s: cCenter },               
          { v: item.NUMOFICIO || '', s: cCenter },                
          { v: item.RFC || '', s: cCenter },                       
          { v: item.NOMBRE || '', s: cLeft },                      
          { v: item.DIRECCION || '', s: cLeft },          
          { v: item.OFICINA_OFICINA || '', s: cCenter },       
          { v: item.FUENTE_FUENTE || '', s: cCenter },        
          { v: item.SUBPROG_SUBPROG || '', s: cCenter },               
          { v: item.EJERCICIO_EJERCICIO || '', s: cCenter },             
          { v: item.MUNICIPIO_MUNICIPIO || '', s: cCenter },                 
          { v: item.METODO_METODO || '', s: cCenter },                    
          { v: item.JEFEDEPTO_RFC || '', s: cCenter },            
          { v: item.PRESUNTIVA || '', s: cCenter },                
          { v: item.PER1 || '', s: cCenter },                      
          { v: item.EJE1_EJERCICIO || '', s: cCenter },                      
          { v: item.PER2 || '', s: cCenter },                      
          { v: item.EJE2_EJERCICIO || '', s: cCenter },                      
          { v: item.PROGRAMADOR_RFC || '', s: cCenter },           
          { v: item.PORTALUSER || '', s: cCenter },      
          { v: item.FECALTASIS || '', s: cCenter },             
          { v: item.ESTATUS_ESTATUS || '', s: cCenter },      
          { v: item.PERIODO || '', s: cCenter }                  
        ]);
      });
      const ws = XLSX.utils.aoa_to_sheet(rows);
      ws['!cols'] = Array(25).fill({ wch: 18 });
      XLSX.utils.book_append_sheet(wb, ws, 'ORDENES');
      XLSX.writeFile(wb, `ORDENES_FISCAWEB_${new Date().toISOString().split('T')[0]}.xlsx`);
    } catch (error) {
      console.error(error);
      Swal.fire('Error', 'No se pudo generar el archivo ORDENES_FISCAWEB', 'error');
    } finally {
      this.progresoVisible = false;
    }
  },
  async obtenerAnios() {
    try {
        const { data } = await axios.post(api.dashboard, { opcion: 1 });
        this.anios = data.map(item => Number(item.anio));
        if (this.anios.includes(this.anioActual)) {
          this.filtros.anio = this.anioActual;
        } else if (this.anios.length) {
          this.filtros.anio = this.anios[0];
        }
        this.mostrar();
    } catch (e) {
        console.error('No se pudieron obtener los años', e);
        this.anios = [];
    }
  },
  async consultar() {
    if (this.filtros.anio == null) {
        Swal.fire('Advertencia', 'Por favor, selecciona un año antes de consultar.', 'warning');
        return;
    }
    try { 
        const { data } = await axios.post(api.emitidas, {
            opcion: 2,
            anio: this.filtros.anio
        });
        this.anioConsultado = this.filtros.anio;           
    }catch (e)
    {
        console.error('Error al consultar los datos de emitidas', e);
        Swal.fire('Error', 'No se pudieron obtener los datos de emitidas', 'error');
    }
  },
  cerrarVistaPrevia() {
      this.dialogVistaPrevia = false;
      if (this.pdfSrc) {
        window.URL.revokeObjectURL(this.pdfSrc);
      }
    },
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
        const response = await axios.post(api.generarOrdenes, {
          opcion: 6, 
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
        const response = await axios.get(api.sessionCheck);
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
        .post(api.emitidas, { opcion: 2, anio: this.filtros.anio})
        .then((response) => {
          if (Array.isArray(response.data)) {
            this.prospectosie = response.data.map(item => ({
              ...item,
              cambio_domicilio: Number(item.cambio_domicilio ?? 0),
              retenedor: Number(item.retenedor ?? 0),
              origen_id: Number(item.origen_id ?? 0)
            }));

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
        .post(api.crud, { // Objeto de datos
            // Cambios
            opcion: 3,
            // Campos a guardar
            rfc:prospectoieData.rfc,
            id:prospectoieData.id,
            nombre:nombre,
            calle:calle,
            num_exterior:num_exterior,
            num_interior:num_interior,
            colonia:colonia,
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
            cambio_domicilio:prospectoieData.cambio_domicilio,
            domicilio_anterior:prospectoieData.domicilio_anterior,
            notificador:prospectoieData.notificador,
            fecha_acta:prospectoieData.fecha_acta,
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
          axios.post(api.generarOrdenes, {
            opcion: 3, // Opción para actualizar datos en tabla de órdenes
            prospecto: prospectoieData
          });
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
        })
    },

    obtieneoficinas: function () {
      axios.post(api.oficinas_listado).then((response) => {
        this.oficinas_listado = response.data;
      });
    },

    obtienefuentes: function () {
      axios.post(api.fuentes).then((response) => {
        this.fuentes_listado = response.data;
      });
    },

    obtieneimpuestos: function () {
      axios.post(api.impuestos).then((response) => {
        this.impuestos_listado = response.data;
      });
    },

    obtieneantecedentes: function () {
      axios.post(api.antecedentes).then((response) => {
        this.antecedentes_listado = response.data;
      });
    },

    obtieneusuarios: function () {
      axios.post(api.programadores, { opcion: 1 }).then((response) => {
        this.programadores_listado = response.data;
      });
    },

    obtienemunicipios: function () {
      axios.post(api.municipios).then((response) => {
        this.municipios_listado = response.data;
      });   
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
      const data = { ...updatedProspectoie };
      if ('retenedor' in data) {
        data.retenedor = Number(data.retenedor);
      }
      if ('origen_id' in data) {
        data.origen_id = Number(data.origen_id);
      }
      this.prospectoie = { ...this.prospectoie, ...data };
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
      this.prospectoie.programador_descripcion = objeto.programador_descripcion;
      this.prospectoie.retenedor=Number(objeto.retenedor ?? 0);
      this.prospectoie.cambio_domicilio = Number(objeto.cambio_domicilio ?? 0);
      this.prospectoie.domicilio_anterior=objeto.domicilio_anterior;
      this.prospectoie.notificador=objeto.notificador;
      this.prospectoie.fecha_acta=this.convertirFecha(objeto.fecha_acta);
      this.prospectoie.origen_id=Number(objeto.origen_id ?? 0);
      this.prospectoie.determinado=objeto.determinado;
      this.prospectoie.representante_legal=objeto.representante_legal;
      this.prospectoie.observaciones=objeto.observaciones;
      this.prospectoie.estatus_descripcion=objeto.estatus_descripcion;
    },
    
    convertirFecha(fechaCaptura) {
    if (!fechaCaptura || fechaCaptura === '0000-00-00') return null;

    // Convertir a objeto Date
    const fecha = new Date(fechaCaptura);
    if (isNaN(fecha.getTime())) return null;

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
        await axios.post(api.crud, { // Objeto de datos
          opcion: 6, // Nueva opción para eliminar periodos por prospecto_id
          prospecto_id: prospectoId
        });

        // 2. Insertar los nuevos periodos uno por uno desde el array.
        for (const periodo of periodsArray) {
          if (periodo.inicio && periodo.fin) {
            await axios.post(api.crud, { // Objeto de datos
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

.tabla-emitidas thead th {
  align-items: center !important;   
  justify-content: center !important;
  text-align: center !important;
  height: 64px;             
  padding: 0 8px !important;
}

.tabla-emitidas thead th .v-data-table-header__text {
  display: block;
  line-height: 1.2;
  white-space: normal;
}
</style>
