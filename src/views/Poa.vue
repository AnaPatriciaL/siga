<template>
  <v-container>
    <v-row class="center">
      <v-spacer></v-spacer>
      <v-col cols="12" class="text-center">
        <h1>PROGRAMA OPERATIVO ANUAL</h1>
      </v-col>
    </v-row>
    <v-row justify="center" class="mt-4">
      <v-col cols="12" sm="12" md="7" lg="6" xl="5">
        <v-card>
          <v-card-title class="pink darken-4 white--text py-2">CAPTURA DE POA: IMPUESTOS ESTATALES</v-card-title>
          <v-spacer></v-spacer>
          <v-card-text>
            <v-form>
                <v-row>
                    <v-col cols="12"><v-text-field label="Año" v-model="anio" type="number" outlined dense/></v-col>
                    <v-col cols="12">
                        <v-simple-table>
                            <thead>
                                <tr>
                                    <th>MES</th>
                                    <th class="text-center">META</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in meses" :key="m.mes">
                                    <td>{{ m.nombre }}</td>
                                    <td class="text-center"><v-text-field v-model.number="m.meta" min="1" type="number" :disabled="bloqueado" dense outlined hide-details/></td>
                                </tr>
                            </tbody>
                        </v-simple-table>
                    </v-col>
                    <v-col cols="12" class="text-center"><v-btn color="pink darken-4" dark :disabled="bloqueado || !formularioCompleto" @click="guardarPOA">Guardar metas</v-btn></v-col>
                </v-row>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
<script>
    import Swal from 'sweetalert2';
    import axios from 'axios';
    import api from '@/services/apiUrls.js';

    export default {
        name: "Poa",
        data() {
            return {
                bloqueado: false,
                anio: new Date().getFullYear(),
                meses: [
                    { mes: 1, nombre: 'ENERO', meta: null },
                    { mes: 2, nombre: 'FEBRERO', meta: null },
                    { mes: 3, nombre: 'MARZO', meta: null },
                    { mes: 4, nombre: 'ABRIL', meta: null },
                    { mes: 5, nombre: 'MAYO', meta: null },
                    { mes: 6, nombre: 'JUNIO', meta: null },
                    { mes: 7, nombre: 'JULIO', meta: null },
                    { mes: 8, nombre: 'AGOSTO', meta: null },
                    { mes: 9, nombre: 'SEPTIEMBRE', meta: null },
                    { mes: 10, nombre: 'OCTUBRE', meta: null },
                    { mes: 11, nombre: 'NOVIEMBRE', meta: null },
                    { mes: 12, nombre: 'DICIEMBRE', meta: null }
                ]
            }
        },
        mounted () {
            this.validarAnio()
        },
        computed: {
            formularioCompleto() {
                if (!this.anio || this.anio <= 0) return false;

                return this.meses.every(m =>
                m.meta !== null && m.meta !== '' && m.meta > 0
                );
            }
        },
        watch: {
            anio () {
                this.bloqueado = false
                this.validarAnio()
            }
        },
        methods: {
            limpiarFormulario() {
                this.anio = new Date().getFullYear(); // o null si prefieres
                this.meses.forEach(m => m.meta = null);
                this.bloqueado = false;
            },
            async guardarPOA() {
                if (this.bloqueado) {
                    Swal.fire('Aviso', 'Este año ya está capturado', 'info');
                    return;
                }
                 if (!this.anio || this.anio <= 0) {
                    Swal.fire('Aviso', 'Captura un año válido', 'warning');
                    return;
                }
                const metasIncompletas = this.meses.some(m =>m.meta === null || m.meta === '' || m.meta <= 0);

                if (metasIncompletas) {
                    Swal.fire('Campos incompletos',
                    'Debes capturar una meta válida en todos los meses',
                    'warning');
                    return;
                }
                const payload = {
                    opcion: 1,
                    anio: this.anio,
                    metas: this.meses.map(m => ({
                    mes: m.mes,
                    meta: m.meta
                    }))
                };
                try {
                    await axios.post(api.poa, payload);
                    Swal.fire('Éxito', 'Metas guardadas correctamente', 'success');
                    this.limpiarFormulario();
                } catch (e) {
                    Swal.fire('Error', 'No se pudieron guardar las metas', 'error');
                }
            },
            async validarAnio () {
                const { data } = await axios.post(api.poa, {
                    opcion: 2,
                    anio: this.anio
                })

                if (data.existe) {
                    Swal.fire('Año ya capturado',
                        `El POA del año ${this.anio} ya fue registrado.`,
                        'info');
                this.bloquearCaptura();
                } else {
                this.bloqueado = false;
                }
            },
            bloquearCaptura () {
                this.meses.forEach(m => m.meta = null);
                this.bloqueado = true;
            },
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

.center-header {
  text-align: center;
}
</style>
