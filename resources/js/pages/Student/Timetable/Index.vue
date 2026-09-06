<script setup lang="ts">
import { ref } from 'vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    CalendarClock, MapPin, User, BookOpen, Calendar, Clock, 
    Building2, ShieldCheck, ArrowRight, Sparkles, FileText, Lock
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const props = withDefaults(defineProps<{
    student: any;
    timetables: any[];
    examSchedules?: any[];
    isExamPublished?: boolean;
    session: any;
    semester: any;
}>(), {
    examSchedules: () => [],
    isExamPublished: false,
});

const activeTab = ref<'class' | 'exam'>('class');

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const getClassesForDay = (day: string) => {
    return props.timetables.filter(t => t.day === day);
};

const formatTime = (time: string | null) => {
    if (!time) return '---';
    const parts = time.split(':');
    let h = parseInt(parts[0], 10);
    if (isNaN(h)) return time;
    const m = parts[1] || '00';
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    return `${h.toString().padStart(2, '0')}:${m} ${ampm}`;
};

const formatDate = (dateString: string) => {
    if (!dateString) return '---';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="My Timetable" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50/50 pb-20">
            <!-- Header -->
            <div class="bg-indigo-900 pt-20 pb-24 px-6 md:px-10 text-white relative overflow-hidden">
                 <div class="absolute inset-0 bg-grid-white/5 mask-image:linear-gradient(to_bottom,transparent,black)"></div>
                 <div class="relative z-10 max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">My Timetable & Schedules</h1>
                        <p class="text-indigo-200">
                            {{ semester?.name }} • {{ session?.name }} • {{ student.department?.name }} ({{ student.current_level }}L)
                        </p>
                    </div>

                    <!-- Navigation to Docket Pass -->
                    <div class="flex items-center gap-3">
                        <Link href="/student/exam-docket">
                            <Button class="bg-emerald-500 hover:bg-emerald-600 text-white gap-2 font-medium shadow-lg shadow-emerald-900/20">
                                <ShieldCheck class="w-4 h-4" />
                                Exam Docket Pass
                                <ArrowRight class="w-4 h-4" />
                            </Button>
                        </Link>
                    </div>
                 </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 -mt-16 relative z-20 space-y-6">
                <!-- Tab Controls -->
                <div class="flex items-center justify-between bg-white p-2 rounded-xl shadow-md border border-gray-100 max-w-md">
                    <button 
                        @click="activeTab = 'class'"
                        :class="[
                            'flex-1 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all flex items-center justify-center gap-2',
                            activeTab === 'class' ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50'
                        ]"
                    >
                        <CalendarClock class="w-4 h-4" />
                        Class Timetable
                    </button>

                    <button 
                        @click="activeTab = 'exam'"
                        :class="[
                            'flex-1 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all flex items-center justify-center gap-2',
                            activeTab === 'exam' ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50'
                        ]"
                    >
                        <Calendar class="w-4 h-4" />
                        Exam Timetable
                        <Badge v-if="examSchedules?.length" class="ml-1 bg-amber-500 hover:bg-amber-600 text-white text-[10px] px-1.5 py-0.2">
                            {{ examSchedules.length }}
                        </Badge>
                    </button>
                </div>

                <!-- TAB 1: WEEKLY CLASS TIMETABLE -->
                <Card v-if="activeTab === 'class'" class="shadow-xl border-0 ring-1 ring-black/5 bg-white/95 backdrop-blur-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-indigo-950">
                                <CalendarClock class="w-5 h-5 text-indigo-600" />
                                Weekly Class Schedule
                            </CardTitle>
                            <CardDescription>Lecture times and venue allocations for your current level</CardDescription>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                                <div v-for="day in days" :key="day" 
                                    class="bg-gray-50/80 rounded-xl border border-gray-200 overflow-hidden flex flex-col min-h-[320px]"
                                >
                                    <div class="bg-indigo-50/80 p-3 border-b border-indigo-100 flex items-center justify-between">
                                        <span class="font-bold text-indigo-900 uppercase text-xs tracking-wider">{{ day }}</span>
                                        <span class="text-xs font-semibold text-indigo-700 bg-white px-2 py-0.5 rounded-full shadow-xs">
                                            {{ getClassesForDay(day).length }} Classes
                                        </span>
                                    </div>
                                    
                                    <div class="p-3 space-y-3 flex-1">
                                        <div v-if="getClassesForDay(day).length === 0" class="h-full flex flex-col items-center justify-center text-gray-400 opacity-60">
                                            <BookOpen class="w-8 h-8 mb-2 stroke-1" />
                                            <span class="text-xs">No lectures</span>
                                        </div>
                                        
                                        <div v-for="cls in getClassesForDay(day)" :key="cls.id" 
                                            class="bg-white rounded-lg p-3 shadow-xs border border-gray-100 border-l-4 border-l-indigo-500 hover:shadow-md transition-all group"
                                        >
                                            <div class="flex justify-between items-start mb-1.5">
                                                <Badge variant="secondary" class="font-mono text-[10px] bg-indigo-50 text-indigo-700 font-medium">
                                                    {{ formatTime(cls.start_time) }} - {{ formatTime(cls.end_time) }}
                                                </Badge>
                                            </div>
                                            
                                            <h4 class="font-bold text-gray-800 text-sm mb-0.5 group-hover:text-indigo-600 transition-colors">
                                                {{ cls.course?.code }}
                                            </h4>
                                            <p class="text-xs text-gray-500 line-clamp-1 mb-2">{{ cls.course?.title }}</p>
                                            
                                            <div class="space-y-1 pt-2 border-t border-gray-100">
                                                <div class="flex items-center gap-1.5 text-xs text-gray-600">
                                                    <MapPin class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                                    <span class="font-medium text-gray-700">{{ cls.venue || 'TBA' }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-xs text-gray-600">
                                                    <User class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                                    <span class="truncate">{{ cls.course?.allocations?.[0]?.staff?.user?.name || 'Lecturer Unassigned' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                <!-- TAB 2: SEMESTER EXAM TIMETABLE -->
                <div v-if="activeTab === 'exam'" class="space-y-6">
                    <!-- If Exam Timetable is NOT published by Admin -->
                    <div v-if="!isExamPublished" class="py-16 text-center text-gray-500 bg-white rounded-2xl p-8 border border-gray-100 shadow-xl">
                        <div class="w-16 h-16 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-purple-100">
                            <Lock class="w-8 h-8" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Exam Timetable Not Published</h3>
                        <p class="text-sm text-gray-500 max-w-md mx-auto mt-2 leading-relaxed">
                            The examination timetable for this semester has not been made public by the examination office. Examination docket clearance will open once the schedule is released.
                        </p>
                    </div>

                    <!-- If Exam Timetable IS Published -->
                    <template v-else>
                        <!-- Banner Alert -->
                        <div class="bg-gradient-to-r from-indigo-900 via-purple-900 to-indigo-950 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                            <div class="relative z-10 space-y-2">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-xs font-semibold text-indigo-200 border border-white/10">
                                    <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                                    Smart Examination Portal
                                </div>
                                <h2 class="text-xl font-bold">Official Examination Schedule & Clearance Pass</h2>
                                <p class="text-sm text-indigo-200 max-w-2xl">
                                    Ensure you carry your stamped Exam Docket Pass to all exam halls. Verification is verified via biometric or token clearance.
                                </p>
                            </div>
                            <Link href="/student/exam-docket" class="relative z-10 shrink-0">
                                <Button class="bg-white text-indigo-950 hover:bg-indigo-50 font-bold gap-2 shadow-lg">
                                    <FileText class="w-4 h-4 text-indigo-600" />
                                    Access Official Exam Docket
                                </Button>
                            </Link>
                        </div>

                        <!-- Exam List -->
                        <Card class="shadow-xl border-0 ring-1 ring-black/5 bg-white/95 backdrop-blur-sm">
                            <CardHeader class="pb-2">
                                <CardTitle class="flex items-center gap-2 text-indigo-950">
                                    <Calendar class="w-5 h-5 text-indigo-600" />
                                    Registered Course Examination Dates
                                </CardTitle>
                                <CardDescription>Official exam timetable for your registered courses this semester</CardDescription>
                            </CardHeader>
                            <CardContent class="pt-4">
                                <div v-if="!examSchedules || examSchedules.length === 0" class="py-16 text-center text-gray-500">
                                    <Calendar class="w-12 h-12 text-gray-300 mx-auto mb-3 stroke-1" />
                                    <h3 class="text-base font-semibold text-gray-700">No Exam Schedule Published Yet</h3>
                                    <p class="text-sm text-gray-500 max-w-md mx-auto mt-1">
                                        The examination timetable for your registered courses has not been finalized by the examination officer. Check back soon.
                                    </p>
                                </div>

                                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="exam in examSchedules" :key="exam.id" 
                                        class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-lg transition-all space-y-4 group relative overflow-hidden"
                                    >
                                        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-indigo-500/10 to-transparent rounded-bl-full pointer-events-none"></div>

                                        <div class="flex justify-between items-start gap-2">
                                            <div>
                                                <Badge variant="outline" class="bg-indigo-50 text-indigo-700 border-indigo-200 font-mono text-xs mb-1">
                                                    {{ exam.course?.code }}
                                                </Badge>
                                                <h3 class="font-bold text-gray-900 text-base group-hover:text-indigo-600 transition-colors">
                                                    {{ exam.course?.title }}
                                                </h3>
                                            </div>
                                            <Badge class="capitalize bg-emerald-100 text-emerald-800 border-emerald-200 hover:bg-emerald-100 shrink-0">
                                                {{ exam.exam_type || 'Main Exam' }}
                                            </Badge>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100 text-xs">
                                            <div class="flex items-center gap-2 text-gray-700">
                                                <Calendar class="w-4 h-4 text-indigo-600 shrink-0" />
                                                <span class="font-semibold">{{ formatDate(exam.exam_date) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-gray-700">
                                                <Clock class="w-4 h-4 text-amber-600 shrink-0" />
                                                <span class="font-semibold">{{ formatTime(exam.start_time) }} - {{ formatTime(exam.end_time) }}</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2 pt-1 text-xs text-gray-600">
                                            <div class="flex items-center gap-2">
                                                <Building2 class="w-4 h-4 text-gray-400 shrink-0" />
                                                <span>Venue: <strong class="text-gray-800 font-semibold">{{ exam.venue }}</strong></span>
                                            </div>
                                            <div v-if="exam.max_capacity" class="flex items-center gap-2">
                                                <User class="w-4 h-4 text-gray-400 shrink-0" />
                                                <span>Venue Capacity: <strong class="text-gray-800">{{ exam.max_capacity }} Candidates</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </template>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

