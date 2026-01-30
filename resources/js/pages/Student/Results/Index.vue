<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import { ChevronRight, GraduationCap, Trophy, BookOpen, Calendar, Clock } from 'lucide-vue-next';

const props = defineProps<{
    cgpa: number;
    history: Array<{
        id: string;
        name: string;
        is_current: boolean;
        semesters: Array<{
            name: string;
            gpa: number;
            courses: Array<{
                id: string;
                score: number;
                grade: string;
                grade_point: string;
                course: {
                    id: string;
                    code: string;
                    title: string;
                    units: number;
                };
            }>;
        }>;
    }>;
}>();

const selectedSessionId = ref<string | null>(null);

const selectedSession = computed(() => {
    return props.history.find(h => h.id === selectedSessionId.value);
});

onMounted(() => {
    if (props.history.length > 0) {
        selectedSessionId.value = props.history[0].id;
    }
});

const selectSession = (id: string) => {
    selectedSessionId.value = id;
};

// Helper for grade color
const getGradeColor = (grade: string) => {
    switch (grade) {
        case 'A': return 'text-emerald-600 font-bold';
        case 'B': return 'text-blue-600 font-bold';
        case 'C': return 'text-yellow-600 font-bold';
        case 'D': return 'text-orange-600 font-bold';
        case 'F': return 'text-red-600 font-bold';
        default: return 'text-gray-600';
    }
};

const getScoreColor = (score: number) => {
    if (score >= 70) return 'text-emerald-600 bg-emerald-50';
    if (score >= 60) return 'text-blue-600 bg-blue-50';
    if (score >= 50) return 'text-yellow-600 bg-yellow-50';
    if (score >= 45) return 'text-orange-600 bg-orange-50';
    return 'text-red-600 bg-red-50';
};
</script>

<template>
    <Head title="My Results" />

    <StudentLayout>
        <div class="space-y-6 p-6 md:p-8 max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b pb-6">
                <div>
                    <h2 class="text-3xl font-serif font-bold tracking-tight text-gray-900 flex items-center gap-3">
                        <GraduationCap class="h-8 w-8 text-primary" />
                        Academic Results
                    </h2>
                    <p class="text-muted-foreground mt-2 text-lg">Detailed performance breakdown across all sessions.</p>
                </div>
                
                <Card class="bg-gradient-to-br from-gray-900 to-gray-800 text-white border-0 shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                         <Trophy class="w-24 h-24" />
                    </div>
                    <CardContent class="p-5 flex items-center gap-6 relative z-10">
                        <div class="bg-white/10 p-3 rounded-full backdrop-blur-sm">
                            <Trophy class="w-8 h-8 text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-300 uppercase tracking-wider mb-1">Cumulative GPA</p>
                            <p class="text-4xl font-mono font-bold text-white tracking-tight">{{ cgpa.toFixed(2) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-if="history.length === 0" class="flex flex-col items-center justify-center p-12 text-center border-2 border-dashed rounded-xl bg-muted/10">
                <BookOpen class="w-12 h-12 text-muted-foreground mb-4 opacity-50" />
                <h3 class="text-lg font-semibold text-gray-900">No Results Available</h3>
                <p class="text-muted-foreground mt-1 max-w-sm">Results typically appear here after the semester exams are concluded and approved.</p>
            </div>

            <!-- Main Layout with Sidebar -->
            <div v-else class="grid lg:grid-cols-12 gap-8">
                
                <!-- Sidebar (Sessions) -->
                <div class="lg:col-span-3 space-y-4">
                    <h3 class="font-semibold text-gray-900 flex items-center gap-2 mb-4 px-1">
                        <Calendar class="w-4 h-4 text-primary" />
                        Academic Sessions
                    </h3>
                    <div class="space-y-2 relative">
                        <!-- Timeline Line (Visual) -->
                        <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-gray-200 hidden md:block"></div>

                        <button 
                            v-for="session in history" 
                            :key="session.id"
                            @click="selectSession(session.id)"
                            class="relative w-full text-left group transition-all duration-200"
                        >
                            <div 
                                class="flex items-center gap-3 p-3 rounded-lg border-2 transition-all duration-200 z-10 relative"
                                :class="selectedSessionId === session.id 
                                    ? 'bg-white border-primary shadow-md translate-x-1' 
                                    : 'bg-gray-50 border-transparent hover:bg-white hover:border-gray-200'"
                            >
                                <div 
                                    class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 border-2 text-xs font-bold transition-colors"
                                    :class="selectedSessionId === session.id 
                                        ? 'bg-primary border-primary text-white' 
                                        : 'bg-white border-gray-300 text-gray-500 group-hover:border-primary/50'"
                                >
                                    {{ session.name.substring(2,4) }}
                                </div>
                                
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-sm" :class="selectedSessionId === session.id ? 'text-gray-900' : 'text-gray-600'">
                                            {{ session.name }}
                                        </span>
                                        <Badge v-if="session.is_current" variant="default" class="text-[10px] h-5 px-1.5 bg-emerald-600">Current</Badge>
                                    </div>
                                    <span class="text-xs text-muted-foreground block mt-0.5">
                                        {{ session.semesters.length }} Semesters
                                    </span>
                                </div>
                                
                                <ChevronRight 
                                    class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                                    :class="{ 'opacity-100 text-primary': selectedSessionId === session.id }"
                                />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Right Content (Details) -->
                <div class="lg:col-span-9 space-y-8 animate-in fade-in slide-in-from-right-4 duration-500">
                    
                    <div v-if="selectedSession">
                        <!-- Session Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                    {{ selectedSession.name }} Session
                                    <Badge variant="outline" class="font-normal text-muted-foreground border-gray-300">
                                        Results View
                                    </Badge>
                                </h3>
                            </div>
                        </div>

                        <!-- Semesters Loop -->
                         <div class="space-y-8">
                            <div v-for="semester in selectedSession.semesters" :key="semester.name" 
                                class="rounded-xl border bg-white shadow-sm overflow-hidden"
                            >
                                <!-- Semester Header -->
                                <div class="bg-gray-50/50 border-b p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                     <div class="flex items-center gap-3">
                                        <div class="p-2 bg-white rounded-lg border shadow-sm">
                                            <BookOpen class="w-5 h-5 text-gray-600" />
                                        </div>
                                        <h4 class="text-lg font-bold text-gray-900">{{ semester.name }}</h4>
                                     </div>
                                     
                                     <div class="flex items-center gap-4 bg-white px-4 py-2 rounded-full border shadow-sm">
                                         <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Semester GPA</span>
                                         <span class="text-xl font-mono font-bold" :class="semester.gpa >= 3.5 ? 'text-emerald-600' : (semester.gpa >= 2.5 ? 'text-blue-600' : 'text-orange-600')">
                                            {{ semester.gpa.toFixed(2) }}
                                         </span>
                                     </div>
                                </div>

                                <!-- Table -->
                                <div class="p-0">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="hover:bg-transparent bg-gray-50/30">
                                                <TableHead class="w-[100px] font-bold">Code</TableHead>
                                                <TableHead class="font-bold">Course Title</TableHead>
                                                <TableHead class="text-center w-[80px] font-bold">Units</TableHead>
                                                <TableHead class="text-center w-[100px] font-bold">Score</TableHead>
                                                <TableHead class="text-center w-[80px] font-bold">Grade</TableHead>
                                                <TableHead class="text-center w-[80px] font-bold">Points</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="reg in semester.courses" :key="reg.id" class="hover:bg-gray-50/50 transition-colors">
                                                <TableCell class="font-mono font-semibold text-primary">
                                                    {{ reg.course.code }}
                                                </TableCell>
                                                <TableCell class="font-medium text-gray-700">
                                                    {{ reg.course.title }}
                                                </TableCell>
                                                <TableCell class="text-center font-mono text-gray-500">
                                                    {{ reg.course.units }}
                                                </TableCell>
                                                <TableCell class="text-center">
                                                    <span class="inline-block w-12 py-1 rounded-md text-xs font-bold font-mono" :class="getScoreColor(reg.score)">
                                                        {{ reg.score ?? '-' }}
                                                    </span>
                                                </TableCell>
                                                <TableCell class="text-center font-bold font-mono text-lg">
                                                    <span :class="getGradeColor(reg.grade)">
                                                        {{ reg.grade ?? '-' }}
                                                    </span>
                                                </TableCell>
                                                <TableCell class="text-center font-mono text-gray-600">
                                                    {{ reg.grade_point ?? '-' }}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                         </div>

                    </div>

                </div>
            </div>
        </div>
    </StudentLayout>
</template>
