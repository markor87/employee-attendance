import { ref, onMounted, onUnmounted } from 'vue';
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

    // Originalni naslov i favicon
    let originalTitle = document.title;
    let originalFavicon = null;

    // Pronađi favicon link element
    const getFaviconElement = () => {
        return document.querySelector('link[rel="icon"]') ||
               document.querySelector('link[rel="shortcut icon"]');
    };

    // Zapamti originalni favicon
    const saveOriginalFavicon = () => {
        const faviconElement = getFaviconElement();
        if (faviconElement) {
            originalFavicon = faviconElement.href;
        }
    };

    // Kreiraj alert favicon (crveni)
    const createAlertFavicon = () => {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 32;
        const ctx = canvas.getContext('2d');

        // Crveni krug
        ctx.fillStyle = '#DC2626';
        ctx.beginPath();
        ctx.arc(16, 16, 16, 0, 2 * Math.PI);
        ctx.fill();

        // Beli uzvičnik
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 20px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('!', 16, 16);

        return canvas.toDataURL();
    };

    // Pokreni favicon/title flashing
    const startFlashing = () => {
        saveOriginalFavicon();
        const alertFavicon = createAlertFavicon();
        const faviconElement = getFaviconElement();

        let isAlert = false;

        flashInterval = setInterval(() => {
            isAlert = !isAlert;

            // Promeni favicon
            if (faviconElement) {
                faviconElement.href = isAlert ? alertFavicon : originalFavicon;
            }

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

        // Vrati originalni favicon
        const faviconElement = getFaviconElement();
        if (faviconElement && originalFavicon) {
            faviconElement.href = originalFavicon;
        }
    };

    // Proveri overtime status svakih 60 sekundi
    const checkOvertimeStatus = async () => {
        try {
            const response = await window.axios.get('/attendance/overtime/check');
            const data = response.data;

            console.log('Overtime check response:', {
                needs_prompt: data.needs_prompt,
                is_checked_in: data.is_checked_in,
                showOvertimePrompt_current: showOvertimePrompt.value,
                debug_info: data.debug_info,
                minutes_passed: data.minutes_passed,
                interval_required: data.interval_required,
            });

            if (data.needs_prompt && !showOvertimePrompt.value) {
                console.log('Prikazujem overtime modal');
                // Prikaži prompt
                showOvertimePrompt.value = true;
                overtimeMessage.value = data.message;
                promptTimeout.value = data.prompt_timeout;
                timeRemaining.value = data.prompt_timeout * 60; // konvertuj u sekunde

                // Pokreni favicon/title flashing
                startFlashing();

                // Pokreni countdown
                startCountdown();

                // Postavi timer za auto-checkout
                promptTimer = setTimeout(() => {
                    autoCheckout();
                }, data.prompt_timeout * 60 * 1000); // minuta → milisekunde
            } else if (data.needs_prompt && showOvertimePrompt.value) {
                console.log('Modal je već prikazan, ne prikazujem ponovo');
            } else if (!data.needs_prompt) {
                console.log('Modal nije potreban', {
                    reason: data.reason || 'interval not passed',
                    next_prompt_at: data.next_prompt_at,
                });
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
            console.log('Potvrđujem prisustvo...');
            await window.axios.post('/attendance/overtime/confirm');

            console.log('Prisustvo potvrđeno, zatvaram modal');
            // Zatvori prompt
            showOvertimePrompt.value = false;

            // Zaustavi flashing
            stopFlashing();

            // Poništi timere
            if (promptTimer) {
                clearTimeout(promptTimer);
                promptTimer = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
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
