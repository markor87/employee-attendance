<template>
    <teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="closeModal"
        >
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-xl flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h2 class="text-xl font-bold text-white">
                            Евидентирање одсуства
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
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">Евидентирате одсуство за:</span>
                            <span class="block mt-1 text-lg font-semibold text-indigo-900">
                                {{ user.FirstName }} {{ user.LastName }}
                            </span>
                            <span class="text-sm text-gray-600">{{ user.Email }}</span>
                        </p>
                    </div>

                    <!-- Date Picker -->
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                            Датум <span class="text-red-500">*</span>
                        </label>
                        <VueDatePicker
                            v-model="form.date"
                            :enable-time-picker="false"
                            format="dd.MM.yyyy"
                            locale="sr"
                            auto-apply
                            :clearable="false"
                            :min-date="new Date()"
                            placeholder="Изаберите датум"
                            :class="{ 'border-red-500': errors.date }"
                        />
                        <p v-if="errors.date" class="text-xs text-red-600 mt-1">{{ errors.date }}</p>
                    </div>

                    <!-- Time Pickers Row -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Check-In Time -->
                        <div>
                            <label for="check-in-time" class="block text-sm font-medium text-gray-700 mb-2">
                                Време почетка <span class="text-red-500">*</span>
                            </label>
                            <VueDatePicker
                                v-model="form.checkInTime"
                                time-picker
                                :is-24="true"
                                auto-apply
                                :clearable="false"
                                placeholder="Изаберите време"
                                :class="{ 'border-red-500': errors.checkInTime }"
                            >
                                <template #input-icon>
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </template>
                            </VueDatePicker>
                            <p v-if="errors.checkInTime" class="text-xs text-red-600 mt-1">{{ errors.checkInTime }}</p>
                        </div>

                        <!-- Check-Out Time -->
                        <div>
                            <label for="check-out-time" class="block text-sm font-medium text-gray-700 mb-2">
                                Време краја <span class="text-red-500">*</span>
                            </label>
                            <VueDatePicker
                                v-model="form.checkOutTime"
                                time-picker
                                :is-24="true"
                                auto-apply
                                :clearable="false"
                                placeholder="Изаберите време"
                                :class="{ 'border-red-500': errors.checkOutTime }"
                            >
                                <template #input-icon>
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </template>
                            </VueDatePicker>
                            <p v-if="errors.checkOutTime" class="text-xs text-red-600 mt-1">{{ errors.checkOutTime }}</p>
                        </div>
                    </div>

                    <!-- Reason Select -->
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Разлог одсуства <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="reason"
                            v-model="form.reason"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                            :class="{ 'border-red-500': errors.reason }"
                        >
                            <option value="">Изаберите разлог...</option>
                            <option
                                v-for="reason in adminReasons"
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
                            maxlength="100"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none transition-colors"
                            placeholder="Унесите напомену..."
                        ></textarea>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-xs text-gray-500">
                                {{ form.notes.length }}/100 карактера
                            </p>
                        </div>
                    </div>

                    <!-- Warning Notice -->
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start">
                        <svg class="h-5 w-5 text-amber-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-xs text-amber-800">
                            Ова акција креира комплетан запис одсуства са почетним и крајњим временом.
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
                            :disabled="loading || !isFormValid"
                            class="flex-1 px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <svg v-if="loading" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{ loading ? 'Евидентирање...' : 'Евидентирај одсуство' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        required: true,
    },
    adminReasons: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
    date: null,
    checkInTime: null, // VueDatePicker time object {hours, minutes}
    checkOutTime: null, // VueDatePicker time object {hours, minutes}
    reason: '',
    notes: '',
});

const errors = ref({
    date: '',
    checkInTime: '',
    checkOutTime: '',
    reason: '',
});

const loading = ref(false);

const isFormValid = computed(() => {
    return form.value.date
        && form.value.checkInTime
        && form.value.checkOutTime
        && form.value.reason;
});

// Reset form when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.value = {
            date: null,
            checkInTime: null,
            checkOutTime: null,
            reason: '',
            notes: '',
        };
        errors.value = {
            date: '',
            checkInTime: '',
            checkOutTime: '',
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

const formatTime = (timeObj) => {
    if (!timeObj) return null;
    const hours = String(timeObj.hours).padStart(2, '0');
    const minutes = String(timeObj.minutes).padStart(2, '0');
    return `${hours}:${minutes}`;
};

const formatDate = (date) => {
    if (!date) return null;

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const handleSubmit = async () => {
    // Validate
    errors.value = {
        date: '',
        checkInTime: '',
        checkInTime: '',
        reason: '',
    };

    let hasErrors = false;

    if (!form.value.date) {
        errors.value.date = 'Датум је обавезан';
        hasErrors = true;
    }

    if (!form.value.checkInTime) {
        errors.value.checkInTime = 'Време почетка је обавезно';
        hasErrors = true;
    }

    if (!form.value.checkOutTime) {
        errors.value.checkOutTime = 'Време краја је обавезно';
        hasErrors = true;
    }

    if (!form.value.reason) {
        errors.value.reason = 'Разлог је обавезан';
        hasErrors = true;
    }

    // Validate check-out time is after check-in time
    if (form.value.checkInTime && form.value.checkOutTime) {
        const checkInMinutes = form.value.checkInTime.hours * 60 + form.value.checkInTime.minutes;
        const checkOutMinutes = form.value.checkOutTime.hours * 60 + form.value.checkOutTime.minutes;

        if (checkOutMinutes <= checkInMinutes) {
            errors.value.checkOutTime = 'Време краја мора бити после времена почетка';
            hasErrors = true;
        }
    }

    if (hasErrors) {
        return;
    }

    loading.value = true;

    try {
        await emit('submit', {
            user_id: props.user.UserID,
            date: formatDate(form.value.date),
            check_in_time: formatTime(form.value.checkInTime),
            check_out_time: formatTime(form.value.checkOutTime),
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
