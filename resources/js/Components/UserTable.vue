<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-hidden">
            <table class="w-full table-fixed divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="w-[250px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Корисник</th>
                        <th scope="col" class="w-[300px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Email</th>
                        <th scope="col" class="w-[150px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Улога</th>
                        <th scope="col" class="w-[150px] px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Акције</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="user in users"
                        :key="user.UserID"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center min-w-0">
                                <div class="h-10 w-10 flex-shrink-0 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-semibold text-sm">
                                    {{ (user.FirstName?.[0] || '') + (user.LastName?.[0] || '') }}
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
                                    getRoleBadgeClass(user.Role)
                                ]"
                            >
                                {{ user.Role }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end">
                                <UserCrudActionsDropdown
                                    :user="user"
                                    @view="$emit('view', user)"
                                    @edit="$emit('edit', user)"
                                    @delete="$emit('delete', user)"
                                    @force-password-change="$emit('force-password-change', user)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-if="users.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Нема корисника</h3>
            <p class="mt-1 text-sm text-gray-500">Почните са креирањем новог корисника.</p>
        </div>
    </div>
</template>

<script setup>
import UserCrudActionsDropdown from './UserCrudActionsDropdown.vue';

defineProps({
    users: {
        type: Array,
        required: true,
    },
});

defineEmits(['view', 'edit', 'delete', 'force-password-change']);

const getRoleBadgeClass = (role) => {
    const classes = {
        'SuperAdmin': 'bg-purple-100 text-purple-800',
        'Admin': 'bg-blue-100 text-blue-800',
        'Kadrovik': 'bg-indigo-100 text-indigo-800',
        'Zaposleni': 'bg-gray-100 text-gray-800',
    };
    return classes[role] || 'bg-gray-100 text-gray-800';
};
</script>
