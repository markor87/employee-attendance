<template>
    <AppLayout :user="user" :laravel-version="laravelVersion">
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

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Сви корисници</h3>
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

            <div class="overflow-hidden">
                <table class="w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="w-[280px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Корисник
                            </th>
                            <th scope="col" class="w-[300px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Email
                            </th>
                            <th scope="col" class="w-[140px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Статус
                            </th>
                            <th scope="col" class="w-[200px] px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Акције
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
                                <span
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        user.Status === 'Prijavljen'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    ]"
                                >
                                    <svg
                                        :class="[
                                            'mr-1.5 h-2 w-2',
                                            user.Status === 'Prijavljen' ? 'animate-pulse' : ''
                                        ]"
                                        fill="currentColor"
                                        viewBox="0 0 8 8"
                                    >
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    {{ user.Status === 'Prijavljen' ? 'Пријављен' : 'Одјављен' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1.5">
                                    <UserActionsDropdown
                                        :user="user"
                                        @forceCheckIn="handleForceCheckIn"
                                        @forceCheckOut="handleForceCheckOut"
                                    />
                                    <a
                                        :href="`/logs/${user.UserID}`"
                                        class="inline-flex items-center px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition-colors flex-shrink-0"
                                    >
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Логови
                                    </a>
                                </div>
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
                <div class="flex space-x-2">
                    <button
                        v-for="page in paginationPages"
                        :key="page"
                        @click="goToPage(page)"
                        :disabled="page === users.current_page || page === '...'"
                        :class="[
                            'px-3 py-1 text-sm font-medium rounded-lg transition-colors',
                            page === users.current_page
                                ? 'bg-blue-600 text-white'
                                : page === '...'
                                ? 'text-gray-400 cursor-default'
                                : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        {{ page }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Force Check-In Modal -->
        <ForceCheckInModal
            v-if="selectedUser"
            :show="showForceCheckInModal"
            :user="selectedUser"
            :checkInReasons="checkInReasons"
            @close="showForceCheckInModal = false"
            @submit="submitForceCheckIn"
        />

        <!-- Force Check-Out Modal -->
        <ForceCheckOutModal
            v-if="selectedUser"
            :show="showForceCheckOutModal"
            :user="selectedUser"
            :activeLog="selectedUserActiveLog"
            :checkOutReasons="checkOutReasons"
            @close="showForceCheckOutModal = false"
            @submit="submitForceCheckOut"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import UserActionsDropdown from '@/Components/UserActionsDropdown.vue';
import ForceCheckInModal from '@/Components/ForceCheckInModal.vue';
import ForceCheckOutModal from '@/Components/ForceCheckOutModal.vue';
import { useAttendance } from '@/composables/useAttendance';

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
    filters: {
        type: Object,
        default: () => ({}),
    },
    laravelVersion: {
        type: String,
        default: '11.x',
    },
});

const searchQuery = ref(props.filters.search || '');

// Attendance composable
const { forceCheckIn, forceCheckOut, getReasons } = useAttendance();

// Modal state
const showForceCheckInModal = ref(false);
const showForceCheckOutModal = ref(false);
const selectedUser = ref(null);
const selectedUserActiveLog = ref(null);

// Reasons data
const checkInReasons = ref([]);
const checkOutReasons = ref([]);

// Load reasons on mount
onMounted(async () => {
    try {
        const reasons = await getReasons();
        checkInReasons.value = reasons.checkIn || [];
        checkOutReasons.value = reasons.checkOut || [];
    } catch (error) {
        console.error('Failed to load reasons:', error);
    }
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

const getRoleBadgeClass = (role) => {
    const classes = {
        'SuperAdmin': 'bg-red-100 text-red-800',
        'Admin': 'bg-purple-100 text-purple-800',
        'Kadrovik': 'bg-blue-100 text-blue-800',
        'Zaposleni': 'bg-gray-100 text-gray-800',
    };
    return classes[role] || 'bg-gray-100 text-gray-800';
};

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/dashboard', {
            search: searchQuery.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

const goToPage = (page) => {
    if (page === '...') return;

    router.get('/admin/dashboard', {
        page: page,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

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

// Force check-in handler
const handleForceCheckIn = async (user) => {
    selectedUser.value = user;
    showForceCheckInModal.value = true;
};

// Force check-out handler
const handleForceCheckOut = async (user) => {
    selectedUser.value = user;

    // Find active log for this user from loaded data
    const userData = props.users.data.find(u => u.UserID === user.UserID);
    selectedUserActiveLog.value = userData?.active_time_log || null;

    showForceCheckOutModal.value = true;
};

// Submit force check-in
const submitForceCheckIn = async (data) => {
    try {
        await forceCheckIn(data);
        showForceCheckInModal.value = false;

        // Reload page to reflect changes
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('Force check-in failed:', error);
    }
};

// Submit force check-out
const submitForceCheckOut = async (data) => {
    try {
        await forceCheckOut(data);
        showForceCheckOutModal.value = false;

        // Reload page to reflect changes
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('Force check-out failed:', error);
    }
};
</script>
