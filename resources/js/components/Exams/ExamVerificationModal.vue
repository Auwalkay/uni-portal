<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    QrCode, ScanLine, Search, CheckCircle2, XCircle, Calendar, Clock, Building, ShieldCheck, UserCheck, AlertTriangle
} from 'lucide-vue-next';
import { format } from 'date-fns';

interface Props {
    open: boolean;
    selectedScheduleId?: string;
    schedules: any[];
    verifiedCandidate: any;
    isVerifying: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'verify', 'markAttendance']);

const internalTokenInput = ref('');
const internalSelectedScheduleId = ref(props.selectedScheduleId || '');

watch(() => props.selectedScheduleId, (newVal) => {
    internalSelectedScheduleId.value = newVal || '';
});

const isOpen = computed({
    get: () => props.open,
    set: (val: boolean) => emit('update:open', val)
});

const activeSelectedExam = computed(() => {
    if (!internalSelectedScheduleId.value) return null;
    return props.schedules.find(e => e.id === internalSelectedScheduleId.value) || null;
});

const isCandidateRegisteredForActiveExam = computed(() => {
    if (!props.verifiedCandidate || !activeSelectedExam.value) return null;
    const regCourseIds = props.verifiedCandidate.registered_course_ids || [];
    return regCourseIds.includes(activeSelectedExam.value.course_id);
});

const submitVerification = () => {
    if (!internalTokenInput.value.trim()) return;
    emit('verify', internalTokenInput.value.trim());
};

const handleMarkAttendance = (scheduleId: string, studentId: string) => {
    emit('markAttendance', scheduleId, studentId);
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[620px] max-h-[90vh] overflow-y-auto w-[95vw]">
            <DialogHeader class="border-b pb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <QrCode class="w-6 h-6" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg">Invigilator QR Verification & Attendance</DialogTitle>
                        <DialogDescription class="text-xs text-slate-500">
                            Scan QR code on student exam card or enter verification token / matric number.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="space-y-5 py-3">
                <!-- Invigilating Exam Paper Selection -->
                <div class="space-y-1.5 p-3 bg-purple-50/50 dark:bg-purple-950/20 rounded-lg border border-purple-100 dark:border-purple-900/40">
                    <Label class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300">Select Exam Paper (Invigilation Hall)</Label>
                    <select v-model="internalSelectedScheduleId" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs font-medium">
                        <option value="">All Scheduled Exams (Auto-Detect Registered Courses)</option>
                        <option v-for="exam in schedules" :key="exam.id" :value="exam.id">
                            {{ exam.course?.code }} - {{ exam.course?.title }} ({{ exam.venue }} &bull; {{ exam.exam_date ? format(new Date(exam.exam_date), 'MMM dd') : '' }} {{ exam.start_time }})
                        </option>
                    </select>
                </div>

                <!-- Scan / Input Form -->
                <form @submit.prevent="submitVerification" class="flex gap-2">
                    <div class="relative flex-1">
                        <Input 
                            v-model="internalTokenInput" 
                            placeholder="Scan QR token, type verification code or matric number..." 
                            class="pl-9 text-xs h-10 font-mono focus-visible:ring-emerald-500"
                            required
                        />
                        <ScanLine class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    </div>
                    <Button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white h-10 px-4 font-semibold text-xs gap-1.5" :disabled="isVerifying">
                        <Search class="w-4 h-4" />
                        <span>{{ isVerifying ? 'Verifying...' : 'Verify Candidate' }}</span>
                    </Button>
                </form>

                <!-- Verification Result Card -->
                <div v-if="verifiedCandidate" class="space-y-4 bg-slate-50 dark:bg-slate-900/60 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                    <!-- Candidate Header -->
                    <div class="flex items-start justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-full bg-purple-100 dark:bg-purple-950 flex items-center justify-center text-purple-700 font-bold text-lg border-2 border-purple-300 overflow-hidden shrink-0">
                                <img v-if="verifiedCandidate.student.passport_photo" :src="verifiedCandidate.student.passport_photo" alt="Candidate Photo" class="w-full h-full object-cover" />
                                <span v-else>{{ verifiedCandidate.student.name.substring(0, 2).toUpperCase() }}</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-slate-100 text-base">{{ verifiedCandidate.student.name }}</h3>
                                <p class="text-xs font-mono text-purple-600 dark:text-purple-400 font-semibold">{{ verifiedCandidate.student.matric_number }}</p>
                                <p class="text-[11px] text-slate-500">{{ verifiedCandidate.student.department }} &bull; {{ verifiedCandidate.student.level }} Level</p>
                            </div>
                        </div>

                        <!-- Clearance Badge -->
                        <div class="text-right shrink-0">
                            <Badge v-if="verifiedCandidate.is_cleared" class="bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/80 dark:text-emerald-300 px-3 py-1 text-xs gap-1 font-semibold">
                                <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                                <span>ELIGIBLE / FEE CLEARED</span>
                            </Badge>
                            <Badge v-else variant="destructive" class="px-3 py-1 text-xs gap-1 font-semibold">
                                <XCircle class="w-3.5 h-3.5" />
                                <span>UNCLEARED ({{ verifiedCandidate.pending_invoices }} Unpaid Invoice)</span>
                            </Badge>
                        </div>
                    </div>

                    <!-- Specific Exam Course Registration Status Banner -->
                    <div v-if="activeSelectedExam">
                        <div v-if="isCandidateRegisteredForActiveExam" class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 rounded-lg flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                                <div class="text-xs">
                                    <p class="font-bold text-emerald-900 dark:text-emerald-200">CONFIRMED: Candidate is Registered for {{ activeSelectedExam.course?.code }}</p>
                                    <p class="text-emerald-700 dark:text-emerald-400 text-[11px]">{{ activeSelectedExam.course?.title }} ({{ activeSelectedExam.venue }})</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-3.5 bg-rose-50 dark:bg-rose-950/50 border-2 border-rose-400 rounded-lg flex items-center gap-3 text-rose-900 dark:text-rose-200">
                            <AlertTriangle class="w-6 h-6 text-rose-600 shrink-0" />
                            <div class="text-xs space-y-0.5">
                                <p class="font-bold text-rose-900 dark:text-rose-100 text-sm">NOT REGISTERED FOR THIS COURSE!</p>
                                <p>Candidate <strong>{{ verifiedCandidate.student.name }}</strong> is NOT registered for {{ activeSelectedExam.course?.code }} ({{ activeSelectedExam.course?.title }}). Student is ineligible to sit for this paper.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Schedules List -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Registered Courses & Attendance Log</h4>
                        
                        <div v-if="verifiedCandidate.schedules && verifiedCandidate.schedules.length > 0" class="space-y-2">
                            <div 
                                v-for="sched in verifiedCandidate.schedules" 
                                :key="sched.id"
                                :class="[
                                    'p-3 rounded-lg border flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-all',
                                    sched.id === internalSelectedScheduleId ? 'bg-purple-50/70 dark:bg-purple-950/40 border-purple-300 dark:border-purple-800 ring-2 ring-purple-500/20' : 'bg-white dark:bg-slate-900'
                                ]"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ sched.course?.code }}</span>
                                        <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">&bull; {{ sched.course?.title }}</span>
                                        <Badge v-if="sched.id === internalSelectedScheduleId" class="bg-purple-600 text-white text-[9px] px-1.5 py-0.2">SELECTED HALL EXAM</Badge>
                                    </div>
                                    <div class="flex items-center gap-3 text-[11px] text-slate-500 mt-1">
                                        <span class="flex items-center gap-1"><Calendar class="w-3 h-3 text-purple-500" /> {{ sched.exam_date ? format(new Date(sched.exam_date), 'MMM dd, yyyy') : 'N/A' }}</span>
                                        <span class="flex items-center gap-1"><Clock class="w-3 h-3 text-slate-400" /> {{ sched.start_time }} - {{ sched.end_time }}</span>
                                        <span class="flex items-center gap-1"><Building class="w-3 h-3 text-slate-400" /> {{ sched.venue }}</span>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <div v-if="sched.attendances && sched.attendances.length > 0" class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-3 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                        <ShieldCheck class="w-4 h-4 text-emerald-600" />
                                        <span>PRESENT / VERIFIED</span>
                                    </div>
                                    <Button 
                                        v-else 
                                        size="sm" 
                                        class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold gap-1.5"
                                        @click="handleMarkAttendance(sched.id, verifiedCandidate.student.id)"
                                    >
                                        <UserCheck class="w-3.5 h-3.5" />
                                        <span>Mark Present</span>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-4 bg-white dark:bg-slate-900 rounded-lg border text-center text-xs text-slate-500 italic">
                            Candidate has no registered courses scheduled for examination.
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="border-t pt-3">
                <Button variant="outline" @click="isOpen = false">Close</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
