<template>
  <v-container>
    <v-card class="mx-auto mt-5">
      <v-card-title class="headline grey darken-3 white--text py-1 mb-10">HISTORIAL DE PAGOS</v-card-title>
      <v-card-text>
        <v-form ref="form">
          <v-row justify="center" class="mb-7">
            <v-col cols="12" sm="12" md="6" lg="4" xl="3">
                <v-text-field v-model="filters.rfc" label="RFC" outlined class="text-h5 mayusculas" placeholder="Ingresa el RFC" clearable append-icon="mdi-magnify" @keydown.enter="fetchData" @click:append="fetchData"/>
            </v-col>
            <v-col cols="12" sm="12" md="6" lg="4" xl="3">
                <v-text-field v-model="filters.nombre" label="NOMBRE" outlined class="text-h5 mayusculas" placeholder="Ingresa el nombre" clearable append-icon="mdi-magnify" @keydown.enter="fetchData" @click:append="fetchData"/>
            </v-col>
          </v-row>
        </v-form>
        <v-row class="mb-2">
          <v-col v-if="totalRegistros > 0 && view=='buscar'" cols="12" class="py-0">
            <v-alert dense text type="success" icon="mdi-file-search">
              <h3>Total de resultados: <strong>{{ totalRegistros }}</strong></h3>
            </v-alert>
          </v-col>
          <v-col cols="12" class="py-0 mb-7">
            <v-data-table v-if="view=='buscar' && results.length" :headers="[{text:'RFC',value:'rfc'}, {text:'NOMBRE',value:'nombre'}, {text:'DOMICILIO',value:'domicilio'}]"
            :items="results" :items-per-page="results.length" hide-default-footer class="elevation-1" @click:row="seleccionarRegistro"></v-data-table>
          </v-col>
          <v-col v-if="totalRegistros > 0 && view=='buscar'" cols="12" class="py-0">
            <v-alert dense text type="success" icon="v-icon notranslate v-alert__icon mdi mdi-update theme--light success--text">
              <strong>Estatus Actualizados a: </strong><span>Enero 2026</span>
            </v-alert>
          </v-col>
        </v-row>
      </v-card-text>
      <v-card v-if="view=='pagos'" class="mx-auto">
        <v-card-title class="grey darken-3 white--text">DATOS DEL CONTRIBUYENTE</v-card-title>
          <v-spacer></v-spacer>
        <v-data-table v-if="view=='pagos'" :headers="infoHeaders" :items="infoContribuyente" hide-default-footer hide-default-header class="elevation-1 mb-4">
          <template v-slot:item.campo="{ item }"><strong>{{ item.campo }}</strong></template>
        </v-data-table>
      </v-card>
    </v-card>
    <v-card v-if="view=='pagos'" class="mb-7 mx-auto mt-5">
      <v-card-title class="grey darken-3 white--text">Historial de pagos</v-card-title>
        <v-spacer></v-spacer>
        <v-spacer></v-spacer>
      <v-card-text>
          <v-row>
              <v-col cols="6" md="2">
                  <v-select :items="meses" item-text="text" item-value="value" label="Mes inicio" v-model="filtrosFecha.mesInicio" outlined/>
              </v-col>
              <v-col cols="6" md="2">
                  <v-select :items="anios" v-model="anioInicio" label="Año inicio" outlined></v-select>
              </v-col>
              <v-col cols="6" md="2">
                  <v-select :items="meses" item-text="text" item-value="value" label="Mes fin" v-model="filtrosFecha.mesFin" outlined/>
              </v-col>
              <v-col cols="6" md="2">
                  <v-select :items="anios" v-model="anioFin" label="Año fin" outlined></v-select>
              </v-col>
              <v-col cols="12" md="2">
                  <v-btn color="primary" @click="filtrarPagos">Filtrar</v-btn>
              </v-col>
              <v-col cols="12" md="2">
                  <v-btn color="success"><v-icon left>mdi-download</v-icon>Descargar</v-btn>
              </v-col>
          </v-row>
          <v-data-table :headers="[{text:'Fecha',value:'fecha'}, {text:'Concepto',value:'concepto'}, {text:'Monto',value:'monto'}]" :items="pagos" hide-default-footer/>
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
  import axios from 'axios';
  import api from '@/services/apiUrls.js';

export default {
  name: "Pagos",
  data() {
    return {
        meses: [
            { text: "ENERO", value: 1 },
            { text: "FEBRERO", value: 2 },
            { text: "MARZO", value: 3 },
            { text: "ABRIL", value: 4 },
            { text: "MAYO", value: 5 },
            { text: "JUNIO", value: 6 },
            { text: "JULIO", value: 7 },
            { text: "AGOSTO", value: 8 },
            { text: "SEPTIEMBRE", value: 9 },
            { text: "OCTUBRE", value: 10 },
            { text: "NOVIEMBRE", value: 11 },
            { text: "DICIEMBRE", value: 12 }
        ],
        anios: Array.from({ length: 20 }, (_, i) => 2010 + i),
        cargando: false,
        totalRegistros: 0,
        view: "buscar",
        filters: {
            rfc: "",
            nombre: "",
        },
        filtrosFecha: {
            mesInicio: "",
            anioInicio: "",
            mesFin: "",
            anioFin: ""
        },
        contribuyente: null,
        results: [],
        pagos: [],
        infoHeaders: [
          { text: "INFORMACION DEL CONTRIBUYENTE", value: "campo" },
          { text: " ", value: "valor" }
        ],
    }
  },
  computed:{
    infoContribuyente(){
      if(!this.contribuyente) return []

      return [
        { campo: "RFC", valor: this.contribuyente.rfc },
        { campo: "NOMBRE", valor: this.contribuyente.nombre },
        { campo: "DOMICILIO", valor: this.contribuyente.domicilio }
      ]
    }
  },
  methods: {
    buscar() {
        if (!this.validarFechas()) return;
        console.log("buscar datos");
    },
    validarFechas() {
        const fechaInicio = new Date(this.anioInicio, this.mesInicio - 1)
        const fechaFin = new Date(this.anioFin, this.mesFin - 1)
        if (fechaFin < fechaInicio) {
        this.$swal('Error', 'La fecha final no puede ser menor a la inicial', 'error')
        return false;
        }

        return true;
    },
    filtrarPagos(){
        if(!this.filtrosFecha.anioInicio || !this.filtrosFecha.anioFin){
            return;
        }
        const inicio = new Date(this.filtrosFecha.anioInicio, this.filtrosFecha.mesInicio-1);
        const fin = new Date(this.filtrosFecha.anioFin, this.filtrosFecha.mesFin-1);
        this.pagos = this.pagos.filter(p=>{const fecha = new Date(p.fecha)
            return fecha >= inicio && fecha <= fin
        });
    },
    seleccionarRegistro(item){
        this.contribuyente = item
        this.view = "pagos"
        this.pagos = [
            {fecha:'2025-01-15', concepto:'Pago Nomina', monto:500},
            {fecha:'2025-03-20', concepto:'Pago Nomina', monto:500},
            {fecha:'2025-06-10', concepto:'Pago Nomina', monto:500},
            {fecha:'2025-09-01', concepto:'Pago Nomina', monto:500}
        ]
    },
    formatFecha(fecha) {
      if (!fecha) return "";

      const separador = fecha.includes("-") ? "-" : "/";
      const [anio, mes, dia] = fecha.split(separador);

      return `${dia}/${mes}/${anio}`;
    },
    async fetchData() {
      this.view = "buscar";
      this.contribuyente = null;
      this.pagos = [];
      if (!this.filters.nombre && !this.filters.rfc) {
        this.$Swal.fire({
          icon: "warning",
          title: "Advertencia",
          confirmButtonColor: "#d33",
          html:
            "Ingrese un <strong>RFC</strong> o un <strong>Nombre</strong> para buscar.",
        });
        return;
      }

      this.results = [];
      this.totalRegistros = 0;
      this.cargando = true;

      try {
        const response = await axios.post(api.pagos, {opcion: 1, nombre: this.filters.nombre || null, rfc: this.filters.rfc || null}
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
            html: `<strong>RFC:</strong> ${this.filters.rfc || "No especificado"}
              <strong>Nombre:</strong> ${this.filters.nombre || "No especificado"}<br>`,
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

</style>
