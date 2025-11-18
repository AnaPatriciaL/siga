<template>
  <v-container>
    <v-container class="my-2">

            <!-- Botón Crear y Exportar -->
            <v-row class="center">
              <v-spacer></v-spacer>
              <v-col cols="8"  class="text-center">
                <h1>PROSPECTOS IMPUESTOS ESTATALES</h1>
              </v-col>
              <v-spacer></v-spacer>
              <v-col cols="1" class="text-right">
                  <v-btn color="pink darken-4" dark @click="salir()">
                    <v-icon class="mr-3">mdi-exit-to-app</v-icon> Salir
                  </v-btn>
              </v-col>
            </v-row>

            <v-row class="mb-4">
              <!-- Boton exportar Excel -->
              <vue-excel-xlsx v-if="permiso"
                :data="lista_prospectosie"
                :columns="columnas"
                :file-name="'Prospectos IE'"
                :file-type="'xlsx'"
                :sheet-name="'ProspectosIE'"
                >
                <v-tooltip top color="green darken-3">
                  <template v-slot:activator="{ on, attrs }">
                    <v-btn fab class="green ml-3 mt-2" dark v-bind="attrs" v-on="on">
                      <v-icon large>mdi-microsoft-excel</v-icon>
                    </v-btn>
                  </template>
                  <span>Exportar a Excel</span>
                </v-tooltip>
              </vue-excel-xlsx>
              <!-- Boton recargar  -->
              <v-tooltip right color="light-blue darken-4">
                <template v-slot:activator="{ on, attrs }">
                  <v-btn class="mt-2 ml-3"
                    color="light-blue darken-4" fab dark @click="mostrar()"
                    v-bind="attrs"
                    v-on="on"
                  >
                    <v-icon large>mdi-refresh</v-icon>
                  </v-btn>
                </template>
                <span>Recargar información</span>
              </v-tooltip>              
              <v-spacer></v-spacer>
              <v-col COL="6">
                <v-text-field
                  v-model="busca"
                  append-icon="mdi-magnify"
                  label="Buscar"
                  single-line
                  hide-details
                ></v-text-field>
              </v-col>
            </v-row>

            <!-- Tabla y formulario -->
            <v-data-table
              :headers="encabezados"
              :items="lista_prospectosie"
              item-key="id"
              class="elevation-1"
              :search="busca"
            >
              <template v-slot:item.tipo="{ item }">
                <v-icon v-if="item.antecedente_id==6"
                  large
                  class="mr-2"
                  color="blue-grey darken-2"
                  dark
                  dense
                >
                  mdi-cog-sync
                </v-icon>
                <v-icon v-if="item.antecedente_id==7"
                  large
                  class="mr-2"
                  color="pink accent-3"
                  dark
                  dense
                >
                  mdi-map-marker-remove
                </v-icon>
                <v-icon v-else
                  large
                  class="mr-2"
                  color="teal accent-4"
                  dark
                  dense
                >
                  mdi-progress-check
                </v-icon>
              </template>
              <!-- Acciones -->
              <template v-slot:item.actions="{ item }">
                <!-- Icono Editar en el data-table -->
                <v-icon
                  large
                  class="mr-2"
                  color="amber"
                  dark
                  dense
                  alt="Editar"
                  @click="formEditar(item)"
                >
                  mdi-pencil
                </v-icon>
                
              </template>

            </v-data-table>
          </v-container>

          <!-- Componente de Diálogo para CREAR y EDITAR -->
          <v-dialog v-model="dialog" max-width="1100px" persistent >
              <v-card>
                <v-form>
                  <v-card-title class="pink darken-4 white--text py-2"
                    >PROSPECTO DE IMPUESTOS ESTATALES</v-card-title
                  >
                  <v-card-text class="mb-2 py-0">
                    <v-container>
                      <v-row class="my-2 pt-4">
                       
                        <!-- RFC -->
                        <v-col class="my-0 py-0" cols="12" md="3">
                          <v-text-field
                            maxlength="13"
														minlength="12"
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.rfc"
                            label="RFC"
                            outlined
                            :readonly="operacion=='editar'"
                            dense
														ref="rfc"
                            >
                            <!-- autocapitalize="words" -->
                            {{prospectoie.rfc}}
                          </v-text-field>
                        </v-col>

                        <!-- Boton buscar -->
                        <v-col class="my-0 py-0" cols="12" md="1">
                          <v-btn 
                            dense 
                            color="orange" 
                            dark
                            @click="datos_contribuyentes">
                            <v-icon>
                              mdi-database-search-outline
                            </v-icon>
                          </v-btn>
                         
                        </v-col>

                        <!-- Nombre -->
                        <v-col class="my-0 py-0" cols="12" md="8">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.nombre"
                            label="Nombre"
                            outlined
                            maxlength="300"
                            dense
                          >
                            {{prospectoie.nombre}}
                          </v-text-field>
                        </v-col>

                        <!-- Calle -->
                        <v-col class="my-0 py-0" cols="12" md="4">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.calle"
                            label="Calle/Avenida/Vialidad"
                            maxlength="250"
                            outlined
                            dense
                          >
                            {{prospectoie.calle}}
                          </v-text-field>
                        </v-col>
                        <!-- Numero exterior -->
                        <v-col class="my-0 py-0" cols="12" md="2">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.num_exterior"
                            label="No. exterior"
                            maxlength="250"
                            outlined
                            dense
                          >
                            {{prospectoie.num_exterior}}
                          </v-text-field>
                        </v-col>
                        <!-- Numero interior -->
                        <v-col class="my-0 py-0" cols="12" md="2">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.num_interior"
                            label="No. interior"
                            maxlength="250"
                            outlined
                            dense
                          >
                            {{prospectoie.num_interior}}
                          </v-text-field>
                        </v-col>

                        <!-- Colonia -->
                        <v-col class="my-0 py-0" cols="12" md="4">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.colonia"
                            label="Colonia"
                            maxlength="150"
                            outlined
                            dense
                          >
                            {{prospectoie.colonia}}
                          </v-text-field>
                        </v-col>

                        <!-- CP -->
                        <v-col class="my-0 py-0" cols="12" md="2">
                          <v-text-field
                            class="my-0 py-0"
                            v-model="prospectoie.cp"
                            label="C.P."
                            outlined
                            maxlength="5"
                            minlength="5"
                            dense
                            type="number"
                          >
                            {{prospectoie.cp}}
                          </v-text-field>
                        </v-col>

                        <!-- Localidad -->
                        <v-col class="my-0 py-0" cols="12" md="4">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.localidad"
                            label="Localidad"
                            maxlength="100"
                            outlined
                            dense
                          >
                            {{prospectoie.localidad}}
                          </v-text-field>
                        </v-col>
                        <!-- Municipio -->
                        <v-col class="my-0 py-0" cols="12" md="3">
                          <v-select
                            :items="oficinas_listado"
                            v-model="prospectoie.municipio"
                            label="Municipio"
                            outlined
                            dense
                            required
                            item-text="nombre"
                            item-value="id"
                          >
                          </v-select>
                        </v-col>
                        <!-- Oficina -->
                        <v-col class="my-0 py-0" cols="12" md="3">
                          <v-select
                            :items="oficinas_listado"
                            v-model="prospectoie.oficina_id"
                            label="Oficina"
                            outlined
                            dense
                            required
                            item-text="nombre"
                            item-value="id"
                          >
                          </v-select>
                        </v-col>
                        <!-- Giro -->
                        <v-col class="my-0 py-0" cols="12" md="12">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.giro"
                            label="Giro/Actividad"
                            item-text=""
                            outlined
                            maxlength="250"
                            dense
                          >
                            {{prospectoie.giro}}
                          </v-text-field>
                        </v-col>
                        <!-- Periodo -->
                        <!-- Campo para la Fecha de Inicio -->
                        <v-col cols="12" md="2">
                          <v-text-field
                            v-model="fechaInicio"
                            v-maska="'##/##/##'"
                            :rules="[rules.dateFormat]"
                            label="Fecha Inicio"
                            outlined
                            dense
                            maxlength="8"
                            placeholder="DD/MM/YY"
                          ></v-text-field>
                        </v-col>

                        <!-- Separador visual -->
                        <v-col cols="12" md="1" class="d-flex align-center justify-center">
                          <span>AL</span>
                        </v-col>

                        <!-- Campo para la fecha de fin -->
                        <v-col cols="12" md="2">
                          <v-text-field
                            v-model="fechaFin"
                            v-maska="'##/##/##'"
                            :rules="[rules.dateFormat]"
                            label="Fecha Fin"
                            outlined
                            dense
                            maxlength="8"
                            placeholder="DD/MM/YY"
                          ></v-text-field>
                        </v-col>                   
                        <!-- Antecedentes -->
                        <v-col class="my-0 py-0" cols="12" md="4">
                          <v-select
                            :items="antecedentes_listado"
                            v-model="prospectoie.antecedente_id"
                            label="Antecedente"
                            outlined
                            dense
                            required
                            item-text="descripcion"
                            item-value="id"
                          >
                          </v-select> 
                        </v-col>
                        <!-- Impuesto -->
                        <v-col class="my-0 py-0" cols="12" md="3">
                          <v-select
                            :items="impuestos_listado"
                            v-model="prospectoie.impuesto_id"
                            label="Impuesto"
                            outlined
                            dense
                            required
                            item-text="impuesto"
                            item-value="id"
                          >
                          </v-select> 
                        </v-col>

                        <!-- Presuntiva -->
                        <v-col class="my-0 py-0" cols="12" md="3">
                          <v-text-field
                            class="my-0 py-0"
                            label="Presuntiva/Determinado"
                            v-model="prospectoie.determinado"
                            item-text=""
                            outlined
                            hide-spin-buttons
                            suffix="$"
                            maxlength="12"
                            reverse
                            type="number"
                            dense
                          >
                            {{prospectoie.determinado}}
                          </v-text-field>
                        </v-col>
                        <!-- Fecha -->
                        <v-col class="my-0 py-0" cols="12" md="2">
                          <v-text-field
                            reverse
                            readonly
                            disabled
                            dense
                            outlined
                            maxlength="10"
                            v-model="prospectoie.fecha_captura"
                            label="Fecha captura"
                          ></v-text-field>
                        </v-col>

                        <!-- Usuario -->
                        <v-col class="my-0 py-0" cols="12" md="4">
                          <v-select
                            :items="programadores_listado"
                            v-model="prospectoie.programador_id"
                            label="Programador"
                            outlined
                            dense
                            required
                            item-text="usuario"
                            item-value="id"
                            ref="programador"
                          >
                          </v-select>
                        </v-col>
                        <!-- Fuente -->
                        <v-col class="my-0 py-0" cols="12" md="3">
                          <v-select
                            :items="fuentes_listado"
                            v-model="prospectoie.fuente_id"
                            label="Fuente"
                            outlined
                            dense
                            required
                            item-text="nombre"
                            item-value="id"
                          >
                          </v-select> 
                        </v-col>
                        <!-- Retenedor -->
                        <v-col class="my-0 py-0" cols="12" md="2">
                          <v-switch
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.retenedor"
                            inset
                            label="Retenedor">
                            {{prospectoie.retenedor}}
                          </v-switch>
                        </v-col>
                        <!-- Representante Legal -->
                        <v-col class="my-0 py-0" cols="12" md="10">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.representante_legal"
                            label="Representante Legal"
                            item-text=""
                            outlined
                            maxlength="200"
                            dense
                          >
                            {{prospectoie.representante_legal}}
                          </v-text-field>
                        </v-col>
                        <!-- Origen -->
                        <v-col class="my-0 py-0" cols="12" md="2">
                          <v-switch
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.origen"
                            inset
                            :label="prospectoie.origen ? 'Origen: Cruce' : 'Origen: Prospecto'">
                             {{prospectoie.origen}}
                          ></v-switch>
                        </v-col>
                        <!-- Observaciones -->
                        <v-col class="my-0 py-0" cols="12" md="10">
                          <v-text-field
                            class="my-0 py-0 mayusculas"
                            v-model="prospectoie.observaciones"
                            label="Observaciones"
                            item-text=""
                            outlined
                            maxlength="200"
                            dense
                          >
                            {{prospectoie.observaciones}}
                          </v-text-field>
                        </v-col>

                      </v-row>
                    </v-container>
                  </v-card-text>
                  <v-divider></v-divider>
                  <v-card-actions class="grey lighten-2 py-2">
                    <v-spacer></v-spacer>
                    <v-btn
                      class="my-1 ma-2 py-1"
                      color="blue-grey"
                      @click="validar_supervisor()"
                      dark
                      >
                      <!-- type="submit" -->
                      Enviar a supervisor
                      <v-icon dark right> mdi-account-tie-hat</v-icon>
                    </v-btn>
                    <v-btn
                      class="my-1 ma-2 py-1"
                      color="success"
                      @click="validar()"
                      dark
                      >
                      Guardar
                      <v-icon dark right> mdi-checkbox-marked-circle </v-icon>
                    </v-btn>
                    <v-btn class="ma-2" dark @click="dialog=false">
                      Cancelar
                      <v-icon dark left> mdi-cancel </v-icon>
                    </v-btn>
                  </v-card-actions>
                </v-form>
                <!-- </v-container> -->
              </v-card>

          </v-dialog>
					<!-- Cargando -->
					<v-dialog v-model="cargando" 	max-width="290" persistent  >

						<v-card
							color="pink darken-4"
							dark
						>
							<v-card-text class="pt-3">
								Buscando información
								<v-progress-linear
									indeterminate
									color="white"
									class="my-3"
								></v-progress-linear>
							</v-card-text>
						</v-card>
					</v-dialog>
  </v-container>
</template>

<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import VueExcelXlsx from "vue-excel-xlsx";

  // var crud = "./backend/crud_prospectosie.php";
  var crud = "http://10.10.120.228/siga/backend/crud_prospectosie.php";
  var urloficinas = "http://10.10.120.228/siga/backend/oficinas_listado.php";
  var urlfuentes = "http://10.10.120.228/siga/backend/fuentes_listado.php";
  var urlprogramadores = "http://10.10.120.228/siga/backend/programadores_listado.php";
  var urlimpuestos = "http://10.10.120.228/siga/backend/impuestos_listado.php";
  var urlantecedentes = "http://10.10.120.228/siga/backend/antecedentes_listado.php";
  var urlpadron = "http://10.10.120.228/siga/backend/padron_contribuyentes.php";
  var urlgenerar_antecedente ="http://10.10.120.228/siga/backend/generar_antecedente.php";

export default {
  name: "ComitesIE",
  data() {
    return {
      cargando:false,
      busca: "",
      rules: {
        dateFormat: value => {
          // Expresión regular para el formato DD/MM/YY (ej. 01/12/24)
          const pattern = /^(0[1-9]|[12]\d|3[01])\/(0[1-9]|1[0-2])\/\d{2}$/;
          
          // Verificar si el valor cumple con la longitud y el patrón
          if (value && value.length === 8 && pattern.test(value)) {
            // Opcional: Validar que sea una fecha real (ej. no 31/02/24)
            return true; 
          }
          
          return 'Formato incorrecto (DD/MM/YY).'; // Mensaje de error
        },
      },
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
      lista_prospectosie: [],
      prospectosie_no_localizados: [],
      dialog: false,
      operacion: "",
      prospectoie: {
        id: null,
        fecha_captura: null,
        rfc: null,
        nombre: null,
        calle: null,
        num_exterior: null,
        num_interior: null,
        colonia: null,
        cp: null,
        localidad: null,
        oficina_id: null,
        fuente_id:null,
        giro: null,
        periodos: null,
        impuesto_id: null,
        antecedente_id:null,
        determinado: 0,
        programador_id: null,
        retenedor:0,
        representante_legal: null,
        estatus: 1,
      },
      date: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
      menufechaorden: false,
      impuestos_listado: [],
      antecedentes_listado:[],
      programadores_listado: [],
      oficinas_listado: [],
      fuentes_listado:[],
      permiso:false,
      fechaInicio: '',
      fechaFin: ''
            // }
    };
  },
  computed: {
    // Propiedad computada que observa cambios en fechaInicio y fechaFin
    // y actualiza prospectoie.periodos
    periodoCompleto() {
      // Concatenar los valores con " AL " en medio si ambos campos tienen datos
      if (this.fechaInicio && this.fechaFin) {
        return `${this.fechaInicio} AL ${this.fechaFin}`;
      }
      return ''; // O maneja el caso donde uno o ambos están vacíos
    }
  },
  watch: {
    // Observador para asignar el valor de la propiedad computada al modelo final
    periodoCompleto(newVal) {
      this.lista_prospectosie.periodos = newVal;
    }
  },
  created() {
    this.obtenerPermisos();
    this.mostrarLista(),
    this.obtieneoficinas(),
    this.obtienefuentes(),
    this.obtieneimpuestos(),
    this.obtieneusuarios(),
    this.obtieneantecedentes()
  },
  methods: {
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

    mostrarLista: function () {
      axios
        .post(crud, { opcion: 1, estatus_prospecto: 4 })
        .then((response) => {
          if (Array.isArray(response.data)) {
            this.lista_prospectosie = response.data;

            // Filtrar los registros con antecedente_id = 7
            this.prospectosie_no_localizados = this.lista_prospectosie
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
    datos_contribuyentes: function () {
      // Validaciones
      if (this.prospectoie.rfc===null || this.prospectoie.rfc==="") {
        Swal.fire({  
          position: 'center',  
          icon: 'error',  
          titleText: 'Oops...',
          text:'El RFC no debe estar vacio',
          allowOutsideClick: false, // Evita que el usuario cierre el cuadro de diálogo haciendo clic fuera de él
          showConfirmButton: false,  
          timerProgressBar: true,
          timer: 1800})
          this.$refs.rfc.focus();
        return
      }
      if (this.prospectoie.rfc.length<12 || this.prospectoie.rfc.length>13) {
        Swal.fire({  
          position: 'center',  
          icon: 'error',  
          titleText:'Oops...',
          text: 'El RFC debe tener 12 digitos minimo y maximo 13',
          allowOutsideClick: false, // Evita que el usuario cierre el cuadro de diálogo haciendo clic fuera de él
          showConfirmButton: false,  
          timerProgressBar: true,
          timer: 1800})
          this.$refs.rfc.focus();
         return
      }
      this.cargando=true;
      
      axios.post( urlpadron, { rfc: this.prospectoie.rfc })
        .then((response) => {
          this.cargando=false;
          if (response.data.length==0) {
              Swal.fire({  
                position: 'center',  
                icon: 'info',  
                text: 'No se encontro información del RFC: '+this.prospectoie.rfc,
                titleText:'',
                showConfirmButton: false,  
                timerProgressBar: true,
                timer: 1800})
                this.$refs.rfc.focus();
              return 
          }
          // limpiamos los campos a llenar
          this.prospectoie.nombre=null;
          this.prospectoie.calle=null;
          this.prospectoie.num_exterior=null;
          this.prospectoie.num_interior=null;
          this.prospectoie.colonia=null;
          this.prospectoie.cp=null;
          this.prospectoie.localidad=null;
          this.prospectoie.giro=null;
          this.prospectoie.nombre=response.data[0].nombre;
          this.prospectoie.calle=response.data[0].calle;
          this.prospectoie.num_exterior=response.data[0].num_exterior;
          this.prospectoie.num_interior=response.data[0].num_interior;
          this.prospectoie.colonia=response.data[0].colonia;
          this.prospectoie.cp=response.data[0].cp;
          this.prospectoie.giro=response.data[0].giro;
          this.prospectoie.localidad=response.data[0].localidad;
          this.prospectoie.retenedor= false;
          this.prospectoie.estatus= 1;
      })
      .catch(e => {
        console.log(e);
        // Capturamos los errores
      });
    },
    async validar() {
      var errores=0;
      var textoMostrar='';
      // Validaciones
      if (this.prospectoie.rfc===null || this.prospectoie.rfc==="") {
        textoMostrar='El RFC no debe estar vacio';
        errores=1;
      }
      if (this.prospectoie && this.prospectoie.rfc && (this.prospectoie.rfc.length < 12 || this.prospectoie.rfc.length > 13)) {
          textoMostrar = 'El RFC debe tener entre 12 y 13 caracteres';
          errores = 2;
      }
      if ((this.prospectoie.fuente_id===null || this.prospectoie.fuente_id==="") || 
        this.prospectoie.rfc.length===12 && (this.prospectoie.representante_legal===null || this.prospectoie.representante_legal==="") ||
        this.prospectoie.nombre===null || this.prospectoie.nombre==="" ||
        this.prospectoie.calle===null || this.prospectoie.calle==="" ||
        this.prospectoie.num_exterior===null || this.prospectoie.num_exterior==="" ||
        this.prospectoie.num_interior===null || this.prospectoie.num_interior==="" ||
        this.prospectoie.colonia===null || this.prospectoie.colonia==="" ||
        this.prospectoie.localidad===null || this.prospectoie.localidad==="" ||
        this.prospectoie.oficina_id===null || this.prospectoie.oficina_id==="" ||
        this.prospectoie.periodos===null || this.prospectoie.periodos==="" ||
        this.prospectoie.antecedente_id===null || this.prospectoie.antecedente_id==="" ||
        this.prospectoie.giro===null || this.prospectoie.giro==="" ||
        this.prospectoie.impuesto_id===null || this.prospectoie.impuesto_id==="" ||
        this.prospectoie.determinado===null || this.prospectoie.determinado==="" ||
        this.prospectoie.programador_id===null
      ) {
        textoMostrar='Es necesario llenar toda la información';
        errores=3;
      }
      if (errores>0) {
        await Swal.fire({  
          position: 'center',  
          icon: 'error',  
          titleText: 'Datos incompletos',
          text: textoMostrar,
          allowOutsideClick: false, // Evita que el usuario cierre el cuadro de diálogo haciendo clic fuera de él
          showConfirmButton: false,  
          timerProgressBar: true,
          timer: 1800})
          this.$refs.rfc.focus();
          return;
      }else{
        console.log('guardar');
        this.guardar();
      }
    },
    async validar_supervisor() {
      
      this.prospectoie.estatus = 2;
      await this.validar();
    },
    crear() {
      let nombre = this.prospectoie.nombre != null && this.prospectoie.nombre !== '' ? this.prospectoie.nombre.toUpperCase() : this.prospectoie.nombre;
      let calle = this.prospectoie.calle != null && this.prospectoie.calle !== '' ? this.prospectoie.calle.toUpperCase() : this.prospectoie.calle;
      let num_exterior = this.prospectoie.num_exterior != null && this.prospectoie.num_exterior !== '' ? this.prospectoie.num_exterior.toUpperCase() : this.prospectoie.num_exterior;
      let num_interior = this.prospectoie.num_interior != null && this.prospectoie.num_interior !== '' ? this.prospectoie.num_interior.toUpperCase() : this.prospectoie.num_interior;
      let colonia = this.prospectoie.colonia != null && this.prospectoie.colonia !== '' ? this.prospectoie.colonia.toUpperCase() : this.prospectoie.colonia;
      let localidad = this.prospectoie.localidad != null && this.prospectoie.localidad !== '' ? this.prospectoie.localidad.toUpperCase() : this.prospectoie.localidad;
      let giro = this.prospectoie.giro != null && this.prospectoie.giro !== '' ? this.prospectoie.giro.toUpperCase() : this.prospectoie.giro;
      let periodos = this.prospectoie.periodos != null && this.prospectoie.periodos !== '' ? this.prospectoie.periodos.toUpperCase() : this.prospectoie.periodos;
      let representante_legal = this.prospectoie.representante_legal != null && this.prospectoie.representante_legal !== '' ? this.prospectoie.representante_legal.toUpperCase() : this.prospectoie.representante_legal;
      let observaciones = this.prospectoie.observaciones != null && this.prospectoie.observaciones !== '' ? this.prospectoie.observaciones.toUpperCase() : this.prospectoie.observaciones;
      axios.post(crud, 
            {
              // Nuevo
              opcion:2, 
              // Campos a guardar
              rfc:this.prospectoie.rfc.toUpperCase(),
              nombre:nombre,
              calle:calle,
              num_exterior:num_exterior,
              num_interior:num_interior,
              colonia:colonia,
              cp:this.prospectoie.cp,
              localidad:localidad,
              giro:giro,
              oficina_id:this.prospectoie.oficina_id,
              fuente_id:this.prospectoie.fuente_id,
              giro: this.prospectoie.giro,
              periodos:periodos,
              antecedente_id:this.prospectoie.antecedente_id,
              impuesto_id:this.prospectoie.impuesto_id,
              determinado:this.prospectoie.determinado,
              programador_id:this.prospectoie.programador_id,
              retenedor:this.prospectoie.retenedor,
              representante_legal:representante_legal,
              observaciones:observaciones,
              estatus:this.prospectoie.estatus
      })
      .then(response =>{
        Swal.fire({
          title: "Exito",
          text: "La información fue guardada satisfactoriamente",
          icon: 'success',
          showCancelButton: false,
          showConfirmButton:false,
          timer:2000,
          timerProgressBar: true,
          allowOutsideClick: false, // Bloquea clics fuera del diálogo
          allowEscapeKey: false, // Bloquea la tecla de escape
          allowEnterKey: false // Bloquea la tecla enter
        })

        
      })
      .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al cargar los datos: ' + error.message
          });
      })
      .finally(() => {
        this.mostrar();
        this.limpiar();
      })
    },
    limpiar: function(){
      this.prospectoie.rfc = null;
      this.prospectoie.nombre = null;
      this.prospectoie.calle = null;
      this.prospectoie.num_exterior = null;
      this.prospectoie.num_interior = null;
      this.prospectoie.colonia = null;
      this.prospectoie.cp = null;
      this.prospectoie.localidad = null;
      this.prospectoie.giro = null;
      this.prospectoie.oficina_id = null;
      this.prospectoie.fuente_id = null;
      this.prospectoie.periodos = null;
      this.prospectoie.antecedente_id = null;
      this.prospectoie.impuesto_id = null;
      this.prospectoie.determinado = null;
      this.prospectoie.programador_id = null;
      this.prospectoie.retenedor = null;
      this.prospectoie.representante_legal = null;
      this.prospectoie.observaciones = null; 
      this.prospectoie.estatus = 1; 
    },   
    editar: function () {
      let nombre = this.prospectoie.nombre != null && this.prospectoie.nombre !== '' ? this.prospectoie.nombre.toUpperCase() : this.prospectoie.nombre;
      let calle = this.prospectoie.calle != null && this.prospectoie.calle !== '' ? this.prospectoie.calle.toUpperCase() : this.prospectoie.calle;
      let num_exterior = this.prospectoie.num_exterior != null && this.prospectoie.num_exterior !== '' ? this.prospectoie.num_exterior.toUpperCase() : this.prospectoie.num_exterior;
      let num_interior = this.prospectoie.num_interior != null && this.prospectoie.num_interior !== '' ? this.prospectoie.num_interior.toUpperCase() : this.prospectoie.num_interior;
      let colonia = this.prospectoie.colonia != null && this.prospectoie.colonia !== '' ? this.prospectoie.colonia.toUpperCase() : this.prospectoie.colonia;
      let localidad = this.prospectoie.localidad != null && this.prospectoie.localidad !== '' ? this.prospectoie.localidad.toUpperCase() : this.prospectoie.localidad;
      let giro = this.prospectoie.giro != null && this.prospectoie.giro !== '' ? this.prospectoie.giro.toUpperCase() : this.prospectoie.giro;
      let periodos = this.prospectoie.periodos != null && this.prospectoie.periodos !== '' ? this.prospectoie.periodos.toUpperCase() : this.prospectoie.periodos;
      let representante_legal = this.prospectoie.representante_legal != null && this.prospectoie.representante_legal !== '' ? this.prospectoie.representante_legal.toUpperCase() : this.prospectoie.representante_legal;
      let observaciones = this.prospectoie.observaciones != null && this.prospectoie.observaciones !== '' ? this.prospectoie.observaciones.toUpperCase() : this.prospectoie.observaciones;

      axios
        .post(crud, {
            // Cambios
            opcion: 3,
            // Campos a guardar
            // rfc:this.prospectoie.rfc,
            id:this.prospectoie.id,
            nombre:nombre,
            calle:calle,
            num_exterior:num_exterior,
            num_interior:num_interior,
            colonia:colonia,
            cp:this.prospectoie.cp,
            localidad:localidad,
            giro:giro,
            oficina_id:this.prospectoie.oficina_id,
            fuente_id:this.prospectoie.fuente_id,
            giro: this.prospectoie.giro,
            periodos:periodos,
            antecedente_id:this.prospectoie.antecedente_id,
            impuesto_id:this.prospectoie.impuesto_id,
            determinado:this.prospectoie.determinado,
            programador_id:this.prospectoie.programador_id,
            determinado:this.prospectoie.determinado,
            retenedor:this.prospectoie.retenedor,
            representante_legal:representante_legal,
            observaciones:observaciones,
            estatus:this.prospectoie.estatus
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
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al cargar los datos: ' + error.message
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
      axios.post(urlprogramadores).then((response) => {
        this.programadores_listado = response.data;
      });
    },
    salir: function(){
      window.location.href = "logout.php";
    },
    //Botones y formularios
    guardar() {
      if (this.operacion == "crear") {
        this.crear();
      }
      if (this.operacion == "editar") {
        this.editar();
      }
      this.dialog = false;
    },
    guardar_supervisor() {
      if (this.operacion == "crear") {
        this.enviar_supervisor();
      }
      if (this.operacion == "editar") {
        this.editar();
      }
      this.dialog = false;
    },

    formNuevo: function () {
      this.dialog = true;
      this.operacion = "crear";
      this.prospectoie.fecha_captura=this.fechaactual();
      this.prospectoie.rfc=null;
      this.prospectoie.nombre=null;
      this.prospectoie.calle=null;
      this.prospectoie.num_exterior=null;
      this.prospectoie.num_interior=null;
      this.prospectoie.colonia=null;
      this.prospectoie.cp=null;
      this.prospectoie.localidad=null;
      this.prospectoie.giro=null;
      this.prospectoie.oficina_id=null;
      this.prospectoie.fuente_id=null;
      this.prospectoie.antecedente_id=null;
      this.prospectoie.periodos=null;
      this.prospectoie.impuesto_id=null;
      this.prospectoie.programador_id=null;
      this.prospectoie.determinado=null;
      this.prospectoie.representante_legal=null;
      this.prospectoie.observaciones=null;
    },

    formEditar: function (objeto) {
      //capturamos los datos del registro seleccionado 
      // y los mostramos en el formulario

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
      this.prospectoie.giro=objeto.giro;
      this.prospectoie.oficina_id=objeto.oficina_id;
      this.prospectoie.fuente_id=objeto.fuente_id;
      this.prospectoie.periodos=objeto.periodos;
      this.prospectoie.antecedente_id=objeto.antecedente_id;
      this.prospectoie.impuesto_id=objeto.impuesto_id;
      this.prospectoie.programador_id=objeto.programador_id;
      this.prospectoie.retenedor=objeto.retenedor;
      this.prospectoie.determinado=objeto.determinado;
      this.prospectoie.representante_legal=objeto.representante_legal;
      this.prospectoie.observaciones=objeto.observaciones;
      // this.ActualizaComboDeptos();
    },
    generar_antecedente: function (objeto) {
      console.log(objeto);
        axios.post(urlgenerar_antecedente, {
          data: objeto
        }, {
          responseType: 'blob' // Para recibir el archivo Excel
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', 'antecedente_'+objeto.rfc.toUpperCase()+'.xlsx'); // Nombre del archivo descargado
          document.body.appendChild(link);
          link.click();
        })
        .catch(error => {
          console.error("Error al generar el archivo:", error);
        });
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
      let date = new Date();

      let day = date.getDate();
      let month = date.getMonth() + 1;
      let year = date.getFullYear();
      let fechaactual = day + "/" + month + "/" + year;

      return fechaactual;
    },
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
