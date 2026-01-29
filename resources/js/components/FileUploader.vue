<script setup lang="ts">
import { ref } from 'vue';
import { UploadCloud, File, X } from 'lucide-vue-next';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    label: string;
    accept?: string;
}>();

const emit = defineEmits(['update:file']);

const file = ref<File | null>(null);
const isDragging = ref(false);

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    const droppedFiles = e.dataTransfer?.files;
    if (droppedFiles && droppedFiles.length > 0) {
        file.value = droppedFiles[0];
        emit('update:file', file.value);
    }
};

const handleFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        file.value = target.files[0];
        emit('update:file', file.value);
    }
};

const removeFile = () => {
    file.value = null;
    emit('update:file', null);
};
</script>

<template>
    <div class="space-y-2">
        <Label>{{ label }}</Label>
        
        <div v-if="!file"
            class="border-2 border-dashed rounded-lg p-6 flex flex-col items-center justify-center text-center cursor-pointer transition-colors"
            :class="isDragging ? 'border-primary bg-primary/5' : 'border-gray-300 hover:border-primary/50'"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="$refs.fileInput.click()"
        >
            <input 
                ref="fileInput"
                type="file" 
                class="hidden" 
                :accept="accept"
                @change="handleFileSelect"
            >
            <div class="h-10 w-10 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                <UploadCloud class="h-5 w-5 text-gray-500" />
            </div>
            <p class="text-sm font-medium">Click to upload or drag and drop</p>
            <p class="text-xs text-muted-foreground mt-1">SVG, PNG, JPG or PDF (max. 5MB)</p>
        </div>

        <div v-else class="flex items-center justify-between p-3 border rounded-lg bg-gray-50">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 bg-blue-100 text-blue-600 rounded flex items-center justify-center">
                    <File class="h-4 w-4" />
                </div>
                <div class="text-sm">
                    <p class="font-medium text-gray-900 truncate max-w-[200px]">{{ file.name }}</p>
                    <p class="text-xs text-gray-500">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</p>
                </div>
            </div>
            <button @click="removeFile" type="button" class="text-gray-400 hover:text-red-500">
                <X class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
