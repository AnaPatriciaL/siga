<template>
<div>
    <!-- Componente de Diálogo para CREAR y EDITAR -->
  <v-dialog v-model="dialog" max-width="1100px" persistent >
      <v-card>
        <v-form>
          <v-card-title class="pink darken-4 white--text py-2">PROSPECTO DE IMPUESTOS ESTATALES
            <v-spacer></v-spacer>
            <span class="text-h6">
              <template v-if="operacion === 'editar'">EDITAR (<span class="yellow--text">Estatus: {{ prospectoie.estatus_descripcion }}</span>)
              </template>
              <template v-else>{{ operacion.toUpperCase() }}</template>
            </span>
          </v-card-title>
          <v-card-text class="mb-2 py-0">
            <v-container>
              <v-row class="my-2 pt-4">
                <!-- RFC -->
                <v-col class="my-0 py-0" cols="12" md="3">
                  <v-text-field maxlength="13" minlength="12" class="my-0 py-0 mayusculas" v-model="prospectoie.rfc"
                    label="RFC" outlined :readonly="operacion=='editar' && esUsuarioNivel1()" dense @keydown="nobloquearBasicos" @input="limpiarRFC" ref="rfc"></v-text-field>
                </v-col>
                <!-- Boton buscar -->
                <v-col class="my-0 py-0" cols="12" md="1">
                  <v-btn dense color="orange" dark @click="datos_contribuyentes"><v-icon>mdi-database-search-outline </v-icon>
                  </v-btn>
                </v-col>
                <!-- Nombre -->
                <v-col class="my-0 py-0" cols="12" md="8">
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.nombre" @blur="prospectoie.nombre = prospectoie.nombre?.trim()" label="Nombre" outlined maxlength="300" dense></v-text-field>
                </v-col>
                <!-- Calle -->
                <v-col class="my-0 py-0" cols="12" md="4">
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.calle" @blur="prospectoie.calle = prospectoie.calle?.trim()" label="Calle/Avenida/Vialidad" maxlength="250" outlined dense></v-text-field>
                </v-col>
                <!-- Numero exterior -->
                <v-col class="my-0 py-0" cols="12" md="2">
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.num_exterior" nombre label="No. exterior" maxlength="250" outlined dense></v-text-field>
                </v-col>
                <!-- Numero interior -->
                <v-col class="my-0 py-0" cols="12" md="2">
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.num_interior" @blur="prospectoie.num_interior = prospectoie.num_interior?.trim()" label="No. interior" maxlength="250" outlined dense></v-text-field>
                </v-col>
                <!-- Colonia -->
                <v-col class="my-0 py-0" cols="12" md="4">
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.colonia" @blur="prospectoie.colonia = prospectoie.colonia?.trim()" label="Colonia" maxlength="150" outlined dense></v-text-field>
                </v-col>
                <!-- CP -->
                <v-col class="my-0 py-0" cols="12" md="2">
                  <v-text-field class="my-0 py-0" v-model="prospectoie.cp" @blur="prospectoie.cp = prospectoie.cp?.trim()" label="C.P." outlined maxlength="5" minlength="5" dense type="number"></v-text-field>
                </v-col>
                <!-- Localidad -->
                <v-col class="my-0 py-0" cols="12" md="4">
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.localidad" @blur="prospectoie.localidad = prospectoie.localidad?.trim()" label="Localidad" maxlength="100" outlined dense></v-text-field>
                </v-col>
                <!-- Municipio -->
                <v-col class="my-0 py-0" cols="12" md="3">
                  <v-select :items="municipiosListado" v-model="prospectoie.municipio_id" label="Municipio" outlined dense required item-text="nombre" item-value="municipio_id"></v-select>
                </v-col>
                <!-- Oficina -->
                <v-col class="my-0 py-0" cols="12" md="3">
                  <v-text-field v-model="prospectoie.oficina_descripcion" label="Oficina" outlined dense disabled readonly></v-text-field>
                </v-col>
                <!-- Giro -->
                <v-col class="my-0 py-0" cols="12" md="6">
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.giro" label="Giro/Actividad" item-text="" outlined maxlength="250" dense></v-text-field>
                </v-col>
              <v-col class="my-0 py-0" cols="12" md="1">
                  <v-tooltip top>
                    <template v-slot:activator="{ on, attrs }">
                      <v-btn icon color="pink darken-4" v-bind="attrs" v-on="on" @click="dialogPeriodos = true"><v-icon large >mdi-plus-circle</v-icon></v-btn>
                    </template>
                    <span>Agregar Periodo</span>
                  </v-tooltip>
                </v-col>
                <v-col class="my-0 py-0" cols="12" md="5" >
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.periodos" @blur="prospectoie.periodos = prospectoie.periodos?.trim()" label="Periodos" outlined dense readonly></v-text-field>
                </v-col>
                <!-- Antecedentes -->
                <v-col class="my-0 py-0" cols="12" md="6">
                  <v-select
                    :items="antecedentesListado" v-model="prospectoie.antecedente_id" label="Antecedente" outlined dense required item-text="descripcion" item-value="id">
                  </v-select> 
                </v-col>
                <!-- Impuesto -->
                <v-col class="my-0 py-0" cols="12" md="3">
                  <v-select
                    :items="impuestosListado" v-model="prospectoie.impuesto_id" label="Impuesto" outlined dense required item-text="impuesto" item-value="id">
                  </v-select> 
                </v-col>
                <!-- Presuntiva -->
                <v-col class="my-0 py-0" cols="12" md="3">
                  <v-text-field
                    class="my-0 py-0" label="Presuntiva/Determinado" v-model="prospectoie.determinado" item-text="" outlined hide-spin-buttons suffix="$" maxlength="12" reverse type="number" dense></v-text-field>
                </v-col>
                <!-- Fecha -->
                <v-col class="my-0 py-0" cols="12" md="2">
                  <v-text-field reverse readonly disabled dense outlined maxlength="10" v-model="prospectoie.fecha_captura" label="Fecha captura"></v-text-field>
                </v-col>
                <!-- Usuario -->
                <v-col class="my-0 py-0" cols="12" md="3">
                  <template v-if="esUsuarioNivel1()">
                    <v-text-field
                      v-model="prospectoie.programador_descripcion"
                      label="Programador"
                      outlined dense readonly disabled
                    ></v-text-field>
                  </template>
                  <template v-else>
                    <v-select 
                      :items="programadoresListado" v-model="prospectoie.programador_id" label="Programador" outlined dense required item-text="usuario" item-value="id" ref="programador">
                    </v-select>
                  </template>
                </v-col>
                <!-- Fuente -->
                <v-col class="my-0 py-0" cols="12" md="3">
                  <v-select
                    :items="fuentesListado" v-model="prospectoie.fuente_id" label="Fuente" outlined dense required item-text="nombre" item-value="id">
                  </v-select> 
                </v-col>
                <!-- Retenedor -->
                <v-col class="my-0 py-0" cols="12" md="2">
                  <v-switch color="pink darken-4" class="my-0 py-0 mayusculas" v-model="prospectoie.retenedor" inset label="Retenedor"></v-switch>
                </v-col>
                <!-- Cambio Domicilio -->
                <v-col class="my-0 py-0" cols="12" md="2">
                  <v-checkbox color="pink darken-4" class="my-0 py-0" v-model="prospectoie.cambio_domicilio" :true-value="1" :false-value="0" label="Cambio Domicilio"/>
                </v-col>
                <!-- Domicilio Anterior -->
                <v-col v-if="prospectoie.cambio_domicilio === 1" class="my-0 py-0" cols="12" md="6">
                  <v-text-field dense class="my-0 py-0 mayusculas" v-model="prospectoie.domicilio_anterior" @blur="prospectoie.domicilio_anterior = prospectoie.domicilio_anterior?.trim()" label="Domicilio Anterior" outlined/>
                </v-col>
                <!-- Notificador / Visitador -->
                <v-col v-if="prospectoie.cambio_domicilio === 1" class="my-0 py-0" cols="12" md="4">
                  <v-text-field dense class="my-0 py-0 mayusculas" v-model="prospectoie.notificador" @blur="prospectoie.notificador = prospectoie.notificador?.trim()" label="Notificador / Visitador" outlined/>
                </v-col>
                <!-- Fecha Acta -->
                <v-col v-if="prospectoie.cambio_domicilio === 1" class="my-0 py-0" cols="12" md="2">
                  <v-text-field v-model="prospectoie.fecha_acta" label="Fecha acta" outlined dense maxlength="10" placeholder="DD/MM/YYYY" @input="formatearFechaActa" :rules="[rules.dateFormat]"/>
                </v-col>
                <!-- Representante Legal -->
                <v-col v-if="prospectoie.rfc && prospectoie.rfc.length === 12" class="my-0 py-0" cols="12" md="12">
                  <v-text-field dense class="my-0 py-0 mayusculas" v-model="prospectoie.representante_legal" @blur="prospectoie.representante_legal = prospectoie.representante_legal?.trim()" label="Representante Legal" item-text="" outlined maxlengt></v-text-field>
                </v-col>
                <!-- Origen -->
                <v-col class="my-0 py-0" cols="12" md="2">
                  <v-switch color="pink darken-4"class="my-0 py-0 mayusculas" v-model="prospectoie.origen_id" inset :label="prospectoie.origen_id ? 'Origen: Prospecto' : 'Origen: Cruce'"></v-switch>
                </v-col>
                <!-- Observaciones -->
                <v-col class="my-0 py-0" cols="12" md="10">
                  <v-text-field class="my-0 py-0 mayusculas" v-model="prospectoie.observaciones" @blur="prospectoie.observaciones = prospectoie.observaciones?.trim()" label="Observaciones" item-text="" outlined  maxlength="200"dense></v-text-field>
                </v-col>
              </v-row>
            </v-container>
          </v-card-text>
          <v-divider></v-divider>
          <v-card-actions class="grey lighten-2 py-2">
            <v-spacer></v-spacer>
            <v-btn v-if="mostrarBotonSupervisor && Number(prospectoie.estatus) === 1" class="my-1 ma-2 py-1" color="blue-grey" @click="validar_supervisor" dark>Enviar a supervisor<v-icon dark right> mdi-account-tie-hat</v-icon></v-btn>
            <v-btn class="my-1 ma-2 py-1" color="success" @click="validar(false)" dark>Guardar<v-icon dark right> mdi-checkbox-marked-circle </v-icon></v-btn>
            <v-btn class="ma-2" dark @click="dialog=false">Cancelar<v-icon dark left> mdi-cancel </v-icon></v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
  </v-dialog>
  <!-- Componente de Diálogo para PERIODOS -->
  <v-dialog v-model="dialogPeriodos" max-width="600px" persistent>
      <v-card>
        <v-card-title class="pink darken-4 white--text py-2">Agregar Periodo</v-card-title>
        <v-card-text>
          <v-container>
            <v-row v-for="(periodo, index) in periodosParaAgregar" :key="index" class="mt-2 align-center no-gutters">
                <v-col cols="12" sm="5">
                  <v-text-field
                  class="mr-4" v-model="periodo.inicio" :ref="'fechaInicio' + index" v-mask="'##/##/####'" :rules="[rules.dateFormat]" label="Fecha Inicial" 
                  outlined dense @input="manejarInputFecha(periodo, index, 'inicio')"@keydown="soloNumeros"maxlength="10"placeholder="DD/MM/YYYY">
                  </v-text-field>
                </v-col>
                <v-col cols="12" sm="5">
                  <v-text-field v-model="periodo.fin" v-mask="'##/##/####'" :rules="[rules.dateFormat]" label="Fecha Final" :ref="'fechaFin' + index" 
                  @input="validarFechaFinal(periodo, index)" @keydown.enter.prevent="enfocarBotonAgregar" @keydown="soloNumeros" outlined dense maxlength="10"placeholder="DD/MM/YYYY">
                  </v-text-field>
                </v-col>
                <v-col cols="12" sm="2" class="text-center">
                  <v-btn icon color="red" @click="eliminarFilaPeriodo(index)"><v-icon>mdi-delete</v-icon></v-btn>
                </v-col>
              </v-row>
          </v-container>
        </v-card-text>
        <v-card-actions class="grey lighten-2 py-2">
          <v-spacer></v-spacer>
          <v-tooltip top>
            <template v-slot:activator="{ on, attrs }">
              <v-btn icon color="pink darken-4" @click="agregarFilaPeriodo" v-bind="attrs" v-on="on"><v-icon large >mdi-plus-circle</v-icon></v-btn>
            </template>
            <span>Agregar un periodo extra</span>
          </v-tooltip>
          <v-btn ref="botonAgregarPeriodo" color="success" dark @click="agregarPeriodo">Agregar</v-btn>
          <v-btn color="blue-grey" dark @click="cerrarDialogoPeriodo">Cerrar</v-btn>
          <v-spacer></v-spacer>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="cargando" 	max-width="290" persistent  >
      <v-card color="pink darken-4" dark>
          <v-card-text class="pt-3">
              Buscando información
              <v-progress-linear indeterminate color="white" class="my-3"></v-progress-linear>
          </v-card-text>
      </v-card>
  </v-dialog>
</div>
</template>
<script>
import Swal from 'sweetalert2';
import axios from 'axios';
import { vMaska } from "maska";
import api from '@/services/api';

var urlpadron = api.padron;

export default {
  name: "FormCrearEditar",
  props: {
    value: { // Para el v-model del diálogo
      type: Boolean,
      default: false,
    },
    operacion: String,
    prospectoieData: Object,
    municipiosListado: Array,
    antecedentesListado: Array,
    impuestosListado: Array,
    programadoresListado: Array,
    fuentesListado: Array,
    oficinasListado: Array,
    cargandoProp: Boolean,
    mostrarBotonSupervisor: {
      type: Boolean,
      default: true,
    },
  },
  directives: {
    maska: vMaska,
  },
  data() {
    return {
      prospectoie: {},
      cargando: false,
      dialogPeriodos: false,
      periodosParaAgregar: [{ inicio: '', fin: '' }],
      rules: {
        dateFormat: value => {
            const pattern = /^(0[1-9]|[12]\d|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/;
            if (value && value.length === 10 && pattern.test(value)) {
              return true; 
            }
            return 'Formato incorrecto (DD/MM/YYYY).'; // Mensaje de error
          },
      },
    };
  },
  computed: {
    periodoCompleto() {
      // Concatenar los valores con " AL " en medio si ambos campos tienen datos
      if (this.fechaInicio && this.fechaFin) {
        return `${this.fechaInicio} AL ${this.fechaFin}`;
      }
        return ''; // O maneja el caso donde uno o ambos están vacíos
    },
    dialog: {
      get() {
        return this.value;
      },
      set(val) {
        this.$emit('input', val);
      }
    }
  },
  watch: {
    value(val) {
      if (val) {
        this.periodosParaAgregar = [{
          inicio: '',
          fin: ''
        }];
      }
    },
    // Cuando el diálogo se abre, copia los datos del prop al estado local.
    dialog(val) {
      if (val) { // Si el diálogo se abre
        this.prospectoie = JSON.parse(JSON.stringify(this.prospectoieData));
        this.prospectoie.programador_id = Number(this.prospectoie.programador_id);
        const usuarioId = Number(localStorage.getItem('id'));
          const usuarioActual = this.programadoresListado.find(u => Number(u.id) === usuarioId);
          if (usuarioActual) {
            // Asignamos siempre el ID y la descripción
            this.prospectoie.programador_id = Number(usuarioActual.programador_id);
            this.prospectoie.programador_descripcion = usuarioActual.usuario;  
        }
      }
    },
    operacion(val) {
      // Limpiar los períodos cuando se abre para crear un nuevo prospecto
      if (val === 'crear') {
        this.periodosParaAgregar = [{ inicio: '', fin: '' }];
      }
    },
    dialogPeriodos(val) {
      if (val) {
        this.$nextTick(() => {
          const ref = this.$refs.fechaInicio0;
          if (ref && ref[0]) {
            ref[0].focus();
          }
        });
      }
    },
    // Observa cambios en el municipio para auto-seleccionar la oficina
     'prospectoie.municipio_id'(id_municipio) {      
      if (id_municipio && this.municipiosListado && this.oficinasListado) {
        const municipioSeleccionado = this.municipiosListado.find(m => m.municipio_id === id_municipio);
        this.prospectoie.oficina_descripcion = null;
        if (municipioSeleccionado) {
          this.prospectoie.oficina_id = municipioSeleccionado.oficina_id;
          const oficina = this.oficinasListado.find(o => o.id === this.prospectoie.oficina_id);
          if (oficina) {
            this.prospectoie.oficina_descripcion = oficina.nombre;
          }
        }
      } else {
        this.prospectoie.oficina_descripcion = null;
        this.prospectoie.oficina_id = null;
      }
    },
    // Propaga los cambios del objeto local al padre
    prospectoie: {
      handler(newVal) {
        this.$emit('update:prospectoieData', newVal);
      },
      deep: true,
    }
    
  },
  methods: {
    formatearFechaActa(valor) {
      if (!valor) {
        this.prospectoie.fecha_acta = '';
        return;
      }

      // Quitar todo lo que no sea número
      let numeros = valor.replace(/\D/g, '');

      // Limitar a 8 dígitos (DDMMYYYY)
      numeros = numeros.substring(0, 8);

      let fecha = '';

      if (numeros.length <= 2) {
        fecha = numeros;
      } else if (numeros.length <= 4) {
        fecha = `${numeros.substring(0, 2)}/${numeros.substring(2)}`;
      } else {
        fecha = `${numeros.substring(0, 2)}/${numeros.substring(2, 4)}/${numeros.substring(4)}`;
      }

      this.prospectoie.fecha_acta = fecha;
    },
    nobloquearBasicos(e) {
      // Permitir teclas especiales
      const permitidas = [
        'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'
      ];
      if (permitidas.includes(e.key)) return;

      // Solo letras y números
      if (!/^[a-zA-Z0-9]$/.test(e.key)) {
        e.preventDefault(); //bloquea el .
      }
    },
    limpiarRFC() {
      if (this.prospectoie.rfc) {
        this.prospectoie.rfc = this.prospectoie.rfc
          .toUpperCase()              // Opcional: forzar mayúsculas
          .replace(/[^A-Z0-9]/g, ''); // Solo letras y números
      }
    },
    esUsuarioNivel1() {
      const idUsuario = Number(localStorage.getItem('id'));
      if (!idUsuario || !this.programadoresListado) return false;
      const usuario = this.programadoresListado.find(p => p.id === idUsuario);
      if (usuario && usuario.nivel === "1") {
        return true;
      }
      return false;
    },
    // Lógica para buscar datos del contribuyente
    datos_contribuyentes() {
      if (!this.prospectoie.rfc || this.prospectoie.rfc.length < 12) {
        Swal.fire('Atención', 'El RFC debe tener entre 12 y 13 caracteres.', 'warning');
        return;
      }
      this.cargando = true;
      axios.post(urlpadron, { rfc: this.prospectoie.rfc }, { withCredentials: true })
        .then(response => {
          if (response.data.length > 0) {
            const data = response.data[0];
            this.prospectoie.nombre = data.nombre;
            this.prospectoie.calle = data.calle;
            this.prospectoie.num_exterior = data.num_exterior;
            this.prospectoie.num_interior = data.num_interior;
            this.prospectoie.colonia = data.colonia;
            this.prospectoie.cp = data.cp;
            this.prospectoie.localidad = data.localidad;
            this.prospectoie.giro = data.giro;
            this.prospectoie.municipio_id = data.municipio_id;
            this.prospectoie.oficina_id = data.oficina_id;
          } else {
            Swal.fire('Info', `No se encontró información para el RFC: ${this.prospectoie.rfc}`, 'info');
          }
        })
        .catch(error => {
          Swal.fire('Error', 'No se pudo obtener la información del padrón.', 'error');
          console.error(error);
        })
        .finally(() => {
          this.cargando = false;
        });
    },
    // Lógica de validación
    async validar(esSupervisor = false) {
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
      if (
        (this.prospectoie.fuente_id === null || this.prospectoie.fuente_id === "") || 
        (this.prospectoie.rfc.length === 12 && (this.prospectoie.representante_legal === null || this.prospectoie.representante_legal === "")) ||
        this.prospectoie.nombre === null || this.prospectoie.nombre === "" ||
        this.prospectoie.calle === null || this.prospectoie.calle === "" ||
        ((this.prospectoie.localidad === null || this.prospectoie.localidad === "") &&
          (this.prospectoie.colonia === null || this.prospectoie.colonia === "")) ||
        this.prospectoie.municipio_id === null || this.prospectoie.municipio_id === "" ||
        this.prospectoie.oficina_id === null || this.prospectoie.oficina_id === "" ||
        this.prospectoie.periodos === null || this.prospectoie.periodos === "" ||
        this.prospectoie.antecedente_id === null || this.prospectoie.antecedente_id === "" ||
        this.prospectoie.giro === null || this.prospectoie.giro === "" ||
        this.prospectoie.impuesto_id === null || this.prospectoie.impuesto_id === "" ||
        this.prospectoie.determinado === null || this.prospectoie.determinado === "" ||
        this.prospectoie.programador_id === null
      ) {
        textoMostrar='Es necesario llenar toda la información';
        errores=3;
      }
      if (errores>0) {
        await Swal.fire({  
          position: 'center',
          icon: 'warning',  
          titleText: 'Datos incompletos',
          text: textoMostrar,
          allowOutsideClick: false, // Evita que el usuario cierre el cuadro de diálogo haciendo clic fuera de él
          showConfirmButton: true,
          confirmButtonText: 'Entendido',
          confirmButtonAriaLabel: 'Entendido, cerrar alerta'
        })
          this.$refs.rfc.focus();
          return;
      }else{
        if (esSupervisor) {
          // Si la validación es para el supervisor, cambia el estatus aquí.
          this.prospectoie.estatus = 2;
        }
        this.$emit('guardar', this.prospectoie, this.periodosParaAgregar);
      this.cerrar();
      }
      
    },
    async validar_supervisor() {
      await this.validar(true);
    },
    cerrar() {
      this.$emit('cerrar');
      this.cerrarDialogoPeriodo(); // Limpia los periodos al cerrar el diálogo principal
    },
    // Métodos para el diálogo de periodos
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
    agregarFilaPeriodo() {
      this.periodosParaAgregar.push({ inicio: '', fin: '' });
    },
    eliminarFilaPeriodo(index) {
      // Evita que se elimine la última fila
      if (this.periodosParaAgregar.length > 1) {
        this.periodosParaAgregar.splice(index, 1);
      }
    },
    agregarPeriodo() {
      // Primero, valida que todos los periodos sean correctos.
      if (!this.validarTodosLosPeriodos()) {
        return; // Detiene la ejecución si hay un error de validación.
      }

      const nuevosPeriodos = this.periodosParaAgregar
        .filter(p => p.inicio && p.fin) // Filtra los que están completos
        .map(p => `${p.inicio}-${p.fin}`); // Les da el nuevo formato

      if (nuevosPeriodos.length > 0) {
        // Reemplaza los periodos existentes con los nuevos.
        this.prospectoie.periodos = nuevosPeriodos.join(', ');
      }
      this.dialogPeriodos = false; // Solo cierra el diálogo, no resetea el array
    },
    async sincronizarPeriodosDetalle(prospectoId, periodsArray, limpiarDespues = false) {
      // Valida que el parámetro sea un array y no esté vacío.
      if (!Array.isArray(periodsArray) || periodsArray.length === 0) {
        console.log("No hay periodos para sincronizar.");
        return;
      }
      try {
        // 1. Eliminar periodos existentes para este prospecto en la BD.
        await axios.post(crud, {
          opcion: 6, // Nueva opción para eliminar periodos por prospecto_id
          prospecto_id: prospectoId
        });

        // 2. Insertar los nuevos periodos uno por uno desde el array.
        for (const periodo of periodsArray) {
          if (periodo.inicio && periodo.fin) {
            await axios.post(crud, {
              opcion: 7, // Nueva opción para insertar un periodo
              prospecto_id: prospectoId,
              fecha_inicial: periodo.inicio,
              fecha_final: periodo.fin,
              status: 1 // Asumiendo un status por defecto
            });
          }
        }
        console.log('Periodos sincronizados correctamente.');

        // 3. Si se indica, limpiar el array de periodos después de una operación exitosa.
        if (limpiarDespues) {
          this.periodosParaAgregar = [{ inicio: '', fin: '' }];
        }

      } catch (error) {
        Swal.fire('Error', 'Hubo un problema al sincronizar los periodos: ' + error.message, 'error');
        console.error('Error al sincronizar periodos:', error);
      }
    },
    cerrarDialogoPeriodo() {
      this.dialogPeriodos = false;
    },
    /**
     * Establece el campo de periodos a partir de un array de objetos.
     * @param {Array<Object>} periodosArray - Un array de objetos, donde cada objeto tiene las propiedades 'inicio' y 'fin'.
     * Ejemplo: [{ inicio: '01/01/2024', fin: '31/01/2024' }]
     */
    establecerPeriodosDesdeArray(periodosArray) {
      // Valida que el parámetro sea un array
      if (!Array.isArray(periodosArray)) {
        console.error("El parámetro proporcionado no es un array válido.");
        this.prospectoie.periodos = ''; // Limpia el campo en caso de error
        return;
      }

      const nuevosPeriodos = periodosArray
        .filter(p => p.inicio && p.fin) // Filtra los periodos que están completos
        .map(p => `${p.inicio}-${p.fin}`); // Les da el formato 'inicio-fin'

      this.prospectoie.periodos = nuevosPeriodos.join(', '); // Asigna el string final
    },
    manejarInputFecha(periodo, index, tipo) {
      if (tipo === 'inicio' && periodo.inicio && periodo.inicio.length === 10) {
        // Usar $nextTick para asegurarse de que el DOM se haya actualizado
        this.$nextTick(() => { //
          const finRef = this.$refs['fechaFin' + index]; //
          if (finRef && finRef[0]) {
            const inputElement = finRef[0].$el.querySelector('input');
            inputElement.focus();
            inputElement.select();
          }
        });
      }
    },
    enfocarBotonAgregar() {
      this.$nextTick(() => {
        const boton = this.$refs.botonAgregarPeriodo;
        if (boton) {
          boton.$el.focus();
        }
      });
    },
    async validarFechaFinal(periodo, index) {
      if (periodo.inicio && periodo.fin && periodo.fin.length === 10) {
        // Primero, verificar que ambas fechas estén completas en formato DD/MM/YYYY
        if (periodo.inicio.length !== 10 || periodo.fin.length !== 10) {
          return; // No hacer nada si las fechas están incompletas, la máscara o el watcher las completará.
        }

        const [diaInicio, mesInicio, anioInicio] = periodo.inicio.split('/');
        const fechaInicio = new Date(`${anioInicio}-${mesInicio}-${diaInicio}`);

        const [diaFin, mesFin, anioFin] = periodo.fin.split('/');
        const fechaFin = new Date(`${anioFin}-${mesFin}-${diaFin}`);
        
        if (fechaFin <= fechaInicio) {
          // Espera a que el usuario interactúe con la alerta.
          const result = await Swal.fire({
            icon: 'error',
            title: 'Fecha incorrecta',
            text: 'La fecha final debe ser mayor a la fecha inicial.',
            confirmButtonText: 'Corregir',
            confirmButtonAriaLabel: 'Corregir fecha',
            // Forzar el foco en el botón de confirmación después de que la alerta se muestre.
            didRender: () => {
              const confirmButton = Swal.getConfirmButton();
              if (confirmButton) confirmButton.focus();
            }
          });

          // Si el usuario confirma, enfoca el campo para su corrección.
          if (result.isConfirmed) {
            const finRef = this.$refs['fechaFin' + index];
            if (finRef && finRef[0]) {
              finRef[0].$el.querySelector('input').select();
            }
          }
        }
      }
    },
    validarTodosLosPeriodos() {
      for (const [index, periodo] of this.periodosParaAgregar.entries()) {
        // Solo validar filas que tienen al menos una fecha ingresada
        const tieneFechaInicial = periodo.inicio && periodo.inicio.length === 10;
        const tieneFechaFinal = periodo.fin && periodo.fin.length === 10;

        if (periodo.inicio || periodo.fin) { // Si hay algún intento de ingresar fechas en esta fila
          if (!this.esFechaValida(periodo.inicio) || !this.esFechaValida(periodo.fin)) {
            Swal.fire('Error', 'Una de las fechas no es válida', 'error');
            return false;
          }

          if (!tieneFechaInicial || !tieneFechaFinal) {
            Swal.fire({
              icon: 'error',
              title: 'Fechas incompletas',
              text: `En la fila ${index + 1}, ambas fechas (inicial y final) deben estar completas (DD/MM/YYYY).`,
              confirmButtonText: 'Corregir'
            });
            return false;
          }

          // Convertir fechas a objetos Date para comparación
          const [diaInicio, mesInicio, anioInicio] = periodo.inicio.split('/');
          const fechaInicio = new Date(`${anioInicio}-${mesInicio}-${diaInicio}`);

          const [diaFin, mesFin, anioFin] = periodo.fin.split('/');
          const fechaFin = new Date(`${anioFin}-${mesFin}-${diaFin}`);

          // 1. Validar que la fecha final sea mayor que la fecha inicial en la misma fila
          if (fechaFin <= fechaInicio) {
            Swal.fire({
              icon: 'error',
              title: 'Fecha incorrecta',
              text: `En la fila ${index + 1}, la fecha final debe ser mayor que la fecha inicial.`,
              confirmButtonText: 'Corregir'
            });
            return false;
          }

          // 2. Validar que la fecha inicial de la fila actual sea mayor que la fecha final de la fila anterior
          if (index > 0) {
            const periodoAnterior = this.periodosParaAgregar[index - 1];
            // Asegurarse de que el período anterior también esté completo para la comparación
            if (periodoAnterior.fin && periodoAnterior.fin.length === 10) {
              const [prevDiaFin, prevMesFin, prevAnioFin] = periodoAnterior.fin.split('/');
              const prevFechaFin = new Date(`${prevAnioFin}-${prevMesFin}-${prevDiaFin}`);

              if (fechaInicio <= prevFechaFin) {
                Swal.fire({
                  icon: 'error',
                  title: 'Fechas superpuestas',
                  text: `En la fila ${index + 1}, la fecha inicial debe ser posterior a la fecha final de la fila anterior.`,
                  confirmButtonText: 'Corregir'
                });
                return false;
              }
            }
          }
        }
      }
      return true; // Indica que todos los periodos son válidos.
    },
    esFechaValida(fecha) {
      const pattern = /^(0[1-9]|[12]\d|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/;
      if (!fecha || !pattern.test(fecha)) {
        return false;
      }

      const [dia, mes, anio] = fecha.split('/');
      const d = new Date(anio, mes - 1, dia);
      
      return (
        d.getFullYear() == anio &&
        d.getMonth() + 1 == mes &&
        d.getDate() == dia
      );
      return true;

    },
    soloNumeros(event) {
      // Permite teclas de control como backspace, delete, tab, escape, enter, y las flechas.
      const controlKeys = [8, 9, 13, 27, 37, 38, 39, 40, 46];
      if (controlKeys.includes(event.keyCode)) {
        return;
      }

      // Permite combinaciones como Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
      if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) {
        return;
      }

      // Si la tecla presionada no es un número, previene la acción.
      if (!/^\d$/.test(event.key)) {
        event.preventDefault();
      }
    }
  },
};
</script>