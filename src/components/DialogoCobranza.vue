<template>
  <v-dialog :value="value" @input="$emit('input', $event)" max-width="1100px" persistent>
    <v-card>
      <v-form @submit.prevent="guardar">
        <v-card-title class="pink darken-4 white--text py-2">
          {{ esEdicion ? 'Editar' : 'Registrar' }} Cobranza
        </v-card-title>
        <v-card-text class="mb-2 py-0">
          <v-container>
            <!-- Orden,fecha,RFC y nombre -->
            <v-row class="my-2 pt-0">
              <v-col class="my-0 py-0" cols="12" md="2">
                <v-text-field readonly class="my-0 py-0 mayusculas texto-grande" :value="orden.orden" outlined hide-details dense background-color="deep-orange lighten-3"></v-text-field>
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="2">
                <v-text-field reverse readonly class="my-0 py-0 texto-grande" :value="fechaOrdenFormateada" label="Fecha Orden" outlined hide-details dense></v-text-field>
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="2">
                <v-text-field readonly class="my-0 py-0 mayusculas texto-grande" :value="orden.rfc" label="RFC" outlined hide-details dense></v-text-field>
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="6">
                <v-text-field class="my-0 py-0 mayusculas" :value="orden.nombre" label="Nombre" outlined maxlength="300" dense readonly hide-details></v-text-field>
              </v-col>
            </v-row>
            <!-- Domicilio y Oficina -->
            <v-row class="my-2 pt-0">
              <v-col class="my-0 py-0" cols="12" md="10">
                <v-text-field readonly class="my-0 py-0 mayusculas" :value="orden.domicilio" label="Domicilio" maxlength="250" outlined dense hide-details></v-text-field>
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="2">
                <v-text-field readonly class="my-0 py-0 mayusculas" :value="orden.oficina" label="Oficina SATES" maxlength="250" outlined dense hide-details></v-text-field>
              </v-col>
            </v-row>
            <!-- Fecha de Pago, Impuesto y Periodo de pago -->
            <v-row class="">
              <v-col class="my-0 py-0" cols="12" md="2">
                <v-menu v-model="menuFecha" :close-on-content-click="false" transition="scale-transition" offset-y max-width="290px" min-width="auto">
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field outlined dense :value="fechaPagoFormateada" label="Fecha del pago" persistent-hint prepend-icon="mdi-calendar" readonly hide-details v-bind="attrs" v-on="on" />
                  </template>
                  <v-date-picker :value="cobranza.fecha_pago" @input="val => updateCobranza('fecha_pago', val)" no-title locale="es" @change="menuFecha = false"></v-date-picker>
                </v-menu>
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="4">
                <v-select 
                  v-model="cobranza.tipo_pago_id"
                  :items="tiposPagos"
                  item-text="descripcion"
                  item-value="id"
                  label="Tipo de pago" 
                  outlined 
                  hide-details 
                  dense></v-select>
                  
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="2">
                <v-select 
                v-model="cobranza.impuesto_id"
                :items="impuestos" 
                item-text="descripcion" 
                item-value="id" 
                label="Impuesto" 
                outlined 
                hide-details 
                dense>
              </v-select>
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="2">
                <v-text-field 
                v-model="cobranza.periodo_inicial"
                v-mask="'##/##/####'"
                reverse label="Periodo Inicial" 
                hint="Formato: DD/MM/AAAA" 
                outlined hide-details 
                dense 
                :rules="[validarFecha]">
              </v-text-field>
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="2">
                <v-text-field 
                reverse   
                v-model="cobranza.periodo_final"
                v-mask="'##/##/####'"
                label="Periodo Final" 
                hint="Formato: DD/MM/AAAA" 
                outlined 
                hide-details 
                dense 
                :rules="[validarFecha]">
              </v-text-field>
              </v-col>
            </v-row>
            <!-- lugarPago y No. Operacion -->
            <v-row class="mt-5">
              <v-col class="my-0 py-0" cols="12" md="4">
                <v-select
                v-model="cobranza.lugar_pago_id"
                :items="lugaresPago" 
                item-text="descripcion" 
                item-value="id" 
                label="Lugar de Pago" 
                hide-details 
                outlined 
                dense></v-select>
              </v-col>
              <v-col class="my-0 py-0" cols="12" md="4">
                <v-text-field 
                class="my-0 py-0 mayusculas" 
                label="No. Operación" 
                outlined 
                reverse 
                maxlength="25" 
                hide-details 
                dense 
                v-model="cobranza.no_operacion" />
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6" md="3">
                <CampoMillares v-model="cobranza.principal" label="Principal" class="mb-2" />
                <CampoMillares v-model="cobranza.actualizacion" label="Actualización" class="mb-2" />
                <CampoMillares v-model="cobranza.recargo" label="Recargo" class="mb-2" />
              </v-col>
              <v-spacer></v-spacer>
              <v-col cols="6" md="3">
                <CampoMillares v-model="cobranza.multa_fondo" label="Multa de Fondo" class="mb-2" />
                <CampoMillares v-model="cobranza.multa_forma" label="Multa de Forma" class="mb-2" />
                <v-text-field class="my-0 font-weight-bold mb-2" :value="Total_Multa_Formateado" label="Total de Multa" background-color="grey lighten-2" readonly reverse hide-details dense tabindex="-1" suffix="$" outlined />
              </v-col>
              <v-spacer></v-spacer>
              <v-col cols="6" class="mt-2 " md="4">
                <v-text-field class="mt-2 py-5 text-h4" :value="Total_Cobranza_Formateado" label="Total Cobranza" background-color="green lighten-4" hide-details readonly reverse tabindex="-1" suffix="$" outlined />
              </v-col>
            </v-row>
            <!-- Auditor y Supervisor -->
            <v-row>
              <v-col class="my-1 py-0" cols="12" md="2">
                <v-text-field v-model="cobranza.auditor_cve" class="my-0 py-0 mayusculas" label="Clave Auditor" outlined hide-details maxlength="4" dense></v-text-field>
              </v-col>
              <v-col class="my-1 py-0" cols="12" md="4">
                <v-text-field disabled :value="cobranza.nombre_auditor" class="my-0 py-0 mayusculas" label="Nombre Auditor" outlined hide-details maxlength="300" dense></v-text-field>
              </v-col>
              <v-col class="my-1 py-0" cols="12" md="2">
                <v-text-field v-model="cobranza.supervisor_cve" class="my-0 py-0 mayusculas" label="Clave Supervisor" outlined hide-details maxlength="4" dense></v-text-field>
              </v-col>
              <v-col class="my-1 py-0" cols="12" md="4">
                <v-text-field disabled :value="cobranza.nombre_supervisor" class="my-0 py-0 mayusculas" label="Nombre Supervisor" outlined hide-details maxlength="300" dense></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col class="my-1 py-0" cols="12" md="12">
                <v-text-field v-model="cobranza.observaciones" class="my-0 py-0 mayusculas" label="Observaciones" outlined hide-details maxlength="300" dense></v-text-field>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions class="grey lighten-2 py-2">
          <v-spacer></v-spacer>
          <v-btn class="my-1 ma-2 py-1" color="primary" @click="guardar" dark>
            Guardar <v-icon dark right>mdi-checkbox-marked-circle</v-icon>
          </v-btn>
          <v-btn class="ma-2" dark @click="$emit('input', false)">
            Cancelar <v-icon dark left>mdi-cancel</v-icon>
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import CampoMillares from '@/components/CampoMillares.vue';
import { formatCurrency } from '@/utils/formatters';

const props = defineProps({
  value: Boolean,
  orden: { type: Object, required: true },
  esEdicion: Boolean, // Prop que indica si el diálogo está en modo edición
  cobranza: { type: Object, required: true },
  impuestos: { type: Array, default: () => [] },
  lugaresPago: { type: Array, default: () => [] },
  tiposPagos: { type: Array, default: () => [] },
  // nombreAuditor: String,
  // nombreSupervisor: String,
});

const emit = defineEmits(['input', 'guardar', 'update:cobranza']);

const menuFecha = ref(false);

// Observa cuando el diálogo se abre o se cierra
watch(() => props.value, (dialogoAbierto) => {
  if (dialogoAbierto) {
    // DIÁLOGO SE ABRE: Si es edición, convierte las fechas de AAAA-MM-DD a DD/MM/AAAA
    if (props.esEdicion) {
      if (props.cobranza.periodo_inicial && !props.cobranza.periodo_inicial.includes('/')) {
        const [year, month, day] = props.cobranza.periodo_inicial.split('-');
        props.cobranza.periodo_inicial = `${day}/${month}/${year}`;
      }
      if (props.cobranza.periodo_final && !props.cobranza.periodo_final.includes('/')) {
        const [year, month, day] = props.cobranza.periodo_final.split('-');
        props.cobranza.periodo_final = `${day}/${month}/${year}`;
      }
    }
  } else {
    // DIÁLOGO SE CIERRA: Emitimos un evento para que el padre limpie el formulario.
    // Emitimos un evento para que el padre limpie el formulario.
    // Esto es crucial para que no queden datos residuales al volver a abrir.
    // El padre debe tener un método que responda a este evento.
    emit('cerrar');
  }
});

const guardar = () => {
  emit('guardar'); // El padre se encarga de guardar y luego cerrar el diálogo
};

// Propiedades computadas para manejar los v-model de los selects
const tipoPagoIdModel = computed({
  // GET: Asegura que el valor que lee el v-select sea siempre un número.
  get: () => Number(props.cobranza.tipo_pago_id),
  // GET: Asegura que el valor que lee el v-select sea siempre un string.
  get: () => String(props.cobranza.tipo_pago_id || ''),
  // SET: Actualiza el valor en el objeto 'cobranza' cuando el usuario selecciona una opción.
  set: (value) => { props.cobranza.tipo_pago_id = value; }
});

const impuestoIdModel = computed({
  // GET: Asegura que el valor que lee el v-select sea siempre un número.
  get: () => Number(props.cobranza.impuesto_id),
  // GET: Asegura que el valor que lee el v-select sea siempre un string.
  get: () => String(props.cobranza.impuesto_id || ''),
  // SET: Actualiza el valor en el objeto 'cobranza'.
  set: (value) => { props.cobranza.impuesto_id = value; }
});

const lugarPagoIdModel = computed({
  // GET: Asegura que el valor que lee el v-select sea siempre un número.
  get: () => Number(props.cobranza.lugar_pago_id),
  // GET: Asegura que el valor que lee el v-select sea siempre un string.
  get: () => String(props.cobranza.lugar_pago_id || ''),
  // SET: Actualiza el valor en el objeto 'cobranza'.
  set: (value) => { props.cobranza.lugar_pago_id = value; }
});

const fechaOrdenFormateada = computed(() => {
  if (!props.orden.fecha_orden) return "";
  const [year, month, day] = props.orden.fecha_orden.split("-");
  return `${day}/${month}/${year}`;
});

const fechaPagoFormateada = computed(() => {
  if (!props.cobranza.fecha_pago) return "";
  const [year, month, day] = props.cobranza.fecha_pago.split("-");
  return `${day}/${month}/${year}`;
});

const Total_Multa = computed(() => 
  (Number(props.cobranza.multa_fondo) || 0) + 
  (Number(props.cobranza.multa_forma) || 0)
);

const Total_Cobranza = computed(() => {
    const principal = Number(props.cobranza.principal) || 0;
    const actualizacion = Number(props.cobranza.actualizacion) || 0;
    const recargo = Number(props.cobranza.recargo) || 0;
    return Total_Multa.value + principal + actualizacion + recargo;
});

const Total_Cobranza_Formateado = computed(() => formatCurrency(Total_Cobranza.value));
const Total_Multa_Formateado = computed(() => formatCurrency(Total_Multa.value));

const validarFecha = (valor) => {
    if (!valor) return true; // Opcional
    const regex = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/;
    if (!regex.test(valor)) return "Formato inválido (DD/MM/AAAA)";
    const [dia, mes, anio] = valor.split("/").map(n => parseInt(n, 10));
    const fechaObj = new Date(anio, mes - 1, dia);
    if (fechaObj.getFullYear() !== anio || fechaObj.getMonth() + 1 !== mes || fechaObj.getDate() !== dia) {
        return "La fecha no es válida";
    }
    return true;
};
</script>

<style scoped>
.texto-grande {
  font-weight: bold;
}
.mayusculas input {
  text-transform: uppercase;
}
</style>