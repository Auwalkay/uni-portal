<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { route } from 'ziggy-js';
import { FileText, PlusCircle, BookOpen, Printer, ChevronRight, GraduationCap, CalendarDays } from 'lucide-vue-next';

const props = defineProps<{
    student: any;
    history: any[];
}>();

const hasCurrentRegistration = computed(() => {
    return props.history.some(h => h.is_current);
});

// Selected Session State
const selectedSessionId = ref<number | null>(null);

// Initialize selected session
onMounted(() => {
    if (props.history.length > 0) {
        const current = props.history.find(h => h.is_current);
        // Default to current session ID if exists, otherwise the first one
        selectedSessionId.value = current ? current.id : props.history[0].id;
    }
});

const selectedSessionRecord = computed(() => {
    return props.history.find(h => h.id === selectedSessionId.value);
});

const selectSession = (sessionId: number) => {
    selectedSessionId.value = sessionId;
};
</script>

<template>
    <Head title="My Courses" />

    <StudentLayout>
        <div class="space-y-6 pb-12 w-full min-h-screen bg-gray-50/30">
             <!-- Header -->
            <div class="relative h-48 md:h-56 rounded-none md:rounded-b-[2rem] bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-800 overflow-hidden shadow-xl border-b border-white/10">
                 <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 brightness-100 mix-blend-overlay"></div>
                 <div class="absolute inset-0 bg-grid-white/5 [mask-image:linear-gradient(0deg,transparent,rgba(255,255,255,0.5))]"></div>
                 
                 <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-black/20 to-transparent"></div>

                 <div class="absolute bottom-8 left-6 md:left-12 text-white z-10 w-full pr-12">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Badge variant="outline" class="text-emerald-50 border-emerald-400/30 bg-emerald-500/20 backdrop-blur-sm px-3 py-1">Academic Records</Badge>
                            </div>
                            <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-white drop-shadow-sm">Course History</h1>
                            <p class="text-emerald-100/90 text-sm md:text-base max-w-xl font-light leading-relaxed">
                                Access your complete academic registration history, view course details, and manage your enrollments.
                            </p>
                        </div>
                         <div class="mb-1 hidden md:block">
                            <Link :href="route('student.courses.create')">
                                <Button size="lg" class="bg-white text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800 border-0 font-semibold shadow-xl shadow-black/10 transition-all hover:scale-105 active:scale-95 px-6">
                                    <PlusCircle v-if="!hasCurrentRegistration" class="mr-2 h-5 w-5" /> 
                                    {{ hasCurrentRegistration ? 'Review Registration' : 'Register Courses' }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                 </div>
            </div>
            
            <!-- Mobile Action Button -->
            <div class="px-4 md:hidden -mt-6 relative z-20">
                 <Link :href="route('student.courses.create')" class="block w-full">
                    <Button size="lg" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg border border-emerald-500/50">
                        <PlusCircle v-if="!hasCurrentRegistration" class="mr-2 h-5 w-5" /> 
                        {{ hasCurrentRegistration ? 'Modify Registration' : 'Register Courses' }}
                    </Button>
                </Link>
            </div>

            <div class="px-4 md:px-12 max-w-7xl mx-auto">
                 <div v-if="history.length === 0" class="text-center py-24 border-2 border-dashed border-gray-200 rounded-2xl bg-white/50 mt-8">
                    <div class="bg-emerald-50 p-6 rounded-full inline-flex mb-6 ring-8 ring-emerald-50/50">
                        <BookOpen class="w-10 h-10 text-emerald-600" />
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">No Records Found</h3>
                    <p class="text-muted-foreground mt-3 mb-8 max-w-md mx-auto">You haven't registered for any courses yet. Start your academic journey by registering for the current session.</p>
                    <Link :href="route('student.courses.create')">
                        <Button size="lg" class="bg-emerald-600 hover:bg-emerald-700">Start Registration</Button>
                    </Link>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 mt-10">
                    <!-- Sidebar Navigation -->
                    <div class="lg:col-span-3 space-y-6">
                        <div class="space-y-3">
                            <h3 class="font-bold text-gray-900 px-3 flex items-center gap-2 text-sm uppercase tracking-wider opacity-70">
                                <History class="w-4 h-4" /> 
                                Sessions
                            </h3>
                            <nav class="space-y-1 relative">
                                <!-- Connecting Line -->
                                <div class="absolute left-[19px] top-4 bottom-4 w-0.5 bg-gray-200 rounded-full z-0 hidden lg:block"></div>

                                <button 
                                    v-for="record in history" 
                                    :key="record.id"
                                    @click="selectSession(record.id)"
                                    class="relative z-10 w-full flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium transition-all duration-300 group outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    :class="selectedSessionId === record.id 
                                        ? 'bg-white text-emerald-700 shadow-md ring-1 ring-emerald-100 translate-x-1 lg:translate-x-0' 
                                        : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900'"
                                >
                                    <div class="flex items-center gap-4">
                                        <div 
                                            class="w-2.5 h-2.5 rounded-full ring-4 ring-white transition-all duration-300 shadow-sm" 
                                            :class="selectedSessionId === record.id 
                                                ? (record.is_current ? 'bg-emerald-500 ring-emerald-100 scale-110' : 'bg-emerald-600 ring-emerald-50 scale-110') 
                                                : (record.is_current ? 'bg-emerald-400/50' : 'bg-gray-300')"
                                        ></div>
                                        <div class="flex flex-col items-start text-left">
                                            <span class="font-bold tracking-tight">{{ record.session }}</span>
                                            <span v-if="record.is_current" class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wide bg-emerald-50 px-1.5 py-0.5 rounded-md mt-0.5">Current</span>
                                        </div>
                                    </div>
                                    <ChevronRight 
                                        class="w-4 h-4 transition-all duration-300 text-emerald-500" 
                                        :class="selectedSessionId === record.id ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-2'"
                                    />
                                </button>
                            </nav>
                        </div>

                         <Card class="bg-gradient-to-b from-emerald-50 to-white border border-emerald-100 shadow-sm mt-6 hidden lg:block overflow-hidden relative">
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-100/50 rounded-full blur-2xl"></div>
                            <CardContent class="p-5 relative z-10">
                                <h4 class="font-bold text-emerald-900 text-sm mb-1.5 flex items-center gap-2">
                                     <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" v-if="!hasCurrentRegistration"></div>
                                    {{ hasCurrentRegistration ? 'Courses Verified' : 'Registration Open' }}
                                </h4>
                                <p class="text-xs text-emerald-700/80 mb-4 leading-relaxed">
                                    {{ hasCurrentRegistration ? 'You have successfully registered for the current session.' : 'Don\'t miss the deadline. Register your courses for this session now.' }}
                                </p>
                                <Link :href="route('student.courses.create')">
                                    <Button size="sm" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm hover:shadow-md transition-all">
                                        {{ hasCurrentRegistration ? 'Modify Selection' : 'Register Now' }}
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Main Content Area -->
                    <div class="lg:col-span-9">
                        <Transition
                            mode="out-in"
                            enter-active-class="transition duration-300 ease-out"
                            enter-from-class="transform opacity-0 translate-y-4"
                            enter-to-class="transform opacity-100 translate-y-0"
                            leave-active-class="transition duration-200 ease-in"
                            leave-from-class="transform opacity-100 translate-y-0"
                            leave-to-class="transform opacity-0 -translate-y-2"
                        >
                            <div v-if="selectedSessionRecord" :key="selectedSessionId || 'empty'" class="space-y-8">
                                 <!-- Session Header Card -->
                                 <div class="relative rounded-2xl border border-gray-100 bg-white shadow-sm p-1 overflow-hidden group">
                                     <div class="absolute top-0 right-0 p-3 opacity-5 pointer-events-none transition-opacity group-hover:opacity-10">
                                        <GraduationCap class="w-32 h-32 rotate-12" />
                                     </div>
                                    
                                     <div class="bg-gray-50/50 rounded-xl p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                                        <div class="space-y-2 relative z-10">
                                            <div class="flex items-center gap-3">
                                                <h2 class="text-3xl font-bold tracking-tight text-gray-900">{{ selectedSessionRecord.session }}</h2>
                                                <Badge v-if="selectedSessionRecord.is_current" variant="default" class="bg-emerald-600 hover:bg-emerald-700 px-3 py-1 text-sm shadow-sm">Active Session</Badge>
                                                <Badge v-else variant="outline" class="bg-white/50 text-gray-500 border-gray-200">Archived</Badge>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-medium text-gray-500">
                                                <span class="flex items-center gap-1.5 transition-colors hover:text-emerald-600">
                                                    <BookOpen class="w-4 h-4" />
                                                    {{ selectedSessionRecord.semesters.reduce((acc: number, s: any) => acc + s.courses.length, 0) }} Courses
                                                </span>
                                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                                <span class="flex items-center gap-1.5 transition-colors hover:text-emerald-600">
                                                    <GraduationCap class="w-4 h-4" />
                                                    {{ selectedSessionRecord.semesters.reduce((acc: number, s: any) => acc + s.total_units, 0) }} Total Units
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 relative z-10">
                                             <a 
                                                v-if="selectedSessionRecord.id"
                                                :href="route('student.courses.form', { session_id: selectedSessionRecord.id })" 
                                                target="_blank"
                                             > 
                                                <Button variant="outline" class="gap-2 border-gray-200 hover:bg-white hover:border-emerald-200 hover:text-emerald-700 shadow-sm transition-all h-10 px-4 group/btn bg-white">
                                                    <Printer class="w-4 h-4 text-gray-400 group-hover/btn:text-emerald-500 transition-colors" />
                                                    Print Form
                                                </Button>
                                             </a>
                                        </div>
                                    </div>
                                </div>

                                 <!-- Semester Cards -->
                                 <div class="space-y-8">
                                    <div v-for="semester in selectedSessionRecord.semesters" :key="semester.id" class="space-y-4">
                                         <div class="flex items-end justify-between px-2 pb-2 border-b border-gray-100">
                                            <h3 class="text-xl font-bold flex items-center gap-3 text-gray-800">
                                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                                    <CalendarDays class="h-4 w-4" />
                                                </span>
                                                {{ semester.name }} Semester
                                            </h3>
                                            <Badge variant="secondary" class="font-mono text-xs bg-gray-100/80 text-gray-600 hover:bg-gray-100 border-0">
                                                {{ semester.total_units }} Credit Units
                                            </Badge>
                                        </div>

                                        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden ring-1 ring-black/[0.02]">
                                            <Table>
                                                <TableHeader class="bg-gray-50/80 backdrop-blur-sm border-b border-gray-100">
                                                    <TableRow class="hover:bg-transparent border-0">
                                                        <TableHead class="w-[120px] font-bold text-gray-600 uppercase text-[11px] tracking-wider py-4 pl-6">Code</TableHead>
                                                        <TableHead class="font-bold text-gray-600 uppercase text-[11px] tracking-wider py-4">Course Title</TableHead>
                                                        <TableHead class="w-[100px] text-center font-bold text-gray-600 uppercase text-[11px] tracking-wider py-4">Units</TableHead>
                                                        <TableHead class="w-[120px] font-bold text-gray-600 uppercase text-[11px] tracking-wider py-4">Type</TableHead>
                                                        <TableHead class="w-[100px] text-center font-bold text-gray-600 uppercase text-[11px] tracking-wider py-4 pr-6">Level</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    <TableRow v-for="course in semester.courses" :key="course.id" class="hover:bg-emerald-50/30 transition-colors border-b border-gray-50 last:border-0 group">
                                                        <TableCell class="font-mono font-semibold text-emerald-700 py-4 pl-6 group-hover:text-emerald-800">{{ course.code }}</TableCell>
                                                        <TableCell class="py-4 font-medium text-gray-700 group-hover:text-gray-900">{{ course.title }}</TableCell>
                                                        <TableCell class="text-center py-4 text-gray-600 font-medium">{{ course.units }}</TableCell>
                                                        <TableCell class="py-4">
                                                            <Badge 
                                                                :variant="course.is_compulsory ? 'default' : 'secondary'" 
                                                                class="text-[10px] h-6 px-2.5 font-semibold tracking-wide border shadow-none"
                                                                :class="course.is_compulsory ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'"
                                                            >
                                                                {{ course.is_compulsory ? 'CORE' : 'ELECTIVE' }}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell class="text-center text-muted-foreground py-4 pr-6">{{ course.level }}</TableCell>
                                                    </TableRow>
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </div>
                                 </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
