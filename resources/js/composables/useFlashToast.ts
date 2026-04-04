import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function useFlashToast(): void {
    const page = usePage<{ flash: { success?: string; error?: string } }>();

    router.on('finish', () => {
        const flash = page.props.flash;

        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    });
}
