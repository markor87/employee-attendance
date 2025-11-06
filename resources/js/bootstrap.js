import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configure base URL for subfolder deployment
// Use environment variable (VITE_BASE_PATH from .env)
// Defaults to '/' if not set
const basePath = import.meta.env.VITE_BASE_PATH || '/';
window.axios.defaults.baseURL = basePath === '/' ? '' : basePath;
