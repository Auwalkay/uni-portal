<script setup lang="ts">
import { ref, computed } from 'vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, ChevronDown, Check } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{
    modelValue: string | number | null;
    items: Array<{ value: string | number; label: string }>;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    errorClass?: boolean;
}>(), {
    placeholder: 'Select...',
    searchPlaceholder: 'Search...',
    emptyText: 'No results found.',
    disabled: false,
    errorClass: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();

const open = ref(false);
const searchQuery = ref('');

const selectedItem = computed(() => {
    return props.items.find(item => String(item.value) === String(props.modelValue));
});

const filteredItems = computed(() => {
    if (!searchQuery.value) return props.items;
    const q = searchQuery.value.toLowerCase();
    return props.items.filter(item => 
        item.label.toLowerCase().includes(q)
    );
});

const selectItem = (value: string | number) => {
    emit('update:modelValue', value);
    open.value = false;
    searchQuery.value = '';
};
</script>

<template>
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
        )"
      >
        <span class="truncate">
          {{ selectedItem ? selectedItem.label : placeholder }}
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
            String(item.value) === String(modelValue) && 'bg-accent text-accent-foreground font-medium'
          )"
        >
          <Check
            :class="cn(
              'mr-2 h-4 w-4 shrink-0',
              String(item.value) === String(modelValue) ? 'opacity-100' : 'opacity-0'
            )"
          />
          <span class="truncate">{{ item.label }}</span>
        </button>
      </div>
    </PopoverContent>
  </Popover>
</template>
