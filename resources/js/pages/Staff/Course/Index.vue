<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Search, BookOpen, Calendar, ArrowRight, Filter } from 'lucide-vue-next';
import debounce from 'lodash/debounce';
import { route } from 'ziggy-js';

const props = defineProps<{
    allocations: {
        data: Array<{
            id: string;
            course: {
                id: string;
                code: string;
                title: string;
                unit: number;
            };
            session: {
                name: string;
            };
        }>;
        links: any[];
    };
    sessions: Array<{ id: string; name: string }>;
    filters: { session_id?: string; search?: string };
    currentSessionId?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'My Courses', href: '/admin/staff/courses' },
];

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || props.currentSessionId);

watch(search, debounce((value) => {
    router.get(route('admin.teaching.courses.index'), { search: value, session_id: selectedSession.value }, { preserveState: true, replace: true });
}, 300));

watch(selectedSession, (value) => {
    router.get(route('admin.teaching.courses.index'), { session_id: value, search: search.value }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="My Courses" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">My Courses</h1>
                    <p class="text-muted-foreground mt-1">Manage your allocated courses and view student registrations.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="Search courses..."
                            class="pl-9 w-full"
                        />
                    </div>
                    <Select v-model="selectedSession">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="All Sessions" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Sessions</SelectItem>
                            <SelectItem v-for="session in sessions" :key="session.id" :value="String(session.id)">
                                {{ session.name }} Session
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Stats Summary -->
             <div class="grid gap-4 md:grid-cols-3">
                 <Card class="bg-indigo-50 dark:bg-indigo-950/20 border-indigo-100 dark:border-indigo-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-indigo-700 dark:text-indigo-400">Total Allocations</CardTitle>
                        <BookOpen class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ allocations.data.length }}</div>
                        <p class="text-xs text-indigo-600/80 dark:text-indigo-400/80">
                            {{ selectedSession ? 'In selected session' : 'All time' }}
                        </p>
                    </CardContent>
                </Card>
             </div>

            <!-- Course Grid -->
            <div v-if="allocations.data.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <a 
                    v-for="allocation in allocations.data" 
                    :key="allocation.id"
                    :href="route('admin.teaching.courses.show', allocation.course.id)"
                    class="group block h-full"
                >
                    <Card class="h-full hover:shadow-lg transition-all duration-300 border-l-4 border-l-indigo-500 overflow-hidden">
                        <CardHeader class="pb-3">
                            <div class="flex justify-between items-start">
                                <Badge variant="secondary" class="font-mono bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border-indigo-100">
                                    {{ allocation.course.code }}
                                </Badge>
                                <Badge variant="outline" class="text-muted-foreground">
                                    {{ allocation.session.name }}
                                </Badge>
                            </div>
                            <CardTitle class="text-lg mt-2 group-hover:text-indigo-600 transition-colors">
                                {{ allocation.course.title }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                             <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                                <div class="text-sm text-muted-foreground">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ allocation.course.unit }}</span> Units
                                </div>
                                <div class="flex items-center text-sm font-medium text-indigo-600 group-hover:translate-x-1 transition-transform">
                                    Manage <ArrowRight class="ml-1 h-3 w-3" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </a>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground border rounded-lg border-dashed">
                <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded-full mb-3">
                    <BookOpen class="w-8 h-8 text-slate-400" />
                </div>
                <h3 class="font-semibold text-lg">No courses found</h3>
                <p class="text-sm max-w-sm mt-1">
                    No courses matched your search or filter criteria. Try creating a new allocation or adjusting filters.
                </p>
                <div class="mt-4" v-if="filters.search || filters.session_id">
                     <Button variant="outline" size="sm" @click="() => { search = ''; selectedSession = ''; }">
                        Clear Filters
                    </Button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
