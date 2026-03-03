<script setup lang="ts">
import { ref } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';

defineOptions({
    inheritAttrs: false
});

const props = defineProps<{
    modelValue?: string | number;
    error?: string;
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();

const showPassword = ref(false);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emits('update:modelValue', target.value);
};
</script>

<template>
    <div class="relative w-full">
        <Input
            v-bind="$attrs"
            :type="showPassword ? 'text' : 'password'"
            :model-value="modelValue"
            @input="handleInput"
            :class="[
                'pr-10 shadow-sm transition-all duration-200 focus-visible:ring-primary/20',
                $attrs.class,
                error ? 'border-destructive focus-visible:ring-destructive/20' : ''
            ]"
        />
        <button
            type="button"
            @click="togglePassword"
            class="absolute right-0 top-0 h-full px-3 py-2 text-muted-foreground hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20 rounded-r-md"
            title="Toggle password visibility"
        >
            <Eye v-if="!showPassword" class="h-4 w-4" />
            <EyeOff v-else class="h-4 w-4" />
        </button>
    </div>
</template>
