import { ref, nextTick, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

export function useOvertimeCheck() {
    const showOvertimePrompt = ref(false);
    const overtimeMessage = ref('');
    const promptTimeout = ref(10); // minuta
    const timeRemaining = ref(0); // sekundi

    let checkInterval = null;
    let promptTimer = null;
    let countdownInterval = null;
    let flashInterval = null;

    // Originalni naslov
    let originalTitle = document.title;

    // Pokreni title flashing
    const startFlashing = () => {
        let isAlert = false;

        flashInterval = setInterval(() => {
            isAlert = !isAlert;

            // Promeni naslov
            document.title = isAlert ? '⚠️ ПОТВРДИТЕ ПРИСУСТВО!' : originalTitle;
        }, 1000); // Trepće svake sekunde
    };

    // Zaustavi flashing i vrati originalne vrednosti
    const stopFlashing = () => {
        if (flashInterval) {
            clearInterval(flashInterval);
            flashInterval = null;
        }

        // Vrati originalni naslov
        document.title = originalTitle;
    };

    // Proveri overtime status svakih 60 sekundi
    const checkOvertimeStatus = async () => {
        try {
            const response = await window.axios.get('/attendance/overtime/check');
            const data = response.data;

            if (data.needs_prompt && !showOvertimePrompt.value) {
                // Prikaži prompt
                showOvertimePrompt.value = true;
                overtimeMessage.value = data.message;
                promptTimeout.value = data.prompt_timeout;
                timeRemaining.value = data.prompt_timeout * 60; // konvertuj u sekunde

                // Pokreni title flashing
                startFlashing();

                // Pokreni countdown
                startCountdown();

                // Postavi timer za auto-checkout
                promptTimer = setTimeout(() => {
                    autoCheckout();
                }, data.prompt_timeout * 60 * 1000); // minuta → milisekunde
            }
        } catch (error) {
            console.error('Overtime check error:', error);
        }
    };

    // Countdown timer
    const startCountdown = () => {
        countdownInterval = setInterval(() => {
            timeRemaining.value--;

            if (timeRemaining.value <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    };

    // Format preostalog vremena (mm:ss)
    const formatTimeRemaining = () => {
        const minutes = Math.floor(timeRemaining.value / 60);
        const seconds = timeRemaining.value % 60;
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    };

    // Korisnik potvrđuje prisustvo
    const confirmPresence = async () => {
        try {
            // KRITIČNO: Zaustavi countdown i timere ODMAH (pre await-a!)
            // Da spreči re-render tokom axios poziva
            stopFlashing();

            if (promptTimer) {
                clearTimeout(promptTimer);
                promptTimer = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }

            // Sada sačekaj API response
            await window.axios.post('/attendance/overtime/confirm');

            // Zatvori modal
            showOvertimePrompt.value = false;

            // Forsiraj Vue da procesira promene
            await nextTick();
        } catch (error) {
            console.error('Confirm presence error:', error);
        }
    };

    // Auto checkout ako korisnik ne odgovori
    const autoCheckout = async () => {
        try {
            const response = await window.axios.post('/attendance/overtime/auto-checkout');

            // Zatvori prompt
            showOvertimePrompt.value = false;

            // Zaustavi flashing
            stopFlashing();

            // Redirect na login
            if (response.data.redirect) {
                window.location.href = response.data.redirect;
            }
        } catch (error) {
            console.error('Auto checkout error:', error);
        }
    };

    // Pokreni proveru
    onMounted(() => {
        // Proveri odmah
        checkOvertimeStatus();

        // Pa onda svakih 60 sekundi
        checkInterval = setInterval(checkOvertimeStatus, 60000);
    });

    // Očisti intervale
    onUnmounted(() => {
        if (checkInterval) clearInterval(checkInterval);
        if (promptTimer) clearTimeout(promptTimer);
        if (countdownInterval) clearInterval(countdownInterval);
        stopFlashing();
    });

    return {
        showOvertimePrompt,
        overtimeMessage,
        timeRemaining,
        formatTimeRemaining,
        confirmPresence
    };
}
