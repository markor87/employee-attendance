<template>
    <teleport to="body">
        <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
            <div class="bg-gray-900 rounded-xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden border border-gray-700">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <h2 class="text-xl font-bold text-white">
                            🏠 Удаљени корисници ({{ remoteUsersCount }})
                        </h2>
                    </div>
                    <button
                        @click="$emit('close')"
                        class="text-gray-400 hover:text-white transition-colors"
                        type="button"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-12">
                        <svg class="animate-spin h-12 w-12 mx-auto text-blue-400 mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-400 text-lg">Учитавање...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="remoteUsers.length === 0" class="text-center py-12">
                        <svg class="h-16 w-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-400 text-lg">Нема тренутно удаљених корисника</p>
                        <p class="text-gray-500 text-sm mt-2">Сви пријављени корисници су у канцеларији</p>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-800 border-b border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Име и презиме</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Време пријаве</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">IP адреса</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="user in remoteUsers" :key="user.UserID" class="hover:bg-gray-800/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-white font-medium">
                                        {{ user.FirstName }} {{ user.LastName }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-300 font-mono">
                                        {{ user.Email }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-300">
                                        {{ formatDateTime(user.active_time_log?.VremePrijave) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-3 py-1 bg-blue-900/50 text-blue-300 text-xs font-mono rounded border border-blue-700">
                                            {{ user.active_time_log?.IpAdresaPrijave || '-' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-800 border-t border-gray-700 flex justify-between items-center">
                    <div class="text-xs text-gray-400 font-mono">
                        DEBUG MODE • Ctrl+Alt+R
                    </div>
                    <button
                        @click="$emit('close')"
                        class="px-6 py-2.5 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition-colors"
                    >
                        Затвори
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { isRemoteIp } from '@/Utils/locationHelper';

const props = defineProps({
    // No longer need users prop - will fetch from API
});

defineEmits(['close']);

const loading = ref(true);
const users = ref([]);

// Fetch remote users from API
onMounted(async () => {
    try {
        const response = await fetch('/api/reports/remote-users');
        const data = await response.json();

        if (data.success) {
            users.value = data.data || [];
        }
    } catch (error) {
        console.error('Failed to load remote users:', error);
    } finally {
        loading.value = false;
    }
});

// Filter users who are currently checked in and remote
const remoteUsers = computed(() => {
    return users.value.filter(user => {
        // User must be checked in
        if (user.current_status !== 'Prijavljen') return false;

        // User must have active log
        if (!user.active_time_log) return false;

        // IP must be remote
        const ip = user.active_time_log.IpAdresaPrijave;
        return isRemoteIp(ip);
    }).sort((a, b) => {
        // Sort by check-in time (newest first)
        const timeA = new Date(a.active_time_log.VremePrijave);
        const timeB = new Date(b.active_time_log.VremePrijave);
        return timeB - timeA;
    });
});

const remoteUsersCount = computed(() => remoteUsers.value.length);

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleString('sr-RS', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
