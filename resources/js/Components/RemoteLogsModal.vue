<template>
    <teleport to="body">
        <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
            <div class="bg-gray-900 rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden border border-gray-700">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h2 class="text-xl font-bold text-white">
                            🏠 Удаљени логови - {{ userName }} ({{ remoteLogsCount }})
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
                    <div v-if="remoteLogs.length === 0" class="text-center py-12">
                        <svg class="h-16 w-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-400 text-lg">Нема удаљених логова</p>
                        <p class="text-gray-500 text-sm mt-2">Корисник нема евиденције рада са удаљене локације</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-800 border-b border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Датум</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Време пријаве</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Време одјаве</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">IP пријаве</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">IP одјаве</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Локације</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="log in remoteLogs" :key="log.LogID" class="hover:bg-gray-800/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-white font-medium">
                                        {{ formatDate(log.RadniDatum) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-300">
                                        {{ formatTime(log.VremePrijave) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-300">
                                        {{ log.VremeOdjave ? formatTime(log.VremeOdjave) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-3 py-1 bg-blue-900/50 text-blue-300 text-xs font-mono rounded border border-blue-700">
                                            {{ log.IpAdresaPrijave || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span v-if="log.IpAdresaOdjave" class="inline-flex items-center px-3 py-1 bg-blue-900/50 text-blue-300 text-xs font-mono rounded border border-blue-700">
                                            {{ log.IpAdresaOdjave }}
                                        </span>
                                        <span v-else class="text-gray-500 text-xs">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex flex-col space-y-1">
                                            <!-- Check-in Location -->
                                            <span :class="[
                                                'inline-flex items-center px-2 py-1 rounded text-xs font-medium border w-fit',
                                                getLocationFromIp(log.IpAdresaPrijave).bgColor,
                                                getLocationFromIp(log.IpAdresaPrijave).textColor,
                                                getLocationFromIp(log.IpAdresaPrijave).borderColor
                                            ]">
                                                <span class="mr-1">{{ getLocationFromIp(log.IpAdresaPrijave).icon }}</span>
                                                {{ getLocationFromIp(log.IpAdresaPrijave).label }}
                                            </span>
                                            <!-- Check-out Location (if different) -->
                                            <span v-if="log.IpAdresaOdjave && log.IpAdresaOdjave !== log.IpAdresaPrijave" :class="[
                                                'inline-flex items-center px-2 py-1 rounded text-xs font-medium border w-fit',
                                                getLocationFromIp(log.IpAdresaOdjave).bgColor,
                                                getLocationFromIp(log.IpAdresaOdjave).textColor,
                                                getLocationFromIp(log.IpAdresaOdjave).borderColor
                                            ]">
                                                <span class="mr-1">{{ getLocationFromIp(log.IpAdresaOdjave).icon }}</span>
                                                {{ getLocationFromIp(log.IpAdresaOdjave).label }}
                                            </span>
                                        </div>
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
import { computed } from 'vue';
import { isRemoteIp, getLocationFromIp } from '@/Utils/locationHelper';

const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    userName: {
        type: String,
        required: true,
    },
});

defineEmits(['close']);

// Filter logs where user was remote (check-in or check-out from remote location)
const remoteLogs = computed(() => {
    const allLogs = props.logs.data || [];

    return allLogs.filter(log => {
        const checkInRemote = isRemoteIp(log.IpAdresaPrijave);
        const checkOutRemote = log.IpAdresaOdjave ? isRemoteIp(log.IpAdresaOdjave) : false;

        // Include log if either check-in or check-out was from remote location
        return checkInRemote || checkOutRemote;
    });
});

const remoteLogsCount = computed(() => remoteLogs.value.length);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('sr-RS', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatTime = (dateTimeString) => {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleTimeString('sr-RS', {
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
