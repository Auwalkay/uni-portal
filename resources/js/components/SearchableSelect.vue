<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  ComboboxRoot,
  ComboboxInput,
  ComboboxTrigger,
  ComboboxContent,
  ComboboxViewport,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxPortal,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxLabel
} from 'radix-vue'
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  modelValue?: string | number | null
  options: Array<{ value: string | number; label: string }>
  placeholder?: string
  disabled?: boolean
}>()

const emits = defineEmits(['update:modelValue'])

const searchTerm = ref('')
const filteredOptions = computed(() => {
    if (!searchTerm.value) return props.options
    return props.options.filter(option => 
        option.label.toLowerCase().includes(searchTerm.value.toLowerCase())
    )
})

const displayValue = (v: any) => {
    if (!v) return ''
    const option = props.options.find(o => String(o.value) === String(v))
    return option ? option.label : ''
}
</script>

<template>
  <ComboboxRoot
    :model-value="modelValue ? String(modelValue) : ''"
    @update:model-value="(v) => emits('update:modelValue', v)"
    v-model:search-term="searchTerm"
    class="relative w-full"
    :display-value="displayValue"
    :disabled="disabled"
  >
    <div class="relative w-full">
        <div class="relative w-full">
            <ComboboxInput
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 pr-10"
            :placeholder="placeholder || 'Select option...'"
            />
            <ComboboxTrigger class="absolute top-0 right-0 h-full px-3 flex items-center justify-center text-muted-foreground hover:text-foreground cursor-pointer">
                <ChevronsUpDown class="h-4 w-4 opacity-50" />
            </ComboboxTrigger>
        </div>
    </div>

    <ComboboxPortal>
      <ComboboxContent 
        class="z-[100] min-w-[200px] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 w-[--radix-combobox-trigger-width]"
      >
        <ComboboxViewport class="p-1 max-h-[200px]">
            <ComboboxEmpty class="py-6 text-center text-sm text-muted-foreground">
                No results found.
            </ComboboxEmpty>
            
            <ComboboxGroup>
                <ComboboxItem
                    v-for="option in filteredOptions"
                    :key="option.value"
                    :value="String(option.value)"
                    :text-value="option.label"
                    class="relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                >
                    <ComboboxItemIndicator class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                        <Check class="h-4 w-4" />
                    </ComboboxItemIndicator>
                    <span>{{ option.label }}</span>
                </ComboboxItem>
            </ComboboxGroup>

        </ComboboxViewport>
      </ComboboxContent>
    </ComboboxPortal>
  </ComboboxRoot>
</template>
