import { ref } from 'vue';

const toasts = ref([]);

export function useToast() {
    const add = (message, type = 'success', duration = 4000) => {
        const id = Date.now();
        toasts.value.push({ id, message, type });
        
        setTimeout(() => {
            remove(id);
        }, duration);
    };

    const remove = (id) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (msg, dur) => add(msg, 'success', dur);
    const error = (msg, dur) => add(msg, 'error', dur);
    const warning = (msg, dur) => add(msg, 'warning', dur);
    const info = (msg, dur) => add(msg, 'info', dur);

    return {
        toasts,
        add,
        remove,
        success,
        error,
        warning,
        info
    };
}
