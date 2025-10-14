<template>
    <teleport to="body">
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="bg-red-600 px-6 py-4 rounded-t-xl">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <h2 class="text-xl font-bold text-white">
                            Потврда брисања
                        </h2>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p class="text-gray-700 mb-4">
                        Да ли сте сигурни да желите да обришете овај лог?
                    </p>

                    <!-- Log Info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-gray-700">Датум:</span>
                            <span class="text-gray-900">{{ formatDate(log.RadniDatum) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-gray-700">Време:</span>
                            <span class="text-gray-900">
                                {{ formatTime(log.VremePrijave) }} - {{ formatTime(log.VremeOdjave) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-gray-700">Трајање:</span>
                            <span class="text-gray-900 font-semibold">{{ calculateDuration(log.VremePrijave, log.VremeOdjave) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-gray-700">Разлог:</span>
                            <span class="text-gray-900">{{ log.RazlogPrijave }}</span>
                        </div>
                        <div v-if="log.Napomena" class="pt-2 border-t border-gray-200">
                            <span class="font-medium text-gray-700 text-sm">Напомена:</span>
                            <p class="text-sm text-gray-600 mt-1">{{ log.Napomena }}</p>
                        </div>
                    </div>

                    <div class="bg-red-50 border-l-4 border-red-600 p-4 mb-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm text-red-700">
                                <strong>Упозорење:</strong> Ова акција је неповратна. Лог ће бити трајно обрисан.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex space-x-3">
                    <button
                        @click="$emit('close')"
                        class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        Откажи
                    </button>
                    <button
                        @click="$emit('confirm')"
                        class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition-colors"
                    >
                        Обриши лог
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
});

defineEmits(['close', 'confirm']);

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
</script>
