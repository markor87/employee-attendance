<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Аутоматски Logout</h2>
            <p class="text-sm text-gray-600">Подешавања за аутоматско одјављивање корисника у одређено време.</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
            <!-- Enable Auto Logout Toggle -->
            <div class="flex items-center justify-between pb-6 border-b border-gray-200">
                <div class="flex-1">
                    <label class="text-base font-semibold text-gray-900">
                        Омогући аутоматски logout
                    </label>
                    <p class="text-sm text-gray-600 mt-1">
                        Аутоматски одјављује све пријављене кориснике у одређено време.
                    </p>
                </div>
                <div class="ml-4">
                    <button
                        type="button"
                        @click="toggleAutoLogout"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                            localSettings.AutoLogoutEnabled ? 'bg-blue-600' : 'bg-gray-200'
                        ]"
                        role="switch"
                        :aria-checked="localSettings.AutoLogoutEnabled"
                    >
                        <span
                            :class="[
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                localSettings.AutoLogoutEnabled ? 'translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Logout Time -->
            <div v-if="localSettings.AutoLogoutEnabled">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Време аутоматског logout-а
                </label>
                <div class="flex items-center space-x-2">
                    <input
                        type="time"
                        v-model="logoutTime"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <span class="text-sm text-gray-600">(формат: HH:MM)</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Корисници ће бити аутоматски одјављени у ово време сваки дан.
                </p>
            </div>

            <!-- Info Messages -->
            <div v-if="localSettings.AutoLogoutEnabled" class="bg-amber-50 border-l-4 border-amber-600 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-amber-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-amber-700">
                        <p class="font-semibold mb-1">Напомена:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Сви активни тајмлогови ће бити затворени</li>
                            <li>Корисници ће бити одјављени у подешено време</li>
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

// Extract HH:MM from HH:MM:SS
const logoutTime = computed({
    get() {
        return localSettings.value.AutoLogoutTime.substring(0, 5);
    },
    set(value) {
        localSettings.value.AutoLogoutTime = value + ':00';
    },
});

const toggleAutoLogout = () => {
    localSettings.value.AutoLogoutEnabled = !localSettings.value.AutoLogoutEnabled;
};

const saveSettings = () => {
    saving.value = true;

    router.post('/settings', {
        AutoLogoutEnabled: localSettings.value.AutoLogoutEnabled,
        AutoLogoutTime: localSettings.value.AutoLogoutTime,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Подешавања аутоматског logout-а су сачувана.');
            saving.value = false;
        },
        onError: (errors) => {
            toast.error('Грешка при чувању подешавања.');
            saving.value = false;
        },
    });
};
</script>
