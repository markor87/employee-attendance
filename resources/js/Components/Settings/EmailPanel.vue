<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Email Подешавања</h2>
            <p class="text-sm text-gray-600">Конфигурација SMTP сервера за слање email нотификација.</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
            <!-- Email From Address -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email адреса пошиљаоца *
                </label>
                <input
                    type="email"
                    v-model="localSettings.EmailFromAddress"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="noreply@firma.com"
                />
                <p class="text-xs text-gray-500 mt-1">
                    Email адреса која ће се користити као пошиљалац свих порука.
                </p>
            </div>

            <!-- Email Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Лозинка *
                </label>
                <input
                    type="password"
                    v-model="localSettings.EmailPassword"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="••••••••"
                />
                <p class="text-xs text-gray-500 mt-1">
                    За Gmail користите App Password, не обичну лозинку.
                </p>
            </div>

            <!-- SMTP Host -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Host *
                </label>
                <input
                    type="text"
                    v-model="localSettings.SmtpHost"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="smtp.gmail.com"
                />
                <p class="text-xs text-gray-500 mt-1">
                    Адреса SMTP сервера (нпр. smtp.gmail.com, smtp.office365.com).
                </p>
            </div>

            <!-- SMTP Port -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Port *
                </label>
                <div class="flex space-x-4">
                    <input
                        type="number"
                        v-model.number="localSettings.SmtpPort"
                        class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        min="1"
                        max="65535"
                    />
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <span>Типични портови:</span>
                        <button @click="localSettings.SmtpPort = 587" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded">587 (TLS)</button>
                        <button @click="localSettings.SmtpPort = 465" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded">465 (SSL)</button>
                        <button @click="localSettings.SmtpPort = 25" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded">25</button>
                    </div>
                </div>
            </div>

            <!-- Enable SSL/TLS -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex-1">
                    <label class="text-base font-semibold text-gray-900">
                        Омогући SSL/TLS енкрипцију
                    </label>
                    <p class="text-sm text-gray-600 mt-1">
                        Препоручено за безбедну комуникацију са SMTP сервером.
                    </p>
                </div>
                <div class="ml-4">
                    <button
                        type="button"
                        @click="toggleSsl"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                            localSettings.EnableSsl ? 'bg-blue-600' : 'bg-gray-200'
                        ]"
                        role="switch"
                        :aria-checked="localSettings.EnableSsl"
                    >
                        <span
                            :class="[
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                localSettings.EnableSsl ? 'translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Test Email -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Тестирај email конфигурацију</h3>
                <div class="flex space-x-3">
                    <input
                        type="email"
                        v-model="testEmail"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="test@example.com"
                    />
                    <button
                        @click="sendTestEmail"
                        :disabled="testing || !testEmail"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="!testing">Пошаљи тест</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Слање...
                        </span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Унесите email адресу на коју желите да пошаљете тест поруку.
                </p>
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
import { ref } from 'vue';
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
const testing = ref(false);
const testEmail = ref('');
const localSettings = ref({ ...props.settings });

const toggleSsl = () => {
    localSettings.value.EnableSsl = !localSettings.value.EnableSsl;
};

const saveSettings = () => {
    saving.value = true;

    router.post('/settings', {
        EmailFromAddress: localSettings.value.EmailFromAddress,
        EmailPassword: localSettings.value.EmailPassword,
        SmtpHost: localSettings.value.SmtpHost,
        SmtpPort: localSettings.value.SmtpPort,
        EnableSsl: localSettings.value.EnableSsl,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Email подешавања су сачувана.');
            saving.value = false;
        },
        onError: (errors) => {
            toast.error('Грешка при чувању подешавања.');
            saving.value = false;
        },
    });
};

const sendTestEmail = () => {
    testing.value = true;

    router.post('/settings/test-email', {
        test_email: testEmail.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Тест email је успешно послат на ${testEmail.value}`);
            testing.value = false;
        },
        onError: (errors) => {
            toast.error(errors.email || 'Грешка при слању тест email-а.');
            testing.value = false;
        },
    });
};
</script>
