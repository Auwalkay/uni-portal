<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { 
    Calendar, Clock, Building, ShieldCheck, ShieldAlert, Printer, 
    CheckCircle2, XCircle, FileText, User, Info, AlertTriangle, QrCode, Lock
} from 'lucide-vue-next';
import { format } from 'date-fns';

interface Props {
    student: {
        id: string;
        name: string;
        email: string;
        matric_number: string;
        department: string;
        programme: string;
        level: string;
        passport_photo: string | null;
    };
    session: any;
    semester: any;
    schedules: any[];
    clearance: {
        is_cleared: boolean;
        fee_cleared: boolean;
        course_reg_cleared: boolean;
        registered_courses_count: number;
        pending_invoices_count: number;
    };
    verificationQrData: string;
    docketToken: string;
    isPublished?: boolean;
}

const props = defineProps<Props>();

const printDocket = () => {
    window.print();
};
</script>

<template>
    <Head title="Examination Clearance & Docket Pass" />

    <StudentLayout>
        <div class="p-6 space-y-6 w-full max-w-6xl mx-auto print:p-0 print:m-0 print:max-w-none">
            
            <!-- If Exam Timetable is NOT published by Admin -->
            <div v-if="!isPublished" class="py-16 text-center text-slate-500 bg-white dark:bg-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-800 shadow-xl print:hidden space-y-4">
                <div class="w-16 h-16 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-full flex items-center justify-center mx-auto border border-purple-100 dark:border-purple-900/40">
                    <Lock class="w-8 h-8" />
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-50">Exam Schedule Not Published</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                    The official semester examination timetable has not been made public by the examination management yet. Official docket clearance pass printing will open once the schedule is released.
                </p>
            </div>

            <template v-else>
                <!-- Header (Hidden on Print) -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <div class="p-2 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                <FileText class="w-6 h-6" />
                            </div>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                                Exam Clearance & Docket Pass
                            </h1>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 pl-10">
                            View examination schedules, clearance status, and generate official exam permit.
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <Button 
                            v-if="clearance.is_cleared" 
                            class="bg-purple-600 hover:bg-purple-700 text-white shadow-sm font-semibold gap-2"
                            @click="printDocket"
                        >
                            <Printer class="w-4 h-4" />
                            <span>Print Exam Docket</span>
                        </Button>
                    </div>
                </div>

            <!-- Clearance Status Banner (Hidden on Print) -->
            <Card :class="[
                'border shadow-sm p-5 print:hidden',
                clearance.is_cleared ? 'bg-emerald-50/60 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-900/40' : 'bg-rose-50/60 border-rose-200 dark:bg-rose-950/30 dark:border-rose-900/40'
            ]">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div :class="[
                            'p-3 rounded-xl shrink-0',
                            clearance.is_cleared ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300'
                        ]">
                            <ShieldCheck v-if="clearance.is_cleared" class="w-7 h-7" />
                            <ShieldAlert v-else class="w-7 h-7" />
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                                    {{ clearance.is_cleared ? 'OFFICIALLY CLEARED FOR EXAMINATIONS' : 'EXAM CLEARANCE PENDING' }}
                                </h2>
                                <Badge :class="[
                                    'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider',
                                    clearance.is_cleared ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'
                                ]">
                                    {{ clearance.is_cleared ? 'CLEARED' : 'ACTION REQUIRED' }}
                                </Badge>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300">
                                {{ clearance.is_cleared 
                                    ? 'You have fulfilled all financial obligations and course registration requirements for this academic session.' 
                                    : 'Please resolve pending fee payments or complete course registration to enable exam permit generation.' 
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Checklist -->
                    <div class="flex flex-col gap-1.5 text-xs font-medium border-l pl-4 border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <CheckCircle2 v-if="clearance.fee_cleared" class="w-4 h-4 text-emerald-600" />
                            <XCircle v-else class="w-4 h-4 text-rose-600" />
                            <span>Financial Fees Cleared</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <CheckCircle2 v-if="clearance.course_reg_cleared" class="w-4 h-4 text-emerald-600" />
                            <XCircle v-else class="w-4 h-4 text-rose-600" />
                            <span>Course Registration Verified ({{ clearance.registered_courses_count }} courses)</span>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Printable Official Exam Permit / Docket Card -->
            <Card class="border-2 border-slate-300 shadow-md bg-white dark:bg-slate-900 overflow-hidden print:border-none print:shadow-none">
                
                <!-- Docket Header -->
                <div class="bg-gradient-to-r from-purple-900 to-indigo-900 text-white p-6 border-b flex items-center justify-between print:bg-white print:text-black print:p-0 print:border-b-2 print:border-black">
                    <div class="space-y-1">
                        <span class="text-xs font-bold tracking-widest uppercase text-purple-200 print:text-slate-600">Official Examination Pass</span>
                        <h2 class="text-2xl font-black tracking-tight">EXAMINATION DOCKET / PERMIT</h2>
                        <p class="text-xs text-purple-200 print:text-slate-700">
                            Session: <strong>{{ session?.name || 'Academic Session' }}</strong> | Semester: <strong>{{ semester?.name || 'Semester' }}</strong>
                        </p>
                    </div>

                    <!-- Security Pass Token Badge -->
                    <div class="text-right flex flex-col items-end">
                        <div class="p-2 bg-white/10 dark:bg-slate-800 rounded-lg border border-white/20 text-center font-mono text-xs font-bold print:border-black print:text-black">
                            <span class="text-[9px] uppercase tracking-wider block text-purple-300 print:text-slate-600">Pass Security Token</span>
                            <span>{{ docketToken.substring(0, 12) }}</span>
                        </div>
                    </div>
                </div>

                <CardContent class="p-6 space-y-6">
                    <!-- Student Bio Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-4 bg-slate-50 dark:bg-slate-950/40 rounded-xl border print:bg-white print:border-black">
                        <!-- Passport Photo -->
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="w-28 h-32 border-2 border-slate-300 rounded-lg overflow-hidden bg-slate-200 dark:bg-slate-800 flex items-center justify-center">
                                <img v-if="student.passport_photo" :src="student.passport_photo" alt="Student Passport" class="w-full h-full object-cover" />
                                <User v-else class="w-12 h-12 text-slate-400" />
                            </div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Candidate Photo</span>
                        </div>

                        <!-- Bio Info -->
                        <div class="md:col-span-3 grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 uppercase font-bold text-[10px] block">Full Candidate Name</span>
                                <span class="font-bold text-slate-900 dark:text-slate-100 text-sm block">{{ student.name }}</span>
                            </div>

                            <div>
                                <span class="text-slate-400 uppercase font-bold text-[10px] block">Matriculation / ID Number</span>
                                <span class="font-mono font-bold text-purple-700 dark:text-purple-400 text-sm block print:text-black">{{ student.matric_number }}</span>
                            </div>

                            <div>
                                <span class="text-slate-400 uppercase font-bold text-[10px] block">Faculty / Department</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 block">{{ student.department }}</span>
                            </div>

                            <div>
                                <span class="text-slate-400 uppercase font-bold text-[10px] block">Academic Programme & Level</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 block">{{ student.programme }} ({{ student.level }} Level)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Timetable Schedule Table -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-purple-600" /> Registered Examination Timetable
                            </h3>
                            <span class="text-xs text-slate-500 font-medium">Total Courses: {{ schedules.length }}</span>
                        </div>

                        <Table class="border rounded-lg overflow-hidden">
                            <TableHeader class="bg-slate-100 dark:bg-slate-950/60 print:bg-slate-200">
                                <TableRow>
                                    <TableHead class="font-bold py-3 text-xs uppercase text-slate-800">Course Code</TableHead>
                                    <TableHead class="font-bold py-3 text-xs uppercase text-slate-800">Course Title</TableHead>
                                    <TableHead class="font-bold py-3 text-xs uppercase text-slate-800">Date & Time</TableHead>
                                    <TableHead class="font-bold py-3 text-xs uppercase text-slate-800">Exam Venue</TableHead>
                                    <TableHead class="font-bold py-3 text-xs uppercase text-slate-800">Type</TableHead>
                                    <TableHead class="font-bold py-3 pr-4 text-right text-xs uppercase text-slate-800">Invigilator Sign</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="sched in schedules" :key="sched.id" class="border-b">
                                    <TableCell class="font-mono text-xs font-bold text-purple-700 dark:text-purple-400 print:text-black">
                                        {{ sched.course?.code }}
                                    </TableCell>
                                    <TableCell class="font-semibold text-xs text-slate-800 dark:text-slate-200">
                                        {{ sched.course?.title }}
                                    </TableCell>
                                    <TableCell class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                        {{ format(new Date(sched.exam_date), 'MMM dd, yyyy') }} ({{ sched.start_time }} - {{ sched.end_time }})
                                    </TableCell>
                                    <TableCell class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        {{ sched.venue }}
                                    </TableCell>
                                    <TableCell class="text-xs uppercase font-bold text-slate-600">
                                        {{ sched.exam_type }}
                                    </TableCell>
                                    <TableCell class="text-right pr-4">
                                        <div class="h-6 w-24 border-b border-dashed border-slate-400 ml-auto"></div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="schedules.length === 0">
                                    <TableCell colspan="6" class="h-24 text-center text-slate-400 font-medium">
                                        No scheduled exams found for your registered courses yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Instructions & Candidate Declaration -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t text-xs">
                        <div class="md:col-span-2 space-y-1 text-slate-600 dark:text-slate-400">
                            <span class="font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 block">Examination Rules & Guidelines</span>
                            <ol class="list-decimal pl-4 space-y-0.5 text-[11px]">
                                <li>Candidates must present this docket along with a valid Student ID Card at hall entrance.</li>
                                <li>No electronic devices, smartwatches, or unauthorized printed materials allowed.</li>
                                <li>Invigilator signature must be obtained for each exam session attended.</li>
                            </ol>
                        </div>

                        <!-- Signatures & Verification -->
                        <div class="space-y-3 text-right flex flex-col justify-end">
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold uppercase block">Registrar / Dean Signature</span>
                                <div class="h-8 border-b border-slate-400 mt-1"></div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
            </template>

        </div>
    </StudentLayout>
</template>
