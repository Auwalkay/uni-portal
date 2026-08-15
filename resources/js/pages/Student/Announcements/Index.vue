<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Megaphone, Pin, Calendar, User, FileText, Download } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';

const props = defineProps<{
    announcements: {
        data: Array<any>;
        links: Array<any>;
        current_page: number;
        last_page: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/student/dashboard' },
    { title: 'Announcements', href: '/student/announcements' },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Announcements & Bulletins" />

    <StudentLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-6">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 p-8 shadow-lg text-white">
                <div class="relative z-10">
                    <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Megaphone class="h-8 w-8 text-blue-100" />
                        Announcements & Bulletins
                    </h1>
                    <p class="text-blue-100 mt-2 max-w-xl">Keep up-to-date with the latest notices, updates, schedules, and information published by the university administration.</p>
                </div>
                <!-- Decorative element -->
                <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
            </div>

            <!-- Announcements List -->
            <div v-if="announcements.data.length > 0" class="space-y-6">
                <Card 
                    v-for="bulletin in announcements.data" 
                    :key="bulletin.id" 
                    :class="['border transition-all duration-200 hover:shadow-md relative overflow-hidden', bulletin.is_pinned ? 'border-amber-400 bg-amber-50/5 dark:border-amber-500/20' : 'bg-card border-border']"
                >
                    <!-- Pin Decoration -->
                    <div v-if="bulletin.is_pinned" class="absolute top-0 right-0 w-16 h-16 pointer-events-none overflow-hidden">
                        <div class="bg-amber-400 text-[10px] font-bold text-amber-950 text-center transform rotate-45 translate-x-4 translate-y-2 py-0.5 shadow-sm flex items-center justify-center gap-0.5">
                            <Pin class="h-2 w-2" /> Pinned
                        </div>
                    </div>

                    <CardHeader class="pb-3 pr-12">
                        <div class="flex items-center gap-2 flex-wrap mb-2">
                            <Badge v-if="bulletin.is_pinned" variant="outline" class="border-amber-500 text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                <Pin class="h-3 w-3" /> Pinned
                            </Badge>
                        </div>
                        <CardTitle class="text-xl font-bold text-foreground">
                            {{ bulletin.title }}
                        </CardTitle>
                        <div class="text-xs text-muted-foreground flex flex-wrap items-center gap-3 mt-1.5">
                            <span class="flex items-center gap-1"><Calendar class="h-3.5 w-3.5" /> {{ formatDate(bulletin.published_at) }}</span>
                            <span class="flex items-center gap-1"><User class="h-3.5 w-3.5" /> Published by {{ bulletin.author?.name || 'Administrator' }}</span>
                        </div>
                    </CardHeader>

                    <CardContent class="pb-6 space-y-4">
                        <div v-if="bulletin.content" class="text-sm text-muted-foreground leading-relaxed" v-html="bulletin.content"></div>
                        
                        <div v-if="bulletin.document_path" class="pt-2">
                            <a 
                                :href="`/storage/${bulletin.document_path}`" 
                                target="_blank" 
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline border border-primary/20 bg-primary/5 rounded px-2.5 py-1"
                            >
                                <Download class="h-3.5 w-3.5" /> View Scanned Document
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center text-center p-12 border rounded-xl bg-card">
                <Megaphone class="h-16 w-16 text-muted-foreground mb-4" />
                <h3 class="text-xl font-bold text-foreground">No announcements yet</h3>
                <p class="text-muted-foreground mt-2 max-w-sm">There are no official notices published at the moment. Keep checking back later!</p>
            </div>

            <!-- Pagination -->
            <div v-if="announcements.last_page > 1" class="flex justify-center mt-6">
                <Pagination :links="announcements.links" />
            </div>
        </div>
    </StudentLayout>
</template>
