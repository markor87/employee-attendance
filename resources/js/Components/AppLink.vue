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

const basePath = '/employee-attendance';

const prefixedHref = computed(() => {
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
