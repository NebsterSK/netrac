import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, defineComponent, h, Teleport } from 'vue';
import { Sonner } from '@/components/ui/sonner';
import { useFlashToast } from '@/composables/useFlashToast';
import '../css/app.css';
import { initializeTheme } from '@/composables/useAppearance';
import 'virtual:instruckt';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const RootComponent = defineComponent({
            setup() {
                useFlashToast();
            },
            render() {
                return [
                    h(App, props),
                    h(Teleport, { to: 'body' }, h(Sonner, { position: 'top-right', richColors: true })),
                ];
            },
        });

        createApp(RootComponent)
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
