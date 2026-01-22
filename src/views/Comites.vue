<template>
  <v-container>
    <v-container class="my-2">
      <!-- Botón Crear y Exportar -->
      <v-row class="center">
        <v-spacer></v-spacer>
        <v-col cols="12"  class="text-center">
          <h1>PROSPECTOS ENVIADOS A COMITE</h1>
        </v-col>
      </v-row>
      <v-row class="mb-4" align="center">
        <v-col class="d-flex align-center">
          <!-- Boton exportar Excel -->
          <v-tooltip top color="green darken-3" v-if="permiso">
            <template v-slot:activator="{ on, attrs }">
              <v-btn fab class="green ml-3 mt-2" dark v-bind="attrs" v-on="on" @click="exportarExcelConEstilo"><v-icon large>mdi-microsoft-excel</v-icon></v-btn>
            </template>
            <span>Exportar a Excel</span>
          </v-tooltip>
          <!-- Boton recargar  -->
          <v-tooltip right color="light-blue darken-4">
            <template v-slot:activator="{ on, attrs }">
              <v-btn class="mt-2 ml-3" color="light-blue darken-4" fab dark @click="mostrar()" v-bind="attrs" v-on="on"><v-icon large>mdi-refresh</v-icon></v-btn>
            </template>
            <span>Recargar información</span>
          </v-tooltip>  
        </v-col>            
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
              <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="success" dark dense @click="seleccionarProspecto(item)">mdi-check-circle</v-icon>
            </template>
            <span>Autorizar Prospecto</span>
          </v-tooltip>
          <v-tooltip top>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-bind="attrs" v-on="on" large class="ml-2" color="red" dark dense @click="prospectoPendiente(item)">mdi-close-circle</v-icon>
            </template>
            <span>No autorizado</span>
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
    <dialog-antecedente
      v-model="dialogAntecedente"
      :antecedentes="antecedentes_listado"
      title="¿Marcar como no autorizado?"
      @confirmar="confirmarPendiente"/>     
  </v-container>
</template>

<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import * as XLSX from 'xlsx-js-style';
  import FormCrearEditar from '@/components/formCrearEditar.vue';
  import DialogAntecedente from '@/components/dialogoNoAutorizado.vue';
  import api from '@/services/apiUrls.js';

  var crud = api.crud;
  var urloficinas = api.oficinas;
  var urlfuentes = api.fuentes;
  var urlprogramadores = api.programadores;
  var urlimpuestos = api.impuestos;
  var urlantecedentes = api.antecedentes;
  var urlpadron = api.padron;
  var urlmunicipios = api.municipios;

export default {
  name: "Comites",
  data() {
    return {
      busca: "",
      dialogAntecedente: false,
      prospectoSeleccionado: null,
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
        colonia: null, cp: null, localidad: null, municipio_id:null, municipio: null, oficina_descripcion: null, oficina_id: null, 
        fuente_id:null, giro: null, periodos: null, impuesto_id: null, antecedente_id:null, determinado: 0, programador_id: null, 
        retenedor:null, cambio_domicilio:null, domicilio_anterior:null, notificador:null, fecha_acta:null, origen_id:null, 
        representante_legal: null, estatus: 4,
      },
      impuestos_listado: [],
      antecedentes_listado:[],
      programadores_listado: [],
      oficinas_listado: [],
      municipios_listado:[],
      fuentes_listado:[],
      permiso:false,
    };
  },
  components:{
    FormCrearEditar,
    DialogAntecedente 
  },
  created() {
    this.obtenerPermisos();
    this.mostrar(),
    this.obtieneoficinas(),
    this.obtienefuentes(),
    this.obtieneimpuestos(),
    this.obtieneusuarios(),
    this.obtieneantecedentes(),
    this.obtienemunicipios()
  },
 methods: {
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
        this.$swal(
          '¡Hecho!',
          'El prospecto se ha cambiado al estatus pendiente.',
          'success'
        );
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
    exportarExcelConEstilo() {
      // Filtrar los prospectos para incluir solo aquellos con estatus 4
      const prospectosFiltrados = this.prospectosie.filter(p => Number(p.estatus) === 4);
      const cruceData = prospectosFiltrados.filter(p => Number(p.origen_id) === 0);
      const prospectoData = prospectosFiltrados.filter(p => Number(p.origen_id) === 1);

      const wb = XLSX.utils.book_new();

      // --- Estilos ---
      const border = {
        top: { style: 'thin', color: { rgb: "000000" } },
        bottom: { style: 'thin', color: { rgb: "000000" } },
        left: { style: 'thin', color: { rgb: "000000" } },
        right: { style: 'thin', color: { rgb: "000000" } }
      };
      const titleStyle = {
        font: { bold: true, sz: 18 },
        alignment: { horizontal: "center", vertical: "center" }
      };
      const subHeaderStyle = {
        font: { bold: true, sz: 11 },
        fill: { fgColor: { rgb: "D9D9D9" } },
        alignment: { horizontal: "center", vertical: "center", wrapText: true },
        border: border
      };
      const dataStyleLeft = {
        font: { sz: 11 },
        border: border
      };
      const dataStyleCenter = {
        ...dataStyleLeft,
        alignment: { horizontal: "center", vertical: "center" }
      };
      const dataStyleCurrency = {
        ...dataStyleCenter,
        numFmt: "$#,##0.00"
      };

      let tipoListado = 'CRUCE';

      if (prospectoData.length > 0 && cruceData.length === 0) {
        tipoListado = 'PROSPECTOS';
      } else if (cruceData.length > 0 && prospectoData.length > 0) {
        tipoListado = 'CRUCE Y PROSPECTOS';
      }
      let tipoOrden = 'VISITAS';
      const impuestos = prospectosFiltrados.map(p =>(p.impuesto || '').trim().toUpperCase());
      if (impuestos.every(i => i.startsWith('M'))) {
        tipoOrden = 'CARTAS';
      } else if (impuestos.every(i => i.startsWith('G'))) {
        tipoOrden = 'GABINETE';
      } else if (impuestos.every(i => i.startsWith('D'))) {
        tipoOrden = 'VISITAS';
      }
      // --- Hoja 1: CRUCE (con secciones CRUCE / PROSPECTO) ---
      const title = `LISTADO DE ${tipoListado} DE ORDENES DE ${tipoOrden} DE IMPUESTOS ESTATALES`;
      const headers = ["No.", "R.F.C.", "NOMBRE DEL CONTRIBUYENTE", "MÉTODO\nPROPUESTO", "PERIODO", "IMPUESTOS", "MUNICIPIO", "PRESUNTIVA", "REPRESENTANTE\nLEGAL"];

      // Generador de filas con estilos
      const generarFilas = (lista, startIndex = 1) => {
        return lista.map((item, index) => {
          const impuesto = (item.impuesto || '').trim().toUpperCase();
          let metodoPropuesto = '';
          if (impuesto.startsWith('M')) {
            metodoPropuesto = 'CARTA';
          } else if (impuesto.startsWith('G')) {
            metodoPropuesto = 'GABINETE';
          } else {
            metodoPropuesto = 'VISITA';
          }

          return [
            { v: startIndex + index, s: dataStyleCenter },
            { v: item.rfc || '', s: dataStyleLeft },
            { v: item.nombre || '', s: dataStyleLeft },
            { v: metodoPropuesto, s: dataStyleCenter },
            { v: item.periodos || '', s: dataStyleCenter },
            { v: item.impuesto || '', s: dataStyleCenter },
            { v: item.municipio_estado || '', s: dataStyleCenter },
            { v: Number(item.determinado) || 0, t: 'n', s: dataStyleCurrency },
            { v: item.representante_legal || '', s: dataStyleLeft }
          ];
        });
      };

      // Construir ws_data y registrar índices de filas de encabezado/ títulos para aplicar estilos/merges
      const ws_data = [];
      const headerRowIndexes = []; // filas donde están los headers (0-indexed)
      const sectionTitleRows = []; // filas con 'CRUCE' y 'PROSPECTO' (para merges)

      let currentRow = 0;

      // 0) Título general
      ws_data.push([{ v: title, s: titleStyle }]);
      const titleRow = currentRow;
      currentRow++;

      // ===== SECCIÓN CRUCE =====
      if (cruceData.length > 0) {
        //ws_data.push([{ v: 'CRUCE', s: titleStyle }]);
        //sectionTitleRows.push(currentRow);
        //currentRow++;

        ws_data.push(headers);
        headerRowIndexes.push(currentRow);
        currentRow++;

        const filasCruce = generarFilas(cruceData, 1);
        filasCruce.forEach(r => { ws_data.push(r); currentRow++; });
      }

      // fila en blanco separadora
      if (cruceData.length > 0 && prospectoData.length > 0) {
        ws_data.push([]);
        currentRow++;
      }

      // ===== SECCIÓN PROSPECTO =====
      if (prospectoData.length > 0) {
        //ws_data.push([{ v: 'PROSPECTO', s: titleStyle }]);
        //sectionTitleRows.push(currentRow);
        //currentRow++;

        ws_data.push(headers);
        headerRowIndexes.push(currentRow);
        currentRow++;

        const filasProspecto = generarFilas(prospectoData, 1);
        filasProspecto.forEach(r => { ws_data.push(r); currentRow++; });
      }

      // Crear hoja
      const ws1 = XLSX.utils.aoa_to_sheet(ws_data);

      // --- Merges: título general y títulos de sección (abarcan todas las columnas) ---
      if (!ws1['!merges']) ws1['!merges'] = [];
      const lastColIndex = headers.length - 1;
      // Título general
      ws1['!merges'].push({ s: { r: titleRow, c: 0 }, e: { r: titleRow, c: lastColIndex } });
      // Secciones
      sectionTitleRows.forEach(rIdx => {
        ws1['!merges'].push({ s: { r: rIdx, c: 0 }, e: { r: rIdx, c: lastColIndex } });
      });

      // --- Aplicar estilo a filas de encabezado (gris) ---
      headerRowIndexes.forEach(rowIdx => {
        for (let c = 0; c <= lastColIndex; c++) {
          const addr = XLSX.utils.encode_cell({ r: rowIdx, c });
          if (!ws1[addr]) {
            // si no existe, crear celda vacía con estilo
            ws1[addr] = { v: '', t: 's', s: subHeaderStyle };
          } else {
            ws1[addr].s = subHeaderStyle;
          }
        }
      });

      // --- Calcular anchos de columna basados en ws_data ---
      const colCount = headers.length;
      const colWidths = new Array(colCount).fill(0);
      ws_data.forEach(row => {
        for (let c = 0; c < colCount; c++) {
          const cell = row[c];
          let text = '';
          if (cell == null) text = '';
          else if (typeof cell === 'object' && cell.v !== undefined) text = String(cell.v);
          else text = String(cell);
          colWidths[c] = Math.max(colWidths[c], text.length);
        }
      });
      ws1['!cols'] = colWidths.map((w, i) => {
        // Columna No. (índice 0) → ancho fijo
        if (i === 0) {
          return { wch: 6 };
        }
        return { wch: Math.min(Math.max(w + 2, 10), 50) };
      });

      // Page setup horizontal
      ws1['!pageSetup'] = { orientation: 'landscape' };

      XLSX.utils.book_append_sheet(wb, ws1, "CRUCE");

      // --- Hoja 2: PRE-APROBADA / Agrupados (la conservé igual que la tuya) ---
      const getProspectoGroupKey = (prospecto) => {
        const fuente = prospecto.fuente_descripcion || '';
        const impuesto = prospecto.impuesto || '';
        const impuestoDesc = (prospecto.impuestos_descripcion || ' ').toUpperCase();
        const isCarta = impuesto.startsWith('M-');
        const isNoRegistrado = ['IMSS', 'Dario'].includes(fuente);
        const isRegistrado = fuente === 'SII';

        let tipo = isCarta ? 'CARTAS' : 'VISITAS';
        let registro = 'No Clasificado';

        if (isNoRegistrado) registro = 'No Registrados';
        else if (isRegistrado) registro = 'Registrados';

        return `${tipo.toUpperCase()} ${registro.toUpperCase()} ${impuestoDesc}`;
      };

      const prospectosAgrupados = prospectosFiltrados.reduce((acc, prospecto) => {
        const groupKey = getProspectoGroupKey(prospecto);
        if (!acc[groupKey]) acc[groupKey] = [];
        acc[groupKey].push(prospecto);
        return acc;
      }, {});

      const ws2 = XLSX.utils.aoa_to_sheet([]);
      let currentRow2 = 0;
      const headers2 = ["No.", "R.F.C.", "NOMBRE DEL CONTRIBUYENTE", "CALLE Y No.", "COLONIA", "C.P. Y MUNICIPIO", "PERIODO", "ANTECEDENTE", "PRESUNTIVA", "MUNICIPIO", "FUENTE", "PROGRAMADOR", "REPRESENTANTE LEGAL"];

      const sortedGroupKeys = Object.keys(prospectosAgrupados).sort((a, b) => {
        const aFirst = prospectosAgrupados[a] && prospectosAgrupados[a][0];
        const bFirst = prospectosAgrupados[b] && prospectosAgrupados[b][0];
        const idA = aFirst && aFirst.impuesto_id !== undefined ? Number(aFirst.impuesto_id) : Infinity;
        const idB = bFirst && bFirst.impuesto_id !== undefined ? Number(bFirst.impuesto_id) : Infinity;
        return idA - idB;
      });

      sortedGroupKeys.forEach(groupKey => {
        const group = prospectosAgrupados[groupKey];
        // título del grupo
        currentRow2++;
        XLSX.utils.sheet_add_aoa(ws2, [[{ v: groupKey, s: titleStyle }]], { origin: `A${currentRow2}` });
        if (!ws2['!merges']) ws2['!merges'] = [];
        ws2['!merges'].push({ s: { r: currentRow2 - 1, c: 0 }, e: { r: currentRow2 - 1, c: headers2.length - 1 } });
        currentRow2++;

        // encabezado
        XLSX.utils.sheet_add_aoa(ws2, [headers2], { origin: `A${currentRow2}` });
        for (let C = 0; C < headers2.length; C++) {
          const addr = XLSX.utils.encode_cell({ r: currentRow2 - 1, c: C });
          if (ws2[addr]) ws2[addr].s = subHeaderStyle;
        }
        currentRow2++;

        // filas
        const filas = group.map((item, idx) => ([
          { v: idx + 1, s: dataStyleCenter },
          { v: item.rfc || '', s: dataStyleLeft },
          { v: item.nombre || '', s: dataStyleLeft },
          { v: `${item.calle || ''} ${item.num_exterior || ''}`.trim(), s: dataStyleLeft },
          { v: item.colonia || '', s: dataStyleLeft },
          { v: `${item.cp || ''} ${item.localidad || ''} ${item.municipio_descripcion || ''} SINALOA`.trim(), s: dataStyleCenter },
          { v: item.periodos || '', s: dataStyleCenter },
          { v: item.antecedente_descripcion || '', s: dataStyleLeft },
          { v: Number(item.determinado) || 0, t: 'n', s: dataStyleCurrency },
          { v: `${item.localidad || ''} ${item.municipio_descripcion || ''} SINALOA`.trim(), s: dataStyleLeft },
          { v: item.fuente_descripcion || '', s: dataStyleLeft },
          { v: item.programador_descripcion || '', s: dataStyleLeft },
          { v: item.representante_legal || '', s: dataStyleLeft }
        ]));
        XLSX.utils.sheet_add_aoa(ws2, filas, { origin: `A${currentRow2}` });
        currentRow2 += filas.length;
        currentRow2++; // separador
      });

      // Col widths for ws2 (simple heuristic)
      const colWidths2 = headers2.map((h, ci) => {
        let max = (h || '').length;
        prospectosFiltrados.forEach(item => {
          let len = 0;
          switch (ci) {
            case 0: len = 5; break;
            case 1: len = (item.rfc || '').length; break;
            case 2: len = (item.nombre || '').length; break;
            case 3: len = (`${item.calle || ''} ${item.num_exterior || ''}`.trim()).length; break;
            case 4: len = (item.colonia || '').length; break;
            case 5: len = (`${item.cp || ''} ${item.localidad || ''} ${item.municipio_descripcion || ''} SINALOA`.trim()).length; break;
            case 6: len = (item.periodos || '').length; break;
            case 7: len = (item.antecedente_descripcion || '').length; break;
            case 8: len = (String(item.determinado) || '').length; break;
            case 9: len = (`${item.localidad || ''} ${item.municipio_descripcion || ''} SINALOA`.trim()).length; break;
            case 10: len = (item.fuente_descripcion || '').length; break;
            case 11: len = (item.programador_descripcion || '').length; break;
            case 12: len = (item.representante_legal || '').length; break;
            default: len = 0;
          }
          if (len > max) max = len;
        });
        return { wch: Math.min(Math.max(max + 2, 8), 60) };
      });
      ws2['!cols'] = colWidths2;
      ws2['!pageSetup'] = { orientation: 'landscape' };

      XLSX.utils.book_append_sheet(wb, ws2, "PRE-APROBADA");

      // Guardar archivo
      XLSX.writeFile(wb, "LISTADO DE CRUCE DE ORDENES DE IMPUESTOS_ESTATALES.xlsx");
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
    seleccionarProspecto: function (item) {
      Swal.fire({
        title: '¿Autorizar este prospecto?',
        text: "Esta acción marcará el prospecto autorizado por comité.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Autorizar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.post(crud, {
            opcion: 5, // Opción para actualizar solo el estatus
            id: item.id,
            estatus: 5
          }).then(response => {
            Swal.fire({
              title:'¡Autorizado!',
              text:'El prospecto ha sido autorizado.',
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
          }).catch(error => {
            Swal.fire({title:'Error', text:'No se pudo autorizar el prospecto.', icon:'error',
            showCancelButton: false,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false});
            console.error("Error al cambiar estatus:", error);
          });
        }
      });
    },
    prospectoPendiente: function (item) {
      this.prospectoSeleccionado = item;
      this.dialogAntecedente = true;
    },
    mostrar: function () {
      axios
        .post(crud, { opcion: 1, estatus_prospecto:4 })
        .then((response) => {
          if (Array.isArray(response.data)) {
            const datosOrdenados = response.data.sort((a, b) => {
              const impuestoA = a.impuesto || '';
              const impuestoB = b.impuesto || '';
              return impuestoA.localeCompare(impuestoB);
            });
            this.prospectosie = datosOrdenados;
            this.prospectosie_no_localizados = this.prospectosie
            .filter(item => Number(item.antecedente_id) === 7) 
            .map(item => ({ ...item }));
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
    limpiar: function(){
      this.prospectoie = { id: null, fecha_captura: null, rfc: null, nombre: null, calle: null, num_exterior: null, num_interior: null,
        colonia: null, cp: null, localidad: null, municipio_id:null, municipio: null, oficina_descripcion: null, oficina_id: null, 
        fuente_id:null, giro: null, periodos: null, impuesto_id: null, antecedente_id:null, determinado: 0, programador_id: null, 
        retenedor:null, cambio_domicilio: null, domicilio_anterior: null, notificador: null, fecha_acta: null, origen_id:null, 
        representante_legal: null, estatus: 4,
      };
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
            "No se pudo eliminar el prospecto",
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
      this.prospectoie.cambio_domicilio=objeto.cambio_domicilio;
      this.prospectoie.domicilio_anterior=objeto.domicilio_anterior;
      this.prospectoie.notificador=objeto.notificador;
      this.prospectoie.fecha_acta=this.convertirFecha(objeto.fecha_acta);
      this.prospectoie.origen_id=objeto.origen_id;
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
