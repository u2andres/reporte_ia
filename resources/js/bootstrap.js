import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// jQuery global: queda en window.$ / window.jQuery para que jQuery UI (y
// cualquier script inline en las vistas) lo encuentren. jQuery UI se importa
// en app.js DESPUÉS de este módulo, cuando window.jQuery ya está definido.
import $ from 'jquery';
window.$ = window.jQuery = $;
