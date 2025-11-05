<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Двофакторска Верификација
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Унесите 6-циферни код који је послат на ваш email
                </p>
            </div>

            <!-- 2FA Form -->
            <div class="mt-8 bg-white py-8 px-6 shadow-xl rounded-xl border border-gray-100">
                <form @submit.prevent="submitVerification" class="space-y-6">
                    <!-- Code Input -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1 text-center">
                            Верификациони код
                        </label>
                        <input
                            id="code"
                            v-model="code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            autocomplete="one-time-code"
                            required
                            class="appearance-none relative block w-full px-4 py-4 text-center text-2xl font-bold tracking-widest border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': errors.code }"
                            placeholder="000000"
                            @input="validateCodeInput"
                        />
                        <p v-if="errors.code" class="mt-2 text-sm text-red-600 text-center">
                            {{ errors.code }}
                        </p>
                    </div>

                    <!-- Timer Display -->
                    <div class="text-center">
                        <div class="inline-flex items-center space-x-2 text-sm" :class="timeRemaining < 60 ? 'text-red-600 font-semibold' : 'text-gray-600'">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Код истиче за: {{ formatTime(timeRemaining) }}</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button
                            type="submit"
                            :disabled="loading || code.length !== 6"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl"
                        >
                            <span v-if="!loading">Верификуј</span>
                            <span v-else class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Верификација...
                            </span>
                        </button>
                    </div>

                    <!-- Resend Button -->
                    <div class="text-center">
                        <button
                            type="button"
                            @click="resendCode"
                            :disabled="resending || timeRemaining > 240"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span v-if="!resending">Нисте добили код? Пошаљи поново</span>
                            <span v-else>Слање...</span>
                        </button>
                        <p v-if="timeRemaining > 240" class="mt-1 text-xs text-gray-500">
                            Можете поново послати код након {{ formatTime(timeRemaining - 240) }}
                        </p>
                    </div>
                </form>

                <!-- Back to Login -->
                <div class="mt-6 text-center border-t pt-6">
                    <AppLink
                        href="/login"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        ← Назад на пријаву
                    </AppLink>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-gray-500">
                <p>&copy; {{ new Date().getFullYear() }} Employee Attendance System</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const props = defineProps({
    remaining_seconds: Number,
});

const toast = useToast();

const code = ref('');
const errors = ref({});
const loading = ref(false);
const resending = ref(false);
const timeRemaining = ref(props.remaining_seconds || 300);
let timerInterval = null;

// Format time as MM:SS
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

// Validate code input (only numbers)
const validateCodeInput = (event) => {
    code.value = code.value.replace(/[^0-9]/g, '');
};

// Submit verification
const submitVerification = async () => {
    if (code.value.length !== 6) {
        return;
    }

    loading.value = true;
    errors.value = {};

    console.log('=== 2FA VERIFICATION DEBUG ===');
    console.log('Code being sent:', code.value);
    console.log('Code length:', code.value.length);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        console.log('CSRF Token:', csrfToken ? 'Found' : 'NOT FOUND');

        const response = await window.axios.post('/2fa/verify', { code: code.value }, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        console.log('Response status:', response.status);
        console.log('Response data:', response.data);

        // Axios automatically parses JSON
        const data = response.data;

        if (data.success) {
            console.log('Verification successful! Redirecting to:', data.redirect);
            toast.success('Верификација успешна!');
            router.visit(data.redirect);
        } else {
            console.error('Verification failed:', data);
            if (data.error) {
                toast.error(data.error);
            } else {
                toast.error('Дошло је до грешке приликом верификације.');
            }
            loading.value = false;
        }
    } catch (error) {
        console.error('=== VERIFICATION EXCEPTION ===');
        console.error('Error type:', error.constructor.name);
        console.error('Error message:', error.message);

        // Handle axios error response
        if (error.response) {
            console.error('Response status:', error.response.status);
            console.error('Response data:', error.response.data);

            if (error.response.data?.errors) {
                errors.value = error.response.data.errors;
                if (error.response.data.errors.code) {
                    toast.error(error.response.data.errors.code[0]);
                }
            } else if (error.response.data?.error) {
                toast.error(error.response.data.error);
            } else {
                toast.error('Дошло је до грешке приликом верификације.');
            }
        } else {
            console.error('Error stack:', error.stack);
            toast.error('Дошло је до грешке приликом верификације.');
        }
        loading.value = false;
    }
};

// Resend code
const resendCode = async () => {
    resending.value = true;

    try {
        const response = await window.axios.post('/2fa/resend', {}, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = response.data;

        toast.success(data.message);
        timeRemaining.value = data.remaining_seconds;
        code.value = '';
        errors.value = {};
    } catch (error) {
        console.error('Resend error:', error);
        if (error.response && error.response.data) {
            toast.error(error.response.data.error || 'Дошло је до грешке при слању кода.');
        } else {
            toast.error('Дошло је до грешке при слању кода.');
        }
    } finally {
        resending.value = false;
    }
};

// Start countdown timer
onMounted(() => {
    timerInterval = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--;
        } else {
            clearInterval(timerInterval);
            toast.error('Код је истекао. Молимо пријавите се поново.');
            setTimeout(() => {
                router.visit('/login');
            }, 2000);
        }
    }, 1000);
});

// Cleanup timer
onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>
