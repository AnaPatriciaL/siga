<template>
  <v-card>
    <v-card-title class="success white--text py-2">
      <v-row class="mx-0 py-0">
        <v-col cols="11" class="mx-0">
          Historico de Cobranzas
        </v-col>
        <v-spacer></v-spacer>
        <v-col cols="1" class="mx-0 text-right">
          <v-tooltip left>
            <template v-slot:activator="{ on, attrs }">
              <v-btn class="mr-4" v-bind="attrs" v-on="on" small dark @click="$emit('nueva-cobranza')">
                Nueva<v-icon>mdi-cash</v-icon>
              </v-btn>
            </template>
            <span>Registrar nueva Cobranza</span>
          </v-tooltip>
        </v-col>
      </v-row>
    </v-card-title>
    <v-card-text class="pt-3">
      <v-data-table
        :headers="encabezadosCobranzas"
        :items="cobranzas"
        item-key="id"
        class="elevation-1 custom-table"
        dense
        :items-per-page="-1"
        hide-default-footer
        hide-disable-pagination
      >
        <!-- Pago -->
        <template v-slot:item.Pago="{ item }">
          {{ item.tipo_pago_id }}
        </template>
        <!-- Auditor -->
        <template v-slot:item.auditorC="{ item }">
          {{ item.auditor_cve }} - {{ item.nombre_auditor }}
        </template>
        <!-- Supervisor -->
        <template v-slot:item.supervisorC="{ item }">
          {{ item.supervisor_cve }} - {{ item.nombre_supervisor }}
        </template>
        <!-- Fecha Cobranza -->
        <template v-slot:item.fecha_pago="{ item }">
          {{ fechaFormateada(item.fecha_pago) }}
        </template>
        <!-- Fecha Captura Cobranza -->
        <template v-slot:item.fecha_captura_cobranza="{ item }">
          {{ fechaFormateada(item.fecha_captura_cobranza.split(' ')[0]) }}
        </template>
        <!-- Principal -->
        <template v-slot:item.principal="{ item }">
          <v-tooltip location="top">
            <template v-slot:activator="{ props }">
              <span v-bind="props" class="cursor-pointer">
                {{ formatearMoneda(parseFloat(item.principal || 0)) }}
              </span>
            </template>
          </v-tooltip>
        </template>
        <!-- Actualizacion -->
        <template v-slot:item.actualizacion="{ item }">
          <v-tooltip location="top">
            <template v-slot:activator="{ props }">
              <span v-bind="props" class="cursor-pointer">
                {{ formatearMoneda(parseFloat(item.actualizacion || 0)) }}
              </span>
            </template>
          </v-tooltip>
        </template>
        <!-- Recargo -->
        <template v-slot:item.recargo="{ item }">
          <v-tooltip location="top">
            <template v-slot:activator="{ props }">
              <span v-bind="props" class="cursor-pointer">
                {{ formatearMoneda(parseFloat(item.recargo || 0)) }}
              </span>
            </template>
          </v-tooltip>
        </template>
        <!-- Multa Forma -->
        <template v-slot:item.multa_forma="{ item }">
          <v-tooltip location="top">
            <template v-slot:activator="{ props }">
              <span v-bind="props" class="cursor-pointer">
                {{ formatearMoneda(parseFloat(item.multa_forma || 0)) }}
              </span>
            </template>
          </v-tooltip>
        </template>
        <!-- Multa Fondo -->
        <template v-slot:item.multa_fondo="{ item }">
          <v-tooltip location="top">
            <template v-slot:activator="{ props }">
              <span v-bind="props" class="cursor-pointer">
                {{ formatearMoneda(parseFloat(item.multa_fondo || 0)) }}
              </span>
            </template>
          </v-tooltip>
        </template>
        <!-- Total Multa -->
        <template v-slot:item.total_multa="{ item }">
          <v-tooltip location="top">
            <template v-slot:activator="{ props }">
              <span v-bind="props" class="cursor-pointer">
                {{ formatearMoneda(parseFloat(item.multa_fondo || 0) + parseFloat(item.multa_forma || 0)) }}
              </span>
            </template>
            <div>
              <div>Multa Fondo: {{ formatearMoneda(item.multa_fondo) }}</div>
              <div>Multa Forma: {{ formatearMoneda(item.multa_forma) }}</div>
              <v-divider class="my-1"></v-divider>
              <div><strong>Total: {{ formatearMoneda(parseFloat(item.multa_fondo || 0) + parseFloat(item.multa_forma || 0)) }}</strong></div>
            </div>
          </v-tooltip>
        </template>
        <!-- Total -->
        <template v-slot:item.total="{ item }">
          <v-tooltip location="top">
            <template v-slot:activator="{ props }">
              <span v-bind="props" class="font-weight-bold text-primary cursor-pointer">
                {{ formatearMoneda(
                  parseFloat(item.principal || 0) + 
                  parseFloat(item.actualizacion || 0) + 
                  parseFloat(item.recargo || 0) + 
                  parseFloat(item.multa_fondo || 0) + 
                  parseFloat(item.multa_forma || 0)
                ) }}
              </span>
            </template>
            <div>
              <div>Principal: {{ formatearMoneda(item.principal) }}</div>
              <div>Actualización: {{ formatearMoneda(item.actualizacion) }}</div>
              <div>Recargo: {{ formatearMoneda(item.recargo) }}</div>
              <div>Multa Fondo: {{ formatearMoneda(item.multa_fondo) }}</div>
              <div>Multa Forma: {{ formatearMoneda(item.multa_forma) }}</div>
              <v-divider class="my-2 bg-white"></v-divider>
              <div class="font-weight-bold">
                Total: {{ formatearMoneda(
                  parseFloat(item.principal || 0) + 
                  parseFloat(item.actualizacion || 0) + 
                  parseFloat(item.recargo || 0) + 
                  parseFloat(item.multa_fondo || 0) + 
                  parseFloat(item.multa_forma || 0)
                ) }}
              </div>
            </div>
          </v-tooltip>
        </template>
        <!-- Periodo -->
        <template v-slot:item.periodo="{ item }">
          {{ fechaFormateada(item.periodo_inicial) }} - {{ fechaFormateada(item.periodo_final) }}
        </template>
        <!-- Acciones -->
        <template v-slot:item.actions="{ item }">
          <v-icon large class="mr-2" color="amber" dark dense alt="Editar" @click="$emit('editar-cobranza', item)">
            mdi-pencil
          </v-icon>
        </template>
        <template v-slot:no-data>
          <h3 class="ma-2 text-center">
             No existen registros
          </h3>
          <!-- <v-alert type="info" color="blue lighten-4" border="left" class="ma-2">
            No existen registros
          </v-alert> -->
        </template>
      </v-data-table>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  cobranzas: {
    type: Array,
    required: true,
  }
});

defineEmits(['nueva-cobranza', 'editar-cobranza']);

const encabezadosCobranzas = ref([
    { text: "Fecha de Pago", value: "fecha_pago", class: "grey darken-1 white--text elevation-1", width:"80" },
    { text: "Pago", value:"Pago", class: "grey darken-1 white--text elevation-1", width: "35"  },
    { text: "Impuesto", value:"descripcion_impuesto", class: "grey darken-1 white--text elevation-1", width: "40", align: 'center' },
    { text: "Lugar de Pago", value:"descripcion_lugar_pago", class: "grey darken-1 white--text elevation-1", width: "120", align: 'center'  },
    { text: "Periodo", value:"periodo", class: "grey darken-1 white--text elevation-1", width: "120", align: 'center'  },
    { text: "No. Ope", value:"no_operacion", class: "grey darken-1 white--text elevation-1", width: "120", align: 'center'  },
    { text: "Principal", value:"principal", class: "grey darken-1 white--text elevation-1", width: "70", align: 'end'  },
    { text: "Actualizacion", value:"actualizacion", class: "grey darken-1 white--text elevation-1", width: "70", align: 'end'  },
    { text: "Recargo", value:"recargo", class: "grey darken-1 white--text elevation-1", width: "70", align: 'end'  },
    { text: "Multa Fondo", value:"multa_fondo", class: "grey darken-1 white--text elevation-1", width: "70", align: 'end'  },
    { text: "Multa Forma", value:"multa_forma", class: "grey darken-1 white--text elevation-1", width: "70", align: 'end'  },
    // { text: "Auditor", value: "auditorC", class: "grey darken-1 white--text elevation-1", width: "250" },
    // { text: "Supervisor", value: "supervisorC", class: "grey darken-1 white--text elevation-1", width:"250" },
    { text: "Total Multa", value:"total_multa", class: "grey darken-1 white--text elevation-1", width: "70", align: 'end'  },
    { text: "Total", value:"total", class: "grey darken-1 white--text elevation-1", width: "70", align: 'end'  },
    { text: "Capturo", value: "usuario", class: "grey darken-1 white--text elevation-1", width:"80" },
    // { text: "Fecha Captura", value: "fecha_captura_pago", class: "grey darken-1 white--text elevation-1", width:"140" },
    { text: "", value: "actions", class: "grey darken-1 white--text elevation-1", width:"20" },
]);

const fechaFormateada = (fecha) => {
  if (!fecha) return "";
  const [year, month, day] = fecha.split("-");
  return `${day}/${month}/${year}`;
};

const formatearMoneda = (valor) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(valor);
};

// const calcularTotalMulta = (item) => {
//   const fondo = parseFloat(item.multa_fondo || 0);
//   const forma = parseFloat(item.multa_forma || 0);
//   return formatearMoneda(fondo + forma);
// };

</script>

<style scoped>
.custom-table .v-data-table__cell {
  font-size: 25px;
}

.custom-table .v-data-table__header th {
  font-size: 50px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
}
</style>
