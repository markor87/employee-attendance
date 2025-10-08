<template>
    <div v-if="password" class="mt-2 space-y-2">
        <!-- Strength Bar -->
        <div class="flex space-x-1">
            <div
                v-for="i in 4"
                :key="i"
                class="h-1.5 flex-1 rounded-full transition-all"
                :class="i <= strength ? strengthColors[strength] : 'bg-gray-200'"
            ></div>
        </div>

        <!-- Strength Label -->
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium" :class="strengthTextColors[strength]">
                {{ strengthLabels[strength] }}
            </span>
            <span v-if="strength >= 3" class="text-green-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </span>
        </div>

        <!-- Requirements List -->
        <ul class="space-y-1 text-sm">
            <li class="flex items-center space-x-2" :class="requirements.length ? 'text-green-600' : 'text-gray-500'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="requirements.length" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    <circle v-else cx="12" cy="12" r="10" stroke-width="2"></circle>
                </svg>
                <span>Минимум 8 карактера</span>
            </li>
            <li class="flex items-center space-x-2" :class="requirements.hasDigit ? 'text-green-600' : 'text-gray-500'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="requirements.hasDigit" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    <circle v-else cx="12" cy="12" r="10" stroke-width="2"></circle>
                </svg>
                <span>Најмање један број</span>
            </li>
            <li class="flex items-center space-x-2" :class="requirements.hasSpecial ? 'text-green-600' : 'text-gray-500'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="requirements.hasSpecial" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    <circle v-else cx="12" cy="12" r="10" stroke-width="2"></circle>
                </svg>
                <span>Најмање један специјални карактер (@$!%*#?&)</span>
            </li>
            <li class="flex items-center space-x-2" :class="requirements.hasUpper ? 'text-green-600' : 'text-gray-500'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="requirements.hasUpper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    <circle v-else cx="12" cy="12" r="10" stroke-width="2"></circle>
                </svg>
                <span>Најмање једно велико слово (препоручено)</span>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    password: {
        type: String,
        required: true,
    },
});

const requirements = computed(() => ({
    length: props.password.length >= 8,
    hasDigit: /[0-9]/.test(props.password),
    hasSpecial: /[@$!%*#?&]/.test(props.password),
    hasUpper: /[A-Z]/.test(props.password),
    hasLower: /[a-z]/.test(props.password),
}));

const strength = computed(() => {
    let score = 0;

    if (requirements.value.length) score++;
    if (requirements.value.hasDigit) score++;
    if (requirements.value.hasSpecial) score++;
    if (requirements.value.hasUpper && requirements.value.hasLower) score++;

    return score;
});

const strengthLabels = {
    0: 'Веома слаба',
    1: 'Слаба',
    2: 'Средња',
    3: 'Јака',
    4: 'Веома јака',
};

const strengthColors = {
    0: 'bg-red-500',
    1: 'bg-orange-500',
    2: 'bg-yellow-500',
    3: 'bg-green-500',
    4: 'bg-green-600',
};

const strengthTextColors = {
    0: 'text-red-600',
    1: 'text-orange-600',
    2: 'text-yellow-600',
    3: 'text-green-600',
    4: 'text-green-700',
};
</script>
