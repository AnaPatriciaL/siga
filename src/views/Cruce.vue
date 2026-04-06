<template>
    <v-container>
        <v-card class="mx-auto mt-5">
            <v-card-title class="headline grey darken-3 white--text py-1 mb-5">CRUCE ESTATALES</v-card-title>
            <v-card-text>
                <v-row justify="center" class="mb-7">
                    <v-col cols="12" sm="12" md="7" lg="6" xl="5">
                        <v-textarea v-model="rfcInput" label="PEGUE EL LISTADO DE RFC" outlined rows="10" auto-grow class="mayusculas text-h6" @blur="formatearRFC"></v-textarea>
                        <v-btn class="mt-4" color="primary" @click="procesarRFC">CREAR CRUCE</v-btn>
                        <vue-excel-xlsx v-if="puedeExportar" :data="results" :columns="columnas" :file-name="'Cruce_Estatales'" :file-type="'xlsx'" :sheet-name="'Cruce'">
                            <v-tooltip top color="green darken-3">
                                <template v-slot:activator="{ on, attrs }">
                                    <v-btn fab class="green ml-3" dark v-bind="attrs" v-on="on"><v-icon large>mdi-microsoft-excel</v-icon></v-btn>
                                </template>
                                <span>Descargar Excel</span>
                            </v-tooltip>
                        </vue-excel-xlsx>
                    </v-col>
                </v-row>
                <v-data-table v-if="results.length" :items-per-page="results.length" hide-default-footer :headers="headers" :items="results" class="mt-5"></v-data-table>
            </v-card-text>
        </v-card>
    </v-container>
</template>
<script>
import axios from 'axios';
import api from '@/services/apiUrls.js';

export default {
    name: "Cruce",
    data() {
        return {
            rfcInput: '',
            rfcList: [],
            results: [],
            headers: [
                { text: "RFC", value: "rfc" },
                { text: "Nombre", value: "nombre" },
                { text: "Periodo", value: "periodos" },
                { text: "Orden", value: "num_orden" },
                { text: "Fecha orden", value: "fecha_orden" },
                { text: "Estatus", value: "estatus" }
            ],
            columnas: [
                { label: "RFC", field: "rfc" },
                { label: "Nombre", field: "nombre" },
                { label: "Periodo", field: "periodos" },
                { label: "Orden", field: "num_orden" },
                { label: "Fecha orden", field: "fecha_orden" },
                { label: "Estatus", field: "estatus" }
            ]
        }
    },
    computed: {
        puedeExportar() {
            return this.results.length > 0;
        }
    }, 
    methods: {
        formatearRFC() {
            const lista = this.rfcInput
                .split(/\r?\n|,|;/)   
                .map(rfc => rfc.trim().toUpperCase()) 
                .filter(rfc => rfc.length > 0); 
            this.rfcList = lista;
            this.rfcInput = lista.join('\n')
        },
        procesarRFC() {
            this.results = [];
            this.formatearRFC();
            if (!this.rfcList.length) {
                return;
            }
            axios.post(api.cruce, {opcion: 1})
            .then(res => {
                const datosBackend = Array.isArray(res.data) ? res.data : [];
                // Set para buscar más rápido
                const rfcSet = new Set(this.rfcList.map(r => r.toUpperCase()));
                // Filtrar solo los RFC que pegó el usuario
                this.results = datosBackend.filter(item =>
                    item.rfc && rfcSet.has(item.rfc.toUpperCase()))
            })
            .catch(error => {
                console.error(error)
            });

        }
    }
}
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
.mayusculas textarea{
  text-transform: uppercase
}
</style>
