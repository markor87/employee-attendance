<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3 mt-6">
        <div class="flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0">
            <!-- Info Text -->
            <div class="text-sm text-gray-700">
                Приказ <span class="font-medium">{{ from }}</span> до <span class="font-medium">{{ to }}</span> од <span class="font-medium">{{ totalRecords }}</span> резултата
            </div>

            <!-- Pagination Controls -->
            <div class="flex items-center space-x-2">
                <!-- Previous Button -->
                <button
                    @click="goToPage(safeCurrent - 1)"
                    :disabled="safeCurrent === 1"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    Претходна
                </button>

                <!-- Page Numbers -->
                <div class="hidden sm:flex space-x-1">
                    <button
                        v-for="page in visiblePages"
                        :key="page"
                        @click="page !== '...' && goToPage(page)"
                        :class="[
                            'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                            page === safeCurrent
                                ? 'bg-blue-600 text-white'
                                : page === '...'
                                ? 'text-gray-700 cursor-default'
                                : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                        ]"
                        :disabled="page === '...'"
                    >
                        {{ page }}
                    </button>
                </div>

                <!-- Next Button -->
                <button
                    @click="goToPage(safeCurrent + 1)"
                    :disabled="safeCurrent === total"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    Следећа
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    current: {
        type: Number,
        required: true,
    },
    total: {
        type: Number,
        required: true,
    },
    from: {
        type: Number,
        required: true,
    },
    to: {
        type: Number,
        required: true,
    },
    totalRecords: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['page-change']);

// Safe current page (boundary check)
const safeCurrent = computed(() => {
    return Math.max(1, Math.min(props.current, props.total));
});

// Calculate visible page numbers
const visiblePages = computed(() => {
    const current = safeCurrent.value;
    const total = props.total;
    const delta = 2; // Number of pages to show on each side of current page

    const range = [];
    const rangeWithDots = [];
    let l;

    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
            range.push(i);
        }
    }

    for (let i of range) {
        if (l) {
            if (i - l === 2) {
                rangeWithDots.push(l + 1);
            } else if (i - l !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(i);
        l = i;
    }

    return rangeWithDots;
});

const goToPage = (page) => {
    if (page >= 1 && page <= props.total && page !== safeCurrent.value) {
        emit('page-change', page);
    }
};
</script>
