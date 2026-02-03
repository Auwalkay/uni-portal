import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed, readonly } from 'vue';

import { toUrl } from '@/lib/utils';

export function useActiveUrl() {
    const page = usePage();
    const currentUrlReactive = computed(
        () => new URL(page.url, window?.location.origin).pathname,
    );

    function urlIsActive(
        urlToCheck: any,
        currentUrl?: string,
    ) {
        let checkPath = toUrl(urlToCheck as string);

        // Normalize checkPath to pathname if it's a full URL
        if (checkPath.startsWith('http')) {
            try {
                checkPath = new URL(checkPath).pathname;
            } catch (e) {
                // Ignore invalid URLs
            }
        }

        const currentPath = currentUrl ?? currentUrlReactive.value;

        if (checkPath === currentPath) {
            return true;
        }

        // Allow partial match for sub-pages (ensure it matches complete segment)
        return checkPath !== '/' && currentPath.startsWith(checkPath + '/');
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        urlIsActive,
    };
}
