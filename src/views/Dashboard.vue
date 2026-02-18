<template> 
    <v-container >
        <!-- Botón Crear y Exportar fluid -->
        <v-row class="center">
            <v-spacer></v-spacer>
            <v-col cols="12"  class="text-center">
                <h1>DASHBOARD ÓRDENES</h1>
            </v-col>
        </v-row>
        <v-row class="mb-6">
            <v-col cols="12">
                <v-card elevation="2">
                    <v-card-title class="font-weight-bold">COMPARATIVO DE ÓRDENES EMITIDAS POR MES</v-card-title>
                    <v-card-text style="height: 350px"><canvas ref="barChart"></canvas></v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-card elevation="2">
            <div class="dashboard-estatales">
                <v-row class="mb-4 mt-2" align="center">
                    <v-col class="d-flex align-center" cols="12" md="2"><v-select v-model="filtros.anio" :items="anios" label="Año" dense outlined hide-details class="mr-4"/></v-col>
                    <V-col cols="12" md="2"><v-select v-model="filtros.mes" :items="meses" item-text="text" item-value="value" label="Mes" dense outlined hide-details/></v-col>
                    <v-col cols="12" md="2"><v-btn color="pink darken-4 white--text" @click="consultar">MOSTRAR</v-btn></v-col>
                    <v-col cols="12" md="4"><v-text-field label="Periodo seleccionado" :value="periodoSeleccionado" outlined dense hide-details readonly/></v-col>
                    <v-col cols="12" md="2" class="text-right"><v-btn v-if="puedeExportar" color="success white--text" dark @click="exportarExcelEstatales">EXPORTAR A EXCEL</v-btn></v-col>
                </v-row>
                <v-data-table :headers="encabezadosInvertidos" :items="tablaInvertida" hide-default-header hide-default-footer :items-per-page="-1" dense class="elevation-1">
                    <template v-slot:header>
                        <thead>
                            <tr class="grey darken-1">
                                <th v-for="h in encabezadosInvertidos" :key="h.value" class="white--text text-center">{{ h.text }}</th>
                            </tr>
                        </thead>
                    </template>
                    <template v-slot:item.tipo="{ item }">
                        <td v-if="item.tipo" class="pink darken-4 white--text font-weight-bold text-center">{{ item.tipo }}</td>
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
        <v-dialog v-model="mostrarDetalleMes" max-width="1200px" persistent scrollable>
            <v-card>
                <v-card-title class="detalle-titulo">
                    <span class="titulo-texto">ÓRDENES DEL MES: {{ mesDetalleTexto }} {{ anioDetalle }}</span>
                    <v-spacer />
                    <v-btn icon small @click="cerrarDetalleMes"><v-icon>mdi-close</v-icon></v-btn>
                </v-card-title>
                <v-divider/>
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
    name: "Dashboard",
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
            anioConsultado: null,
            mesConsultado: null,
            barChart: null,
            filtros: {anio: null, mes: null},
            anios: [],
            estatales: [],
            totales_oficina:{mochis:0, guasave:0, guamuchil:0, culiacan:0, mazatlan:0, total:0},
            totales: {nomina: 0, obtencionPremios: 0, sorteosyjuegos: 0, predialRustico: 0, hospedaje: 0, casasEmpeno: 0, erogaciones: 0, cartaNomina: 0,
            cartaHospedaje: 0, cartaPremios: 0, cartasorteosyjuegos: 0, cartacasasEmpeno: 0, cartapredialRustico: 0, cartaerogaciones: 0, gabineteNomina: 0},
            meses: [{ text: 'ENERO', value: 1 }, { text: 'FEBRERO', value: 2 }, { text: 'MARZO', value: 3 }, { text: 'ABRIL', value: 4 }, 
            { text: 'MAYO', value: 5 }, { text: 'JUNIO', value: 6 }, { text: 'JULIO', value: 7 }, { text: 'AGOSTO', value: 8 }, { text: 'SEPTIEMBRE', value: 9 }, 
            { text: 'OCTUBRE', value: 10 }, { text: 'NOVIEMBRE', value: 11 }, { text: 'DICIEMBRE', value: 12 }, { text: 'TODOS', value: 0 }],
            encabezados: [{value: 'oficina_descripcion', align: 'left', width: '200px' },
            {value: 'visitas_nomina', align: 'center' }, {value: 'visitas_obtencionPremios', align: 'center' }, {value: 'visitas_sorteosyjuegos', align: 'center' },
            {value: 'visitas_predialRustico', align: 'center' }, {value: 'visitas_hospedaje', align: 'center' }, {value: 'visitas_casasEmpeno', align: 'center' },
            {value: 'visitas_erogaciones', align: 'center' }, {value: 'cartaInvitacion_impuestoNomina', align: 'center' }, {value: 'cartaInvitacion_impuestoHospedaje', align: 'center' },
            {value: 'cartaInvitacion_obtencionPremios', align: 'center' }, {value: 'cartaInvitacion_sorteosyjuegos', align: 'center' }, {value: 'cartaInvitacion_casasEmpeno', align: 'center' },
            {value: 'cartaInvitacion_predialRustico', align: 'center' }, {value: 'cartaInvitacion_erogaciones', align: 'center' }, {value: 'gabineteNomina', align: 'center' }],
            encabezadosInvertidos: [{ text: 'TIPO', value: 'tipo', class: 'col-tipo'}, { text: 'IMPUESTO', value: 'impuesto' }, { text: 'LOS MOCHIS', value: 'los_mochis' },
            { text: 'GUASAVE', value: 'guasave' }, { text: 'GUAMÚCHIL', value: 'guamuchil' }, { text: 'CULIACÁN', value: 'culiacan' }, { text: 'MAZATLÁN', value: 'mazatlan' }, { text: 'TOTAL', value: 'total' }]
        };
    },
    computed: {
        mesDetalleTexto() {
            const m = this.meses.find(x => x.value === this.mesDetalle)
            return m ? m.text : ''
        },
        puedeExportar() {
            return (
            this.estatales.length > 0 &&
            this.filtros.anio === this.anioConsultado &&
            this.filtros.mes === this.mesConsultado
            )
        },
        tablaInvertida () {
            const grupos = {
                'VISITAS DOMICILIARIAS': [
                { impuesto: 'D-ISN', key: 'visitas_nomina' },
                { impuesto: 'D-IOP', key: 'visitas_obtencionPremios' },
                { impuesto: 'D-ISJ', key: 'visitas_sorteosyjuegos' },
                { impuesto: 'D-IPRM', key: 'visitas_predialRustico' },
                { impuesto: 'D-ISH', key: 'visitas_hospedaje' },
                { impuesto: 'D-ICE', key: 'visitas_casasEmpeno' },
                { impuesto: 'D-IEJA', key: 'visitas_erogaciones' }
                ],
                'CARTA INVITACIÓN': [
                { impuesto: 'M-ISN', key: 'cartaInvitacion_impuestoNomina' },
                { impuesto: 'M-ISH', key: 'cartaInvitacion_impuestoHospedaje' },
                { impuesto: 'M-IOP', key: 'cartaInvitacion_obtencionPremios' },
                { impuesto: 'M-ISJ', key: 'cartaInvitacion_sorteosyjuegos' },
                { impuesto: 'M-ICE', key: 'cartaInvitacion_casasEmpeno' },
                { impuesto: 'M-IPRM', key: 'cartaInvitacion_predialRustico' },
                { impuesto: 'M-IEJA', key: 'cartaInvitacion_erogaciones' }
                ],
                'GABINETE': [
                { impuesto: 'G-ISN', key: 'gabineteNomina' }
                ]
            }
            const filas = []
            const totales = {los_mochis: 0, guasave: 0, guamuchil: 0, culiacan: 0, mazatlan: 0, total: 0}
            Object.entries(grupos).forEach(([tipo, impuestos]) => {
                impuestos.forEach((imp, index) => {
                const fila = {
                    tipo: index === 0 ? tipo : '',
                    impuesto: imp.impuesto,
                    los_mochis: 0,
                    guasave: 0,
                    guamuchil: 0,
                    culiacan: 0,
                    mazatlan: 0,
                    total: 0
                }
                this.estatales.forEach(e => {
                    const oficina = this.normalizarOficina(e.oficina_descripcion)
                    if (!fila.hasOwnProperty(oficina)) return
                    const val = Number(e[imp.key] || 0)
                    fila[oficina] += val
                    fila.total += val
                    totales[oficina] += val
                    totales.total += val
                })
                filas.push(fila)
                })
            })
            filas.push({
                tipo: 'TOTALES',
                impuesto: '',
                ...totales
            })
            return filas
        },
        periodoSeleccionado() {
            if (!this.filtros.anio && !this.filtros.mes) return '';
            const anio = this.filtros.anio ?? '';
            const mesObj = this.meses.find(m => m.value === this.filtros.mes);
            const mes = mesObj ? mesObj.text : '';
            return `AÑO: ${anio}    |    MES: ${mes}`;
        }
    },
    components:{

    },
    async created() {
        this.obtenerPermisos();
        await this.obtenerAnios();
        this.consultarMesActual();
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
                const { data } = await axios.post(api.dashboard, {opcion: 4, anio, mes})
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
                const { data } = await axios.post(api.dashboard, {opcion: 3});
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
                        data: totalesAnterior
                    },
                    {
                        label: anioActual.toString(),
                        data: totalesActual
                    }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
        },
        normalizarOficina(nombre) {
            return (nombre || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '_')
        },
        exportarExcelEstatales() {
            const wb = XLSX.utils.book_new()
            const border = {top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' }}
            const titleStyle = {font: { bold: true, sz: 16 }, alignment: { horizontal: 'center', vertical: 'center' }}
            const headerStyle = {font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '616161' } }, alignment: { horizontal: 'center', vertical: 'center' }, border}
            const tipoStyle = {font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: 'AD1457' } }, alignment: { horizontal: 'center', vertical: 'center' }, border}
            const cellCenter = {alignment: { horizontal: 'center', vertical: 'center' }, border}
            const cellLeft = {alignment: { horizontal: 'left', vertical: 'center' }, border}
            const totalStyle = {font: { bold: true }, fill: { fgColor: { rgb: 'E0E0E0' } }, alignment: { horizontal: 'center', vertical: 'center' }, border}
            const rows = []
            rows.push([{ v: `REPORTE DE ÓRDENES ESTATALES | ${this.periodoSeleccionado}`, s: titleStyle }])
            rows.push([])
            rows.push([{ v: 'TIPO', s: headerStyle },
                { v: 'IMPUESTO', s: headerStyle },
                { v: 'LOS MOCHIS', s: headerStyle },
                { v: 'GUASAVE', s: headerStyle },
                { v: 'GUAMÚCHIL', s: headerStyle },
                { v: 'CULIACÁN', s: headerStyle },
                { v: 'MAZATLÁN', s: headerStyle },
                { v: 'TOTAL', s: headerStyle }])
            this.tablaInvertida.forEach(row => {
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
            XLSX.utils.book_append_sheet(wb, ws, 'Órdenes Estatales')
            XLSX.writeFile(wb, 'Reporte_Ordenes_Estatales.xlsx')
        },
        async obtenerPermisos() {
            try {
                const response = await axios.get(api.sessionCheck);
                this.sessionData = response.data;
                const nivel = Number(this.sessionData?.nivel); 
                this.permiso = [0, 2].includes(nivel);
            } catch (error) {
                console.error('Error al obtener los datos de la sesión:', error);
            }
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
        async consultar() {
            if (this.filtros.anio == null || this.filtros.mes == null) {
                Swal.fire('Advertencia', 'Por favor, selecciona un año y un mes antes de consultar.', 'warning');
                return;
            }
            try { 
                const { data } = await axios.post(api.dashboard, {
                    opcion: 2,
                    anio: this.filtros.anio,
                    mes: this.filtros.mes
                });
                this.estatales = this.mapearDatosDashboard(data);
                this.calcularTotales();
                this.anioConsultado = this.filtros.anio;
                this.mesConsultado = this.filtros.mes;              
            }catch (e)
            {
                console.error('Error al consultar los datos del dashboard', e);
                Swal.fire('Error', 'No se pudieron obtener los datos del dashboard', 'error');
            }
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
            await this.consultar();
        }, 
        mapearDatosDashboard(data) {
            const resultado = {};

            data.forEach(item => {
                const oficina = item.nombre;
                const impuesto = (item.impuesto || '').toUpperCase();

                if (!resultado[oficina]) {
                resultado[oficina] = {
                    oficina_descripcion: oficina,
                    visitas_nomina: 0,
                    visitas_obtencionPremios: 0,
                    visitas_sorteosyjuegos: 0,
                    visitas_predialRustico: 0,
                    visitas_hospedaje: 0,
                    visitas_casasEmpeno: 0,
                    visitas_erogaciones: 0,
                    cartaInvitacion_impuestoNomina: 0,
                    cartaInvitacion_impuestoHospedaje: 0,
                    cartaInvitacion_obtencionPremios: 0,
                    cartaInvitacion_sorteosyjuegos: 0,
                    cartaInvitacion_casasEmpeno: 0,
                    cartaInvitacion_predialRustico: 0,
                    cartaInvitacion_erogaciones: 0,
                    gabineteNomina: 0
                };
                }

                const total = Number(item.total) || 0;

                if (impuesto.startsWith('G-')) {
                resultado[oficina].gabineteNomina += total;

                } else if (impuesto.startsWith('M-')) {
                    if (impuesto.includes('ISN')) {
                        resultado[oficina].cartaInvitacion_impuestoNomina += total;
                    }else if (impuesto.includes('ISH')) {
                        resultado[oficina].cartaInvitacion_impuestoHospedaje += total;
                    }else if (impuesto.includes('IOP')) {
                        resultado[oficina].cartaInvitacion_obtencionPremios += total;
                    }else if (impuesto.includes('ISJ')) {
                        resultado[oficina].cartaInvitacion_sorteosyjuegos += total;
                    }else if (impuesto.includes('ICE')) {
                        resultado[oficina].cartaInvitacion_casasEmpeno += total;
                    }else if (impuesto.includes('IPRM')) {
                        resultado[oficina].cartaInvitacion_predialRustico += total;
                    }else if (impuesto.includes('IEJA')) {
                        resultado[oficina].cartaInvitacion_erogaciones += total;
                    }
                } else {
                if (impuesto.startsWith('ISN')) resultado[oficina].visitas_nomina += total;
                if (impuesto.startsWith('IOP')) resultado[oficina].visitas_obtencionPremios += total;
                if (impuesto.startsWith('ISJ')) resultado[oficina].visitas_sorteosyjuegos += total;
                if (impuesto.startsWith('IPRM')) resultado[oficina].visitas_predialRustico += total;
                if (impuesto.startsWith('ISH')) resultado[oficina].visitas_hospedaje += total;
                if (impuesto.startsWith('ICE')) resultado[oficina].visitas_casasEmpeno += total;
                if (impuesto.startsWith('IEJA')) resultado[oficina].visitas_erogaciones += total;
                }
            });

            return Object.values(resultado);
        },
        calcularTotales() {
            Object.keys(this.totales).forEach(k => this.totales[k] = 0);

            this.estatales.forEach(row => {
            this.totales.nomina += row.visitas_nomina;
            this.totales.obtencionPremios += row.visitas_obtencionPremios;
            this.totales.sorteosyjuegos += row.visitas_sorteosyjuegos;
            this.totales.predialRustico += row.visitas_predialRustico;
            this.totales.hospedaje += row.visitas_hospedaje;
            this.totales.casasEmpeno += row.visitas_casasEmpeno;
            this.totales.erogaciones += row.visitas_erogaciones;
            this.totales.cartaNomina += row.cartaInvitacion_impuestoNomina;
            this.totales.cartaHospedaje += row.cartaInvitacion_impuestoHospedaje;
            this.totales.cartaPremios += row.cartaInvitacion_obtencionPremios;
            this.totales.cartasorteosyjuegos += row.cartaInvitacion_sorteosyjuegos;
            this.totales.cartacasasEmpeno += row.cartaInvitacion_casasEmpeno;
            this.totales.cartapredialRustico += row.cartaInvitacion_predialRustico;
            this.totales.cartaerogaciones += row.cartaInvitacion_erogaciones;
            this.totales.gabineteNomina += row.gabineteNomina;
            });
        },
        mapearDatosDashboardOficina(data) {
            const resultado = {};

            data.forEach(item => {
                const oficina = item.oficina_id;

                if (!resultado[oficina]) {
                resultado[oficina] = {
                    oficina_descripcion: oficina,
                    totalmochis: 0,
                    totalguasave: 0,
                    totalguamuchil: 0,
                    totalculiacan: 0,
                    totalmazatlan: 0
                };
                }

                const total = Number(item.total) || 0;

                if (oficina === "1") {
                    resultado[oficina].totalmochis += total;
                } else if (oficina === "2") {
                    resultado[oficina].totalguasave += total;
                } else if (oficina === "3") {
                    resultado[oficina].totalguamuchil += total;
                } else if (oficina === "4") {
                    resultado[oficina].totalculiacan += total;
                } else if (oficina === "5") {
                   resultado[oficina].totalmazatlan += total;
                }
            });

            return Object.values(resultado);
        },
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
.dashboard-estatales {
  margin: 0 auto;
  overflow-y: visible;
  padding: 5px;
  max-width: 100%;
}
.dashboard-estatales .v-data-table {
  width: 100%;
}
.dashboard-estatales .v-data-table table {
  width: 100%;
  table-layout: auto;
}
.dashboard-estatales th, .dashboard-estatales td {
  text-align: center;
  max-width: 120px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dashboard-estatales th:first-child, .dashboard-estatales td:first-child {
  width: 280px;
  max-width: 280px;
  text-align: left;
  white-space: normal;
}
.dashboard-estatales th:nth-child(16), .dashboard-estatales td:nth-child(16) {
  width: 80px;
  max-width: 80px;
}
.dashboard-estatales .v-data-table thead tr:first-child th {
  min-height: 30px;
  max-width: none !important;
  white-space: nowrap;
  vertical-align: middle;
  font-size: 15px;
  font-weight: 600;
  border: 1px solid #ffffff !important;
}
.dashboard-estatales .v-data-table thead tr:nth-child(2) th {
  border: 1px solid #ffffff;
}
.dashboard-estatale .dashboard-estatales td {
  max-width: 150px;
  text-align: center;
}
.dashboard-estatales th:first-child, .dashboard-estatales td:first-child {
  text-align: left;
}
.dashboard-estatales th:not(:first-child), .dashboard-estatales td:not(:first-child) {
  max-width: 120px;
  white-space: nowrap;
}
.v-data-table td {
  text-align: center;
}
</style>
