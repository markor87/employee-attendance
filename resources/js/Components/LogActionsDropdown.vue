<template>
    <div class="relative inline-block text-left">
        <!-- Dropdown Button -->
        <button
            @click="toggleDropdown"
            type="button"
            class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-md transition-colors border border-gray-300"
        >
            <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
            </svg>
            Акције
            <svg
                class="ml-1.5 h-4 w-4 transition-transform"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <teleport to="body">
            <div
                v-if="isOpen"
                ref="dropdown"
                class="fixed z-50 mt-2 w-56 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                :style="dropdownStyle"
            >
                <div class="py-1">
                    <!-- View Log -->
                    <button
                        @click="handleView"
                        type="button"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors flex items-center"
                    >
                        <svg class="h-5 w-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Преглед
                    </button>

                    <!-- Edit Log (only if canManage) -->
                    <button
                        v-if="canManage"
                        @click="handleEdit"
                        type="button"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors flex items-center"
                    >
                        <svg class="h-5 w-5 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Измени
                    </button>

                    <!-- Delete Log (only if canManage) -->
                    <button
                        v-if="canManage"
                        @click="handleDelete"
                        type="button"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors flex items-center"
                    >
                        <svg class="h-5 w-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Обриши
                    </button>
                </div>
            </div>
        </teleport>

        <!-- Backdrop (close dropdown when clicking outside) -->
        <div
            v-if="isOpen"
            @click="closeDropdown"
            class="fixed inset-0 z-40"
        ></div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    log: {
        type: Object,
        required: true,
    },
    canManage: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['view', 'edit', 'delete']);

const isOpen = ref(false);
const dropdown = ref(null);
const buttonRef = ref(null);

const dropdownStyle = ref({
    top: '0px',
    left: '0px',
});

const toggleDropdown = async (event) => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        buttonRef.value = event.currentTarget;
        await nextTick();
        positionDropdown();
    }
};

const closeDropdown = () => {
    isOpen.value = false;
};

const positionDropdown = () => {
    if (!buttonRef.value || !dropdown.value) return;

    const buttonRect = buttonRef.value.getBoundingClientRect();
    const dropdownHeight = dropdown.value.offsetHeight;
    const viewportHeight = window.innerHeight;

    // Default position: below the button
    let top = buttonRect.bottom + window.scrollY + 8;
    let left = buttonRect.left + window.scrollX;

    // If dropdown would go off bottom of screen, position above button
    if (buttonRect.bottom + dropdownHeight + 8 > viewportHeight) {
        top = buttonRect.top + window.scrollY - dropdownHeight - 8;
    }

    // If dropdown would go off right of screen, align to right edge of button
    const dropdownWidth = dropdown.value.offsetWidth;
    if (left + dropdownWidth > window.innerWidth) {
        left = buttonRect.right + window.scrollX - dropdownWidth;
    }

    dropdownStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
    };
};

const handleView = () => {
    emit('view', props.log);
    closeDropdown();
};

const handleEdit = () => {
    emit('edit', props.log);
    closeDropdown();
};

const handleDelete = () => {
    emit('delete', props.log);
    closeDropdown();
};

// Close dropdown on ESC key
const handleKeydown = (e) => {
    if (e.key === 'Escape' && isOpen.value) {
        closeDropdown();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('resize', closeDropdown);
    window.addEventListener('scroll', positionDropdown, true);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('resize', closeDropdown);
    window.removeEventListener('scroll', positionDropdown, true);
});
</script>
