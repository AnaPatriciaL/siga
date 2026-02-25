<template>
  <v-container>
    <v-card class="mx-auto mt-5">
      <v-card-title class="headline grey darken-3 white--text py-1 mb-10">HISTORIAL DE EXPEDIENTES ESTATALES EMITIDOS</v-card-title>
      <v-card-text>
        <v-form ref="form">
          <v-row justify="center" class="mb-7">
            <v-col cols="12" md="3">
              <v-text-field v-model="filters.orden" label="Orden" outlined class="text-h5 mayusculas" placeholder="Ingresa la orden" clearable append-icon="mdi-magnify" @keydown.enter="fetchData" @click:append="fetchData"/>
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field v-model="filters.rfc" label="RFC" outlined class="text-h5 mayusculas" placeholder="Ingresa el RFC" clearable append-icon="mdi-magnify" @keydown.enter="fetchData" @click:append="fetchData"/>
            </v-col>
          </v-row>
        </v-form>
        <v-row v-if="results.length" class="mb-2">
          <v-col cols="12" class="py-0">
            <v-alert dense text type="success" icon="mdi-file-search">
              <h3>Total de resultados: <strong>{{ totalRegistros }}</strong></h3>
            </v-alert>
          </v-col>
          <v-col cols="12" class="py-0 mb-7">
            <v-data-table :headers="headers" :items="results" :items-per-page="results.length" hide-default-footer class="custom-table elevation-1">
                <template v-slot:item.fecha_orden="{ item }">{{ formatFecha(item.fecha_orden) }}</template>
                <template v-slot:item.seguimiento="{ item }">{{ formatFecha(item.seguimiento) }}</template>
            </v-data-table>
          </v-col>
          <v-col cols="12" class="py-0">
            <v-alert dense text type="success" icon="v-icon notranslate v-alert__icon mdi mdi-update theme--light success--text">
              <strong>Estatus Actualizados a: </strong><span>Enero 2026</span>
            </v-alert>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
    <!-- Loader -->
    <v-dialog v-model="cargando" max-width="290" persistent>
      <v-card color="pink darken-4" dark>
        <v-card-text class="pt-3">Buscando información<v-progress-linear indeterminate color="white" class="my-3" /></v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import api from '@/services/apiUrls.js';

export default {
  name: "Historial",
  data() {
    return {
      cargando: false,
      totalRegistros: 0,
      filters: {
        orden: "",
        rfc: "",
      },
      results: [],
      headers: [
        {
          text: "ORDEN",
          value: "num_orden",
          class: "pink darken-4 white--text center-header",
          width: "120",
        },
        {
          text: "FECHA",
          value: "fecha_orden",
          class: "pink darken-4 white--text center-header",
          width: "110",
        },
        {
          text: "RFC",
          value: "rfc",
          class: "pink darken-4 white--text center-header",
          width: "70",
        },
        {
          text: "NOMBRE",
          value: "nombre",
          class: "pink darken-4 white--text center-header",
          width: "250",
        },
        {
          text: "DOMICILIO",
          value: "domicilio",
          class: "pink darken-4 white--text center-header",
          width: "300",
        },
        {
          text: "OFICINA",
          value: "oficina",
          class: "pink darken-4 white--text center-header",
          width: "100",
        },
        {
          text: "PERIODO",
          value: "periodos",
          class: "pink darken-4 white--text center-header",
          width: "100",
        },
        {
          text: "IMPUESTO",
          value: "impuestos",
          class: "pink darken-4 white--text center-header",
          width: "50",
        },
        {
          text: "ESTATUS",
          value: "estatus",
          class: "pink darken-4 white--text center-header",
          width: "50",
        },
        {
          text: "FECHA MOVIMIENTO",
          value: "seguimiento",
          class: "pink darken-4 white--text center-header",
          width: "70",
        },
      ],
    };
  },
  methods: {
    formatFecha(fecha) {
      if (!fecha) return "";

      const separador = fecha.includes("-") ? "-" : "/";
      const [anio, mes, dia] = fecha.split(separador);

      return `${dia}/${mes}/${anio}`;
    },
    async fetchData() {
      if (!this.filters.orden && !this.filters.rfc) {
        this.$Swal.fire({
          icon: "warning",
          title: "Advertencia",
          confirmButtonColor: "#d33",
          html:
            "Ingrese una <strong>Orden</strong> o un <strong>RFC</strong> para buscar.",
        });
        return;
      }

      this.results = [];
      this.totalRegistros = 0;
      this.cargando = true;

      try {
        const response = await axios.post(api.historial, {opcion: 2, orden: this.filters.orden || null, rfc: this.filters.rfc || null}
        );

        this.cargando = false;

        if (Array.isArray(response.data) && response.data.length) {
          this.results = response.data;
          this.totalRegistros = response.data.length;
        } else {
          this.$Swal.fire({
            icon: "info",
            title: "Sin resultados",
            confirmButtonColor: "#3085d6",
            html: `
              <strong>Orden:</strong> ${this.filters.orden || "No especificado"}<br>
              <strong>RFC:</strong> ${this.filters.rfc || "No especificado"}
            `,
          });
        }
      } catch (error) {
        this.cargando = false;
        console.error(error);
        this.$Swal.fire({
          icon: "error",
          title: "Error",
          confirmButtonColor: "#d33",
          text: "Ocurrió un error al consultar la información.",
        });
      }
    },
    irADetalle(item) {
      this.$router.push({
        name: "DetalleOrden",
        params: { orden: item },
      });
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

.tabla-emitidas thead th {
  align-items: center !important;   
  justify-content: center !important;
  text-align: center !important;
  height: 64px;             
  padding: 0 8px !important;
}

.tabla-emitidas thead th .v-data-table-header__text {
  display: block;
  line-height: 1.2;
  white-space: normal;
}
</style>
