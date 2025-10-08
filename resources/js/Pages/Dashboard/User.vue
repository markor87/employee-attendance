<template>
    <AppLayout :user="user" :laravel-version="laravelVersion">
        <!-- Welcome Header with Real-Time Clock -->
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                Добродошли, {{ user.FirstName }}!
            </h1>
            <div class="flex items-center justify-center space-x-2 text-lg text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ currentTime }}</span>
            </div>
        </div>

        <!-- Main Content - Centered -->
        <div class="max-w-2xl mx-auto">
            <!-- Status Card -->
            <div
                :class="[
                    'mb-8 rounded-2xl p-8 text-center shadow-xl border-2 transition-all duration-300',
                    isCheckedIn
                        ? 'bg-gradient-to-br from-green-50 to-emerald-50 border-green-300'
                        : 'bg-gradient-to-br from-red-50 to-rose-50 border-red-300'
                ]"
            >
                <!-- Status Icon -->
                <div class="mb-4 flex justify-center">
                    <div
                        :class="[
                            'h-24 w-24 rounded-full flex items-center justify-center shadow-lg',
                            isCheckedIn
                                ? 'bg-green-500'
                                : 'bg-red-500'
                        ]"
                    >
                        <svg v-if="isCheckedIn" class="h-14 w-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <svg v-else class="h-14 w-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Status Text -->
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    {{ isCheckedIn ? 'Тренутно пријављени' : 'Тренутно одјављени' }}
                </h2>
                <p class="text-sm text-gray-600">
                    {{ isCheckedIn ? 'Имате активну пријаву на посао' : 'Нисте пријављени на посао' }}
                </p>

                <!-- Check-in Time (if checked in) -->
                <div v-if="isCheckedIn && activeLog" class="mt-4 pt-4 border-t border-green-200">
                    <p class="text-sm text-gray-600 mb-1">Време пријаве:</p>
                    <p class="text-lg font-semibold text-gray-900">{{ formatDateTime(activeLog.VremePrijave) }}</p>
                    <p class="text-xs text-gray-500 mt-2">{{ calculateWorkTime(activeLog.VremePrijave) }}</p>
                </div>
            </div>

            <!-- Main Action Button -->
            <div class="mb-6">
                <button
                    v-if="!isCheckedIn"
                    @click="openCheckInModal"
                    class="w-full py-6 text-xl font-bold text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center space-x-3"
                >
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Пријави се на посао</span>
                </button>

                <button
                    v-else
                    @click="openCheckOutModal"
                    class="w-full py-6 text-xl font-bold text-white bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center space-x-3"
                >
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Одјави се са посла</span>
                </button>
            </div>

            <!-- My Logs Button -->
            <div class="mb-8">
                <a
                    :href="`/logs/${user.UserID}`"
                    class="w-full block py-4 text-center text-base font-semibold text-gray-700 bg-white border-2 border-gray-300 hover:border-gray-400 hover:bg-gray-50 rounded-xl shadow-sm hover:shadow-md transition-all duration-200"
                >
                    📋 Моји логови
                </a>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
                    <p class="text-sm text-gray-600 mb-1">Пријава данас</p>
                    <p class="text-2xl font-bold text-gray-900">{{ todayCheckIns }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
                    <p class="text-sm text-gray-600 mb-1">Укупно логова</p>
                    <p class="text-2xl font-bold text-gray-900">{{ totalLogs }}</p>
                </div>
            </div>
        </div>

        <!-- Check-In Modal -->
        <teleport to="body">
            <div
                v-if="showCheckInModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="showCheckInModal = false"
            >
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Пријава на посао</h3>

                    <!-- Reason Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Разлог пријаве</label>
                        <select
                            v-model="checkInForm.reason"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Изаберите разлог</option>
                            <option v-for="reason in checkInReasons" :key="reason.ReasonID" :value="reason.ReasonName">
                                {{ reason.ReasonName }}
                            </option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Напомена (опционо)</label>
                        <textarea
                            v-model="checkInForm.notes"
                            maxlength="500"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Унесите напомену..."
                        ></textarea>
                        <p class="text-xs text-gray-500 text-right mt-1">{{ checkInForm.notes.length }}/500</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button
                            @click="showCheckInModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Откажи
                        </button>
                        <button
                            @click="submitCheckIn"
                            :disabled="!checkInForm.reason || submitting"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            {{ submitting ? 'Пријављивање...' : 'Потврди' }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <!-- Check-Out Modal -->
        <teleport to="body">
            <div
                v-if="showCheckOutModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="showCheckOutModal = false"
            >
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Одјава са посла</h3>

                    <!-- Reason Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Разлог одјаве</label>
                        <select
                            v-model="checkOutForm.reason"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Изаберите разлог</option>
                            <option v-for="reason in checkOutReasons" :key="reason.ReasonID" :value="reason.ReasonName">
                                {{ reason.ReasonName }}
                            </option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Напомена (опционо)</label>
                        <textarea
                            v-model="checkOutForm.notes"
                            maxlength="500"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Унесите напомену..."
                        ></textarea>
                        <p class="text-xs text-gray-500 text-right mt-1">{{ checkOutForm.notes.length }}/500</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button
                            @click="showCheckOutModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Откажи
                        </button>
                        <button
                            @click="submitCheckOut"
                            :disabled="!checkOutForm.reason || submitting"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            {{ submitting ? 'Одјављивање...' : 'Потврди' }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    activeLog: {
        type: Object,
        default: null,
    },
    checkInReasons: {
        type: Array,
        default: () => [],
    },
    checkOutReasons: {
        type: Array,
        default: () => [],
    },
    todayCheckIns: {
        type: Number,
        default: 0,
    },
    totalLogs: {
        type: Number,
        default: 0,
    },
    laravelVersion: {
        type: String,
        default: '11.x',
    },
});

const toast = useToast();
const currentTime = ref('');
const showCheckInModal = ref(false);
const showCheckOutModal = ref(false);
const submitting = ref(false);

const checkInForm = ref({
    reason: '',
    notes: '',
});

const checkOutForm = ref({
    reason: '',
    notes: '',
});

const isCheckedIn = computed(() => props.user.Status === 'Prijavljen');

// Real-time clock
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleString('sr-RS', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

let clockInterval = null;

onMounted(() => {
    updateTime();
    clockInterval = setInterval(updateTime, 1000);
});

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval);
    }
});

const formatDateTime = (dateTime) => {
    if (!dateTime) return '-';
    const date = new Date(dateTime);
    return date.toLocaleString('sr-RS', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const calculateWorkTime = (checkInTime) => {
    if (!checkInTime) return '';
    const start = new Date(checkInTime);
    const now = new Date();
    const diff = now - start;

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    return `Радите ${hours}h ${minutes}m`;
};

const openCheckInModal = () => {
    checkInForm.value = { reason: '', notes: '' };
    showCheckInModal.value = true;
};

const openCheckOutModal = () => {
    checkOutForm.value = { reason: '', notes: '' };
    showCheckOutModal.value = true;
};

const submitCheckIn = async () => {
    submitting.value = true;

    try {
        const response = await fetch('/attendance/check-in', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                reason: checkInForm.value.reason,
                notes: checkInForm.value.notes,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            toast.error(data.message || 'Грешка приликом пријављивања.');
            submitting.value = false;
            return;
        }

        toast.success('Успешно сте се пријавили на посао!');
        showCheckInModal.value = false;
        submitting.value = false;

        // Reload page to update status
        router.reload();
    } catch (error) {
        console.error('Check-in error:', error);
        toast.error('Дошло је до грешке приликом пријављивања.');
        submitting.value = false;
    }
};

const submitCheckOut = async () => {
    submitting.value = true;

    try {
        const response = await fetch('/attendance/check-out', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                reason: checkOutForm.value.reason,
                notes: checkOutForm.value.notes,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            toast.error(data.message || 'Грешка приликом одјављивања.');
            submitting.value = false;
            return;
        }

        toast.success('Успешно сте се одјавили са посла!');
        showCheckOutModal.value = false;
        submitting.value = false;

        // Reload page to update status
        router.reload();
    } catch (error) {
        console.error('Check-out error:', error);
        toast.error('Дошло је до грешке приликом одјављивања.');
        submitting.value = false;
    }
};
</script>
