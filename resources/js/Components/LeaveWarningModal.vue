<template>
    <teleport to="body">
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$emit('cancel')">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4 rounded-t-xl">
                    <div class="flex items-center space-x-3">
                        <div class="h-12 w-12 bg-white/20 rounded-full flex items-center justify-center">
                            <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-white">
                            Упозорење
                        </h2>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-lg font-semibold text-gray-900 mb-2">
                            Тренутно имате активно службено одсуство
                        </p>
                        <p class="text-sm text-gray-600">
                            Евидентирано је службено одсуство за данашњи дан. Ако наставите са пријавом,
                            ваш статус ће бити промењен на "Пријављен".
                        </p>
                    </div>

                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start space-x-3">
                            <svg class="h-5 w-5 text-orange-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm text-orange-800">
                                Да ли сте сигурни да желите да се пријавите на посао?
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <button
                            type="button"
                            @click="$emit('cancel')"
                            class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Откажи
                        </button>
                        <button
                            type="button"
                            @click="$emit('confirm')"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-semibold rounded-lg shadow-md transition-all duration-200"
                        >
                            Настави са пријавом
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
defineEmits(['confirm', 'cancel']);
</script>
