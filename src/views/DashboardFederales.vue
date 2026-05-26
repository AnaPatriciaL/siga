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
                <!-- TABLA INVERTIDA -->
                <v-card elevation="2">
                    <div class="dashboard-federales">
                        <v-row class="mb-4 mt-2" align="center">
                            <v-col cols="12" md="2">
                                <v-select v-model="filtros.anio" :items="anios" label="Año" dense outlined hide-details/>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-select v-model="filtros.mes" :items="meses" item-text="text" item-value="value" label="Mes" dense outlined hide-details/>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn color="pink darken-4 white--text" @click="consultarFederales">MOSTRAR</v-btn>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-text-field label="Periodo seleccionado" :value="periodoSeleccionado" outlined dense hide-details readonly/>
                            </v-col>
                            <v-col cols="12" md="1" v-if="puedeExportar">
                                <v-tooltip top color="green darken-3">
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-btn fab class="green ml-3" dark v-bind="attrs" v-on="on" @click="exportarExcelFederales"><v-icon large>mdi-microsoft-excel</v-icon></v-btn>
                                    </template>
                                <span>Exportar a Excel</span>
                                </v-tooltip>
                            </v-col>
                        </v-row>
                        <!-- TABLA -->
                        <v-data-table :headers="encabezadosInvertidosFederales" :items="tablaInvertidaFederales" hide-default-header hide-default-footer :items-per-page="-1" dense class="elevation-1">
                            <template v-slot:header>
                                <thead>
                                    <tr class="grey darken-1">
                                        <th v-for="h in encabezadosInvertidosFederales" :key="h.value" class="white--text text-center">{{ h.text }}</th>
                                    </tr>
                                </thead>
                            </template>
                            <template v-slot:item="{ item }">
                                <tr :class="item.tipo === 'TOTALES' ? 'grey lighten-3 font-weight-bold' : ''">
                                <td :class="item.tipo && item.tipo !== 'TOTALES' ? 'pink darken-4 white--text font-weight-bold text-center' : 'text-center font-weight-bold'">{{ item.tipo }}</td>
                                <td :class="item.impuesto ? 'font-weight-bold text-left' : ''">{{ item.impuesto }}</td>
                                <td>{{ item.los_mochis }}</td>
                                <td>{{ item.guasave }}</td>
                                <td>{{ item.guamuchil }}</td>
                                <td>{{ item.culiacan }}</td>
                                <td>{{ item.mazatlan }}</td>
                                <td>{{ item.total }}</td>
                                </tr>
                            </template>
                        </v-data-table>
                    </div>
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
    import * as XLSX from 'xlsx-js-style';
    import Chart from 'chart.js/auto';
    import ChartDataLabels from 'chartjs-plugin-datalabels';
    Chart.register(ChartDataLabels);

export default {
    name: "DashboardFederales",
    data() {
        return {
            anioConsultado: null,
            mesConsultado: null,
            filtros: { anio: null, mes: null },
            anios: [],
            encabezadosInvertidosFederales: [
                { text: 'TIPO', value: 'tipo' },
                { text: 'IMPUESTO', value: 'impuesto' },
                { text: 'LOS MOCHIS', value: 'los_mochis' },
                { text: 'GUASAVE', value: 'guasave' },
                { text: 'GUAMÚCHIL', value: 'guamuchil' },
                { text: 'CULIACÁN', value: 'culiacan' },
                { text: 'MAZATLÁN', value: 'mazatlan' },
                { text: 'TOTAL', value: 'total' }
            ],
            federales: [],
            catalogoFederales: [],
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
        puedeExportar() {
            return this.federales.length > 0
        },
        periodoSeleccionado() {
            if (!this.filtros.anio && !this.filtros.mes) return '';
            const anio = this.filtros.anio ?? '';
            const mesObj = this.meses.find(m => m.value === this.filtros.mes);
            const mes = mesObj ? mesObj.text : '';
            return `AÑO: ${anio}    |    MES: ${mes}`;
        },
        mesDetalleTexto() {
            const m = this.meses.find(x => x.value === this.mesDetalle)
            return m ? m.text : ''
        },
        tablaInvertidaFederales() {
            if (!this.catalogoFederales.length) return [];
            const filas = [];
            const totales = {
                los_mochis: 0,
                guasave: 0,
                guamuchil: 0,
                culiacan: 0,
                mazatlan: 0,
                total: 0
            };
            const mapaOficinas = {
                1: 'los_mochis',
                2: 'guasave',
                3: 'guamuchil',
                4: 'culiacan',
                5: 'mazatlan'
            };
            const grupos = this.catalogoFederales.reduce((acc, item) => {
                if (!acc[item.tipo]) acc[item.tipo] = [];
                acc[item.tipo].push(item);
                return acc;}, {});

            Object.entries(grupos).forEach(([tipo, items]) => {
                items.forEach((imp, index) => {
                    const fila = {tipo: index === 0 ? tipo : '',
                        impuesto: imp.depto,
                        los_mochis: 0,
                        guasave: 0,
                        guamuchil: 0,
                        culiacan: 0,
                        mazatlan: 0,
                        total: 0};
                    this.federales.forEach(f => {
                        if (f.tipo !== imp.tipo || f.depto !== imp.depto) return;
                        const keyOficina = mapaOficinas[f.oficina];
                        if (!keyOficina) return;
                        const val = Number(f.total_ordenes || 0);
                        fila[keyOficina] += val;
                        fila.total += val;
                        totales[keyOficina] += val;
                        totales.total += val;
                    });
                    filas.push(fila);
                });
            });
            filas.push({ tipo: 'TOTALES', impuesto: '', ...totales });
            return filas;
        }
    },
    components:{

    },
    async created() {
        await this.obtenerAnios();
        await this.cargarCatalogoFederales();
        await this.consultarMesActual();
        await this.cargarBarChartComparativo();
    },
    methods: {
        exportarExcelFederales() {
            const wb = XLSX.utils.book_new()
            const border = {top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' }}
            const titleStyle = {font: { bold: true, sz: 16 }, alignment: { horizontal: 'center', vertical: 'center' }}
            const headerStyle = {font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '616161' } }, alignment: { horizontal: 'center', vertical: 'center' }, border}
            const tipoStyle = {font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: 'AD1457' } }, alignment: { horizontal: 'center', vertical: 'center' }, border}
            const cellCenter = {alignment: { horizontal: 'center', vertical: 'center' }, border}
            const cellLeft = {alignment: { horizontal: 'left', vertical: 'center' }, border}
            const totalStyle = {font: { bold: true }, fill: { fgColor: { rgb: 'E0E0E0' } }, alignment: { horizontal: 'center', vertical: 'center' }, border}
            const rows = []
            rows.push([{ v: `REPORTE DE ÓRDENES FEDERALES | ${this.periodoSeleccionado}`, s: titleStyle }])
            rows.push([])
            rows.push([{ v: 'TIPO', s: headerStyle },
                { v: 'IMPUESTO', s: headerStyle },
                { v: 'LOS MOCHIS', s: headerStyle },
                { v: 'GUASAVE', s: headerStyle },
                { v: 'GUAMÚCHIL', s: headerStyle },
                { v: 'CULIACÁN', s: headerStyle },
                { v: 'MAZATLÁN', s: headerStyle },
                { v: 'TOTAL', s: headerStyle }])
            this.tablaInvertidaFederales.forEach(row => {
                const isTotal = row.tipo === 'TOTALES'
                rows.push([
                { v: row.tipo || '', s: isTotal ? totalStyle : tipoStyle },
                { v: row.impuesto || '', s: isTotal ? totalStyle : cellLeft },
                { v: row.los_mochis, s: isTotal ? totalStyle : cellCenter },
                { v: row.guasave, s: isTotal ? totalStyle : cellCenter },
                { v: row.guamuchil, s: isTotal ? totalStyle : cellCenter },
                { v: row.culiacan, s: isTotal ? totalStyle : cellCenter },
                { v: row.mazatlan, s: isTotal ? totalStyle : cellCenter },
                { v: row.total, s: isTotal ? totalStyle : cellCenter }
                ])
            })
            const ws = XLSX.utils.aoa_to_sheet(rows)
            ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 7 } }]
            ws['!cols'] = [
                { wch: 24 },
                { wch: 14 },
                { wch: 12 },
                { wch: 12 },
                { wch: 12 },
                { wch: 12 },
                { wch: 12 },
                { wch: 12 }
            ]
            XLSX.utils.book_append_sheet(wb, ws, 'Órdenes Federales')
            XLSX.writeFile(wb, 'Reporte_Ordenes_Federales.xlsx')
        },
        async consultarMesActual() {
            const fechaActual = new Date();
            const anioActual = fechaActual.getFullYear();

            if (this.anios.includes(anioActual)) {
                this.filtros.anio = anioActual;
            } else if (this.anios.length) {
                this.filtros.anio = this.anios[0];
            }

            this.filtros.mes = fechaActual.getMonth() + 1;

            await this.consultarFederales();
        },
        async obtenerAnios() {
            try {
                const { data } = await axios.post(api.dashboard, { opcion: 1 });
                this.anios = data.map(item => Number(item.anio));
            } catch (e) {
                console.error('No se pudieron obtener los años', e);
                this.anios = [];
            }
        },
        async cargarCatalogoFederales() {
            try {
                const { data } = await axios.post(api.dashboard, {opcion: 12});
                this.catalogoFederales = data;
            } catch (e) {
                console.error('Error catálogo federales', e);
                this.catalogoFederales = [];
            }
        },
        async consultarFederales() {
            try {
                this.anioConsultado = this.filtros.anio;
                this.mesConsultado = this.filtros.mes;
                const { data } = await axios.post(api.dashboard, {opcion: 13, anio: this.filtros.anio, mes: this.filtros.mes});
                this.federales = data;
            } catch (e) {
                console.error('Error federales', e);
                this.federales = [];
            }
        },
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
