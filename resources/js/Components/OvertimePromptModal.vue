<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[9999] overflow-y-auto">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border-4 border-amber-500 animate-pulse-border">
                        <!-- Icon -->
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 mb-4 animate-bounce">
                            <svg class="h-10 w-10 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <!-- Title -->
                        <h3 class="text-xl font-bold text-gray-900 text-center mb-3">
                            {{ message }}
                        </h3>

                        <!-- Description -->
                        <p class="text-sm text-gray-700 text-center mb-4">
                            Прошло је редовно радно време. Молимо Вас да потврдите да ли сте и даље на послу.
                        </p>

                        <!-- Countdown -->
                        <div class="bg-gradient-to-r from-amber-50 to-red-50 border-2 border-amber-300 rounded-lg p-4 mb-6">
                            <p class="text-center mb-2">
                                <span class="text-xs text-amber-800 uppercase font-semibold tracking-wide">Преостало време</span>
                            </p>
                            <p class="text-center">
                                <span class="font-mono font-bold text-4xl text-amber-900">{{ formattedTime }}</span>
                            </p>
                            <p class="text-xs text-red-700 text-center mt-3 font-medium">
                                ⚠️ Ако не потврдите, бићете аутоматски одјављени!
                            </p>
                        </div>

                        <!-- Action Button -->
                        <button
                            @click="$emit('confirm')"
                            class="w-full bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold py-3.5 px-6 rounded-lg transition-all transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl text-lg"
                        >
                            ✓ Да, и даље сам на послу
                        </button>

                        <!-- Warning -->
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-xs text-gray-600 text-center">
                                <svg class="inline w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Ова провера се обавља у складу са политиком евиденције радног времена
                            </p>
                        </div>
                    </div>
                </div>
            </div>
    </Teleport>
</template>

<script setup>
defineProps({
    show: Boolean,
    message: String,
    formattedTime: String
});

defineEmits(['confirm']);
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

@keyframes pulse-border {
    0%, 100% {
        border-color: #F59E0B;
    }
    50% {
        border-color: #DC2626;
    }
}

.animate-pulse-border {
    animation: pulse-border 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
