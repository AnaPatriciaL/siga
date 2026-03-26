<template>
  <v-container>
    <BusquedaOrden v-if="!orden_encontrada" @buscar="buscarOrden" />

    <!-- Datos de la Orden -->
    <DetallesOrden 
      v-if="orden_encontrada"
      :orden="datos_orden"
      :avances="listadoAvancesPorOrden"
      :cobranzas="listadoCobranzasPorOrden"
      :totalescobranzas="totalCobranzasPorOrden"
      @regresar="regresar"
      @nuevo-avance="capturaAvance"
      @editar-avance="EditarAvance"
      @nueva-cobranza="capturaCobranza"
      @editar-cobranza="EditarCobranza"
    />

    <!-- Componente de Diálogo para Cobranza -->
    <DialogoCobranza
      v-model="dialogCobranza"
      :orden="datos_orden"
      :cobranza.sync="cobranza"
      :impuestos="cat_impuestos"
      :lugaresPago="cat_LugaresPago"
      :tipos-pagos="cat_TiposPago"
      :es-edicion="esEdicionCobranza"
      @guardar="validarCobranza"
      @cerrar="resetCobranza"
      />
      <!-- :nombre-auditor="nombreAuditor"
      :nombre-supervisor="nombreSupervisor" -->

    <!-- Componente de Diálogo para Avances -->
    <DialogoAvance
      v-if="dialogAvance"
      v-model="dialogAvance"
      :orden="datos_orden"
      :seguimiento="seguimiento"
      :cat_seguimientos="cat_seguimientos"
      :cat_impuestos="cat_impuestos"
      :cat_LugaresPago="cat_LugaresPago"
      :cat_TiposPago="cat_TiposPago"
      :es-edicion="esEdicion"
      @guardar="validarSeguimiento"
      @update:seguimiento="handleSeguimientoUpdate"
      />
      <!-- :nombre-auditor="nombreAuditor"
      :nombre-supervisor="nombreSupervisor" -->

    <!-- Cargando  -->
    <v-dialog v-model="cargando" max-width="290" persistent>
      <v-card color="pink darken-4" dark>
        <v-card-text class="pt-3">
          Buscando información
          <v-progress-linear indeterminate color="white" class="my-3"></v-progress-linear>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, getCurrentInstance } from 'vue';
import BusquedaOrden from '@/components/BusquedaOrden.vue';
import DetallesOrden from '@/components/DetallesOrden.vue';
import DialogoAvance from '@/components/DialogoAvance.vue';
import DialogoCobranza from '@/components/DialogoCobranza.vue';
import api from '@/services/api'; // Importación local del servicio de API
import { formatCurrency } from '@/utils/formatters'; // Importación local del formateador
import Swal from 'sweetalert2';

// Para acceder a propiedades globales como $user en Vue 2 con Composition API
const instance = getCurrentInstance();
const { $user } = instance.proxy;

const hoy = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().substr(0, 10);

const cargando = ref(false);
const dialogCobranza = ref(false);
const dialogAvance = ref(false);
// const nombreAuditor = ref('');
const esEdicionCobranza = ref(false);
const esEdicion = ref(false);
// const nombreSupervisor = ref('');
const menu2 = ref(false);
const orden_encontrada = ref(false);
const datos_orden = ref({});
const listadoAvancesPorOrden = ref([]);
const listadoCobranzasPorOrden = ref([]);
const totalCobranzasPorOrden = ref([]);
const cat_seguimientos = ref([]);
const cat_impuestos = ref([]);
const cat_LugaresPago = ref([]);
const cat_TiposPago = ref([]);

const seguimiento = reactive({
    // id: null,
    orden_id:'', 
    usuario_id:'', 
    fecha: hoy, 
    cve_seguimiento:'',
    descripcion_seguimiento:'',
    descripcion_estado_seguimiento:'', 
    total_credito:null, 
    auditor_cve:'', nombre_auditor:'', 
    supervisor_cve:'', nombre_supervisor:'',
    observaciones:'',
});

const handleSeguimientoUpdate = (nuevoSeguimiento) => {
  // El diálogo emite el objeto completo.
  // Usamos Object.assign para actualizar el objeto reactivo sin perder su referencia.
  // Esto asegura que todas las propiedades, incluyendo id, orden_id, etc., se preserven.
  Object.assign(seguimiento, nuevoSeguimiento);
};

const handleCobranzaUpdate = (nuevaCobranza) => {
  // Actualiza el objeto 'cobranza' reactivo con los datos del diálogo.
  Object.assign(cobranza, nuevaCobranza);
};

const cobranza = reactive({
    // id: null,
    orden_id: '',
    usuario_id: '',
    fecha_pago: hoy,
    periodo_inicial: '',
    periodo_final: '',
    principal: 0,
    actualizacion: 0,
    recargo: 0,
    lugar_pago_id: null, // Corregido: nombre de propiedad estandarizado
    impuesto_id: null,
    no_operacion: '',
    tipo_pago_id: null, // Corregido: nombre de propiedad estandarizado
    multa_fondo: 0,
    multa_forma: 0,
    observaciones: '',
    auditor_cve: '',
    nombre_auditor: '',
    supervisor_cve: '',
    nombre_supervisor: '',
});

// const buscarOrden = async (ordenABuscar) => {
//     if (!ordenABuscar) {
//         Swal.fire({
//             icon: "warning", title: "Advertencia", confirmButtonColor: "#d33",
//             html: "Por favor, ingrese un numero de <strong>orden</strong> para buscar.",
//         });
//         return;
//     }
    
//     cargando.value = true;
//     try {
//         const response = await api.buscarOrden(ordenABuscar);
//         if (response.data) {
//             datos_orden.value = response.data;
//             seguimiento.orden_id = datos_orden.value.id;
//             cobranza.orden_id = datos_orden.value.id;
//             orden_encontrada.value = true;

//             // Ejecutar ambos en paralelo (más rápido)
//             await Promise.all([
//                 listadoAvancePorOrden(),
//                 listadoCobranzaPorOrden()
//             ]);
//             // await listadoAvancePorOrden();
//         } else {
//             datos_orden.value = {};
//             Swal.fire({
//                 icon: "info", 
//                 confirmButtonColor: "#3085d6", 
//                 title: "No se encontraron resultados",
//                 html: `Con el numero de orden:<br><strong>${ordenABuscar.toUpperCase()}</strong>`,
//             });
//         }
//     } catch (error) {
//         console.error("Error al realizar la consulta:", error);
//         Swal.fire({
//             icon: "error", title: "Error", confirmButtonColor: "#d33",
//             text: "Ocurrió un error al obtener los datos. Consulte la consola.",
//         });
//     } finally {
//         cargando.value = false;
//     }
// };
const buscarOrden = async (ordenABuscar) => {
    if (!ordenABuscar) {
        Swal.fire({
            icon: "error", 
            title: "Error", 
            confirmButtonColor: "#d33",
            html: "Por favor, ingrese un numero de <strong>orden</strong> a buscar.",
        });
        return;
    }
    
    cargando.value = true;
    try {
        const response = await api.buscarOrden(ordenABuscar);
        if (response.data) {
            datos_orden.value = response.data;
            seguimiento.orden_id = datos_orden.value.id;
            cobranza.orden_id = datos_orden.value.id;
            orden_encontrada.value = true;
            
            // Ejecutar ambos en paralelo (más rápido)
            await Promise.all([
                listadoAvancePorOrden(),
                listadoCobranzaPorOrden(),
                totalesCobranzaPorOrden()
            ]);
            
        } else {
            datos_orden.value = {};
            Swal.fire({
                icon: "info", 
                confirmButtonColor: "#3085d6", 
                title: "No se encontraron resultados",
                html: `Con el numero de orden:<br><strong>${ordenABuscar.toUpperCase()}</strong>`,
            });
        }
    } catch (error) {
        console.error("Error al realizar la consulta:", error);
        Swal.fire({
            icon: "error", 
            title: "Error", 
            confirmButtonColor: "#d33",
            text: "Ocurrió un error al obtener los datos. Consulte la consola.",
        });
    } finally {
        cargando.value = false;
    }
};

const listadoAvancePorOrden = async () => {
    cargando.value = true;
    try {
        const response = await api.obtenerAvances(seguimiento.orden_id);
        // La función api.obtenerAvances ya extrae el array de datos.
        listadoAvancesPorOrden.value = response.data.data || [];
    } catch (error) {
        console.error("Error al obtener avances:", error);
        Swal.fire({
            icon: "error", title: "Error", text: "Ocurrió un error al obtener los avances."
        });
    } finally {
        cargando.value = false;
    }
};

const totalesCobranzaPorOrden = async () => {
    cargando.value = true;
    try {
        const response = await api.obtenerTotalesCobranzas(datos_orden.value.id);
        // La API devuelve un objeto, pero v-data-table espera un array.
        // La API ya devuelve un array, así que lo asignamos directamente.
        totalCobranzasPorOrden.value = response.data.data || [];
        // console.log('totalCobranzasPorOrden: ',totalCobranzasPorOrden.value);
    } catch (error) {
        console.error("Error al obtener cobranzas:", error);
        Swal.fire({
            icon: "error", title: "Error", text: "Ocurrió un error al obtener los totales de cobranzas."
        });
    } finally {
        cargando.value = false;
    }
};


const listadoCobranzaPorOrden = async () => {
    cargando.value = true;
    try {
        const response = await api.obtenerCobranzas(cobranza.orden_id);
        // La función api.obtenerAvances ya extrae el array de datos.
        listadoCobranzasPorOrden.value = response.data.data || [];
        // console.log('listadoCobranzasPorOrden: ',listadoCobranzasPorOrden.value);
    } catch (error) {
        console.error("Error al obtener avances:", error);
        Swal.fire({
            icon: "error", title: "Error", text: "Ocurrió un error al obtener los avances."
        });
    } finally {
        cargando.value = false;
    }
};


const cargaSeguimientos = async () => {
    try {
        const response = await api.obtenerSeguimientos();
        cat_seguimientos.value = response.data.success ? response.data.data : [];
    } catch (error) {
        console.error("Error al cargar datos de seguimiento:", error);
    }
};

const cargaImpuestos = async () => {
    try {
        const response = await api.obtenerImpuestos();
        cat_impuestos.value = response.data.success ? response.data.data : [];
    } catch (error) {
        console.error("Error al cargar datos de Impuestos:", error);
    }
};

const cargaLugaresPago = async () => {
    try {
        const response = await api.obtenerLugaresPago();
        cat_LugaresPago.value = response.data.success ? response.data.data : [];
    } catch (error) {
        console.error("Error al cargar datos de Lugares de Pago:", error);
    }
};

const cargaTiposPagos = async () => {
    try {
        const response = await api.obtenerTiposPago(); // Corregido: Llamar a la función correcta de la API
        cat_TiposPago.value = response.data.success ? response.data.data : []; // Asumo que la propiedad es cat_TiposPago
    } catch (error) {
        console.error("Error al cargar datos de Tipos de Pago:", error);
    }
};


const capturaCobranza = () => {
    esEdicionCobranza.value = false;
    resetCobranza(); // Limpia el objeto 'cobranza' a su estado inicial
    dialogCobranza.value = true;
};

const capturaAvance = () => {
  esEdicion.value = false;
  resetSeguimiento(); // Limpia completamente
  dialogAvance.value = true;
};

const resetSeguimiento = () => {
  // Usamos Object.assign para modificar el objeto reactivo sin romper la referencia
  Object.assign(seguimiento, {
    id: null,
    orden_id: datos_orden.value.id || '',
    usuario_id: $user.id || '',
    fecha: hoy,
    cve_seguimiento:'',
    descripcion_seguimiento:'',
    descripcion_estado_seguimiento:'',
    total_credito:null,
    auditor_cve:'',
    nombre_auditor:'',
    supervisor_cve:'',
    nombre_supervisor:'',
    observaciones:'',
  });
};

const EditarAvance = (item) => {
  esEdicion.value = true;

  // console.log('EditarAvance item: ',item);
  // Usamos Object.assign para poblar el objeto reactivo
  Object.assign(seguimiento, {
    id: item.avance_id,
    orden_id: item.orden_id,
    usuario_id: item.usuario_id,
    fecha: item.fecha_avance || hoy,
    cve_seguimiento: item.cve_seguimiento,
    descripcion_seguimiento: item.descripcion_seguimiento || '',
    descripcion_estado_seguimiento: item.descripcion_estado_seguimiento || '',
    total_credito: item.total_credito,
    auditor_cve: item.auditor_cve || '',
    nombre_auditor: item.nombre_auditor || '',
    supervisor_cve: item.supervisor_cve || '',
    nombre_supervisor: item.nombre_supervisor || '',
    observaciones: item.observaciones || '',
  });
  dialogAvance.value = true;
};

const EditarCobranza = (item) => {
  // LOG 1: Muestra el objeto 'item' tal como viene de la tabla.
    //   console.log("1. [PADRE] Datos originales del item:", JSON.parse(JSON.stringify(item)));

  esEdicionCobranza.value = true;
  // 1. Poblamos el objeto 'cobranza' con los datos del item a editar.
  // Se puebla el objeto 'cobranza' con los datos del item a editar.
  Object.assign(cobranza, {
    ...item, 
    // Mapeamos las fechas de período a las propiedades correctas del formulario
    periodo_inicial: item.periodo_inicial, 
    periodo_final: item.periodo_final,
    // Con la API corregida, los IDs ya son numéricos o vienen como números.
    // Solo nos aseguramos de que sean del tipo correcto.
    lugar_pago_id: Number(item.lugar_pago_id) || null,
    impuesto_id: Number(item.impuesto_id) || null,
    tipo_pago_id: Number(item.tipo_pago_id) || null,
  });

  // LOG 2: Muestra el objeto 'cobranza' después de la conversión a número.
    //   console.log("2. [PADRE] Objeto 'cobranza' preparado para el diálogo:", JSON.parse(JSON.stringify(cobranza)));

  // 2. Usamos nextTick para asegurar que Vue haya procesado los cambios en 'cobranza'
  // ANTES de abrir el diálogo. Esto resuelve el problema de sincronización.
  nextTick(() => {
    dialogCobranza.value = true;
  });
};

const resetCobranza = () => {
  // Se resetea el objeto 'cobranza' a sus valores por defecto para un nuevo registro.
  Object.assign(cobranza, {
    id: null, // Se limpia el ID para nuevos registros
    orden_id: datos_orden.value.id || '', // Se preserva el ID de la orden
    usuario_id: $user.id || '', // Se asigna el ID del usuario logueado
    fecha_pago: hoy, // Fecha de pago por defecto
    periodo_inicial: '', // Aseguramos que la propiedad exista
    periodo_final: '', // Aseguramos que la propiedad exista
    principal: 0,
    actualizacion: 0,
    recargo: 0,
    lugar_pago_id: null, // Corregido: nombre de propiedad estandarizado
    impuesto_id: null,
    no_operacion: '',
    tipo_pago_id: null, // Corregido: nombre de propiedad estandarizado
    multa_fondo: 0,
    multa_forma: 0,
    auditor_cve: '',
    nombre_auditor: '',
    supervisor_cve: '',
    nombre_supervisor: '',
    observaciones: '',
  });
};

const regresar = () => {
    orden_encontrada.value = false;
    datos_orden.value = {};
    Object.keys(seguimiento).forEach(key => {
        if (typeof seguimiento[key] === 'number') seguimiento[key] = 0;
        else if (typeof seguimiento[key] === 'boolean') seguimiento[key] = false;
        else seguimiento[key] = null;
    });
    seguimiento.fecha = '';
};

const validarSeguimiento = async () => {
    // console.log('Seguimiento en validarSeguimiento: ',seguimiento);
    // 1. Construir el payload con los datos requeridos
    const payload = {
        id: seguimiento.id, // Será null para nuevos, y tendrá valor para edición
        orden_id: seguimiento.orden_id,
        usuario_id: seguimiento.usuario_id,
        fecha: seguimiento.fecha,
        cve_seguimiento: seguimiento.cve_seguimiento,
        total_credito: seguimiento.total_credito,
        auditor_cve: seguimiento.auditor_cve,
        supervisor_cve: seguimiento.supervisor_cve,
        observaciones: (seguimiento.observaciones || '').toUpperCase(),
    };

    // Validación básica
    if (!payload.cve_seguimiento) {
        Swal.fire('Error', 'La clave de seguimiento es obligatoria.', 'error');
        return;
    }

    cargando.value = true;
    try {
        // 2. Enviar los datos al backend
        const response = await api.guardarAvance(payload);

        if (response.data.success) {
            Swal.fire('¡Guardado!', 'El avance ha sido guardado correctamente.', 'success');
            dialogAvance.value = false; // Cierra el diálogo
            resetSeguimiento(); // Limpiamos el formulario DESPUÉS de guardar y cerrar
            await listadoAvancePorOrden(); // Recarga la lista de avances
        } else {
            Swal.fire('Error', response.data.message || 'Ocurrió un error al guardar.', 'error');
        }
    } catch (error) {
        console.error("Error al guardar el avance:", error);
        Swal.fire('Error', 'No se pudo conectar con el servidor para guardar el avance.', 'error');
    } finally {
        // Aseguramos la limpieza también en caso de error si el diálogo sigue abierto
        cargando.value = false;
    }
};

const validarCobranza = async () => {
    // console.log('Cobranza en validarCobranza: ', cobranza);
      // Función auxiliar para convertir fecha de DD/MM/AAAA a AAAA-MM-DD
    const convertirFechaParaAPI = (fechaDDMMAAAA) => {
        if (!fechaDDMMAAAA || typeof fechaDDMMAAAA !== 'string' || fechaDDMMAAAA.length !== 10) {
            return null; // Devuelve null si la fecha no es válida o está vacía
        }
        const [dia, mes, anio] = fechaDDMMAAAA.split('/');
        return `${anio}-${mes}-${dia}`;
    };

    const payload = { ...cobranza };
    payload.observaciones = (payload.observaciones || '').toUpperCase();
    // Convertimos las fechas al formato esperado por el backend
    payload.periodo_inicial = convertirFechaParaAPI(payload.periodo_inicial);
    payload.periodo_final = convertirFechaParaAPI(payload.periodo_final);

    // Validación básica (puedes añadir más)
    if (!payload.fecha_pago || !payload.principal || !payload.lugar_pago_id || !payload.impuesto_id) {
        Swal.fire('Campos Incompletos', 'Fecha de Pago, Principal, lugarPago e Impuesto son obligatorios.', 'warning');
        return;
    }

    cargando.value = true;
    try {
        // Asumimos que tendrás un endpoint 'guardarCobranza' en tu API
        const response = await api.guardarCobranza(payload);

        if (response.data.success) {
            Swal.fire('¡Guardado!', 'El registro de cobranza ha sido guardado.', 'success');
            dialogCobranza.value = false;
            resetCobranza();
            // Aquí deberías recargar la lista de cobranzas, similar a listadoAvancePorOrden()
            await listadoCobranzaPorOrden(); 
        } else {
            Swal.fire('Error', response.data.message || 'Ocurrió un error al guardar la cobranza.', 'error');
        }
    } catch (error) {
        console.error("Error al guardar la cobranza:", error);
        Swal.fire('Error', 'No se pudo conectar con el servidor para guardar la cobranza.', 'error');
    } finally {
        cargando.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    cargaSeguimientos();
    cargaImpuestos();
    cargaLugaresPago();
    cargaTiposPagos();
});
</script>

<style>
/* Estilos que antes estaban en Captura.vue y son globales */
</style>

<style>
.v-container {
  max-width: 800px;
  margin: 0 auto;
}

.mayusculas input {
  text-transform: uppercase;
}
/* Estilo personalizado para el botón */
.swal-btn-ok {
  background-color: #dc3741; /* Color de fondo */
  border-color: #dc3741; /* Color del borde */
  color: white !important; /* Color del texto (con !important para asegurarse) */
  font-weight: bold; /* Tipo de letra en negrita */
  padding: 10px 20px; /* Ajuste del padding para que se vea mejor */
  font-size: 16px; /* Tamaño de la fuente */
  border-radius: 12px; /* Bordes redondeados */
}
.swal-btn-error {
  background-color: #dc3741; /* Color de fondo */
  border-color: #dc3741; /* Color del borde */
  color: white !important; /* Color del texto (con !important para asegurarse) */
  font-weight: bold; /* Tipo de letra en negrita */
  padding: 10px 20px; /* Ajuste del padding para que se vea mejor */
  font-size: 16px; /* Tamaño de la fuente */
  border-radius: 12px; /* Bordes redondeados */
}

.swal-btn-ok:hover {
  border-color: #b10743; /* Color del borde al pasar el ratón */
  color: yellow !important;
}
</style>
