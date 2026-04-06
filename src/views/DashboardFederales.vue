<template>
    <v-container fluid class="pa-8">
        <v-row class="center mb-4">
            <v-col cols="12" class="text-center">
                <h1>DASHBOARD ÓRDENES FEDERALES</h1>
            </v-col>
        </v-row>
        <v-row class="mb-6">
            <!-- COLUMNA IZQUIERDA -->
            <v-col cols="12" md="9">
                <!-- GRÁFICA -->
                <v-card elevation="2" class="mb-6">
                    <v-card-title class="font-weight-bold">COMPARATIVO DE ÓRDENES FEDERALES EMITIDAS POR MES</v-card-title>
                    <v-card-text style="height: 350px"><canvas ref="barChart"></canvas></v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="mostrarDetalleMes" max-width="1200px" persistent scrollable>
            <v-card>
                <v-card-title class="detalle-titulo">
                    <span class="titulo-texto">ÓRDENES DEL MES: {{ mesDetalleTexto }} {{ anioDetalle }}</span>
                    <v-spacer />
                    <v-btn icon small @click="cerrarDetalleMes"><v-icon>mdi-close</v-icon></v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text>
                    <v-data-table :headers="headersDetalle" :items="ordenesMes" dense class="elevation-1"/>
                </v-card-text>
                <v-divider />
                <v-card-actions class="justify-end">
                    <v-btn text color="pink darken-4" @click="cerrarDetalleMes">Cerrar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
<script>
    import Swal from 'sweetalert2';
    import axios from 'axios';
    import api from '@/services/apiUrls.js';
    import Chart from 'chart.js/auto';
    import ChartDataLabels from 'chartjs-plugin-datalabels';
    Chart.register(ChartDataLabels);

export default {
    name: "DashboardFederales",
    data() {
        return {
            mostrarDetalleMes: false,
            mesDetalle: null,
            anioDetalle: null,
            ordenesMes: [],
            headersDetalle: [
                {text: "ORDEN",
                value: "num_orden",
                class: "pink darken-4 white--text elevation-1 center-header",
                width: "100"},
                {text: "FECHA DE ORDEN",
                value: "fecha_orden",
                class: "pink darken-4 white--text elevation-1 center-header",
                width: "120"},
                {text: "RFC",
                value: "rfc",
                class: "pink darken-4 white--text elevation-1 center-header",
                width: "120"},
                {text: "NOMBRE",
                value: "nombre",
                class: "pink darken-4 white--text elevation-1 center-header",
                width: "250"},
                {text: "PERIODOS",
                value: "periodos",
                class: "pink darken-4 white--text elevation-1 center-header",
                width: "100"}
            ],
            barChart: null,
            meses: [{ text: 'ENERO', value: 1 }, { text: 'FEBRERO', value: 2 }, { text: 'MARZO', value: 3 }, { text: 'ABRIL', value: 4 }, 
            { text: 'MAYO', value: 5 }, { text: 'JUNIO', value: 6 }, { text: 'JULIO', value: 7 }, { text: 'AGOSTO', value: 8 }, { text: 'SEPTIEMBRE', value: 9 }, 
            { text: 'OCTUBRE', value: 10 }, { text: 'NOVIEMBRE', value: 11 }, { text: 'DICIEMBRE', value: 12 }, { text: 'TODOS', value: 0 }],
            };
    },
    computed: {
        mesDetalleTexto() {
            const m = this.meses.find(x => x.value === this.mesDetalle)
            return m ? m.text : ''
        }
    },
    components:{

    },
    async created() {
        await this.cargarBarChartComparativo();
    },
    methods: {
        cerrarDetalleMes() {
            this.mostrarDetalleMes = false
            this.ordenesMes = []
            this.mesDetalle = null
            this.anioDetalle = null
        },
        async cargarDetalleMes(anio, mes) {
            try {
                const { data } = await axios.post(api.dashboard, {opcion: 11, anio, mes})
                this.ordenesMes = Array.isArray(data) ? data : [];
                this.mesDetalle = mes;
                this.anioDetalle = anio;
                this.mostrarDetalleMes = true;
            } catch (e) {
                console.error('Error al cargar detalle del mes', e)
                Swal.fire('Error', 'No se pudieron cargar las órdenes del mes', 'error')
            }
        },
        async cargarBarChartComparativo() {
            try {
                this.mostrarDetalleMes = false;
                this.ordenesMes = [];
                const { data } = await axios.post(api.dashboard, {opcion: 10});
                const normalizados = data.map(d => ({anio: Number(d.anio), mes: Number(d.mes), total: Number(d.total)}));
                const meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO', 'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
                const anios = [...new Set(normalizados.map(d => d.anio))].sort((a,b)=>a-b);
                const anioActual = Math.max(...anios);
                const anioAnterior = anioActual - 1;
                const totalesActual = Array(12).fill(0);
                const totalesAnterior = Array(12).fill(0);
                normalizados.forEach(row => {
                const mesIndex = row.mes - 1
                const total = row.total
                if (row.anio === anioActual) {
                    totalesActual[mesIndex] = total
                }
                if (row.anio === anioAnterior) {
                    totalesAnterior[mesIndex] = total
                }
                });
                if (this.barChart) {
                this.barChart.destroy();
                }

                this.barChart = new Chart(this.$refs.barChart, {
                type: 'bar',
                data: {
                    labels: meses,
                    datasets: [
                    {
                        label: anioAnterior.toString(),
                        data: totalesAnterior,
                        backgroundColor: '#90CAF9',
                        order: 1,
                        animations: {
                            y: {
                            from: 0
                            }
                        }
                    },
                    {
                        label: anioActual.toString(),
                        data: totalesActual,
                        backgroundColor: '#F48FB1',
                        borderColor: '#C2185B',
                        borderWidth: 2,
                        order: 1,
                        animations: {
                            y: {
                            from: 0
                            }
                        }
                    }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1200
                    },
                    onClick: (evt, elements) => {
                        if (!elements.length) return
                        const index = elements[0].index
                        const datasetIndex = elements[0].datasetIndex
                        const mes = index + 1
                        const anio = datasetIndex === 0 ? anioAnterior : anioActual
                        this.cargarDetalleMes(anio, mes)
                    },
                    plugins: {
                        legend: { position: 'top' },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            animation: false,
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            formatter: value => value > 0 ? value : ''
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }  
                }
            });

            } catch (e) {
                console.error('Error al cargar gráfica comparativa', e);
            }
        }
    }
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

.center-header {
  text-align: center;
}
.dashboard-federales {
  margin: 0 auto;
  overflow-y: visible;
  padding: 5px;
  max-width: 100%;
}
.dashboard-federales .v-data-table {
  width: 100%;
}
.dashboard-federales .v-data-table table {
  width: 100%;
  table-layout: auto;
}
.dashboard-federales th, .dashboard-federales td {
  text-align: center;
  max-width: 120px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dashboard-federales th:first-child, .dashboard-federales td:first-child {
  width: 220px;
  max-width: 220px;
  text-align: left;
  white-space: normal;
}
.dashboard-federales th:nth-child(16), .dashboard-federales td:nth-child(16) {
  width: 80px;
  max-width: 80px;
}
.dashboard-federales .v-data-table thead tr:first-child th {
  min-height: 30px;
  max-width: none !important;
  white-space: nowrap;
  vertical-align: middle;
  font-size: 15px;
  font-weight: 600;
  border: 1px solid #ffffff !important;
}
.dashboard-federales .v-data-table thead tr:nth-child(2) th {
  border: 1px solid #ffffff;
}
.dashboard-federales .dashboard-federales td {
  max-width: 150px;
  text-align: center;
}
.dashboard-federales th:first-child, .dashboard-federales td:first-child {
  text-align: left;
}
.dashboard-federales th:not(:first-child), .dashboard-federales td:not(:first-child) {
  max-width: 150px;
  white-space: nowrap;
}
.v-data-table td {
  text-align: center;
}
</style>
