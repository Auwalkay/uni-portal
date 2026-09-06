<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Megaphone, Pin, Edit, Trash2, Calendar, Users, FileText, Download } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';

const props = defineProps<{
    bulletins: {
        data: Array<any>;
        links: Array<any>;
        current_page: number;
        last_page: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Announcements', href: '/admin/announcements' },
];

const form = useForm({});

const deleteBulletin = (id: string) => {
    if (confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) {
        form.delete(`/admin/announcements/${id}`);
    }
};

const getAudienceColor = (audience: string) => {
    switch (audience) {
        case 'students': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'staff': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
        default: return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Announcements & Bulletins" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
                        <Megaphone class="h-8 w-8 text-primary" />
                        Announcements & Bulletins
                    </h1>
                    <p class="text-muted-foreground mt-1">Publish notices, update bulletins, and broadcast email notifications to students and staff.</p>
                </div>
                <Link href="/admin/announcements/create">
                    <Button class="bg-primary hover:bg-primary/95 flex items-center gap-2">
                        <Megaphone class="h-4 w-4" />
                        New Announcement
                    </Button>
                </Link>
            </div>

            <!-- Bulletins Grid -->
            <div v-if="bulletins.data.length > 0" class="grid gap-6 md:grid-cols-2">
                <Card 
                    v-for="bulletin in bulletins.data" 
                    :key="bulletin.id" 
                    :class="['border transition-all duration-200 hover:shadow-md relative overflow-hidden flex flex-col justify-between', bulletin.is_pinned ? 'border-amber-400 bg-amber-50/10 dark:border-amber-500/30 dark:bg-amber-500/5' : 'bg-card border-border']"
                >
                    <!-- Pin Decoration -->
                    <div v-if="bulletin.is_pinned" class="absolute top-0 right-0 w-16 h-16 pointer-events-none overflow-hidden">
                        <div class="bg-amber-400 text-[10px] font-bold text-amber-950 text-center transform rotate-45 translate-x-4 translate-y-2 py-0.5 shadow-sm flex items-center justify-center gap-0.5">
                            <Pin class="h-2 w-2" /> Pinned
                        </div>
                    </div>

                    <CardHeader class="pb-3 pr-12">
                        <div class="flex items-center gap-2 flex-wrap mb-2">
                            <Badge :class="getAudienceColor(bulletin.target_audience)" class="capitalize">
                                <Users class="mr-1 h-3 w-3 inline" />
                                {{ bulletin.target_audience }}
                            </Badge>
                            <Badge v-if="bulletin.is_pinned" variant="outline" class="border-amber-500 text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                <Pin class="h-3 w-3" /> Pinned
                            </Badge>
                            <Badge v-if="bulletin.document_path" variant="outline" class="border-blue-500 text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                <FileText class="h-3 w-3" /> Scanned File
                            </Badge>
                        </div>
                        <CardTitle class="text-xl font-bold text-foreground line-clamp-1">
                            {{ bulletin.title }}
                        </CardTitle>
                        <p class="text-xs text-muted-foreground flex items-center gap-1.5 mt-1">
                            <Calendar class="h-3 w-3" />
                            {{ formatDate(bulletin.published_at) }} &bull; By {{ bulletin.author?.name || 'Administrator' }}
                        </p>
                    </CardHeader>

                    <CardContent class="pb-6 flex-1 flex flex-col justify-between">
                        <!-- Written Message -->
                        <div v-if="bulletin.content" class="text-sm text-muted-foreground line-clamp-4 mb-4" v-html="bulletin.content"></div>
                        <div v-else class="text-sm text-muted-foreground italic mb-4">No text content. Scanned document attached.</div>

                        <!-- Scanned Document View Action -->
                        <div v-if="bulletin.document_path" class="mb-4">
                            <a 
                                :href="`/storage/${bulletin.document_path}`" 
                                target="_blank" 
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline border border-primary/20 bg-primary/5 rounded px-2.5 py-1"
                            >
                                <Download class="h-3.5 w-3.5" /> View Scanned Document
                            </a>
                        </div>

                        <!-- Actions footer -->
                        <div class="flex items-center justify-end gap-2 border-t pt-4 mt-auto">
                            <Link :href="`/admin/announcements/${bulletin.id}/edit`">
                                <Button size="sm" variant="outline" class="flex items-center gap-1.5">
                                    <Edit class="h-3.5 w-3.5" /> Edit
                                </Button>
                            </Link>
                            <Button @click="deleteBulletin(bulletin.id)" size="sm" variant="destructive" class="flex items-center gap-1.5">
                                <Trash2 class="h-3.5 w-3.5" /> Delete
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center text-center p-12 border-2 border-dashed rounded-xl bg-card">
                <Megaphone class="h-16 w-16 text-muted-foreground mb-4 animate-bounce" />
                <h3 class="text-xl font-bold text-foreground">No announcements yet</h3>
                <p class="text-muted-foreground mt-2 max-w-sm">Publish your first bulletin to communicate with staff and students on the platform.</p>
                <Link href="/admin/announcements/create">
                    <Button class="mt-4 bg-primary hover:bg-primary/90">
                        Create Announcement
                    </Button>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="bulletins.last_page > 1" class="flex justify-center mt-6">
                <Pagination :links="bulletins.links" />
            </div>
        </div>
    </AdminLayout>
</template>
