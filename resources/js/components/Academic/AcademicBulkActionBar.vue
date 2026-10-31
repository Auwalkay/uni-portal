<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { CheckCircle2, XCircle, X } from 'lucide-vue-next'

const props = withDefaults(
    defineProps<{
        selectedCount: number
        itemName?: string
        isProcessing?: boolean
    }>(),
    {
        itemName: 'item',
        isProcessing: false,
    }
)

const emit = defineEmits<{
    (e: 'activate'): void
    (e: 'deactivate'): void
    (e: 'clear'): void
}>()

const itemLabel = computed(() => {
    return props.selectedCount === 1
        ? props.itemName
        : `${props.itemName}s`
})
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform translate-y-8 opacity-0 scale-95"
        enter-to-class="transform translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100 scale-100"
        leave-to-class="transform translate-y-8 opacity-0 scale-95"
    >
        <div
            v-if="selectedCount > 0"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 max-w-[calc(100vw-2rem)] flex flex-wrap sm:flex-nowrap items-center justify-between gap-2 sm:gap-3 px-3 py-2 sm:px-4 sm:py-2.5 bg-neutral-900/95 dark:bg-neutral-950/95 text-white backdrop-blur-md rounded-2xl shadow-2xl border border-neutral-700/60"
        >
            <!-- Selection Counter -->
            <div class="flex items-center gap-2 pr-2 sm:pr-3 sm:border-r border-neutral-700/80 text-xs font-semibold">
                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-[11px] font-black rounded-full bg-primary text-primary-foreground">
                    {{ selectedCount }}
                </span>
                <span class="whitespace-nowrap">{{ itemLabel }} selected</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-1.5 sm:gap-2">
                <Button
                    type="button"
                    size="sm"
                    :disabled="isProcessing"
                    @click="emit('activate')"
                    class="h-8 px-2.5 sm:px-3 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white gap-1.5 shadow-sm shrink-0"
                >
                    <CheckCircle2 class="w-3.5 h-3.5 shrink-0" />
                    <span>Activate</span>
                </Button>

                <Button
                    type="button"
                    size="sm"
                    :disabled="isProcessing"
                    @click="emit('deactivate')"
                    class="h-8 px-2.5 sm:px-3 text-xs font-bold bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white gap-1.5 shadow-sm shrink-0"
                >
                    <XCircle class="w-3.5 h-3.5 shrink-0" />
                    <span>Deactivate</span>
                </Button>

                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    :disabled="isProcessing"
                    @click="emit('clear')"
                    class="h-8 px-2 text-xs text-neutral-400 hover:text-white hover:bg-neutral-800 shrink-0 gap-1"
                    title="Deselect all"
                >
                    <X class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Clear</span>
                </Button>
            </div>
        </div>
    </Transition>
</template>
