<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';

import { BookOpen, CreditCard, FileText, GraduationCap, Users } from 'lucide-vue-next';
import StatsCard from '@/components/StatsCard.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Total Students"
                    value="12,345"
                    description="+180 from last month"
                    :icon="GraduationCap"
                    trend="up"
                    trendValue="+1.2%"
                />
                <StatsCard
                    title="Applications"
                    value="543"
                    description="Pending review"
                    :icon="FileText"
                    trend="up"
                    trendValue="+12%"
                />
                <StatsCard
                    title="Active Courses"
                    value="120"
                    description="Across 8 faculties"
                    :icon="BookOpen"
                />
                <StatsCard
                    title="Revenue"
                    value="₦45.2M"
                    description="This semester"
                    :icon="CreditCard"
                    trend="up"
                    trendValue="+8%"
                />
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <!-- Recent Activity / Chart Placeholder -->
                <div class="col-span-4 rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-sidebar-foreground">Admission Statistics</h3>
                    <div class="relative aspect-video w-full overflow-hidden rounded-lg bg-sidebar-accent/50">
                        <PlaceholderPattern />
                        <div class="absolute inset-0 flex items-center justify-center text-sm text-muted-foreground">
                            Chart Visualization Placeholder
                        </div>
                    </div>
                </div>

                <!-- Quick Actions / Notifications -->
                <div class="col-span-3 rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-sidebar-foreground">Recent Activities</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 border-b border-sidebar-border/50 pb-4 last:border-0 last:pb-0">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                <Users class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-sidebar-foreground">New student registration</p>
                                <p class="text-xs text-muted-foreground">John Doe completed registration</p>
                                <p class="mt-1 text-xs text-muted-foreground">2 mins ago</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 border-b border-sidebar-border/50 pb-4 last:border-0 last:pb-0">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-yellow-100 text-yellow-600">
                                <FileText class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-sidebar-foreground">Application submitted</p>
                                <p class="text-xs text-muted-foreground">Sarah Smith applied for CS</p>
                                <p class="mt-1 text-xs text-muted-foreground">1 hour ago</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 border-b border-sidebar-border/50 pb-4 last:border-0 last:pb-0">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                                <CreditCard class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-sidebar-foreground">Payment received</p>
                                <p class="text-xs text-muted-foreground">Acceptance fee paid by #8291</p>
                                <p class="mt-1 text-xs text-muted-foreground">3 hours ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
