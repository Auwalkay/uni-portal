<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    User, Mail, Building2, Briefcase, GraduationCap, Shield, ArrowLeft, 
    Calendar, Pencil, ShieldCheck, UserCircle, Building, Hash, BookOpen, 
    BarChart3, Clock, AlertCircle, CalendarClock, MapPin
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Progress } from '@/components/ui/progress'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import { route } from 'ziggy-js';

const props = defineProps<{
    staff: {
        id: string;
        name: string;
        email: string;
        roles: Array<{ id: string; name: string }>;
        staff: {
            staff_number: string;
            designation: string;
            is_academic: boolean;
            department: {
                name: string;
                faculty: {
                    name: string;
                } | null;
            } | null;
            allocations: Array<{
                id: string;
                course: {
                    code: string;
                    title: string;
                    unit: number;
                };
                session: {
                    name: string;
                };
            }>;
        } | null;
    };
    timetable?: Array<any>;
}>();

const formatTime = (time: string) => {
    return time.substring(0, 5);
};

const getClassesForDay = (day: string) => {
    return props.timetable?.filter((t: any) => t.day === day) || [];
};

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const totalUnits = computed(() => {
    if (!props.staff.staff?.allocations) return 0;
    return props.staff.staff.allocations.reduce((acc, curr) => acc + (curr.course?.unit || 0), 0);
});

// Assuming a "full load" is around 12 units for visualization purposes
const teachingLoadWithPercentage = computed(() => {
    const units = totalUnits.value;
    const maxLoad = 15; // Arbitrary max load
    return Math.min((units / maxLoad) * 100, 100);
});

const breadcrumbs = [
    { title: 'Staff Management', href: '/admin/staff' },
    { title: 'Staff Profile', href: '#' }
];
</script>

<template>
    <Head :title="`${staff.name} - Staff Profile`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-[1600px] mx-auto">
            
            <!-- Hero Section -->
            <div class="relative bg-white dark:bg-slate-950 rounded-2xl overflow-hidden shadow-sm border border-slate-200 dark:border-slate-800">
                <!-- Decorative Background -->
                <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-r from-blue-600 to-indigo-700 opacity-90">
                    <div class="absolute inset-0 bg-grid-white/[0.1] bg-[length:20px_20px]"></div>
                </div>

                <div class="relative pt-24 px-8 pb-8 flex flex-col md:flex-row items-end gap-8">
                     <!-- Avatar -->
                    <Avatar class="h-40 w-40 border-[6px] border-white dark:border-slate-950 shadow-2xl rounded-2xl">
                        <AvatarFallback class="bg-indigo-100 text-indigo-700 text-5xl font-bold rounded-2xl">
                            {{ staff.name.charAt(0) }}
                        </AvatarFallback>
                    </Avatar>

                    <!-- Info -->
                    <div class="flex-1 mb-2">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-2">{{ staff.name }}</h1>
                                <div class="flex flex-wrap items-center gap-3 text-white/90 md:text-slate-600 md:dark:text-slate-400 font-medium">
                                    <span class="flex items-center gap-1.5 bg-black/20 md:bg-transparent backdrop-blur-sm md:backdrop-blur-none rounded-full px-3 py-1 md:px-0 md:py-0">
                                        <Briefcase class="w-4 h-4" /> {{ staff.staff?.designation || 'Staff Member' }}
                                    </span>
                                    <span class="hidden md:inline text-slate-300">•</span>
                                    <span class="flex items-center gap-1.5 bg-black/20 md:bg-transparent backdrop-blur-sm md:backdrop-blur-none rounded-full px-3 py-1 md:px-0 md:py-0">
                                        <Mail class="w-4 h-4" /> {{ staff.email }}
                                    </span>
                                    <span class="hidden md:inline text-slate-300">•</span>
                                    <span class="flex items-center gap-1.5 bg-black/20 md:bg-transparent backdrop-blur-sm md:backdrop-blur-none rounded-full px-3 py-1 md:px-0 md:py-0">
                                        <Hash class="w-4 h-4" /> {{ staff.staff?.staff_number }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <Button variant="outline" as-child class="md:text-slate-700">
                                    <Link :href="route('admin.staff.edit', staff.id)">
                                        <Pencil class="w-4 h-4 mr-2" /> Edit Profile
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
                
                <!-- Sidebar Info (1/4) -->
                <div class="space-y-6">
                    <Card class="bg-indigo-50/50 dark:bg-indigo-950/10 border-indigo-100 dark:border-indigo-900">
                        <CardHeader>
                            <CardTitle class="text-sm uppercase tracking-wider font-bold text-indigo-900 dark:text-indigo-400">Departmental Info</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Faculty</div>
                                <div class="font-semibold flex items-center gap-2">
                                    <Building class="w-4 h-4 text-indigo-600" />
                                    {{ staff.staff?.department?.faculty?.name || 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Department</div>
                                <div class="font-semibold flex items-center gap-2">
                                    <Building2 class="w-4 h-4 text-orange-600" />
                                    {{ staff.staff?.department?.name || 'Unassigned' }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm uppercase tracking-wider font-bold text-slate-500">System Access</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <Badge v-for="role in staff.roles" :key="role.id" variant="secondary">
                                    {{ formatRoleName(role.name) }}
                                </Badge>
                            </div>
                        </CardContent>
                        <CardFooter class="bg-slate-50 dark:bg-slate-900 border-t p-4">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <ShieldCheck class="w-4 h-4 text-green-600" />
                                Account Active and Secured
                            </div>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Main Content Tabs (3/4) -->
                <div class="xl:col-span-3">
                    <Tabs default-value="overview" class="w-full">
                        <TabsList class="w-full justify-start border-b rounded-none h-12 bg-transparent p-0 mb-6 gap-6">
                            <TabsTrigger value="overview" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-12 px-2 text-base">
                                Overview
                            </TabsTrigger>
                            <TabsTrigger value="academic" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-12 px-2 text-base" v-if="staff.staff?.is_academic">
                                Teaching & Research
                            </TabsTrigger>
                            <TabsTrigger value="activity" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-12 px-2 text-base">
                                Activity Log
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="overview" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- Status Card -->
                                <Card>
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-sm font-medium text-muted-foreground">Employment Status</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-bold">Active</div>
                                        <p class="text-xs text-muted-foreground">Full-time Employee</p>
                                    </CardContent>
                                </Card>
                                <!-- Role Card -->
                                <Card>
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-sm font-medium text-muted-foreground">Primary Role</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-bold">{{ staff.staff?.is_academic ? 'Academic' : 'Non-Academic' }}</div>
                                        <p class="text-xs text-muted-foreground">{{ staff.staff?.designation }}</p>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="academic" class="space-y-6" v-if="staff.staff?.is_academic">
                            
                            <!-- Workload Analysis -->
                             <div class="grid md:grid-cols-3 gap-6">
                                <Card class="md:col-span-1 bg-slate-900 text-white border-0">
                                    <CardHeader>
                                        <CardTitle class="text-lg font-normal text-slate-400">Total Units</CardTitle>
                                        <div class="text-5xl font-bold mt-2 text-white">{{ totalUnits }}</div>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-xs text-slate-400">
                                                <span>Workload Capacity</span>
                                                <span>{{ Math.round(teachingLoadWithPercentage) }}%</span>
                                            </div>
                                            <Progress :model-value="teachingLoadWithPercentage" class="h-2 bg-slate-800" />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card class="md:col-span-2">
                                    <CardHeader>
                                        <CardTitle>Teaching Assignment</CardTitle>
                                        <CardDescription>Courses allocated for the current academic session.</CardDescription>
                                    </CardHeader>
                                    <CardContent class="p-0">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead class="w-[100px]">Code</TableHead>
                                                    <TableHead>Course Title</TableHead>
                                                    <TableHead>Session</TableHead>
                                                    <TableHead class="text-right">Units</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow v-for="allocation in staff.staff?.allocations" :key="allocation.id">
                                                    <TableCell class="font-medium">{{ allocation.course?.code }}</TableCell>
                                                    <TableCell>{{ allocation.course?.title }}</TableCell>
                                                    <TableCell><Badge variant="outline">{{ allocation.session?.name }}</Badge></TableCell>
                                                    <TableCell class="text-right">{{ allocation.course?.unit }}</TableCell>
                                                </TableRow>
                                                <TableRow v-if="!staff.staff?.allocations?.length">
                                                    <TableCell colspan="4" class="h-24 text-center text-muted-foreground">
                                                        No courses assigned yet.
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </CardContent>
                                </Card>
                             </div>

                            <!-- Timetable Section -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <CalendarClock class="w-5 h-5 text-indigo-600" />
                                        Weekly Timetable
                                    </CardTitle>
                                    <CardDescription>Scheduled classes based on allocated courses.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div v-if="!timetable || timetable.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground border rounded-lg border-dashed">
                                        <CalendarClock class="w-10 h-10 mb-3 opacity-20" />
                                        <p class="font-medium">No classes scheduled</p>
                                        <p class="text-xs text-muted-foreground mt-1">Allocated courses have not been added to the timetable for the current session.</p>
                                    </div>
                                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                                        <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']" :key="day" 
                                            class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-100 dark:border-slate-800 overflow-hidden flex flex-col min-h-[200px]"
                                        >
                                            <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-3 border-b border-indigo-100 dark:border-indigo-900 flex items-center justify-between">
                                                <span class="font-bold text-indigo-900 dark:text-indigo-400 uppercase text-xs tracking-wider">{{ day }}</span>
                                                <span class="text-[10px] font-semibold text-indigo-400 dark:text-indigo-300 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full shadow-sm">
                                                    {{ getClassesForDay(day).length }}
                                                </span>
                                            </div>
                                            
                                            <div class="p-2 space-y-2 flex-1">
                                                <div v-if="getClassesForDay(day).length === 0" class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 opacity-60">
                                                    <span class="text-xs">No classes</span>
                                                </div>
                                                
                                                <div v-for="cls in getClassesForDay(day)" :key="cls.id" 
                                                    class="bg-white dark:bg-slate-950 rounded-lg p-3 shadow-sm border-l-4 border-indigo-500 hover:shadow-md transition-all group"
                                                >
                                                    <div class="flex justify-between items-start mb-1">
                                                        <Badge variant="secondary" class="font-mono text-[10px] bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-100">
                                                            {{ formatTime(cls.start_time) }} - {{ formatTime(cls.end_time) }}
                                                        </Badge>
                                                    </div>
                                                    
                                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 text-xs mb-0.5 group-hover:text-indigo-600 transition-colors">
                                                        {{ cls.course.code }}
                                                    </h4>
                                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1 mb-1">{{ cls.course.title }}</p>
                                                    
                                                    <div class="space-y-1 pt-1 border-t border-slate-50 dark:border-slate-900">
                                                        <div class="flex items-center gap-1.5 text-[10px] text-slate-600 dark:text-slate-400">
                                                            <MapPin class="w-2.5 h-2.5 text-slate-400" />
                                                            <span class="font-medium">{{ cls.venue || 'TBA' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="activity">
                             <div class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground border rounded-lg border-dashed">
                                <Clock class="w-10 h-10 mb-4 opacity-20" />
                                <h3 class="font-semibold text-lg">No recent activity</h3>
                                <p class="text-sm max-w-sm">Activity logs including login history and actions will be displayed here.</p>
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
