<template>
    <AppLayout :user="user" :laravel-version="laravelVersion">
        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row gap-4 items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Сви корисници</h3>
                    <!-- Search -->
                    <div class="flex-1 md:max-w-md">
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
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1.5">
                                    <UserActionsDropdown
                                        :user="user"
                                        @scheduleEntry="handleScheduleEntry"
                                    />
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

        <!-- Admin Schedule Entry Modal -->
        <AdminScheduleEntryModal
            v-if="selectedUser"
            :show="showScheduleEntryModal"
            :user="selectedUser"
            :adminReasons="adminReasons"
            @close="showScheduleEntryModal = false"
            @submit="submitScheduleEntry"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import UserActionsDropdown from '@/Components/UserActionsDropdown.vue';
import AdminScheduleEntryModal from '@/Components/AdminScheduleEntryModal.vue';
import { isOfficeIp, isRemoteIp } from '@/utils/locationHelper';

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

// Toast notifications
const toast = useToast();

// Computed - Statistics
const checkedInPercentage = computed(() => {
    if (props.stats.total_users === 0) return 0;
    return Math.round((props.stats.checked_in / props.stats.total_users) * 100);
});

// Computed - Location Statistics
const locationStats = computed(() => {
    let office = 0;
    let remote = 0;

    props.users.data.forEach(user => {
        // Only count users who are currently checked in (Prijavljen)
        if (user.current_status === 'Prijavljen' && user.activeTimeLog) {
            const ip = user.activeTimeLog.IpAdresaPrijave;
            if (ip) {
                if (isOfficeIp(ip)) {
                    office++;
                } else if (isRemoteIp(ip)) {
                    remote++;
                }
            }
        }
    });

    return { office, remote };
});

// Modal state
const showScheduleEntryModal = ref(false);
const selectedUser = ref(null);

// Reasons data
const adminReasons = ref([]);

// Load reasons on mount
onMounted(async () => {
    try {
        // Load admin reasons (excludes "Dolazak na posao")
        const adminReasonsResponse = await window.axios.get('/attendance/admin/reasons');
        const adminReasonsData = adminReasonsResponse.data;
        if (adminReasonsData.success) {
            adminReasons.value = adminReasonsData.data || [];
        }
    } catch (error) {
        console.error('Failed to load reasons:', error);
    }
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

// Schedule entry handler
const handleScheduleEntry = async (user) => {
    selectedUser.value = user;
    showScheduleEntryModal.value = true;
};

// Submit schedule entry
const submitScheduleEntry = async (data) => {
    try {
        const response = await window.axios.post('/attendance/admin/schedule-entry', data, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const result = response.data;

        if (result.success) {
            toast.success('Одсуство је успешно евидентирано!');
            showScheduleEntryModal.value = false;
            // Reload page to reflect changes
            router.reload({ preserveScroll: true });
        } else {
            console.error('Schedule entry failed:', result.message);
            toast.error(result.message || 'Грешка при евидентирању одсуства');
        }
    } catch (error) {
        console.error('Schedule entry failed:', error);
        // Handle validation errors (422)
        if (error.response && error.response.status === 422) {
            toast.error(error.response.data.message || 'Валидација није успела.');
        } else {
            toast.error('Дошло је до грешке приликом евидентирања');
        }
    }
};
</script>
