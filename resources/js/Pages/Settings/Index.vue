<template>
    <AppLayout :user="$page.props.auth.user" :laravel-version="laravelVersion">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Подешавања
            </h1>
            <p class="text-gray-600">
                Конфигуришите систем према вашим потребама.
            </p>
        </div>

        <!-- Settings Layout -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar Navigation -->
            <div class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <nav class="space-y-2">
                        <button
                            v-for="section in sections"
                            :key="section.id"
                            @click="activeSection = section.id"
                            :class="[
                                'w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center space-x-3',
                                activeSection === section.id
                                    ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md'
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]"
                        >
                            <span class="text-xl">{{ section.icon }}</span>
                            <span class="font-medium">{{ section.label }}</span>
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Content Panel -->
            <div class="flex-1">
                <component
                    :is="activeComponent"
                    :settings="settings"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecurityPanel from '@/Components/Settings/SecurityPanel.vue';
import AutoLogoutPanel from '@/Components/Settings/AutoLogoutPanel.vue';
import EmailPanel from '@/Components/Settings/EmailPanel.vue';
import ReminderPanel from '@/Components/Settings/ReminderPanel.vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    laravelVersion: {
        type: String,
        default: '11.x',
    },
});

const sections = [
    {
        id: 'security',
        label: 'Безбедност',
        icon: '🔒',
        component: SecurityPanel,
    },
    {
        id: 'autologout',
        label: 'Аутоматски Logout',
        icon: '⏰',
        component: AutoLogoutPanel,
    },
    {
        id: 'email',
        label: 'Email Подешавања',
        icon: '📧',
        component: EmailPanel,
    },
    {
        id: 'reminders',
        label: 'Email Подсетници',
        icon: '🔔',
        component: ReminderPanel,
    },
];

const activeSection = ref('security');

const activeComponent = computed(() => {
    const section = sections.find(s => s.id === activeSection.value);
    return section ? section.component : SecurityPanel;
});
</script>
