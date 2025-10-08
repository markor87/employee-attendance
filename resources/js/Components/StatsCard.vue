<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 mb-1">{{ title }}</p>
                <p class="text-3xl font-bold text-gray-900">{{ formattedValue }}</p>

                <!-- Change Indicator (Optional) -->
                <div v-if="change !== null" class="mt-2 flex items-center space-x-1">
                    <svg
                        v-if="change > 0"
                        class="h-4 w-4 text-green-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                    <svg
                        v-else-if="change < 0"
                        class="h-4 w-4 text-red-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                    <span
                        :class="[
                            'text-sm font-medium',
                            change > 0 ? 'text-green-600' : change < 0 ? 'text-red-600' : 'text-gray-500'
                        ]"
                    >
                        {{ Math.abs(change) }}%
                    </span>
                    <span class="text-xs text-gray-500">vs. прошле недеље</span>
                </div>

                <!-- Subtitle (Optional) -->
                <p v-if="subtitle" class="mt-2 text-xs text-gray-500">{{ subtitle }}</p>
            </div>

            <!-- Icon -->
            <div
                :class="[
                    'flex-shrink-0 h-12 w-12 rounded-lg flex items-center justify-center',
                    iconBgClass
                ]"
            >
                <svg
                    :class="['h-6 w-6', iconColorClass]"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath"></path>
                </svg>
            </div>
        </div>

        <!-- Additional Info (Optional) -->
        <div v-if="$slots.footer" class="mt-4 pt-4 border-t border-gray-200">
            <slot name="footer"></slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
    subtitle: {
        type: String,
        default: null,
    },
    change: {
        type: Number,
        default: null,
    },
    icon: {
        type: String,
        default: 'chart',
        validator: (value) => ['chart', 'users', 'clock', 'check', 'activity'].includes(value),
    },
    color: {
        type: String,
        default: 'blue',
        validator: (value) => ['blue', 'green', 'purple', 'orange', 'red'].includes(value),
    },
});

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString('sr-RS');
    }
    return props.value;
});

const iconBgClass = computed(() => {
    const colors = {
        blue: 'bg-blue-100',
        green: 'bg-green-100',
        purple: 'bg-purple-100',
        orange: 'bg-orange-100',
        red: 'bg-red-100',
    };
    return colors[props.color] || colors.blue;
});

const iconColorClass = computed(() => {
    const colors = {
        blue: 'text-blue-600',
        green: 'text-green-600',
        purple: 'text-purple-600',
        orange: 'text-orange-600',
        red: 'text-red-600',
    };
    return colors[props.color] || colors.blue;
});

const iconPath = computed(() => {
    const paths = {
        chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        activity: 'M13 10V3L4 14h7v7l9-11h-7z',
    };
    return paths[props.icon] || paths.chart;
});
</script>
