<script setup lang="ts">
import { type LucideIcon } from 'lucide-vue-next';

interface Props {
    title: string;
    value: string | number;
    description?: string;
    icon?: LucideIcon;
    trend?: 'up' | 'down' | 'neutral';
    trendValue?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">{{ title }}</h3>
            <component 
                :is="icon" 
                v-if="icon" 
                class="h-4 w-4 text-muted-foreground" 
            />
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-bold tracking-tight text-sidebar-foreground">{{ value }}</span>
            <span 
                v-if="trend && trendValue"
                class="text-xs font-medium"
                :class="{
                    'text-green-600': trend === 'up',
                    'text-red-600': trend === 'down',
                    'text-gray-600': trend === 'neutral'
                }"
            >
                {{ trendValue }}
            </span>
        </div>
        <p v-if="description" class="mt-1 text-xs text-muted-foreground">
            {{ description }}
        </p>
    </div>
</template>
