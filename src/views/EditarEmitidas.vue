<template>
  <v-container>
    <v-card class="mx-auto mt-5">
        <v-card-title class="headline grey darken-3 white--text py-1 mb-10">EDITAR EMITIDAS</v-card-title>
        <v-card-text>
            <v-form ref="form">
            <v-row justify="center" class="mb-7">
                <v-col cols="12" sm="12" md="6" lg="4" xl="3">
                <v-text-field v-model="filters.orden" label="Orden" outlined class="text-h5 mayusculas" placeholder="Ingresa la orden" clearable append-icon="mdi-magnify" @keydown.enter="fetchData" @click:append="fetchData"/>
                </v-col>
                <v-col cols="12" sm="12" md="6" lg="4" xl="3">
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
                    <template v-slot:item.actions="{ item }">
                    <!-- Icono Editar en el data-table -->
                    <v-icon large class="mr-2" color="amber" dark dense alt="Editar" @click="formEditar(item)">mdi-pencil</v-icon>
                    <v-icon large class="ml-2" color="success" dark dense style="font-size: 32px" @click="generarDocumentoUnico(item)">mdi-file-word</v-icon>
                    </template>
                </v-data-table>
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
    <v-dialog v-model="dialogFecha" max-width="400">
        <v-card>
            <v-card-title class="headline">Seleccionar fecha</v-card-title>
            <v-card-text>
                <p class="mb-2">Fecha actual: <strong>{{ formatFecha(itemSeleccionado?.fecha_orden) }}</strong></p>
                <v-date-picker v-model="fechaTemp" locale="es-MX" color="primary"></v-date-picker>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn text @click="cancelarFecha">Cancelar</v-btn>
                <v-btn color="primary" @click="confirmarFecha">Continuar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <!-- Componente de Diálogo para CREAR y EDITAR (extraído) -->
    <form-crear-editar
      v-model="dialog"
      :operacion="operacion"                      
      :prospectoie-data="prospectoie"
      :municipios-listado="municipios_listado"
      :antecedentes-listado="antecedentes_listado"
      :impuestos-listado="impuestos_listado"
      :programadores-listado="programadores_listado"
      :fuentes-listado="fuentes_listado"
      :oficinas-listado="oficinas_listado"
      :cargando-prop="cargando"
      :permitir-editar-emitidas="true"
      @cerrar="dialog = false"
      @guardar="handleGuardar"
      @update:prospectoieData="updateProspectoie">
    </form-crear-editar>
  </v-container>
</template>

<script>
    import Swal from 'sweetalert2';
    import axios from 'axios';
    import api from '@/services/apiUrls.js';
    import FormCrearEditar from '@/components/formCrearEditar.vue';

export default {
    name: "EditarEmitidas",
    data() {
        return {
            dialogFecha: false,
            fechaTemp: null,
            itemSeleccionado: null,
            fechaOrdenSeleccion: null,
            dialog: false,
            operacion: "",
            prospectoie: {},
            cargando: false,
            totalRegistros: 0,
            impresoraPredeterminada: '',
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
                text: "ACCIONES",
                value: "actions",
                class: "pink darken-4 white--text center-header",
                width: "50",
                }
            ],
            impuestos_listado: [],
            antecedentes_listado:[],
            programadores_listado: [],
            oficinas_listado: [],
            municipios_listado:[],
            fuentes_listado:[],
        };
    },
    components:{
        FormCrearEditar,
    },
    created() {
        this.obtienemunicipios();
        this.obtieneantecedentes();
        this.obtieneimpuestos();
        this.obtieneusuarios();
        this.obtienefuentes();
        this.obtieneoficinas();
        this.obtenerImpresora();
        },
    methods: {
        async confirmarFecha() {
            this.dialogFecha = false;
            this.fechaOrdenSeleccion = this.fechaTemp;
            try {
                this.cargando = true;
                const response = await axios.post(api.editarEmitidas, {opcion: 3, id: this.itemSeleccionado.id_prospectos_siga});
                this.cargando = false;
                if (response.data && Object.keys(response.data).length) {
                    const data = response.data;
                    this.operacion = "editar";
                    this.dialog = true;
                    this.prospectoie = {
                        ...data,
                        fecha_orden: this.fechaOrdenSeleccion || data.fecha_orden,
                        id_orden: this.itemSeleccionado.id_orden_siga,
                        id: Number(data.id),
                        municipio_id: data.municipio_id,
                        oficina_id: Number(data.oficina_id),
                        fuente_id: data.fuente_id,
                        impuesto_id: data.impuesto_id,
                        antecedente_id: data.antecedente_id,
                        programador_id: Number(data.programador_id),
                        estatus: Number(data.estatus),
                        retenedor: Number(data.retenedor ?? 0),
                        cambio_domicilio: Number(data.cambio_domicilio ?? 0),
                        determinado: data.determinado ?? 0,
                        origen_id: Number(data.origen_id ?? 0),
                    };
                }

            } catch (error) {
                this.cargando = false;
                this.$Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se pudo obtener la información"
                });
            }
        },
        cancelarFecha() {
            this.dialogFecha = false;
            this.itemSeleccionado = null;
            this.fechaTemp = null;
        },
        formatoISO(fecha) {
            if (!fecha) return "";
            return fecha.split("T")[0];
        },
        async obtenerImpresora() {
            try {
                const { data } = await axios.post(api.generarOrdenes, {
                opcion: 7
                });

                this.impresoraPredeterminada = data.impresora || 'No detectada';
            } catch (e) {
                console.error('No se pudo obtener la impresora', e);
                this.impresoraPredeterminada = 'No disponible';
            }
        },
        async generarDocumentoUnico(item) {
            this.fechaOrdenSeleccion = null;      
            const { value: numCopias } = await Swal.fire({
                title: 'Número de impresiones',
                html: `<p style="margin-bottom:6px">
                    El documento se enviará a la impresora predeterminada del servidor:
                </p>
                <b>${this.impresoraPredeterminada}</b>`,
                input: 'number',
                inputLabel: '¿Cuántos juegos desea imprimir?',
                inputValue: 1,
                showCancelButton: true,
                inputValidator: (value) => {
                if (!value || value < 1) {
                    return '¡Necesitas ingresar un número válido de impresiones!'
                }
                }
            });

            if (!numCopias) return;

            this.cargando = true;
            const resp = await this.generarDocumento(item, numCopias);
            this.cargando = false;
            if (resp && resp.success) {        
                if (resp.impreso === true) {
                Swal.fire({
                    title: "Impresión completada",
                    text: "El documento se generó y se envió a la impresora.",
                    icon: "success",
                    timer: 2500
                });
                } else {
                Swal.fire({
                    title: "Generado pero no impreso",
                    text: "El documento se generó correctamente pero no se imprimió.",
                    icon: "warning"
                });
                }
            } else {
                Swal.fire("Error", "No se pudo generar el documento.", "error");
            }
        },
        async generarDocumento(item, numCopias) {      
            try {
                let data = {};
                data = {
                    opcion: 4,
                    prospecto: {
                        id: item.id_prospectos_siga
                    },
                    copias: numCopias,
                    usuario_id: Number(localStorage.getItem('siga_id')),
                    fecha_orden: this.fechaOrdenSeleccion || item.fecha_orden
                };
                console.log("Enviando datos para REIMPRESIÓN:", data);
                const response = await axios.post(api.generarOrdenes, data, { responseType: 'blob' });
                console.log("Respuesta del servidor (blob):", response);
                if (response.data.size > 0) {
                    const text = await response.data.text();
                    if (text.includes('Error') || text.includes('Warning')) {
                        console.error('Error en backend:', text);
                        return { success: false, impreso: false };
                    }
                    return { success: true, impreso: true };
                } else {
                    return { success: false, impreso: false };
                }
            } catch (error) {
                this.cargando = false;
                console.error("Error en la petición a generar_ordenes.php:", error);
                Swal.fire('Error', 'Hubo un problema de comunicación con el servidor.', 'error');
                return { success: false, impreso: false };
            }
        },
        updateProspectoie(updated) {
            this.prospectoie = {
                ...this.prospectoie,
                ...updated
            };
        },
        async handleGuardar(data, periodosParaAgregar) {
            try {
                if (this.operacion === "editar") {
                    const response = await axios.post(api.editarEmitidas, {
                    opcion: 4,
                    ...data,
                    fecha_orden: this.fechaOrdenSeleccion || data.fecha_orden
                    });

                    if (response.data.status) {
                        this.$Swal.fire({
                            icon: "success",
                            title: "Actualizado",
                            text: response.data.msg || "Prospecto actualizado correctamente",
                        });
                        this.dialog = false;
                        this.fetchData();
                    } else {
                        this.$Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: response.data.msg || "Ocurrió un error al actualizar",
                        });
                    }
                }
            } catch (error) {
                this.$Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Error de conexión con el servidor",
                });
            }
        },
        async formEditar(item) {
            this.itemSeleccionado = item;
            this.fechaTemp = this.formatoISO(item.fecha_orden);
            this.dialogFecha = true;
        },
        formatFecha(fecha) {
            if (!fecha) return "";
            fecha = fecha.split("T")[0];
            const [anio, mes, dia] = fecha.split("-");
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
                const response = await axios.post(api.editarEmitidas, {opcion: 2, orden: this.filters.orden || null, rfc: this.filters.rfc || null}
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
        obtieneoficinas: function () {
            axios.post(api.oficinas_listado).then((response) => {
                this.oficinas_listado = response.data;
            });
        },
        obtienefuentes: function () {
            axios.post(api.fuentes).then((response) => {
                this.fuentes_listado = response.data;
            });
           },
        obtieneimpuestos: function () {
            axios.post(api.impuestos).then((response) => {
                this.impuestos_listado = response.data;
            });
        },
        obtieneantecedentes: function () {
            axios.post(api.antecedentes).then((response) => {
                this.antecedentes_listado = response.data;
            });
        },
        obtieneusuarios: function () {
            const usuarioId = localStorage.getItem('siga_id');
            axios.post(api.programadores, {  opcion: 2, id: usuarioId  }).then((response) => {
                this.programadores_listado = response.data;
            });
        },
        obtienemunicipios: function () {
            axios.post(api.municipios).then((response) => {
                this.municipios_listado = response.data;
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
