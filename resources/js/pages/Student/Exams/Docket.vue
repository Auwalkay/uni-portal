<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { 
    Calendar, ShieldCheck, ShieldAlert, Printer, Download,
    CheckCircle2, XCircle, FileText, User, Lock, QrCode
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
        gender?: string;
        passport_photo: string | null;
    };
    session: any;
    semester: any;
    semesters?: any[];
    schedules: any[];
    clearance: {
        is_cleared: boolean;
        fee_cleared: boolean;
        course_reg_cleared: boolean;
        registered_courses_count: number;
        pending_invoices_count: number;
    };
    verificationQrData: string;
    qrCodeUrl?: string;
    docketToken: string;
    isPublished?: boolean;
}

const props = defineProps<Props>();

const switchSemester = (semesterId: string) => {
    router.get(route('student.docket.index'), { semester_id: semesterId }, { preserveScroll: true, replace: true });
};

const printDocket = () => {
    window.print();
};

const downloadPdfCard = () => {
    const params = new URLSearchParams();
    if (props.semester?.id) params.append('semester_id', props.semester.id);
    if (props.session?.id) params.append('session_id', props.session.id);
    window.location.href = route('student.courses.exam_card') + '?' + params.toString();
};
</script>

<template>
    <Head title="Examination Clearance & Docket Pass" />

    <StudentLayout>
        <div class="p-6 space-y-6 w-full max-w-6xl mx-auto print:p-0 print:m-0 print:max-w-none print:w-full">
            
            <!-- If Exam Timetable is NOT published by Admin -->
            <div v-if="!isPublished" class="py-16 text-center text-slate-500 bg-white dark:bg-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-800 shadow-xl print:hidden space-y-4">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-full flex items-center justify-center mx-auto border border-slate-200 dark:border-slate-700">
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
                            <div class="p-2 rounded-xl bg-[#000a29]/10 text-[#000a29] dark:bg-white/10 dark:text-white">
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
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <select 
                            v-if="semesters && semesters.length > 1"
                            :value="semester?.id"
                            @change="switchSemester(($event.target as HTMLSelectElement).value)"
                            class="text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-800 dark:text-slate-200 shadow-sm focus:ring-2 focus:ring-[#000a29]"
                        >
                            <option v-for="sem in semesters" :key="sem.id" :value="sem.id">
                                {{ sem.name }} {{ sem.is_current ? '(Current Active)' : '' }}
                            </option>
                        </select>

                        <Button 
                            v-if="clearance.is_cleared" 
                            variant="outline"
                            class="border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold gap-2"
                            @click="downloadPdfCard"
                        >
                            <Download class="w-4 h-4" />
                            <span>Download PDF</span>
                        </Button>

                        <Button 
                            v-if="clearance.is_cleared" 
                            class="bg-[#000a29] hover:bg-[#000a29]/90 text-white shadow-sm font-semibold gap-2"
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
            <div class="docket-printable-container border-2 border-slate-900 shadow-xl bg-white dark:bg-slate-900 rounded-xl overflow-hidden print:rounded-none print:border-2 print:border-black print:shadow-none print:m-0">
                
                <!-- Docket Header -->
                <div class="bg-[#000a29] text-white p-6 border-b-4 border-amber-400 flex items-center justify-between print:bg-white print:text-black print:p-4 print:border-b-2 print:border-black">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white p-1.5 rounded-xl shadow-md border border-slate-200 shrink-0 flex items-center justify-center">
                            <img src="/miu-logo.png" alt="Mewar International University Logo" class="max-h-full max-w-full object-contain" />
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-[11px] font-extrabold tracking-widest uppercase text-amber-400 print:text-slate-800 block">Mewar International University Nigeria</span>
                            <h2 class="text-xl md:text-2xl font-black tracking-tight text-white print:text-black uppercase">OFFICIAL EXAMINATION DOCKET / PERMIT</h2>
                            <p class="text-xs text-slate-300 print:text-slate-700 font-semibold">
                                Session: <strong class="text-white print:text-black">{{ session?.name || 'Academic Session' }}</strong> &nbsp;|&nbsp; Semester: <strong class="text-white print:text-black">{{ semester?.name || 'Semester' }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Security Pass Token Badge -->
                    <div class="text-right flex flex-col items-end shrink-0">
                        <div class="p-2 bg-white/10 dark:bg-slate-800 rounded-lg border border-white/20 text-center font-mono text-xs font-bold print:border-black print:bg-slate-100 print:text-black">
                            <span class="text-[9px] uppercase tracking-wider block text-amber-300 print:text-slate-700">Security Pass Token</span>
                            <span class="tracking-wider text-sm">{{ docketToken.substring(0, 12) }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6 print:p-4 print:space-y-4">
                    <!-- Student Bio Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-4 bg-slate-50 dark:bg-slate-950/40 rounded-xl border border-slate-200 dark:border-slate-800 print:bg-white print:border-black print:p-3 print:gap-4">
                        <!-- Passport Photo -->
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="w-28 h-32 border-2 border-[#000a29] rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center shadow-sm print:border-black">
                                <img v-if="student.passport_photo" :src="student.passport_photo" alt="Student Passport" class="w-full h-full object-cover" />
                                <User v-else class="w-12 h-12 text-slate-400" />
                            </div>
                            <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider print:text-slate-800">CANDIDATE PHOTO</span>
                        </div>

                        <!-- Bio Info -->
                        <div class="md:col-span-3 grid grid-cols-2 gap-4 text-xs">
                            <div class="border-b pb-2 border-slate-200 dark:border-slate-800 print:border-slate-300">
                                <span class="text-slate-500 uppercase font-bold text-[10px] block print:text-slate-600">Full Candidate Name</span>
                                <span class="font-bold text-slate-900 dark:text-slate-100 text-sm block print:text-black uppercase">{{ student.name }}</span>
                            </div>

                            <div class="border-b pb-2 border-slate-200 dark:border-slate-800 print:border-slate-300">
                                <span class="text-slate-500 uppercase font-bold text-[10px] block print:text-slate-600">Matriculation / ID Number</span>
                                <span class="font-mono font-bold text-[#000a29] dark:text-slate-200 text-sm block print:text-black">{{ student.matric_number }}</span>
                            </div>

                            <div>
                                <span class="text-slate-500 uppercase font-bold text-[10px] block print:text-slate-600">Faculty / Department</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 block print:text-black">{{ student.department }}</span>
                            </div>

                            <div>
                                <span class="text-slate-500 uppercase font-bold text-[10px] block print:text-slate-600">Academic Programme & Level</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 block print:text-black">{{ student.programme }} ({{ student.level }} Level {{ student.gender ? ' • ' + student.gender : '' }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Timetable Schedule Table -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-slate-100 flex items-center gap-2 print:text-black">
                                <Calendar class="w-4 h-4 text-[#000a29] print:hidden" /> Registered Examination Timetable
                            </h3>
                            <span class="text-xs text-slate-600 font-bold print:text-black">Total Registered Courses: {{ schedules.length }}</span>
                        </div>

                        <Table class="border border-slate-300 rounded-lg overflow-hidden print:border-black">
                            <TableHeader class="bg-slate-100 dark:bg-slate-900 print:bg-slate-200">
                                <TableRow class="border-b-2 border-slate-300 print:border-black">
                                    <TableHead class="font-bold py-2.5 px-3 text-xs uppercase text-slate-900 print:text-black w-10 text-center">S/N</TableHead>
                                    <TableHead class="font-bold py-2.5 px-3 text-xs uppercase text-slate-900 print:text-black w-24">Course Code</TableHead>
                                    <TableHead class="font-bold py-2.5 px-3 text-xs uppercase text-slate-900 print:text-black">Course Title</TableHead>
                                    <TableHead class="font-bold py-2.5 px-3 text-xs uppercase text-slate-900 print:text-black w-12 text-center">Units</TableHead>
                                    <TableHead class="font-bold py-2.5 px-3 text-xs uppercase text-slate-900 print:text-black w-44">Date & Time</TableHead>
                                    <TableHead class="font-bold py-2.5 px-3 text-xs uppercase text-slate-900 print:text-black w-24">Venue</TableHead>
                                    <TableHead class="font-bold py-2.5 px-3 text-xs uppercase text-slate-900 print:text-black w-20">Type</TableHead>
                                    <TableHead class="font-bold py-2.5 pr-4 text-right text-xs uppercase text-slate-900 print:text-black w-32">Invigilator Sign</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(sched, idx) in schedules" :key="sched.id" class="border-b border-slate-200 dark:border-slate-800 print:border-slate-400">
                                    <TableCell class="text-center font-bold text-xs text-slate-600 print:text-black">
                                        {{ idx + 1 }}
                                    </TableCell>
                                    <TableCell class="font-mono text-xs font-bold text-[#000a29] dark:text-slate-200 print:text-black">
                                        {{ sched.course?.code }}
                                    </TableCell>
                                    <TableCell class="font-semibold text-xs text-slate-800 dark:text-slate-200 print:text-black">
                                        {{ sched.course?.title }}
                                    </TableCell>
                                    <TableCell class="text-center font-bold text-xs text-slate-700 print:text-black">
                                        {{ sched.course?.units ?? '-' }}
                                    </TableCell>
                                    <TableCell class="text-xs font-medium text-slate-700 dark:text-slate-300 print:text-black">
                                        {{ sched.exam_date ? (format(new Date(sched.exam_date), 'MMM dd, yyyy') + (sched.start_time ? ' (' + sched.start_time + ' - ' + sched.end_time + ')' : '')) : 'TBA' }}
                                    </TableCell>
                                    <TableCell class="text-xs font-bold text-slate-800 dark:text-slate-200 print:text-black">
                                        {{ sched.venue || 'TBA' }}
                                    </TableCell>
                                    <TableCell class="text-xs uppercase font-bold text-slate-700 print:text-black">
                                        {{ sched.exam_type }}
                                    </TableCell>
                                    <TableCell class="text-right pr-4">
                                        <div class="h-6 w-24 border-b border-dashed border-slate-400 ml-auto print:border-black"></div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="schedules.length === 0">
                                    <TableCell colspan="8" class="h-20 text-center text-slate-400 font-medium">
                                        No scheduled exams found for your registered courses yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Instructions & Security Verification Footer -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-4 border-t-2 border-slate-200 dark:border-slate-800 print:border-black text-xs">
                        <div class="md:col-span-3 space-y-2 text-slate-700 dark:text-slate-300 print:text-black">
                            <span class="font-bold uppercase tracking-wider text-slate-900 dark:text-slate-100 block print:text-black text-[11px]">Examination Regulations & Candidate Rules</span>
                            <ol class="list-decimal pl-4 space-y-1 text-[11px] print:text-[10px] leading-relaxed">
                                <li>Candidates must present this docket along with a valid Student ID Card at the examination hall entrance.</li>
                                <li>No mobile phones, smartwatches, or unauthorized printed materials are permitted inside the examination hall.</li>
                                <li>Invigilator signature must be obtained for each course paper attended. Any unauthorized alteration voids this permit.</li>
                            </ol>

                            <!-- Signatures Grid -->
                            <div class="grid grid-cols-3 gap-4 pt-6 text-center">
                                <div>
                                    <div class="h-8 border-b border-slate-600 print:border-black"></div>
                                    <span class="text-[9px] text-slate-600 font-bold uppercase block mt-1 print:text-black">Candidate Signature</span>
                                </div>
                                <div>
                                    <div class="h-8 border-b border-slate-600 print:border-black"></div>
                                    <span class="text-[9px] text-slate-600 font-bold uppercase block mt-1 print:text-black">Registrar / Exams Officer</span>
                                </div>
                                <div>
                                    <div class="h-8 border-b border-slate-600 print:border-black"></div>
                                    <span class="text-[9px] text-slate-600 font-bold uppercase block mt-1 print:text-black">Faculty Stamp</span>
                                </div>
                            </div>
                        </div>

                        <!-- Verification QR Code Box -->
                        <div class="flex flex-col items-center justify-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 print:bg-white print:border-black">
                            <img :src="qrCodeUrl || ('https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(verificationQrData))" alt="Security Verification QR Code" class="w-24 h-24 object-contain mb-1" />
                            <span class="text-[9px] font-extrabold text-[#000a29] uppercase tracking-wider text-center print:text-black">SCAN TO VERIFY</span>
                            <span class="text-[8px] font-mono text-slate-500 font-semibold print:text-slate-800 text-center">{{ docketToken.substring(0, 10) }}</span>
                        </div>
                    </div>

                    <!-- Footer Warning -->
                    <div class="pt-2 text-center text-[10px] text-slate-400 print:text-slate-600 border-t border-slate-100 dark:border-slate-800 print:border-slate-300">
                        Official Examination Docket Pass • Mewar International University Nigeria • Issued on {{ format(new Date(), 'MMMM dd, yyyy') }}
                    </div>
                </div>
            </div>
            </template>

        </div>
    </StudentLayout>
</template>

<style scoped>
@media print {
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .docket-printable-container {
        border: 2px solid #000000 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: #ffffff !important;
    }
}
</style>
