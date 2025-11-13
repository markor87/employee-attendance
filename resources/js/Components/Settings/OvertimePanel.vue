<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Overtime Подешавања</h2>
            <p class="text-sm text-gray-600">Подешавања за overtime проверу присуства корисника.</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
            <!-- Enable Overtime Toggle -->
            <div class="flex items-center justify-between pb-6 border-b border-gray-200">
                <div class="flex-1">
                    <label class="text-base font-semibold text-gray-900">
                        Омогући overtime проверу
                    </label>
                    <p class="text-sm text-gray-600 mt-1">
                        Активира систем за проверу присуства корисника после радног времена.
                    </p>
                </div>
                <div class="ml-4">
                    <button
                        type="button"
                        @click="toggleOvertime"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                            localSettings.overtime_enabled ? 'bg-blue-600' : 'bg-gray-200'
                        ]"
                        role="switch"
                        :aria-checked="localSettings.overtime_enabled"
                    >
                        <span
                            :class="[
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                localSettings.overtime_enabled ? 'translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <div v-if="localSettings.overtime_enabled" class="space-y-6">
                <!-- Check Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Време почетка провере
                    </label>
                    <div class="flex items-center space-x-2">
                        <input
                            type="time"
                            v-model="checkTime"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <span class="text-sm text-gray-600">(формат: HH:MM)</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Време када почиње провера присуства за overtime (нпр. 15:30).
                    </p>
                </div>

                <!-- Working Days -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Радни дани
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <label
                            v-for="day in weekDays"
                            :key="day.code"
                            class="flex items-center space-x-2 cursor-pointer group"
                        >
                            <input
                                type="checkbox"
                                :value="day.code"
                                v-model="selectedDays"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                            />
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ day.label }}</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Одаберите дане када се врши overtime провера.
                    </p>
                </div>

                <!-- Prompt Interval -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Интервал провере (минути)
                    </label>
                    <input
                        type="number"
                        v-model.number="localSettings.overtime_prompt_interval"
                        min="1"
                        max="120"
                        class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <p class="text-xs text-gray-500 mt-2">
                        Број минута између приказивања модала за потврду присуства (1-120).
                    </p>
                </div>

                <!-- Prompt Timeout -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Timeout за одговор (минути)
                    </label>
                    <input
                        type="number"
                        v-model.number="localSettings.overtime_prompt_timeout"
                        min="1"
                        max="60"
                        class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <p class="text-xs text-gray-500 mt-2">
                        Број минута које корисник има да потврди присуство пре аутоматске одјаве (1-60).
                    </p>
                </div>

                <!-- Info Messages -->
                <div class="bg-blue-50 border-l-4 border-blue-600 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-700">
                            <p class="font-semibold mb-1">Како ради overtime провера:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Систем проверава присуство после подешеног времена</li>
                                <li>Корисници добијају модал за потврду присуства</li>
                                <li>Ако не одговоре у року, аутоматски се одјављују</li>
                                <li>Провера се врши само радним данима</li>
                            </ul>
                        </div>
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
import { ref, computed, watch } from 'vue';
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

// Watch for props changes and sync localSettings
watch(() => props.settings, (newSettings) => {
    localSettings.value = { ...newSettings };
}, { deep: true });

// Week days mapping
const weekDays = [
    { code: 'Mon', label: 'Понедељак' },
    { code: 'Tue', label: 'Уторак' },
    { code: 'Wed', label: 'Среда' },
    { code: 'Thu', label: 'Четвртак' },
    { code: 'Fri', label: 'Петак' },
    { code: 'Sat', label: 'Субота' },
    { code: 'Sun', label: 'Недеља' },
];

// Selected days as array for checkboxes
const selectedDays = computed({
    get() {
        if (!localSettings.value.overtime_working_days) return [];
        return localSettings.value.overtime_working_days.split(',').filter(d => d);
    },
    set(value) {
        localSettings.value.overtime_working_days = value.join(',');
    },
});

// Extract HH:MM from HH:MM:SS
const checkTime = computed({
    get() {
        const time = localSettings.value.overtime_check_time || '15:30:00';
        return time.substring(0, 5);
    },
    set(value) {
        localSettings.value.overtime_check_time = value + ':00';
    },
});

const toggleOvertime = () => {
    localSettings.value.overtime_enabled = !localSettings.value.overtime_enabled;
};

const saveSettings = () => {
    saving.value = true;

    router.post('/settings', {
        overtime_enabled: localSettings.value.overtime_enabled,
        overtime_check_time: localSettings.value.overtime_check_time,
        overtime_prompt_interval: localSettings.value.overtime_prompt_interval,
        overtime_prompt_timeout: localSettings.value.overtime_prompt_timeout,
        overtime_working_days: localSettings.value.overtime_working_days,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Overtime подешавања су успешно сачувана.');
            saving.value = false;
        },
        onError: (errors) => {
            toast.error('Грешка при чувању подешавања.');
            console.error('Errors:', errors);
            saving.value = false;
        },
    });
};
</script>
