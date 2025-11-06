import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configure CSRF token for all axios requests
// Read from meta tag and set as default header
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Add axios request interceptor to ensure CSRF token is always fresh
// This handles cases where the token might not be available at initial load
window.axios.interceptors.request.use(function (config) {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.content;
    }
    return config;
}, function (error) {
    return Promise.reject(error);
});

// Configure base URL for subfolder deployment
// Use environment variable (VITE_BASE_PATH from .env)
// Defaults to '/' if not set
const basePath = import.meta.env.VITE_BASE_PATH || '/';
window.axios.defaults.baseURL = basePath === '/' ? '' : basePath;
