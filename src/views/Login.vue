<template>
  <v-container class="d-flex align-center justify-center" style="height: 100vh">
    <v-card class="elevation-10" width="380">
      <v-card-title
        class="d-flex justify-center align-center ma-0 pink darken-3"
      >
        <h1 class="white--text mb-0">SIGA</h1>
      </v-card-title>
      <v-card-text>
        <v-col cols="l2" sm="12" class="align-center">
          <div class="my-5 pl-3">
            <v-img
              max-height="120"
              max-width="280"
              justify="center"
              src="@/assets/img/logo_sates_digital.png"
            ></v-img>
          </div>
        </v-col>
        <v-form ref="form">
          <v-text-field
            label="Usuario"
            v-model="usuario"
            :rules="[rules.required]"
            prepend-icon="mdi-account"
            required
          ></v-text-field>
          <v-text-field
            label="Contraseña"
            class="mb-5"
            v-model="contrasena"
            :type="showPassword ? 'text' : 'password'"
            :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
            @click:append="togglePassword"
            :rules="[rules.required]"
            prepend-icon="mdi-lock"
            required
            @keydown.enter="login"
          ></v-text-field>
          <div class="d-flex justify-center mt-5">
            <v-btn dark color="pink darken-3" @click="login">Ingresar</v-btn>
          </div>
          <div class="d-flex justify-center my-5">
            <span>SATES © 2025</span>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script>
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css"; // SweetAlert2 styles
import axios from "axios";
import api from '@/services/apiUrls.js';

export default {
  data() {
    return {
      usuario: "",
      contrasena: "",
      showPassword: false,
      rules: {
        required: (value) => !!value || "Este campo es obligatorio",
      },
    };
  },
  methods: {
    togglePassword() {
      this.showPassword = !this.showPassword;
    },
    async login() {
      try {
        const response = await axios.post(api.login,
          {
            usuario: this.usuario,
            contrasena: this.contrasena,
          }
        );

        if (response.data.success) {
          localStorage.setItem("id", response.data.id);
          localStorage.setItem("token", response.data.token);
          localStorage.setItem("nombre", response.data.nombre);
          localStorage.setItem("nivel", response.data.nivel);
          this.$user.id = localStorage.getItem("id");
          this.$user.nombre = localStorage.getItem("nombre");
          this.$user.nivel = localStorage.getItem("nivel");
          this.$root.$emit("authenticated");
          this.$router.push("/");
        } else {
          Swal.fire("Error", response.data.message, "error");
        }
      } catch (error) {
        console.error(error);
        Swal.fire("Error", "Error al iniciar sesión", "error");
      }
    },
  },
};
</script>
