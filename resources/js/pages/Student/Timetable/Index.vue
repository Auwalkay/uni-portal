<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    CalendarClock, MapPin, User, BookOpen 
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    student: any;
    timetables: any[];
    session: any;
    semester: any;
}>();

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const hours = Array.from({ length: 11 }, (_, i) => i + 8); // 8am to 6pm

// Helper to check if a class exists for a specific day and hour slot
// This assumes 1-hour slots or simple grid for MVP. 
// A more complex implementation would handle overlapping/variable duration.
const getClassForSlot = (day: string, hour: number) => {
    // Check if any class STARTs at this hour or COVERS this hour
    // For simplicity, let's just check start time matches hour (e.g. 08:00 start matches 8)
    return props.timetables.find(t => {
        const startH = parseInt(t.start_time.split(':')[0]);
        const endH = parseInt(t.end_time.split(':')[0]);
        // If it starts in this hour OR (starts before and ends after)
        // Let's do a simple list view per day instead of complex grid for robustness first?
        // User asked for "timetable module", grid is nice but list is safer to implement quickly without bugs.
        // Let's stick to a Column-per-Day view which is cleaner than a rigid grid.
        return t.day === day; 
    });
};

const getClassesForDay = (day: string) => {
    return props.timetables.filter(t => t.day === day);
};

const formatTime = (time: string) => {
    return time.substring(0, 5);
};

// Breadcrumbs if layout supported it
const breadcrumbs = [
    { title: 'Dashboard', href: '/student/dashboard' },
    { title: 'My Timetable', href: '#' },
];
</script>

<template>
    <Head title="My Timetable" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50/50 pb-20">
            <!-- Header -->
            <div class="bg-indigo-900 pt-20 pb-24 px-6 md:px-10 text-white relative overflow-hidden">
                 <div class="absolute inset-0 bg-grid-white/5 mask-image:linear-gradient(to_bottom,transparent,black)"></div>
                 <div class="relative z-10 max-w-7xl mx-auto">
                    <h1 class="text-3xl font-bold mb-2">My Timetable</h1>
                    <p class="text-indigo-200">
                        {{ semester?.name }} • {{ session?.name }} • {{ student.department?.name }} ({{ student.current_level }}L)
                    </p>
                 </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 -mt-16 relative z-20">
                <Card class="shadow-xl border-0 ring-1 ring-black/5 bg-white/95 backdrop-blur-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2">
                            <CalendarClock class="w-5 h-5 text-indigo-600" />
                            Weekly Schedule
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- Mobile/List View for smaller screens? OR Grid for all? -->
                        <!-- A "Column per Day" Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                            <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']" :key="day" 
                                class="bg-gray-50 rounded-xl border border-gray-100 overflow-hidden flex flex-col min-h-[300px]"
                            >
                                <div class="bg-indigo-50/50 p-3 border-b border-indigo-100 flex items-center justify-between">
                                    <span class="font-bold text-indigo-900 uppercase text-sm tracking-wider">{{ day }}</span>
                                    <span class="text-xs font-semibold text-indigo-400 bg-white px-2 py-0.5 rounded-full shadow-sm">
                                        {{ getClassesForDay(day).length }} Classes
                                    </span>
                                </div>
                                
                                <div class="p-3 space-y-3 flex-1">
                                    <div v-if="getClassesForDay(day).length === 0" class="h-full flex flex-col items-center justify-center text-gray-400 opacity-60">
                                        <span class="text-sm">No classes</span>
                                    </div>
                                    
                                    <div v-for="cls in getClassesForDay(day)" :key="cls.id" 
                                        class="bg-white rounded-lg p-3 shadow-sm border-l-4 border-indigo-500 hover:shadow-md transition-all group"
                                    >
                                        <div class="flex justify-between items-start mb-2">
                                            <Badge variant="secondary" class="font-mono text-[10px] bg-indigo-50 text-indigo-700">
                                                {{ formatTime(cls.start_time) }} - {{ formatTime(cls.end_time) }}
                                            </Badge>
                                        </div>
                                        
                                        <h4 class="font-bold text-gray-800 text-sm mb-0.5 group-hover:text-indigo-600 transition-colors">
                                            {{ cls.course.code }}
                                        </h4>
                                        <p class="text-xs text-gray-500 line-clamp-1 mb-2">{{ cls.course.title }}</p>
                                        
                                        <div class="space-y-1 pt-2 border-t border-gray-50">
                                            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                                                <MapPin class="w-3 h-3 text-gray-400" />
                                                <span class="font-medium">{{ cls.venue || 'TBA' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                                                <User class="w-3 h-3 text-gray-400" />
                                                <span>{{ cls.course.allocations[0]?.staff?.user?.name || 'Lecturer Unassigned' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </StudentLayout>
</template>
