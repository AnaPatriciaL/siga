<template>
  <v-dialog v-model="localDialog" max-width="600px" persistent>
    <v-card>
      <v-card-title class="red darken-3 white--text">
        {{ title }}
      </v-card-title>

      <v-card-text>
        <v-select v-model="form.antecedente_id" :items="antecedentes" item-text="descripcion" item-value="id" label="Motivo (Antecedente)" outlined/>
        <v-textarea class= "mayusculas" v-model="form.observaciones" label="Observaciones" outlined rows="3"/>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="cancelar">Cancelar</v-btn>
        <v-btn color="red darken-2" dark @click="confirmar" :disabled="!form.antecedente_id">Aceptar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
export default {
  name: 'dialogoNoAutorizado',
  props: {
    value: Boolean,
    antecedentes: {
      type: Array,
      required: true
    },
    title: {
      type: String,
      default: 'Confirmar acción'
    }
  },
  data() {
    return {
      localDialog: false,
      form: {
        antecedente_id: null,
        observaciones: ''
      }
    };
  },
  watch: {
    value(val) {
      this.localDialog = val;
    },
    localDialog(val) {
      this.$emit('input', val);
      if (!val) this.reset();
    }
  },
  methods: {
    confirmar() {
      this.$emit('confirmar', { ...this.form });
      this.localDialog = false;
    },
    cancelar() {
      this.localDialog = false;
    },
    reset() {
      this.form = {
        antecedente_id: null,
        observaciones: ''
      };
    }
  }
};
</script>