// services/api.js
import axios from 'axios';

const apiClient = axios.create({
  baseURL: 'http://10.10.120.228/siga/backend/',
  headers: {
    'Content-Type': 'application/json',
  }
});

export default {
  buscarOrden(orden) {
    return apiClient.get('busqueda_orden.php', { params: { orden } });
  },
  obtenerAvances(ordenId) {
    return apiClient.get('listado_avances_por_orden.php', { params: { orden_id: ordenId } });
  },
  obtenerCobranzas(ordenId) {
    return apiClient.get('listado_cobranzas_por_orden.php', { params: { orden_id: ordenId } });
  },
    obtenerTotalesCobranzas(ordenId) {
    return apiClient.get('total_cobranzas_por_orden.php', { params: { orden_id: ordenId } });
  },

  obtenerSeguimientos() {
    return apiClient.get('obtener_seguimientos.php');
  },
    obtenerImpuestos() {
    return apiClient.get('obtener_impuestos.php');
  },
     obtenerLugaresPago() {
    return apiClient.get('obtener_lugares_pago.php');
  },
   obtenerTiposPago() {
    return apiClient.get('obtener_tipos_pago.php');
  },
  guardarAvance(avance){
    return apiClient.post('guardar_avance.php',avance);
  },
  guardarCobranza(cobranza){
    return apiClient.post('guardar_cobranza.php', cobranza);
  }
};
