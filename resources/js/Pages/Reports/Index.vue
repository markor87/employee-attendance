<template>
    <AppLayout :user="user">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
                title="На службеном одсуству"
                :value="stats.on_leave"
                :subtitle="`${onLeavePercentage}% од укупног броја`"
                icon="calendar"
                color="orange"
            />
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Корисници</h3>

                    <!-- Filters -->
                    <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                        <!-- Sector Filter (only for non-Rukovodilac) -->
                        <div v-if="!user.isRukovodilac" class="w-full md:w-64">
                            <select
                                v-model="sectorFilter"
                                @change="handleFilterChange"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Сви сектори</option>
                                <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                                    {{ sector.sector }}
                                </option>
                            </select>
                        </div>

                        <!-- Location Filter -->
                        <div class="w-full md:w-48">
                            <select
                                v-model="locationFilter"
                                @change="handleFilterChange"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Све локације</option>
                                <option value="office">🏢 Канцеларија</option>
                                <option value="remote">🏠 Удаљено</option>
                            </select>
                        </div>

                        <!-- Search -->
                        <div class="w-full md:w-96">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Претражи по имену, презимену или email-у..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                @input="handleSearch"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden">
                <table class="w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="w-[260px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Корисник
                            </th>
                            <th scope="col" class="w-[280px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Email
                            </th>
                            <th scope="col" class="w-[220px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Сектор
                            </th>
                            <th scope="col" class="w-[160px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Статус
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="users.data.length === 0">
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                <svg class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="font-medium">Нема корисника</p>
                                <p class="text-xs mt-1">Покушајте другу претрагу</p>
                            </td>
                        </tr>
                        <tr v-for="user in users.data" :key="user.UserID" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center min-w-0">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ getUserInitials(user) }}
                                    </div>
                                    <div class="ml-4 min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 truncate" :title="`${user.FirstName} ${user.LastName}`">
                                            {{ user.FirstName }} {{ user.LastName }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 truncate" :title="user.Email">{{ user.Email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 truncate" :title="user.sector?.sector || 'Без сектора'">
                                    {{ user.sector?.sector || 'Без сектора' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        getStatusBadgeClass(user.current_status)
                                    ]"
                                >
                                    <svg
                                        :class="[
                                            'mr-1.5 h-2 w-2',
                                            user.current_status === 'Prijavljen' ? 'animate-pulse' : ''
                                        ]"
                                        fill="currentColor"
                                        viewBox="0 0 8 8"
                                    >
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    {{ getStatusLabel(user.current_status) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Приказано {{ users.from || 0 }}-{{ users.to || 0 }} од {{ users.total }} корисника
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Previous Button -->
                    <button
                        @click="goToPage(users.current_page - 1)"
                        :disabled="users.current_page === 1"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Претходна
                    </button>

                    <!-- Page Numbers -->
                    <div class="flex space-x-1">
                        <button
                            v-for="page in paginationPages"
                            :key="page"
                            @click="goToPage(page)"
                            :disabled="page === users.current_page || page === '...'"
                            :class="[
                                'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                page === users.current_page
                                    ? 'bg-blue-600 text-white'
                                    : page === '...'
                                    ? 'text-gray-700 cursor-default'
                                    : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                            ]"
                        >
                            {{ page }}
                        </button>
                    </div>

                    <!-- Next Button -->
                    <button
                        @click="goToPage(users.current_page + 1)"
                        :disabled="users.current_page === users.last_page"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Следећа
                    </button>
                </div>
            </div>
        </div>

        <!-- Remote Users Modal -->
        <RemoteUsersModal
            v-if="showRemoteUsersModal"
            @close="closeRemoteUsersModal"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import RemoteUsersModal from '@/Components/RemoteUsersModal.vue';
import { isOfficeIp, isRemoteIp } from '@/Utils/locationHelper';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    users: {
        type: Object,
        required: true,
    },
    sectors: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref(props.filters.search || '');
const sectorFilter = ref(props.filters.sector || '');
const locationFilter = ref(props.filters.location || '');
const showRemoteUsersModal = ref(false);

const checkedInPercentage = computed(() => {
    if (props.stats.total_users === 0) return 0;
    return Math.round((props.stats.checked_in / props.stats.total_users) * 100);
});

const onLeavePercentage = computed(() => {
    if (props.stats.total_users === 0) return 0;
    return Math.round((props.stats.on_leave / props.stats.total_users) * 100);
});

const getUserInitials = (user) => {
    if (!user) return '?';
    const first = user.FirstName?.[0] || '';
    const last = user.LastName?.[0] || '';
    return (first + last).toUpperCase();
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

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/reports', {
            search: searchQuery.value || undefined,
            sector: sectorFilter.value || undefined,
            location: locationFilter.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

const handleFilterChange = () => {
    router.get('/reports', {
        search: searchQuery.value || undefined,
        sector: sectorFilter.value || undefined,
        location: locationFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const goToPage = (page) => {
    if (page === '...') return;

    router.get('/reports', {
        page: page,
        search: searchQuery.value || undefined,
        sector: sectorFilter.value || undefined,
        location: locationFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Remote Users Modal
const openRemoteUsersModal = () => {
    showRemoteUsersModal.value = true;
};

const closeRemoteUsersModal = () => {
    showRemoteUsersModal.value = false;
};

// Keyboard shortcut: Ctrl+Alt+R to open Remote Users Modal
const handleKeyPress = (event) => {
    if (event.ctrlKey && event.altKey && event.key === 'r') {
        event.preventDefault();
        openRemoteUsersModal();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyPress);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyPress);
});

// Pagination with boundary checks
const paginationPages = computed(() => {
    const current = props.users.current_page;
    const last = props.users.last_page;
    const pages = [];

    if (last <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        // Always show first page
        pages.push(1);

        if (current > 3) {
            pages.push('...');
        }

        // Show pages around current
        for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
            pages.push(i);
        }

        if (current < last - 2) {
            pages.push('...');
        }

        // Always show last page
        pages.push(last);
    }

    return pages;
});
</script>
