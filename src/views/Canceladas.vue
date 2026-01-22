<template>
  <v-container>
    <v-row class="center">
      <v-spacer></v-spacer>
      <v-col cols="12" class="text-center">
        <h1>CANCELAR ORDEN</h1>
      </v-col>
    </v-row>
    <v-row justify="center" class="mt-4">
      <v-col cols="12" md="4">
        <v-card>
          <v-card-title class="pink darken-4 white--text py-2">
            Cancelar Orden
          </v-card-title>
          <v-card-text>
            <v-form>
              <v-container>
                <v-row>
                  <!-- Orden para cancelar -->
                  <v-col cols="12" md="8">
                    <v-text-field class= "mayusculas" label="Orden para cancelar" outlined dense v-model="num_orden_cancelar" type="text"></v-text-field>
                  </v-col>
                  <!-- Selector de Año -->
                  <v-col cols="12" md="4">
                    <v-text-field v-model="anio" label="Año" outlined dense maxlength="4" counter type="number" :rules="[v => (v && v.length === 4) || 'El año debe tener 4 dígitos']"></v-text-field>
                  </v-col>
                </v-row>
                <v-row>
                  <!-- Buscar orden -->
                  <v-col cols="12" md="8">
                    <v-btn color="success" dark @click="buscarOrden()">
                      BUSCAR ORDEN
                    </v-btn>
                  </v-col>
                </v-row>
              </v-container>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Diálogo de confirmación de cancelación -->
    <v-dialog v-model="dialogCancelar" persistent max-width="600px">
      <v-card v-if="ordenParaCancelar">
        <v-card-title class="headline pink darken-4 white--text">
          ¿Desea cancelar esta orden?
        </v-card-title>
        <v-card-text class="pt-4">
          <p><b>Orden:</b> {{ ordenParaCancelar.num_orden }}</p>
          <p><b>RFC:</b> {{ ordenParaCancelar.rfc }}</p>
          <p><b>Contribuyente:</b> {{ ordenParaCancelar.nombre }}</p>
          <v-textarea class= "mayusculas"
            v-model="motivoCancelacion"
            label="Motivo de la cancelación"
            outlined
            rows="3"
            required
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="dialogCancelar = false">Cancelar</v-btn>
          <v-btn color="red darken-1" dark @click="procederCancelacion()">PROCEDER CON LA CANCELACIÓN</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
  import Swal from 'sweetalert2';
  import axios from 'axios';
  import api from '@/services/apiUrls.js';

  var url_canceladas = api.canceladas;

export default {
    name: "Folios",
    data() {
        return {
            num_orden_cancelar: null,
            dialogCancelar: false,
            ordenParaCancelar: null,
            motivoCancelacion: '',
            anio: new Date().getFullYear().toString(),
        };
    },
    methods: {
        
        async buscarOrden() {
          if (!this.num_orden_cancelar) {
                Swal.fire('Error', 'Por favor, ingrese un número de orden.', 'error');
                return;
            }
            try {
                const response = await axios.post(url_canceladas, {
                    opcion: 1,
                    num_orden: this.num_orden_cancelar,
                    anio: this.anio
                });

                if (response.data && response.data.success !== false) {
                    this.ordenParaCancelar = response.data.data;
                    this.motivoCancelacion = ''; // Limpiar el motivo anterior
                    this.dialogCancelar = true;
                } else {
                    Swal.fire('Error', response.data.message || 'No se encontró la orden.', 'error');
                }
            } catch (error) {
                console.error('Error al buscar la orden:', error);
                Swal.fire('Error', 'Ocurrió un error al buscar la orden.', 'error');
            }
        },
        async procederCancelacion() {
            if (!this.motivoCancelacion.trim()) {
                Swal.fire('Campo requerido', 'Por favor, ingrese el motivo de la cancelación.', 'warning');
                return;
            }
            try {
                await axios.post(url_canceladas, {
                    opcion: 2,
                    id_prospecto: this.ordenParaCancelar.id_prospecto,
                    id_orden: this.ordenParaCancelar.id_orden,
                    num_oficio: this.ordenParaCancelar.num_oficio,
                    num_orden: this.ordenParaCancelar.num_orden,
                    observaciones: this.motivoCancelacion.toUpperCase()
                });
                Swal.fire('¡Cancelada!', 'La orden ha sido cancelada.', 'success');
                this.dialogCancelar = false;
                this.num_orden_cancelar = null;
            } catch (error) {
                Swal.fire('Error', 'No se pudo cancelar la orden.', 'error');
            }
        }
    },
};
</script>

<style scoped>
.center {
  text-align: center;
}
.mt-4 {
  margin-top: 16px;
}
.mayusculas input{
  text-transform: uppercase
}
</style>
