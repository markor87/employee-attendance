<template>
    <AppLayout :user="user" :laravel-version="laravelVersion">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Добродошли назад, {{ user.FirstName }}!</h2>
            <p class="text-sm text-gray-600 mt-1">Ево прегледа вашег система евиденције присуства.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <StatsCard
                title="Укупно корисника"
                :value="stats.total_users"
                subtitle="Укупан број запослених"
                icon="users"
                color="blue"
            />

            <StatsCard
                title="Тренутно пријављени"
                :value="stats.checked_in"
                :subtitle="`${checkedInPercentage}% од укупног броја`"
                icon="check"
                color="green"
            />

            <StatsCard
                title="Укупно логова"
                :value="stats.total_logs"
                subtitle="Укупан број пријава/одјава"
                icon="clock"
                color="purple"
            />

            <StatsCard
                title="Данашње пријаве"
                :value="stats.today_checkins || 0"
                subtitle="Пријаве данас"
                icon="activity"
                color="orange"
            />
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Недавна активност</h3>
                    <button
                        class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
                        disabled
                    >
                        Погледај све
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Корисник
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Време пријаве
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Време одјаве
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Разлог
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Статус
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="recentLogs.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="font-medium">Нема недавне активности</p>
                                <p class="text-xs mt-1">Пријаве и одјаве ће се приказати овде</p>
                            </td>
                        </tr>
                        <tr v-for="log in recentLogs" :key="log.LogID" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ getUserInitials(log.user) }}
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ log.user?.FirstName }} {{ log.user?.LastName }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ log.user?.Email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDateTime(log.VremePrijave) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span v-if="log.VremeOdjave">{{ formatDateTime(log.VremeOdjave) }}</span>
                                <span v-else class="text-gray-400 italic">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ log.reason?.Description || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        log.VremeOdjave
                                            ? 'bg-gray-100 text-gray-800'
                                            : 'bg-green-100 text-green-800'
                                    ]"
                                >
                                    <svg
                                        :class="[
                                            'mr-1.5 h-2 w-2',
                                            log.VremeOdjave ? '' : 'animate-pulse'
                                        ]"
                                        fill="currentColor"
                                        viewBox="0 0 8 8"
                                    >
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    {{ log.VremeOdjave ? 'Одјављен' : 'Пријављен' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination or Load More (Future) -->
            <div v-if="recentLogs.length > 0" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center">
                    Приказано {{ recentLogs.length }} најновијих активности
                </p>
            </div>
        </div>

        <!-- Quick Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <!-- Your Status Card -->
            <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-lg border border-blue-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Ваш статус</h3>
                        <p class="text-sm text-gray-600 mt-1">Тренутни статус присуства</p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-white shadow-sm flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span
                        :class="[
                            'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                            user.Status === 'Prijavljen'
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-800'
                        ]"
                    >
                        <svg
                            :class="[
                                'mr-2 h-2.5 w-2.5',
                                user.Status === 'Prijavljen' ? 'animate-pulse' : ''
                            ]"
                            fill="currentColor"
                            viewBox="0 0 8 8"
                        >
                            <circle cx="4" cy="4" r="3" />
                        </svg>
                        {{ user.Status === 'Prijavljen' ? 'Тренутно пријављен' : 'Одјављен' }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-3">
                    Користите дугме за пријаву/одјаву у панелу са леве стране.
                </p>
            </div>

            <!-- System Info Card -->
            <div class="bg-gradient-to-br from-green-50 to-teal-50 rounded-lg border border-green-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Информације о систему</h3>
                        <p class="text-sm text-gray-600 mt-1">Верзија и статус</p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-white shadow-sm flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Верзија:</span>
                        <span class="font-medium text-gray-900">1.0.0</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Laravel:</span>
                        <span class="font-medium text-gray-900">{{ laravelVersion }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Статус:</span>
                        <span class="inline-flex items-center text-green-600 font-medium">
                            <svg class="mr-1 h-2 w-2 animate-pulse" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Online
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    recentLogs: {
        type: Array,
        default: () => [],
    },
    laravelVersion: {
        type: String,
        default: '11.x',
    },
});

const checkedInPercentage = computed(() => {
    if (props.stats.total_users === 0) return 0;
    return Math.round((props.stats.checked_in / props.stats.total_users) * 100);
});

const getUserInitials = (user) => {
    if (!user) return '?';
    const first = user.FirstName?.[0] || '';
    const last = user.LastName?.[0] || '';
    return (first + last).toUpperCase();
};

const formatDateTime = (dateTime) => {
    if (!dateTime) return '-';
    const date = new Date(dateTime);
    return date.toLocaleString('sr-RS', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
