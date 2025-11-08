<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo & Title -->
                    <div class="flex items-center space-x-4">
                        <div class="h-10 w-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Employee Attendance</h1>
                            <p class="text-xs text-gray-500">Систем за евиденцију присуства</p>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center space-x-4">
                        <!-- User Info -->
                        <div class="hidden md:block text-right">
                            <p class="text-sm font-medium text-gray-900">{{ user.FirstName }} {{ user.LastName }}</p>
                            <p class="text-xs text-gray-500">{{ getRoleLabel(user.Role) }}</p>
                        </div>

                        <!-- User Avatar & Dropdown -->
                        <div class="relative">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center space-x-2 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg p-1"
                            >
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-semibold">
                                    {{ userInitials }}
                                </div>
                                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-if="showUserMenu"
                                    class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1"
                                    @click="showUserMenu = false"
                                >
                                    <!-- User Info (Mobile) -->
                                    <div class="md:hidden px-4 py-3 border-b border-gray-200">
                                        <p class="text-sm font-medium text-gray-900">{{ user.FirstName }} {{ user.LastName }}</p>
                                        <p class="text-xs text-gray-500">{{ user.Email }}</p>
                                        <span :class="['inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full', getRoleBadgeClass(user.Role)]">
                                            {{ getRoleLabel(user.Role) }}
                                        </span>
                                    </div>

                                    <!-- Menu Items -->
                                    <AppLink
                                        href="/change-password"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
                                        </svg>
                                        Промени лозинку
                                    </AppLink>

                                    <div class="border-t border-gray-200 my-1"></div>

                                    <button
                                        @click="logout"
                                        class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                    >
                                        <svg class="h-5 w-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        Одјави се
                                    </button>
                                </div>
                            </transition>
                        </div>

                        <!-- Mobile Menu Toggle -->
                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Sidebar Navigation -->
                <aside class="w-full md:w-64 flex-shrink-0">
                    <nav class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-1">
                        <!-- User Dashboard (for everyone) -->
                        <AppLink
                            href="/dashboard"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                                isActive('/dashboard')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Почетна
                        </AppLink>

                        <!-- Admin Dashboard (for Admin/Kadrovik/SuperAdmin/Rukovodilac) -->
                        <AppLink
                            v-if="user.isAdmin || user.Role === 'Kadrovik' || user.Role === 'Rukovodilac'"
                            href="/admin/dashboard"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                                isActive('/admin/dashboard')
                                    ? 'bg-purple-50 text-purple-700'
                                    : 'text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Admin Dashboard
                        </AppLink>

                        <!-- Users (Admin) -->
                        <AppLink
                            v-if="user.isAdmin"
                            href="/users"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                                isActive('/users')
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Корисници
                        </AppLink>

                        <!-- Reports (Admin/Kadrovik/Rukovodilac) -->
                        <AppLink
                            v-if="user.isAdmin || user.Role === 'Kadrovik' || user.Role === 'Rukovodilac'"
                            href="/reports"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                                isActive('/reports')
                                    ? 'bg-orange-50 text-orange-700'
                                    : 'text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Извештаји
                        </AppLink>

                        <!-- Settings (SuperAdmin only) -->
                        <AppLink
                            v-if="user.Role === 'SuperAdmin'"
                            href="/settings"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                                isActive('/settings')
                                    ? 'bg-green-50 text-green-700'
                                    : 'text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Подешавања
                        </AppLink>
                    </nav>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 min-w-0">
                    <slot />
                </main>
            </div>
        </div>

        <!-- Overtime Prompt Modal -->
        <OvertimePromptModal
            :show="showOvertimePrompt"
            :message="overtimeMessage"
            :formatted-time="formatTimeRemaining()"
            @confirm="confirmPresence"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { getRoleLabel, getRoleBadgeClass } from '@/utils/roleMapping';
import { useOvertimeCheck } from '@/composables/useOvertimeCheck';
import OvertimePromptModal from '@/Components/OvertimePromptModal.vue';
import AppLink from '@/Components/AppLink.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    laravelVersion: {
        type: String,
        default: '11.x',
    },
});

const toast = useToast();
const showUserMenu = ref(false);
const showMobileMenu = ref(false);
let heartbeatInterval = null;

// Overtime presence check
const {
    showOvertimePrompt,
    overtimeMessage,
    formatTimeRemaining,
    confirmPresence
} = useOvertimeCheck();

const userInitials = computed(() => {
    const first = props.user.FirstName?.[0] || '';
    const last = props.user.LastName?.[0] || '';
    return (first + last).toUpperCase();
});

const isActive = (path) => {
    return window.location.pathname === path;
};

const logout = () => {
    router.post('/logout', {}, {
        onSuccess: () => {
            toast.success('Успешно сте се одјавили.');
        },
        onError: () => {
            toast.error('Дошло је до грешке приликом одјављивања.');
        },
    });
};

// Heartbeat check for auto-logout detection
const checkAuthStatus = async () => {
    try {
        const response = await window.axios.get('/attendance/status');
        // Axios throws on non-2xx status, so if we get here, user is authenticated
    } catch (error) {
        // If 401 or 419, user is logged out (session expired)
        if (error.response && (error.response.status === 401 || error.response.status === 419)) {
            console.log('Session expired detected via heartbeat, redirecting to login...');
            clearInterval(heartbeatInterval);
            window.location.href = '/employee-attendance/login';
        } else {
            console.error('Heartbeat check error:', error);
        }
    }
};

// Start heartbeat on mount
onMounted(() => {
    // Check every 30 seconds
    heartbeatInterval = setInterval(checkAuthStatus, 30000);
    console.log('Auto-logout heartbeat started (30s interval)');
});

// Stop heartbeat on unmount
onUnmounted(() => {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        console.log('Auto-logout heartbeat stopped');
    }
});

// Close dropdowns when clicking outside
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showUserMenu.value = false;
    }
};

if (typeof window !== 'undefined') {
    window.addEventListener('click', handleClickOutside);
}
</script>
