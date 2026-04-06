<template>
  <v-text-field
    :label="label"
    :value="formattedDisplayValue"
    @input="onInput"
    @blur="onBlur"
    @keypress="onKeyPress"
    @keydown.enter="onEnter"
    outlined
    hide-details
    suffix="$"
    dense
    reverse
    inputmode="decimal"
    type="text" />
</template>

<script>
export default {
  name: "CampoMillares",
  props: {
    label: { type: String, default: "" },
    value: { type: [Number, String], default: null }
  },
  data() {
    return {
      internalValue: this.value ? String(this.value) : ""
    };
  },
  computed: {
    formattedDisplayValue() {
      if (!this.internalValue) return "";

      if (this.internalValue.endsWith('.')) {
        const valueWithoutTrailingDot = this.internalValue.slice(0, -1);
        if (valueWithoutTrailingDot === '') return '.';
        return this.formatNumber(parseFloat(valueWithoutTrailingDot)) + '.';
      }

      if (this.internalValue === '.') {
        return '.';
      }

      const numericValue = parseFloat(this.internalValue);
      if (!isNaN(numericValue)) {
        return this.formatNumber(numericValue);
      }
      return "";
    }
  },
  watch: {
    value(newVal) {
      this.internalValue = newVal !== null ? String(newVal) : "";
    }
  },
  methods: {
    onKeyPress(event) {
      const char = event.key;
      const currentValue = this.internalValue;

      // Permitir solo números, un punto, y teclas de control
      if (!/[0-9.]/.test(char)) {
        event.preventDefault();
        return;
      }

      // Prevenir múltiples puntos
      if (char === '.' && currentValue.includes('.')) {
        event.preventDefault();
        return;
      }

      // Limitar decimales a 2 dígitos
      if (currentValue.includes('.')) {
        const parts = currentValue.split('.');
        if (parts[1] && parts[1].length >= 2 && event.target.selectionStart > currentValue.indexOf('.')) {
          event.preventDefault();
          return;
        }
      }

      // Limitar parte entera a 16 dígitos
      const parts = currentValue.split('.');
      if (!currentValue.includes('.') || event.target.selectionStart <= currentValue.indexOf('.')) {
        if (parts[0].length >= 16 && char !== '.') {
          event.preventDefault();
          return;
        }
      }
    },

    onInput(eventValue) {
      // Eliminar el formato de miles (comas) que pueda tener el display
      let cleanValue = eventValue.replace(/,/g, '');
      
      // Limpiar: solo números y punto
      cleanValue = cleanValue.replace(/[^0-9.]/g, "");

      // Limitar a un solo punto
      const parts = cleanValue.split('.');
      if (parts.length > 2) {
        cleanValue = parts[0] + '.' + parts.slice(1).join('');
      }

      // Limitar a 2 decimales
      if (parts.length === 2 && parts[1].length > 2) {
        cleanValue = parts[0] + '.' + parts[1].substring(0, 2);
      }
      
      // Limitar a 16 dígitos enteros
      const integerPart = parts[0];
      if (integerPart.length > 16) {
        cleanValue = integerPart.substring(0, 16) + (parts[1] ? '.' + parts[1] : '');
      }

      this.internalValue = cleanValue;
    },

    onBlur() {
      if (!this.internalValue || this.internalValue === '.') {
        this.$emit("input", null);
        this.internalValue = "";
        return;
      }

      let cleanValue = this.internalValue.replace(/[^0-9.]/g, "");
      let numericValue = cleanValue ? parseFloat(cleanValue) : null;

      this.$emit("input", numericValue);
      this.internalValue = numericValue !== null ? String(numericValue) : "";
    },

    onEnter(event) {
      event.preventDefault();
      
      // Ejecutar el blur para guardar el valor
      this.onBlur();
      
       const focusableElements = document.querySelectorAll(
        'input:not([tabindex="-1"]):not([disabled]), button:not([tabindex="-1"]):not([disabled]), select:not([tabindex="-1"]):not([disabled]), textarea:not([tabindex="-1"]):not([disabled]), a[href]:not([tabindex="-1"]), [tabindex]:not([tabindex="-1"])'
      );

      // Mover el foco al siguiente elemento enfocable
      // const focusableElements = document.querySelectorAll(
      //   'input, button, select, textarea, a[href], [tabindex]:not([tabindex="-1"])'
      // );
      const currentIndex = Array.from(focusableElements).indexOf(event.target);
      const nextElement = focusableElements[currentIndex + 1];
      
      if (nextElement) {
        nextElement.focus();
      }
    },

    formatNumber(value) {
      if (value === "" || value === ".") return String(value);

      let num = parseFloat(value);
      if (isNaN(num)) return "";

      return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(num);
    }
  }
};
</script>

<style scoped>
/* Estilos específicos si es necesario */
</style>