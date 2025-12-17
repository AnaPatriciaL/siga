<template>
  <v-container>
    <v-container class="my-2">
      <!-- Botón Crear y Exportar -->
      <v-row class="center">
        <v-spacer></v-spacer>
        <v-col cols="8"  class="text-center">
          <h1>PROSPECTOS AUTORIZADOS PARA EMITIR</h1>
        </v-col>
        <v-spacer></v-spacer>
        <v-col cols="1" class="text-right">
            <v-btn color="pink darken-4" dark @click="salir()"><v-icon class="mr-3">mdi-exit-to-app</v-icon> Salir</v-btn>
        </v-col>
      </v-row>
       <v-row class="mb-4" align="center">
        <v-col class="d-flex align-center">
          <!-- Boton exportar Excel -->
          <vue-excel-xlsx v-if="permiso" :data="prospectosie" :columns="columnas" :file-name="'Autorizados'" :file-type="'xlsx'" :sheet-name="'ProspectosIE'">
            <v-tooltip top color="green darken-3">
              <template v-slot:activator="{ on, attrs }">
                <v-btn fab class="green ml-3" dark v-bind="attrs" v-on="on"><v-icon large>mdi-microsoft-excel</v-icon></v-btn>
              </template>
              <span>Exportar a Excel</span>
            </v-tooltip>
          </vue-excel-xlsx>
          <!-- Boton recargar  -->
          <v-tooltip right color="light-blue darken-4">
            <template v-slot:activator="{ on, attrs }">
              <v-btn class="ml-3" color="light-blue darken-4" fab dark @click="mostrar()" v-bind="attrs" v-on="on"><v-icon large>mdi-refresh</v-icon></v-btn>
            </template>
            <span>Recargar información</span>
          </v-tooltip>
        </v-col>             
        <v-spacer></v-spacer>
        <v-col COL="6">
          <v-text-field v-model="busca" append-icon="mdi-magnify" label="Buscar" single-line hide-details></v-text-field>
        </v-col>
      </v-row>
      <!-- Contador de folios y órdenes -->
      <v-row align="center" class="mb-2">
        <v-spacer></v-spacer>
        <v-col cols="auto" class="text-right">
          <span class="mr-4 font-weight-bold">Órdenes Generadas: {{ ordenesGeneradasCount }}</span>
          <span class="mr-4 font-weight-bold">Órdenes Pendientes: {{ ordenesPendientesCount }}</span>
          <span class="mr-4 font-weight-bold">Folios Disponibles: {{ foliosDisponiblesCount }}</span>
        </v-col>
        <v-col cols="auto">
            <v-menu v-model="menuFechaOrden" :close-on-content-click="false" transition="scale-transition" offset-y min-width="auto">
            <template v-slot:activator="{ on, attrs }">
              <v-text-field
                v-model="fechaOrdenFormateada" label="Fecha de la Orden" prepend-icon="mdi-calendar" readonly
                v-bind="attrs" v-on="on" dense outlined hide-details style="width: 200px;"></v-text-field>
            </template>
            <v-date-picker v-model="fechaOrden" @input="menuFechaOrden = false" no-title locale="es-es"></v-date-picker>
          </v-menu>
        </v-col>
      </v-row>
      <!-- Tabla y formulario -->
      <v-data-table v-model="selectedProspectos" :headers="encabezados" :items="prospectosie" item-key="id" class="elevation-1" 
      :search="busca" show-select>
        <template v-slot:item.tipo="{ item }">
          <v-icon v-if="item.antecedente_id==6" large class="mr-2" color="blue-grey darken-2" dark dense>mdi-cog-sync</v-icon>
          <v-icon v-if="item.antecedente_id==7" large class="mr-2" color="pink accent-3" dark dense>mdi-map-marker-remove</v-icon>
          <v-icon v-else large class="mr-2" color="teal accent-4" dark dense>mdi-progress-check </v-icon>
        </template>
        <!-- Acciones -->
        <template v-slot:item.actions="{ item }">
          <div class="d-flex">
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="amber" dark dense style="font-size: 32px" @click="formEditar(item)">mdi-pencil</v-icon>
              </template>
              <span>Editar Prospecto</span>
            </v-tooltip>
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="black" dark dense style="font-size: 32px" @click="generarDocumento(item, $event, 1)">mdi-file-eye</v-icon>
              </template>
              <span>Vista previa</span>
            </v-tooltip>
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs" v-on="on" large class="ml-2" :color="tieneOrdenGenerada(item) ? 'success' : 'orange'" dark dense style="font-size: 32px" @click="generarDocumentoUnico(item, $event, 0)">mdi-file-word</v-icon>
              </template>
              <span>Generar/imprimir Orden</span>
            </v-tooltip>
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="success" dark dense style="font-size: 32px" @click="seleccionarProspecto(item)">mdi-check-circle </v-icon>
              </template>
              <span>Enviar a Emitidas</span>
            </v-tooltip>
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="red" dark dense @click="prospectoPendiente(item)">mdi-close-circle</v-icon>
              </template>
              <span>No autorizado</span>
            </v-tooltip>
          </div>
        </template>
      </v-data-table>
      <v-row class="mt-4">
        <v-col class="d-flex justify-end">
          <v-btn color="teal" dark @click="generarDocumentoFirmas" :disabled="selectedProspectos.length === 0" class="mr-2">Generar documento de firmas ({{ selectedProspectos.length }})</v-btn>
          <v-btn color="blue-grey" dark @click="generarDocumentosSeleccionados" :disabled="selectedProspectos.length === 0">Generar Órdenes Seleccionadas ({{ selectedProspectos.length }})</v-btn>
        </v-col>
      </v-row>
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
    <!-- Componente de Diálogo para "No Autorizado" -->
    <dialog-antecedente
      v-model="dialogAntecedente"
      :antecedentes="antecedentes_listado"
      @confirmar="confirmarPendiente"
      @cerrar="dialogAntecedente = false"
    ></dialog-antecedente>
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
  import * as XLSX from 'xlsx-js-style';
  import FormCrearEditar from '@/components/formCrearEditar.vue';
  import DialogAntecedente from '@/components/dialogoNoAutorizado.vue';

  var crud = "http://10.10.120.228/siga/backend/crud_prospectosie.php";
  var urloficinas = "http://10.10.120.228/siga/backend/oficinas_listado.php";
  var urlfuentes = "http://10.10.120.228/siga/backend/fuentes_listado.php";
  var urlprogramadores = "http://10.10.120.228/siga/backend/programadores_listado.php";
  var urlimpuestos = "http://10.10.120.228/siga/backend/impuestos_listado.php";
  var urlantecedentes = "http://10.10.120.228/siga/backend/antecedentes_listado.php";
  var urlpadron = "http://10.10.120.228/siga/backend/padron_contribuyentes.php";
  var urlmunicipios ="http://10.10.120.228/siga/backend/municipios_listado.php";
  var urlgenerar_ordenes ="http://10.10.120.228/siga/backend/generar_ordenes.php";
  var urlfolios_oficios ="http://10.10.120.228/siga/backend/folios_oficios.php";

export default {
  name: "Autorizadas",
  data() {
    return {
      ordenesGeneradasCount: 0,
      ordenesPendientesCount: 0,
      prospectosConOrdenGenerada: new Set(),
      selectedProspectos: [],
      foliosDisponiblesCount: 0,
      busca: "",
      progresoVisible: false,
      progresoMensaje: "",
      dialogAntecedente: false,
      prospectoSeleccionado: null,
      encabezados: [
        {
          text: "", // El texto del encabezado del checkbox puede ser vacío
          value: "data-table-select", // Valor especial para la columna de selección
          class: "pink darken-4 white--text elevation-1 center-header", // Aplica las mismas clases de estilo
          width: "20" // Ajusta el ancho según sea necesario
        },
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
          class: "pink darken-4 white--text elevation-1 text-center",
          width:"250"
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
        programador_id: null, retenedor:null, origen_id:null, representante_legal: null, estatus: 5,
      },
      impuestos_listado: [],
      antecedentes_listado:[],
      programadores_listado: [],
      oficinas_listado: [],
      municipios_listado:[],
      fuentes_listado:[],
      permiso:false,
      dialogVistaPrevia: false, // Controla la visibilidad del diálogo de vista previa
      cargandoVistaPrevia: false, // Controla el estado de carga de la vista previa
      pdfSrc: '', // Contendrá la URL del PDF para el embed
      vistaPreviaRFC: '', // Para mostrar el RFC en el título del diálogo de vista previa
      foliosDisponibles: 0,
      fechaOrden: new Date().toISOString().substr(0, 10),
      menuFechaOrden: false,
    };
  },
  computed: {
    fechaOrdenFormateada() {
      if (!this.fechaOrden) return null;
      const [year, month, day] = this.fechaOrden.split('-');
      return `${day}/${month}/${year}`;
    },
  },
  components:{
    FormCrearEditar,
    DialogAntecedente 
  },
  created() {
    this.obtenerPermisos(),
    this.mostrar(),
    this.obtieneoficinas(),
    this.obtienefuentes(),
    this.obtieneimpuestos(),
    this.obtieneusuarios(),
    this.obtieneantecedentes(),
    this.obtienemunicipios(),
    this.obtienefoliosoficios();
  },
 methods: {
    formatearFechaDMY(fecha) {
      if (!fecha) return "";

      const [anio, mes, dia] = fecha.split("-");
      return `${dia}/${mes}/${anio}`;
    },
    async generarDocumentoFirmas() {
      if (this.selectedProspectos.length === 0) return;

      const prospectoSinOrden = this.selectedProspectos.some(p => !this.tieneOrdenGenerada(p));
      if (prospectoSinOrden) {
        Swal.fire({
          icon: 'warning',
          title: 'Acción no permitida',
          text: 'Seleccionó prospectos sin orden, es necesario generarle una orden a todos.'
        });
        return;
      }

      this.actualizarProgreso('Obteniendo datos de órdenes...');

      const prospecto_ids = this.selectedProspectos.map(p => p.id);
      let prospectosOrdenados = [];
      try {
        const response = await axios.post(urlgenerar_ordenes, {
          opcion: 5,
          prospecto_ids
        });
        prospectosOrdenados = response.data;
      } catch (error) {
        this.progresoVisible = false;
        Swal.fire('Error', 'No se pudieron obtener los datos actualizados de las órdenes.', 'error');
        console.error("Error al obtener datos para documento de firmas:", error);
        return;
      }

      this.actualizarProgreso('Generando documento...');

      const wb = XLSX.utils.book_new();
      const borderAll = {
        top: { style: "thin" },
        bottom: { style: "thin" },
        left: { style: "thin" },
        right: { style: "thin" },
        left: { style: "thin" }, right: { style: "thin" }
      };
      const titleRight = {
        font: { sz: 11 },
        alignment: { horizontal: "right", vertical: "center" }
      };

      const titleCenter = {
        font: { sz: 11 },
        alignment: { horizontal: "center", vertical: "center", wrapText: true }
      };

      const headerStyle = {
        font: { bold: true, sz: 11 },
        fill: { fgColor: { rgb: "D9D9D9" } },
        alignment: { horizontal: "center", vertical: "center", wrapText: true },
        border: borderAll
      };

      const cellCenter = {
        font: { sz: 11 },
        alignment: { horizontal: "center", vertical: "center", wrapText: true },
        border: borderAll
      };

      const firmaStyle = {
        font: { sz: 11 },
        alignment: { horizontal: "left", vertical: "top", wrapText: true }
      };
      const firmaTituloStyle = {
        font: { bold: true, sz: 11 },
        alignment: { horizontal: "center", vertical: "top", wrapText: true }
      };
      
      const ws_data = [];

      const nombreJefe = prospectosOrdenados.length > 0 ? prospectosOrdenados[0].nombre_jefe : "";
      const nombreFirmante = prospectosOrdenados.length > 0 ? prospectosOrdenados[0].nombre_firmante : "";

      const hoy = new Date();
      const fechaFormateada = `${String(hoy.getDate()).padStart(2,'0')}/${String(hoy.getMonth()+1).padStart(2,'0')}/${hoy.getFullYear()}`;
      const encabezadoFecha = `CULIACÁN, SINALOA A ${fechaFormateada}`;
      
      // Encabezado
      ws_data.push([{ v: encabezadoFecha, s: titleRight }]);
      ws_data.push([{ v: "SECRETARÍA DE ADMINISTRACIÓN Y FINANZAS", s: titleCenter }]);
      ws_data.push([{ v: "SERVICIO DE ADMINISTRACIÓN TRIBUTARIA DEL ESTADO DE SINALOA", s: titleCenter }]);
      ws_data.push([{ v: "DIRECCIÓN DE AUDITORÍA", s: titleCenter }]);
      ws_data.push([]);

      // Encabezados tabla
      ws_data.push([
        { v: "No.", s: headerStyle },
        { v: "FECHA OFICIO", s: headerStyle },
        { v: "No. OFICIO\nSATES-DA-DP-\n/2025", s: headerStyle },
        { v: "ORDEN", s: headerStyle },
        { v: "NOMBRE", s: headerStyle },
        { v: "LOCALIDAD", s: headerStyle }
      ]);

      // Filas de datos
      prospectosOrdenados.forEach((item, index) => {
        ws_data.push([
          { v: index + 1, s: cellCenter },
          { v: this.formatearFechaDMY(item.fecha_orden), s: cellCenter },
          { v: item.num_oficio || "", s: cellCenter },
          { v: item.num_orden || "", s: cellCenter },
          { v: item.nombre || "", s: cellCenter },
          { v: item.municipio || "", s: cellCenter }
        ]);
      });

      // Fila vacía
      ws_data.push([]);

      // === INICIO DEL BLOQUE DE FIRMAS: Se prepara el texto y se añaden las filas necesarias ===
      const firmaInicio = ws_data.length;
      const textoEnvia = `ENVÍA:\n\n${nombreJefe.toUpperCase()}\n\n\nFIRMA:\n\nFECHA:`;
      const textoRecibe = `RECIBE PARA FIRMA:\n\n${nombreFirmante.toUpperCase()}\n\n\nFIRMA:\n\nFECHA:`;

      // Se agrega el contenido en la primera fila de los cuadros de firma.
      ws_data.push([
        { v: textoEnvia, s: firmaStyle }, "", "", "", 
        { v: textoRecibe, s: firmaStyle }, ""
      ]);
      ws_data.push(["", "", "", "", "", ""]); // Fila vacía para la segunda línea del cuadro
      ws_data.push(["", "", "", "", "", ""]); // Fila vacía para la tercera línea del cuadro

      // Crear hoja
      const ws = XLSX.utils.aoa_to_sheet(ws_data);
      ws['!merges'] = ws['!merges'] || [];

      // === MERGES ===
      ws['!merges'].push(
        { s: { r: 0, c: 0 }, e: { r: 0, c: 5 } },
        { s: { r: 1, c: 0 }, e: { r: 1, c: 5 } },
        { s: { r: 2, c: 0 }, e: { r: 2, c: 5 } },
        { s: { r: 3, c: 0 }, e: { r: 3, c: 5 } },
        { s: { r: firmaInicio, c: 0 }, e: { r: firmaInicio + 2, c: 3 } }, // Cuadro ENVÍA
        { s: { r: firmaInicio, c: 4 }, e: { r: firmaInicio + 2, c: 5 } }  // Cuadro RECIBE
      );

      // --- Aplicación de Bordes a los Cuadros de Firma ---
      const firmaFin = firmaInicio + 2;
      const thinBorder = { style: "thin" };

      // Itera sobre el rango completo de los cuadros de firma
      for (let r = firmaInicio; r <= firmaFin; ++r) {
        for (let c = 0; c <= 5; ++c) {
          const cell_address = XLSX.utils.encode_cell({ r, c });
          if (!ws[cell_address]) ws[cell_address] = { t: 's', v: '' };
          if (!ws[cell_address].s) ws[cell_address].s = {};
          if (!ws[cell_address].s.border) ws[cell_address].s.border = {};

          // Cuadro Izquierdo (ENVÍA)
          if (c >= 0 && c <= 3) {
            if (r === firmaInicio) ws[cell_address].s.border.top = thinBorder;
            if (r === firmaFin) ws[cell_address].s.border.bottom = thinBorder;
            if (c === 0) ws[cell_address].s.border.left = thinBorder;
            if (c === 3) ws[cell_address].s.border.right = thinBorder;
          }
          // Cuadro Derecho (RECIBE)
          else if (c >= 4 && c <= 5) {
            if (r === firmaInicio) ws[cell_address].s.border.top = thinBorder;
            if (r === firmaFin) ws[cell_address].s.border.bottom = thinBorder;
            if (c === 4) ws[cell_address].s.border.left = thinBorder;
            if (c === 5) ws[cell_address].s.border.right = thinBorder;
          }
        }
      }

      ws['!rows'] = ws_data.map((_, i) => i >= firmaInicio && i <= firmaInicio + 2 ? { hpt: 45 } : {} );


      // Tamaño columnas
      ws['!cols'] = [
        { wch: 4 },
        { wch: 14 },
        { wch: 16 },
        { wch: 16 },
        { wch: 45 },
        { wch: 22 }
      ];
      
      // Márgenes estrechos
      ws["!margins"] = {
        left: 0.3,
        right: 0.3,
        top: 0.5,
        bottom: 0.5,
        header: 0.3
      };

      XLSX.utils.book_append_sheet(wb, ws, "Firmas");
      XLSX.writeFile(wb, "Documento_Firmas.xlsx");

      this.progresoVisible = false;
      this.selectedProspectos = [];
    },
    confirmarPendiente({ antecedente_id, observaciones }) {
      if (!this.prospectoSeleccionado) return;

      axios.post(crud, {
        opcion: 5,
        id: this.prospectoSeleccionado.id,
        estatus: 7,
        antecedente_id,
        observaciones
      })
      .then(() => {
        this.$swal({
          title:'¡Hecho!',
          text:'El prospecto se ha cambiado al estatus pendiente.',
          icon:'success',
          showCancelButton: false,
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false});
        this.mostrar();
      })
      .catch(error => {
        this.$swal(
          'Error',
          'No se pudo actualizar el estatus.',
          'error'
        );
        console.error(error);
      })
      .finally(() => {
        this.prospectoSeleccionado = null;
      });
    },
    prospectoPendiente(item) {
      this.prospectoSeleccionado = item;
      this.dialogAntecedente = true;
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
    async generarDocumentoUnico(item, event, tipo) {      
      const { value: numCopias } = await Swal.fire({
        title: 'Número de impresiones',
        input: 'number',
        inputLabel: '¿Cuántas juegos desea imprimir?',
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
      const resp = await this.generarDocumento(item, event, tipo, numCopias);
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
    async generarDocumentosSeleccionados() {
      if (this.selectedProspectos.length === 0) return;

      const { value: numCopias } = await Swal.fire({
        title: 'Número de impresiones',
        input: 'number',
        inputLabel: '¿Cuántas juegos desea imprimir de cada documento?',
        inputValue: 1,
        showCancelButton: true,
        inputValidator: (value) => {
          if (!value || value < 1) {
            return '¡Necesitas ingresar un número válido de impresiones!';
          }
        }
      });

      if (!numCopias) return;
      if (this.selectedProspectos.length === 0) return;

      let total = this.selectedProspectos.length;
      let generados = 0;
      let impresos = 0;

      this.progresoVisible = true;

      for (let i = 0; i < total; i++) {
        const prospecto = this.selectedProspectos[i];

        this.actualizarProgreso(`Generando documento ${i+1} de ${total}...`);

        const resp = await this.generarDocumento(prospecto, null, 0, numCopias);

        if (resp.success) {
          generados++;
          if (resp.impreso) impresos++;
        }
      }

      this.progresoVisible = false;
      this.selectedProspectos = [];

      Swal.fire({
        title: "Proceso completado",
        html: `
          <b>${generados}</b> documentos generados.<br>
          <b>${impresos}</b> enviados a imprimir.
        `,
        icon: "success"
      });
    },
    tieneOrdenGenerada(item) {
        return this.prospectosConOrdenGenerada.has(item.id);
    },
    async obtenerPermisos() {
      try {
        // Hacer la solicitud al endpoint PHP
        const response = await axios.get('http://10.10.120.228/siga/backend/session_check.php');
        this.sessionData = response.data;
        // Verificar la propiedad 'nivel' para establecer el permiso
        const nivel = Number(this.sessionData?.nivel); // Convertir nivel a número directamente
        this.permiso = [0, 2].includes(nivel); // Validar si está en los valores permitidos
      } catch (error) {
        console.error('Error al obtener los datos de la sesión:', error);
      }
    },
    async obtienefoliosoficios() {
      try {
        const response = await axios.post(urlfolios_oficios, { opcion: 1 });
        if (Array.isArray(response.data)) {
          this.folios_oficios = response.data;
          const foliosConEstatusDisponible = this.folios_oficios.filter(item => Number(item.estatus) === 0);
          if (foliosConEstatusDisponible.length > 0) {
            this.siguientefolio = foliosConEstatusDisponible[0];
          }
          this.foliosDisponiblesCount = foliosConEstatusDisponible.length;
          await this.actualizarOrdenesCount();
        } else if (response.data.error) {
          console.error('Error desde el servidor:', response.data.error);
          Swal.fire('Error', response.data.error, 'error');
        } else {
          console.warn('Respuesta inesperada:', response.data);
        }
      } catch (error) {
        console.error('Error en la solicitud de folios:', error);
        Swal.fire('Error de conexión', 'No se pudo obtener la información de los folios', 'error');
      }
    },
    async generarDocumento(item, event, tipo, numCopias) {      
      if (event && event.target) {
        event.target.blur();
      }

      if (tipo === 1) {
        this.vistaPrevia(item);
        return { success: true, impreso: false }; // No se genera, solo se visualiza
      }

      // Lógica para generar o reimprimir (tipo === 0)
      if (this.foliosDisponiblesCount < 1 && !this.tieneOrdenGenerada(item)) {
        Swal.fire('Folios no suficientes', 'No tienes folios para generar una nueva orden.', 'warning');
        return { success: false };
      }

      try {
        let data = {};

        if (this.tieneOrdenGenerada(item)) {
          // Es una REIMPRESIÓN
          data = {
            opcion: 4, // Opción para REIMPRIMIR
            prospecto: item,
            copias: numCopias,
            usuario_id: this.sessionData.id,
            fecha_orden: this.fechaOrden,
          };
          console.log("Enviando datos para REIMPRESIÓN:", data);
        } else {
          // Es una GENERACIÓN NUEVA
          data = {
            prospecto: item,
            usuario_id: this.sessionData.id,
            fecha_orden: this.fechaOrden,
            copias: numCopias
          };
          console.log("Enviando datos para GENERACIÓN:", data);
        }

        // Tanto para generar como para reimprimir, esperamos un PDF directamente
        const response = await axios.post(urlgenerar_ordenes, data, { responseType: 'blob' });
        console.log("Respuesta del servidor (blob):", response);

        // Si la respuesta es un blob (PDF) con contenido, asumimos que fue exitoso
        if (response.data.size > 0) {
          this.obtienefoliosoficios(); // <-- AÑADIDO: Actualiza el contador de folios
          this.mostrar();
          this.actualizarOrdenesCount();
          return { success: true, impreso: true };
        } else {
          return { success: false, impreso: false };
          }
        } catch (error) {
            this.cargando = false;
            console.error("Error en la petición a generar_ordenes.php:", error);
            Swal.fire('Error', 'Hubo un problema de comunicación con el servidor.', 'error');
            return { success: false, impreso: false };
          }
    },
    async vistaPrevia(item) {
      this.cargandoVistaPrevia = true;
      this.pdfSrc = '';
      this.vistaPreviaRFC = item.rfc;
      this.dialogVistaPrevia = true;

      try {
        const response = await axios.post(urlgenerar_ordenes, {
          opcion: 1, // Opción para VISTA PREVIA
          prospecto: item,
          usuario_id: this.sessionData.id_usuario,
          fecha_orden: this.fechaOrden
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
    async actualizarOrdenesCount() {
      this.ordenesGeneradasCount = 0;
      this.ordenesPendientesCount = 0;
      if (this.prospectosie.length === 0) {
        return;
      }
      const prospectoIds = this.prospectosie.map(p => p.id);

      try {
        const response = await axios.post(urlgenerar_ordenes, {
          opcion: 2, // Opción para obtener conteos de órdenes
          prospecto_ids: prospectoIds
        });

        if (response.data && !response.data.error) {
          this.ordenesGeneradasCount = response.data.ordenes_generadas_count || 0;
          this.ordenesPendientesCount = response.data.ordenes_pendientes_count || 0;
          this.prospectosConOrdenGenerada = new Set(response.data.ids_con_orden || []);
          if (this.ordenesPendientesCount > 0 && this.foliosDisponiblesCount < this.ordenesPendientesCount) {
            Swal.fire({
              title: 'Folios no suficientes',
              text: 'No tienes folios suficientes para generar todas las órdenes pendientes.',
              icon: 'warning',
              showCancelButton: false,
              showConfirmButton: false,
              timer: 2000,
              timerProgressBar: true,
              allowOutsideClick: false,
              allowEscapeKey: false,
              allowEnterKey: false
            });
          }
        } else {
          console.error('Error al obtener conteo de órdenes:', response.data.error);
        }
      } catch (error) {
        console.error('Error en la solicitud para conteo de órdenes:', error);
      }
    },
    seleccionarProspecto: function (item) {
      // Validar si el prospecto tiene una orden generada
      if (!this.tieneOrdenGenerada(item)) {
        Swal.fire({
          title: 'Acción no permitida',
          text: 'Debe generar la orden para este prospecto antes de enviarlo a emitidas.',
          icon: 'warning',
          confirmButtonText: 'Entendido',
          showCancelButton: false,
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false
        });
        return; // Detener la ejecución si no hay orden
      }

      Swal.fire({
        title: '¿Enviar este prospecto a emitidas?',
        text: "Esta acción enviará el prospecto a emitidas.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Enviar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.post(crud, {
            opcion: 5, // Opción para actualizar solo el estatus
            id: item.id,
            estatus: 6
          }).then(response => {
            Swal.fire({
              title:'¡Emitida!',
              text:'El prospecto ha sido enviado a emitidas.',
              icon:'success',
              showCancelButton: false,
              showConfirmButton: false,
              timer: 2000,
              timerProgressBar: true,
              allowOutsideClick: false,
              allowEscapeKey: false,
              allowEnterKey: false
            });
            this.mostrar(); // Recargar la tabla para reflejar el cambio
            this.actualizarOrdenesCount(); // Actualizar los conteos de órdenes

          }).catch(error => {
            Swal.fire('Error', 'No se pudo enviar el prospecto.', 'error');
            console.error("Error al cambiar estatus:", error);
          });
        }
      });
    },
    mostrar: function () {
      axios
        .post(crud, { opcion: 1, estatus_prospecto:5 })
        .then((response) => {
          if (Array.isArray(response.data)) {
            this.prospectosie = response.data;
            this.prospectosie_no_localizados = this.prospectosie
            .filter(item => Number(item.antecedente_id) === 7) // fuerza a número por si viene como string
            .map(item => ({ ...item }));
            //this.actualizarOrdenesCount();
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
          
    borrar: function (id) {
      Swal.fire({
        title: "¿Confirma eliminar el prospecto?",
        icon: "warning",
        confirmButtonColor: "#3085d6",
        confirmButtonText: `Si, eliminarlo`,
        cancelButtonColor: "#d33",
        showCancelButton: true,
      }).then((result) => {
        if (result.isConfirmed) {
          axios.post(crud, { opcion: 4, id: id }).then((response) => {
            Swal.fire(
              "¡Eliminado!",
              "se ha eliminado el prospecto",
              "success"
            );
            this.mostrar();
          });
         
        } else if (result.isDenied) {
          Swal.fire(
            "¡Error!",
            "No se pudos eliminar el prospecto",
            "error"
          );
        }
      });
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
        Swal.fire({title:'Error', text:'Hubo un problema al sincronizar los periodos: ' + error.message, icon:'error',
            showCancelButton: false,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false});
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