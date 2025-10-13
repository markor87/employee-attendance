<template>
    <teleport to="body">
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-xl">
                    <h2 class="text-2xl font-bold text-white">
                        Преглед корисника
                    </h2>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                    <p class="mt-4 text-gray-600">Учитавање података...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-4 text-red-600 font-medium">{{ error }}</p>
                </div>

                <!-- User Details -->
                <div v-else-if="userData" class="p-6 space-y-4">
                    <!-- Avatar and Name -->
                    <div class="flex items-center space-x-4 pb-4 border-b border-gray-200">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-2xl">
                            {{ getUserInitials(userData) }}
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">
                                {{ userData.FirstName }} {{ userData.LastName }}
                            </h3>
                            <p class="text-sm text-gray-500">{{ userData.Email }}</p>
                        </div>
                    </div>

                    <!-- User Information Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- First Name -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Име
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ userData.FirstName }}</p>
                        </div>

                        <!-- Last Name -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Презиме
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ userData.LastName }}</p>
                        </div>

                        <!-- Email -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Email
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ userData.Email }}</p>
                        </div>

                        <!-- Role -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Улога
                            </label>
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    getRoleBadgeClass(userData.Role)
                                ]"
                            >
                                {{ getRoleLabel(userData.Role) }}
                            </span>
                        </div>

                        <!-- Sector -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Сектор
                            </label>
                            <p class="text-sm font-medium text-gray-900">
                                {{ userData.sector?.sector || 'Без сектора' }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Статус
                            </label>
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    getStatusBadgeClass(userData.current_status)
                                ]"
                            >
                                <svg
                                    :class="[
                                        'mr-1.5 h-2 w-2',
                                        userData.current_status === 'Prijavljen' ? 'animate-pulse' : ''
                                    ]"
                                    fill="currentColor"
                                    viewBox="0 0 8 8"
                                >
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                {{ getStatusLabel(userData.current_status) }}
                            </span>
                        </div>

                        <!-- Date Created -->
                        <div v-if="userData.DateCreated" class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Датум креирања
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ formatDate(userData.DateCreated) }}</p>
                        </div>

                        <!-- Date Updated -->
                        <div v-if="userData.DateUpdated" class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                Последња измена
                            </label>
                            <p class="text-sm font-medium text-gray-900">{{ formatDate(userData.DateUpdated) }}</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors"
                        >
                            Затвори
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { getRoleLabel, getRoleBadgeClass } from '@/utils/roleMapping';

const props = defineProps({
    userId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['close']);

const loading = ref(true);
const error = ref(null);
const userData = ref(null);

const getUserInitials = (user) => {
    if (!user) return '?';
    const first = user.FirstName?.[0] || '';
    const last = user.LastName?.[0] || '';
    return (first + last).toUpperCase();
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('sr-RS', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'Prijavljen': 'bg-green-100 text-green-800',
        'Одјављен': 'bg-gray-100 text-gray-800',
        'Odjavljen': 'bg-gray-100 text-gray-800',
        'Службено одсуство': 'bg-orange-100 text-orange-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        'Prijavljen': 'Пријављен',
        'Одјављен': 'Одјављен',
        'Odjavljen': 'Одјављен',
        'Службено одсуство': 'Службено одсуство',
    };
    return labels[status] || status;
};

// Fetch user data
onMounted(async () => {
    try {
        const response = await fetch(`/users/${props.userId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Грешка при учитавању података о кориснику');
        }

        const data = await response.json();

        if (data.success) {
            userData.value = data.user;
        } else {
            error.value = 'Грешка при учитавању података';
        }
    } catch (err) {
        error.value = err.message || 'Грешка при учитавању података';
    } finally {
        loading.value = false;
    }
});
</script>
