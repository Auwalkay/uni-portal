<script setup lang="ts">
import { ref, computed } from 'vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, ChevronDown, Check, X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{
    modelValue: any;
    items?: Array<{ value: string | number; label: string }>;
    options?: Array<{ value: string | number; label: string }>;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    errorClass?: boolean;
    triggerClass?: string;
    multiple?: boolean;
}>(), {
    items: () => [],
    options: () => [],
    placeholder: 'Select...',
    searchPlaceholder: 'Search...',
    emptyText: 'No results found.',
    disabled: false,
    errorClass: false,
    triggerClass: '',
    multiple: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
}>();

const open = ref(false);
const searchQuery = ref('');

const normalizedItems = computed(() => {
    if (Array.isArray(props.items) && props.items.length > 0) {
        return props.items;
    }
    if (Array.isArray(props.options) && props.options.length > 0) {
        return props.options;
    }
    if (Array.isArray(props.items)) {
        return props.items;
    }
    if (Array.isArray(props.options)) {
        return props.options;
    }
    return [];
});

const selectedItems = computed(() => {
    const itemList = normalizedItems.value || [];
    if (props.multiple) {
        const valArray = Array.isArray(props.modelValue) ? props.modelValue : [];
        return valArray.map(val => {
            const found = itemList.find(i => i && String(i.value) === String(val));
            return {
                value: val,
                label: found ? found.label : String(val)
            };
        });
    }
    return [];
});

const selectedItemText = computed(() => {
    const itemList = normalizedItems.value || [];
    if (props.multiple) {
        const count = selectedItems.value.length;
        if (count === 0) return props.placeholder;
        if (count === 1) return selectedItems.value[0].label;
        return `${count} Items Selected`;
    } else {
        if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
            return props.placeholder;
        }
        const found = itemList.find(item => item && String(item.value) === String(props.modelValue));
        return found ? found.label : props.placeholder;
    }
});

const isItemSelected = (val: string | number) => {
    if (props.multiple) {
        const valArray = Array.isArray(props.modelValue) ? props.modelValue : [];
        return valArray.some(v => String(v) === String(val));
    }
    return String(props.modelValue) === String(val);
};

const filteredItems = computed(() => {
    const itemList = normalizedItems.value || [];
    if (!searchQuery.value) return itemList;
    const q = searchQuery.value.toLowerCase();
    return itemList.filter(item => 
        item && item.label && String(item.label).toLowerCase().includes(q)
    );
});

const selectItem = (val: string | number) => {
    if (props.multiple) {
        const current = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        const index = current.findIndex(v => String(v) === String(val));
        if (index > -1) {
            current.splice(index, 1);
        } else {
            current.push(val);
        }
        emit('update:modelValue', current);
    } else {
        emit('update:modelValue', val);
        open.value = false;
        searchQuery.value = '';
    }
};

const removeItem = (val: string | number) => {
    if (props.multiple) {
        const current = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        const index = current.findIndex(v => String(v) === String(val));
        if (index > -1) {
            current.splice(index, 1);
            emit('update:modelValue', current);
        }
    }
};
</script>

<template>
  <div class="w-full space-y-2">
    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <Button
          type="button"
          variant="outline"
          role="combobox"
          :aria-expanded="open"
          :disabled="disabled"
          :class="cn(
            'w-full justify-between text-left font-normal bg-background hover:bg-background border-input px-3 h-10',
            errorClass && 'border-red-500 text-red-500',
            !modelValue && 'text-muted-foreground',
            triggerClass,
          )"
        >
          <span class="truncate">
            {{ selectedItemText }}
          </span>
          <ChevronDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent 
        class="w-[var(--radix-popover-trigger-width)] p-0 bg-popover text-popover-foreground border shadow-md rounded-md" 
        align="start"
        disable-portal
        @pointerdown.stop
        @mousedown.stop
        @touchstart.stop
      >
        <div class="flex items-center border-b px-3">
          <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
          <Input
            v-model="searchQuery"
            type="search"
            :placeholder="searchPlaceholder"
            class="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none border-none focus-visible:ring-0 focus-visible:ring-offset-0 px-0 shadow-none focus-visible:border-none focus:outline-none focus:ring-0"
          />
        </div>
        
        <div class="max-h-[200px] overflow-y-auto p-1 space-y-0.5">
          <div 
            v-if="filteredItems.length === 0" 
            class="py-6 text-center text-sm text-muted-foreground"
          >
            {{ emptyText }}
          </div>
          
          <button
            v-else
            v-for="item in filteredItems"
            :key="item.value"
            type="button"
            @click="selectItem(item.value)"
            :class="cn(
              'relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground text-left',
              isItemSelected(item.value) && 'bg-accent text-accent-foreground font-medium'
            )"
          >
            <Check
              :class="cn(
                'mr-2 h-4 w-4 shrink-0',
                isItemSelected(item.value) ? 'opacity-100' : 'opacity-0'
              )"
            />
            <span class="truncate">{{ item.label }}</span>
          </button>
        </div>
      </PopoverContent>
    </Popover>

    <!-- Selected items tag chips container for multiple mode -->
    <div 
      v-if="multiple && selectedItems.length > 0" 
      class="flex flex-wrap gap-1.5 max-h-[140px] overflow-y-auto p-1 bg-neutral-50 dark:bg-neutral-900/60 border border-neutral-200/80 dark:border-neutral-800 rounded-lg"
    >
      <div
        v-for="item in selectedItems"
        :key="item.value"
        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/80 shadow-2xs"
      >
        <span class="truncate max-w-[280px]">{{ item.label }}</span>
        <button
          type="button"
          @click.stop="removeItem(item.value)"
          class="hover:bg-rose-200/70 dark:hover:bg-rose-900/90 rounded-full p-0.5 text-rose-600 dark:text-rose-300 transition-colors cursor-pointer"
          title="Remove course"
        >
          <X class="w-3 h-3" />
        </button>
      </div>
    </div>
  </div>
</template>
