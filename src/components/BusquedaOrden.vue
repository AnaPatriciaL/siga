<template>
  <v-card max-width="900px" class="mx-auto mt-5">
    <v-card-title class="headline grey darken-3 white--text py-1 my-3">
      Captura de Cobranza y Avances
    </v-card-title>
    <v-card-text class="pt-5">
      <v-form @submit.prevent="$emit('buscar', orden)">
        <v-row>
          <v-spacer></v-spacer>
          <v-col cols="12" md="8" class="text-center">
            <v-text-field
              v-model="orden"
              label="Orden"
              outlined
              class="my-0 py-0 mayusculas text-h4"
              large
              placeholder="Ingresa la orden"
              clearable
              autofocus
               append-icon="mdi-magnify"
              @click:append="$emit('buscar', orden)"
              ref="form"
              ></v-text-field>
              <!-- :append-icon="orden ? 'mdi-close-circle' : ''" -->
          </v-col>
          
          <!-- <v-col cols="12" md="3" class="text-center">
            <v-btn class="pink darken-4" x-large dark type="submit">
              <v-icon class="mr-2">mdi-database-search</v-icon>
              Buscar
            </v-btn>
          </v-col> -->
          <v-spacer></v-spacer>
        </v-row>
      </v-form>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref } from 'vue';

const orden = ref('');

// Definir los eventos que el componente puede emitir
defineEmits(['buscar']);
</script>
