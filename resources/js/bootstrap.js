import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configure base URL for subfolder deployment
window.axios.defaults.baseURL = '/employee-attendance';
