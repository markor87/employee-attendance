<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Email Подешавања</h2>
            <p class="text-sm text-gray-600">Преглед тренутне SMTP конфигурације и тестирање email функционалности.</p>
        </div>

        <!-- Info Card -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1">Email подешавања су конфигурисана у .env фајлу</p>
                    <p>За промену SMTP параметара, уредите <code class="bg-blue-100 px-1 py-0.5 rounded">.env</code> фајл и рестартујте апликацију.</p>
                </div>
            </div>
        </div>

        <!-- Read-only Settings Display -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Тренутна конфигурација</h3>

            <!-- Email From Address -->
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">Email адреса:</span>
                <span class="text-sm text-gray-900">{{ settings.EmailFromAddress || 'Није конфигурисано' }}</span>
            </div>

            <!-- SMTP Host -->
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">SMTP Host:</span>
                <span class="text-sm text-gray-900">{{ settings.SmtpHost || 'smtp.gmail.com' }}</span>
            </div>

            <!-- SMTP Port -->
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">SMTP Port:</span>
                <span class="text-sm text-gray-900">{{ settings.SmtpPort || 587 }}</span>
            </div>

            <!-- SSL/TLS -->
            <div class="flex justify-between items-center py-2">
                <span class="text-sm font-medium text-gray-600">SSL/TLS Енкрипција:</span>
                <span :class="[
                    'text-sm font-semibold',
                    settings.EnableSsl ? 'text-green-600' : 'text-gray-500'
                ]">
                    {{ settings.EnableSsl ? '✓ Омогућено' : '✗ Онемогућено' }}
                </span>
            </div>
        </div>

        <!-- Test Email Section -->
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
const testing = ref(false);
const testEmail = ref('');

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
