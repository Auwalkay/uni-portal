<script setup lang="ts">
import { GraduationCap } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-white text-primary"
    >
        <GraduationCap class="size-6" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold"
            >UniPortal</span
        >
        <span class="text-xs text-muted-foreground font-normal">Management System</span>
    </div>
</template>
