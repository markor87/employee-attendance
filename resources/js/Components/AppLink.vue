<template>
    <Link :href="prefixedHref" v-bind="$attrs">
        <slot />
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
});

// Get base path from environment variable (defaults to '/' for local development)
const basePath = import.meta.env.VITE_BASE_PATH || '/';

const prefixedHref = computed(() => {
    // If base path is root, no need to prefix
    if (basePath === '/') {
        return props.href;
    }

    // If href already starts with base path, return as is
    if (props.href.startsWith(basePath)) {
        return props.href;
    }

    // If href starts with /, prepend base path
    if (props.href.startsWith('/')) {
        return basePath + props.href;
    }

    // Otherwise return as is (external links, etc)
    return props.href;
});
</script>
