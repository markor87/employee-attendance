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
                    <!-- Schedule Entry (Evidentiranje odsustva) -->
                    <button
                        @click="handleScheduleEntry"
                        type="button"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors flex items-center"
                    >
                        <svg class="h-5 w-5 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Евидентирај одсуство
                    </button>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 my-1"></div>

                    <!-- View Logs -->
                    <AppLink
                        :href="`/logs/${user.UserID}`"
                        class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors flex items-center"
                    >
                        <svg class="h-5 w-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Прегледај логове
                    </AppLink>
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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['scheduleEntry']);

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

const handleScheduleEntry = () => {
    emit('scheduleEntry', props.user);
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
