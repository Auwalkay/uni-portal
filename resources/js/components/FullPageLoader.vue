<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const isVisible = ref(false);
let showTimer: ReturnType<typeof setTimeout> | null = null;
const unsubscribers: Array<() => void> = [];

function clearShowTimer() {
    if (showTimer !== null) {
        clearTimeout(showTimer);
        showTimer = null;
    }
}

function hideLoader() {
    clearShowTimer();
    isVisible.value = false;
}

onMounted(() => {
    unsubscribers.push(
        router.on('start', (event) => {
            if ((event.detail.visit as any)?.prefetch) {
                return;
            }

            clearShowTimer();
            showTimer = setTimeout(() => {
                isVisible.value = true;
                showTimer = null;
            }, 120);
        }),
        router.on('finish', (event) => {
            if ((event.detail.visit as any)?.prefetch) {
                return;
            }
            hideLoader();
        }),
        router.on('navigate', hideLoader),
        router.on('error', hideLoader),
        router.on('invalid', hideLoader),
        router.on('exception', hideLoader),
    );
});

onUnmounted(() => {
    hideLoader();
    unsubscribers.forEach((off) => off());
});
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-150 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isVisible"
            role="status"
            aria-live="polite"
            aria-busy="true"
            class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white/75 dark:bg-slate-950/75"
        >
            <div class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-7 py-6 shadow-lg dark:border-slate-800 dark:bg-slate-900">
                <div class="relative h-11 w-11">
                    <div class="absolute inset-0 rounded-full border-[3px] border-slate-200 dark:border-slate-700" />
                    <div class="absolute inset-0 animate-spin rounded-full border-[3px] border-transparent border-t-red-600 dark:border-t-red-500" />
                </div>
                <span class="text-xs font-medium tracking-wide text-slate-600 dark:text-slate-300">
                    Loading...
                </span>
            </div>
        </div>
    </Transition>
</template>
