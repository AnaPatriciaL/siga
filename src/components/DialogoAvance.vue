<template>
  <v-dialog :value="value" @input="$emit('input', $event)" max-width="1100px" persistent @after-enter="enfocarSeguimiento">
    <v-card>
      <v-card-title class="pink darken-4 white--text py-2">
        {{ esEdicion ? 'Editar' : 'Registrar' }} Avance
      </v-card-title>
      <v-card-text class="mb-2 py-0">
        <v-container>
          <!-- Orden,fecha,RFC y nombre -->
          <v-row class="my-2 pt-0">
            <!-- Orden -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field
                readonly
                class="my-0 py-0 mayusculas texto-grande"
                :value="orden.orden"
                label="Orden"
                outlined
                hide-details
                dense
                background-color="deep-orange lighten-3"
              ></v-text-field>
            </v-col>
            <!-- Fecha Orden -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field
                reverse
                readonly
                class="my-0 py-0 texto-grande"
                :value="fechaOrdenFormateada"
                label="Fecha Orden"
                outlined
                hide-details
                dense
              ></v-text-field>
            </v-col>
            <!-- RFC -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field
                readonly
                class="my-0 py-0 mayusculas texto-grande"
                :value="orden.rfc"
                label="RFC"
                outlined
                hide-details
                dense
              ></v-text-field>
            </v-col>
            <!-- Nombre -->
            <v-col class="my-0 py-0" cols="12" md="6">
              <v-text-field
                class="my-0 py-0 mayusculas"
                :value="orden.nombre"
                label="Nombre"
                outlined
                maxlength="300"
                dense
                readonly
                hide-details
              ></v-text-field>
            </v-col>
          </v-row>
          <!-- Domicilio y Oficina -->
          <v-row class="my-2 pt-0">
            <!-- Domicilio -->
            <v-col class="my-0 py-0" cols="12" md="10">
              <v-text-field
                readonly
                class="my-0 py-0 mayusculas"
                :value="orden.domicilio"
                label="Domicilio"
                maxlength="250"
                outlined
                dense
                hide-details
              ></v-text-field>
            </v-col>
            <!-- Oficina -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field
                readonly
                class="my-0 py-0 mayusculas"
                :value="orden.oficina"
                label="Oficina SATES"
                maxlength="250"
                outlined
                dense
                hide-details
              ></v-text-field>
            </v-col>
          </v-row>
          <v-row>
            <!-- Seguimiento -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field
                v-model="cveSeguimientoLocal"
                ref="seguimiento_cve_ref"
                type="text"
                label="Clave Seguimiento"
                outlined
                dense
                reverse
                maxlength="2"
                @keydown.enter.prevent
                @keyup.enter="buscarSeguimiento"
                autofocus
              ></v-text-field>
            </v-col>

            <!-- Boton buscar -->
            <v-col class="my-0 py-0" cols="12" md="1">
              <v-btn dense color="orange" dark @click="buscarSeguimiento" type="button">
                <v-icon>mdi-database-search-outline</v-icon>
              </v-btn>
            </v-col>

            <!-- Descripción Seguimiento -->
            <v-col class="my-0 py-0" cols="12" md="4">
              <v-text-field v-if="cveSeguimientoLocal"
                disabled
                readonly
                :value="seguimiento.descripcion_seguimiento"
                class="my-0 py-0 mayusculas"
                label="Seguimiento"
                outlined
                maxlength="300"
                dense
              ></v-text-field>
            </v-col>
            <!-- Estatus del Seguimiento -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field v-if="cveSeguimientoLocal"
                disabled
                readonly
                :value="seguimiento.descripcion_estado_seguimiento"
                class="my-0 py-0 mayusculas"
                label="Estatus"
                outlined
                maxlength="300"
                dense
              ></v-text-field>
            </v-col>
            <!-- Fecha del Seguimiento -->
            <v-col class="my-0 py-0" cols="12" md="3">
              <v-menu
                v-model="menuFecha"
                :close-on-content-click="false"
                transition="scale-transition"
                offset-y
                max-width="290px"
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    outlined
                    dense
                    :value="fechaSeguimientoFormateada"
                    label="Fecha Seguimiento"
                    persistent-hint
                    prepend-icon="mdi-calendar"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  />
                </template>
                <v-date-picker
                  :value="seguimiento.fecha"
                  @input="val => { updateSeguimiento('fecha', val); menuFecha = false; }"
                  no-title
                  locale="es"
                ></v-date-picker>
              </v-menu>
            </v-col>

            <!-- Auditor -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field
                :value="seguimiento.auditor_cve"
                ref="auditor_cve_ref"
                @input="updateSeguimiento('auditor_cve', $event)"
                class="my-0 py-0 mayusculas"
                label="Clave Auditor"
                outlined
                maxlength="4"
                dense
                @keydown.enter.prevent="supervisor_cve_ref.focus()"
              ></v-text-field>
            </v-col>
            <!-- Nombre Auditor -->
            <v-col class="my-0 py-0" cols="12" md="4">
              <v-text-field
                disabled
                :value="seguimiento.nombre_auditor"  
                class="my-0 py-0 mayusculas"
                label="Nombre Auditor"
                outlined
                maxlength="300"
                dense
              ></v-text-field>
            </v-col>
            <!-- Supervisor -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field
                :value="seguimiento.supervisor_cve"
                ref="supervisor_cve_ref"
                @input="updateSeguimiento('supervisor_cve', $event)"
                class="my-0 py-0 mayusculas"
                label="Clave Supervisor"
                outlined
                maxlength="4"
                dense
                @keydown.enter.prevent="enfocarSiguienteDesdeSupervisor"
              ></v-text-field>
            </v-col>
            <!-- Nombre Supervisor -->
            <v-col class="my-0 py-0" cols="12" md="4">
              <v-text-field
                disabled
                :value="seguimiento.nombre_supervisor"
                class="my-0 py-0 mayusculas"
                label="Nombre Supervisor"
                outlined
                maxlength="300"
                dense
              ></v-text-field>
            </v-col>
            <!-- Total Credito -->
            <v-col class="my-0 py-0" cols="12" md="2">
              <v-text-field
                v-if="[48, 50, 43].includes(Number(seguimiento.cve_seguimiento))"
                reverse
                ref="total_credito_ref"
                :value="seguimiento.total_credito"
                @input="updateSeguimiento('total_credito', $event)"
                class="my-0 py-0 mayusculas"
                label="Total Crédito"
                suffix="$"
                outlined
                maxlength="20"
                dense
                @keydown.enter.prevent="observaciones_ref.focus()"
              ></v-text-field>
            </v-col>
            <!-- Observaciones -->
            <v-col class="my-0 py-0" cols="12" md="10">
              <v-text-field
                :value="seguimiento.observaciones"
                ref="observaciones_ref"
                @input="updateSeguimiento('observaciones', $event)"
                class="my-0 py-0 mayusculas"
                label="Observaciones"
                outlined
                maxlength="300"
                dense
                @keydown.enter.prevent="cancelar_btn_ref.$el.focus()"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>
      <v-divider></v-divider>
      <v-card-actions class="grey lighten-2 py-2">
        <v-spacer></v-spacer>
        <v-btn 
          ref="guardar_btn_ref" 
          class="my-1 ma-2 py-1" 
          color="primary" 
          @click="intentarGuardar" 
          dark
        > Guardar 
          <v-icon dark right>mdi-checkbox-marked-circle</v-icon>
        </v-btn>
        <v-btn ref="cancelar_btn_ref" class="ma-2" dark @click="$emit('input', false)">
          Cancelar <v-icon dark left>mdi-cancel</v-icon>
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  value: Boolean, // Para v-model del dialog
  orden: { type: Object, required: true },
  seguimiento: { type: Object, required: true },
  cat_seguimientos: { type: Array, default: () => [] },
  nombreAuditor: String,
  nombreSupervisor: String,
  esEdicion: Boolean,
});

const emit = defineEmits(['input', 'guardar', 'update:seguimiento', 'cerrar']);

// Observador para detectar cuando el diálogo se cierra (desde fuera o por tecla Esc)
const unwatch = watch(() => props.value, (newValue, oldValue) => {
  // Si el valor cambia de true a false, significa que el diálogo se cerró.
  if (oldValue === true && newValue === false) {
    emit('cerrar');
  }
});
onUnmounted(unwatch); // Limpiar el observador al destruir el componente

const menuFecha = ref(false);
const seguimiento_cve_ref = ref(null);
const auditor_cve_ref = ref(null);
const supervisor_cve_ref = ref(null);
const total_credito_ref = ref(null);
const observaciones_ref = ref(null);
const guardar_btn_ref = ref(null);
const cancelar_btn_ref = ref(null);


// Variable para controlar el estado de la alerta y evitar ciclos
let isAlertShowing = false;

// Variable local para manejar el v-model del campo de clave
const cveSeguimientoLocal = ref('');

// Sincronizar la variable local cuando la prop del padre cambie (al abrir/editar)
watch(() => props.seguimiento.cve_seguimiento, (newVal) => {
  cveSeguimientoLocal.value = newVal;
}, { immediate: true });

// Sincronizar al padre cuando la variable local cambie
watch(cveSeguimientoLocal, (newVal) => onCveSeguimientoInput(newVal));

// Función para emitir actualizaciones al padre, simulando v-model en objetos
const updateSeguimiento = (key, value) => {
  const nuevoSeguimiento = { ...props.seguimiento, [key]: value };
  // Se emite el objeto completo para evitar que el padre pierda propiedades.
  emit('update:seguimiento', nuevoSeguimiento);
};

const fechaOrdenFormateada = computed(() => {
  if (!props.orden.fecha_orden) return "";
  const [year, month, day] = props.orden.fecha_orden.split("-");
  return `${day}/${month}/${year}`;
});

const fechaSeguimientoFormateada = computed(() => {
  // Usamos la prop directamente
  if (!props.seguimiento.fecha) return "";
  const [year, month, day] = props.seguimiento.fecha.split("-");
  return `${day}/${month}/${year}`;
});

const enfocarSeguimiento = () => nextTick(() => seguimiento_cve_ref.value?.focus());

const enfocarSiguienteDesdeSupervisor = () => {
  if ([48, 50, 43].includes(Number(props.seguimiento.cve_seguimiento))) {
    total_credito_ref.value?.focus();
  } else {
    observaciones_ref.value?.focus();
  }
};

const onCveSeguimientoInput = (value) => {
  const cleanedValue = String(value || '').replace(/\D/g, '').slice(0, 2);
  updateSeguimiento('cve_seguimiento', cleanedValue);
};

const limitarTeclas = (event) => {
  const permitido = ['Backspace', 'ArrowLeft', 'ArrowRight', 'Tab', 'Delete', 'Enter'];
  if (permitido.includes(event.key)) return;
  if (String(props.seguimiento.cve_seguimiento || '').length >= 2 && !permitido.includes(event.key)) {
    event.preventDefault();
  }
  if (!/[0-9]/.test(event.key)) {
    event.preventDefault();
  }
};

const buscarSeguimiento = () => {
  // Si ya se está mostrando una alerta, no hacer nada para evitar el ciclo.
  if (isAlertShowing) {
    return;
  }

  const cve = cveSeguimientoLocal.value;
  const encontrado = props.cat_seguimientos.find(item => Number(item.cve) === Number(cve));

  if (encontrado) {
    // Se agrupan las actualizaciones en un solo objeto para emitir una sola vez.
    // Esto es más eficiente y previene condiciones de carrera.
    const actualizaciones = {
      descripcion_seguimiento: encontrado.descripcion,
      descripcion_estado_seguimiento: encontrado.estado,
    };
    emit('update:seguimiento', { ...props.seguimiento, ...actualizaciones });
    // Abrir el menú de la fecha después de una búsqueda exitosa
    nextTick(() => {
      menuFecha.value = true;
    });

  } else {
    // Si no se encuentra, limpiamos los campos relacionados
    const actualizaciones = {
      descripcion_seguimiento: '',
      descripcion_estado_seguimiento: '',
    };
    emit('update:seguimiento', { ...props.seguimiento, ...actualizaciones });

    Swal.fire({ 
      icon: "error", 
      title: "Oops...", 
      text: "Seguimiento no existe", 
      timer: 1000, 
      showConfirmButton: false, 
      timerProgressBar: true })
      .then(() => {
      nextTick(() => seguimiento_cve_ref.value?.focus());
    });
  }
};

const intentarGuardar = () => {
  // Si la clave de seguimiento tiene un valor pero no se ha validado (no hay descripción)
  if (props.seguimiento.cve_seguimiento && !props.seguimiento.descripcion_seguimiento) {
    // Usamos nextTick para asegurar que el DOM no esté bloqueado
    nextTick(() => {
      // Devolvemos el foco al campo de la clave
      seguimiento_cve_ref.value?.focus();
      // Mostramos una alerta para guiar al usuario y detenemos el guardado
      Swal.fire({
        icon: 'warning',
        title: 'Validación Requerida',
        text: 'Por favor, valide la "Clave Seguimiento" antes de continuar.',
      });
    });
  } else {
    // Si todo está bien, emitimos el evento para que el padre guarde.
    emit('guardar');
  }
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