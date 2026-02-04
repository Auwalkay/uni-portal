<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Users, FileText, BookOpen, Clock, ArrowRight, UserPlus, MessageSquare, AlertCircle } from 'lucide-vue-next';
import { format } from 'date-fns';

interface Props {
    stats: {
        active_visitors: number;
        total_visitors_today: number;
        pending_complaints: number;
        open_enquiries: number;
    };
    recent_visitors: any[];
    recent_complaints: any[];
}

defineProps<Props>();

const breadcrumbs = [
    { title: 'Front Desk', href: '#' },
    { title: 'Dashboard', href: '/admin/front-desk' },
];
</script>

<template>
    <Head title="Front Desk Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Front Desk Hub</h1>
                    <p class="text-slate-500 dark:text-slate-400">Welcome to the central command for campus hospitality and enquiries.</p>
                </div>
                <div class="flex gap-3">
                    <Button as-child class="bg-indigo-600 hover:bg-indigo-700">
                        <Link :href="route('admin.front-desk.visitors.index')">
                            <UserPlus class="w-4 h-4 mr-2" /> Register Visitor
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium">Active Visitors</CardTitle>
                        <Users class="w-4 h-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.active_visitors }}</div>
                        <p class="text-xs text-slate-500">Currently on campus</p>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium">Today's Visitors</CardTitle>
                        <Clock class="w-4 h-4 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_visitors_today }}</div>
                        <p class="text-xs text-slate-500">Scheduled/Walk-ins today</p>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium">Pending Complaints</CardTitle>
                        <AlertCircle class="w-4 h-4 text-rose-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_complaints }}</div>
                        <p class="text-xs text-slate-500">Awaiting resolution</p>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium">Open Enquiries</CardTitle>
                        <MessageSquare class="w-4 h-4 text-amber-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.open_enquiries }}</div>
                        <p class="text-xs text-slate-500">Active public queries</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Recent Visitors -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Recent Visitors</CardTitle>
                            <CardDescription>Latest campus access logs</CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('admin.front-desk.visitors.index')">View All <ArrowRight class="w-4 h-4 ml-2" /></Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="visitor in recent_visitors" :key="visitor.id" class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                                        {{ visitor.visitor_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold">{{ visitor.visitor_name }}</div>
                                        <div class="text-xs text-slate-500">{{ visitor.purpose }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <Badge :variant="visitor.check_out ? 'secondary' : 'default'" class="text-[10px]">
                                        {{ visitor.check_out ? 'Checked Out' : 'On Campus' }}
                                    </Badge>
                                    <div class="text-[10px] text-slate-400 mt-1">{{ format(new Date(visitor.check_in), 'HH:mm') }}</div>
                                </div>
                            </div>
                            <div v-if="recent_visitors.length === 0" class="text-center py-8 text-slate-400">
                                No visitor records found.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Complaints -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Recent Complaints</CardTitle>
                            <CardDescription>Latest feedback and issues</CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('admin.front-desk.complaints.index')">View All <ArrowRight class="w-4 h-4 ml-2" /></Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="complaint in recent_complaints" :key="complaint.id" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="text-sm font-semibold">{{ complaint.subject }}</div>
                                    <Badge :variant="complaint.status === 'resolved' ? 'secondary' : 'destructive'" class="text-[10px]">
                                        {{ complaint.status }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-1 mb-2">{{ complaint.description }}</p>
                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span>{{ complaint.complainant_name }}</span>
                                    <span>{{ format(new Date(complaint.created_at), 'MMM dd, yyyy') }}</span>
                                </div>
                            </div>
                            <div v-if="recent_complaints.length === 0" class="text-center py-8 text-slate-400">
                                No complaint records found.
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
