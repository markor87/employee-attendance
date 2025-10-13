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
                        Да ли сте сигурни да желите да обришете корисника?
                    </p>

                    <!-- User Info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-lg">
                                {{ getUserInitials }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">
                                    {{ user.FirstName }} {{ user.LastName }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ user.Email }}
                                </p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-xs font-semibold',
                                            getRoleBadgeClass(user.Role)
                                        ]"
                                    >
                                        {{ getRoleLabel(user.Role) }}
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-xs font-semibold',
                                            user.Status === 'Prijavljen'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        {{ user.Status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-red-50 border-l-4 border-red-600 p-4 mb-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm text-red-700">
                                <strong>Упозорење:</strong> Ова акција је неповратна. Сви подаци везани за овог корисника ће бити обрисани.
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
                        Обриши корисника
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { computed } from 'vue';
import { getRoleLabel, getRoleBadgeClass } from '@/utils/roleMapping';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

defineEmits(['close', 'confirm']);

const getUserInitials = computed(() => {
    const first = props.user.FirstName?.[0] || '';
    const last = props.user.LastName?.[0] || '';
    return (first + last).toUpperCase();
});
</script>
