<template>
    <teleport to="body">
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 rounded-t-xl">
                    <h2 class="text-2xl font-bold text-white">
                        {{ isEdit ? 'Измена корисника' : 'Нови корисник' }}
                    </h2>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                    <!-- First Name -->
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-gray-700 mb-1">
                            Име *
                        </label>
                        <input
                            id="firstName"
                            v-model="form.FirstName"
                            type="text"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Унесите име"
                        />
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="lastName" class="block text-sm font-medium text-gray-700 mb-1">
                            Презиме *
                        </label>
                        <input
                            id="lastName"
                            v-model="form.LastName"
                            type="text"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Унесите презиме"
                        />
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email *
                        </label>
                        <input
                            id="email"
                            v-model="form.Email"
                            type="email"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="primer@email.com"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Лозинка {{ isEdit ? '' : '*' }}
                        </label>
                        <input
                            id="password"
                            v-model="form.Password"
                            type="password"
                            :required="!isEdit"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :placeholder="isEdit ? 'Оставите празно ако не желите да промените' : 'Минимум 8 карактера, 1 број, 1 специјални карактер'"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            {{ isEdit ? 'Оставите празно ако не желите да промените лозинку' : 'Минимум 8 карактера, најмање 1 број и 1 специјални карактер' }}
                        </p>

                        <!-- Password Strength Indicator -->
                        <div v-if="form.Password" class="mt-2">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    :class="[
                                        'h-full transition-all duration-300',
                                        passwordStrength.class
                                    ]"
                                    :style="{ width: passwordStrength.width }"
                                ></div>
                            </div>
                            <p class="text-xs mt-1" :class="passwordStrength.textClass">
                                {{ passwordStrength.label }}
                            </p>
                        </div>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                            Улога *
                        </label>
                        <select
                            id="role"
                            v-model="form.Role"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Изаберите улогу</option>
                            <option value="SuperAdmin">СуперАдмин</option>
                            <option value="Admin">Админ</option>
                            <option value="Kadrovik">Кадровик</option>
                            <option value="Zaposleni">Запослени</option>
                            <option value="Rukovodilac">Руководилац</option>
                        </select>
                    </div>

                    <!-- Sector -->
                    <div>
                        <label for="sector" class="block text-sm font-medium text-gray-700 mb-1">
                            Сектор
                        </label>
                        <select
                            id="sector"
                            v-model="form.sector_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Без сектора</option>
                            <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                                {{ sector.sector }}
                            </option>
                        </select>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Откажи
                        </button>
                        <button
                            type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200"
                        >
                            {{ isEdit ? 'Сачувај измене' : 'Креирај корисника' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
    sectors: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'save']);

// Form data
const form = ref({
    FirstName: props.user?.FirstName || '',
    LastName: props.user?.LastName || '',
    Email: props.user?.Email || '',
    Password: '',
    Role: props.user?.Role || '',
    sector_id: props.user?.sector_id || '',
});

// Password strength calculation
const passwordStrength = computed(() => {
    const password = form.value.Password;
    if (!password) return { width: '0%', class: 'bg-gray-300', label: '', textClass: 'text-gray-500' };

    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[!@#$%^&*()_+\-=\[\]{};:'"|,.<>\/?]/.test(password)) strength++;

    if (strength === 1) {
        return { width: '25%', class: 'bg-red-500', label: 'Слаба', textClass: 'text-red-600' };
    } else if (strength === 2) {
        return { width: '50%', class: 'bg-orange-500', label: 'Средња', textClass: 'text-orange-600' };
    } else if (strength === 3) {
        return { width: '75%', class: 'bg-yellow-500', label: 'Добра', textClass: 'text-yellow-600' };
    } else if (strength === 4) {
        return { width: '100%', class: 'bg-green-500', label: 'Јака', textClass: 'text-green-600' };
    }

    return { width: '0%', class: 'bg-gray-300', label: '', textClass: 'text-gray-500' };
});

// Handle form submission
const handleSubmit = () => {
    emit('save', form.value);
};
</script>
