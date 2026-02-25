<template>
    <div>
        <!-- Componente de Diálogo para Crear y Editar Memos -->
        <v-dialog v-model="dialog" max-width="1100px" persistent :retain-focus="!cargando" >
            <v-card>
                <v-form>
                    <v-card-title class="pink darken-4 white--text py-2">MEMORANDUM
                        <v-spacer></v-spacer>
                        <span class="text-h6">
                        <template v-if="operacion === 'editar'">EDITAR (<span class="yellow--text"></span>)
                        </template>
                        <template v-else>{{ operacion.toUpperCase() }}</template>
                        </span>
                    </v-card-title>
                    <v-card-text class="mb-2 py-0">
                        <v-container>
                            <v-row class="my-2 pt-4">
                                 <!-- Fecha -->
                                <v-col class="my-0 py-0" cols="12" md="3">
                                    <v-text-field reverse readonly dense outlined maxlength="10" v-model="memo.fecha_memo" label="Fecha memo"></v-text-field>
                                </v-col>
                                <!-- Oficina -->
                                <v-col class="my-0 py-0" cols="12" md="4">
                                    <v-select v-model="memo.oficina_id" :items="oficinas" item-text="nombre" item-value="id" label="Oficina" outlined dense></v-select>
                                </v-col>
                                <!-- Departamento -->
                                <v-col class="my-0 py-0" v-if="memo.oficina_id === 4" cols="12" md="5">
                                   <v-select v-model="memo.departamento_id" :items="departamentosFiltrados" item-text="nombre" item-value="id" label="Departamento" outlined dense/>
                                </v-col>
                                <!-- Destinatario -->
                                <v-col class="my-0 py-0" cols="12" md="6">
                                    <v-text-field v-model="memo.destinatario" label="Destinatario" outlined dense readonly></v-text-field>
                                </v-col>
                                <!-- Asunto -->
                                <v-col class="my-0 py-0" cols="12" md="6">
                                    <v-text-field class="my-0 py-0 mayusculas" v-model="memo.asunto" @blur="memo.asunto = memo.asunto?.trim()" label="Asunto" maxlength="150" outlined dense></v-text-field>
                                </v-col>
                                <!-- Usuario -->
                                <v-col class="my-0 py-0" cols="12" md="3">
                                    <v-text-field v-model="memo.usuario" label="Usuario" outlined dense readonly></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                        <v-divider></v-divider>
                        <v-container fluid v-if="operacion === 'crear'">
                            <v-row class="my-2 pt-4">
                                <v-col cols="12">
                                    <v-data-table v-model="selectedProspectos" :headers="encabezadosProspectos" :items="prospectosie" item-key="id" show-select dense class="elevation-1" :loading="cargando" :disabled="cargando"/>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="grey lighten-2 py-2" cols="12" md="12">
                        <v-spacer></v-spacer>
                        <v-btn class="my-1 ma-2 py-1" color="success" :loading="cargando" :disabled="cargando" @click="validar" dark>Guardar<v-icon dark right>mdi-checkbox-marked-circle</v-icon></v-btn>
                        <v-btn class="ma-2" dark :disabled="cargando" @click="cerrar">Cancelar<v-icon dark left>mdi-cancel</v-icon></v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>
    </div>
</template>
<script>
import Swal from 'sweetalert2';

export default {
    name: "FormMemosCrearEditar",
    props: {
        value: Boolean,
        operacion: String,
        oficinas: Array,
        departamentos: Array,
        destinatarios: Array,
        impresora: {
            type: String,
            default: ''
        },
        prospectosie: {
            type: Array,
            default: () => []
        },
        memo: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            cargando: false,
            selectedProspectos: [],
            encabezadosProspectos: [
                { text: 'FOLIO', value: 'num_oficio' },
                { text: 'ORDEN', value: 'num_orden' },
                { text: 'RFC', value: 'rfc' },
                { text: 'NOMBRE', value: 'nombre' },
                { text: 'OFICINA', value: 'oficina' },
            ],
        };
    },
    computed: {
        departamentosFiltrados() {
            if (this.memo.oficina_id !== 4) return [];
            return this.departamentos;
        },
        dialog: {
        get() {
            return this.value;
        },
        set(val) {
            this.$emit('input', val);
        }
        }
    },
    watch: {
        value(val) {
            if (!val) {
            this.cargando = false;
            this.selectedProspectos = [];
            }
        },
        operacion() {
            this.cargando = false;
        },
        'memo.oficina_id'(val) {
            // Si cambia oficina y no es 4
            if (val !== 4) {
            this.memo.departamento_id = null;
            this.asignarDestinatario();
            }
        },
        'memo.departamento_id'() {
            this.asignarDestinatario();
        },
        destinatarios(newVal) {
            if (newVal.length && this.memo.oficina_id) {
            this.asignarDestinatario();
            }
        },
        dialog(val) {
            if (val && this.operacion === 'crear') {
                this.memo.oficina_id = null;
                this.memo.departamento_id = null;
                this.memo.fecha_memo = this.fechaactual();
                this.memo.destinatario = '';
                this.memo.asunto = '';
                this.memo.usuario = localStorage.getItem('siga_nombre');
            }
        },
    },
    methods: {
        asignarDestinatario() {
            let destinatario = null;

            const oficinaId = Number(this.memo.oficina_id);
            const deptoId = Number(this.memo.departamento_id);

            if (oficinaId === 4 && deptoId) {
                destinatario = this.destinatarios.find(d =>
                    Number(d.oficina_id) === oficinaId &&
                    Number(d.departamento_id) === deptoId
                );
            } else {
                destinatario = this.destinatarios.find(d =>
                    Number(d.oficina_id) === oficinaId
                );
            }

            this.memo.destinatario = destinatario
                ? destinatario.nombre_completo
                : '';
        },
        fechaactual() {
            const hoy = new Date();
            const d = String(hoy.getDate()).padStart(2, '0');
            const m = String(hoy.getMonth() + 1).padStart(2, '0');
            const y = hoy.getFullYear();
            return `${d}/${m}/${y}`;
        },
        // Lógica de validación
        
        async validar() {
            if (this.cargando) return;
            if (!this.impresora) {
            await Swal.fire('Error', 'No hay impresora configurada en el servidor', 'error');
            return;
            }

            const { value: numCopias } = await Swal.fire({
            title: 'Número de impresiones',
            html: `<p style="margin-bottom:6px">
                El documento se enviará a la impresora predeterminada del servidor:
                </p>
                <b>${this.impresora}</b>`,
            input: 'number',
            inputLabel: '¿Cuántos juegos desea imprimir?',
            inputValue: 1,
            showCancelButton: true,
            inputValidator: (value) => {
                if (!value || value < 1) {
                return '¡Necesitas ingresar un número válido de impresiones!';
                }
            }
            });
            if (!numCopias) return;

            var textoMostrar='';
            // Validaciones
            if (!this.memo.oficina_id) {
            textoMostrar = 'La oficina es obligatoria';
            }
            else if (this.memo.oficina_id === 4 && !this.memo.departamento_id) {
            textoMostrar = 'Debe seleccionar un departamento para esta oficina';
            }
            else if (!this.memo.destinatario || !this.memo.asunto || !this.memo.fecha_memo) {
            textoMostrar = 'Es necesario llenar toda la información';
            }
            if (textoMostrar) {
                await Swal.fire({
                icon: 'warning',
                title: 'Datos incompletos',
                text: textoMostrar,
                confirmButtonText: 'Entendido',
                });
                return;
            }else{
                if (this.operacion === 'crear' && !this.selectedProspectos.length) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Sin prospectos',
                        text: 'Debes seleccionar al menos un prospecto para el memorándum',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
                const payload = {
                    ...this.memo,
                    copias: numCopias,
                    prospectos: this.operacion === 'crear'
                        ? this.selectedProspectos.map(p => ({
                            id: p.id,
                            num_oficio: p.num_oficio,
                            num_orden: p.num_orden,
                            oficina_id: p.oficina_id,
                            rfc: p.rfc}))
                        : []
                };
                this.cargando = true;
                this.$emit('guardar', payload);
                //this.cerrar();
            }
        },
        cerrar() {
            if (this.cargando) return;
            this.$emit('cerrar');
        }
    },
};
</script>