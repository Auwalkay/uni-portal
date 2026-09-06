<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Megaphone, ArrowLeft, Upload, FileText, X, CheckCircle, ExternalLink } from 'lucide-vue-next';
import WysiwygEditor from '@/components/WysiwygEditor.vue';

const props = defineProps<{
    bulletin: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Announcements', href: '/admin/announcements' },
    { title: 'Edit', href: `/admin/announcements/${props.bulletin.id}/edit` },
];

const form = useForm({
    title: props.bulletin.title,
    content: props.bulletin.content || '',
    target_audience: props.bulletin.target_audience,
    is_pinned: props.bulletin.is_pinned,
    document: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const filePreview = ref<string | null>(props.bulletin.document_path ? 'Current Scanned Document' : null);
const fileError = ref<string | null>(null);
const isDragActive = ref(false);

const handleFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        setFile(target.files[0]);
    }
};

const handleFileDrop = (e: DragEvent) => {
    isDragActive.value = false;
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
        setFile(e.dataTransfer.files[0]);
    }
};

const setFile = (file: File) => {
    fileError.value = null;
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
        fileError.value = 'Only PDF, JPEG, JPG, and PNG files are allowed.';
        return;
    }
    if (file.size > 10 * 1024 * 1024) { // 10MB
        fileError.value = 'File size must be less than 10MB.';
        return;
    }

    form.document = file;
    filePreview.value = file.name;
};

const removeFile = () => {
    form.document = null;
    filePreview.value = null;
    fileError.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitForm = () => {
    // In Laravel, file uploads via PUT/PATCH can fail.
    // We send via POST to the update endpoint (which supports native file parsing).
    form.post(`/admin/announcements/${props.bulletin.id}`, {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Edit Announcement" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center gap-4 border-b pb-4">
                <Link href="/admin/announcements">
                    <Button variant="outline" size="icon" class="h-8 w-8">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
                        Edit Announcement
                    </h1>
                    <p class="text-muted-foreground mt-0.5">Modify announcement content or replace its scanned file.</p>
                </div>
            </div>

            <!-- Main Creator Layout -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Creator Form Column -->
                <div class="lg:col-span-2 space-y-6">
                    <Card class="bg-card border-border shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-lg font-bold flex items-center gap-2">
                                <FileText class="h-5 w-5 text-primary" /> Announcement Message
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Title -->
                            <div class="space-y-1">
                                <label class="text-sm font-semibold text-foreground">Title / Headline</label>
                                <Input 
                                    v-model="form.title" 
                                    placeholder="E.g., Special Senate Meeting Rescheduled" 
                                    required 
                                />
                                <span v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</span>
                            </div>

                            <!-- Content WYSIWYG Editor -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-sm font-semibold text-foreground">Message Body</label>
                                    <span class="text-xs text-muted-foreground">Rich text formatting supported</span>
                                </div>
                                <WysiwygEditor v-model="form.content" />
                                <span v-if="form.errors.content" class="text-xs text-destructive">{{ form.errors.content }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Config & Upload Column -->
                <div class="space-y-6">
                    <!-- Settings -->
                    <Card class="bg-card border-border shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-lg font-bold">Publish Settings</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Target Audience -->
                            <div class="space-y-1">
                                <label class="text-sm font-semibold text-foreground">Recipient Audience</label>
                                <Select v-model="form.target_audience">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select target audience" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Portal Users</SelectItem>
                                        <SelectItem value="students">Students Only</SelectItem>
                                        <SelectItem value="staff">Staff Members Only</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Pinned Toggle -->
                            <div class="flex items-center justify-between border rounded-lg p-3">
                                <div class="space-y-0.5">
                                    <label class="text-sm font-semibold text-foreground">Pin Announcement</label>
                                    <p class="text-xs text-muted-foreground">Keep at the top of feeds.</p>
                                </div>
                                <Switch 
                                    :checked="form.is_pinned"
                                    @update:checked="form.is_pinned = $event"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Scanned Document Upload -->
                    <Card class="bg-card border-border shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-lg font-bold flex items-center gap-1.5">
                                <Upload class="h-4 w-4" /> Scanned Document
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-xs text-muted-foreground">Upload a new scanned memo or PDF file to replace the current attachment (if any).</p>

                            <!-- Current File Preview Link -->
                            <div v-if="bulletin.document_path" class="mb-2">
                                <a 
                                    :href="`/storage/${bulletin.document_path}`" 
                                    target="_blank" 
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                                >
                                    <ExternalLink class="h-3.5 w-3.5" /> View Current Attachment
                                </a>
                            </div>
                            
                            <!-- Drag & Drop Container -->
                            <div 
                                @dragover.prevent="isDragActive = true"
                                @dragleave.prevent="isDragActive = false"
                                @drop.prevent="handleFileDrop"
                                @click="fileInput?.click()"
                                :class="[
                                    'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center min-h-[140px]',
                                    isDragActive ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted/30'
                                ]"
                            >
                                <input 
                                    type="file" 
                                    ref="fileInput" 
                                    class="hidden" 
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="handleFileSelect"
                                />

                                <div v-if="!filePreview" class="space-y-2">
                                    <Upload class="h-8 w-8 text-muted-foreground mx-auto" />
                                    <div class="text-sm font-medium text-foreground">Drag file here or click to browse</div>
                                    <div class="text-[10px] text-muted-foreground">PDF, PNG, JPG (Max 10MB)</div>
                                </div>

                                <div v-else class="w-full flex items-center justify-between bg-muted/50 p-2.5 rounded-lg border border-border">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <FileText class="h-5 w-5 text-primary flex-shrink-0" />
                                        <span class="text-xs font-semibold text-foreground truncate max-w-[150px]">{{ filePreview }}</span>
                                    </div>
                                    <Button 
                                        type="button" 
                                        variant="ghost" 
                                        size="icon" 
                                        class="h-7 w-7 text-muted-foreground hover:text-foreground"
                                        @click.stop="removeFile"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            
                            <span v-if="fileError" class="text-xs text-destructive block">{{ fileError }}</span>
                            <span v-if="form.errors.document" class="text-xs text-destructive block">{{ form.errors.document }}</span>
                        </CardContent>
                    </Card>

                    <!-- Submit Actions -->
                    <div class="flex flex-col gap-2">
                        <Button 
                            @click="submitForm" 
                            class="w-full bg-primary hover:bg-primary/95 flex items-center justify-center gap-2" 
                            size="lg" 
                            :disabled="form.processing"
                        >
                            <CheckCircle class="h-5 w-5" />
                            Update Announcement
                        </Button>
                        <Link href="/admin/announcements" class="w-full">
                            <Button variant="outline" class="w-full" size="lg">Cancel</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
