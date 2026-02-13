import { onMounted, ref } from 'vue';

export type ResolvedAppearance = 'light';
type Appearance = ResolvedAppearance;

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }

    // Always remove dark mode class to enforce light mode
    document.documentElement.classList.remove('dark');
}

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    updateTheme('light');
}

const appearance = ref<Appearance>('light');

export function useAppearance() {
    onMounted(() => {
        updateTheme('light');
    });

    function updateAppearance(value: Appearance) {
        appearance.value = 'light';
        updateTheme('light');
    }

    return {
        appearance,
        resolvedAppearance: ref('light'),
        updateAppearance,
    };
}
