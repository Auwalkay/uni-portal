<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import Swal from 'sweetalert2';

import { SidebarProvider } from '@/components/ui/sidebar';

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const page = usePage();
const isOpen = page.props.sidebarOpen;

// Helper for Toast style
const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.onmouseenter = Swal.stopTimer;
    toast.onmouseleave = Swal.resumeTimer;
  }
});

watch(() => page.props.flash, (flash: any) => {
    if (flash?.success) {
        Toast.fire({
            icon: "success",
            title: flash.success
        });
    }
    if (flash?.error) {
        // Can switch to modal for errors if preferred, but user requested consistent notifications.
        // Let's us Toast for general errors, and maybe modal for specific critical ones if needed.
        // For now: Toast for consistency.
        Toast.fire({
            icon: "error",
            title: flash.error
        });
    }
    if (flash?.warning) {
        Toast.fire({
            icon: "warning",
            title: flash.warning
        });
    }
    if (flash?.info) {
        Toast.fire({
            icon: "info",
            title: flash.info
        });
    }
    if (flash?.status) {
        Toast.fire({
            icon: "info",
            title: flash.status
        });
    }
}, { deep: true, immediate: true });
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
    </SidebarProvider>
</template>
