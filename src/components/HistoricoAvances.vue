<template>
  <v-card>
    <v-card-title class="blue lighten-3 py-2">
      <v-row class="mx-0 py-0">
        <v-col cols="11" class="mx-0">
          Historico de Avances
        </v-col>
        <v-spacer></v-spacer>
        <v-col cols="1" class="mx-0 text-right">
          <v-tooltip left>
            <template v-slot:activator="{ on, attrs }">
              <v-btn class="mr-4" v-bind="attrs" v-on="on" small dark @click="$emit('nuevo-avance')">
                Nuevo<v-icon>mdi-forward</v-icon>
              </v-btn>
            </template>
            <span>Registrar nuevo Avance</span>
          </v-tooltip>
        </v-col>
      </v-row>
    </v-card-title>
    <v-card-text class="pt-3">
      <v-data-table
        :headers="encabezadosAvances"
        :items="avances"
        item-key="id"
        class="elevation-1 custom-table"
        dense
        hide-default-footer
        :items-per-page="-1"
        hide-disable-pagination
      >
        <!-- Seguimiento -->
        <template v-slot:item.seguimiento="{ item }">
          {{ item.cve_seguimiento }} - {{ item.descripcion_seguimiento }}
        </template>
        <!-- Auditor -->
        <template v-slot:item.auditorC="{ item }">
          {{ item.auditor_cve }} - {{ item.nombre_auditor }}
        </template>
        <!-- Supervisor -->
        <template v-slot:item.supervisorC="{ item }">
          {{ item.supervisor_cve }} - {{ item.nombre_supervisor }}
        </template>
        <!-- Fecha Avance -->
        <template v-slot:item.fecha_avance="{ item }">
          {{ fechaFormateada(item.fecha_avance) }}
        </template>
        <!-- Fecha Captura Avance -->
        <template v-slot:item.fecha_captura_seguimiento="{ item }">
          {{ fechaFormateada(item.fecha_captura_seguimiento.split(' ')[0]) }}
        </template>
        <!-- Acciones -->
        <template v-slot:item.actions="{ item }">
          <v-icon large class="mr-2" color="amber" dark dense alt="Editar" @click="$emit('editar-avance', item)">
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
  avances: {
    type: Array,
    required: true,
  }
});

defineEmits(['nuevo-avance', 'editar-avance']);

const encabezadosAvances = ref([
    { text: "Seguimiento", value: "seguimiento", class: "grey darken-1 white--text elevation-1", width:"150" },
    { text: "Fecha Seguimiento", value: "fecha_avance", class: "grey darken-1 white--text elevation-1", width:"130" },
    { text: "Auditor", value: "auditorC", class: "grey darken-1 white--text elevation-1", width: "250" },
    { text: "Supervisor", value: "supervisorC", class: "grey darken-1 white--text elevation-1", width:"250" },
    { text: "Total Credito", value: "total_credito", class: "grey darken-1 white--text elevation-1", width:"120" },
    { text: "Capturo", value: "usuario", class: "grey darken-1 white--text elevation-1", width:"100" },
    { text: "Fecha Captura", value: "fecha_captura_seguimiento", class: "grey darken-1 white--text elevation-1", width:"140" },
    { text: "", value: "actions", class: "grey darken-1 white--text elevation-1", width:"30" },
]);

const fechaFormateada = (fecha) => {
  if (!fecha) return "";
  const [year, month, day] = fecha.split("-");
  return `${day}/${month}/${year}`;
};
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
