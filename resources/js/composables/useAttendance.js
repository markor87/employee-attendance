import axios from 'axios';
import { useToast } from 'vue-toastification';

export function useAttendance() {
    const toast = useToast();

    /**
     * Force check-in a user
     * @param {Object} data - { user_id, reason, notes }
     * @returns {Promise<Object>}
     */
    const forceCheckIn = async (data) => {
        try {
            const response = await axios.post('/attendance/force-check-in', {
                user_id: data.user_id,
                reason: data.reason,
                notes: data.notes || null,
            });

            if (response.data.success) {
                toast.success(response.data.message || 'Корисник је успешно пријављен.');
                return response.data;
            } else {
                toast.error(response.data.message || 'Грешка при пријављивању корисника.');
                throw new Error(response.data.message);
            }
        } catch (error) {
            console.error('Force check-in error:', error);

            const errorMessage = error.response?.data?.message || 'Дошло је до грешке приликом пријављивања.';
            toast.error(errorMessage);

            throw error;
        }
    };

    /**
     * Force check-out a user
     * @param {Object} data - { user_id, reason, notes }
     * @returns {Promise<Object>}
     */
    const forceCheckOut = async (data) => {
        try {
            const response = await axios.post('/attendance/force-check-out', {
                user_id: data.user_id,
                reason: data.reason,
                notes: data.notes || null,
            });

            if (response.data.success) {
                toast.success(response.data.message || 'Корисник је успешно одјављен.');
                return response.data;
            } else {
                toast.error(response.data.message || 'Грешка при одјављивању корисника.');
                throw new Error(response.data.message);
            }
        } catch (error) {
            console.error('Force check-out error:', error);

            const errorMessage = error.response?.data?.message || 'Дошло је до грешке приликом одјављивања.';
            toast.error(errorMessage);

            throw error;
        }
    };

    /**
     * Get reasons for check-in/check-out
     * @returns {Promise<Object>} - { checkIn: Array, checkOut: Array }
     */
    const getReasons = async () => {
        try {
            const response = await axios.get('/attendance/reasons');

            if (response.data.success) {
                return response.data.data;
            } else {
                toast.error('Грешка при учитавању разлога.');
                throw new Error(response.data.message);
            }
        } catch (error) {
            console.error('Get reasons error:', error);

            const errorMessage = error.response?.data?.message || 'Грешка при учитавању разлога.';
            toast.error(errorMessage);

            throw error;
        }
    };

    /**
     * Get user's active time log
     * @param {Number} userId
     * @returns {Promise<Object|null>}
     */
    const getActiveLog = async (userId) => {
        try {
            const response = await axios.get(`/attendance/status?user_id=${userId}`);
            return response.data.activeLog || null;
        } catch (error) {
            console.error('Get active log error:', error);
            return null;
        }
    };

    return {
        forceCheckIn,
        forceCheckOut,
        getReasons,
        getActiveLog,
    };
}
