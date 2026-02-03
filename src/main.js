/* eslint-disable */
import Vue from "vue";
import App from "./App.vue";
import router from "./router/index.js";
import Vuetify from "vuetify";
import VueTheMask from "vue-the-mask";
import VueExcelXlsx from "vue-excel-xlsx";

// import CompoMillares from "@/components/CampoMillares.vue"; // 👈 importa el componente
import "vuetify/dist/vuetify.min.css"; // Importar los estilos de Vuetify
import axios from "axios"; // Importar axios
import Swal from "sweetalert2";
// import { formatCurrency } from '@/utils/formatters';
// import api from '@/services/api';
import api from './services/api';
import VueSweetalert2 from 'vue-sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

Vue.use(VueSweetalert2);


// Configurar axios globalmente
Vue.prototype.$axios = axios; // Esto hará que axios esté disponible en todas las vistas

// Hacer que SweetAlert2 esté disponible globalmente en Vue
Vue.prototype.$Swal = Swal; // Esto hace que $Swal esté accesible en todos los componentes

Vue.config.productionTip = false;

// Usar Vuetify
Vue.use(Vuetify);
Vue.use(VueTheMask);
Vue.use(VueExcelXlsx);


// Definir una variable global `$user` en Vue prototype
Vue.prototype.$user = {
  id: null,
  nombre: "",
};
// Vue.component("CampoMillares", CompoMillares); // 👈 registro global

// Crear una nueva instancia de Vuetify
new Vue({
  router,
  vuetify: new Vuetify(),
  render: (h) => h(App),
  created() {
    this.$user.id = localStorage.getItem("siga_id");
    this.$user.nombre = localStorage.getItem("siga_nombre");
  },
}).$mount("#app");
