import '../css/app.css';

// Trigger rebuild - new file added (update)
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';

import { initializeTheme } from './composables/useAppearance';

import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const baseZiggy = (props.initialPage.props as any)?.ziggy || Ziggy;
        const ziggyConfig = {
            ...baseZiggy,
            location: typeof window !== 'undefined' ? window.location.href : baseZiggy?.location,
            url: typeof window !== 'undefined' ? window.location.origin : baseZiggy?.url,
            port: typeof window !== 'undefined' && window.location.port ? parseInt(window.location.port) : null,
        };

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, ziggyConfig as any)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
