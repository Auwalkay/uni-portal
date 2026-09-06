<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Bold, Italic, Underline, List, ListOrdered, AlignLeft, AlignCenter, AlignRight, Eraser } from 'lucide-vue-next';

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const editor = ref<HTMLDivElement | null>(null);
const initialHtml = ref(props.modelValue);

const executeCommand = (command: string, value: string = '') => {
    document.execCommand(command, false, value);
    if (editor.value) {
        emit('update:modelValue', editor.value.innerHTML);
    }
};

const handleInput = (e: Event) => {
    const target = e.target as HTMLDivElement;
    emit('update:modelValue', target.innerHTML);
};

// Sync parent edits if any (like when resetting form)
watch(() => props.modelValue, (newVal) => {
    if (editor.value && editor.value.innerHTML !== newVal) {
        editor.value.innerHTML = newVal;
    }
});

onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = props.modelValue;
    }
});
</script>

<template>
    <div class="border rounded-lg overflow-hidden bg-card border-border flex flex-col focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary transition-all duration-200">
        <!-- Editor Toolbar -->
        <div class="flex flex-wrap items-center gap-1 bg-muted/40 p-2 border-b border-border select-none">
            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('bold')"
                title="Bold"
            >
                <Bold class="h-4 w-4" />
            </Button>
            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('italic')"
                title="Italic"
            >
                <Italic class="h-4 w-4" />
            </Button>
            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('underline')"
                title="Underline"
            >
                <Underline class="h-4 w-4" />
            </Button>

            <div class="w-px h-6 bg-border mx-1"></div>

            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('insertUnorderedList')"
                title="Unordered List"
            >
                <List class="h-4 w-4" />
            </Button>
            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('insertOrderedList')"
                title="Ordered List"
            >
                <ListOrdered class="h-4 w-4" />
            </Button>

            <div class="w-px h-6 bg-border mx-1"></div>

            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('justifyLeft')"
                title="Align Left"
            >
                <AlignLeft class="h-4 w-4" />
            </Button>
            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('justifyCenter')"
                title="Align Center"
            >
                <AlignCenter class="h-4 w-4" />
            </Button>
            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('justifyRight')"
                title="Align Right"
            >
                <AlignRight class="h-4 w-4" />
            </Button>

            <div class="w-px h-6 bg-border mx-1"></div>

            <Button 
                type="button" 
                variant="ghost" 
                size="sm" 
                class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                @click="executeCommand('removeFormat')"
                title="Clear Formatting"
            >
                <Eraser class="h-4 w-4" />
            </Button>
        </div>

        <!-- Editable Area -->
        <div
            ref="editor"
            contenteditable="true"
            class="p-4 min-h-[250px] max-h-[400px] overflow-y-auto outline-none prose prose-sm max-w-none text-foreground dark:prose-invert"
            @input="handleInput"
        ></div>
    </div>
</template>

<style>
/* Add default paragraph styling for editable contents */
[contenteditable="true"] p {
    margin-bottom: 0.75rem;
}
[contenteditable="true"] ul {
    list-style-type: disc;
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
}
[contenteditable="true"] ol {
    list-style-type: decimal;
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
}
</style>
