<template>
  <v-card max-width="1280px" class="mx-auto mt-5">
    <v-card-title class="pink darken-4 white--text py-2">
      DATOS DE LA ORDEN
      <v-spacer></v-spacer>
      <v-btn class="ml-2 my-1" color="grey darken-2" dark @click="$emit('regresar')">
        <v-icon class="mr-2">mdi-arrow-u-left-top</v-icon>
        Regresar
      </v-btn>
    </v-card-title>
    <v-card-text>
      <!-- Orden,fecha,RFC y nombre -->
      <v-row class="my-2 pt-0">
        <!-- Orden -->
        <v-col class="my-0 py-0" cols="12" md="2">
          <v-text-field
            readonly
            class="my-0 py-0 mayusculas texto-grande"
            :value="orden.orden"
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
      <v-row>
        <!-- Domicilio -->
        <v-col class="my-0 py-0" cols="12" md="8">
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
        <v-col class="my-0 py-0" cols="12" md="4">
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
      
      <v-row class="my-2 mx-1 pt-5">
        <v-col cols="12">
          <div v-if="props.totalescobranzas.length > 0">
            <v-simple-table dense class="tabla-totales">
              <template v-slot:default>
                <thead>
                  <tr>
                    <th 
                      v-for="header in encabezadosTotales" 
                      :key="header.cobranza" 
                      :class="header.class"
                      :style="{ width: header.width + 'px', textAlign: header.align }"
                    >
                      {{ header.text }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in props.totalescobranzas"
                   :key="`${item.orden_id}-${item.Cobranza}`"                   >
                    <td class="text-center">{{ item.Cobranza }}</td>
                    <td class="text-center">{{ item.Total_Pagos }}</td>
                    <td class="text-right">{{ formatearMoneda(item.Total_Principal) }}</td>
                    <td class="text-right">{{ formatearMoneda(item.Total_Actualizacion) }}</td>
                    <td class="text-right">{{ formatearMoneda(item.Total_Recargo) }}</td>
                    <td class="text-right">{{ formatearMoneda(item.Total_Multa_Fondo) }}</td>
                    <td class="text-right">{{ formatearMoneda(item.Total_Multa_Forma) }}</td>
                    <td class="text-right font-weight-bold">{{ formatearMoneda(item.Total_Multa) }}</td>
                    <td class="text-right font-weight-bold green lighten-4"> <h2>{{ formatearMoneda(item.Gran_Total) }}</h2></td>
                  </tr>
                </tbody>
              </template>
            </v-simple-table>
          </div>
          <div>
            <v-alert v-if="props.totalescobranzas.length === 0"
              type="error"
              border="left"
              elevation="2"
              icon="mdi-cash"
            >
              No hay cobranza registrada para esta orden.
            </v-alert>
          </div>
          <!-- <v-alert v-else :value="true" color="info" icon="mdi-information-outline">
            No hay totales de cobranza para mostrar.
          </v-alert> -->
        </v-col>
      </v-row>

      <!-- Historicos Avances y Cobranzas -->
      <v-row>
        <v-col cols="12" class="mx-0">
          <HistoricoAvances 
            :avances="avances"
            @nuevo-avance="$emit('nuevo-avance')"
            @editar-avance="(item) => $emit('editar-avance', item)"
          />
        </v-col>
        <!-- Historico Cobranza -->
        <v-col cols="12" class="mx-0">
          <HistoricoCobranzas 
            :cobranzas="cobranzas"
            @nueva-cobranza="$emit('nueva-cobranza')"
            @editar-cobranza="(item) => $emit('editar-cobranza', item)"
          />
          <!-- <v-card>
            <v-card-title class="green lighten-1 white--text py-2">
              <v-row class="mx-0">
                <v-col cols="11" class="mx-0">Historico de Cobranza</v-col>
                <v-spacer></v-spacer>
                <v-col cols="1" class="mx-0 text-right">
                  <v-tooltip left>
                    <template v-slot:activator="{ on, attrs }">
                      <v-btn class="mr-4" v-bind="attrs" v-on="on" small dark @click="$emit('nueva-cobranza')">
                        Nuevo<v-icon>mdi-cash-plus</v-icon>
                      </v-btn>
                    </template>
                    <span>Registrar nueva Cobranza</span>
                  </v-tooltip>
                </v-col>
              </v-row>
            </v-card-title>
            <v-card-text class="pt-3">
              <v-container>
              </v-container>
            </v-card-text>
          </v-card> -->
        </v-col>
      </v-row>
    </v-card-text>
    <v-card-actions class="grey darken-4">
      <v-spacer></v-spacer>
      <v-btn class="ml-2 my-1" color="grey darken-2" dark @click="$emit('regresar')">
        <v-icon class="mr-2">mdi-arrow-u-left-top</v-icon>
        Regresar
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { computed, ref } from 'vue';
import HistoricoAvances from './HistoricoAvances.vue';
import HistoricoCobranzas from './HistoricoCobranzas.vue';

const encabezadosTotales = ref([
    { text: "Tipo de Cobranza", value: "Cobranza", class: "brown lighten-2 white--text elevation-1", width:"100", align: 'center' },
    { text: "Total Pagos", value: "Total_Pagos", class: "brown lighten-2 white--text elevation-1", width:"80", align: 'center' },
    { text: "Total Principal", value: "Total_Principal", class: "brown lighten-2 white--text elevation-1", width: "125", align: 'end' },
    { text: "Total Actualizacion", value: "Total_Actualizacion", class: "brown lighten-2 white--text elevation-1", width:"125", align: 'end' },
    { text: "Total Recargo", value: "Total_Recargo", class: "brown lighten-2 white--text elevation-1", width:"125", align: 'end' },
    { text: "Total Multa Fondo", value: "Total_Multa_Fondo", class: "brown lighten-2 white--text elevation-1", width:"125", align: 'end' },
    { text: "Total Multa Forma", value: "Total_Multa_Forma", class: "brown lighten-2 white--text elevation-1", width:"125", align: 'end' },
    { text: "Total Multas", value: "Total_Multa", class: "brown lighten-2 white--text elevation-1", width:"125", align: 'end' },
    { text: "Gran Total", value: "Gran_Total", class: "brown lighten-2 white--text elevation-1", width:"125", align: 'end' },
]);

const props = defineProps({
  orden: {
    type: Object,
    required: true,
  },
  avances: {
    type: Array,
    required: true,
  },
  cobranzas: {
    type: Array,
    required: true,
  },
  totalescobranzas: {
    type: Array,
    required: true,
  }
});

defineEmits(['regresar', 'nuevo-avance', 'editar-avance', 'nueva-cobranza', 'editar-cobranza']);

const fechaOrdenFormateada = computed(() => {
  if (!props.orden.fecha_orden) return "";
  const [year, month, day] = props.orden.fecha_orden.split("-");
  return `${day}/${month}/${year}`;
});

const formatearMoneda = (valor) => {
  const numero = Number(valor);
  if (isNaN(numero)) return '$0.00';
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(numero);
};
</script>

<style scoped>
.texto-grande {
  font-size: 22px; /* Ajustado a un valor razonable */
  font-weight: bold;
}

.tabla-totales th, .tabla-totales td {
  border: 1px solid #E0E0E0; /* Borde gris claro para todas las celdas */
}

.tabla-totales th {
  font-weight: bold !important; /* Asegura que el texto del encabezado sea negrita */
  font-size: 13px !important;
}
</style>
