<template>
    <teleport to="body">
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 rounded-t-xl">
                    <h2 class="text-2xl font-bold text-white">
                        Измена лога
                    </h2>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                    <!-- Date -->
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                            Датум *
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
                        />
                    </div>

                    <!-- Check-in and Check-out times -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Check-in Time -->
                        <div>
                            <label for="check_in_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Време почетка *
                            </label>
                            <VueDatePicker
                                v-model="form.check_in_time"
                                time-picker
                                :is-24="true"
                                auto-apply
                                :clearable="false"
                                placeholder="Изаберите време"
                            >
                                <template #input-icon>
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </template>
                            </VueDatePicker>
                        </div>

                        <!-- Check-out Time -->
                        <div>
                            <label for="check_out_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Време краја *
                            </label>
                            <VueDatePicker
                                v-model="form.check_out_time"
                                time-picker
                                :is-24="true"
                                auto-apply
                                :clearable="false"
                                placeholder="Изаберите време"
                            >
                                <template #input-icon>
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </template>
                            </VueDatePicker>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                            Разлог одсуства *
                        </label>
                        <select
                            id="reason"
                            v-model="form.reason"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Изаберите разлог</option>
                            <option v-for="reason in reasons" :key="reason.ReasonID" :value="reason.ReasonName">
                                {{ reason.ReasonName }}
                            </option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Напомена (опционо)
                        </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            maxlength="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                            placeholder="Унесите напомену..."
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">{{ form.notes?.length || 0 }}/100 карактера</p>
                    </div>

                    <!-- Warning -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm text-yellow-800">
                                Можете мењати само логове који нису истекли. Време краја мора бити у будућности.
                            </p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Откажи
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ isSubmitting ? 'Чување...' : 'Сачувај измене' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    log: {
        type: Object,
        required: true,
    },
    reasons: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'submit']);

const toast = useToast();
const isSubmitting = ref(false);

// Form data
const form = ref({
    date: null, // Date object for VueDatePicker
    check_in_time: null, // VueDatePicker time object {hours, minutes}
    check_out_time: null, // VueDatePicker time object {hours, minutes}
    reason: '',
    notes: '',
});

// Initialize form with log data
onMounted(() => {
    const checkInDate = new Date(props.log.VremePrijave);
    const checkOutDate = new Date(props.log.VremeOdjave);

    form.value = {
        date: checkInDate,
        check_in_time: {
            hours: checkInDate.getHours(),
            minutes: checkInDate.getMinutes(),
        },
        check_out_time: {
            hours: checkOutDate.getHours(),
            minutes: checkOutDate.getMinutes(),
        },
        reason: props.log.RazlogPrijave || '',
        notes: props.log.Napomena || '',
    };
});

const formatDate = (date) => {
    if (!date) return null;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const formatTime = (timeObj) => {
    if (!timeObj) return null;
    const hours = String(timeObj.hours).padStart(2, '0');
    const minutes = String(timeObj.minutes).padStart(2, '0');
    return `${hours}:${minutes}`;
};

const handleSubmit = () => {
    // Validate times before submission
    const checkInMinutes = form.value.check_in_time.hours * 60 + form.value.check_in_time.minutes;
    const checkOutMinutes = form.value.check_out_time.hours * 60 + form.value.check_out_time.minutes;

    if (checkOutMinutes <= checkInMinutes) {
        toast.error('Време краја мора бити после времена почетка.');
        return;
    }

    // Format date and time for validation
    const dateStr = formatDate(form.value.date);
    const checkOutTimeStr = formatTime(form.value.check_out_time);

    // Check if check-out time is in the future
    const checkOutDateTime = new Date(`${dateStr}T${checkOutTimeStr}`);
    const now = new Date();
    if (checkOutDateTime <= now) {
        toast.error('Време краја мора бити у будућности.');
        return;
    }

    isSubmitting.value = true;

    // Submit with formatted data
    emit('submit', {
        date: dateStr,
        check_in_time: formatTime(form.value.check_in_time),
        check_out_time: formatTime(form.value.check_out_time),
        reason: form.value.reason,
        notes: form.value.notes,
    });
};
</script>
