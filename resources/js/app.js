import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import AppLink from '@/Components/AppLink.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Employee Attendance';

// Subfolder base path configuration from environment variable
// Defaults to '/' if not set (local development)
const basePath = import.meta.env.VITE_BASE_PATH || '/';

// Inertia router interceptor for subfolder deployment
// Automatically prepends base path to all Inertia requests (only if not root path)
if (basePath !== '/') {
    router.on('start', ({ detail: { visit } }) => {
        const path = visit.url.pathname;
        // Only prepend if path doesn't already start with basePath
        if (path.startsWith('/') && !path.startsWith(basePath)) {
            visit.url.pathname = basePath + path;
        }
    });
}

// Update CSRF meta tag after each Inertia navigation
// This ensures the CSRF token stays fresh and prevents 419 errors
router.on('success', (event) => {
    const page = event.detail.page;
    if (page.props && page.props.csrf_token) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.content = page.props.csrf_token;
        }
    }
});

// Inertia error interceptor for auto-logout redirect
router.on('error', (event) => {
    // Check if error is 401 (Unauthenticated) or 419 (CSRF token mismatch/session expired)
    if (event.detail?.response?.status === 401 || event.detail?.response?.status === 419) {
        // Redirect to login page
        const loginPath = basePath === '/' ? '/login' : basePath + '/login';
        window.location.href = loginPath;
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Toast, {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnFocusLoss: true,
                pauseOnHover: true,
                draggable: true,
                draggablePercent: 0.6,
                showCloseButtonOnHover: false,
                hideProgressBar: false,
                closeButton: 'button',
                icon: true,
                rtl: false,
            })
            .component('AppLink', AppLink)
            .mount(el);
    },
    progress: {
        color: '#4F46E5',
    },
});
