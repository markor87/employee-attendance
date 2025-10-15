<template>
    <AppLayout :user="user">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ isOwnLogs ? 'Моји логови' : `Логови - ${viewingUser.FirstName} ${viewingUser.LastName}` }}
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Преглед евиденције радног времена
                    </p>
                </div>
                <a
                    href="/dashboard"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    ← Назад на почетну
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-end md:space-x-4 space-y-4 md:space-y-0">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Датум од</label>
                    <input
                        v-model="dateFilters.start_date"
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Датум до</label>
                    <input
                        v-model="dateFilters.end_date"
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div class="flex space-x-2">
                    <button
                        @click="applyFilters"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                        Примени
                    </button>
                    <button
                        @click="clearFilters"
                        class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                    >
                        Ресетуј
                    </button>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <!-- Table for desktop -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Датум</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Пријава</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Одјава</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Разлог пријаве</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Разлог одјаве</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Акције</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="logs.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <svg class="h-12 w-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-lg font-medium">Нема логова за приказ</p>
                                <p class="text-sm text-gray-400 mt-1">Изаберите други датумски опсег или се пријавите на посао</p>
                            </td>
                        </tr>
                        <tr v-for="log in logs.data" :key="log.LogID" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ formatDate(log.RadniDatum) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ formatTime(log.VremePrijave) }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span v-if="log.VremeOdjave" class="text-gray-900">{{ formatTime(log.VremeOdjave) }}</span>
                                <span v-else class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                    Активна
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ log.RazlogPrijave || '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ log.RazlogOdjave || '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <LogActionsDropdown
                                    :log="log"
                                    :canManage="canManageLog(log)"
                                    @view="openViewModal(log)"
                                    @edit="openEditModal(log)"
                                    @delete="openDeleteModal(log)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Cards for mobile -->
            <div class="md:hidden divide-y divide-gray-200">
                <div v-if="logs.data.length === 0" class="px-4 py-8 text-center text-gray-500">
                    <svg class="h-12 w-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-lg font-medium">Нема логова за приказ</p>
                    <p class="text-sm text-gray-400 mt-1">Изаберите други датумски опсег</p>
                </div>

                <div v-for="log in logs.data" :key="log.LogID" class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-semibold text-gray-900">{{ formatDate(log.RadniDatum) }}</p>
                            <p class="text-sm text-gray-600">{{ log.RazlogPrijave }}</p>
                        </div>
                        <span v-if="!log.VremeOdjave" class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                            Активна
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-600">Пријава:</span>
                            <span class="ml-1 text-gray-900 font-medium">{{ formatTime(log.VremePrijave) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Одјава:</span>
                            <span class="ml-1 text-gray-900 font-medium">{{ log.VremeOdjave ? formatTime(log.VremeOdjave) : '-' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-600">Трајање:</span>
                            <span class="ml-1 text-gray-900 font-medium">{{ calculateDuration(log.VremePrijave, log.VremeOdjave) }}</span>
                        </div>
                        <div v-if="log.Napomena" class="col-span-2">
                            <span class="text-gray-600">Напомена:</span>
                            <p class="text-gray-900 text-xs mt-1">{{ log.Napomena }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Приказано {{ logs.from || 0 }}-{{ logs.to || 0 }} од {{ logs.total }} логова
                </div>
                <div class="flex space-x-2">
                    <button
                        v-for="page in paginationPages"
                        :key="page"
                        @click="goToPage(page)"
                        :disabled="page === logs.current_page || page === '...'"
                        :class="[
                            'px-3 py-1 text-sm font-medium rounded-lg transition-colors',
                            page === logs.current_page
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

        <!-- View Log Modal -->
        <ViewLogModal
            v-if="showViewModal"
            :log="selectedLog"
            :users="allUsers"
            @close="closeViewModal"
        />

        <!-- Edit Log Modal -->
        <EditLogModal
            v-if="showEditModal"
            :log="selectedLog"
            :reasons="adminReasons"
            @close="closeEditModal"
            @submit="handleEditSubmit"
        />

        <!-- Delete Log Confirm Modal -->
        <DeleteLogConfirmModal
            v-if="showDeleteModal"
            :log="selectedLog"
            @close="closeDeleteModal"
            @confirm="handleDeleteConfirm"
        />

        <!-- Remote Logs Modal -->
        <RemoteLogsModal
            v-if="showRemoteLogsModal"
            :logs="logs"
            :userName="`${viewingUser.FirstName} ${viewingUser.LastName}`"
            @close="closeRemoteLogsModal"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import LogActionsDropdown from '@/Components/LogActionsDropdown.vue';
import ViewLogModal from '@/Components/ViewLogModal.vue';
import EditLogModal from '@/Components/EditLogModal.vue';
import DeleteLogConfirmModal from '@/Components/DeleteLogConfirmModal.vue';
import RemoteLogsModal from '@/Components/RemoteLogsModal.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    viewingUser: {
        type: Object,
        required: true,
    },
    logs: {
        type: Object,
        required: true,
    },
    statistics: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    isOwnLogs: {
        type: Boolean,
        default: true,
    },
    allUsers: {
        type: Array,
        default: () => [],
    },
});

const toast = useToast();

const dateFilters = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

// Modal state
const showViewModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showRemoteLogsModal = ref(false);
const selectedLog = ref(null);
const adminReasons = ref([]);

// Load admin reasons on mount
onMounted(async () => {
    try {
        const response = await fetch('/attendance/admin/reasons');
        const data = await response.json();
        if (data.success) {
            adminReasons.value = data.data || [];
        }
    } catch (error) {
        console.error('Failed to load reasons:', error);
    }

    // Add keyboard listener for Ctrl+Alt+R
    window.addEventListener('keydown', handleKeyPress);
});

const formatDate = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('sr-RS', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatTime = (dateTime) => {
    if (!dateTime) return '-';
    const d = new Date(dateTime);
    return d.toLocaleTimeString('sr-RS', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const calculateDuration = (checkIn, checkOut) => {
    if (!checkOut) return '-';

    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const diff = end - start;

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    return `${hours}h ${minutes}m`;
};

const applyFilters = () => {
    router.get(`/logs/${props.viewingUser.UserID}`, {
        start_date: dateFilters.value.start_date || undefined,
        end_date: dateFilters.value.end_date || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    dateFilters.value.start_date = '';
    dateFilters.value.end_date = '';
    router.get(`/logs/${props.viewingUser.UserID}`, {}, {
        preserveState: false,
    });
};

const goToPage = (page) => {
    if (page === '...') return;

    router.get(`/logs/${props.viewingUser.UserID}`, {
        page: page,
        start_date: dateFilters.value.start_date || undefined,
        end_date: dateFilters.value.end_date || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Pagination with boundary checks
const paginationPages = computed(() => {
    const current = props.logs.current_page;
    const last = props.logs.last_page;
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

// Check if current user can manage (edit/delete) a log
const canManageLog = (log) => {
    // Log must be completed (have VremeOdjave)
    if (!log.VremeOdjave) return false;

    // Log must not be expired (VremeOdjave must be in the future)
    const checkOutTime = new Date(log.VremeOdjave);
    const now = new Date();
    if (checkOutTime < now) return false;

    // Current user must be the creator of the log
    if (log.PerformedByPrijava !== props.user.UserID) return false;

    return true;
};

// Open view modal
const openViewModal = (log) => {
    selectedLog.value = log;
    showViewModal.value = true;
};

// Close view modal
const closeViewModal = () => {
    showViewModal.value = false;
    selectedLog.value = null;
};

// Open edit modal
const openEditModal = (log) => {
    selectedLog.value = log;
    showEditModal.value = true;
};

// Close edit modal
const closeEditModal = () => {
    showEditModal.value = false;
    selectedLog.value = null;
};

// Handle edit submit
const handleEditSubmit = async (formData) => {
    try {
        const response = await fetch(`/attendance/logs/${selectedLog.value.LogID}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(formData),
        });

        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }

        const result = await response.json();

        if (response.ok && result.success) {
            toast.success('Лог је успешно ажуриран!');
            closeEditModal();
            // Reload logs
            router.reload({ preserveScroll: true });
        } else {
            // Handle validation errors
            if (result.errors) {
                const errorMessages = Object.values(result.errors).flat().join(', ');
                toast.error(errorMessages);
            } else {
                toast.error(result.message || 'Грешка при ажурирању лога');
            }
        }
    } catch (error) {
        console.error('Failed to update log:', error);
        toast.error('Дошло је до грешке приликом ажурирања лога');
    }
};

// Open delete modal
const openDeleteModal = (log) => {
    selectedLog.value = log;
    showDeleteModal.value = true;
};

// Close delete modal
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedLog.value = null;
};

// Remote Logs Modal
const openRemoteLogsModal = () => {
    showRemoteLogsModal.value = true;
};

const closeRemoteLogsModal = () => {
    showRemoteLogsModal.value = false;
};

// Keyboard shortcut: Ctrl+Alt+R to open Remote Logs Modal
const handleKeyPress = (event) => {
    if (event.ctrlKey && event.altKey && event.key === 'r') {
        event.preventDefault();
        openRemoteLogsModal();
    }
};

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyPress);
});

// Handle delete confirm
const handleDeleteConfirm = async () => {
    try {
        const response = await fetch(`/attendance/logs/${selectedLog.value.LogID}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const result = await response.json();

        if (result.success) {
            toast.success('Лог је успешно обрисан!');
            closeDeleteModal();
            // Reload logs
            router.reload({ preserveScroll: true });
        } else {
            toast.error(result.message || 'Грешка при брисању лога');
        }
    } catch (error) {
        console.error('Failed to delete log:', error);
        toast.error('Дошло је до грешке приликом брисања лога');
    }
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
