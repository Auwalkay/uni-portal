<script setup lang="ts">
import { Check } from 'lucide-vue-next';

defineProps<{
    steps: string[];
    currentStep: number;
}>();
</script>

<template>
    <div class="w-full py-4">
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-200 -z-10"></div>
            <div 
                class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-primary transition-all duration-300 -z-10"
                :style="{ width: `${(currentStep / (steps.length - 1)) * 100}%` }"
            ></div>

            <div v-for="(step, index) in steps" :key="index" class="flex flex-col items-center gap-2 bg-white px-2">
                <div 
                    class="h-8 w-8 rounded-full flex items-center justify-center border-2 transition-colors duration-300"
                    :class="[
                        index <= currentStep ? 'border-primary bg-primary text-primary-foreground' : 'border-gray-300 bg-white text-gray-400'
                    ]"
                >
                    <Check v-if="index < currentStep" class="h-4 w-4" />
                    <span v-else class="text-xs font-bold">{{ index + 1 }}</span>
                </div>
                <span 
                    class="text-xs font-medium hidden md:block"
                    :class="index <= currentStep ? 'text-primary' : 'text-gray-400'"
                >
                    {{ step }}
                </span>
            </div>
        </div>
    </div>
</template>
