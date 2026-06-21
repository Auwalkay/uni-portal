<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { Check } from "lucide-vue-next"
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from "reka-ui"
import { cn } from "@/lib/utils"
import { computed } from "vue"

interface Props {
  class?: HTMLAttributes["class"]
  checked?: boolean | 'indeterminate'
  modelValue?: boolean | 'indeterminate' | null
  defaultValue?: boolean | 'indeterminate'
  disabled?: boolean
  value?: any
  id?: string
  name?: string
  required?: boolean
  as?: any
  asChild?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: undefined,
  value: 'on',
  as: 'button',
})

const emits = defineEmits<{
  'update:modelValue': [value: boolean | 'indeterminate']
  'update:checked': [value: boolean | 'indeterminate']
}>()

const modelValue = computed({
  get() {
    return props.modelValue !== undefined ? props.modelValue : props.checked
  },
  set(value) {
    emits('update:modelValue', value as boolean | 'indeterminate')
    emits('update:checked', value as boolean | 'indeterminate')
  }
})

const delegatedProps = reactiveOmit(props, "class", "checked", "modelValue")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <CheckboxRoot
    v-slot="slotProps"
    data-slot="checkbox"
    v-bind="forwarded"
    v-model="modelValue"
    :class="
      cn('peer border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
         props.class)"
  >
    <CheckboxIndicator
      data-slot="checkbox-indicator"
      class="grid place-content-center text-current transition-none"
    >
      <slot v-bind="slotProps">
        <Check class="size-3.5" />
      </slot>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
