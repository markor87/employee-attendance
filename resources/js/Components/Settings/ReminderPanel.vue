<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Email Подсетници</h2>
            <p class="text-sm text-gray-600">Аутоматско слање email подсетника пријављеним корисницима.</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
            <!-- Enable Reminders Toggle -->
            <div class="flex items-center justify-between pb-6 border-b border-gray-200">
                <div class="flex-1">
                    <label class="text-base font-semibold text-gray-900">
                        Омогући email подсетнике
                    </label>
                    <p class="text-sm text-gray-600 mt-1">
                        Подсетници се шаљу у зависности од статуса корисника.
                    </p>
                </div>
                <div class="ml-4">
                    <button
                        type="button"
                        @click="toggleReminders"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                            localSettings.ReminderEnabled ? 'bg-blue-600' : 'bg-gray-200'
                        ]"
                        role="switch"
                        :aria-checked="localSettings.ReminderEnabled"
                    >
                        <span
                            :class="[
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                localSettings.ReminderEnabled ? 'translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Check-In Reminder Time -->
            <div v-if="localSettings.ReminderEnabled">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Време подсетника за пријаву
                </label>
                <div class="flex items-center space-x-2">
                    <input
                        type="time"
                        v-model="checkInTime"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <span class="text-sm text-gray-600">(формат: HH:MM)</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Email подсетник за пријаву ће бити послат одјављеним корисницима у ово време сваки дан.
                </p>
            </div>

            <!-- Check-Out Reminder Time -->
            <div v-if="localSettings.ReminderEnabled">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Време подсетника за одјаву
                </label>
                <div class="flex items-center space-x-2">
                    <input
                        type="time"
                        v-model="checkOutTime"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <span class="text-sm text-gray-600">(формат: HH:MM)</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Email подсетник за одјаву ће бити послат пријављеним корисницима у ово време сваки дан.
                </p>
            </div>

            <!-- Info Messages -->
            <div v-if="localSettings.ReminderEnabled" class="bg-blue-50 border-l-4 border-blue-600 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p class="font-semibold mb-1">Напомена:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Подсетник за пријаву (нпр. 07:25) се шаље корисницима са статусом "Одјављен"</li>
                            <li>Подсетник за одјаву (нпр. 15:25) се шаље корисницима са статусом "Пријављен"</li>
                            <li>Email подешавања морају бити исправно конфигурисана</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button
                @click="saveSettings"
                :disabled="saving"
                class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="!saving">💾 Сачувај подешавања</span>
                <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Чување...
                </span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const toast = useToast();
const saving = ref(false);
const localSettings = ref({ ...props.settings });

// Extract HH:MM from HH:MM:SS for check-in time
const checkInTime = computed({
    get() {
        return localSettings.value.ReminderCheckInTime?.substring(0, 5) || '07:25';
    },
    set(value) {
        localSettings.value.ReminderCheckInTime = value + ':00';
    },
});

// Extract HH:MM from HH:MM:SS for check-out time
const checkOutTime = computed({
    get() {
        return localSettings.value.ReminderCheckOutTime?.substring(0, 5) || '15:25';
    },
    set(value) {
        localSettings.value.ReminderCheckOutTime = value + ':00';
    },
});

const toggleReminders = () => {
    localSettings.value.ReminderEnabled = !localSettings.value.ReminderEnabled;
};

const saveSettings = () => {
    saving.value = true;

    router.post('/settings', {
        ReminderEnabled: localSettings.value.ReminderEnabled,
        ReminderCheckInTime: localSettings.value.ReminderCheckInTime,
        ReminderCheckOutTime: localSettings.value.ReminderCheckOutTime,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Подешавања email подсетника су сачувана.');
            saving.value = false;
        },
        onError: (errors) => {
            toast.error('Грешка при чувању подешавања.');
            saving.value = false;
        },
    });
};
</script>
