<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Корисник</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Улога</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Акције</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr
                        v-for="user in users"
                        :key="user.UserID"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs">
                                    {{ (user.FirstName?.[0] || '') + (user.LastName?.[0] || '') }}
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ user.FirstName }} {{ user.LastName }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                            {{ user.Email }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <span
                                :class="[
                                    'px-2 py-1 rounded-full text-xs font-semibold',
                                    getRoleBadgeClass(user.Role)
                                ]"
                            >
                                {{ user.Role }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <span
                                :class="[
                                    'px-2 py-1 rounded-full text-xs font-semibold',
                                    user.Status === 'Prijavljen'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                ]"
                            >
                                {{ user.Status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <div class="flex flex-col space-y-1">
                                <!-- Edit Button -->
                                <button
                                    @click="$emit('edit', user)"
                                    :disabled="user.UserID === 1"
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-md transition-colors text-left',
                                        user.UserID === 1
                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            : 'bg-blue-100 text-blue-700 hover:bg-blue-200'
                                    ]"
                                    :title="user.UserID === 1 ? 'Не можете изменити SuperAdmin корисника' : 'Измени корисника'"
                                >
                                    Измени
                                </button>

                                <!-- Delete Button -->
                                <button
                                    @click="$emit('delete', user)"
                                    :disabled="user.UserID === 1"
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-md transition-colors text-left',
                                        user.UserID === 1
                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            : 'bg-red-100 text-red-700 hover:bg-red-200'
                                    ]"
                                    :title="user.UserID === 1 ? 'Не можете обрисати SuperAdmin корисника' : 'Обриши корисника'"
                                >
                                    Обриши
                                </button>

                                <!-- Force Password Change Button -->
                                <button
                                    @click="$emit('force-password-change', user)"
                                    :disabled="user.UserID === 1"
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-md transition-colors text-left',
                                        user.UserID === 1
                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            : 'bg-amber-100 text-amber-700 hover:bg-amber-200'
                                    ]"
                                    :title="user.UserID === 1 ? 'Не можете форсирати промену лозинке за SuperAdmin корисника' : 'Форсирај промену лозинке'"
                                >
                                    Промени лозинку
                                </button>
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
defineProps({
    users: {
        type: Array,
        required: true,
    },
});

defineEmits(['edit', 'delete', 'force-password-change']);

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
