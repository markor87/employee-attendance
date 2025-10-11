<template>
    <teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="closeModal"
        >
            <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 rounded-t-xl flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h2 class="text-xl font-bold text-white">
                            Пријављивање корисника
                        </h2>
                    </div>
                    <button
                        @click="closeModal"
                        class="text-white hover:text-gray-200 transition-colors"
                        type="button"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
                    <!-- User Info -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">Пријављујете:</span>
                            <span class="block mt-1 text-lg font-semibold text-blue-900">
                                {{ user.FirstName }} {{ user.LastName }}
                            </span>
                            <span class="text-sm text-gray-600">{{ user.Email }}</span>
                        </p>
                    </div>

                    <!-- Reason Select -->
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Разлог пријаве <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="reason"
                            v-model="form.reason"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors"
                            :class="{ 'border-red-500': errors.reason }"
                        >
                            <option value="">Изаберите разлог...</option>
                            <option
                                v-for="reason in checkInReasons"
                                :key="reason.ReasonID"
                                :value="reason.ReasonName"
                            >
                                {{ reason.ReasonName }}
                            </option>
                        </select>
                        <p v-if="errors.reason" class="text-xs text-red-600 mt-1">{{ errors.reason }}</p>
                    </div>

                    <!-- Notes Textarea -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Напомена <span class="text-gray-400">(опционо)</span>
                        </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            maxlength="500"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none transition-colors"
                            placeholder="Унесите напомену..."
                        ></textarea>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-xs text-gray-500">
                                {{ form.notes.length }}/500 карактера
                            </p>
                        </div>
                    </div>

                    <!-- Warning Notice -->
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start">
                        <svg class="h-5 w-5 text-amber-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-xs text-amber-800">
                            Ова акција ће се евидентирати са вашим корисничким именом.
                        </p>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            @click="closeModal"
                            :disabled="loading"
                            class="flex-1 px-6 py-2.5 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Откажи
                        </button>
                        <button
                            type="submit"
                            :disabled="loading || !form.reason"
                            class="flex-1 px-6 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <svg v-if="loading" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ loading ? 'Пријављивање...' : 'Пријави корисника' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        required: true,
    },
    checkInReasons: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
    reason: '',
    notes: '',
});

const errors = ref({
    reason: '',
});

const loading = ref(false);

// Reset form when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.value = {
            reason: '',
            notes: '',
        };
        errors.value = {
            reason: '',
        };
        loading.value = false;
    }
});

const closeModal = () => {
    if (!loading.value) {
        emit('close');
    }
};

const handleSubmit = async () => {
    // Validate
    errors.value = { reason: '' };

    if (!form.value.reason) {
        errors.value.reason = 'Разлог је обавезан';
        return;
    }

    loading.value = true;

    try {
        await emit('submit', {
            user_id: props.user.UserID,
            reason: form.value.reason,
            notes: form.value.notes || null,
        });
    } catch (error) {
        console.error('Error submitting form:', error);
    } finally {
        loading.value = false;
    }
};

// Close modal on ESC key
const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.show && !loading.value) {
        closeModal();
    }
};

if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleKeydown);
}
</script>
