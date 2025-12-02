import { useNotification } from '@kyvg/vue3-notification';

export const useNotifications = () => {
    const { notify } = useNotification();

    const success = (message: string, title?: string) => {
        notify({
            type: 'success',
            title: title || 'Succès',
            text: message,
            duration: 5000,
        });
    };

    const error = (message: string, title?: string) => {
        notify({
            type: 'error',
            title: title || 'Erreur',
            text: message,
            duration: 6000,
        });
    };

    const warning = (message: string, title?: string) => {
        notify({
            type: 'warning',
            title: title || 'Attention',
            text: message,
            duration: 5000,
        });
    };

    const info = (message: string, title?: string) => {
        notify({
            type: 'info',
            title: title || 'Information',
            text: message,
            duration: 4000,
        });
    };

    return {
        success,
        error,
        warning,
        info,
    };
};

