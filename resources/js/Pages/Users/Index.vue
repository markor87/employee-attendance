<template>
    <AppLayout :user="$page.props.auth.user" :laravel-version="laravelVersion">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Управљање корисницима</h1>
            <p class="text-gray-600 mt-1">Креирајте, измените или обришите кориснике</p>
        </div>

        <!-- Toolbar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <!-- Search Input -->
                <div class="flex-1 w-full md:w-auto">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Претражи по имену, презимену или email-у..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        @input="handleSearch"
                    />
                </div>

                <!-- Role Filter -->
                <select
                    v-model="roleFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    @change="handleFilter"
                >
                    <option value="">Све улоге</option>
                    <option value="SuperAdmin">SuperAdmin</option>
                    <option value="Admin">Admin</option>
                    <option value="Kadrovik">Kadrovik</option>
                    <option value="Zaposleni">Запослени</option>
                </select>

                <!-- Status Filter -->
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    @change="handleFilter"
                >
                    <option value="">Сви статуси</option>
                    <option value="Prijavljen">Пријављен</option>
                    <option value="Odjavljen">Одјављен</option>
                </select>

                <!-- Create User Button -->
                <button
                    @click="openCreateModal"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200"
                >
                    + Нови корисник
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <UserTable
            :users="users.data"
            @view="openViewModal"
            @edit="openEditModal"
            @delete="openDeleteModal"
            @force-password-change="forcePasswordChange"
        />

        <!-- Pagination -->
        <Pagination
            v-if="users.data.length > 0"
            :current="users.current_page"
            :total="users.last_page"
            :from="users.from"
            :to="users.to"
            :total-records="users.total"
            @page-change="goToPage"
        />

        <!-- View User Modal -->
        <UserViewModal
            v-if="showViewModal"
            :user-id="selectedUserId"
            @close="closeViewModal"
        />

        <!-- User Modal (Create/Edit) -->
        <UserModal
            v-if="showUserModal"
            :user="selectedUser"
            :is-edit="isEditMode"
            :sectors="sectors"
            @close="closeUserModal"
            @save="saveUser"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmModal
            v-if="showDeleteModal"
            :user="selectedUser"
            @close="closeDeleteModal"
            @confirm="deleteUser"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';
import UserTable from '@/Components/UserTable.vue';
import Pagination from '@/Components/Pagination.vue';
import UserModal from '@/Components/UserModal.vue';
import UserViewModal from '@/Components/UserViewModal.vue';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal.vue';

const props = defineProps({
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
    laravelVersion: {
        type: String,
        default: '11.x',
    },
});

const toast = useToast();

// Search and filter state
const searchQuery = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const statusFilter = ref(props.filters.status || '');

// Modal state
const showViewModal = ref(false);
const showUserModal = ref(false);
const showDeleteModal = ref(false);
const selectedUser = ref(null);
const selectedUserId = ref(null);
const isEditMode = ref(false);

// Debounced search
let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/users', {
            search: searchQuery.value,
            role: roleFilter.value,
            status: statusFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

// Handle filter change
const handleFilter = () => {
    router.get('/users', {
        search: searchQuery.value,
        role: roleFilter.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Pagination
const goToPage = (page) => {
    router.get('/users', {
        page: page,
        search: searchQuery.value,
        role: roleFilter.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// View user modal
const openViewModal = (user) => {
    selectedUserId.value = user.UserID;
    showViewModal.value = true;
};

// Close view modal
const closeViewModal = () => {
    showViewModal.value = false;
    selectedUserId.value = null;
};

// Create user modal
const openCreateModal = () => {
    selectedUser.value = null;
    isEditMode.value = false;
    showUserModal.value = true;
};

// Edit user modal
const openEditModal = (user) => {
    selectedUser.value = { ...user };
    isEditMode.value = true;
    showUserModal.value = true;
};

// Close user modal
const closeUserModal = () => {
    showUserModal.value = false;
    selectedUser.value = null;
};

// Save user (create or update)
const saveUser = (userData) => {
    if (isEditMode.value) {
        // Update user
        router.put(`/users/${selectedUser.value.UserID}`, userData, {
            onSuccess: () => {
                closeUserModal();
                toast.success('Корисник је успешно ажуриран.');
            },
            onError: (errors) => {
                if (errors.error) {
                    toast.error(errors.error);
                } else {
                    toast.error('Дошло је до грешке приликом ажурирања корисника.');
                }
            },
        });
    } else {
        // Create user
        router.post('/users', userData, {
            onSuccess: () => {
                closeUserModal();
                toast.success('Корисник је успешно креиран.');
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors).flat().join(' ');
                toast.error(errorMessage || 'Дошло је до грешке приликом креирања корисника.');
            },
        });
    }
};

// Delete user modal
const openDeleteModal = (user) => {
    selectedUser.value = user;
    showDeleteModal.value = true;
};

// Close delete modal
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedUser.value = null;
};

// Delete user
const deleteUser = () => {
    router.delete(`/users/${selectedUser.value.UserID}`, {
        onSuccess: () => {
            closeDeleteModal();
            toast.success('Корисник је успешно обрисан.');
        },
        onError: (errors) => {
            closeDeleteModal();
            if (errors.error) {
                toast.error(errors.error);
            } else {
                toast.error('Дошло је до грешке приликом брисања корисника.');
            }
        },
    });
};

// Force password change
const forcePasswordChange = (user) => {
    router.post(`/users/${user.UserID}/force-password-change`, {}, {
        onSuccess: () => {
            toast.success('Корисник ће морати да промени лозинку при следећем логовању.');
        },
        onError: (errors) => {
            if (errors.error) {
                toast.error(errors.error);
            } else {
                toast.error('Дошло је до грешке.');
            }
        },
    });
};
</script>
