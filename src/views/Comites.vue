<template>
  <v-container>
    <v-container class="my-2">
      <!-- Botón Crear y Exportar -->
      <v-row class="center">
        <v-spacer></v-spacer>
        <v-col cols="8"  class="text-center">
          <h1>PROSPECTOS ENVIADOS A COMITE</h1>
        </v-col>
        <v-spacer></v-spacer>
        <v-col cols="1" class="text-right">
            <v-btn color="pink darken-4" dark @click="salir()"><v-icon class="mr-3">mdi-exit-to-app</v-icon> Salir</v-btn>
        </v-col>
      </v-row>
      <v-row class="mb-4">
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
  </v-container>
</template>

<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import * as XLSX from 'xlsx-js-style';
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

export default {
  name: "Comites",
  data() {
    return {
      busca: "",
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
        programador_id: null, retenedor:null, origen_id:null, representante_legal: null, estatus: 4,
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
    exportarExcelConEstilo() {      
      // Filtrar los prospectos para incluir solo aquellos con estatus 4
      const prospectosFiltrados = this.prospectosie.filter(p => Number(p.estatus) === 4);

      const wb = XLSX.utils.book_new();

      // --- Hoja 1: CRUCE ---
      // 1.a. Definir los estilos para la primera hoja
      const border = {
        top: { style: 'thin', color: { rgb: "000000" } },
        bottom: { style: 'thin', color: { rgb: "000000" } },
        left: { style: 'thin', color: { rgb: "000000" } },
        right: { style: 'thin', color: { rgb: "000000" } }
      };
      const subHeaderStyleborder = {
        top: { style: 'thick', color: { rgb: "000000" } },
        bottom: { style: 'thick', color: { rgb: "000000" } },
        left: { style: 'thick', color: { rgb: "000000" } },
        right: { style: 'thick', color: { rgb: "000000" } }
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

      // 1.b. Preparar los datos y encabezados para la primera hoja
      const title = "LISTADO DE CRUCE DE ORDENES DE VISITAS DE IMPUESTOS ESTATALES";
      const headers = ["No.", "R.F.C.", "NOMBRE DEL CONTRIBUYENTE", "MÉTODO\nPROPUESTO", "PERIODO", "IMPUESTOS", "MUNICIPIO", "PRESUNTIVA", "REPRESENTANTE\nLEGAL"];
      
      const data = prospectosFiltrados.map((item, index) => {
        const impuesto = item.impuesto || '';
        const metodoPropuesto = !impuesto.startsWith('M-') ? 'VISITA' : 'CARTA';
        return [ // Se cambia 'const row =' por 'return' para asegurar que siempre se devuelva un array
          { v: index + 1, s: dataStyleCenter },
          { v: item.rfc, s: dataStyleLeft },
          { v: item.nombre, s: dataStyleLeft },
          { v: metodoPropuesto, s: dataStyleCenter }, // Se usa la variable correcta
          { v: item.periodos, s: dataStyleCenter },
          { v: item.impuesto, s: dataStyleCenter },
          { v: item.municipio_descripcion || item.localidad, s: dataStyleCenter },
          { v: Number(item.determinado), t: 'n', s: dataStyleCurrency },
          { v: item.representante_legal, s: dataStyleLeft }
        ];
      });

      // 1.c. Crear la hoja de cálculo con el título y encabezados
      const ws_data = [
        [{v: title, s: titleStyle}],
        headers,
        ...data
      ];
      const ws1 = XLSX.utils.aoa_to_sheet(ws_data);

      if (!ws1['!merges']) ws1['!merges'] = [];
      ws1['!merges'].push({ s: { r: 0, c: 0 }, e: { r: 0, c: 8 } });

      const range = XLSX.utils.decode_range(ws1['!ref']);
      for (let C = range.s.c; C <= range.e.c; ++C) {
        const cell_address = XLSX.utils.encode_cell({ r: 1, c: C });
        if (ws1[cell_address]) ws1[cell_address].s = subHeaderStyle;
      }

      const colWidths = headers.map((header, i) => {
        let max = header.length;
        data.forEach(row => {
          const cellValue = row[i].v;
          if (cellValue != null) {
            max = Math.max(max, String(cellValue).length);
          }
        });
        return { wch: max + 2 }; // Añadir un pequeño padding
      });
      ws1['!cols'] = colWidths;

      XLSX.utils.book_append_sheet(wb, ws1, "CRUCE");

      // --- Hoja 2: PROSPECTOS (Agrupados) ---
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
        if (!acc[groupKey]) {
          acc[groupKey] = [];
        }
        acc[groupKey].push(prospecto);
        return acc;
      }, {});

      const ws2 = XLSX.utils.aoa_to_sheet([]);
      let currentRow = 0;

      const headers2 = ["No.", "R.F.C.", "NOMBRE DEL CONTRIBUYENTE", "CALLE Y No.", "COLONIA", "C.P. Y MUNICIPIO", "PERIODO", "ANTECEDENTE", "PRESUNTIVA", "MUNICIPIO", "FUENTE", "PROGRAMADOR", "REPRESENTANTE LEGAL"];
      // Ordenar las claves de grupo (los títulos) basándose en el impuesto_id del primer elemento de cada grupo.
      const sortedGroupKeys = Object.keys(prospectosAgrupados).sort((keyA, keyB) => {
        let firstItemA = undefined;
        let firstItemB = undefined;

        // Asegurarse de que el grupo existe, es un array y no está vacío antes de intentar acceder a su primer elemento
        if (prospectosAgrupados[keyA] && Array.isArray(prospectosAgrupados[keyA]) && prospectosAgrupados[keyA].length > 0) {
          firstItemA = prospectosAgrupados[keyA][0];
        }
        if (prospectosAgrupados[keyB] && Array.isArray(prospectosAgrupados[keyB]) && prospectosAgrupados[keyB].length > 0) {
          firstItemB = prospectosAgrupados[keyB][0];
        }

        const idA = (firstItemA && firstItemA.impuesto_id !== undefined) ? Number(firstItemA.impuesto_id) : Infinity;
        const idB = (firstItemB && firstItemB.impuesto_id !== undefined) ? Number(firstItemB.impuesto_id) : Infinity;

        return idA - idB;
      });

      sortedGroupKeys.forEach(groupKey => {
        const prospectosDelGrupo = prospectosAgrupados[groupKey];

        // 1. Agregar Título del Grupo (una sola línea)
        currentRow++;
        const groupTitle = [{ v: groupKey, s: titleStyle }];
        XLSX.utils.sheet_add_aoa(ws2, [groupTitle], { origin: `A${currentRow + 1}` });
        if (!ws2['!merges']) ws2['!merges'] = [];
        ws2['!merges'].push({ s: { r: currentRow, c: 0 }, e: { r: currentRow, c: headers2.length - 1 } });
        currentRow++;

        // 2. Agregar Encabezados de la tabla
        XLSX.utils.sheet_add_aoa(ws2, [headers2], { origin: `A${currentRow + 1}` });
        for (let C = 0; C < headers2.length; C++) {
          const cell_address = XLSX.utils.encode_cell({ r: currentRow, c: C });
          if (ws2[cell_address]) ws2[cell_address].s = subHeaderStyle;
        }
        currentRow++;

        // 3. Agregar datos del grupo
        const dataGrupo = prospectosDelGrupo.map((item, index) => [
          { v: index + 1, s: dataStyleCenter },
          { v: item.rfc, s: dataStyleLeft },
          { v: item.nombre, s: dataStyleLeft },
          { v: `${item.calle || ''} ${item.num_exterior || ''}`.trim(), s: dataStyleLeft },
          { v: item.colonia, s: dataStyleLeft },
          { v: `${item.cp || ''} ${item.localidad || ''} ${item.municipio_descripcion || ''} ${'SINALOA' || ''}`.trim(), s: dataStyleCenter },
          { v: item.periodos, s: dataStyleCenter },
          { v: item.antecedente_descripcion, s: dataStyleLeft },
          { v: Number(item.determinado), t: 'n', s: dataStyleCurrency },
          { v: `${item.localidad || ''} ${item.municipio_descripcion || ''} ${'SINALOA' || ''}`.trim(), s: dataStyleLeft },
          { v: item.fuente_descripcion, s: dataStyleLeft },
          { v: item.fuente_descripcion, s: dataStyleLeft },
          { v: item.programador_descripcion, s: dataStyleLeft },
          { v: item.representante_legal, s: dataStyleLeft }
        ]);

        XLSX.utils.sheet_add_aoa(ws2, dataGrupo, { origin: `A${currentRow + 1}` });
        currentRow += dataGrupo.length;
        currentRow++; // Fila en blanco para separar tablas
      });

      // Lógica simplificada y corregida para calcular el ancho de las columnas
      const colWidths2 = headers2.map((header, colIndex) => {
        const headerWidth = (header || '').length;
        const dataWidths = prospectosFiltrados.map(item => {
            switch (colIndex) {
                case 0: return 5; // No.
                case 1: return (item.rfc || '').length;
                case 2: return (item.nombre || '').length;
                case 3: return (`${item.calle || ''} ${item.num_exterior || ''}`.trim()).length;
                case 4: return (item.colonia || '').length;
                case 5: return (`${item.cp || ''} ${item.localidad || ''} ${item.municipio_descripcion || ''} SINALOA`.trim()).length;
                case 6: return (item.periodos || '').length;
                case 7: return (item.antecedente_descripcion || '').length;
                case 8: return (String(item.determinado) || '').length;
                case 9: return (`${item.localidad || ''} ${item.municipio_descripcion || ''} SINALOA`.trim()).length;
                case 10: return (item.fuente_descripcion || '').length;
                case 11: return (item.programador_descripcion || '').length;
                case 12: return (item.representante_legal || '').length;
                default: return 0;
            }
        });
        const maxWidth = Math.max(headerWidth, ...dataWidths);
        return { wch: maxWidth + 2 };
      });
      ws2['!cols'] = colWidths2;

      XLSX.utils.book_append_sheet(wb, ws2, "PRE-APROBADA");

      XLSX.writeFile(wb, "LISTADO DE CRUCE DE ORDENES DE IMPUESTOS ESTATALES.xlsx");
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
            Swal.fire(
              '¡Autorizado!',
              'El prospecto ha sido autorizado.',
              'success'
            );
            this.mostrar(); // Recargar la tabla para reflejar el cambio
          }).catch(error => {
            Swal.fire('Error', 'No se pudo autorizar el prospecto.', 'error');
            console.error("Error al cambiar estatus:", error);
          });
        }
      });
    },
    prospectoPendiente: function (item) {
      Swal.fire({
        title: '¿Marcar como no autorizado?',
        text: "Esta acción cambiará el estatus del prospecto a pendiente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.post(crud, {
            opcion: 5, // Opción para actualizar solo el estatus
            id: item.id,
            estatus: 7
          }).then(response => {
            Swal.fire('¡Hecho!', 'El prospecto ha sido marcado como pendiente.', 'success');
            this.mostrar(); // Recargar la tabla para reflejar el cambio
          }).catch(error => {
            Swal.fire('Error', 'No se pudo actualizar el prospecto.', 'error');
            console.error("Error al cambiar estatus:", error);
          });
        }
      });
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
        colonia: null, cp: null, localidad: null, municipio_id:null, municipio: null, oficina_descripcion: null,
        oficina_id: null, fuente_id:null, giro: null, periodos: null, impuesto_id: null, antecedente_id:null, determinado: 0,
        programador_id: null, retenedor:null, origen_id:null, representante_legal: null, estatus: 4,
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
