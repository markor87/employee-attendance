<template>
    <teleport to="body">
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
            <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 rounded-t-xl flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <h2 class="text-2xl font-bold text-white">
                            Преглед лога
                        </h2>
                    </div>
                    <button
                        @click="$emit('close')"
                        class="text-white hover:text-gray-200 transition-colors"
                        type="button"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Log ID - Featured -->
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-300 rounded-lg p-4 mb-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 uppercase tracking-wider">Log ID:</span>
                            <span class="text-2xl font-bold text-gray-900 font-mono">#{{ log.LogID }}</span>
                        </div>
                    </div>

                    <!-- Grid of Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Датум -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Датум
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ formatDate(log.RadniDatum) }}</p>
                        </div>

                        <!-- Трајање -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Трајање
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ calculateDuration(log.VremePrijave, log.VremeOdjave) }}</p>
                        </div>

                        <!-- Време пријаве -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Време пријаве
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ formatTime(log.VremePrijave) }}</p>
                        </div>

                        <!-- Време одјаве -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Време одјаве
                            </label>
                            <p v-if="log.VremeOdjave" class="text-sm font-medium text-gray-900">{{ formatTime(log.VremeOdjave) }}</p>
                            <span v-else class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                <svg class="h-3 w-3 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Активна пријава
                            </span>
                        </div>

                        <!-- Разлог пријаве -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Разлог пријаве
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ log.RazlogPrijave || '-' }}</p>
                        </div>

                        <!-- Разлог одјаве -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Разлог одјаве
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ log.RazlogOdjave || '-' }}</p>
                        </div>

                        <!-- Креирао пријаву -->
                        <div v-if="log.PerformedByPrijava" class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Креирао пријаву
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ getPerformedByName(log.PerformedByPrijava) }}</p>
                        </div>

                        <!-- Креирао одјаву -->
                        <div v-if="log.PerformedByOdjava" class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Креирао одјаву
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ getPerformedByName(log.PerformedByOdjava) }}</p>
                        </div>
                    </div>

                    <!-- Notes Section - Full Width -->
                    <div v-if="log.Napomena" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                        <label class="block text-xs font-medium text-yellow-700 uppercase tracking-wider mb-2 flex items-center">
                            <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            Напомена
                        </label>
                        <p class="text-sm text-yellow-900 whitespace-pre-wrap">{{ log.Napomena }}</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex justify-end">
                    <button
                        @click="$emit('close')"
                        class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition-colors"
                    >
                        Затвори
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
const props = defineProps({
    log: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['close']);

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

const calculateDuration = (startTime, endTime) => {
    if (!startTime || !endTime) return '-';

    const start = new Date(startTime);
    const end = new Date(endTime);
    const diffMs = end - start;
    const diffMins = Math.floor(diffMs / 60000);

    const hours = Math.floor(diffMins / 60);
    const minutes = diffMins % 60;

    return `${hours}h ${minutes}m`;
};

const getPerformedByName = (userId) => {
    if (!userId || !props.users || props.users.length === 0) return 'Систем';

    const user = props.users.find(u => u.UserID === userId);
    if (user) {
        return `${user.FirstName} ${user.LastName}`;
    }
    return `User #${userId}`;
};
</script>
